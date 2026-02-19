<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsWhatsAppConversationThread;
use Platform\Crm\Models\CommsWhatsAppThread;
use Platform\Crm\Tools\Comms\Concerns\ResolvesCommsRootTeam;

class ListWhatsAppConversationThreadsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.wa_threads.GET';
    }

    public function getDescription(): string
    {
        return 'GET /comms/wa_threads – Alle Pseudo-Threads (Konversations-Threads) eines WhatsApp-Kontakts auflisten. Zeigt aktive und archivierte Threads mit Zeitraum, Label und Nachrichtenanzahl. Benötigt thread_id (= WhatsApp-Kontakt).';
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
                    'thread_id' => [
                        'type' => 'integer',
                        'description' => 'ERFORDERLICH: WhatsApp-Thread-ID (= Kontakt). Nutze core.comms.wa_contacts.GET um Kontakte zu finden.',
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Nur aktive (true) oder archivierte/geschlossene (false) Threads.',
                    ],
                ],
                'required' => ['thread_id'],
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

            $threadId = (int) ($arguments['thread_id'] ?? 0);
            if ($threadId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'thread_id ist erforderlich.');
            }

            $isActive = isset($arguments['is_active']) ? (bool) $arguments['is_active'] : null;

            // Verify access to the WhatsApp thread
            $waThread = CommsWhatsAppThread::query()
                ->where('team_id', $rootTeam->id)
                ->whereKey($threadId)
                ->first();

            if (!$waThread) {
                return ToolResult::error('NOT_FOUND', 'WhatsApp-Thread nicht gefunden.');
            }

            $channel = CommsChannel::query()
                ->where('team_id', $rootTeam->id)
                ->whereKey($waThread->comms_channel_id)
                ->first();

            if (!$channel) {
                return ToolResult::error('NOT_FOUND', 'Channel zum Thread nicht gefunden.');
            }

            if ($channel->visibility === 'private' && (int) $channel->created_by_user_id !== (int) $context->user->id) {
                if (!$this->isRootTeamAdmin($context, $rootTeam)) {
                    return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen privaten Kanal.');
                }
            }

            $query = CommsWhatsAppConversationThread::query()
                ->where('comms_whatsapp_thread_id', $threadId)
                ->withCount('messages')
                ->with('createdBy:id,first_name,last_name,email');

            if ($isActive === true) {
                $query->whereNull('ended_at');
            } elseif ($isActive === false) {
                $query->whereNotNull('ended_at');
            }

            $this->applyStandardFilters($query, $arguments, [
                'label', 'started_at', 'ended_at', 'created_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['label']);
            $this->applyStandardSort($query, $arguments, [
                'started_at', 'ended_at', 'created_at',
            ], 'started_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);
            $convThreads = $result['data'];

            $items = $convThreads->map(function (CommsWhatsAppConversationThread $ct) {
                $createdByName = null;
                if ($ct->createdBy) {
                    $createdByName = trim($ct->createdBy->first_name . ' ' . $ct->createdBy->last_name) ?: $ct->createdBy->email;
                }

                return [
                    'id' => (int) $ct->id,
                    'uuid' => $ct->uuid,
                    'label' => $ct->label,
                    'is_active' => $ct->isActive(),
                    'started_at' => $ct->started_at?->toIso8601String(),
                    'ended_at' => $ct->ended_at?->toIso8601String(),
                    'messages_count' => (int) ($ct->messages_count ?? 0),
                    'created_by' => $createdByName,
                    'created_by_user_id' => $ct->created_by_user_id ? (int) $ct->created_by_user_id : null,
                    'created_at' => $ct->created_at?->toIso8601String(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'whatsapp_thread' => [
                    'id' => (int) $waThread->id,
                    'remote_phone_number' => $waThread->remote_phone_number,
                ],
                'conversation_threads' => $items,
                'count' => count($items),
                'pagination' => $result['pagination'],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Konversations-Threads: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'query',
            'tags' => ['comms', 'whatsapp', 'threads', 'conversation'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
