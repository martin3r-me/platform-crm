<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CommsLog;
use Platform\Crm\Tools\Comms\Concerns\ResolvesCommsRootTeam;

class ListCommsLogsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.logs.GET';
    }

    public function getDescription(): string
    {
        return 'GET /comms/logs – Zeigt Comms-Logs (WhatsApp-Sends, Fehler etc.). Nützlich zur Fehlerdiagnose bei fehlgeschlagenen Template-Sends. Filter: channel_type, event, status, source, recipient.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Team-ID. Standard: aktuelles Team.',
                    ],
                    'channel_type' => [
                        'type' => 'string',
                        'description' => 'Optional: Kanaltyp filtern (z.B. "whatsapp", "email").',
                    ],
                    'event' => [
                        'type' => 'string',
                        'description' => 'Optional: Event filtern (z.B. "template_sent", "template_failed", "message_sent", "message_failed").',
                    ],
                    'status' => [
                        'type' => 'string',
                        'description' => 'Optional: Status filtern ("success", "error", "warning", "info").',
                    ],
                    'source' => [
                        'type' => 'string',
                        'description' => 'Optional: Quelle filtern (z.B. "auto_pilot", "manual_template", "inline_comms").',
                    ],
                    'recipient' => [
                        'type' => 'string',
                        'description' => 'Optional: Empfänger filtern (Telefonnummer oder E-Mail).',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveRootTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }

            $rootTeam = $resolved['team'];

            $query = CommsLog::query()
                ->where('team_id', $rootTeam->id);

            if (!empty($arguments['channel_type'])) {
                $query->where('channel_type', (string) $arguments['channel_type']);
            }
            if (!empty($arguments['event'])) {
                $query->where('event', (string) $arguments['event']);
            }
            if (!empty($arguments['status'])) {
                $query->where('status', (string) $arguments['status']);
            }
            if (!empty($arguments['source'])) {
                $query->where('source', (string) $arguments['source']);
            }
            if (!empty($arguments['recipient'])) {
                $query->where('recipient', 'like', '%' . (string) $arguments['recipient'] . '%');
            }

            $this->applyStandardFilters($query, $arguments, [
                'channel_type', 'event', 'status', 'source', 'recipient', 'channel_id', 'thread_id', 'message_id', 'triggered_by_user_id', 'created_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['summary', 'recipient', 'event', 'source']);
            $this->applyStandardSort($query, $arguments, ['created_at', 'event', 'status', 'channel_type'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);
            $logs = $result['data'];

            $items = $logs->map(fn (CommsLog $log) => [
                'id' => (int) $log->id,
                'channel_type' => (string) $log->channel_type,
                'channel_id' => $log->channel_id ? (int) $log->channel_id : null,
                'event' => (string) $log->event,
                'status' => (string) $log->status,
                'summary' => (string) $log->summary,
                'details' => $log->details,
                'recipient' => $log->recipient,
                'thread_id' => $log->thread_id ? (int) $log->thread_id : null,
                'message_id' => $log->message_id ? (int) $log->message_id : null,
                'triggered_by_user_id' => $log->triggered_by_user_id ? (int) $log->triggered_by_user_id : null,
                'source' => $log->source,
                'created_at' => $log->created_at?->toIso8601String(),
            ])->values()->toArray();

            return ToolResult::success([
                'logs' => $items,
                'count' => count($items),
                'pagination' => $result['pagination'],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Comms-Logs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['comms', 'logs', 'debugging'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
