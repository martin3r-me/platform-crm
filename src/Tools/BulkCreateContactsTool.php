<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\NormalizesLookupIds;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactRelation;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-Tool zum Erstellen mehrerer Contacts im CRM-Modul
 */
class BulkCreateContactsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;
    use NormalizesLookupIds;

    public function getName(): string
    {
        return 'crm.contacts.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /contacts/bulk - Erstellt mehrere Contacts gleichzeitig. Maximal 50 Contacts pro Aufruf. Jeder Eintrag im items-Array entspricht dem Schema von crm.contacts.POST. WICHTIG: Lookup/FK-IDs niemals raten.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID für alle Contacts. Wenn nicht angegeben, wird das aktuelle Team verwendet.'
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'Array von Contact-Objekten (max. 50). Jedes Objekt hat die gleichen Felder wie crm.contacts.POST.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'first_name' => [
                                'type' => 'string',
                                'description' => 'Vorname (ERFORDERLICH, wenn last_name nicht angegeben).'
                            ],
                            'last_name' => [
                                'type' => 'string',
                                'description' => 'Nachname (ERFORDERLICH, wenn first_name nicht angegeben).'
                            ],
                            'middle_name' => ['type' => 'string', 'description' => 'Optional: Zweiter Vorname.'],
                            'nickname' => ['type' => 'string', 'description' => 'Optional: Spitzname.'],
                            'birth_date' => ['type' => 'string', 'description' => 'Optional: Geburtsdatum (YYYY-MM-DD).'],
                            'notes' => ['type' => 'string', 'description' => 'Optional: Notizen.'],
                            'salutation_id' => ['type' => 'integer', 'description' => 'Optional: Anrede-ID (nur mit salutation_confirm=true).'],
                            'salutation_confirm' => ['type' => 'boolean', 'description' => 'Bestätigung für salutation_id.', 'default' => false],
                            'academic_title_id' => ['type' => 'integer', 'description' => 'Optional: Akademischer Titel-ID (nur mit academic_title_confirm=true).'],
                            'academic_title_confirm' => ['type' => 'boolean', 'description' => 'Bestätigung für academic_title_id.', 'default' => false],
                            'gender_id' => ['type' => 'integer', 'description' => 'Optional: Geschlechts-ID (nur mit gender_confirm=true).'],
                            'gender_confirm' => ['type' => 'boolean', 'description' => 'Bestätigung für gender_id.', 'default' => false],
                            'language_id' => ['type' => 'integer', 'description' => 'Optional: Sprach-ID (nur mit language_confirm=true).'],
                            'language_confirm' => ['type' => 'boolean', 'description' => 'Bestätigung für language_id.', 'default' => false],
                            'contact_status_id' => ['type' => 'integer', 'description' => 'Optional: Kontaktstatus-ID (nur mit contact_status_confirm=true).'],
                            'contact_status_confirm' => ['type' => 'boolean', 'description' => 'Bestätigung für contact_status_id.', 'default' => false],
                            'owned_by_user_id' => ['type' => 'integer', 'description' => 'Optional: Owner-User-ID.'],
                            'is_active' => ['type' => 'boolean', 'description' => 'Optional: Aktiv-Status. Standard: true.'],
                            'company_id' => ['type' => 'integer', 'description' => 'Optional: Company-ID für Verknüpfung.'],
                            'company_relation_type_id' => ['type' => 'integer', 'description' => 'Optional: Beziehungstyp-ID (erforderlich wenn company_id gesetzt).'],
                            'company_relation_position' => ['type' => 'string', 'description' => 'Optional: Position in der Company.'],
                        ],
                        'required' => []
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

            // Team bestimmen (global für alle Items)
            $requestedTeamId = $arguments['team_id'] ?? null;
            $teamId = $this->normalizeToRootTeamId(
                is_numeric($requestedTeamId) ? (int)$requestedTeamId : null,
                $context->user
            ) ?? $context->team?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }

            if (!$this->userHasAccessToCrmRootTeam($context->user, (int)$teamId)) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamId}.");
            }

            try {
                Gate::forUser($context->user)->authorize('create', CrmContact::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Contacts erstellen (Policy).');
            }

            $created = [];
            $errors = [];

            DB::transaction(function () use ($items, $teamId, $context, &$created, &$errors) {
                foreach ($items as $index => $item) {
                    try {
                        $item = $this->normalizeLookupIds($item, [
                            'salutation_id', 'academic_title_id', 'gender_id', 'language_id', 'contact_status_id',
                        ]);

                        if (empty($item['first_name']) && empty($item['last_name'])) {
                            $errors[] = ['index' => $index, 'error' => 'Vorname oder Nachname ist erforderlich.'];
                            continue;
                        }

                        // Owner bestimmen
                        $ownedByUserId = $item['owned_by_user_id'] ?? null;
                        if ($ownedByUserId === 1 || $ownedByUserId === 0 || $ownedByUserId === '1' || $ownedByUserId === '0') {
                            $ownedByUserId = null;
                        }
                        if (!$ownedByUserId) {
                            $ownedByUserId = $context->user->id;
                        }

                        $warnings = [];

                        // Guard: Lookup-Confirm-Checks
                        if (($item['academic_title_id'] ?? null) !== null && !((bool)($item['academic_title_confirm'] ?? false))) {
                            $item['academic_title_id'] = null;
                            $warnings[] = 'academic_title_id ohne Bestätigung ignoriert.';
                        }
                        if (($item['salutation_id'] ?? null) !== null && !((bool)($item['salutation_confirm'] ?? false))) {
                            $item['salutation_id'] = null;
                            $warnings[] = 'salutation_id ohne Bestätigung ignoriert.';
                        }
                        if (($item['gender_id'] ?? null) !== null && !((bool)($item['gender_confirm'] ?? false))) {
                            $item['gender_id'] = null;
                            $warnings[] = 'gender_id ohne Bestätigung ignoriert.';
                        }
                        if (($item['language_id'] ?? null) !== null && !((bool)($item['language_confirm'] ?? false))) {
                            $item['language_id'] = null;
                            $warnings[] = 'language_id ohne Bestätigung ignoriert.';
                        }
                        if (($item['contact_status_id'] ?? null) !== null && !((bool)($item['contact_status_confirm'] ?? false))) {
                            $item['contact_status_id'] = null;
                            $warnings[] = 'contact_status_id ohne Bestätigung ignoriert.';
                        }

                        // Geburtsdatum parsen
                        $birthDate = null;
                        if (!empty($item['birth_date'])) {
                            try {
                                $birthDate = \Carbon\Carbon::parse($item['birth_date'])->format('Y-m-d');
                            } catch (\Throwable $e) {
                                $errors[] = ['index' => $index, 'error' => 'Ungültiges Geburtsdatum-Format.'];
                                continue;
                            }
                        }

                        $contact = CrmContact::create([
                            'first_name' => $item['first_name'] ?? null,
                            'last_name' => $item['last_name'] ?? null,
                            'team_id' => $teamId,
                            'created_by_user_id' => $context->user->id,
                            'owned_by_user_id' => $ownedByUserId,
                            'middle_name' => $item['middle_name'] ?? null,
                            'nickname' => $item['nickname'] ?? null,
                            'birth_date' => $birthDate,
                            'notes' => $item['notes'] ?? null,
                            'salutation_id' => $item['salutation_id'] ?? null,
                            'academic_title_id' => $item['academic_title_id'] ?? null,
                            'gender_id' => $item['gender_id'] ?? null,
                            'language_id' => $item['language_id'] ?? null,
                            'contact_status_id' => $item['contact_status_id'] ?? null,
                            'is_active' => $item['is_active'] ?? true,
                        ]);

                        // Optional: Company-Relation
                        $companyId = $item['company_id'] ?? null;
                        $relationTypeId = $item['company_relation_type_id'] ?? null;
                        if (!empty($companyId) && !empty($relationTypeId)) {
                            CrmContactRelation::create([
                                'contact_id' => $contact->id,
                                'company_id' => (int) $companyId,
                                'relation_type_id' => (int) $relationTypeId,
                                'position' => $item['company_relation_position'] ?? null,
                                'is_primary' => true,
                                'is_active' => true,
                            ]);
                        } elseif (!empty($companyId) && empty($relationTypeId)) {
                            $warnings[] = 'company_id ohne company_relation_type_id ignoriert.';
                        }

                        $created[] = [
                            'index' => $index,
                            'id' => $contact->id,
                            'first_name' => $contact->first_name,
                            'last_name' => $contact->last_name,
                            'full_name' => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
                            'warnings' => $warnings ?: null,
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = ['index' => $index, 'error' => $e->getMessage()];
                    }
                }
            });

            return ToolResult::success([
                'created_count' => count($created),
                'error_count' => count($errors),
                'total_requested' => count($items),
                'created' => $created,
                'errors' => $errors ?: null,
                'message' => count($created) . ' von ' . count($items) . ' Contacts erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Erstellen von Contacts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'contact', 'bulk', 'create'],
            'risk_level' => 'write',
        ];
    }
}
