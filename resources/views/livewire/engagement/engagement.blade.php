<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Engagements', 'href' => route('crm.engagements.index')],
            ['label' => $engagement->title],
        ]">
            <div class="flex items-center gap-2">
                <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />

                {{-- Prev/Next Navigation --}}
                @if($prevEngagementId || $nextEngagementId)
                    <div class="flex items-center gap-1">
                        @if($prevEngagementId)
                            <a href="{{ route('crm.engagements.show', $prevEngagementId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 transition">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </span>
                        @endif
                        @if($nextEngagementId)
                            <a href="{{ route('crm.engagements.show', $nextEngagementId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 transition">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </span>
                        @endif
                    </div>
                @endif

                @if($this->isDirty)
                    <button type="button" wire:click="save" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        <span>Speichern</span>
                    </button>
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Verknüpfungen" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-5">
                {{-- Linked Companies --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Unternehmen</h4>
                        <button wire:click="openCompanyLinkModal" class="text-[#ff7a59] hover:opacity-70">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                        </button>
                    </div>
                    @forelse($engagement->companyLinks as $link)
                        @if($link->company)
                            <div class="flex items-center justify-between gap-2 p-2 rounded-lg border border-gray-200 mb-1.5">
                                <a href="{{ route('crm.companies.show', $link->company) }}" wire:navigate class="flex items-center gap-2 min-w-0 hover:text-[#ff7a59] transition text-sm">
                                    @svg('heroicon-o-building-office', 'w-4 h-4 text-gray-400 flex-shrink-0')
                                    <span class="truncate">{{ $link->company->name }}</span>
                                </a>
                                <button wire:click="detachCompany({{ $link->company->id }})" wire:confirm="Verknüpfung entfernen?" class="text-gray-400 hover:text-red-500 flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                                </button>
                            </div>
                        @endif
                    @empty
                        <p class="text-xs text-gray-400">Keine Unternehmen verknüpft.</p>
                    @endforelse
                </div>

                {{-- Linked Contacts --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Kontakte</h4>
                        <button wire:click="openContactLinkModal" class="text-[#ff7a59] hover:opacity-70">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                        </button>
                    </div>
                    @forelse($engagement->contactLinks as $link)
                        @if($link->contact)
                            <div class="flex items-center justify-between gap-2 p-2 rounded-lg border border-gray-200 mb-1.5">
                                <a href="{{ route('crm.contacts.show', $link->contact) }}" wire:navigate class="flex items-center gap-2 min-w-0 hover:text-[#ff7a59] transition text-sm">
                                    @svg('heroicon-o-user', 'w-4 h-4 text-gray-400 flex-shrink-0')
                                    <span class="truncate">{{ $link->contact->full_name }}</span>
                                </a>
                                <button wire:click="detachContact({{ $link->contact->id }})" wire:confirm="Verknüpfung entfernen?" class="text-gray-400 hover:text-red-500 flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                                </button>
                            </div>
                        @endif
                    @empty
                        <p class="text-xs text-gray-400">Keine Kontakte verknüpft.</p>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="h-full flex flex-col">
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    @forelse($engagement->activities as $activity)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($activity->activity_type === 'manual')
                                    <div class="w-6 h-6 rounded-full bg-orange-100 flex items-center justify-center">
                                        @svg('heroicon-s-pencil', 'w-3 h-3 text-[#ff7a59]')
                                    </div>
                                @elseif($activity->name === 'created')
                                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                                        @svg('heroicon-s-plus', 'w-3 h-3 text-green-600')
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center">
                                        @svg('heroicon-s-cog-6-tooth', 'w-3 h-3 text-gray-400')
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow min-w-0">
                                @if($activity->activity_type === 'manual')
                                    <p class="text-sm">{{ $activity->message }}</p>
                                @elseif($activity->name === 'created')
                                    <p class="text-sm text-gray-400">Engagement erstellt</p>
                                @elseif($activity->name === 'updated' && is_array($activity->properties))
                                    <p class="text-sm text-gray-400">
                                        {{ collect($activity->properties)->keys()->map(fn($k) => str_replace('_', ' ', ucfirst($k)))->implode(', ') }} geändert
                                    </p>
                                @else
                                    <p class="text-sm text-gray-400">{{ $activity->name }}</p>
                                @endif
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-gray-400">
                                        {{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                    @if($activity->activity_type === 'manual' && $activity->user_id === auth()->id())
                                        <button wire:click="deleteNote({{ $activity->id }})" wire:confirm="Notiz wirklich löschen?" class="text-xs text-red-400 hover:text-red-600">
                                            @svg('heroicon-o-trash', 'w-3 h-3')
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">Keine Aktivitäten vorhanden.</p>
                    @endforelse
                </div>

                <div class="flex-shrink-0 border-t border-gray-200 p-3">
                    @error('newNote')
                        <p class="text-xs text-red-500 mb-2">{{ $message }}</p>
                    @enderror
                    <form wire:submit="addNote" class="flex items-end gap-2">
                        <textarea
                            wire:model="newNote"
                            rows="2"
                            class="flex-1 text-sm rounded-lg border border-gray-200 bg-white p-2 focus:border-[#ff7a59] focus:ring-1 focus:ring-[#ff7a59] outline-none resize-none"
                            placeholder="Notiz hinzufügen..."
                        ></textarea>
                        <button type="submit" class="flex-shrink-0 w-8 h-8 rounded-lg bg-[#ff7a59] text-white flex items-center justify-center hover:opacity-90 transition">
                            @svg('heroicon-s-arrow-up', 'w-4 h-4')
                        </button>
                    </form>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Hero Header --}}
        <div class="flex items-start gap-4 mb-6 p-4 rounded-xl border border-gray-200 bg-white">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0
                @switch($engagement->type)
                    @case('note') bg-blue-100 @break
                    @case('call') bg-green-100 @break
                    @case('meeting') bg-purple-100 @break
                    @case('task') bg-amber-100 @break
                @endswitch
            ">
                @switch($engagement->type)
                    @case('note') @svg('heroicon-o-pencil-square', 'w-6 h-6 text-blue-600') @break
                    @case('call') @svg('heroicon-o-phone', 'w-6 h-6 text-green-600') @break
                    @case('meeting') @svg('heroicon-o-calendar', 'w-6 h-6 text-purple-600') @break
                    @case('task') @svg('heroicon-o-clipboard-document-check', 'w-6 h-6 text-amber-600') @break
                @endswitch
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold text-gray-900">{{ $engagement->title }}</h1>
                    @php
                        $typeBadge = match($engagement->type) {
                            'note' => ['variant' => 'primary', 'label' => 'Notiz'],
                            'call' => ['variant' => 'success', 'label' => 'Anruf'],
                            'meeting' => ['variant' => 'warning', 'label' => 'Meeting'],
                            'task' => ['variant' => 'secondary', 'label' => 'Aufgabe'],
                            default => ['variant' => 'secondary', 'label' => $engagement->type],
                        };
                        $typeBadgeClass = match($typeBadge['variant']) {
                            'primary' => 'bg-orange-100 text-orange-800',
                            'success' => 'bg-green-100 text-green-800',
                            'warning' => 'bg-amber-100 text-amber-800',
                            'danger' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $typeBadgeClass }}">{{ $typeBadge['label'] }}</span>
                    @if($engagement->status)
                        @php
                            $statusVariant = match($engagement->status) {
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'in_progress' => 'warning',
                                default => 'secondary',
                            };
                            $statusLabel = match($engagement->status) {
                                'open' => 'Offen',
                                'in_progress' => 'In Bearbeitung',
                                'completed' => 'Abgeschlossen',
                                'cancelled' => 'Abgebrochen',
                                default => $engagement->status,
                            };
                            $statusBadgeClass = match($statusVariant) {
                                'primary' => 'bg-orange-100 text-orange-800',
                                'success' => 'bg-green-100 text-green-800',
                                'warning' => 'bg-amber-100 text-amber-800',
                                'danger' => 'bg-red-100 text-red-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-400">
                    @if($engagement->ownedByUser)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            {{ $engagement->ownedByUser->name }}
                        </span>
                    @endif
                    @if($engagement->scheduled_at)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-clock', 'w-3.5 h-3.5')
                            {{ $engagement->scheduled_at->format('d.m.Y H:i') }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1">
                        @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                        Erstellt {{ $engagement->created_at->format('d.m.Y H:i') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Details --}}
            <section class="bg-white rounded-lg border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Details</h3></div>
                <div class="p-4">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Titel</label>
                            <input type="text" wire:model.live.debounce.500ms="title" placeholder="Titel eingeben..." required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Beschreibung</label>
                            <textarea wire:model.live.debounce.500ms="body" placeholder="Beschreibung (optional)" rows="4" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                        </div>
                        @if(in_array($engagement->type, ['call', 'meeting', 'task']))
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                                <select wire:model.live="status" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                                    <option value="">– Status auswählen –</option>
                                    <option value="open">Offen</option>
                                    <option value="in_progress">In Bearbeitung</option>
                                    <option value="completed">Abgeschlossen</option>
                                    <option value="cancelled">Abgebrochen</option>
                                </select>
                            </div>
                        @endif
                        @if($engagement->type === 'task')
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Priorität</label>
                                <select wire:model.live="priority" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                                    <option value="">– Priorität auswählen –</option>
                                    <option value="low">Niedrig</option>
                                    <option value="medium">Mittel</option>
                                    <option value="high">Hoch</option>
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Zeitplanung --}}
            @if(in_array($engagement->type, ['meeting', 'task']))
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Zeitplanung</h3></div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">{{ $engagement->type === 'task' ? 'Fällig am' : 'Geplant am' }}</label>
                                <input type="datetime-local" wire:model.live="scheduledAt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                            </div>
                            @if($engagement->type === 'meeting')
                                <div>
                                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Ende</label>
                                    <input type="datetime-local" wire:model.live="endedAt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                </div>
                            @endif
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Abgeschlossen am</label>
                                <input type="datetime-local" wire:model.live="completedAt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            {{-- Metadata --}}
            @if($engagement->metadata)
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Metadata</h3></div>
                    <div class="p-4">
                        <pre class="text-xs text-gray-400 bg-gray-50 p-3 rounded-lg overflow-auto max-h-40">{{ json_encode($engagement->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </section>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Contact Link Modal --}}
    <x-ui-modal size="sm" model="contactLinkModalShow">
        <x-slot name="header">Kontakt verknüpfen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Kontakt</label>
                <select wire:model.live="linkContactId" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    <option value="">– Kontakt auswählen –</option>
                    @foreach($this->availableContacts as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->full_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="$set('contactLinkModalShow', false)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="attachContact" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Verknüpfen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Company Link Modal --}}
    <x-ui-modal size="sm" model="companyLinkModalShow">
        <x-slot name="header">Unternehmen verknüpfen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Unternehmen</label>
                <select wire:model.live="linkCompanyId" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    <option value="">– Unternehmen auswählen –</option>
                    @foreach($this->availableCompanies as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="$set('companyLinkModalShow', false)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="attachCompany" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Verknüpfen</button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
