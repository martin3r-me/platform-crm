<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Platform\Core\Enums\TeamRole;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsProviderConnection;
use Platform\Crm\Models\CommsProviderConnectionDomain;
use Platform\Core\Models\Team;
use Platform\Crm\Livewire\Concerns\WithCommsChat;
use Platform\Integrations\Models\IntegrationsWhatsAppAccount;

/**
 * UI-only Comms v2 shell (no data, no logic).
 * Triggered from the navbar via the `open-modal-comms` event.
 */
class ModalComms extends Component
{
    use WithCommsChat;

    public bool $open = false;

    /**
     * Postmark provider connection form (stored at root team level).
     * Secrets remain encrypted in DB via model casts.
     *
     * @var array<string, mixed>
     */
    public array $postmark = [
        'server_token' => '',
        'inbound_user' => '',
        'inbound_pass' => '',
        'signing_secret' => '',
    ];

    public bool $postmarkConfigured = false;
    public ?string $postmarkMessage = null;
    public ?int $rootTeamId = null;
    public ?string $rootTeamName = null;

    /**
     * Loaded Postmark domains for the active connection (UI list).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $postmarkDomains = [];

    /**
     * New domain form (UI).
     *
     * @var array<string, mixed>
     */
    public array $postmarkNewDomain = [
        'domain' => '',
        'is_primary' => true,
    ];

    public ?string $postmarkDomainMessage = null;

    /**
     * Channels (UI list) – stored at root team.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $channels = [];

    /**
     * New channel form (UI).
     *
     * @var array<string, mixed>
     */
    public array $newChannel = [
        'type' => 'email',
        'provider' => 'postmark',
        'sender_local_part' => '',
        'sender_domain' => '',
        'name' => '',
        'visibility' => 'private', // private|team
        'whatsapp_account_id' => null, // for WhatsApp channels
    ];

    public ?string $channelsMessage = null;

