<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class GetNewsletterStatsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletters.stats.GET';
    }

    public function getDescription(): string
    {
        return 'GET /newsletters/{id}/stats - Zeigt detaillierte Versandstatistiken eines Newsletters.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Newsletter-ID.'],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $id = (int) ($arguments['id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $newsletter = CommsNewsletter::find($id);
            if (!$newsletter) {
                return ToolResult::error('NOT_FOUND', 'Newsletter nicht gefunden.');
            }

            $teamId = $this->normalizeToRootTeamId(null, $context->user) ?? $context->team?->id;
            if ((int) $newsletter->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff.');
            }

            // Refresh stats from DB
            $newsletter->updateStats();
            $newsletter->refresh();

            $stats = $newsletter->stats ?? [];
            $total = max($stats['total'] ?? 0, 1);

            return ToolResult::success([
                'newsletter_id' => $newsletter->id,
                'name' => $newsletter->name,
                'status' => $newsletter->status,
                'stats' => $stats,
                'rates' => [
                    'delivery_rate' => round((($stats['delivered'] ?? 0) / $total) * 100, 1) . '%',
                    'open_rate' => round((($stats['opened'] ?? 0) / $total) * 100, 1) . '%',
                    'click_rate' => round((($stats['clicked'] ?? 0) / $total) * 100, 1) . '%',
                    'bounce_rate' => round((($stats['bounced'] ?? 0) / $total) * 100, 1) . '%',
                    'unsubscribe_rate' => round((($stats['unsubscribed'] ?? 0) / $total) * 100, 1) . '%',
                ],
                'sent_at' => $newsletter->sent_at?->toIso8601String(),
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
            'tags' => ['crm', 'newsletter', 'stats'],
            'risk_level' => 'read',
        ];
    }
}
