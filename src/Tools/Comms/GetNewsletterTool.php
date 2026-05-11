<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class GetNewsletterTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletters.{id}.GET';
    }

    public function getDescription(): string
    {
        return 'GET /newsletters/{id} - Zeigt Details eines Newsletters inkl. Stats und Empfänger-Zusammenfassung.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Newsletter-ID.',
                ],
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

            $newsletter = CommsNewsletter::with(['createdByUser', 'channel', 'contactLists'])->find($id);
            if (!$newsletter) {
                return ToolResult::error('NOT_FOUND', 'Newsletter nicht gefunden.');
            }

            $teamId = $this->normalizeToRootTeamId(null, $context->user) ?? $context->team?->id;
            if ((int) $newsletter->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf diesen Newsletter.');
            }

            return ToolResult::success([
                'id' => $newsletter->id,
                'uuid' => $newsletter->uuid,
                'name' => $newsletter->name,
                'subject' => $newsletter->subject,
                'preheader' => $newsletter->preheader,
                'status' => $newsletter->status,
                'html_body' => $newsletter->html_body ? mb_substr($newsletter->html_body, 0, 500) . '...' : null,
                'channel' => $newsletter->channel ? [
                    'id' => $newsletter->channel->id,
                    'name' => $newsletter->channel->name,
                    'sender' => $newsletter->channel->sender_identifier,
                ] : null,
                'contact_lists' => $newsletter->contactLists->map(fn ($list) => [
                    'id' => $list->id,
                    'name' => $list->name,
                    'member_count' => $list->member_count,
                ])->values()->toArray(),
                'stats' => $newsletter->stats,
                'scheduled_at' => $newsletter->scheduled_at?->toIso8601String(),
                'sent_at' => $newsletter->sent_at?->toIso8601String(),
                'created_by' => $newsletter->createdByUser?->name,
                'created_at' => $newsletter->created_at->toIso8601String(),
                'updated_at' => $newsletter->updated_at->toIso8601String(),
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
            'tags' => ['crm', 'newsletter', 'detail'],
            'risk_level' => 'read',
        ];
    }
}
