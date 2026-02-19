<div wire:poll.5s="refreshTimelines">
    <div
        x-data="{
            activeChannel: 'email',
            activeEmailChannelId: @entangle('activeEmailChannelId').live,
            activeWhatsAppChannelId: @entangle('activeWhatsAppChannelId').live,
            isAtBottom: true,
            autoGrow(el, maxPx = 132){
                if(!el) return;
                el.style.height = 'auto';
                const next = Math.min(el.scrollHeight || 0, maxPx);
                el.style.height = (next > 0 ? next : 44) + 'px';
                el.style.overflowY = (el.scrollHeight > maxPx) ? 'auto' : 'hidden';
            },
            onScroll(el) {
                this.isAtBottom = el.scrollTop > -50;
            },
            scrollToBottom(force = false){
                if (!force && !this.isAtBottom) return;
                this.$nextTick(() => {
                    const el = this.$refs.chatScroll;
                    if (!el) return;
                    el.scrollTop = 0;
                    this.isAtBottom = true;
                });
            },
            init(){
                this.$watch('activeChannel', () => this.scrollToBottom(true));
            }
        }"
        x-on:comms:scroll-bottom.window="scrollToBottom()"
        class="bg-white rounded-lg border border-[var(--ui-border)]/60 overflow-hidden flex flex-col"
        style="height: 500px;"
    >
        {{-- Header: Title + Channel Buttons --}}
        <div class="px-4 py-3 border-b border-[var(--ui-border)]/60 flex items-center gap-3 flex-shrink-0">
            <div class="flex items-center gap-2 flex-shrink-0">
                @svg('heroicon-o-paper-airplane', 'w-5 h-5 text-[var(--ui-primary)]')
                <span class="text-sm font-bold text-[var(--ui-secondary)]">Kommunikation</span>
            </div>

            <div class="flex items-center gap-1 flex-1 min-w-0 overflow-x-auto">
                @forelse($emailChannels as $c)
                    <button
                        type="button"
                        @click="activeChannel = 'email'; activeEmailChannelId = {{ intval($c['id']) }};"
                        class="px-2 py-1 rounded text-[11px] border transition whitespace-nowrap flex items-center gap-1"
                        :class="(activeChannel === 'email' && activeEmailChannelId === {{ intval($c['id']) }})
                            ? 'bg-[var(--ui-primary)] text-white border-[var(--ui-primary)]'
                            : 'bg-[var(--ui-bg)] text-[var(--ui-muted)] border-[var(--ui-border)] hover:text-[var(--ui-secondary)]'"
                    >
                        @svg('heroicon-o-envelope', 'w-3.5 h-3.5')
                        <span class="font-semibold">{{ strval($c['label'] ?? '') }}</span>
                    </button>
                @empty
                    <span class="text-[11px] text-[var(--ui-muted)] px-2">Kein E-Mail Kanal</span>
                @endforelse

                @if(!empty($whatsappChannels))
                    <div class="mx-1 h-4 w-px bg-[var(--ui-border)]/60 flex-shrink-0"></div>
                @endif

                @foreach($whatsappChannels as $wc)
                    <button
                        type="button"
                        @click="activeChannel = 'whatsapp'; activeWhatsAppChannelId = {{ intval($wc['id']) }};"
                        class="px-2 py-1 rounded text-[11px] border transition whitespace-nowrap flex items-center gap-1"
                        :class="(activeChannel === 'whatsapp' && activeWhatsAppChannelId === {{ intval($wc['id']) }})
                            ? 'bg-[var(--ui-primary)] text-white border-[var(--ui-primary)]'
                            : 'bg-[var(--ui-bg)] text-[var(--ui-muted)] border-[var(--ui-border)] hover:text-[var(--ui-secondary)]'"
                    >
                        @svg('heroicon-o-chat-bubble-left-right', 'w-3.5 h-3.5')
                        <span class="font-semibold">{{ $wc['name'] ?: 'WA' }} · {{ strval($wc['label'] ?? '') }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Body: Thread List (1/4) + Timeline + Compose (3/4) --}}
        <div class="flex-1 min-h-0 grid grid-cols-4 gap-0 overflow-hidden">
            {{-- Left: Thread List --}}
            <div class="col-span-1 min-h-0 border-r border-[var(--ui-border)]/60 flex flex-col overflow-hidden">
                <div class="px-3 py-2 border-b border-[var(--ui-border)]/60 flex items-center justify-between flex-shrink-0">
                    <span class="text-[11px] font-semibold text-[var(--ui-secondary)]">Threads</span>
                    <div class="flex items-center gap-2">
                        <button type="button" class="text-[11px] text-[var(--ui-muted)] hover:underline" wire:click="startNewEmailThread" x-show="activeChannel === 'email'" x-cloak>Neu</button>
                        <button type="button" class="text-[11px] text-[var(--ui-muted)] hover:underline" wire:click="startNewWhatsAppThread" x-show="activeChannel === 'whatsapp'" x-cloak>Neu</button>
                    </div>
                </div>

                <div class="p-2 space-y-2 flex-1 min-h-0 overflow-y-auto">
                    {{-- Context card --}}
                    @if($contextModel)
                        <div class="rounded-lg border border-[rgba(var(--ui-primary-rgb),0.2)] bg-[rgba(var(--ui-primary-rgb),0.04)] px-2 py-1.5">
                            <div class="flex items-center gap-1">
                                @svg('heroicon-o-link', 'w-3 h-3 text-[var(--ui-primary)] flex-shrink-0')
                                <span class="text-[10px] font-semibold text-[var(--ui-secondary)] truncate">{{ class_basename($contextModel) }} #{{ $contextModelId }}</span>
                            </div>
                            @if($contextSubject)
                                <div class="mt-0.5 text-[9px] text-[var(--ui-muted)] truncate">{{ $contextSubject }}</div>
                            @endif
                        </div>

                        <button
                            type="button"
                            wire:click="toggleShowAllThreads"
                            class="w-full text-left text-[10px] px-2 py-1 rounded border transition
                                {{ $showAllThreads
                                    ? 'border-[var(--ui-primary)]/30 bg-[rgba(var(--ui-primary-rgb),0.06)] text-[var(--ui-primary)] font-semibold'
                                    : 'border-[var(--ui-border)]/60 bg-[var(--ui-bg)] text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]' }}"
                        >
                            {{ $showAllThreads ? 'Nur Kontext-Threads' : 'Alle Threads' }}
                        </button>
                    @endif

                    {{-- Email Threads --}}
                    <div x-show="activeChannel === 'email'" x-cloak class="space-y-1.5">
                        @if(!$activeEmailChannelId)
                            <div class="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 rounded px-2 py-1.5">
                                Bitte oben einen E-Mail Kanal wählen.
                            </div>
                        @endif

                        @forelse($emailThreads as $t)
                            <div
                                class="w-full rounded-md border border-[var(--ui-border)]/60 bg-[var(--ui-bg)] px-2 py-1.5 hover:bg-[var(--ui-muted-5)] transition cursor-pointer"
                                @if((int) $activeEmailThreadId === (int) $t['id']) style="outline: 1px solid rgba(var(--ui-primary-rgb), 0.4);" @endif
                            >
                                <button
                                    type="button"
                                    wire:click="setActiveEmailThread({{ intval($t['id']) }})"
                                    class="w-full text-left"
                                >
                                    <div class="text-[10px] font-semibold text-[var(--ui-secondary)] truncate">{{ $t['subject'] }}</div>
                                    <div class="mt-0.5 text-[9px] text-[var(--ui-muted)] truncate">{{ $t['counterpart'] ?: '—' }}</div>
                                </button>
                                <div class="mt-1 flex items-center gap-1.5 text-[9px] text-[var(--ui-muted)]">
                                    <span class="inline-flex items-center gap-0.5">
                                        @svg('heroicon-o-chat-bubble-left-ellipsis', 'w-3 h-3')
                                        {{ intval($t['messages_count'] ?? 0) }}
                                    </span>
                                    @if(!empty($t['last_direction']))
                                        <span class="font-semibold {{ $t['last_direction'] === 'inbound' ? 'text-[var(--ui-primary)]' : '' }}">
                                            {{ $t['last_direction'] === 'inbound' ? 'In' : 'Out' }}
                                        </span>
                                    @endif
                                    <span class="ml-auto whitespace-nowrap">{{ $t['last_at'] ?? '' }}</span>
                                    <div x-data="{ confirmDelete: false }">
                                        <button
                                            type="button"
                                            class="text-[var(--ui-muted)] hover:text-red-500 transition"
                                            x-on:click.stop="
                                                if (!confirmDelete) {
                                                    confirmDelete = true;
                                                    setTimeout(() => { confirmDelete = false; }, 2500);
                                                } else {
                                                    $wire.call('deleteEmailThread', {{ intval($t['id']) }});
                                                }
                                            "
                                            title="Thread löschen"
                                        >
                                            <span x-show="!confirmDelete">@svg('heroicon-o-trash', 'w-3 h-3')</span>
                                            <span x-show="confirmDelete" x-cloak class="text-[9px] font-semibold text-red-500">Löschen?</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded border border-[var(--ui-border)]/60 bg-[var(--ui-bg)] p-2">
                                <div class="text-[10px] font-semibold text-[var(--ui-secondary)]">Kein Thread</div>
                                <div class="mt-0.5 text-[9px] text-[var(--ui-muted)]">Klick auf <span class="font-semibold">Neu</span>.</div>
                            </div>
                        @endforelse
                    </div>

                    {{-- WhatsApp Threads --}}
                    <div x-show="activeChannel === 'whatsapp'" x-cloak class="space-y-1.5">
                        @if(!$activeWhatsAppChannelId)
                            <div class="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 rounded px-2 py-1.5">
                                Bitte oben einen WhatsApp Kanal wählen.
                            </div>
                        @endif

                        @forelse($whatsappThreads as $wt)
                            <div
                                class="w-full rounded-md border border-[var(--ui-border)]/60 bg-[var(--ui-bg)] px-2 py-1.5 hover:bg-[var(--ui-muted-5)] transition cursor-pointer"
                                @if((int) $activeWhatsAppThreadId === (int) $wt['id']) style="outline: 1px solid rgba(var(--ui-primary-rgb), 0.4);" @endif
                            >
                                <button
                                    type="button"
                                    wire:click="setActiveWhatsAppThread({{ intval($wt['id']) }})"
                                    class="w-full text-left"
                                >
                                    <div class="text-[10px] font-semibold text-[var(--ui-secondary)] truncate flex items-center gap-1">
                                        @svg('heroicon-o-chat-bubble-left-right', 'w-3 h-3 flex-shrink-0')
                                        {{ $wt['remote_phone'] }}
                                        @if($wt['is_unread'])
                                            <span class="w-1.5 h-1.5 rounded-full bg-[var(--ui-primary)]"></span>
                                        @endif
                                    </div>
                                    @if(!empty($wt['last_message_preview']))
                                        <div class="mt-0.5 text-[9px] text-[var(--ui-muted)] truncate">
                                            {{ \Illuminate\Support\Str::limit($wt['last_message_preview'], 40) }}
                                        </div>
                                    @endif
                                </button>
                                <div class="mt-1 flex items-center gap-1.5 text-[9px] text-[var(--ui-muted)]">
                                    <span class="inline-flex items-center gap-0.5">
                                        @svg('heroicon-o-chat-bubble-left-ellipsis', 'w-3 h-3')
                                        {{ intval($wt['messages_count'] ?? 0) }}
                                    </span>
                                    @if(!empty($wt['last_direction']))
                                        <span class="font-semibold {{ $wt['last_direction'] === 'inbound' ? 'text-[var(--ui-primary)]' : '' }}">
                                            {{ $wt['last_direction'] === 'inbound' ? 'In' : 'Out' }}
                                        </span>
                                    @endif
                                    <span class="ml-auto whitespace-nowrap">{{ $wt['last_at'] ?? '' }}</span>
                                    <div x-data="{ confirmDelete: false }">
                                        <button
                                            type="button"
                                            class="text-[var(--ui-muted)] hover:text-red-500 transition"
                                            x-on:click.stop="
                                                if (!confirmDelete) {
                                                    confirmDelete = true;
                                                    setTimeout(() => { confirmDelete = false; }, 2500);
                                                } else {
                                                    $wire.call('deleteWhatsAppThread', {{ intval($wt['id']) }});
                                                }
                                            "
                                            title="Thread löschen"
                                        >
                                            <span x-show="!confirmDelete">@svg('heroicon-o-trash', 'w-3 h-3')</span>
                                            <span x-show="confirmDelete" x-cloak class="text-[9px] font-semibold text-red-500">Löschen?</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded border border-[var(--ui-border)]/60 bg-[var(--ui-bg)] p-2">
                                <div class="text-[10px] font-semibold text-[var(--ui-secondary)]">Kein Thread</div>
                                <div class="mt-0.5 text-[9px] text-[var(--ui-muted)]">Klick auf <span class="font-semibold">Neu</span>.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right: Timeline + Compose (3/4) --}}
            <div class="col-span-3 min-h-0 flex flex-col overflow-hidden">
                {{-- Timeline --}}
                <div class="flex-1 min-h-0 overflow-y-auto p-3 flex flex-col-reverse" x-ref="chatScroll" @scroll="onScroll($el)">
                    <div class="space-y-3 min-w-0">

                        {{-- Email Timeline --}}
                        <div x-show="activeChannel==='email'" class="space-y-2" x-cloak>
                            @if(!$activeEmailChannelId)
                                <div class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                    Kein E-Mail Kanal ausgewählt/verfügbar.
                                </div>
                            @else
                                @forelse($emailTimeline as $m)
                                    @php
                                        $isInbound = ($m['direction'] ?? '') === 'inbound';
                                        $from = (string) ($m['from'] ?? '');
                                        $to = (string) ($m['to'] ?? '');
                                        $subject = (string) ($m['subject'] ?? '');
                                        $body = trim((string) ($m['text'] ?? ''));
                                        if ($body === '' && !empty($m['html'])) {
                                            $body = trim(strip_tags((string) $m['html']));
                                        }
                                    @endphp
                                    <div class="flex {{ $isInbound ? 'justify-start' : 'justify-end' }}">
                                        <div class="w-full max-w-3xl rounded-xl border {{ $isInbound ? 'border-[var(--ui-border)]/60' : 'border-[var(--ui-primary)]/20' }} bg-white overflow-hidden">
                                            <div class="px-3 py-2 border-b border-[var(--ui-border)]/60 {{ $isInbound ? 'bg-[var(--ui-bg)]' : 'bg-[rgba(var(--ui-primary-rgb),0.06)]' }}">
                                                <div class="flex items-start justify-between gap-2">
                                                    <div class="min-w-0">
                                                        <div class="flex items-center gap-1.5 min-w-0">
                                                            <div class="text-xs font-semibold text-[var(--ui-secondary)] truncate">
                                                                {{ $subject ?: 'Ohne Betreff' }}
                                                            </div>
                                                            <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $isInbound ? 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)] border border-[var(--ui-border)]/60' : 'bg-[rgba(var(--ui-primary-rgb),0.12)] text-[var(--ui-primary)] border border-[rgba(var(--ui-primary-rgb),0.18)]' }}">
                                                                {{ $isInbound ? 'In' : 'Out' }}
                                                            </span>
                                                        </div>
                                                        <div class="mt-0.5 text-[10px] text-[var(--ui-muted)] truncate">
                                                            <span class="font-semibold">Von:</span> {{ $from ?: '—' }}
                                                            <span class="mx-0.5">·</span>
                                                            <span class="font-semibold">An:</span> {{ $to ?: '—' }}
                                                        </div>
                                                    </div>
                                                    <div class="text-[10px] text-[var(--ui-muted)] whitespace-nowrap">{{ $m['at'] ?? '' }}</div>
                                                </div>
                                            </div>
                                            <div class="px-3 py-3 text-sm text-[var(--ui-secondary)] whitespace-pre-wrap">{{ $body }}</div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-sm text-[var(--ui-muted)]">
                                        Noch keine Nachrichten im Thread.
                                    </div>
                                @endforelse
                            @endif
                        </div>

                        {{-- WhatsApp Timeline --}}
                        <div x-show="activeChannel==='whatsapp'" class="space-y-2" x-cloak>
                            <div class="text-[10px] text-[var(--ui-muted)] flex items-center gap-1.5 flex-wrap">
                                <span class="px-1.5 py-0.5 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-bg)]">WhatsApp</span>
                                <span class="truncate">{{ $activeWhatsAppChannelPhone ?? '' }}</span>
                                @if($activeWhatsAppThreadId || !$whatsappWindowOpen)
                                    @if($whatsappWindowOpen)
                                        <span
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200"
                                            x-data="{
                                                expiresAt: @js($whatsappWindowExpiresAt),
                                                remaining: '',
                                                interval: null,
                                                update() {
                                                    if (!this.expiresAt) { this.remaining = ''; return; }
                                                    const diff = new Date(this.expiresAt) - new Date();
                                                    if (diff <= 0) { this.remaining = ''; clearInterval(this.interval); return; }
                                                    const h = Math.floor(diff / 3600000);
                                                    const m = Math.floor((diff % 3600000) / 60000);
                                                    this.remaining = h + 'h ' + String(m).padStart(2, '0') + 'min';
                                                },
                                                init() { this.update(); this.interval = setInterval(() => this.update(), 30000); },
                                                destroy() { clearInterval(this.interval); }
                                            }"
                                        >
                                            @svg('heroicon-o-check-circle', 'w-3 h-3')
                                            <span>Offen</span>
                                            <template x-if="remaining">
                                                <span class="text-emerald-600" x-text="'· ' + remaining"></span>
                                            </template>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            @svg('heroicon-o-clock', 'w-3 h-3')
                                            Templates
                                        </span>
                                    @endif
                                @endif
                                @if(!$activeWhatsAppThreadId)
                                    <span class="ml-auto text-[9px] text-[var(--ui-muted)]">Neuer Thread</span>
                                @endif
                            </div>

                            {{-- Conversation Thread Selector --}}
                            @if($activeWhatsAppThreadId && !empty($conversationThreads))
                                <div class="flex items-center gap-1 flex-wrap">
                                    <button
                                        type="button"
                                        wire:click="setActiveConversationThread(null)"
                                        class="px-1.5 py-0.5 rounded-full text-[9px] font-medium border transition
                                            {{ !$activeConversationThreadId
                                                ? 'bg-[var(--ui-primary)] text-white border-[var(--ui-primary)]'
                                                : 'bg-[var(--ui-bg)] text-[var(--ui-muted)] border-[var(--ui-border)]/60 hover:text-[var(--ui-secondary)]' }}"
                                    >
                                        Alle
                                    </button>
                                    @foreach($conversationThreads as $ct)
                                        <button
                                            type="button"
                                            wire:click="setActiveConversationThread({{ intval($ct['id']) }})"
                                            class="px-1.5 py-0.5 rounded-full text-[9px] font-medium border transition inline-flex items-center gap-0.5
                                                {{ (int) $activeConversationThreadId === (int) $ct['id']
                                                    ? 'bg-[var(--ui-primary)] text-white border-[var(--ui-primary)]'
                                                    : ($ct['is_active']
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                                                        : 'bg-[var(--ui-bg)] text-[var(--ui-muted)] border-[var(--ui-border)]/60 hover:text-[var(--ui-secondary)]') }}"
                                            title="{{ $ct['started_at'] }}{{ $ct['ended_at'] ? ' – ' . $ct['ended_at'] : ' (aktiv)' }}"
                                        >
                                            @if($ct['is_active'])
                                                <span class="w-1 h-1 rounded-full {{ (int) $activeConversationThreadId === (int) $ct['id'] ? 'bg-white' : 'bg-emerald-500' }}"></span>
                                            @endif
                                            {{ $ct['label'] }}
                                            <span class="{{ (int) $activeConversationThreadId === (int) $ct['id'] ? 'text-white/70' : 'text-[var(--ui-muted)]' }}">({{ $ct['messages_count'] }})</span>
                                        </button>
                                    @endforeach

                                    {{-- New conversation thread --}}
                                    <div x-data="{ showInput: false }" class="inline-flex items-center gap-0.5">
                                        <button
                                            type="button"
                                            x-show="!showInput"
                                            @click="showInput = true; $nextTick(() => $refs.convLabel.focus())"
                                            class="px-1.5 py-0.5 rounded-full text-[9px] font-medium border border-dashed border-[var(--ui-border)]/60 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition inline-flex items-center gap-0.5"
                                        >
                                            @svg('heroicon-o-plus', 'w-2.5 h-2.5')
                                            Neu
                                        </button>
                                        <div x-show="showInput" x-cloak class="inline-flex items-center gap-0.5">
                                            <input
                                                type="text"
                                                x-ref="convLabel"
                                                wire:model="newConversationThreadLabel"
                                                @keydown.enter="$wire.startNewConversationThread(); showInput = false;"
                                                @keydown.escape="showInput = false"
                                                class="px-1.5 py-0.5 text-[10px] border border-[var(--ui-border)] rounded focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)] w-24"
                                                placeholder="z.B. Onboarding"
                                            />
                                            <button
                                                type="button"
                                                wire:click="startNewConversationThread"
                                                @click="showInput = false"
                                                class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-[var(--ui-primary)] text-white"
                                            >
                                                OK
                                            </button>
                                            <button type="button" @click="showInput = false" class="text-[var(--ui-muted)]">
                                                @svg('heroicon-o-x-mark', 'w-3 h-3')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @elseif($activeWhatsAppThreadId && empty($conversationThreads))
                                <div x-data="{ showInput: false }">
                                    <button
                                        type="button"
                                        x-show="!showInput"
                                        @click="showInput = true; $nextTick(() => $refs.convLabelFirst.focus())"
                                        class="px-1.5 py-0.5 rounded text-[9px] font-medium border border-dashed border-[var(--ui-border)]/60 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition inline-flex items-center gap-0.5"
                                    >
                                        @svg('heroicon-o-chat-bubble-bottom-center-text', 'w-3 h-3')
                                        Konversation unterteilen
                                    </button>
                                    <div x-show="showInput" x-cloak class="inline-flex items-center gap-0.5">
                                        <input
                                            type="text"
                                            x-ref="convLabelFirst"
                                            wire:model="newConversationThreadLabel"
                                            @keydown.enter="$wire.startNewConversationThread(); showInput = false;"
                                            @keydown.escape="showInput = false"
                                            class="px-1.5 py-0.5 text-[10px] border border-[var(--ui-border)] rounded focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)] w-28"
                                            placeholder="Label (z.B. Bewerbung)"
                                        />
                                        <button
                                            type="button"
                                            wire:click="startNewConversationThread"
                                            @click="showInput = false"
                                            class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-[var(--ui-primary)] text-white"
                                        >
                                            Starten
                                        </button>
                                        <button type="button" @click="showInput = false" class="text-[var(--ui-muted)]">
                                            @svg('heroicon-o-x-mark', 'w-3 h-3')
                                        </button>
                                    </div>
                                </div>
                            @endif

                            {{-- Read-only indicator --}}
                            @if($viewingConversationHistory && $activeConversationThreadId)
                                <div class="rounded border border-amber-200 bg-amber-50 px-2 py-1.5 flex items-center gap-1.5">
                                    @svg('heroicon-o-archive-box', 'w-3.5 h-3.5 text-amber-600 flex-shrink-0')
                                    <span class="text-[10px] text-amber-800 font-medium">
                                        Archiviert (nur lesen)
                                        @php
                                            $archivedLabel = '';
                                            foreach ($conversationThreads as $ct) {
                                                if ((int) $ct['id'] === (int) $activeConversationThreadId) {
                                                    $archivedLabel = $ct['label'];
                                                    break;
                                                }
                                            }
                                        @endphp
                                        @if($archivedLabel) – {{ $archivedLabel }} @endif
                                    </span>
                                </div>
                            @endif

                            @if(!$activeWhatsAppChannelId)
                                <div class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                    Kein WhatsApp Kanal ausgewählt/verfügbar.
                                </div>
                            @else
                                @if($activeWhatsAppThreadId)
                                    <div class="space-y-1.5">
                                        @forelse($whatsappTimeline as $wm)
                                            @php
                                                $isInbound = ($wm['direction'] ?? '') === 'inbound';
                                                $body = (string) ($wm['body'] ?? '');
                                                $at = (string) ($wm['at'] ?? '');
                                                $fullAt = (string) ($wm['full_at'] ?? '');
                                                $sentBy = (string) ($wm['sent_by'] ?? '');
                                                $status = (string) ($wm['status'] ?? '');
                                                $messageType = (string) ($wm['message_type'] ?? 'text');
                                                $mediaDisplayType = (string) ($wm['media_display_type'] ?? $messageType);
                                                $hasMedia = (bool) ($wm['has_media'] ?? false);
                                                $attachments = $wm['attachments'] ?? [];
                                            @endphp

                                            @if($isInbound)
                                                <div class="flex justify-start">
                                                    <div class="max-w-[85%] rounded-2xl bg-white border border-[var(--ui-border)]/60 px-3 py-2">
                                                        <div class="flex items-center gap-1.5 text-[9px] text-[var(--ui-muted)]">
                                                            <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60">
                                                                @svg('heroicon-o-user', 'w-2.5 h-2.5')
                                                            </span>
                                                            <span>Extern</span>
                                                        </div>
                                                        @if($hasMedia && !empty($attachments))
                                                            @foreach($attachments as $att)
                                                                @php
                                                                    $attUrl = $att['url'] ?? null;
                                                                    $attThumb = $att['thumbnail'] ?? $attUrl;
                                                                    $attTitle = $att['title'] ?? 'Datei';
                                                                @endphp
                                                                @if($mediaDisplayType === 'image' && $attUrl)
                                                                    <a href="{{ $attUrl }}" target="_blank" class="block my-1">
                                                                        <img src="{{ $attThumb }}" alt="{{ $attTitle }}" class="rounded-xl max-w-full max-h-48 object-cover" loading="lazy" />
                                                                    </a>
                                                                @elseif($mediaDisplayType === 'sticker' && $attUrl)
                                                                    <div class="my-1"><img src="{{ $attUrl }}" alt="Sticker" class="w-24 h-24 object-contain" loading="lazy" /></div>
                                                                @elseif($mediaDisplayType === 'video' && $attUrl)
                                                                    <div class="my-1"><video controls preload="metadata" class="rounded-xl max-w-full max-h-48"><source src="{{ $attUrl }}" /></video></div>
                                                                @elseif(($mediaDisplayType === 'voice' || $mediaDisplayType === 'audio') && $attUrl)
                                                                    <div class="my-1 flex items-center gap-1.5">
                                                                        @svg('heroicon-o-microphone', 'w-4 h-4 text-[var(--ui-muted)] shrink-0')
                                                                        <audio controls preload="metadata" class="h-7 w-full min-w-[140px]"><source src="{{ $attUrl }}" /></audio>
                                                                    </div>
                                                                @elseif($mediaDisplayType === 'document' && $attUrl)
                                                                    <a href="{{ $attUrl }}" target="_blank" class="flex items-center gap-2 my-1 px-2 py-1.5 rounded-xl bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60 hover:bg-[var(--ui-muted-10)] transition-colors">
                                                                        @svg('heroicon-o-document-text', 'w-4 h-4 text-[var(--ui-secondary)] shrink-0')
                                                                        <span class="text-xs font-medium text-[var(--ui-secondary)] truncate">{{ $attTitle }}</span>
                                                                        @svg('heroicon-o-arrow-down-tray', 'w-3 h-3 text-[var(--ui-muted)] shrink-0')
                                                                    </a>
                                                                @else
                                                                    <div class="flex items-center gap-2 my-1">
                                                                        @svg('heroicon-o-paper-clip', 'w-4 h-4 text-[var(--ui-secondary)]')
                                                                        <span class="text-xs text-[var(--ui-secondary)] truncate">{{ $attTitle }}</span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @elseif($hasMedia && empty($attachments))
                                                            <div class="flex items-center gap-2 my-1">
                                                                @if($mediaDisplayType === 'image' || $mediaDisplayType === 'sticker')
                                                                    @svg('heroicon-o-photo', 'w-4 h-4 text-[var(--ui-muted)]')
                                                                @elseif($mediaDisplayType === 'video')
                                                                    @svg('heroicon-o-video-camera', 'w-4 h-4 text-[var(--ui-muted)]')
                                                                @elseif($mediaDisplayType === 'voice' || $mediaDisplayType === 'audio')
                                                                    @svg('heroicon-o-microphone', 'w-4 h-4 text-[var(--ui-muted)]')
                                                                @else
                                                                    @svg('heroicon-o-document-text', 'w-4 h-4 text-[var(--ui-muted)]')
                                                                @endif
                                                                <span class="text-xs text-[var(--ui-muted)]">{{ ucfirst($mediaDisplayType) }}</span>
                                                            </div>
                                                        @endif
                                                        @if($body)
                                                            <div class="text-sm text-[var(--ui-secondary)] whitespace-pre-wrap">{{ $body }}</div>
                                                        @endif
                                                        <div class="mt-0.5 text-[9px] text-[var(--ui-muted)] text-right" title="{{ $fullAt }}">{{ $at }}</div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="flex justify-end">
                                                    <div class="max-w-[85%] rounded-2xl bg-[#dcf8c6] border border-[var(--ui-border)]/60 px-3 py-2">
                                                        <div class="flex items-center justify-end gap-1.5 text-[9px] text-[var(--ui-muted)]">
                                                            @if($messageType === 'template')
                                                                <span class="inline-flex items-center gap-0.5 px-1 py-0.5 rounded border border-[var(--ui-border)]/40 bg-white/50 text-[9px]">
                                                                    @svg('heroicon-o-document-text', 'w-2.5 h-2.5')
                                                                    Template
                                                                </span>
                                                            @endif
                                                            <span>{{ $sentBy ?: 'Ich' }}</span>
                                                        </div>
                                                        @if($hasMedia && !empty($attachments))
                                                            @foreach($attachments as $att)
                                                                @php
                                                                    $attUrl = $att['url'] ?? null;
                                                                    $attThumb = $att['thumbnail'] ?? $attUrl;
                                                                    $attTitle = $att['title'] ?? 'Datei';
                                                                @endphp
                                                                @if($mediaDisplayType === 'image' && $attUrl)
                                                                    <a href="{{ $attUrl }}" target="_blank" class="block my-1">
                                                                        <img src="{{ $attThumb }}" alt="{{ $attTitle }}" class="rounded-xl max-w-full max-h-48 object-cover" loading="lazy" />
                                                                    </a>
                                                                @elseif($mediaDisplayType === 'video' && $attUrl)
                                                                    <div class="my-1"><video controls preload="metadata" class="rounded-xl max-w-full max-h-48"><source src="{{ $attUrl }}" /></video></div>
                                                                @elseif(($mediaDisplayType === 'voice' || $mediaDisplayType === 'audio') && $attUrl)
                                                                    <div class="my-1 flex items-center gap-1.5">
                                                                        @svg('heroicon-o-microphone', 'w-4 h-4 text-[var(--ui-muted)] shrink-0')
                                                                        <audio controls preload="metadata" class="h-7 w-full min-w-[140px]"><source src="{{ $attUrl }}" /></audio>
                                                                    </div>
                                                                @elseif($mediaDisplayType === 'document' && $attUrl)
                                                                    <a href="{{ $attUrl }}" target="_blank" class="flex items-center gap-2 my-1 px-2 py-1.5 rounded-xl bg-white/60 border border-[var(--ui-border)]/60 hover:bg-white transition-colors">
                                                                        @svg('heroicon-o-document-text', 'w-4 h-4 text-[var(--ui-secondary)] shrink-0')
                                                                        <span class="text-xs font-medium text-[var(--ui-secondary)] truncate">{{ $attTitle }}</span>
                                                                        @svg('heroicon-o-arrow-down-tray', 'w-3 h-3 text-[var(--ui-muted)] shrink-0')
                                                                    </a>
                                                                @else
                                                                    <div class="flex items-center gap-2 my-1">
                                                                        @svg('heroicon-o-paper-clip', 'w-4 h-4 text-[var(--ui-secondary)]')
                                                                        <span class="text-xs text-[var(--ui-secondary)] truncate">{{ $attTitle }}</span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        @endif
                                                        @if($body)
                                                            <div class="text-sm text-[var(--ui-secondary)] whitespace-pre-wrap">{{ $body }}</div>
                                                        @endif
                                                        <div class="mt-0.5 flex items-center justify-end gap-0.5 text-[9px] text-[var(--ui-muted)]">
                                                            <span title="{{ $fullAt }}">{{ $at }}</span>
                                                            @if($status === 'read')
                                                                <span class="text-blue-500">✓✓</span>
                                                            @elseif($status === 'delivered')
                                                                <span class="text-[var(--ui-muted)]">✓✓</span>
                                                            @elseif($status === 'sent')
                                                                <span class="text-[var(--ui-muted)]">✓</span>
                                                            @elseif($status === 'failed')
                                                                <span class="text-red-500">✕</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        @empty
                                            <div class="text-sm text-[var(--ui-muted)]">
                                                Noch keine Nachrichten im Thread.
                                            </div>
                                        @endforelse
                                    </div>
                                @else
                                    <div class="rounded-xl border border-[var(--ui-border)]/60 bg-[var(--ui-bg)] p-3">
                                        <div class="text-sm font-semibold text-[var(--ui-secondary)]">Neuer WhatsApp Thread</div>
                                        <div class="mt-1 text-sm text-[var(--ui-muted)]">
                                            Gib unten eine Telefonnummer und Nachricht ein.
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Compose --}}
                <div class="border-t border-[var(--ui-border)]/60 p-2.5 flex-shrink-0 bg-[var(--ui-surface)]">
                    <form method="post" action="javascript:void(0)" onsubmit="return false;">
                        {{-- Email Compose --}}
                        <template x-if="activeChannel==='email'">
                            <div class="w-full space-y-1.5">
                                @if(!$activeEmailThreadId)
                                    <div class="grid grid-cols-2 gap-1.5">
                                        <x-ui-input-text
                                            name="emailCompose.to"
                                            label="An"
                                            placeholder="empfaenger@firma.de"
                                            wire:model.live="emailCompose.to"
                                        />
                                        <x-ui-input-text
                                            name="emailCompose.subject"
                                            label="Betreff"
                                            placeholder="Betreff…"
                                            wire:model.live="emailCompose.subject"
                                        />
                                    </div>
                                @endif
                                <div class="flex gap-1.5 items-end w-full">
                                    <textarea
                                        x-ref="emailBody"
                                        x-init="$nextTick(() => autoGrow($refs.emailBody))"
                                        @input="autoGrow($event.target)"
                                        @focus="autoGrow($event.target)"
                                        @keydown.enter="if(!$event.shiftKey){ $event.preventDefault(); $wire.sendEmail(); }"
                                        rows="1"
                                        wire:model="emailCompose.body"
                                        class="flex-1 w-full px-3 py-1.5 border border-[var(--ui-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] resize-none text-sm"
                                        placeholder="Nachricht…"
                                    ></textarea>
                                    <x-ui-button
                                        variant="primary"
                                        size="sm"
                                        wire:click="sendEmail"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="animate-pulse"
                                        wire:target="sendEmail"
                                        class="h-8 self-end"
                                    >
                                        <span wire:loading.remove wire:target="sendEmail">Senden</span>
                                        <span wire:loading wire:target="sendEmail">Sende…</span>
                                    </x-ui-button>
                                </div>
                                @error('emailCompose.body')
                                    <div class="text-xs text-[color:var(--ui-danger)]">{{ $message }}</div>
                                @enderror
                                @if($emailMessage)
                                    <div class="text-xs text-[var(--ui-secondary)]">{{ $emailMessage }}</div>
                                @endif
                            </div>
                        </template>

                        {{-- WhatsApp Compose --}}
                        <template x-if="activeChannel==='whatsapp'">
                            <div class="w-full space-y-1.5">
                                @if($viewingConversationHistory)
                                    <div class="flex items-center gap-1.5 px-2 py-1.5 rounded bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60">
                                        @svg('heroicon-o-lock-closed', 'w-3.5 h-3.5 text-[var(--ui-muted)] flex-shrink-0')
                                        <span class="text-[10px] text-[var(--ui-muted)]">Archivierter Thread. Wechsle zum aktiven Thread, um zu senden.</span>
                                    </div>
                                @else
                                    @if(!$activeWhatsAppThreadId)
                                        <x-ui-input-text
                                            name="whatsappCompose.to"
                                            label="An (Telefonnummer)"
                                            placeholder="+49 172 123 45 67"
                                            wire:model.live="whatsappCompose.to"
                                        />
                                    @endif

                                    @if($whatsappWindowOpen)
                                        <div class="flex gap-1.5 items-end w-full">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-[var(--ui-border)] bg-[var(--ui-bg)] text-[var(--ui-muted)] opacity-60 cursor-not-allowed"
                                                title="Anhang (bald verfügbar)"
                                                disabled
                                            >
                                                @svg('heroicon-o-paper-clip', 'w-4 h-4')
                                            </button>
                                            <textarea
                                                x-ref="waBody"
                                                x-init="$nextTick(() => autoGrow($refs.waBody))"
                                                @input="autoGrow($event.target)"
                                                @focus="autoGrow($event.target)"
                                                @keydown.enter="if(!$event.shiftKey){ $event.preventDefault(); $wire.sendWhatsApp(); }"
                                                rows="1"
                                                wire:model="whatsappCompose.body"
                                                class="flex-1 px-3 py-1.5 border border-[var(--ui-border)] rounded-lg focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)] resize-none text-sm"
                                                placeholder="Nachricht…"
                                            ></textarea>
                                            <x-ui-button
                                                variant="primary"
                                                size="sm"
                                                wire:click="sendWhatsApp"
                                                wire:loading.attr="disabled"
                                                wire:loading.class="animate-pulse"
                                                wire:target="sendWhatsApp"
                                                class="h-8 self-end"
                                            >
                                                <span wire:loading.remove wire:target="sendWhatsApp">Senden</span>
                                                <span wire:loading wire:target="sendWhatsApp">Sende…</span>
                                            </x-ui-button>
                                        </div>
                                    @else
                                        {{-- Closed window: Template mode --}}
                                        <div class="rounded border border-amber-300 bg-amber-50 px-2 py-1.5">
                                            <div class="flex items-start gap-1.5">
                                                @svg('heroicon-o-clock', 'w-3.5 h-3.5 text-amber-600 flex-shrink-0 mt-0.5')
                                                <div class="text-[10px] text-amber-800">
                                                    <span class="font-semibold">24h-Fenster geschlossen.</span>
                                                    Nur vorab genehmigte Templates können gesendet werden.
                                                </div>
                                            </div>
                                        </div>

                                        @if(!empty($whatsappTemplates))
                                            <x-ui-input-select
                                                name="whatsappSelectedTemplateId"
                                                label="Template"
                                                :options="$whatsappTemplates"
                                                optionValue="id"
                                                optionLabel="label"
                                                :nullable="true"
                                                nullLabel="– Template wählen –"
                                                wire:model.live="whatsappSelectedTemplateId"
                                            />

                                            @if(!empty($whatsappTemplatePreview))
                                                <div class="rounded border border-[var(--ui-border)]/60 bg-white p-2 space-y-2">
                                                    <div class="flex items-center gap-1.5">
                                                        @svg('heroicon-o-document-text', 'w-3.5 h-3.5 text-[var(--ui-primary)]')
                                                        <span class="text-[10px] font-semibold text-[var(--ui-secondary)]">{{ $whatsappTemplatePreview['name'] ?? '' }}</span>
                                                        <span class="text-[9px] text-[var(--ui-muted)] px-1 py-0.5 rounded-full border border-[var(--ui-border)]/60 bg-[var(--ui-bg)]">{{ $whatsappTemplatePreview['language'] ?? '' }}</span>
                                                    </div>

                                                    <div class="rounded bg-[#dcf8c6] border border-[var(--ui-border)]/30 px-2 py-1.5">
                                                        <div class="text-xs text-[var(--ui-secondary)] whitespace-pre-wrap">{{ $this->getTemplatePreviewText() }}</div>
                                                    </div>

                                                    @if(($whatsappTemplatePreview['variables_count'] ?? 0) > 0)
                                                        <div class="space-y-1.5">
                                                            <div class="text-[10px] font-semibold text-[var(--ui-secondary)]">Platzhalter</div>
                                                            @for($i = 1; $i <= $whatsappTemplatePreview['variables_count']; $i++)
                                                                <div class="flex items-center gap-1.5">
                                                                    <span class="text-[10px] text-[var(--ui-muted)] font-mono w-8 flex-shrink-0">&#123;&#123;{{ $i }}&#125;&#125;</span>
                                                                    <input
                                                                        type="text"
                                                                        wire:model.live="whatsappTemplateVariables.{{ $i }}"
                                                                        class="flex-1 px-2 py-1 border border-[var(--ui-border)] rounded text-xs focus:outline-none focus:ring-1 focus:ring-[var(--ui-primary)]"
                                                                        placeholder="Variable {{ $i }}…"
                                                                    />
                                                                </div>
                                                            @endfor
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="flex justify-end">
                                                    <x-ui-button
                                                        variant="primary"
                                                        size="sm"
                                                        wire:click="sendWhatsAppTemplate"
                                                        wire:loading.attr="disabled"
                                                        wire:loading.class="animate-pulse"
                                                        wire:target="sendWhatsAppTemplate"
                                                        class="h-8"
                                                    >
                                                        <span wire:loading.remove wire:target="sendWhatsAppTemplate">Template senden</span>
                                                        <span wire:loading wire:target="sendWhatsAppTemplate">Sende…</span>
                                                    </x-ui-button>
                                                </div>
                                            @endif
                                        @else
                                            <div class="rounded border border-[var(--ui-border)]/60 bg-[var(--ui-bg)] px-2 py-1.5">
                                                <div class="text-[10px] text-[var(--ui-muted)]">
                                                    Keine Templates verfügbar. Bitte in der Meta Business Suite erstellen.
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                @endif {{-- end viewingConversationHistory --}}

                                @error('whatsappCompose.body')
                                    <div class="text-xs text-[color:var(--ui-danger)]">{{ $message }}</div>
                                @enderror
                                @if($whatsappMessage)
                                    <div class="text-xs text-[var(--ui-secondary)]">{{ $whatsappMessage }}</div>
                                @endif
                            </div>
                        </template>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
