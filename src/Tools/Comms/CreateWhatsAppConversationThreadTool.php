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

class CreateWhatsAppConversationThreadTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCommsRootTeam;

    public function getName(): string
    {
        return 'core.comms.wa_threads.POST';
    }

    public function getDescription(): string
    {
        return 'POST /comms/wa_threads – Neuen Pseudo-Thread (Konversations-Thread) für einen WhatsApp-Kontakt starten. Schliesst automatisch den aktuellen aktiven Thread. Label ist erforderlich (z.B. "Bewerbung 2026", "Support-Anfrage #42").';
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
                    'description' => 'ERFORDERLICH: WhatsApp-Thread-ID (= Kontakt), für den ein neuer Konversations-Thread erstellt werden soll.',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'ERFORDERLICH: Bezeichnung des neuen Threads (z.B. "Bewerbung 2026", "Support-Anfrage #42").',
                ],
            ],
            'required' => ['thread_id', 'label'],
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

            $label = trim((string) ($arguments['label'] ?? ''));
            if ($label === '') {
                return ToolResult::error('VALIDATION_ERROR', 'label ist erforderlich (z.B. "Bewerbung 2026").');
            }

            // Verify WhatsApp thread access
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

            // Check if there's an active thread that will be closed
            $previousThread = CommsWhatsAppConversationThread::findActiveForThread($threadId);

            // Start a new conversation thread (closes any active one)
            $newThread = CommsWhatsAppConversationThread::startNew(
                whatsappThreadId: $threadId,
                teamId: $rootTeam->id,
                label: $label,
                createdByUserId: $context->user->id,
            );

            $result = [
                'message' => 'Neuer Konversations-Thread erstellt.',
                'conversation_thread' => [
                    'id' => (int) $newThread->id,
                    'uuid' => $newThread->uuid,
                    'label' => $newThread->label,
                    'is_active' => true,
                    'started_at' => $newThread->started_at?->toIso8601String(),
                    'created_by_user_id' => (int) $context->user->id,
                ],
                'whatsapp_thread' => [
                    'id' => (int) $waThread->id,
                    'remote_phone_number' => $waThread->remote_phone_number,
                ],
            ];

            if ($previousThread) {
                $previousThread->refresh();
                $result['closed_previous_thread'] = [
                    'id' => (int) $previousThread->id,
                    'label' => $previousThread->label,
                    'ended_at' => $previousThread->ended_at?->toIso8601String(),
                ];
            }

            return ToolResult::success($result);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Konversations-Threads: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['comms', 'whatsapp', 'threads', 'conversation', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
