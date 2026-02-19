<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmContact;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-Tool zum Aktualisieren mehrerer Contacts im CRM-Modul
 */
class BulkUpdateContactsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'crm.contacts.bulk.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /contacts/bulk - Aktualisiert mehrere Contacts gleichzeitig. Maximal 50 Contacts pro Aufruf. Jeder Eintrag im items-Array benötigt eine contact_id und die zu ändernden Felder.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'Array von Update-Objekten (max. 50). Jedes Objekt benötigt contact_id und die zu ändernden Felder.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'contact_id' => [
                                'type' => 'integer',
                                'description' => 'ID des zu aktualisierenden Contacts (ERFORDERLICH).'
                            ],
                            'first_name' => ['type' => 'string', 'description' => 'Optional: Neuer Vorname.'],
                            'last_name' => ['type' => 'string', 'description' => 'Optional: Neuer Nachname.'],
                            'middle_name' => ['type' => 'string', 'description' => 'Optional: Neuer zweiter Vorname.'],
                            'nickname' => ['type' => 'string', 'description' => 'Optional: Neuer Spitzname.'],
                            'birth_date' => ['type' => 'string', 'description' => 'Optional: Neues Geburtsdatum (YYYY-MM-DD). Leer/null zum Entfernen.'],
                            'notes' => ['type' => 'string', 'description' => 'Optional: Neue Notizen.'],
                            'salutation_id' => ['type' => 'integer', 'description' => 'Optional: Neue Anrede-ID.'],
                            'academic_title_id' => ['type' => 'integer', 'description' => 'Optional: Neue akademische Titel-ID.'],
                            'academic_title_confirm' => ['type' => 'boolean', 'description' => 'Bestätigung für academic_title_id.', 'default' => false],
                            'gender_id' => ['type' => 'integer', 'description' => 'Optional: Neue Geschlechts-ID.'],
                            'language_id' => ['type' => 'integer', 'description' => 'Optional: Neue Sprach-ID.'],
                            'contact_status_id' => ['type' => 'integer', 'description' => 'Optional: Neue Kontaktstatus-ID.'],
                            'owned_by_user_id' => ['type' => 'integer', 'description' => 'Optional: Neue Owner-User-ID.'],
                            'is_active' => ['type' => 'boolean', 'description' => 'Optional: Aktiv/Inaktiv Status.'],
                        ],
                        'required' => ['contact_id']
                    ]
                ]
            ],
            'required' => ['items']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $items = $arguments['items'] ?? [];
            if (empty($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items-Array darf nicht leer sein.');
            }
            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Contacts pro Bulk-Aufruf erlaubt.');
            }

            $updated = [];
            $errors = [];

            DB::transaction(function () use ($items, $context, &$updated, &$errors) {
                foreach ($items as $index => $item) {
                    try {
                        $contactId = $item['contact_id'] ?? null;
                        if (!$contactId || !is_numeric($contactId)) {
                            $errors[] = ['index' => $index, 'error' => 'contact_id ist erforderlich und muss numerisch sein.'];
                            continue;
                        }

                        $contact = CrmContact::find((int) $contactId);
                        if (!$contact) {
                            $errors[] = ['index' => $index, 'contact_id' => $contactId, 'error' => 'Contact nicht gefunden.'];
                            continue;
                        }

                        try {
                            Gate::forUser($context->user)->authorize('update', $contact);
                        } catch (AuthorizationException $e) {
                            $errors[] = ['index' => $index, 'contact_id' => $contactId, 'error' => 'Keine Berechtigung zum Bearbeiten.'];
                            continue;
                        }

                        $updateData = [];
                        $fields = ['first_name', 'last_name', 'middle_name', 'nickname', 'notes',
                                  'salutation_id', 'academic_title_id', 'gender_id', 'language_id',
                                  'contact_status_id', 'is_active'];

                        foreach ($fields as $field) {
                            if (isset($item[$field])) {
                                $v = $item[$field];
                                if (in_array($field, ['salutation_id', 'academic_title_id', 'gender_id', 'language_id', 'contact_status_id'], true)) {
                                    if ($v === 0 || $v === '0' || $v === '') { $v = null; }
                                }
                                $updateData[$field] = $v;
                            }
                        }

                        // Geburtsdatum
                        if (isset($item['birth_date'])) {
                            if (empty($item['birth_date'])) {
                                $updateData['birth_date'] = null;
                            } else {
                                try {
                                    $updateData['birth_date'] = \Carbon\Carbon::parse($item['birth_date'])->format('Y-m-d');
                                } catch (\Throwable $e) {
                                    $errors[] = ['index' => $index, 'contact_id' => $contactId, 'error' => 'Ungültiges Geburtsdatum-Format.'];
                                    continue;
                                }
                            }
                        }

                        // Owner
                        if (isset($item['owned_by_user_id'])) {
                            $owned = $item['owned_by_user_id'];
                            if ($owned === 0 || $owned === 1 || $owned === '0' || $owned === '1') { $owned = null; }
                            if ($owned !== null) {
                                $updateData['owned_by_user_id'] = $owned;
                            }
                        }

                        // Guard: akademischen Titel
                        if (array_key_exists('academic_title_id', $updateData) && $updateData['academic_title_id'] !== null) {
                            $confirm = (bool) ($item['academic_title_confirm'] ?? false);
                            if (!$confirm) {
                                $errors[] = ['index' => $index, 'contact_id' => $contactId, 'error' => 'academic_title_id ohne academic_title_confirm=true nicht erlaubt.'];
                                continue;
                            }
                        }

                        if (!empty($updateData)) {
                            $contact->update($updateData);
                            $contact->refresh();
                        }

                        $updated[] = [
                            'index' => $index,
                            'id' => $contact->id,
                            'full_name' => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = ['index' => $index, 'contact_id' => $item['contact_id'] ?? null, 'error' => $e->getMessage()];
                    }
                }
            });

            return ToolResult::success([
                'updated_count' => count($updated),
                'error_count' => count($errors),
                'total_requested' => count($items),
                'updated' => $updated,
                'errors' => $errors ?: null,
                'message' => count($updated) . ' von ' . count($items) . ' Contacts erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Aktualisieren von Contacts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'contact', 'bulk', 'update'],
            'risk_level' => 'write',
        ];
    }
}
