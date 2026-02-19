<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsWhatsAppConversationThread;
use Platform\Crm\Models\CommsWhatsAppThread;
use Platform\Crm\Tools\Comms\Concerns\ResolvesCommsRootTeam;

class ShowWhatsAppContactTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.wa_contacts.SHOW';
    }

    public function getDescription(): string
    {
        return 'GET /comms/wa_contacts/{id} – Einzelnen WhatsApp-Kontakt mit aktivem Thread, Thread-History und Kontaktdetails laden. Gibt den aktuellen Konversations-Thread und eine Übersicht vergangener Threads zurück.';
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
                'thread_id' => [
                    'type' => 'integer',
                    'description' => 'ERFORDERLICH: WhatsApp-Thread-ID (= Kontakt-ID).',
                ],
                'include_messages' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Wenn true, werden die letzten Nachrichten des aktiven Threads mitgeliefert (max. 20). Standard: false.',
                ],
            ],
            'required' => ['thread_id'],
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

            $threadId = (int) ($arguments['thread_id'] ?? 0);
            if ($threadId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'thread_id ist erforderlich.');
            }

            $includeMessages = (bool) ($arguments['include_messages'] ?? false);

            $thread = CommsWhatsAppThread::query()
                ->where('team_id', $rootTeam->id)
                ->whereKey($threadId)
                ->withCount('messages')
                ->with('contact')
                ->first();

            if (!$thread) {
                return ToolResult::error('NOT_FOUND', 'WhatsApp-Kontakt nicht gefunden.');
            }

            // Verify channel access
            $channel = CommsChannel::query()
                ->where('team_id', $rootTeam->id)
                ->whereKey($thread->comms_channel_id)
                ->first();

            if (!$channel) {
                return ToolResult::error('NOT_FOUND', 'Channel zum Thread nicht gefunden.');
            }

            if ($channel->visibility === 'private' && (int) $channel->created_by_user_id !== (int) $context->user->id) {
                if (!$this->isRootTeamAdmin($context, $rootTeam)) {
                    return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen privaten Kanal.');
                }
            }

            // Contact details
            $contactInfo = null;
            if ($thread->contact) {
                $contact = $thread->contact;
                $contactName = method_exists($contact, 'getDisplayName')
                    ? $contact->getDisplayName()
                    : (trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')) ?: ($contact->name ?? null));

                $contactInfo = [
                    'type' => $thread->contact_type,
                    'id' => (int) $thread->contact_id,
                    'name' => $contactName,
                ];
            }

            // Active conversation thread
            $activeConvThread = CommsWhatsAppConversationThread::findActiveForThread($thread->id);

            // Thread history (all conversation threads)
            $convThreads = CommsWhatsAppConversationThread::query()
                ->where('comms_whatsapp_thread_id', $thread->id)
                ->withCount('messages')
                ->orderByDesc('started_at')
                ->get();

            $threadHistory = $convThreads->map(function (CommsWhatsAppConversationThread $ct) {
                return [
                    'id' => (int) $ct->id,
                    'label' => $ct->label,
                    'is_active' => $ct->isActive(),
                    'started_at' => $ct->started_at?->toIso8601String(),
                    'ended_at' => $ct->ended_at?->toIso8601String(),
                    'messages_count' => (int) ($ct->messages_count ?? 0),
                    'created_by_user_id' => $ct->created_by_user_id ? (int) $ct->created_by_user_id : null,
                ];
            })->values()->toArray();

            $lastIsInbound = $thread->last_inbound_at && (!$thread->last_outbound_at || $thread->last_inbound_at->greaterThanOrEqualTo($thread->last_outbound_at));
            $lastAt = $lastIsInbound ? $thread->last_inbound_at : ($thread->last_outbound_at ?: $thread->updated_at);

            $result = [
                'contact' => [
                    'id' => (int) $thread->id,
                    'comms_channel_id' => (int) $thread->comms_channel_id,
                    'remote_phone_number' => (string) $thread->remote_phone_number,
                    'is_unread' => (bool) $thread->is_unread,
                    'messages_count' => (int) ($thread->messages_count ?? 0),
                    'last_message_preview' => $thread->last_message_preview,
                    'last_direction' => $thread->messages_count > 0 ? ($lastIsInbound ? 'inbound' : 'outbound') : null,
                    'last_activity_at' => $lastAt?->toIso8601String(),
                    'window_open' => $thread->isWindowOpen(),
                    'window_expires_at' => $thread->windowExpiresAt()?->toIso8601String(),
                    'context_model' => $thread->context_model,
                    'context_model_id' => $thread->context_model_id ? (int) $thread->context_model_id : null,
                    'crm_contact' => $contactInfo,
                    'created_at' => $thread->created_at?->toIso8601String(),
                    'updated_at' => $thread->updated_at?->toIso8601String(),
                ],
                'active_thread' => $activeConvThread ? [
                    'id' => (int) $activeConvThread->id,
                    'label' => $activeConvThread->label,
                    'started_at' => $activeConvThread->started_at?->toIso8601String(),
                    'messages_count' => $activeConvThread->messages()->count(),
                ] : null,
                'thread_history' => $threadHistory,
                'thread_count' => count($threadHistory),
            ];

            // Optionally include recent messages from active thread
            if ($includeMessages && $activeConvThread) {
                $messages = $thread->messages()
                    ->where('conversation_thread_id', $activeConvThread->id)
                    ->with('sentByUser:id,first_name,last_name,email')
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get()
                    ->reverse()
                    ->values();

                $result['recent_messages'] = $messages->map(function ($m) {
                    $item = [
                        'id' => (int) $m->id,
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
            }

            return ToolResult::success($result);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des WhatsApp-Kontakts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['comms', 'whatsapp', 'contacts', 'detail'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
