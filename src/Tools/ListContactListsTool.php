<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class ListContactListsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.contact_lists.GET';
    }

    public function getDescription(): string
    {
        return 'GET /contact-lists?team_id={id}&filters=[...]&search=...&sort=[...] - Listet Kontaktlisten auf. REST-Parameter: team_id (optional), filters (optional), search (optional), sort (optional), limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Team-ID. Wenn nicht angegeben, wird das aktuelle Team verwendet.'
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Filter nach aktiv/inaktiv Status.'
                    ],
                    'owned_by_user_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Besitzer (User-ID).'
                    ],
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $teamIdArg = $arguments['team_id'] ?? null;
            if ($teamIdArg === 0 || $teamIdArg === '0') {
                $teamIdArg = null;
            }

            $teamIdArg = $this->normalizeToRootTeamId(
                is_numeric($teamIdArg) ? (int)$teamIdArg : null,
                $context->user
            ) ?? $context->team?->id;

            if (!$teamIdArg) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }

            if (!$this->userHasAccessToCrmRootTeam($context->user, (int)$teamIdArg)) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamIdArg}.");
            }

            $query = CrmContactList::query()
                ->where('team_id', $teamIdArg)
                ->with(['createdByUser', 'ownedByUser']);

            $this->applyStandardFilters($query, $arguments, [
                'name', 'description', 'is_active', 'owned_by_user_id', 'created_at', 'updated_at'
            ]);

            if (isset($arguments['is_active'])) {
                $query->where('is_active', $arguments['is_active']);
            }

            if (!empty($arguments['owned_by_user_id'])) {
                $query->where('owned_by_user_id', $arguments['owned_by_user_id']);
            }

            $this->applyStandardSorting($query, $arguments, 'name', 'asc');
            $result = $this->applyStandardPaginationResult($query, $arguments);

            $lists = $result['data']->map(function ($list) {
                return [
                    'id' => $list->id,
                    'uuid' => $list->uuid,
                    'name' => $list->name,
                    'description' => $list->description,
                    'color' => $list->color,
                    'is_active' => $list->is_active,
                    'member_count' => $list->member_count,
                    'created_by' => $list->createdByUser?->name,
                    'owned_by' => $list->ownedByUser?->name,
                    'owned_by_user_id' => $list->owned_by_user_id,
                    'created_at' => $list->created_at->toIso8601String(),
                    'updated_at' => $list->updated_at->toIso8601String(),
                ];
            })->toArray();

            return ToolResult::success([
                'contact_lists' => $lists,
                'pagination' => $result['pagination'],
                'team_id' => $teamIdArg,
                'message' => count($lists) . ' Kontaktliste(n) gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auflisten der Kontaktlisten: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['crm', 'contact_list', 'list'],
            'risk_level' => 'read',
        ];
    }
}
