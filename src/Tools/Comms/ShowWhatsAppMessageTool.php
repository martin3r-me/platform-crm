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

class ShowWhatsAppMessageTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.wa_messages.SHOW';
    }

    public function getDescription(): string
    {
        return 'GET /comms/wa_messages/{id} – Einzelne WhatsApp-Nachricht mit vollständigen Metadaten laden (Zustellstatus, Lesebestätigung, Anhänge, Template-Info).';
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
                'message_id' => [
                    'type' => 'integer',
                    'description' => 'ERFORDERLICH: ID der WhatsApp-Nachricht.',
                ],
            ],
            'required' => ['message_id'],
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

            $messageId = (int) ($arguments['message_id'] ?? 0);
            if ($messageId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'message_id ist erforderlich.');
            }

            $message = CommsWhatsAppMessage::query()
                ->whereKey($messageId)
                ->with(['sentByUser:id,first_name,last_name,email', 'thread', 'conversationThread'])
                ->first();

            if (!$message) {
                return ToolResult::error('NOT_FOUND', 'Nachricht nicht gefunden.');
            }

            // Verify team access via thread
            $thread = $message->thread;
            if (!$thread || (int) $thread->team_id !== (int) $rootTeam->id) {
                return ToolResult::error('NOT_FOUND', 'Nachricht nicht gefunden.');
            }

            // Verify channel access
            $channel = CommsChannel::query()
                ->where('team_id', $rootTeam->id)
                ->whereKey($thread->comms_channel_id)
                ->first();

            if (!$channel) {
                return ToolResult::error('NOT_FOUND', 'Channel nicht gefunden.');
            }

            if ($channel->visibility === 'private' && (int) $channel->created_by_user_id !== (int) $context->user->id) {
                if (!$this->isRootTeamAdmin($context, $rootTeam)) {
                    return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen privaten Kanal.');
                }
            }

            $item = [
                'id' => (int) $message->id,
                'thread_id' => (int) $message->comms_whatsapp_thread_id,
                'conversation_thread_id' => $message->conversation_thread_id ? (int) $message->conversation_thread_id : null,
                'direction' => $message->direction,
                'body' => $message->body,
                'message_type' => $message->message_type,
                'media_display_type' => $message->media_display_type,
                'has_media' => $message->hasMedia(),
                'meta_message_id' => $message->meta_message_id,
                'status' => $message->status,
                'status_updated_at' => $message->status_updated_at?->toIso8601String(),
                'sent_at' => $message->sent_at?->toIso8601String(),
                'delivered_at' => $message->delivered_at?->toIso8601String(),
                'read_at' => $message->read_at?->toIso8601String(),
                'created_at' => $message->created_at?->toIso8601String(),
                'updated_at' => $message->updated_at?->toIso8601String(),
            ];

            // Sender info
            if ($message->direction === 'outbound' && $message->sentByUser) {
                $item['sent_by'] = [
                    'id' => (int) $message->sentByUser->id,
                    'name' => trim($message->sentByUser->first_name . ' ' . $message->sentByUser->last_name) ?: $message->sentByUser->email,
                ];
            }

            // Template info
            if ($message->template_name) {
                $item['template_name'] = $message->template_name;
                $item['template_params'] = $message->template_params;
            }

            // Attachments
            $item['attachments'] = $message->attachments;

            // Conversation thread info
            if ($message->conversationThread) {
                $item['conversation_thread'] = [
                    'id' => (int) $message->conversationThread->id,
                    'label' => $message->conversationThread->label,
                    'is_active' => $message->conversationThread->isActive(),
                ];
            }

            // Thread context
            $item['thread'] = [
                'id' => (int) $thread->id,
                'remote_phone_number' => $thread->remote_phone_number,
                'comms_channel_id' => (int) $thread->comms_channel_id,
            ];

            return ToolResult::success(['message' => $item]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der WhatsApp-Nachricht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['comms', 'whatsapp', 'messages', 'detail'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
