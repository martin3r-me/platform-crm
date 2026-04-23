<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Models\CrmContactListMember;

class ListContactListMembersTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'crm.contact_list.members.GET';
    }

    public function getDescription(): string
    {
        return 'GET /contact-lists/{id}/members - Listet Mitglieder einer Kontaktliste auf. REST-Parameter: contact_list_id (required), search (optional), sort (optional), limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'contact_list_id' => [
                        'type' => 'integer',
                        'description' => 'ID der Kontaktliste (ERFORDERLICH).',
                    ],
                ],
                'required' => ['contact_list_id'],
            ]
        );
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

            $list = CrmContactList::find($listId);
            if (!$list) {
                return ToolResult::error('NOT_FOUND', 'Die angegebene Kontaktliste wurde nicht gefunden.');
            }

            $query = CrmContactListMember::query()
                ->where('contact_list_id', $listId)
                ->with([
                    'contact.emailAddresses' => fn($q) => $q->where('is_primary', true),
                    'contact.phoneNumbers' => fn($q) => $q->where('is_primary', true),
                    'addedByUser',
                ]);

            // Search on contact fields
            if (!empty($arguments['search'])) {
                $search = $arguments['search'];
                $query->whereHas('contact', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('nickname', 'like', "%{$search}%");
                });
            }

            $this->applyStandardSorting($query, $arguments, 'created_at', 'asc');
            $result = $this->applyStandardPaginationResult($query, $arguments);

            $members = $result['data']->map(function ($member) {
                $contact = $member->contact;
                $primaryEmail = $contact?->emailAddresses->first();
                $primaryPhone = $contact?->phoneNumbers->first();
                return [
                    'member_id' => $member->id,
                    'contact_id' => $contact?->id,
                    'contact_uuid' => $contact?->uuid,
                    'first_name' => $contact?->first_name,
                    'last_name' => $contact?->last_name,
                    'full_name' => trim(($contact?->first_name ?? '') . ' ' . ($contact?->last_name ?? '')),
                    'email' => $primaryEmail?->email_address,
                    'phone' => $primaryPhone?->international,
                    'is_active' => $contact?->is_active,
                    'notes' => $member->notes,
                    'added_by' => $member->addedByUser?->name,
                    'added_at' => $member->created_at?->toIso8601String(),
                ];
            })->toArray();

            return ToolResult::success([
                'contact_list_id' => $list->id,
                'contact_list_name' => $list->name,
                'members' => $members,
                'pagination' => $result['pagination'],
                'message' => count($members) . ' Mitglied(er) gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auflisten der Mitglieder: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['crm', 'contact_list', 'members', 'list'],
            'risk_level' => 'read',
        ];
    }
}
