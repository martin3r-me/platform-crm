<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Models\CrmContactListMember;

class AddContactListMembersTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact_list.members.POST';
    }

    public function getDescription(): string
    {
        return 'POST /contact-lists/{id}/members - Fügt Kontakte zu einer Kontaktliste hinzu (dedupliziert). Required: contact_list_id, contact_ids (array).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'contact_list_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Kontaktliste (ERFORDERLICH).',
                ],
                'contact_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'Array von Contact-IDs, die hinzugefügt werden sollen (ERFORDERLICH).',
                ],
            ],
            'required' => ['contact_list_id', 'contact_ids'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $listId = $arguments['contact_list_id'] ?? null;
            if (!$listId) {
                return ToolResult::error('VALIDATION_ERROR', 'contact_list_id ist erforderlich.');
            }

            $contactIds = $arguments['contact_ids'] ?? [];
            if (empty($contactIds) || !is_array($contactIds)) {
                return ToolResult::error('VALIDATION_ERROR', 'contact_ids muss ein nicht-leeres Array von IDs sein.');
            }

            $list = CrmContactList::find($listId);
            if (!$list) {
                return ToolResult::error('NOT_FOUND', 'Die angegebene Kontaktliste wurde nicht gefunden.');
            }

            // Existing members for dedup
            $existingContactIds = CrmContactListMember::where('contact_list_id', $listId)
                ->whereIn('contact_id', $contactIds)
                ->pluck('contact_id')
                ->toArray();

            $added = 0;
            $skipped = 0;

            foreach ($contactIds as $contactId) {
                if (in_array((int)$contactId, $existingContactIds)) {
                    $skipped++;
                    continue;
                }

                CrmContactListMember::create([
                    'contact_list_id' => $listId,
                    'contact_id' => (int)$contactId,
                    'added_by_user_id' => $context->user->id,
                ]);
                $added++;
            }

            $list->updateMemberCount();

            return ToolResult::success([
                'added' => $added,
                'skipped' => $skipped,
                'total' => $list->fresh()->member_count,
                'message' => "{$added} Kontakt(e) hinzugefügt, {$skipped} übersprungen (bereits vorhanden).",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Hinzufügen von Mitgliedern: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'contact_list', 'members', 'add'],
            'read_only' => false,
            'risk_level' => 'low',
        ];
    }
}
