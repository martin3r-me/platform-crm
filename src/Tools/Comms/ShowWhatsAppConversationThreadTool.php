<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsWhatsAppConversationThread;
use Platform\Crm\Models\CommsWhatsAppMessage;
use Platform\Crm\Models\CommsWhatsAppThread;
use Platform\Crm\Tools\Comms\Concerns\ResolvesCommsRootTeam;

class ShowWhatsAppConversationThreadTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.wa_threads.SHOW';
    }

    public function getDescription(): string
    {
        return 'GET /comms/wa_threads/{id} – Einzelnen Pseudo-Thread (Konversations-Thread) mit paginierten Nachrichten laden. Standard: nur aktiver Thread. Für historische Threads: conversation_thread_id angeben.';
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
                'conversation_thread_id' => [
                    'type' => 'integer',
                    'description' => 'ERFORDERLICH: ID des Konversations-Threads. Nutze core.comms.wa_threads.GET um verfügbare Threads zu sehen.',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Optional: Maximale Anzahl Nachrichten (Standard: 50, Max: 200).',
                ],
                'offset' => [
                    'type' => 'integer',
                    'description' => 'Optional: Offset für Pagination (Standard: 0).',
                ],
                'include_attachments' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden Datei-Anhänge (ContextFiles) mit zurückgegeben.',
                ],
            ],
            'required' => ['conversation_thread_id'],
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

            $convThreadId = (int) ($arguments['conversation_thread_id'] ?? 0);
            if ($convThreadId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'conversation_thread_id ist erforderlich.');
            }

            $limit = min((int) ($arguments['limit'] ?? 50), 200);
            $offset = max((int) ($arguments['offset'] ?? 0), 0);
            $includeAttachments = (bool) ($arguments['include_attachments'] ?? false);

            // Find the conversation thread
            $convThread = CommsWhatsAppConversationThread::query()
                ->where('team_id', $rootTeam->id)
                ->whereKey($convThreadId)
                ->first();

            if (!$convThread) {
                return ToolResult::error('NOT_FOUND', 'Konversations-Thread nicht gefunden.');
            }

            // Verify access via WhatsApp thread -> channel
            $waThread = CommsWhatsAppThread::query()
                ->where('team_id', $rootTeam->id)
                ->whereKey($convThread->comms_whatsapp_thread_id)
                ->first();

            if (!$waThread) {
                return ToolResult::error('NOT_FOUND', 'WhatsApp-Thread nicht gefunden.');
            }

            $channel = CommsChannel::query()
                ->where('team_id', $rootTeam->id)
                ->whereKey($waThread->comms_channel_id)
                ->first();

            if (!$channel) {
                return ToolResult::error('NOT_FOUND', 'Channel nicht gefunden.');
            }

            if ($channel->visibility === 'private' && (int) $channel->created_by_user_id !== (int) $context->user->id) {
                if (!$this->isRootTeamAdmin($context, $rootTeam)) {
                    return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen privaten Kanal.');
                }
            }

            // Load messages for this conversation thread
            $totalCount = CommsWhatsAppMessage::query()
                ->where('conversation_thread_id', $convThreadId)
                ->count();

            $messages = CommsWhatsAppMessage::query()
                ->where('conversation_thread_id', $convThreadId)
                ->with('sentByUser:id,first_name,last_name,email')
                ->orderBy('created_at', 'asc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $items = $messages->map(function (CommsWhatsAppMessage $m) use ($includeAttachments) {
                $item = [
                    'id' => (int) $m->id,
                    'direction' => $m->direction,
                    'body' => $m->body,
                    'message_type' => $m->message_type,
                    'media_display_type' => $m->media_display_type,
                    'has_media' => $m->hasMedia(),
                    'status' => $m->status,
                    'sent_at' => $m->sent_at?->toIso8601String(),
                    'delivered_at' => $m->delivered_at?->toIso8601String(),
                    'read_at' => $m->read_at?->toIso8601String(),
                    'created_at' => $m->created_at?->toIso8601String(),
                ];

                if ($m->direction === 'outbound' && $m->sentByUser) {
                    $item['sent_by'] = [
                        'id' => (int) $m->sentByUser->id,
                        'name' => trim($m->sentByUser->first_name . ' ' . $m->sentByUser->last_name) ?: $m->sentByUser->email,
                    ];
                }

                if ($m->template_name) {
                    $item['template_name'] = $m->template_name;
                }

                if ($includeAttachments) {
                    $item['attachments'] = $m->attachments;
                }

                return $item;
            })->values()->toArray();

            return ToolResult::success([
                'conversation_thread' => [
                    'id' => (int) $convThread->id,
                    'uuid' => $convThread->uuid,
                    'label' => $convThread->label,
                    'is_active' => $convThread->isActive(),
                    'started_at' => $convThread->started_at?->toIso8601String(),
                    'ended_at' => $convThread->ended_at?->toIso8601String(),
                ],
                'whatsapp_thread' => [
                    'id' => (int) $waThread->id,
                    'remote_phone_number' => $waThread->remote_phone_number,
                ],
                'messages' => $items,
                'count' => count($items),
                'pagination' => [
                    'total' => $totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount,
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Konversations-Threads: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['comms', 'whatsapp', 'threads', 'conversation', 'detail'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
