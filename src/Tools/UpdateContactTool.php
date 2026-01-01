<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmContact;

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
        return 'Bearbeitet einen bestehenden Contact. RUF DIESES TOOL AUF, wenn der Nutzer einen Contact ändern möchte (Name, Status, etc.). Die Contact-ID ist erforderlich. Nutze "crm.contacts.GET" um Contacts zu finden.';
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
                    'description' => 'Optional: Neue akademische Titel-ID.'
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

            // Prüfe Zugriff (optional - kann überschrieben werden)
            $accessCheck = $this->checkAccess($contact, $context, function($model, $ctx) {
                // Custom Access-Check: Owner oder Team-Mitglied
                return $model->owned_by_user_id === $ctx->user->id || 
                       $model->team_id === $ctx->team?->id;
            });
            
            if ($accessCheck) {
                return $accessCheck;
            }

            // Update-Daten sammeln
            $updateData = [];

            $fields = ['first_name', 'last_name', 'middle_name', 'nickname', 'notes',
                      'salutation_id', 'academic_title_id', 'gender_id', 'language_id',
                      'contact_status_id', 'is_active'];

            foreach ($fields as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
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
                $updateData['owned_by_user_id'] = $arguments['owned_by_user_id'];
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

