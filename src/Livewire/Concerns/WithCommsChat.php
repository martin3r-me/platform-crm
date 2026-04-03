<?php

namespace Platform\Crm\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Platform\Core\Enums\TeamRole;
use Platform\Core\Models\Team;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsEmailThread;
use Platform\Crm\Models\CommsEmailInboundMail;
use Platform\Crm\Models\CommsEmailOutboundMail;
use Platform\Crm\Models\CommsWhatsAppThread;
use Platform\Crm\Models\CommsWhatsAppMessage;
use Platform\Crm\Models\CommsWhatsAppConversationThread;
use Platform\Crm\Models\CommsEmailMailAttachment;
use Platform\Crm\Services\Comms\PostmarkEmailService;
use Platform\Crm\Services\Comms\WhatsAppMetaService;
use Platform\Crm\Services\Comms\WhatsAppChannelSyncService;
use Platform\Integrations\Models\IntegrationsWhatsAppTemplate;

/**
 * Shared chat-runtime logic for ModalComms and InlineComms.
 *
 * Contains: context handling, email channel/thread/timeline/compose,
 * WhatsApp channel/thread/timeline/compose, conversation threads, templates.
 *
 * Does NOT contain: admin/setup (Postmark connection, domain CRUD, channel CRUD, debug).
 */
trait WithCommsChat
{
    // --- Context ---
    public ?string $contextModel = null;
    public ?int $contextModelId = null;
    public ?string $contextSubject = null;
    public ?string $contextDescription = null;
    public ?string $contextUrl = null;
    public ?string $contextSource = null;
    public array $contextRecipients = [];
    public array $contextMeta = [];
    public array $contextCapabilities = [];

    // --- Email Runtime ---
    /** @var array<int, array<string, mixed>> */
    public array $emailChannels = [];
    public ?int $activeEmailChannelId = null;
    public ?string $activeEmailChannelAddress = null;

    /** @var array<int, array<string, mixed>> */
    public array $emailThreads = [];
    public ?int $activeEmailThreadId = null;

    /** @var array<int, int> */
    public array $lastActiveEmailThreadByChannel = [];

    /** @var array<int, array<string, mixed>> */
    public array $emailTimeline = [];

    /** @var array<string, mixed> */
    public array $emailCompose = [
        'to' => '',
        'subject' => '',
        'body' => '',
    ];

    public bool $showAllThreads = false;
    public ?string $emailMessage = null;

    // --- WhatsApp Runtime ---
    /** @var array<int, array<string, mixed>> */
    public array $whatsappChannels = [];
    public ?int $activeWhatsAppChannelId = null;
    public ?string $activeWhatsAppChannelPhone = null;

    /** @var array<int, array<string, mixed>> */
    public array $whatsappThreads = [];
    public ?int $activeWhatsAppThreadId = null;

    /** @var array<int, int> */
    public array $lastActiveWhatsAppThreadByChannel = [];

    /** @var array<int, array<string, mixed>> */
    public array $whatsappTimeline = [];

    /** @var array<string, mixed> */
    public array $whatsappCompose = [
        'to' => '',
        'body' => '',
    ];

    public ?string $whatsappMessage = null;

    // --- WhatsApp Conversation Threads ---
    /** @var array<int, array<string, mixed>> */
    public array $conversationThreads = [];
    public ?int $activeConversationThreadId = null;
    public bool $viewingConversationHistory = false;
    public string $newConversationThreadLabel = '';

    // --- WhatsApp Template (24h Window) ---
    public bool $whatsappWindowOpen = true;
    public ?string $whatsappWindowExpiresAt = null;
    public array $whatsappTemplates = [];
    public ?int $whatsappSelectedTemplateId = null;
    public array $whatsappTemplatePreview = [];
    public array $whatsappTemplateVariables = [];

    // --- Inline Comms: Conversation-First UX ---
    /** Flat list of all context threads (email + whatsapp), sorted by last activity DESC */
    public array $allContextThreads = [];
    /** Active tab index in $allContextThreads */
    public ?int $activeContextThreadIndex = null;
    /** When true, show the full setup UI even when threads exist ("new message" mode) */
    public bool $forceSetupMode = false;

    // -------------------------------------------------------------------------
    // Abstract: each component decides when to poll
    // -------------------------------------------------------------------------

    abstract protected function shouldRefreshTimelines(): bool;

    // -------------------------------------------------------------------------
    // Context
    // -------------------------------------------------------------------------

    public function setCommsContext(array $payload = []): void
    {
        $this->contextModel       = $payload['model']       ?? null;
        $this->contextModelId     = $payload['modelId']      ?? null;
        $this->contextSubject     = $payload['subject']      ?? null;
        $this->contextDescription = $payload['description']  ?? null;
        $this->contextUrl         = $payload['url']          ?? null;
        $this->contextSource      = $payload['source']       ?? null;
        $this->contextRecipients  = $payload['recipients']   ?? [];
        $this->contextMeta        = $payload['meta']         ?? [];
        $this->contextCapabilities = $payload['capabilities'] ?? [];
    }

    public function hasContext(): bool
    {
        return !empty($this->contextModel) && !empty($this->contextModelId);
    }

