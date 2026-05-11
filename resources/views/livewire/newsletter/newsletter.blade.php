<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Newsletter', 'href' => route('crm.newsletters.index')],
            ['label' => $newsletter->name],
        ]">
            <div class="flex items-center gap-2">
                @if($newsletter->canEdit())
                    <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                @endif

                @if($newsletter->isSent())
                    <button type="button" wire:click="duplicate" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                        @svg('heroicon-o-document-duplicate', 'w-4 h-4')
                        <span>Duplizieren</span>
                    </button>
                @endif

                {{-- Prev/Next Navigation --}}
                @if($prevNewsletterId || $nextNewsletterId)
                    <div class="flex items-center gap-1">
                        @if($prevNewsletterId)
                            <a href="{{ route('crm.newsletters.show', $prevNewsletterId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 transition">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </span>
                        @endif
                        @if($nextNewsletterId)
                            <a href="{{ route('crm.newsletters.show', $nextNewsletterId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 transition">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Action buttons based on status --}}
                @if($newsletter->isDraft())
                    @if($this->isDirty)
                        <button type="button" wire:click="save" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">
                            @svg('heroicon-o-check', 'w-4 h-4')
                            <span>Speichern</span>
                        </button>
                    @endif
                    <button type="button" wire:click="schedule" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-blue-300 bg-blue-50 text-blue-700 text-[13px] font-medium hover:bg-blue-100 transition-colors">
                        @svg('heroicon-o-clock', 'w-4 h-4')
                        <span>Planen</span>
                    </button>
                    <button type="button" wire:click="sendNow" wire:confirm="Newsletter jetzt an alle Empfänger senden?" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-green-600 text-white text-[13px] font-medium hover:bg-green-700 transition-colors">
                        @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                        <span>Jetzt senden</span>
                    </button>
                @elseif($newsletter->isScheduled())
                    <button type="button" wire:click="cancelSchedule" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        <span>Abbrechen</span>
                    </button>
                    <button type="button" wire:click="sendNow" wire:confirm="Newsletter jetzt senden?" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-green-600 text-white text-[13px] font-medium hover:bg-green-700 transition-colors">
                        @svg('heroicon-o-paper-airplane', 'w-4 h-4')
                        <span>Jetzt senden</span>
                    </button>
                @elseif($newsletter->isSending())
                    <button type="button" wire:click="cancelSending" wire:confirm="Versand wirklich abbrechen?" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-red-300 bg-red-50 text-red-700 text-[13px] font-medium hover:bg-red-100 transition-colors">
                        @svg('heroicon-o-stop', 'w-4 h-4')
                        <span>Abbrechen</span>
                    </button>
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar"></x-slot>
    <x-slot name="activity"></x-slot>

    <x-ui-page-container>

        {{-- Flash Messages --}}
        @if(session('message'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('message') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        {{-- Hero Header --}}
        <div class="flex items-start gap-4 mb-6 p-4 rounded-xl border border-gray-200 bg-white">
            <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                @svg('heroicon-o-envelope', 'w-6 h-6 text-indigo-600')
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold text-gray-900">{{ $newsletter->name }}</h1>
                    @php
                        $statusClasses = match($newsletter->status) {
                            'draft' => 'bg-gray-100 text-gray-700',
                            'scheduled' => 'bg-blue-100 text-blue-800',
                            'sending' => 'bg-amber-100 text-amber-800',
                            'sent' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-700',
                        };
                        $statusLabel = match($newsletter->status) {
                            'draft' => 'Entwurf',
                            'scheduled' => 'Geplant',
                            'sending' => 'Wird gesendet',
                            'sent' => 'Gesendet',
                            'cancelled' => 'Abgebrochen',
                            default => $newsletter->status,
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">{{ $statusLabel }}</span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-400">
                    @if($newsletter->createdByUser)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            {{ $newsletter->createdByUser->name }}
                        </span>
                    @endif
                    @if($newsletter->scheduled_at)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-clock', 'w-3.5 h-3.5')
                            Geplant: {{ $newsletter->scheduled_at->format('d.m.Y H:i') }}
                        </span>
                    @endif
                    @if($newsletter->sent_at)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-paper-airplane', 'w-3.5 h-3.5')
                            Gesendet: {{ $newsletter->sent_at->format('d.m.Y H:i') }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1">
                        @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                        Erstellt {{ $newsletter->created_at->format('d.m.Y H:i') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex gap-6">
                @foreach([
                    'settings' => 'Einstellungen',
                    'content' => 'Inhalt',
                    'preview' => 'Vorschau',
                    'recipients' => 'Empfänger',
                    'stats' => 'Statistiken',
                ] as $tab => $label)
                    <button wire:click="$set('activeTab', '{{ $tab }}')" class="pb-3 text-[13px] font-medium border-b-2 transition-colors {{ $activeTab === $tab ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab: Einstellungen --}}
        @if($activeTab === 'settings')
            <div class="space-y-6">
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Grundeinstellungen</h3></div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Name</label>
                            <input type="text" wire:model.live.debounce.500ms="name" placeholder="Newsletter-Name" required {{ !$newsletter->canEdit() ? 'disabled' : '' }} class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors disabled:bg-gray-50 disabled:text-gray-500" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Betreff</label>
                            <input type="text" wire:model.live.debounce.500ms="subject" placeholder="E-Mail Betreffzeile" required {{ !$newsletter->canEdit() ? 'disabled' : '' }} class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors disabled:bg-gray-50 disabled:text-gray-500" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Preheader</label>
                            <input type="text" wire:model.live.debounce.500ms="preheader" placeholder="Vorschautext in der Inbox (optional)" {{ !$newsletter->canEdit() ? 'disabled' : '' }} class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors disabled:bg-gray-50 disabled:text-gray-500" />
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Versand</h3></div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail Kanal</label>
                            <select wire:model.live="commsChannelId" {{ !$newsletter->canEdit() ? 'disabled' : '' }} class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors disabled:bg-gray-50">
                                <option value="">– Kanal auswählen –</option>
                                @foreach($this->channels as $ch)
                                    <option value="{{ $ch->id }}">{{ $ch->name }} ({{ $ch->sender_identifier }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Kontaktliste</label>
                            <select wire:model.live="contactListId" {{ !$newsletter->canEdit() ? 'disabled' : '' }} class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors disabled:bg-gray-50">
                                <option value="">– Kontaktliste auswählen –</option>
                                @foreach($this->contactLists as $list)
                                    <option value="{{ $list->id }}">{{ $list->name }} ({{ $list->member_count }} Kontakte)</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Geplanter Versand</label>
                            <input type="datetime-local" wire:model.live="scheduledAt" {{ !$newsletter->canEdit() ? 'disabled' : '' }} class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors disabled:bg-gray-50" />
                        </div>
                    </div>
                </section>
            </div>
        @endif

        {{-- Tab: Inhalt --}}
        @if($activeTab === 'content')
            <div class="space-y-6">
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">HTML Body</h3></div>
                    <div class="p-4">
                        <textarea wire:model.live.debounce.500ms="htmlBody" rows="20" placeholder="HTML-Inhalt des Newsletters..." {{ !$newsletter->canEdit() ? 'disabled' : '' }} class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 font-mono focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors disabled:bg-gray-50"></textarea>
                    </div>
                </section>

                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Text Body (Fallback)</h3></div>
                    <div class="p-4">
                        <textarea wire:model.live.debounce.500ms="textBody" rows="10" placeholder="Plaintext-Version (optional, wird automatisch aus HTML generiert)" {{ !$newsletter->canEdit() ? 'disabled' : '' }} class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 font-mono focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors disabled:bg-gray-50"></textarea>
                    </div>
                </section>
            </div>
        @endif

        {{-- Tab: Vorschau --}}
        @if($activeTab === 'preview')
            <section class="bg-white rounded-lg border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">E-Mail Vorschau</h3></div>
                <div class="p-4">
                    @if($newsletter->html_body)
                        <iframe
                            srcdoc="{{ e($newsletter->html_body) }}"
                            sandbox="allow-same-origin"
                            class="w-full border border-gray-200 rounded-lg"
                            style="min-height: 600px;"
                        ></iframe>
                    @else
                        <div class="text-sm text-gray-400 py-12 text-center">
                            Kein HTML-Inhalt vorhanden. Wechseln Sie zum Tab "Inhalt", um den Newsletter zu bearbeiten.
                        </div>
                    @endif
                </div>
            </section>
        @endif

        {{-- Tab: Empfänger --}}
        @if($activeTab === 'recipients')
            <section class="bg-white rounded-lg border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Empfänger ({{ $this->recipients->count() }})</h3>
                    </div>
                </div>
                @if($this->recipients->count() === 0)
                    <div class="p-6 text-sm text-gray-400 text-center">
                        Noch keine Empfänger. Empfänger werden beim Versand automatisch aus der Kontaktliste generiert.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">E-Mail</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Kontakt</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Status</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Gesendet</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Geöffnet</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Geklickt</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($this->recipients as $recipient)
                                    <tr wire:key="recipient-{{ $recipient->id }}">
                                        <td class="px-4 py-2 text-[13px]">{{ $recipient->email_address }}</td>
                                        <td class="px-4 py-2 text-[13px]">{{ $recipient->contact?->full_name ?? '–' }}</td>
                                        <td class="px-4 py-2 text-[13px]">
                                            @php
                                                $rStatusClasses = match($recipient->status) {
                                                    'pending' => 'bg-gray-100 text-gray-700',
                                                    'sent' => 'bg-blue-100 text-blue-800',
                                                    'delivered' => 'bg-green-100 text-green-800',
                                                    'opened' => 'bg-emerald-100 text-emerald-800',
                                                    'clicked' => 'bg-teal-100 text-teal-800',
                                                    'bounced' => 'bg-red-100 text-red-800',
                                                    'unsubscribed' => 'bg-orange-100 text-orange-800',
                                                    'failed' => 'bg-red-100 text-red-800',
                                                    default => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rStatusClasses }}">{{ $recipient->status }}</span>
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-400">{{ $recipient->sent_at?->format('d.m.Y H:i') ?? '–' }}</td>
                                        <td class="px-4 py-2 text-xs text-gray-400">{{ $recipient->opened_at?->format('d.m.Y H:i') ?? '–' }}</td>
                                        <td class="px-4 py-2 text-xs text-gray-400">{{ $recipient->clicked_at?->format('d.m.Y H:i') ?? '–' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif

        {{-- Tab: Statistiken --}}
        @if($activeTab === 'stats')
            @php $s = $this->stats; @endphp
            @if(empty($s) || ($s['total'] ?? 0) === 0)
                <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-400 text-center">
                    Statistiken sind erst nach dem Versand verfügbar.
                </div>
            @else
                @php
                    $total = max($s['total'] ?? 1, 1);
                    $statCards = [
                        ['label' => 'Gesamt', 'value' => $s['total'] ?? 0, 'pct' => 100, 'color' => 'bg-gray-500'],
                        ['label' => 'Zugestellt', 'value' => $s['delivered'] ?? 0, 'pct' => round((($s['delivered'] ?? 0) / $total) * 100, 1), 'color' => 'bg-green-500'],
                        ['label' => 'Geöffnet', 'value' => $s['opened'] ?? 0, 'pct' => round((($s['opened'] ?? 0) / $total) * 100, 1), 'color' => 'bg-blue-500'],
                        ['label' => 'Geklickt', 'value' => $s['clicked'] ?? 0, 'pct' => round((($s['clicked'] ?? 0) / $total) * 100, 1), 'color' => 'bg-indigo-500'],
                        ['label' => 'Bounced', 'value' => $s['bounced'] ?? 0, 'pct' => round((($s['bounced'] ?? 0) / $total) * 100, 1), 'color' => 'bg-red-500'],
                        ['label' => 'Abgemeldet', 'value' => $s['unsubscribed'] ?? 0, 'pct' => round((($s['unsubscribed'] ?? 0) / $total) * 100, 1), 'color' => 'bg-orange-500'],
                    ];
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($statCards as $card)
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="text-[11px] font-medium text-gray-500 uppercase tracking-wide mb-1">{{ $card['label'] }}</div>
                            <div class="flex items-end gap-2 mb-2">
                                <span class="text-2xl font-bold text-gray-900">{{ number_format($card['value']) }}</span>
                                <span class="text-sm text-gray-400 mb-0.5">{{ $card['pct'] }}%</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="{{ $card['color'] }} h-full rounded-full transition-all" style="width: {{ $card['pct'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </x-ui-page-container>
</x-ui-page>
