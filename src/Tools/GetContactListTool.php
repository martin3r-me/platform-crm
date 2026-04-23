<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContactList;

class GetContactListTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact_list.GET';
    }

    public function getDescription(): string
    {
        return 'GET /contact-lists/{id} - Ruft eine einzelne Kontaktliste inkl. Member-Preview (erste 20 Kontakte) ab. REST-Parameter: contact_list_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'contact_list_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Kontaktliste (ERFORDERLICH). Nutze "crm.contact_lists.GET" um Listen zu finden.',
                ],
            ],
            'required' => ['contact_list_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $arguments['contact_list_id'] ?? null;
            if (!$id) {
                return ToolResult::error('VALIDATION_ERROR', 'contact_list_id ist erforderlich.');
            }

            $list = CrmContactList::with(['createdByUser', 'ownedByUser'])->find($id);

            if (!$list) {
                return ToolResult::error('NOT_FOUND', 'Die angegebene Kontaktliste wurde nicht gefunden.');
            }

            // Member preview: erste 20 Kontakte
            $memberPreview = $list->members()
                ->with(['contact.emailAddresses' => fn($q) => $q->where('is_primary', true)])
                ->limit(20)
                ->get()
                ->map(function ($member) {
                    $contact = $member->contact;
                    $primaryEmail = $contact?->emailAddresses->first();
                    return [
                        'contact_id' => $contact?->id,
                        'name' => trim(($contact?->first_name ?? '') . ' ' . ($contact?->last_name ?? '')),
                        'email' => $primaryEmail?->email_address,
                        'added_at' => $member->created_at?->toIso8601String(),
                        'notes' => $member->notes,
                    ];
                })->toArray();

            return ToolResult::success([
                'id' => $list->id,
                'uuid' => $list->uuid,
                'name' => $list->name,
                'description' => $list->description,
                'color' => $list->color,
                'is_active' => $list->is_active,
                'member_count' => $list->member_count,
                'created_by_user_id' => $list->created_by_user_id,
                'created_by' => $list->createdByUser?->name,
                'owned_by_user_id' => $list->owned_by_user_id,
                'owned_by' => $list->ownedByUser?->name,
                'team_id' => $list->team_id,
                'member_preview' => $memberPreview,
                'created_at' => $list->created_at?->toIso8601String(),
                'updated_at' => $list->updated_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Kontaktliste: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['crm', 'contact_list', 'get'],
            'read_only' => true,
            'risk_level' => 'safe',
        ];
    }
}
