<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class ListNewslettersTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletters.GET';
    }

    public function getDescription(): string
    {
        return 'GET /newsletters?team_id={id}&status=...&search=...&sort=[...] - Listet Newsletter/Kampagnen auf. REST-Parameter: team_id (optional), status (optional: draft/scheduled/sending/sent/cancelled), search (optional), sort (optional), limit/offset (optional).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Team-ID.',
                    ],
                    'status' => [
                        'type' => 'string',
                        'enum' => ['draft', 'scheduled', 'sending', 'sent', 'cancelled'],
                        'description' => 'Optional: Filter nach Status.',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $teamId = $this->normalizeToRootTeamId(
                isset($arguments['team_id']) ? (int) $arguments['team_id'] : null,
                $context->user
            ) ?? $context->team?->id;

            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben.');
            }

            if (!$this->userHasAccessToCrmRootTeam($context->user, $teamId)) {
                return ToolResult::error('ACCESS_DENIED', "Kein Zugriff auf Team-ID {$teamId}.");
            }

            $query = CommsNewsletter::query()
                ->where('team_id', $teamId)
                ->with(['createdByUser', 'contactList', 'channel']);

            if (!empty($arguments['status'])) {
                $query->where('status', $arguments['status']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'name', 'subject', 'status', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSorting($query, $arguments, 'created_at', 'desc');
            $result = $this->applyStandardPaginationResult($query, $arguments);

            $newsletters = $result['data']->map(fn ($nl) => [
                'id' => $nl->id,
                'uuid' => $nl->uuid,
                'name' => $nl->name,
                'subject' => $nl->subject,
                'status' => $nl->status,
                'contact_list' => $nl->contactList?->name,
                'channel' => $nl->channel?->name,
                'stats' => $nl->stats,
                'scheduled_at' => $nl->scheduled_at?->toIso8601String(),
                'sent_at' => $nl->sent_at?->toIso8601String(),
                'created_by' => $nl->createdByUser?->name,
                'created_at' => $nl->created_at->toIso8601String(),
            ])->toArray();

            return ToolResult::success([
                'newsletters' => $newsletters,
                'pagination' => $result['pagination'],
                'team_id' => $teamId,
                'message' => count($newsletters) . ' Newsletter gefunden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['crm', 'newsletter', 'list'],
            'risk_level' => 'read',
        ];
    }
}
