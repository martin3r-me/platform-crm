<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CommsNewsletterTemplate;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class ListNewsletterTemplatesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletter_templates.GET';
    }

    public function getDescription(): string
    {
        return 'GET /newsletter-templates - Listet Newsletter-Vorlagen auf. Optional: category, is_active (bool), search, sort, limit/offset.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => ['type' => 'integer', 'description' => 'Optional: Filter nach Team-ID.'],
                    'category' => ['type' => 'string', 'description' => 'Optional: Filter nach Kategorie.'],
                    'is_active' => ['type' => 'boolean', 'description' => 'Optional: Nur aktive/inaktive Vorlagen.'],
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

            $query = CommsNewsletterTemplate::query()
                ->where('team_id', $teamId)
                ->with('createdByUser');

            if (!empty($arguments['category'])) {
                $query->where('category', $arguments['category']);
            }

            if (isset($arguments['is_active'])) {
                $query->where('is_active', (bool) $arguments['is_active']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'name', 'category', 'created_at', 'updated_at',
            ]);
            $this->applyStandardSorting($query, $arguments, 'created_at', 'desc');
            $result = $this->applyStandardPaginationResult($query, $arguments);

            $templates = $result['data']->map(fn ($tpl) => [
                'id' => $tpl->id,
                'uuid' => $tpl->uuid,
                'name' => $tpl->name,
                'description' => $tpl->description,
                'category' => $tpl->category,
                'is_active' => $tpl->is_active,
                'default_subject' => $tpl->default_subject,
                'created_by' => $tpl->createdByUser?->name,
                'created_at' => $tpl->created_at->toIso8601String(),
            ])->toArray();

            return ToolResult::success([
                'templates' => $templates,
                'pagination' => $result['pagination'],
                'team_id' => $teamId,
                'message' => count($templates) . ' Vorlagen gefunden.',
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
            'tags' => ['crm', 'newsletter', 'template', 'list'],
            'risk_level' => 'read',
        ];
    }
}
