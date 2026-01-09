<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmContact;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von Contacts im CRM-Modul
 */
class UpdateContactTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'crm.contacts.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /contacts/{id} - Aktualisiert einen bestehenden Contact. REST-Parameter: id (required, integer) - Contact-ID. first_name (optional, string) - Vorname. last_name (optional, string) - Nachname. company_id (optional, integer) - zugehörige Company-ID. email (optional, string) - E-Mail. phone (optional, string) - Telefon. is_active (optional, boolean) - Status.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'contact_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu bearbeitenden Contacts (ERFORDERLICH). Nutze "crm.contacts.GET" um Contacts zu finden.'
                ],
                'first_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Vorname.'
                ],
                'last_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Nachname.'
                ],
                'middle_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer zweiter Vorname.'
                ],
                'nickname' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Spitzname.'
                ],
                'birth_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Geburtsdatum im Format YYYY-MM-DD oder ISO 8601. Setze auf null oder leeren String, um das Datum zu entfernen.'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Notizen.'
                ],
                'salutation_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Anrede-ID.'
                ],
                'academic_title_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue akademische Titel-ID. WICHTIG: Setze dies nur, wenn der Titel explizit genannt wurde. Niemals "raten" (z.B. nicht automatisch 1/Dr.). Setze auf 0/null/leeren String, um den Titel zu entfernen.'
                ],
                'academic_title_confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass der akademische Titel wirklich gesetzt werden soll. ERFORDERLICH, wenn academic_title_id auf einen nicht-leeren Wert gesetzt wird.',
                    'default' => false
                ],
                'gender_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Geschlechts-ID.'
                ],
                'language_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Sprach-ID.'
                ],
                'contact_status_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Kontaktstatus-ID.'
                ],
                'owned_by_user_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Owner-User-ID.'
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Aktiv/Inaktiv Status.'
                ]
            ],
            'required' => ['contact_id']
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'contact_id',
                CrmContact::class,
                'CONTACT_NOT_FOUND',
                'Der angegebene Contact wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $contact = $validation['model'];

            // Policy wie Planner: update
            try {
                Gate::forUser($context->user)->authorize('update', $contact);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diesen Contact nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            $fields = ['first_name', 'last_name', 'middle_name', 'nickname', 'notes',
                      'salutation_id', 'academic_title_id', 'gender_id', 'language_id',
                      'contact_status_id', 'is_active'];

            foreach ($fields as $field) {
                if (isset($arguments[$field])) {
                    $v = $arguments[$field];
                    // FK-IDs: 0/"0" ist KEIN gültiger FK-Wert → als null behandeln (verhindert FK-Constraint Errors)
                    if (in_array($field, ['salutation_id', 'academic_title_id', 'gender_id', 'language_id', 'contact_status_id'], true)) {
                        if ($v === 0 || $v === '0' || $v === '') { $v = null; }
                    }
                    $updateData[$field] = $v;
                }
            }

            // Geburtsdatum parsen
            if (isset($arguments['birth_date'])) {
                if (empty($arguments['birth_date'])) {
                    $updateData['birth_date'] = null;
                } else {
                    try {
                        $updateData['birth_date'] = \Carbon\Carbon::parse($arguments['birth_date'])->format('Y-m-d');
                    } catch (\Throwable $e) {
                        return ToolResult::error('VALIDATION_ERROR', 'Ungültiges Geburtsdatum-Format. Verwende YYYY-MM-DD.');
                    }
                }
            }

            if (isset($arguments['owned_by_user_id'])) {
                $owned = $arguments['owned_by_user_id'];
                if ($owned === 0 || $owned === 1 || $owned === '0' || $owned === '1') { $owned = null; }
                // null bedeutet: Owner nicht ändern (wir setzen kein "owned_by_user_id = null" automatisch)
                if ($owned !== null) {
                    $updateData['owned_by_user_id'] = $owned;
                }
            }

            // Guard: akademischen Titel nie "raten" – nur setzen, wenn explizit bestätigt.
            // (Titel entfernen ist immer erlaubt ohne confirm.)
            if (array_key_exists('academic_title_id', $updateData) && $updateData['academic_title_id'] !== null) {
                $confirm = (bool) ($arguments['academic_title_confirm'] ?? false);
                if (!$confirm) {
                    return ToolResult::error(
                        'VALIDATION_ERROR',
                        'academic_title_id soll gesetzt werden, ist aber nicht bestätigt. Bitte setze academic_title_confirm: true (oder lasse academic_title_id weg).'
                    );
                }
            }

            // Contact aktualisieren
            if (!empty($updateData)) {
                $contact->update($updateData);
            }

            // Aktualisierten Contact laden
            $contact->refresh();
            $contact->load(['salutation', 'academicTitle', 'gender', 'language', 'contactStatus', 'createdByUser', 'ownedByUser']);

            return ToolResult::success([
                'id' => $contact->id,
                'uuid' => $contact->uuid,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'full_name' => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
                'nickname' => $contact->nickname,
                'birth_date' => $contact->birth_date?->toDateString(),
                'salutation' => $contact->salutation?->name,
                'academic_title' => $contact->academicTitle?->name,
                'gender' => $contact->gender?->name,
                'language' => $contact->language?->name,
                'contact_status' => $contact->contactStatus?->name,
                'is_active' => $contact->is_active,
                'owned_by' => $contact->ownedByUser?->name,
                'updated_at' => $contact->updated_at->toIso8601String(),
                'message' => "Contact '{$contact->first_name} {$contact->last_name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Contacts: ' . $e->getMessage());
        }
    }
}

