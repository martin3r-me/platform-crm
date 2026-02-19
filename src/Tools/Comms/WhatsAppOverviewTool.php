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

class WhatsAppOverviewTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.wa_overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /comms/wa_overview – WhatsApp-Übersicht: Ungelesene Nachrichten, aktive Threads, letzte Aktivitäten, Kanal-Status. Team-scoped Dashboard für schnellen Überblick.';
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
            ],
            'required' => [],
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

            // Get accessible WhatsApp channels
            $channels = CommsChannel::query()
                ->where('team_id', $rootTeam->id)
                ->where('type', 'whatsapp')
                ->where('is_active', true)
                ->where(function ($q) use ($context) {
                    $q->where('visibility', 'team')
                        ->orWhere('created_by_user_id', $context->user->id);
                })
                ->get();

            $channelIds = $channels->pluck('id')->toArray();

            if (empty($channelIds)) {
                return ToolResult::success([
                    'summary' => [
                        'channels_count' => 0,
                        'total_contacts' => 0,
                        'unread_contacts' => 0,
                        'active_threads' => 0,
                        'messages_today' => 0,
                        'messages_this_week' => 0,
                    ],
                    'channels' => [],
                    'unread_contacts' => [],
                    'recent_activity' => [],
                    'available_tools' => $this->getAvailableTools(),
                ]);
            }

            // Count threads/contacts
            $totalContacts = CommsWhatsAppThread::query()
                ->where('team_id', $rootTeam->id)
                ->whereIn('comms_channel_id', $channelIds)
                ->count();

            $unreadContacts = CommsWhatsAppThread::query()
                ->where('team_id', $rootTeam->id)
                ->whereIn('comms_channel_id', $channelIds)
                ->where('is_unread', true)
                ->count();

            // Count active conversation threads
            $activeConvThreads = CommsWhatsAppConversationThread::query()
                ->where('team_id', $rootTeam->id)
                ->whereNull('ended_at')
                ->count();

            // Messages today and this week
            $today = now()->startOfDay();
            $weekStart = now()->startOfWeek();

            $messagesToday = CommsWhatsAppMessage::query()
                ->whereHas('thread', function ($q) use ($rootTeam, $channelIds) {
                    $q->where('team_id', $rootTeam->id)
                        ->whereIn('comms_channel_id', $channelIds);
                })
                ->where('created_at', '>=', $today)
                ->count();

            $messagesThisWeek = CommsWhatsAppMessage::query()
                ->whereHas('thread', function ($q) use ($rootTeam, $channelIds) {
                    $q->where('team_id', $rootTeam->id)
                        ->whereIn('comms_channel_id', $channelIds);
                })
                ->where('created_at', '>=', $weekStart)
                ->count();

            // Unread contacts (top 10)
            $unreadThreads = CommsWhatsAppThread::query()
                ->where('team_id', $rootTeam->id)
                ->whereIn('comms_channel_id', $channelIds)
                ->where('is_unread', true)
                ->with('contact')
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();

            $unreadItems = $unreadThreads->map(function (CommsWhatsAppThread $t) {
                $contactName = null;
                if ($t->contact) {
                    $contactName = method_exists($t->contact, 'getDisplayName')
                        ? $t->contact->getDisplayName()
                        : (trim(($t->contact->first_name ?? '') . ' ' . ($t->contact->last_name ?? '')) ?: ($t->contact->name ?? null));
                }

                return [
                    'thread_id' => (int) $t->id,
                    'remote_phone_number' => $t->remote_phone_number,
                    'contact_name' => $contactName,
                    'last_message_preview' => $t->last_message_preview,
                    'last_activity_at' => $t->last_activity_at?->toIso8601String(),
                ];
            })->values()->toArray();

            // Recent activity (last 10 messages across all threads)
            $recentMessages = CommsWhatsAppMessage::query()
                ->whereHas('thread', function ($q) use ($rootTeam, $channelIds) {
                    $q->where('team_id', $rootTeam->id)
                        ->whereIn('comms_channel_id', $channelIds);
                })
                ->with(['thread:id,remote_phone_number', 'sentByUser:id,first_name,last_name'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $recentActivity = $recentMessages->map(function (CommsWhatsAppMessage $m) {
                $item = [
                    'message_id' => (int) $m->id,
                    'thread_id' => (int) $m->comms_whatsapp_thread_id,
                    'remote_phone_number' => $m->thread?->remote_phone_number,
                    'direction' => $m->direction,
                    'body_preview' => mb_substr($m->body ?? '', 0, 100),
                    'message_type' => $m->message_type,
                    'status' => $m->status,
                    'created_at' => $m->created_at?->toIso8601String(),
                ];

                if ($m->direction === 'outbound' && $m->sentByUser) {
                    $item['sent_by'] = trim($m->sentByUser->first_name . ' ' . $m->sentByUser->last_name);
                }

                return $item;
            })->values()->toArray();

            // Channel info
            $channelInfo = $channels->map(function (CommsChannel $c) use ($rootTeam) {
                $threadCount = CommsWhatsAppThread::query()
                    ->where('team_id', $rootTeam->id)
                    ->where('comms_channel_id', $c->id)
                    ->count();

                return [
                    'id' => (int) $c->id,
                    'name' => $c->name,
                    'sender_identifier' => $c->sender_identifier,
                    'visibility' => $c->visibility,
                    'contacts_count' => $threadCount,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'summary' => [
                    'channels_count' => count($channelIds),
                    'total_contacts' => $totalContacts,
                    'unread_contacts' => $unreadContacts,
                    'active_threads' => $activeConvThreads,
                    'messages_today' => $messagesToday,
                    'messages_this_week' => $messagesThisWeek,
                ],
                'channels' => $channelInfo,
                'unread_contacts' => $unreadItems,
                'recent_activity' => $recentActivity,
                'available_tools' => $this->getAvailableTools(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der WhatsApp-Übersicht: ' . $e->getMessage());
        }
    }

    private function getAvailableTools(): array
    {
        return [
            'contacts' => [
                'list' => 'core.comms.wa_contacts.GET',
                'show' => 'core.comms.wa_contacts.SHOW',
            ],
            'threads' => [
                'list' => 'core.comms.wa_threads.GET',
                'create' => 'core.comms.wa_threads.POST',
                'show' => 'core.comms.wa_threads.SHOW',
            ],
            'messages' => [
                'list' => 'core.comms.whatsapp_messages.GET',
                'send' => 'core.comms.whatsapp_messages.POST',
                'show' => 'core.comms.wa_messages.SHOW',
                'search' => 'core.comms.wa_messages.search',
            ],
            'typical_flows' => [
                [
                    'name' => 'WhatsApp-Nachricht senden (neuer Kontakt)',
                    'steps' => [
                        '1) core.comms.channels.GET { type: "whatsapp" } → WhatsApp-Channel wählen',
                        '2) core.comms.whatsapp_messages.POST { comms_channel_id: <id>, to: "+491751234567", body: "Hallo!" }',
                        '3) Optional: core.comms.wa_threads.POST { thread_id: <id>, label: "Erstkontakt" } → Pseudo-Thread starten',
                    ],
                ],
                [
                    'name' => 'WhatsApp-Nachricht senden (bestehender Kontakt)',
                    'steps' => [
                        '1) core.comms.wa_contacts.GET → Kontakt finden',
                        '2) core.comms.whatsapp_messages.POST { thread_id: <id>, body: "Nachricht" }',
                    ],
                ],
                [
                    'name' => 'Konversation nach Thema organisieren',
                    'steps' => [
                        '1) core.comms.wa_threads.POST { thread_id: <id>, label: "Neues Thema" } → Neuen Pseudo-Thread starten',
                        '2) Neue Nachrichten werden automatisch dem aktiven Thread zugeordnet',
                        '3) core.comms.wa_threads.GET { thread_id: <id> } → Alle Threads des Kontakts sehen',
                    ],
                ],
            ],
        ];
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'utility',
            'tags' => ['comms', 'whatsapp', 'overview', 'dashboard'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
