<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmContactList;

class DeleteContactListTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'crm.contact_list.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /contact-lists/{id} - Löscht eine Kontaktliste (Soft-Delete). REST-Parameter: contact_list_id (required).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'contact_list_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Kontaktliste (ERFORDERLICH).',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung bei Listen mit Mitgliedern.',
                ],
            ],
            'required' => ['contact_list_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'contact_list_id',
                CrmContactList::class,
                'NOT_FOUND',
                'Die angegebene Kontaktliste wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $list = $validation['model'];

            if ($list->member_count > 0 && !($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', "Die Liste '{$list->name}' hat {$list->member_count} Mitglied(er). Bitte bestätige die Löschung mit 'confirm: true'.");
            }

            $listName = $list->name;
            $listId = $list->id;

            $list->delete();

            return ToolResult::success([
                'contact_list_id' => $listId,
                'name' => $listName,
                'message' => "Kontaktliste '{$listName}' wurde gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Kontaktliste: ' . $e->getMessage());
        }
    }
}
