<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsWhatsAppMessage;
use Platform\Crm\Models\CommsWhatsAppThread;
use Platform\Crm\Tools\Comms\Concerns\ResolvesCommsRootTeam;

class SearchWhatsAppMessagesTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.wa_messages.search';
    }

    public function getDescription(): string
    {
        return 'POST /comms/wa_messages/search – Volltextsuche über WhatsApp-Nachrichten. Kann thread-übergreifend (alle Kontakte) oder auf einen bestimmten Thread/Konversations-Thread beschränkt werden. Unterstützt Zeitraum- und Richtungsfilter.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Es wird auf das Root-Team aufgelöst.',
                ],
                'query' => [
                    'type' => 'string',
                    'description' => 'ERFORDERLICH: Suchbegriff für die Volltextsuche in Nachrichtentexten.',
                ],
                'thread_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Auf einen bestimmten WhatsApp-Thread (Kontakt) beschränken.',
                ],
                'conversation_thread_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Auf einen bestimmten Konversations-Thread (Pseudo-Thread) beschränken.',
                ],
                'direction' => [
                    'type' => 'string',
                    'enum' => ['inbound', 'outbound'],
                    'description' => 'Optional: Nur eingehende (inbound) oder ausgehende (outbound) Nachrichten.',
                ],
                'from_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Nachrichten ab diesem Datum (ISO 8601, z.B. "2026-01-01").',
                ],
                'to_date' => [
                    'type' => 'string',
                    'description' => 'Optional: Nachrichten bis zu diesem Datum (ISO 8601, z.B. "2026-02-28").',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximale Anzahl Ergebnisse (Standard: 50, Max: 200).',
                ],
                'offset' => [
                    'type' => 'integer',
                    'description' => 'Optional: Offset für Pagination (Standard: 0).',
                ],
            ],
            'required' => ['query'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveRootTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $rootTeam = $resolved['team'];

            $searchQuery = trim((string) ($arguments['query'] ?? ''));
            if ($searchQuery === '') {
                return ToolResult::error('VALIDATION_ERROR', 'query (Suchbegriff) ist erforderlich.');
            }

            $threadId = isset($arguments['thread_id']) ? (int) $arguments['thread_id'] : null;
            $convThreadId = isset($arguments['conversation_thread_id']) ? (int) $arguments['conversation_thread_id'] : null;
            $direction = isset($arguments['direction']) ? (string) $arguments['direction'] : null;
            $fromDate = isset($arguments['from_date']) ? (string) $arguments['from_date'] : null;
            $toDate = isset($arguments['to_date']) ? (string) $arguments['to_date'] : null;
            $limit = min((int) ($arguments['limit'] ?? 50), 200);
            $offset = max((int) ($arguments['offset'] ?? 0), 0);

            // Build query - join with threads for team scoping and channel access
            $query = CommsWhatsAppMessage::query()
                ->whereHas('thread', function ($q) use ($rootTeam, $context, $threadId) {
                    $q->where('team_id', $rootTeam->id);

                    if ($threadId) {
                        $q->where('id', $threadId);
                    }

                    // Only messages from accessible channels
                    $q->whereHas('channel', function ($cq) use ($context) {
                        $cq->where('type', 'whatsapp')
                            ->where('is_active', true)
                            ->where(function ($cq2) use ($context) {
                                $cq2->where('visibility', 'team')
                                    ->orWhere('created_by_user_id', $context->user->id);
                            });
                    });
                })
                ->with(['thread:id,remote_phone_number,comms_channel_id', 'sentByUser:id,first_name,last_name,email']);

            // Fulltext search on body
            $query->where('body', 'LIKE', '%' . str_replace(['%', '_'], ['\\%', '\\_'], $searchQuery) . '%');

            // Filter by conversation thread
            if ($convThreadId) {
                $query->where('conversation_thread_id', $convThreadId);
            }

            // Filter by direction
            if ($direction && in_array($direction, ['inbound', 'outbound'])) {
                $query->where('direction', $direction);
            }

            // Filter by date range
            if ($fromDate) {
                $query->where('created_at', '>=', $fromDate);
            }
            if ($toDate) {
                $query->where('created_at', '<=', $toDate . ' 23:59:59');
            }

            // Get total count before pagination
            $totalCount = $query->count();

            // Apply pagination and ordering
            $messages = $query
                ->orderByDesc('created_at')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $items = $messages->map(function (CommsWhatsAppMessage $m) {
                $item = [
                    'id' => (int) $m->id,
                    'thread_id' => (int) $m->comms_whatsapp_thread_id,
                    'conversation_thread_id' => $m->conversation_thread_id ? (int) $m->conversation_thread_id : null,
                    'remote_phone_number' => $m->thread?->remote_phone_number,
                    'direction' => $m->direction,
                    'body' => $m->body,
                    'message_type' => $m->message_type,
                    'status' => $m->status,
                    'created_at' => $m->created_at?->toIso8601String(),
                ];

                if ($m->direction === 'outbound' && $m->sentByUser) {
                    $item['sent_by'] = trim($m->sentByUser->first_name . ' ' . $m->sentByUser->last_name) ?: $m->sentByUser->email;
                }

                return $item;
            })->values()->toArray();

            return ToolResult::success([
                'query' => $searchQuery,
                'results' => $items,
                'count' => count($items),
                'pagination' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount,
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler bei der Nachrichtensuche: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['comms', 'whatsapp', 'messages', 'search', 'fulltext'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
