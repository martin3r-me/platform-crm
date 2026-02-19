<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CommsWhatsAppConversationThread;
use Platform\Crm\Models\CommsWhatsAppThread;
use Platform\Crm\Tools\Comms\Concerns\ResolvesCommsRootTeam;

class ListWhatsAppContactsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.wa_contacts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /comms/wa_contacts – WhatsApp-Kontakte (Konversationspartner) auflisten. Zeigt Telefonnummer, verknüpften Kontaktnamen, letzte Aktivität und aktiven Thread-Label. Jeder Kontakt entspricht einem WhatsApp-Thread mit einer eindeutigen Telefonnummer.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Team-ID. Es wird auf das Root-Team aufgelöst.',
                    ],
                    'comms_channel_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Nur Kontakte eines bestimmten WhatsApp-Channels.',
                    ],
                    'is_unread' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Nur Kontakte mit ungelesenen (true) oder gelesenen (false) Nachrichten.',
                    ],
                    'has_contact' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Nur Kontakte mit (true) oder ohne (false) verknüpften CRM-Kontakt.',
                    ],
                ],
                'required' => [],
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

            $channelId = isset($arguments['comms_channel_id']) ? (int) $arguments['comms_channel_id'] : null;
            $isUnread = isset($arguments['is_unread']) ? (bool) $arguments['is_unread'] : null;
            $hasContact = isset($arguments['has_contact']) ? (bool) $arguments['has_contact'] : null;

            $query = CommsWhatsAppThread::query()
                ->where('team_id', $rootTeam->id)
                ->withCount('messages')
                ->with('contact');

            // Only show threads from accessible channels
            if ($channelId) {
                $query->where('comms_channel_id', $channelId);
            } else {
                $query->whereHas('channel', function ($q) use ($context, $rootTeam) {
                    $q->where('type', 'whatsapp')
                        ->where('is_active', true)
                        ->where(function ($q2) use ($context) {
                            $q2->where('visibility', 'team')
                                ->orWhere('created_by_user_id', $context->user->id);
                        });
                });
            }

            if ($isUnread !== null) {
                $query->where('is_unread', $isUnread);
            }

            if ($hasContact === true) {
                $query->whereNotNull('contact_type')->whereNotNull('contact_id');
            } elseif ($hasContact === false) {
                $query->where(function ($q) {
                    $q->whereNull('contact_type')->orWhereNull('contact_id');
                });
            }

            $this->applyStandardFilters($query, $arguments, [
                'remote_phone_number', 'is_unread', 'updated_at', 'created_at',
                'last_inbound_at', 'last_outbound_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['remote_phone_number', 'last_message_preview']);
            $this->applyStandardSort($query, $arguments, [
                'updated_at', 'created_at', 'last_inbound_at', 'last_outbound_at',
            ], 'updated_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);
            $threads = $result['data'];

            $items = $threads->map(function (CommsWhatsAppThread $t) {
                $lastIsInbound = $t->last_inbound_at && (!$t->last_outbound_at || $t->last_inbound_at->greaterThanOrEqualTo($t->last_outbound_at));
                $lastAt = $lastIsInbound ? $t->last_inbound_at : ($t->last_outbound_at ?: $t->updated_at);

                $activeConvThread = CommsWhatsAppConversationThread::findActiveForThread($t->id);

                $contactName = null;
                if ($t->contact) {
                    $contactName = method_exists($t->contact, 'getDisplayName')
                        ? $t->contact->getDisplayName()
                        : (trim(($t->contact->first_name ?? '') . ' ' . ($t->contact->last_name ?? '')) ?: ($t->contact->name ?? null));
                }

                return [
                    'id' => (int) $t->id,
                    'comms_channel_id' => (int) $t->comms_channel_id,
                    'remote_phone_number' => (string) $t->remote_phone_number,
                    'contact_name' => $contactName,
                    'contact_type' => $t->contact_type,
                    'contact_id' => $t->contact_id ? (int) $t->contact_id : null,
                    'is_unread' => (bool) $t->is_unread,
                    'messages_count' => (int) ($t->messages_count ?? 0),
                    'last_message_preview' => $t->last_message_preview,
                    'last_direction' => $t->messages_count > 0 ? ($lastIsInbound ? 'inbound' : 'outbound') : null,
                    'last_activity_at' => $lastAt?->toIso8601String(),
                    'active_thread_label' => $activeConvThread?->label,
                    'active_thread_id' => $activeConvThread ? (int) $activeConvThread->id : null,
                    'window_open' => $t->isWindowOpen(),
                    'updated_at' => $t->updated_at?->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'contacts' => $items,
                'count' => count($items),
                'pagination' => $result['pagination'],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der WhatsApp-Kontakte: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['comms', 'whatsapp', 'contacts'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
