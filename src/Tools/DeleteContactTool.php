<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmContact;

/**
 * Tool zum Löschen von Contacts im CRM-Modul
 */
class DeleteContactTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'crm.contacts.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /contacts/{id} - Löscht einen Contact. REST-Parameter: id (required, integer) - Contact-ID. Hinweis: Beim Löschen werden auch alle zugehörigen Beziehungen gelöscht.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'contact_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zu löschenden Contacts (ERFORDERLICH). Nutze "crm.contacts.GET" um Contacts zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass der Contact wirklich gelöscht werden soll. Frage den Nutzer explizit nach Bestätigung, wenn der Contact wichtig erscheint oder viele Details hat.'
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

            // Prüfe Zugriff (nur Owner kann löschen)
            $accessCheck = $this->checkAccess($contact, $context, function($model, $ctx) {
                return $model->owned_by_user_id === $ctx->user->id;
            });
            
            if ($accessCheck) {
                return $accessCheck;
            }

            // Bestätigung prüfen (wenn Contact wichtig erscheint)
            $isImportant = !empty($contact->notes) || !empty($contact->contactRelations);
            if ($isImportant && !($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', "Der Contact '{$contact->first_name} {$contact->last_name}' scheint wichtig zu sein (hat Notizen oder Beziehungen). Bitte bestätige die Löschung mit 'confirm: true'.");
            }

            $contactName = trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? ''));
            $contactId = $contact->id;

            // Contact löschen
            $contact->delete();

            return ToolResult::success([
                'contact_id' => $contactId,
                'contact_name' => $contactName,
                'message' => "Contact '{$contactName}' wurde erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Contacts: ' . $e->getMessage());
        }
    }
}