    /**
     * Find the first context recipient matching a type (email or phone).
     */
    protected function findContextRecipientByType(string $type): ?string
    {
        foreach ($this->contextRecipients as $recipient) {
            $cleaned = preg_replace('/[\s\-()]/', '', (string) $recipient);
            $isPhone = preg_match('/^\+?\d{7,}$/', $cleaned);

            if ($type === 'phone' && $isPhone) {
                return (string) $recipient;
            }
            if ($type === 'email' && !$isPhone && str_contains((string) $recipient, '@')) {
                return (string) $recipient;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Email Runtime
    // -------------------------------------------------------------------------

    public function loadEmailRuntime(): void
    {
        $this->emailMessage = null;
        $this->emailChannels = [];
        $this->emailThreads = [];
        $this->emailTimeline = [];

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $channels = CommsChannel::query()
            ->where('team_id', $rootTeam->id)
            ->where('type', 'email')
            ->where('provider', 'postmark')
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'team')
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('visibility', 'private')->where('created_by_user_id', $user->id);
                    });
            })
            ->orderBy('visibility')
            ->orderBy('sender_identifier')
            ->get();

        // Count context threads per channel for smart pre-selection & badges
        $hasCtx = $this->hasContext();

        $this->emailChannels = $channels->map(function (CommsChannel $c) use ($hasCtx) {
            $threadCount = 0;
            if ($hasCtx) {
                $threadCount = CommsEmailThread::countForContext($c->id, $this->contextModel, (int) $this->contextModelId);
            }
            return [
                'id' => (int) $c->id,
                'label' => (string) $c->sender_identifier,
                'context_thread_count' => $threadCount,
            ];
        })->all();

        if (!$this->activeEmailChannelId && $channels->isNotEmpty()) {
            // Prefer channel that has threads for this context
            $preferred = collect($this->emailChannels)->firstWhere(fn ($c) => ($c['context_thread_count'] ?? 0) > 0);
            $this->activeEmailChannelId = (int) ($preferred ? $preferred['id'] : $channels->first()->id);
        }

        $this->refreshActiveEmailChannelLabel();
        $this->loadEmailThreads();
    }

    public function updatedActiveEmailChannelId(): void
    {
        $this->refreshActiveEmailChannelLabel();

        $rememberedThreadId = (int) ($this->lastActiveEmailThreadByChannel[(int) $this->activeEmailChannelId] ?? 0);
        $useRemembered = false;

        $this->emailCompose['subject'] = '';
        $this->emailCompose['body'] = '';
        $this->emailCompose['to'] = '';

        $this->activeEmailThreadId = null;
        if ($rememberedThreadId > 0 && $this->activeEmailChannelId) {
            $exists = CommsEmailThread::query()
                ->where('comms_channel_id', $this->activeEmailChannelId)
                ->whereKey($rememberedThreadId)
                ->exists();
            if ($exists) {
                $this->activeEmailThreadId = $rememberedThreadId;
                $useRemembered = true;
            }
        }

        $this->loadEmailThreads();
        if ($useRemembered && $this->activeEmailThreadId) {
            $this->setActiveEmailThread((int) $this->activeEmailThreadId);
        }
        $this->dispatch('comms:scroll-bottom');
    }

    public function updatingActiveEmailChannelId($value): void
    {
        if ($this->activeEmailChannelId && $this->activeEmailThreadId) {
            $this->lastActiveEmailThreadByChannel[(int) $this->activeEmailChannelId] = (int) $this->activeEmailThreadId;
        }
    }

    private function refreshActiveEmailChannelLabel(): void
    {
        $this->activeEmailChannelAddress = null;
        if (!$this->activeEmailChannelId) {
            return;
        }
        foreach ($this->emailChannels as $c) {
            if ((int) ($c['id'] ?? 0) === (int) $this->activeEmailChannelId) {
                $this->activeEmailChannelAddress = (string) ($c['label'] ?? null);
                return;
            }
        }
    }

    public function toggleShowAllThreads(): void
    {
        $this->showAllThreads = !$this->showAllThreads;
        $this->loadEmailThreads();
    }

    public function loadEmailThreads(): void
    {
        $this->emailThreads = [];
        $this->emailTimeline = [];

        if (!$this->activeEmailChannelId) {
            return;
        }

        $query = CommsEmailThread::query()
            ->where('comms_channel_id', $this->activeEmailChannelId);

        if ($this->hasContext() && !$this->showAllThreads) {
            $query->forContext($this->contextModel, (int) $this->contextModelId);
        }

        $threads = $query
            ->withCount(['inboundMails', 'outboundMails'])
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        $this->emailThreads = $threads->map(fn (CommsEmailThread $t) => [
            'id' => (int) $t->id,
            'subject' => (string) ($t->subject ?: 'Ohne Betreff'),
            'counterpart' => (string) ($t->last_inbound_from_address ?: $t->last_outbound_to_address ?: ''),
            'messages_count' => (int) (($t->inbound_mails_count ?? 0) + ($t->outbound_mails_count ?? 0)),
            'last_direction' => ($t->last_inbound_at && (!$t->last_outbound_at || $t->last_inbound_at->greaterThanOrEqualTo($t->last_outbound_at)))
                ? 'inbound'
                : (($t->last_outbound_at || $t->last_inbound_at) ? 'outbound' : null),
            'last_at' => ($t->last_inbound_at || $t->last_outbound_at)
                ? ((($t->last_inbound_at && (!$t->last_outbound_at || $t->last_inbound_at->greaterThanOrEqualTo($t->last_outbound_at))))
                    ? $t->last_inbound_at?->format('d.m. H:i')
                    : $t->last_outbound_at?->format('d.m. H:i'))
                : ($t->updated_at?->format('d.m. H:i')),
        ])->all();

        if (!$this->activeEmailThreadId && $threads->isNotEmpty()) {
            $this->setActiveEmailThread((int) $threads->first()->id);
        }
    }

    public function setActiveEmailThread(int $threadId): void
    {
        $this->activeEmailThreadId = $threadId;
        $this->loadEmailTimeline();

        $thread = CommsEmailThread::query()->whereKey($threadId)->first();
        if ($thread?->last_inbound_from_address) {
            $this->emailCompose['to'] = (string) $thread->last_inbound_from_address;
        } else {
            $lastInbound = CommsEmailInboundMail::query()
                ->where('thread_id', $threadId)
                ->orderByDesc('received_at')
                ->first();
            if ($lastInbound?->from) {
                $this->emailCompose['to'] = $this->extractEmailAddress((string) $lastInbound->from) ?: (string) $lastInbound->from;
            }
        }
        $this->dispatch('comms:scroll-bottom');
    }

    public function deleteEmailThread(int $threadId): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->emailMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $thread = CommsEmailThread::query()
            ->where('team_id', $rootTeam->id)
            ->whereKey($threadId)
            ->first();

        if (!$thread) {
            $this->emailMessage = '⛔️ Thread nicht gefunden.';
            return;
        }

        $channel = CommsChannel::query()
            ->where('team_id', $rootTeam->id)
            ->whereKey($thread->comms_channel_id)
            ->first();

        if (!$channel) {
            $this->emailMessage = '⛔️ Kanal zum Thread nicht gefunden.';
            return;
        }

        if ($channel->visibility === 'team') {
            if (!$this->canManageProviderConnections()) {
                $this->emailMessage = '⛔️ Keine Berechtigung (teamweite Kanäle nur Owner/Admin).';
                return;
            }
        } else {
            if (!$this->canManageProviderConnections() && (int) $channel->created_by_user_id !== (int) $user->id) {
                $this->emailMessage = '⛔️ Keine Berechtigung (privater Kanal gehört einem anderen User).';
                return;
            }
        }

        $thread->forceDelete();

        if ((int) $this->activeEmailThreadId === (int) $threadId) {
            $this->activeEmailThreadId = null;
            $this->emailTimeline = [];
        }

        $this->emailMessage = '✅ Thread gelöscht.';
        $this->loadEmailThreads();
        $this->dispatch('comms:scroll-bottom');
    }

    private function extractEmailAddress(string $raw): ?string
    {
        if (preg_match('/<([^>]+)>/', $raw, $m)) {
            return trim((string) ($m[1] ?? '')) ?: null;
        }
        if (filter_var($raw, FILTER_VALIDATE_EMAIL)) {
            return $raw;
        }
        return null;
    }

    public function startNewEmailThread(): void
    {
        $this->activeEmailThreadId = null;
        $this->emailTimeline = [];
        $this->emailCompose['body'] = '';

        if ($this->hasContext()) {
            $this->emailCompose['subject'] = (string) ($this->contextSubject ?? '');
            $email = $this->findContextRecipientByType('email');
            $this->emailCompose['to'] = (string) ($email ?: $this->emailCompose['to']);
        } else {
            $this->emailCompose['subject'] = '';
        }

        $this->dispatch('comms:scroll-bottom');
    }

    public function sendEmail(): void
    {
        $this->emailMessage = null;
        $wasNewThread = !$this->activeEmailThreadId;

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->emailMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        if (!$this->activeEmailChannelId) {
            $this->emailMessage = '⛔️ Kein E‑Mail Kanal ausgewählt.';
            return;
        }

        try {
            $isReply = (bool) $this->activeEmailThreadId;
            $this->validate([
                'emailCompose.to' => [$isReply ? 'nullable' : 'required', 'email', 'max:255'],
                'emailCompose.body' => ['required', 'string', 'min:1'],
                'emailCompose.subject' => [$isReply ? 'nullable' : 'required', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            $this->emailMessage = '⛔️ Bitte Eingaben prüfen.';
            return;
        }

        $channel = CommsChannel::query()
            ->whereKey($this->activeEmailChannelId)
            ->where('type', 'email')
            ->where('provider', 'postmark')
            ->where('is_active', true)
            ->first();
        if (!$channel) {
            $this->emailMessage = '⛔️ E‑Mail Kanal nicht gefunden.';
            return;
        }

        $subject = (string) ($this->emailCompose['subject'] ?? '');
        $isReply = false;
        $token = null;
        $to = (string) ($this->emailCompose['to'] ?? '');

        if ($this->activeEmailThreadId) {
            $thread = CommsEmailThread::query()->whereKey($this->activeEmailThreadId)->first();
            if ($thread) {
                $subject = (string) ($thread->subject ?: $subject);
                $token = (string) $thread->token;
                $isReply = true;
            }

            if (trim($to) === '') {
                if ($thread?->last_inbound_from_address) {
                    $to = (string) $thread->last_inbound_from_address;
                } else {
                    $lastInbound = CommsEmailInboundMail::query()
                        ->where('thread_id', $this->activeEmailThreadId)
                        ->orderByDesc('received_at')
                        ->first();
                    if ($lastInbound?->from) {
                        $to = $this->extractEmailAddress((string) $lastInbound->from) ?: (string) $lastInbound->from;
                    }
                }
                $this->emailCompose['to'] = $to;
            }
            if (trim($to) === '') {
                $this->emailMessage = '⛔️ Kein Empfänger für Antwort gefunden. Bitte neuen Thread starten und „An" setzen.';
                return;
            }
        }

        try {
            /** @var PostmarkEmailService $svc */
            $svc = app(PostmarkEmailService::class);
            $token = $svc->send(
                $channel,
                $to,
                $subject ?: '(Ohne Betreff)',
                nl2br(e((string) $this->emailCompose['body'])),
                null,
                [],
                [
                    'sender' => $user,
                    'token' => $token,
                    'is_reply' => $isReply,
                    'context_model' => $this->contextModel,
                    'context_model_id' => $this->contextModelId,
                ]
            );
        } catch (\Throwable $e) {
            $this->emailMessage = '⛔️ Versand fehlgeschlagen: ' . $e->getMessage();
            return;
        }

        $this->emailCompose['body'] = '';
        if ($wasNewThread) {
            $this->emailCompose['subject'] = '';
            $this->emailCompose['to'] = '';
        }

        if ($wasNewThread && $this->hasContext() && $token) {
            $newThread = CommsEmailThread::query()
                ->where('comms_channel_id', $channel->id)
                ->where('token', $token)
                ->first();
            if ($newThread) {
                $newThread->addContext($this->contextModel, (int) $this->contextModelId, 'outbound');
                if (!$newThread->context_model) {
                    $newThread->updateQuietly([
                        'context_model' => $this->contextModel,
                        'context_model_id' => $this->contextModelId,
                    ]);
                }
            }
        }

        $this->loadEmailThreads();
        if ($token) {
            $thread = CommsEmailThread::query()
                ->where('comms_channel_id', $channel->id)
                ->where('token', $token)
                ->first();
            if ($thread) {
                $this->setActiveEmailThread((int) $thread->id);
            }
        } elseif ($this->activeEmailThreadId) {
            $this->loadEmailTimeline();
        }

        $this->emailMessage = '✅ E‑Mail gesendet.';
        $this->buildContextThreadsList();
        $this->forceSetupMode = false;
        $this->dispatch('comms:scroll-bottom');
    }

    private function loadEmailTimeline(): void
    {
        $this->emailTimeline = [];
        if (!$this->activeEmailThreadId) {
            return;
        }

        // Load all attachments for this thread (for CID resolution + file list)
        $threadAttachments = CommsEmailMailAttachment::query()
            ->where(function ($q) {
                $q->whereIn('inbound_mail_id', CommsEmailInboundMail::where('thread_id', $this->activeEmailThreadId)->select('id'))
                  ->orWhereIn('outbound_mail_id', CommsEmailOutboundMail::where('thread_id', $this->activeEmailThreadId)->select('id'));
            })
            ->get()
            ->groupBy(fn ($a) => $a->inbound_mail_id ? 'in_' . $a->inbound_mail_id : 'out_' . $a->outbound_mail_id);

        $resolveHtml = function (?string $html, string $mailKey) use ($threadAttachments): ?string {
            if (!$html) return null;
            $attachments = $threadAttachments->get($mailKey, collect());
            // Replace cid: references with signed URLs
            foreach ($attachments->where('cid', '!=', null) as $att) {
                $cid = trim($att->cid, '<>');
                $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'crm.comms.email-attachment.show',
                    now()->addHour(),
                    ['attachment' => $att->id]
                );
                $html = str_replace(
                    ['cid:' . $cid, 'cid:' . '<' . $cid . '>'],
                    $url,
                    $html
                );
            }

            // Collapse quoted content (Gmail, Outlook, Apple Mail, blockquote, text markers)
            $detailsOpen = '<details style="margin-top:12px;border-top:1px solid #e0e0e0;padding-top:8px"><summary style="cursor:pointer;display:inline-flex;align-items:center;gap:6px;padding:4px 12px;font-size:12px;color:#666;background:#f5f5f5;border:1px solid #e0e0e0;border-radius:12px;list-style:none;user-select:none;transition:background 0.15s">&#9662; Zitierter Verlauf anzeigen</summary><div style="margin-top:8px">';
            $detailsClose = '</div></details>';
            $collapsed = false;

            // Gmail quote: <div class="gmail_quote">
            if (!$collapsed) {
                $html = preg_replace(
                    '/<div\s+class=["\']gmail_quote["\'][^>]*>/i',
                    $detailsOpen . '<div class="gmail_quote">',
                    $html, 1, $count
                );
                if ($count > 0) { $html .= $detailsClose; $collapsed = true; }
            }

            // Outlook: <div id="divRplyFwdMsg">
            if (!$collapsed) {
                $html = preg_replace(
                    '/<div\s+id=["\']divRplyFwdMsg["\'][^>]*>/i',
                    $detailsOpen . '<div id="divRplyFwdMsg">',
                    $html, 1, $count
                );
                if ($count > 0) { $html .= $detailsClose; $collapsed = true; }
            }

            // Outlook separator line: <div style="border:none;border-top:solid #E1E1E1...">
            if (!$collapsed) {
                $html = preg_replace(
                    '/<div\s+style=["\'][^"\']*border-top:\s*solid\s+#[A-Fa-f0-9]+[^"\']*["\'][^>]*>/i',
                    $detailsOpen . '$0',
                    $html, 1, $count
                );
                if ($count > 0) { $html .= $detailsClose; $collapsed = true; }
            }

            // Outlook/Thunderbird: <hr> followed by Von:/From:
            if (!$collapsed) {
                $html = preg_replace(
                    '/<hr[^>]*>\s*(?:<[^>]+>\s*)*(Von:|From:|De:)/i',
                    $detailsOpen . '$0',
                    $html, 1, $count
                );
                if ($count > 0) { $html .= $detailsClose; $collapsed = true; }
            }

            // Apple Mail: <div dir="ltr"><br><blockquote> or standalone blockquote
            if (!$collapsed) {
                $html = preg_replace(
                    '/<blockquote[^>]*>/i',
                    $detailsOpen . '<blockquote>',
                    $html, 1, $count
                );
                if ($count > 0) { $html .= $detailsClose; $collapsed = true; }
            }

            // Text-based markers: "Am ... schrieb", "On ... wrote", "-----Ursprüngliche Nachricht-----"
            if (!$collapsed) {
                $html = preg_replace(
                    '/(<br\s*\/?>|<p[^>]*>)\s*(-{3,}.*?-{3,}|Am\s+.{5,60}\s+schrieb\s|On\s+.{5,60}\s+wrote:)/i',
                    $detailsOpen . '$0',
                    $html, 1, $count
                );
                if ($count > 0) { $html .= $detailsClose; $collapsed = true; }
            }

            return $html;
        };

        $buildAttachmentList = function (string $mailKey) use ($threadAttachments): array {
            return $threadAttachments->get($mailKey, collect())
                ->where('inline', false)
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'filename' => $a->filename,
                    'mime' => $a->mime,
                    'size' => $a->size,
                    'url' => \Illuminate\Support\Facades\URL::temporarySignedRoute(
                        'crm.comms.email-attachment.show',
                        now()->addHour(),
                        ['attachment' => $a->id]
                    ),
                ])
                ->values()
                ->all();
        };

        $inbound = CommsEmailInboundMail::query()
            ->where('thread_id', $this->activeEmailThreadId)
            ->get()
            ->map(fn (CommsEmailInboundMail $m) => [
                'direction' => 'inbound',
                'at' => $m->received_at?->toDateTimeString() ?: $m->created_at?->toDateTimeString(),
                'from' => $m->from,
                'to' => $m->to,
                'cc' => $m->cc,
                'subject' => $m->subject,
                'html' => $resolveHtml($m->html_body, 'in_' . $m->id),
                'text' => $m->text_body,
                'attachments' => $buildAttachmentList('in_' . $m->id),
            ]);

        $outbound = CommsEmailOutboundMail::query()
            ->where('thread_id', $this->activeEmailThreadId)
            ->get()
            ->map(fn (CommsEmailOutboundMail $m) => [
                'direction' => 'outbound',
                'at' => $m->sent_at?->toDateTimeString() ?: $m->created_at?->toDateTimeString(),
                'from' => $m->from,
                'to' => $m->to,
                'cc' => $m->cc,
                'subject' => $m->subject,
                'html' => $resolveHtml($m->html_body, 'out_' . $m->id),
                'text' => $m->text_body,
                'attachments' => $buildAttachmentList('out_' . $m->id),
            ]);

        $this->emailTimeline = $inbound
            ->concat($outbound)
            ->sortBy(fn ($x) => $x['at'] ?? '')
            ->values()
            ->all();

        $this->dispatch('comms:scroll-bottom');
    }

    // -------------------------------------------------------------------------
    // WhatsApp Runtime
    // -------------------------------------------------------------------------

    public function loadWhatsAppRuntime(): void
    {
        $this->whatsappMessage = null;
        $this->whatsappChannels = [];
        $this->whatsappThreads = [];
        $this->whatsappTimeline = [];
        $this->conversationThreads = [];
        $this->activeConversationThreadId = null;
        $this->viewingConversationHistory = false;
        $this->newConversationThreadLabel = '';

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $channelQuery = fn () => CommsChannel::query()
            ->where('team_id', $rootTeam->id)
            ->where('type', 'whatsapp')
            ->where('provider', 'whatsapp_meta')
            ->where('is_active', true)
            ->where(function ($q) use ($user) {
                $q->where('visibility', 'team')
                    ->orWhere(function ($q2) use ($user) {
                        $q2->where('visibility', 'private')->where('created_by_user_id', $user->id);
                    });
            })
            ->orderBy('visibility')
            ->orderBy('sender_identifier')
            ->get();

        $channels = $channelQuery();

        if ($channels->isEmpty()) {
            try {
                $syncService = app(WhatsAppChannelSyncService::class);
                $syncService->syncForTeam($rootTeam);
                $channels = $channelQuery();
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('[WithCommsChat] WhatsApp sync failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Count context threads per channel for smart pre-selection & badges
        $hasCtx = $this->hasContext();

        $this->whatsappChannels = $channels->map(function (CommsChannel $c) use ($hasCtx) {
            $threadCount = 0;
            if ($hasCtx) {
                $threadCount = CommsWhatsAppThread::countForContext($c->id, $this->contextModel, (int) $this->contextModelId);
            }
            return [
                'id' => (int) $c->id,
                'label' => (string) $c->sender_identifier,
                'name' => $c->name ? (string) $c->name : null,
                'context_thread_count' => $threadCount,
            ];
        })->all();

        if (!$this->activeWhatsAppChannelId && $channels->isNotEmpty()) {
            // Prefer channel that has threads for this context
            $preferred = collect($this->whatsappChannels)->firstWhere(fn ($c) => ($c['context_thread_count'] ?? 0) > 0);
            $this->activeWhatsAppChannelId = (int) ($preferred ? $preferred['id'] : $channels->first()->id);
        }

        $this->refreshActiveWhatsAppChannelLabel();
        $this->loadWhatsAppThreads();
    }

    public function updatedActiveWhatsAppChannelId(): void
    {
        $this->refreshActiveWhatsAppChannelLabel();

        $rememberedThreadId = (int) ($this->lastActiveWhatsAppThreadByChannel[(int) $this->activeWhatsAppChannelId] ?? 0);
        $useRemembered = false;

        $this->whatsappCompose['body'] = '';
        $this->whatsappCompose['to'] = '';

        $this->activeWhatsAppThreadId = null;
        if ($rememberedThreadId > 0 && $this->activeWhatsAppChannelId) {
            $exists = CommsWhatsAppThread::query()
                ->where('comms_channel_id', $this->activeWhatsAppChannelId)
                ->whereKey($rememberedThreadId)
                ->exists();
            if ($exists) {
                $this->activeWhatsAppThreadId = $rememberedThreadId;
                $useRemembered = true;
            }
        }

        $this->loadWhatsAppThreads();
        if ($useRemembered && $this->activeWhatsAppThreadId) {
            $this->setActiveWhatsAppThread((int) $this->activeWhatsAppThreadId);
        }
        $this->dispatch('comms:scroll-bottom');
    }

    public function updatingActiveWhatsAppChannelId($value): void
    {
        if ($this->activeWhatsAppChannelId && $this->activeWhatsAppThreadId) {
            $this->lastActiveWhatsAppThreadByChannel[(int) $this->activeWhatsAppChannelId] = (int) $this->activeWhatsAppThreadId;
        }
    }

    private function refreshActiveWhatsAppChannelLabel(): void
    {
        $this->activeWhatsAppChannelPhone = null;
        if (!$this->activeWhatsAppChannelId) {
            return;
        }
        foreach ($this->whatsappChannels as $c) {
            if ((int) ($c['id'] ?? 0) === (int) $this->activeWhatsAppChannelId) {
                $this->activeWhatsAppChannelPhone = (string) ($c['label'] ?? null);
                return;
            }
        }
    }

    public function loadWhatsAppThreads(): void
    {
        $this->whatsappThreads = [];
        $this->whatsappTimeline = [];

        if (!$this->activeWhatsAppChannelId) {
            return;
        }

        $query = CommsWhatsAppThread::query()
            ->where('comms_channel_id', $this->activeWhatsAppChannelId);

        if ($this->hasContext() && !$this->showAllThreads) {
            $query->forContext($this->contextModel, (int) $this->contextModelId);
        }

        $threads = $query
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        $this->whatsappThreads = $threads->map(fn (CommsWhatsAppThread $t) => [
            'id' => (int) $t->id,
            'remote_phone' => (string) ($t->remote_phone_number ?: '—'),
            'messages_count' => (int) ($t->messages_count ?? 0),
            'last_message_preview' => (string) ($t->last_message_preview ?: ''),
            'is_unread' => (bool) $t->is_unread,
            'last_direction' => ($t->last_inbound_at && (!$t->last_outbound_at || $t->last_inbound_at->greaterThanOrEqualTo($t->last_outbound_at)))
                ? 'inbound'
                : (($t->last_outbound_at || $t->last_inbound_at) ? 'outbound' : null),
            'last_at' => ($t->last_inbound_at || $t->last_outbound_at)
                ? ((($t->last_inbound_at && (!$t->last_outbound_at || $t->last_inbound_at->greaterThanOrEqualTo($t->last_outbound_at))))
                    ? $t->last_inbound_at?->format('d.m. H:i')
                    : $t->last_outbound_at?->format('d.m. H:i'))
                : ($t->updated_at?->format('d.m. H:i')),
        ])->all();

        if (!$this->activeWhatsAppThreadId && $threads->isNotEmpty()) {
            $this->setActiveWhatsAppThread((int) $threads->first()->id);
        }
    }

    public function setActiveWhatsAppThread(int $threadId): void
    {
        $this->activeWhatsAppThreadId = $threadId;
        $this->viewingConversationHistory = false;

        $this->loadConversationThreads();

        $activeConvThread = CommsWhatsAppConversationThread::findActiveForThread($threadId);
        $this->activeConversationThreadId = $activeConvThread?->id;

        $this->loadWhatsAppTimeline();

        $thread = CommsWhatsAppThread::query()->whereKey($threadId)->first();
        if ($thread?->remote_phone_number) {
            $this->whatsappCompose['to'] = (string) $thread->remote_phone_number;
        }

        $this->whatsappWindowOpen = $thread?->isWindowOpen() ?? false;
        $this->whatsappWindowExpiresAt = $thread?->windowExpiresAt()?->toIso8601String();
        if (!$this->whatsappWindowOpen) {
            $this->loadWhatsAppTemplates();
        }
        $this->resetTemplateSelection();

        $thread?->markAsRead();

        $this->dispatch('comms:scroll-bottom');
    }

    private function loadWhatsAppTimeline(): void
    {
        $this->whatsappTimeline = [];
        if (!$this->activeWhatsAppThreadId) {
            return;
        }

        $query = CommsWhatsAppMessage::query()
            ->where('comms_whatsapp_thread_id', $this->activeWhatsAppThreadId);

        if ($this->activeConversationThreadId) {
            $query->where('conversation_thread_id', $this->activeConversationThreadId);
        }

        $messages = $query
            ->orderByRaw('COALESCE(sent_at, created_at) ASC')
            ->get();

        $this->whatsappTimeline = $messages->map(fn (CommsWhatsAppMessage $m) => [
            'id' => (int) $m->id,
            'direction' => (string) ($m->direction ?? 'outbound'),
            'body' => (string) ($m->body ?? ''),
            'message_type' => (string) ($m->message_type ?? 'text'),
            'media_display_type' => (string) $m->media_display_type,
            'status' => (string) ($m->status ?? ''),
            'at' => $m->sent_at?->format('H:i') ?: $m->created_at?->format('H:i'),
            'full_at' => $m->sent_at?->format('d.m.Y H:i') ?: $m->created_at?->format('d.m.Y H:i'),
            'sent_by' => $m->sentByUser?->name ?? null,
            'has_media' => $m->hasMedia(),
            'attachments' => $m->attachments ?? [],
            'reactions' => $m->reactions ?? [],
        ])->all();

        $this->dispatch('comms:scroll-bottom');
    }

    // -------------------------------------------------------------------------
    // WhatsApp Conversation Thread (Pseudo-Thread) Methods
    // -------------------------------------------------------------------------

    public function loadConversationThreads(): void
    {
        $this->conversationThreads = [];

        if (!$this->activeWhatsAppThreadId) {
            return;
        }

        $threads = CommsWhatsAppConversationThread::query()
            ->where('comms_whatsapp_thread_id', $this->activeWhatsAppThreadId)
            ->withCount('messages')
            ->orderByDesc('started_at')
            ->get();

        $this->conversationThreads = $threads->map(fn (CommsWhatsAppConversationThread $ct) => [
            'id' => (int) $ct->id,
            'uuid' => (string) $ct->uuid,
            'label' => (string) $ct->label,
            'started_at' => $ct->started_at?->format('d.m.Y H:i'),
            'ended_at' => $ct->ended_at?->format('d.m.Y H:i'),
            'is_active' => $ct->isActive(),
            'messages_count' => (int) ($ct->messages_count ?? 0),
            'created_by' => $ct->createdBy?->name ?? null,
        ])->all();
    }

    public function startNewConversationThread(): void
    {
        $this->whatsappMessage = null;

        if (!$this->activeWhatsAppThreadId) {
            $this->whatsappMessage = 'Bitte zuerst einen WhatsApp Thread auswählen.';
            return;
        }

        $label = trim($this->newConversationThreadLabel);
        if ($label === '') {
            $this->whatsappMessage = 'Bitte ein Label für den neuen Konversations-Thread eingeben.';
            return;
        }

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->whatsappMessage = 'Kein Team-Kontext gefunden.';
            return;
        }

        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $conversationThread = CommsWhatsAppConversationThread::startNew(
            $this->activeWhatsAppThreadId,
            $rootTeam->id,
            $label,
            $user->id,
        );

        $this->activeConversationThreadId = (int) $conversationThread->id;
        $this->viewingConversationHistory = false;
        $this->newConversationThreadLabel = '';

        $this->loadConversationThreads();
        $this->loadWhatsAppTimeline();

        $this->whatsappMessage = 'Neuer Konversations-Thread gestartet: ' . $label;
        $this->dispatch('comms:scroll-bottom');
    }

    public function setActiveConversationThread(?int $conversationThreadId): void
    {
        if ($conversationThreadId === null) {
            $this->activeConversationThreadId = null;
            $this->viewingConversationHistory = false;
        } else {
            $ct = CommsWhatsAppConversationThread::query()->whereKey($conversationThreadId)->first();
            if (!$ct) {
                return;
            }
            $this->activeConversationThreadId = (int) $ct->id;
            $this->viewingConversationHistory = !$ct->isActive();
        }

        $this->loadWhatsAppTimeline();
        $this->dispatch('comms:scroll-bottom');
    }

    public function startNewWhatsAppThread(): void
    {
        $this->activeWhatsAppThreadId = null;
        $this->whatsappTimeline = [];
        $this->whatsappCompose['body'] = '';
        $this->conversationThreads = [];
        $this->activeConversationThreadId = null;
        $this->viewingConversationHistory = false;
        $this->newConversationThreadLabel = '';

        $this->whatsappWindowOpen = false;
        $this->whatsappWindowExpiresAt = null;
        $this->loadWhatsAppTemplates();
        $this->resetTemplateSelection();

        if ($this->hasContext()) {
            $phone = $this->findContextRecipientByType('phone') ?: $this->whatsappCompose['to'];
            if ($phone && preg_match('/^\+?\d{7,}$/', preg_replace('/[\s\-()]/', '', $phone))) {
                $this->whatsappCompose['to'] = $phone;
            }
        } else {
            $this->whatsappCompose['to'] = '';
        }

        $this->dispatch('comms:scroll-bottom');
    }

    public function sendWhatsApp(): void
    {
        $this->whatsappMessage = null;
        $wasNewThread = !$this->activeWhatsAppThreadId;

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->whatsappMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        if (!$this->activeWhatsAppChannelId) {
            $this->whatsappMessage = '⛔️ Kein WhatsApp Kanal ausgewählt.';
            return;
        }

        if (!$this->whatsappWindowOpen) {
            $this->sendWhatsAppTemplate();
            return;
        }

        $isReply = (bool) $this->activeWhatsAppThreadId;
        try {
            $this->validate([
                'whatsappCompose.to' => [$isReply ? 'nullable' : 'required', 'string', 'max:32'],
                'whatsappCompose.body' => ['required', 'string', 'min:1'],
            ]);
        } catch (ValidationException $e) {
            $this->whatsappMessage = '⛔️ Bitte Eingaben prüfen.';
            return;
        }

        $channel = CommsChannel::query()
            ->whereKey($this->activeWhatsAppChannelId)
            ->where('type', 'whatsapp')
            ->where('provider', 'whatsapp_meta')
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            $this->whatsappMessage = '⛔️ WhatsApp Kanal nicht gefunden.';
            return;
        }

        $to = (string) ($this->whatsappCompose['to'] ?? '');

        if ($this->activeWhatsAppThreadId && trim($to) === '') {
            $thread = CommsWhatsAppThread::query()->whereKey($this->activeWhatsAppThreadId)->first();
            if ($thread?->remote_phone_number) {
                $to = (string) $thread->remote_phone_number;
            }
            $this->whatsappCompose['to'] = $to;
        }

        if (trim($to) === '') {
            $this->whatsappMessage = '⛔️ Kein Empfänger angegeben.';
            return;
        }

        try {
            /** @var WhatsAppMetaService $svc */
            $svc = app(WhatsAppMetaService::class);
            $message = $svc->sendText(
                $channel,
                $to,
                (string) $this->whatsappCompose['body'],
                $user
            );
        } catch (\Throwable $e) {
            $this->whatsappMessage = '⛔️ Versand fehlgeschlagen: ' . $e->getMessage();
            return;
        }

        $this->whatsappCompose['body'] = '';
        if ($wasNewThread) {
            $this->whatsappCompose['to'] = '';
        }

        if ($wasNewThread && $this->hasContext() && $message?->thread) {
            $newThread = $message->thread;
            if ($newThread) {
                $newThread->addContext($this->contextModel, (int) $this->contextModelId, 'outbound');
                if (!$newThread->context_model) {
                    $newThread->updateQuietly([
                        'context_model' => $this->contextModel,
                        'context_model_id' => $this->contextModelId,
                    ]);
                }
            }
        }

        $this->loadWhatsAppThreads();
        if ($message?->thread) {
            $this->setActiveWhatsAppThread((int) $message->thread->id);
        } elseif ($this->activeWhatsAppThreadId) {
            $this->loadWhatsAppTimeline();
        }

        $this->whatsappMessage = '✅ Nachricht gesendet.';
        $this->buildContextThreadsList();
        $this->forceSetupMode = false;
        $this->dispatch('comms:scroll-bottom');
    }

    public function deleteWhatsAppThread(int $threadId): void
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->whatsappMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        /** @var Team $rootTeam */
        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        $thread = CommsWhatsAppThread::query()
            ->where('team_id', $rootTeam->id)
            ->whereKey($threadId)
            ->first();

        if (!$thread) {
            $this->whatsappMessage = '⛔️ Thread nicht gefunden.';
            return;
        }

        $channel = CommsChannel::query()
            ->where('team_id', $rootTeam->id)
            ->whereKey($thread->comms_channel_id)
            ->first();

        if (!$channel) {
            $this->whatsappMessage = '⛔️ Kanal zum Thread nicht gefunden.';
            return;
        }

        if ($channel->visibility === 'team') {
            if (!$this->canManageProviderConnections()) {
                $this->whatsappMessage = '⛔️ Keine Berechtigung (teamweite Kanäle nur Owner/Admin).';
                return;
            }
        } else {
            if (!$this->canManageProviderConnections() && (int) $channel->created_by_user_id !== (int) $user->id) {
                $this->whatsappMessage = '⛔️ Keine Berechtigung (privater Kanal gehört einem anderen User).';
                return;
            }
        }

        $thread->delete();

        if ((int) $this->activeWhatsAppThreadId === (int) $threadId) {
            $this->activeWhatsAppThreadId = null;
            $this->whatsappTimeline = [];
        }

        $this->whatsappMessage = '✅ Thread gelöscht.';
        $this->loadWhatsAppThreads();
        $this->dispatch('comms:scroll-bottom');
    }

    // -------------------------------------------------------------------------
    // WhatsApp Template Methods (24h Window)
    // -------------------------------------------------------------------------

    public function loadWhatsAppTemplates(): void
    {
        $this->whatsappTemplates = [];

        if (!$this->activeWhatsAppChannelId) {
            return;
        }

        $channel = CommsChannel::query()->whereKey($this->activeWhatsAppChannelId)->first();
        if (!$channel) {
            return;
        }

        $accountId = $channel->meta['integrations_whatsapp_account_id'] ?? null;
        if (!$accountId) {
            return;
        }

        $templates = IntegrationsWhatsAppTemplate::query()
            ->where('whatsapp_account_id', $accountId)
            ->where('status', 'APPROVED')
            ->orderBy('name')
            ->get();

        $this->whatsappTemplates = $templates->map(fn (IntegrationsWhatsAppTemplate $t) => [
            'id' => (int) $t->id,
            'name' => (string) $t->name,
            'language' => (string) $t->language,
            'category' => (string) ($t->category ?? ''),
            'label' => $t->name . ' (' . $t->language . ')' . ($t->category ? ' — ' . $t->category : ''),
            'components' => $t->components ?? [],
            'body_text' => $this->extractTemplateBodyText($t->components ?? []),
            'variables_count' => $this->countTemplateVariables($t->components ?? []),
        ])->all();
    }

    public function selectWhatsAppTemplate(?int $templateId): void
    {
        $this->whatsappSelectedTemplateId = $templateId;
        $this->whatsappTemplatePreview = [];
        $this->whatsappTemplateVariables = [];

        if (!$templateId) {
            return;
        }

        foreach ($this->whatsappTemplates as $t) {
            if ((int) $t['id'] === $templateId) {
                $bodyText = (string) ($t['body_text'] ?? '');
                $variablesCount = (int) ($t['variables_count'] ?? 0);

                $this->whatsappTemplatePreview = [
                    'id' => $t['id'],
                    'name' => $t['name'],
                    'language' => $t['language'],
                    'category' => $t['category'],
                    'body_text' => $bodyText,
                    'components' => $t['components'],
                    'variables_count' => $variablesCount,
                ];

                for ($i = 1; $i <= $variablesCount; $i++) {
                    $this->whatsappTemplateVariables[$i] = '';
                }

                break;
            }
        }
    }

    public function updatedWhatsappSelectedTemplateId($value): void
    {
        $this->selectWhatsAppTemplate($value ? (int) $value : null);
    }

    private function resetTemplateSelection(): void
    {
        $this->whatsappSelectedTemplateId = null;
        $this->whatsappTemplatePreview = [];
        $this->whatsappTemplateVariables = [];
    }

    public function sendWhatsAppTemplate(): void
    {
        $this->whatsappMessage = null;
        $wasNewThread = !$this->activeWhatsAppThreadId;

        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            $this->whatsappMessage = '⛔️ Kein Team-Kontext gefunden.';
            return;
        }

        if (!$this->activeWhatsAppChannelId) {
            $this->whatsappMessage = '⛔️ Kein WhatsApp Kanal ausgewählt.';
            return;
        }

        if (!$this->whatsappSelectedTemplateId) {
            $this->whatsappMessage = '⛔️ Bitte ein Template auswählen.';
            return;
        }

        $preview = $this->whatsappTemplatePreview;
        if (empty($preview)) {
            $this->whatsappMessage = '⛔️ Template-Daten nicht gefunden.';
            return;
        }

        $variablesCount = (int) ($preview['variables_count'] ?? 0);
        for ($i = 1; $i <= $variablesCount; $i++) {
            if (trim((string) ($this->whatsappTemplateVariables[$i] ?? '')) === '') {
                $this->whatsappMessage = "⛔️ Bitte alle Platzhalter ausfüllen (Variable {$i} fehlt).";
                return;
            }
        }

        $channel = CommsChannel::query()
            ->whereKey($this->activeWhatsAppChannelId)
            ->where('type', 'whatsapp')
            ->where('provider', 'whatsapp_meta')
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            $this->whatsappMessage = '⛔️ WhatsApp Kanal nicht gefunden.';
            return;
        }

        $to = (string) ($this->whatsappCompose['to'] ?? '');

        if ($this->activeWhatsAppThreadId && trim($to) === '') {
            $thread = CommsWhatsAppThread::query()->whereKey($this->activeWhatsAppThreadId)->first();
            if ($thread?->remote_phone_number) {
                $to = (string) $thread->remote_phone_number;
            }
            $this->whatsappCompose['to'] = $to;
        }

        if (trim($to) === '') {
            $this->whatsappMessage = '⛔️ Kein Empfänger angegeben.';
            return;
        }

        $components = [];
        if ($variablesCount > 0) {
            $parameters = [];
            for ($i = 1; $i <= $variablesCount; $i++) {
                $parameters[] = [
                    'type' => 'text',
                    'text' => (string) ($this->whatsappTemplateVariables[$i] ?? ''),
                ];
            }
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters,
            ];
        }

        // URL button — auto-fill with context URL if template has a URL button
        foreach ($preview['components'] ?? [] as $comp) {
            if (($comp['type'] ?? '') === 'BUTTONS') {
                foreach ($comp['buttons'] ?? [] as $btn) {
                    if (($btn['type'] ?? '') === 'URL') {
                        $contextUrl = $this->resolveUrlButtonParameter();
                        if ($contextUrl) {
                            $components[] = [
                                'type' => 'button',
                                'sub_type' => 'url',
                                'index' => 0,
                                'parameters' => [['type' => 'text', 'text' => $contextUrl]],
                            ];
                        }
                        break 2;
                    }
                }
            }
        }

        try {
            /** @var WhatsAppMetaService $svc */
            $svc = app(WhatsAppMetaService::class);
            $message = $svc->sendTemplate(
                $channel,
                $to,
                (string) $preview['name'],
                $components,
                (string) ($preview['language'] ?? 'de'),
                $user
            );
        } catch (\Throwable $e) {
            $this->whatsappMessage = '⛔️ Template-Versand fehlgeschlagen: ' . $e->getMessage();
            return;
        }

        $this->resetTemplateSelection();
        if ($wasNewThread) {
            $this->whatsappCompose['to'] = '';
        }

        if ($wasNewThread && $this->hasContext() && $message?->thread) {
            $newThread = $message->thread;
            if ($newThread) {
                $newThread->addContext($this->contextModel, (int) $this->contextModelId, 'outbound');
                if (!$newThread->context_model) {
                    $newThread->updateQuietly([
                        'context_model' => $this->contextModel,
                        'context_model_id' => $this->contextModelId,
                    ]);
                }
            }
        }

        $this->loadWhatsAppThreads();
        if ($message?->thread) {
            $this->setActiveWhatsAppThread((int) $message->thread->id);
        } elseif ($this->activeWhatsAppThreadId) {
            $this->loadWhatsAppTimeline();
        }

        $this->whatsappMessage = '✅ Template-Nachricht gesendet.';
        $this->buildContextThreadsList();
        $this->forceSetupMode = false;
        $this->dispatch('comms:scroll-bottom');
    }

    private function extractTemplateBodyText(array $components): string
    {
        foreach ($components as $component) {
            if (strtolower((string) ($component['type'] ?? '')) === 'body') {
                return (string) ($component['text'] ?? '');
            }
        }
        return '';
    }

    private function countTemplateVariables(array $components): int
    {
        $bodyText = $this->extractTemplateBodyText($components);
        if ($bodyText === '') {
            return 0;
        }
        preg_match_all('/\{\{(\d+)\}\}/', $bodyText, $matches);
        return !empty($matches[1]) ? (int) max($matches[1]) : 0;
    }

    public function getTemplatePreviewText(): string
    {
        $bodyText = (string) ($this->whatsappTemplatePreview['body_text'] ?? '');
        if ($bodyText === '') {
            return '';
        }

        foreach ($this->whatsappTemplateVariables as $index => $value) {
            $replacement = trim((string) $value) !== '' ? (string) $value : "{{" . $index . "}}";
            $bodyText = str_replace("{{" . $index . "}}", $replacement, $bodyText);
        }

        return $bodyText;
    }

    // -------------------------------------------------------------------------
    // Inline Comms: Context Threads List
    // -------------------------------------------------------------------------

    /**
     * Build a flat list of all email + whatsapp threads for the current context,
     * across all channels, sorted by last activity DESC.
     */
    public function buildContextThreadsList(): void
    {
        $this->allContextThreads = [];

        if (!$this->hasContext()) {
            return;
        }

        $list = [];

        // Email threads across all channels
        $emailThreads = CommsEmailThread::query()
            ->whereIn('comms_channel_id', collect($this->emailChannels)->pluck('id')->all())
            ->forContext($this->contextModel, (int) $this->contextModelId)
            ->get();

        $emailChannelLabels = collect($this->emailChannels)->keyBy('id');

        foreach ($emailThreads as $t) {
            $lastAt = $t->last_inbound_at && (!$t->last_outbound_at || $t->last_inbound_at->greaterThanOrEqualTo($t->last_outbound_at))
                ? $t->last_inbound_at
                : ($t->last_outbound_at ?: $t->updated_at);

            $list[] = [
                'type' => 'email',
                'thread_id' => (int) $t->id,
                'channel_id' => (int) $t->comms_channel_id,
                'label' => (string) ($t->subject ?: 'Ohne Betreff'),
                'counterpart' => (string) ($t->last_inbound_from_address ?: $t->last_outbound_to_address ?: ''),
                'last_at' => $lastAt?->format('d.m. H:i') ?? '',
                'last_at_sort' => $lastAt?->toDateTimeString() ?? '',
                'channel_label' => (string) ($emailChannelLabels[(int) $t->comms_channel_id]['label'] ?? ''),
            ];
        }

        // WhatsApp threads across all channels
        $waThreads = CommsWhatsAppThread::query()
            ->whereIn('comms_channel_id', collect($this->whatsappChannels)->pluck('id')->all())
            ->forContext($this->contextModel, (int) $this->contextModelId)
            ->get();

        $waChannelLabels = collect($this->whatsappChannels)->keyBy('id');

        foreach ($waThreads as $t) {
            $lastAt = $t->last_inbound_at && (!$t->last_outbound_at || $t->last_inbound_at->greaterThanOrEqualTo($t->last_outbound_at))
                ? $t->last_inbound_at
                : ($t->last_outbound_at ?: $t->updated_at);

            $waChannel = $waChannelLabels[(int) $t->comms_channel_id] ?? [];
            $channelLabel = ($waChannel['name'] ?? '') ?: ($waChannel['label'] ?? '');

            $list[] = [
                'type' => 'whatsapp',
                'thread_id' => (int) $t->id,
                'channel_id' => (int) $t->comms_channel_id,
                'label' => (string) ($t->remote_phone_number ?: '—'),
                'counterpart' => (string) ($t->remote_phone_number ?: ''),
                'last_at' => $lastAt?->format('d.m. H:i') ?? '',
                'last_at_sort' => $lastAt?->toDateTimeString() ?? '',
                'channel_label' => (string) $channelLabel,
            ];
        }

        // Sort by last activity DESC
        usort($list, fn ($a, $b) => strcmp((string) $b['last_at_sort'], (string) $a['last_at_sort']));

        $this->allContextThreads = array_values($list);
    }

    /**
     * Switch to a context thread by its index in $allContextThreads.
     */
    public function switchToContextThread(int $index): void
    {
        if (!isset($this->allContextThreads[$index])) {
            return;
        }

        $this->activeContextThreadIndex = $index;
        $entry = $this->allContextThreads[$index];

        if ($entry['type'] === 'email') {
            $this->activeEmailChannelId = (int) $entry['channel_id'];
            $this->refreshActiveEmailChannelLabel();
            $this->loadEmailThreads();
            $this->setActiveEmailThread((int) $entry['thread_id']);
        } elseif ($entry['type'] === 'whatsapp') {
            $this->activeWhatsAppChannelId = (int) $entry['channel_id'];
            $this->refreshActiveWhatsAppChannelLabel();
            $this->loadWhatsAppThreads();
            $this->setActiveWhatsAppThread((int) $entry['thread_id']);
        }
    }

    // -------------------------------------------------------------------------
    // Refresh & Permissions
    // -------------------------------------------------------------------------

    public function refreshTimelines(): void
    {
        if (!$this->shouldRefreshTimelines()) {
            return;
        }

        if ($this->activeWhatsAppThreadId) {
            $this->loadWhatsAppTimeline();

            $thread = CommsWhatsAppThread::query()->whereKey($this->activeWhatsAppThreadId)->first();
            if ($thread) {
                $wasOpen = $this->whatsappWindowOpen;
                $isNowOpen = $thread->isWindowOpen();

                if ($wasOpen !== $isNowOpen) {
                    $this->whatsappWindowOpen = $isNowOpen;
                    $this->whatsappWindowExpiresAt = $thread->windowExpiresAt()?->toIso8601String();

                    if (!$isNowOpen) {
                        $this->loadWhatsAppTemplates();
                        $this->resetTemplateSelection();
                    } else {
                        $this->resetTemplateSelection();
                    }
                }
            }
        }
        if ($this->activeEmailThreadId) {
            $this->loadEmailTimeline();
        }

        $this->buildContextThreadsList();
    }

    public function canManageProviderConnections(): bool
    {
        $user = Auth::user();
        $team = $user?->currentTeam;
        if (!$user || !$team) {
            return false;
        }

        $rootTeam = method_exists($team, 'getRootTeam') ? $team->getRootTeam() : $team;

        return $rootTeam->users()
            ->where('user_id', $user->id)
            ->wherePivotIn('role', [TeamRole::OWNER->value, TeamRole::ADMIN->value])
            ->exists();
    }

    /**
     * Resolve the URL button parameter from context model (e.g. public form token).
     */
    private function resolveUrlButtonParameter(): ?string
    {
        if (!$this->hasContext()) {
            return null;
        }

        try {
            $model = $this->contextModel::find($this->contextModelId);
            if ($model && method_exists($model, 'getPublicUrl')) {
                $publicUrl = $model->getPublicUrl();
                return basename(parse_url($publicUrl, PHP_URL_PATH));
            }
        } catch (\Throwable $e) {
            // Silent fail
        }

        return null;
    }
}
