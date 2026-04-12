<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Livewire\Concerns\WithCommsChat;
use Platform\Crm\Livewire\Concerns\WithCommsChannelSettings;
use Platform\Integrations\Models\IntegrationsWhatsAppAccount;

/**
 * UI-only Comms v2 shell (no data, no logic).
 * Triggered from the navbar via the `open-modal-comms` event.
 */
class ModalComms extends Component
{
    use WithCommsChat;
    use WithCommsChannelSettings;

    public bool $open = false;

    // Debug WhatsApp Tab
    public array $debugWhatsAppAccounts = [];
    public array $debugWhatsAppChannels = [];
    public array $debugWhatsAppThreads = [];
    public array $debugInfo = [];

    // -------------------------------------------------------------------------
    // WithCommsChat: abstract implementation
    // -------------------------------------------------------------------------

    protected function shouldRefreshTimelines(): bool
    {
        return $this->open;
    }

    // -------------------------------------------------------------------------
    // Event Handlers (Modal-specific)
    // -------------------------------------------------------------------------

    #[On('comms')]
    public function onCommsContext(array $payload = []): void
    {
        $this->setCommsContext($payload);
    }

    #[On('open-modal-comms')]
    public function openModal(array $payload = []): void
    {
        $this->open = true;
        $this->loadPostmarkConnection();
        $this->loadCommsSettingsChannels();
        $this->loadEmailRuntime();
        $this->loadWhatsAppRuntime();
        $this->loadDebugWhatsApp();
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    // -------------------------------------------------------------------------
    // Debug (Modal-specific)
    // -------------------------------------------------------------------------

    public function loadDebugWhatsApp(): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        $rootTeam = $team && method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        // Debug Info
        $this->debugInfo = [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'team_id' => $team?->id,
            'team_name' => $team?->name,
            'root_team_id' => $rootTeam?->id,
            'root_team_name' => $rootTeam?->name,
        ];

        // Alle IntegrationsWhatsAppAccount (ohne Filter)
        $this->debugWhatsAppAccounts = IntegrationsWhatsAppAccount::query()
            ->with('integrationConnection.ownerUser')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'phone_number' => $a->phone_number,
                'phone_number_id' => $a->phone_number_id,
                'title' => $a->title,
                'active' => $a->active,
                'connection_id' => $a->integration_connection_id,
                'owner_user_id' => $a->integrationConnection?->ownerUser?->id,
                'owner_team_id' => $a->integrationConnection?->ownerUser?->team_id,
            ])
            ->all();

        // Alle CommsChannel type=whatsapp (ohne Filter)
        $this->debugWhatsAppChannels = CommsChannel::query()
            ->where('type', 'whatsapp')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'team_id' => $c->team_id,
                'sender_identifier' => $c->sender_identifier,
                'name' => $c->name,
                'visibility' => $c->visibility,
                'is_active' => $c->is_active,
                'meta' => $c->meta,
            ])
            ->all();

        // Alle CommsWhatsAppThread (ohne Filter)
        $this->debugWhatsAppThreads = \Platform\Crm\Models\CommsWhatsAppThread::query()
            ->withCount('messages')
            ->limit(50)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'channel_id' => $t->comms_channel_id,
                'team_id' => $t->team_id,
                'remote_phone' => $t->remote_phone_number,
                'messages_count' => $t->messages_count,
                'updated_at' => $t->updated_at?->format('d.m.Y H:i'),
            ])
            ->all();
    }

    public function render()
    {
        return view('crm::livewire.modal-comms');
    }
}
