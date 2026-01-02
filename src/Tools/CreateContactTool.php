<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContact;

/**
 * Tool zum Erstellen von Contacts im CRM-Modul
 */
class CreateContactTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contacts.POST';
    }

    public function getDescription(): string
    {
        return 'POST /contacts - Erstellt einen neuen Contact. REST-Parameter: first_name (optional, string) - Vorname. last_name (optional, string) - Nachname. Mindestens einer der beiden ist erforderlich. team_id (optional, integer) - wenn nicht angegeben, wird aktuelles Team verwendet. company_id (optional, integer) - zugehörige Company-ID. email (optional, string) - E-Mail. phone (optional, string) - Telefon. is_active (optional, boolean) - Status.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'first_name' => [
                    'type' => 'string',
                    'description' => 'Vorname des Contacts (ERFORDERLICH, wenn last_name nicht angegeben). Frage den Nutzer explizit nach dem Namen, wenn er nicht angegeben wurde.'
                ],
                'last_name' => [
                    'type' => 'string',
                    'description' => 'Nachname des Contacts (ERFORDERLICH, wenn first_name nicht angegeben). Frage den Nutzer explizit nach dem Namen, wenn er nicht angegeben wurde.'
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Teams, in dem der Contact erstellt werden soll. Wenn nicht angegeben, wird das aktuelle Team aus dem Kontext verwendet.'
                ],
                'middle_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Zweiter Vorname.'
                ],
                'nickname' => [
                    'type' => 'string',
                    'description' => 'Optional: Spitzname.'
                ],
                'birth_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Geburtsdatum im Format YYYY-MM-DD oder ISO 8601.'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Notizen zum Contact.'
                ],
                'salutation_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID der Anrede. Frage nach, wenn der Nutzer eine Anrede angibt.'
                ],
                'academic_title_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des akademischen Titels. Frage nach, wenn der Nutzer einen Titel angibt.'
                ],
                'gender_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Geschlechts. Frage nach, wenn der Nutzer ein Geschlecht angibt.'
                ],
                'language_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID der Sprache. Frage nach, wenn der Nutzer eine Sprache angibt.'
                ],
                'contact_status_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Kontaktstatus. Frage nach, wenn der Nutzer einen Status angibt.'
                ],
                'owned_by_user_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Users, der den Contact besitzt. Wenn nicht angegeben, wird automatisch der aktuelle Nutzer verwendet. Verwende NIEMALS hardcoded IDs wie 1 oder 0.'
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der Contact aktiv ist. Standard: true.'
                ],
                'company_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID der Company, mit der der Contact verknüpft werden soll. Nutze "crm.companies.GET" um Companies zu finden.'
                ]
            ],
            'required' => []
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Validierung
            if (empty($arguments['first_name']) && empty($arguments['last_name'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Vorname oder Nachname ist erforderlich.');
            }

            // Team bestimmen
            $teamId = $arguments['team_id'] ?? $context->team?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden. Nutze das Tool "core.teams.GET" um alle verfügbaren Teams zu sehen.');
            }

            // Owner bestimmen (behandle 1/0 als null)
            $ownedByUserId = $arguments['owned_by_user_id'] ?? null;
            if ($ownedByUserId === 1 || $ownedByUserId === 0) {
                $ownedByUserId = null;
            }
            if (!$ownedByUserId) {
                $ownedByUserId = $context->user->id;
            }

            // Geburtsdatum parsen
            $birthDate = null;
            if (!empty($arguments['birth_date'])) {
                try {
                    $birthDate = \Carbon\Carbon::parse($arguments['birth_date'])->format('Y-m-d');
                } catch (\Throwable $e) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiges Geburtsdatum-Format. Verwende YYYY-MM-DD.');
                }
            }

            // Contact erstellen
            $contact = CrmContact::create([
                'first_name' => $arguments['first_name'] ?? null,
                'last_name' => $arguments['last_name'] ?? null,
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
                'owned_by_user_id' => $ownedByUserId,
                'middle_name' => $arguments['middle_name'] ?? null,
                'nickname' => $arguments['nickname'] ?? null,
                'birth_date' => $birthDate,
                'notes' => $arguments['notes'] ?? null,
                'salutation_id' => $arguments['salutation_id'] ?? null,
                'academic_title_id' => $arguments['academic_title_id'] ?? null,
                'gender_id' => $arguments['gender_id'] ?? null,
                'language_id' => $arguments['language_id'] ?? null,
                'contact_status_id' => $arguments['contact_status_id'] ?? null,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            // Company-Verknüpfung erstellen (falls angegeben)
            if (!empty($arguments['company_id'])) {
                \Platform\Crm\Models\CrmContactRelation::create([
                    'contact_id' => $contact->id,
                    'company_id' => $arguments['company_id'],
                ]);
            }

            // Beziehungen laden
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
                'created_at' => $contact->created_at->toIso8601String(),
                'message' => "Contact '{$contact->first_name} {$contact->last_name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Contacts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'contact', 'create'],
            'risk_level' => 'write',
        ];
    }
}