    /**
     * Available WhatsApp accounts for channel creation (user has access via owner or grant).
     *
     * @var array<int, array<string, mixed>>
     */
    public array $availableWhatsAppAccounts = [];

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
        $this->loadChannels();
        $this->loadEmailRuntime();
        $this->loadWhatsAppRuntime();
        $this->loadDebugWhatsApp();
    }

    public function closeModal(): void
    {
        $this->open = false;
    }

    // -------------------------------------------------------------------------
    // Admin / Setup Methods (Postmark, Channels, Debug)
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

    public function loadPostmarkConnection(): void
    {
        $this->postmarkMessage = null;
        $this->postmarkDomainMessage = null;
        $this->postmarkDomains = [];

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$team) {
            $this->postmarkConfigured = false;
            $this->rootTeamId = null;
            $this->rootTeamName = null;
            return;
        }

        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;
        $this->rootTeamId = (int) $rootTeam->id;
        $this->rootTeamName = (string) ($rootTeam->name ?? '');

        $conn = CommsProviderConnection::forTeamProvider($rootTeam, 'postmark');
        if (!$conn) {
            $this->postmarkConfigured = false;
            return;
        }

        $this->postmarkConfigured = true;

        $creds = is_array($conn->credentials) ? $conn->credentials : [];
        if (!empty($creds['inbound_user'])) {
            $this->postmark['inbound_user'] = (string) $creds['inbound_user'];
        }

        $this->loadPostmarkDomains($conn);
    }

    private function loadPostmarkDomains(CommsProviderConnection $conn): void
    {
        $this->postmarkDomains = $conn->domains()
            ->orderByDesc('is_primary')
            ->orderBy('domain')
            ->get()
            ->map(fn (CommsProviderConnectionDomain $d) => [
                'id' => (int) $d->id,
                'domain' => (string) $d->domain,
                'is_primary' => (bool) $d->is_primary,
                'is_verified' => (bool) $d->is_verified,
                'last_error' => $d->last_error ? (string) $d->last_error : null,
            ])
            ->all();
    }

    public function canCreateTeamSharedChannel(): bool
    {
        return $this->canManageProviderConnections();
    }

    public function loadChannels(): void
    {
        $this->channelsMessage = null;
        $this->channels = [];

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;
        $this->rootTeamId = (int) $rootTeam->id;
        $this->rootTeamName = (string) ($rootTeam->name ?? '');

        // Load both email and whatsapp channels
        $this->channels = CommsChannel::query()
            ->where('team_id', $rootTeam->id)
            ->whereIn('type', ['email', 'whatsapp'])
            ->orderByDesc('is_active')
            ->orderBy('type')
            ->orderBy('visibility')
            ->orderBy('sender_identifier')
            ->get()
            ->map(fn (CommsChannel $c) => [
                'id' => (int) $c->id,
                'type' => (string) $c->type,
                'provider' => (string) $c->provider,
                'sender_identifier' => (string) $c->sender_identifier,
                'name' => $c->name ? (string) $c->name : null,
                'visibility' => (string) $c->visibility,
                'is_active' => (bool) $c->is_active,
            ])
            ->all();

        // Load available WhatsApp accounts for channel creation
        $this->loadAvailableWhatsAppAccounts();
    }

    /**
     * Load WhatsApp accounts that the current user has access to (as owner or via grant).
     */
    public function loadAvailableWhatsAppAccounts(): void
    {
        $this->availableWhatsAppAccounts = [];

        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Get all IntegrationConnections where user is owner or has a grant
        $accounts = IntegrationsWhatsAppAccount::query()
            ->whereHas('integrationConnection', function ($q) use ($user) {
                $q->where('owner_user_id', $user->id)
                  ->orWhereHas('grants', function ($gq) use ($user) {
                      $gq->where('grantee_user_id', $user->id);
                  });
            })
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->with('integrationConnection.ownerUser')
            ->get();

        $this->availableWhatsAppAccounts = $accounts->map(fn (IntegrationsWhatsAppAccount $a) => [
            'id' => (int) $a->id,
            'phone_number' => (string) $a->phone_number,
            'title' => $a->title ? (string) $a->title : null,
            'label' => $a->title
                ? "{$a->title} ({$a->phone_number})"
                : (string) $a->phone_number,
            'owner' => $a->integrationConnection?->ownerUser?->name ?? '—',
        ])->all();
    }

    public function createChannel(): void
    {
        $this->channelsMessage = null;

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->channelsMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $type = (string) ($this->newChannel['type'] ?? 'email');
        $visibility = (string) ($this->newChannel['visibility'] ?? 'private');

        if ($visibility === 'team' && !$this->canCreateTeamSharedChannel()) {
            $this->channelsMessage = '⛔️ Teamweite Kanäle dürfen nur Owner/Admin des Root-Teams anlegen.';
            return;
        }

        // Handle different channel types
        if ($type === 'whatsapp') {
            $this->createWhatsAppChannel($rootTeam, $user, $visibility);
        } else {
            $this->createEmailChannel($rootTeam, $user, $visibility);
        }
    }

    private function createEmailChannel(Team $rootTeam, $user, string $visibility): void
    {
        $this->validate([
            'newChannel.type' => ['required', 'string', 'max:32'],
            'newChannel.provider' => ['required', 'string', 'max:64'],
            'newChannel.sender_local_part' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9._%+\\-]+$/i'],
            'newChannel.sender_domain' => ['required', 'string', 'max:255'],
            'newChannel.name' => ['nullable', 'string', 'max:255'],
            'newChannel.visibility' => ['required', 'in:private,team'],
        ]);

        $provider = (string) $this->newChannel['provider'];
        $local = trim((string) $this->newChannel['sender_local_part']);
        $selectedDomain = strtolower(trim((string) $this->newChannel['sender_domain']));
        $sender = $local . '@' . $selectedDomain;

        if (!filter_var($sender, FILTER_VALIDATE_EMAIL)) {
            $this->channelsMessage = '⛔️ Bitte eine gültige E‑Mail-Adresse als Absender eintragen.';
            return;
        }

        $connectionId = null;
        if ($provider === 'postmark') {
            $conn = CommsProviderConnection::forTeamProvider($rootTeam, 'postmark');
            if (!$conn) {
                $this->channelsMessage = '⛔️ Keine Postmark Connection gefunden. Bitte zuerst im Tab „Connections" speichern.';
                return;
            }
            $connectionId = $conn->id;

            // Absender-Domain MUSS in hinterlegten Domains enthalten sein.
            $configuredDomains = $conn->domains()->pluck('domain')->map(fn ($d) => strtolower((string) $d))->all();
            if (empty($configuredDomains)) {
                $this->channelsMessage = '⛔️ Bitte zuerst mindestens eine Domain in „Connections" hinterlegen (Postmark Domains).';
                return;
            }
            if (!$selectedDomain || !in_array($selectedDomain, $configuredDomains, true)) {
                $this->channelsMessage = '⛔️ Absender-Domain ist nicht in den Postmark-Domains hinterlegt.';
                return;
            }
        }

        try {
            CommsChannel::create([
                'team_id' => $rootTeam->id,
                'created_by_user_id' => $user->id,
                'comms_provider_connection_id' => $connectionId,
                'type' => 'email',
                'provider' => $provider,
                'name' => trim((string) ($this->newChannel['name'] ?? '')) ?: null,
                'sender_identifier' => $sender,
                'visibility' => $visibility,
                'is_active' => true,
                'meta' => [],
            ]);
        } catch (QueryException $e) {
            $this->channelsMessage = '⛔️ Dieser Kanal existiert bereits (Team/Typ/Absender).';
            return;
        }

        $this->newChannel['sender_local_part'] = '';
        $this->newChannel['sender_domain'] = '';
        $this->newChannel['name'] = '';
        $this->newChannel['visibility'] = 'private';

        $this->loadChannels();
        $this->channelsMessage = '✅ E-Mail Kanal angelegt.';
    }

    private function createWhatsAppChannel(Team $rootTeam, $user, string $visibility): void
    {
        $this->validate([
            'newChannel.whatsapp_account_id' => ['required', 'integer'],
            'newChannel.name' => ['nullable', 'string', 'max:255'],
            'newChannel.visibility' => ['required', 'in:private,team'],
        ]);

        $accountId = (int) $this->newChannel['whatsapp_account_id'];

        // Verify the user has access to this account
        $account = IntegrationsWhatsAppAccount::query()
            ->whereKey($accountId)
            ->whereHas('integrationConnection', function ($q) use ($user) {
                $q->where('owner_user_id', $user->id)
                  ->orWhereHas('grants', function ($gq) use ($user) {
                      $gq->where('grantee_user_id', $user->id);
                  });
            })
            ->first();

        if (!$account) {
            $this->channelsMessage = '⛔️ WhatsApp Account nicht gefunden oder keine Berechtigung.';
            return;
        }

        if (!$account->phone_number) {
            $this->channelsMessage = '⛔️ Der gewählte WhatsApp Account hat keine Telefonnummer.';
            return;
        }

        // Get or create the WhatsApp Meta provider connection for this team
        $connection = CommsProviderConnection::firstOrCreate(
            [
                'team_id' => $rootTeam->id,
                'provider' => 'whatsapp_meta',
            ],
            [
                'name' => 'WhatsApp Meta',
                'is_active' => true,
                'credentials' => [],
            ]
        );

        try {
            CommsChannel::create([
                'team_id' => $rootTeam->id,
                'created_by_user_id' => $user->id,
                'comms_provider_connection_id' => $connection->id,
                'type' => 'whatsapp',
                'provider' => 'whatsapp_meta',
                'name' => trim((string) ($this->newChannel['name'] ?? '')) ?: ($account->title ?: $account->phone_number),
                'sender_identifier' => $account->phone_number,
                'visibility' => $visibility,
                'is_active' => true,
                'meta' => [
                    'integrations_whatsapp_account_id' => $account->id,
                    'phone_number_id' => $account->phone_number_id,
                    'access_token' => $account->access_token,
                ],
            ]);
        } catch (QueryException $e) {
            $this->channelsMessage = '⛔️ Dieser WhatsApp Kanal existiert bereits.';
            return;
        }

        $this->newChannel['whatsapp_account_id'] = null;
        $this->newChannel['name'] = '';
        $this->newChannel['visibility'] = 'private';
        $this->newChannel['type'] = 'email'; // Reset to default

        $this->loadChannels();
        $this->loadWhatsAppRuntime(); // Refresh WhatsApp runtime
        $this->channelsMessage = '✅ WhatsApp Kanal angelegt.';
    }

    public function removeChannel(int $channelId): void
    {
        $this->channelsMessage = null;

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->channelsMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $channel = CommsChannel::query()
            ->where('team_id', $rootTeam->id)
            ->whereKey($channelId)
            ->first();

        if (!$channel) {
            $this->channelsMessage = '⛔️ Kanal nicht gefunden.';
            return;
        }

        // Owner/Admin can delete anything; otherwise only private channels created by the user
        if (!$this->canManageProviderConnections()) {
            if ($channel->visibility !== 'private' || (int) $channel->created_by_user_id !== (int) $user->id) {
                $this->channelsMessage = '⛔️ Keine Berechtigung zum Löschen dieses Kanals.';
                return;
            }
        }

        $channel->forceDelete();
        $this->loadChannels();
        $this->channelsMessage = '✅ Kanal entfernt.';
    }

    public function savePostmarkConnection(): void
    {
        $this->postmarkMessage = null;
        $this->postmarkDomainMessage = null;

        if (!$this->canManageProviderConnections()) {
            $this->postmarkMessage = '⛔️ Keine Berechtigung: nur Owner/Admin des Root-Teams kann Provider-Connections verwalten.';
            return;
        }

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->postmarkMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $this->validate([
            'postmark.server_token' => ['required', 'string', 'min:10'],
            'postmark.inbound_user' => ['nullable', 'string', 'max:255'],
            'postmark.inbound_pass' => ['nullable', 'string', 'max:255'],
            'postmark.signing_secret' => ['nullable', 'string', 'max:255'],
        ]);

        CommsProviderConnection::updateOrCreate(
            [
                'team_id' => $rootTeam->id,
                'provider' => 'postmark',
            ],
            [
                'created_by_user_id' => $user->id,
                'name' => 'Postmark',
                'is_active' => true,
                'credentials' => [
                    'server_token' => (string) $this->postmark['server_token'],
                    'inbound_user' => (string) ($this->postmark['inbound_user'] ?? ''),
                    'inbound_pass' => (string) ($this->postmark['inbound_pass'] ?? ''),
                    'signing_secret' => (string) ($this->postmark['signing_secret'] ?? ''),
                ],
                'meta' => [],
                'last_error' => null,
            ]
        );

        $this->postmarkConfigured = true;
        $this->postmarkMessage = '✅ Postmark Connection gespeichert (am Root-Team).';

        // Reload domains list (connection might have been created just now)
        if ($conn = CommsProviderConnection::forTeamProvider($rootTeam, 'postmark')) {
            $this->loadPostmarkDomains($conn);
        }

        // Clear secrets from the form (avoid showing them back).
        $this->postmark['server_token'] = '';
        $this->postmark['inbound_pass'] = '';
        $this->postmark['signing_secret'] = '';
    }

    public function addPostmarkDomain(): void
    {
        $this->postmarkDomainMessage = null;

        if (!$this->canManageProviderConnections()) {
            $this->postmarkDomainMessage = '⛔️ Keine Berechtigung: nur Owner/Admin des Root-Teams.';
            return;
        }

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->postmarkDomainMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $conn = CommsProviderConnection::forTeamProvider($rootTeam, 'postmark');
        if (!$conn) {
            $this->postmarkDomainMessage = '⛔️ Bitte zuerst Postmark speichern (Connection existiert noch nicht).';
            return;
        }

        $this->validate([
            'postmarkNewDomain.domain' => [
                'required',
                'string',
                'max:255',
                // simple domain validation (subdomains allowed)
                'regex:/^(?!-)(?:[a-z0-9-]{1,63}\\.)+[a-z]{2,63}$/i',
            ],
            'postmarkNewDomain.is_primary' => ['boolean'],
        ]);

        $domain = strtolower(trim((string) $this->postmarkNewDomain['domain']));
        $purpose = 'email';
        $isPrimary = (bool) ($this->postmarkNewDomain['is_primary'] ?? false);

        try {
            $created = $conn->domains()->create([
                'domain' => $domain,
                'purpose' => $purpose,
                'is_primary' => $isPrimary,
                'is_verified' => false,
                'meta' => [],
            ]);

            if ($isPrimary) {
                $conn->domains()
                    ->where('purpose', $purpose)
                    ->where('id', '!=', $created->id)
                    ->update(['is_primary' => false]);
            }
        } catch (QueryException $e) {
            $this->postmarkDomainMessage = '⛔️ Domain existiert bereits für diesen Purpose.';
            return;
        }

        $this->postmarkNewDomain['domain'] = '';
        $this->postmarkNewDomain['is_primary'] = true;

        $this->loadPostmarkDomains($conn);
        $this->postmarkDomainMessage = '✅ Domain hinzugefügt.';
    }

    public function setPostmarkPrimaryDomain(int $domainId): void
    {
        $this->postmarkDomainMessage = null;

        if (!$this->canManageProviderConnections()) {
            $this->postmarkDomainMessage = '⛔️ Keine Berechtigung: nur Owner/Admin des Root-Teams.';
            return;
        }

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->postmarkDomainMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $conn = CommsProviderConnection::forTeamProvider($rootTeam, 'postmark');
        if (!$conn) {
            $this->postmarkDomainMessage = '⛔️ Postmark Connection nicht gefunden.';
            return;
        }

        $domain = $conn->domains()->whereKey($domainId)->first();
        if (!$domain) {
            $this->postmarkDomainMessage = '⛔️ Domain nicht gefunden.';
            return;
        }

        $conn->domains()
            ->where('purpose', $domain->purpose)
            ->update(['is_primary' => false]);

        $domain->is_primary = true;
        $domain->save();

        $this->loadPostmarkDomains($conn);
        $this->postmarkDomainMessage = '✅ Primary gesetzt.';
    }

    public function removePostmarkDomain(int $domainId): void
    {
        $this->postmarkDomainMessage = null;

        if (!$this->canManageProviderConnections()) {
            $this->postmarkDomainMessage = '⛔️ Keine Berechtigung: nur Owner/Admin des Root-Teams.';
            return;
        }

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->postmarkDomainMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $conn = CommsProviderConnection::forTeamProvider($rootTeam, 'postmark');
        if (!$conn) {
            $this->postmarkDomainMessage = '⛔️ Postmark Connection nicht gefunden.';
            return;
        }

        $deleted = $conn->domains()->whereKey($domainId)->delete();
        if (!$deleted) {
            $this->postmarkDomainMessage = '⛔️ Domain nicht gefunden.';
            return;
        }

        $this->loadPostmarkDomains($conn);
        $this->postmarkDomainMessage = '✅ Domain entfernt.';
    }

    public function render()
    {
        return view('crm::livewire.modal-comms');
    }
}
