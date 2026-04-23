<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Kontakte', 'href' => route('crm.contacts.index')],
            ['label' => $contact->full_name],
        ]">
            <x-slot name="left">
                {{-- Status --}}
                <div>
                    <select
                        name="contact.contact_status_id"
                        wire:model.live="contact.contact_status_id"
                        class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                    >
                        <option value="">– Status –</option>
                        @foreach($contactStatuses as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </x-slot>

            {{-- Right side actions --}}
            <div class="flex items-center gap-2">
                <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />

                {{-- Blacklist --}}
                <button
                    wire:click="toggleBlacklist"
                    class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium transition border {{ $contact->is_blacklisted ? 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100' : 'border-gray-200 text-gray-400 hover:border-red-300 hover:text-red-600 hover:bg-red-50' }}"
                >
                    @svg('heroicon-s-no-symbol', 'w-3 h-3')
                    {{ $contact->is_blacklisted ? 'Blacklisted' : 'Blacklist' }}
                </button>

                {{-- Prev/Next Navigation --}}
                @if($prevContactId || $nextContactId)
                    <div class="flex items-center gap-1">
                        @if($prevContactId)
                            <a href="{{ route('crm.contacts.show', $prevContactId) }}" wire:navigate class="inline-flex items-center gap-1 px-2 py-1 rounded-md border border-gray-200 hover:bg-gray-50 transition text-xs text-gray-400" title="{{ $prevContactName }}">
                                @svg('heroicon-o-chevron-left', 'w-3.5 h-3.5')
                                <span class="max-w-[6rem] truncate hidden sm:inline">{{ $prevContactName }}</span>
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-left', 'w-3.5 h-3.5')
                            </span>
                        @endif
                        @if($nextContactId)
                            <a href="{{ route('crm.contacts.show', $nextContactId) }}" wire:navigate class="inline-flex items-center gap-1 px-2 py-1 rounded-md border border-gray-200 hover:bg-gray-50 transition text-xs text-gray-400" title="{{ $nextContactName }}">
                                <span class="max-w-[6rem] truncate hidden sm:inline">{{ $nextContactName }}</span>
                                @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-right', 'w-3.5 h-3.5')
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
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-5">
                {{-- Summary Card --}}
                <div class="p-3 bg-gray-50 rounded-lg">
                    <div class="space-y-1 text-sm">
                        <div class="font-semibold text-gray-900">{{ $contact->full_name }}</div>
                        @if($contact->nickname)
                            <div class="text-gray-400">{{ $contact->nickname }}</div>
                        @endif
                        @if($contact->birth_date)
                            <div class="text-gray-400">@svg('heroicon-o-cake', 'w-3.5 h-3.5 inline') {{ $contact->birth_date->format('d.m.Y') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Follow-ups --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-2">Wiedervorlagen</h4>
                    @php
                        $openFollowUps = $contact->followUps->whereNull('completed_at')->sortBy('due_date');
                        $completedFollowUps = $contact->followUps->whereNotNull('completed_at')->sortByDesc('completed_at')->take(3);
                    @endphp
                    @if($openFollowUps->count() > 0)
                        <div class="space-y-1.5 mb-2">
                            @foreach($openFollowUps as $followUp)
                                <div class="flex items-start gap-2 p-2 rounded-lg border {{ $followUp->due_date->lt(now()->startOfDay()) ? 'border-red-300 bg-red-50/50' : ($followUp->due_date->isToday() ? 'border-amber-300 bg-amber-50/50' : 'border-gray-200') }}">
                                    <button wire:click="toggleFollowUp({{ $followUp->id }})" class="mt-0.5 flex-shrink-0">
                                        <div class="w-4 h-4 rounded border-2 {{ $followUp->due_date->lt(now()->startOfDay()) ? 'border-red-400' : ($followUp->due_date->isToday() ? 'border-amber-400' : 'border-gray-200') }} hover:border-[#ff7a59] transition"></div>
                                    </button>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-medium truncate">{{ $followUp->title }}</div>
                                        <div class="text-[10px] {{ $followUp->due_date->lt(now()->startOfDay()) ? 'text-red-600 font-medium' : ($followUp->due_date->isToday() ? 'text-amber-600' : 'text-gray-400') }}">
                                            {{ $followUp->due_date->format('d.m.Y') }}
                                            @if($followUp->due_date->lt(now()->startOfDay()))
                                                (überfällig)
                                            @elseif($followUp->due_date->isToday())
                                                (heute)
                                            @endif
                                        </div>
                                    </div>
                                    <button wire:click="deleteFollowUp({{ $followUp->id }})" wire:confirm="Wiedervorlage löschen?" class="flex-shrink-0 text-gray-400 hover:text-red-500 transition">
                                        @svg('heroicon-o-x-mark', 'w-3 h-3')
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if($completedFollowUps->count() > 0)
                        <div class="space-y-1 mb-2">
                            @foreach($completedFollowUps as $followUp)
                                <div class="flex items-center gap-2 p-1.5 rounded text-gray-400">
                                    <button wire:click="toggleFollowUp({{ $followUp->id }})" class="flex-shrink-0">
                                        <div class="w-4 h-4 rounded border-2 border-green-400 bg-green-400 flex items-center justify-center">
                                            @svg('heroicon-s-check', 'w-2.5 h-2.5 text-white')
                                        </div>
                                    </button>
                                    <span class="text-xs line-through truncate">{{ $followUp->title }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <form wire:submit="addFollowUp" class="space-y-1.5">
                        <div>
                            <input type="text" name="followUpForm.title" wire:model="followUpForm.title" placeholder="Wiedervorlage..." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                            @error('followUpForm.title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end gap-1.5">
                            <div class="flex-1">
                                <input type="date" name="followUpForm.due_date" wire:model="followUpForm.due_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('followUpForm.due_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors flex-shrink-0">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Quick-Links --}}
                <div class="space-y-1">
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'kontaktdaten' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-phone', 'w-4 h-4 text-gray-400')
                            Telefonnummern
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $contact->phoneNumbers->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'kontaktdaten' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-envelope', 'w-4 h-4 text-gray-400')
                            E-Mail-Adressen
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $contact->emailAddresses->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'kontaktdaten' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-map-pin', 'w-4 h-4 text-gray-400')
                            Adressen
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $contact->postalAddresses->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'unternehmen')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'unternehmen' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-building-office', 'w-4 h-4 text-gray-400')
                            Unternehmen
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $contact->contactRelations->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'engagements')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'engagements' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-clipboard-document-list', 'w-4 h-4 text-gray-400')
                            Engagements
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $this->engagements->count() }}</span>
                    </button>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="h-full flex flex-col">
                {{-- Timeline (scrollbar) --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    @forelse($contact->activities as $activity)
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
                                    <p class="text-sm text-gray-400">Kontakt erstellt</p>
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

                {{-- Notiz-Eingabe (fixed bottom) --}}
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
            <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center text-lg font-bold text-[#ff7a59] flex-shrink-0">
                {{ strtoupper(mb_substr($contact->first_name, 0, 1) . mb_substr($contact->last_name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold text-gray-900">{{ $contact->first_name }} {{ $contact->last_name }}</h1>
                    @if($contact->contactStatus)
                        @php
                            $statusBadgeVariant = \Platform\Crm\Models\CrmContactStatus::getVariantForCode($contact->contactStatus->code ?? '');
                            $statusBadgeClasses = match($statusBadgeVariant) {
                                'success' => 'bg-green-100 text-green-800',
                                'danger' => 'bg-red-100 text-red-800',
                                'warning' => 'bg-amber-100 text-amber-800',
                                'primary' => 'bg-orange-100 text-orange-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeClasses }}">
                            {{ $contact->contactStatus->name }}
                        </span>
                    @endif
                    @if($contact->is_blacklisted)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">@svg('heroicon-s-no-symbol', 'w-3 h-3') Blacklisted</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-400">
                    @php
                        $primaryRelation = $contact->contactRelations->where('is_primary', true)->first() ?? $contact->contactRelations->first();
                    @endphp
                    @if($primaryRelation)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-building-office', 'w-3.5 h-3.5')
                            @if($primaryRelation->position) {{ $primaryRelation->position }} @ @endif
                            {{ $primaryRelation->company?->display_name }}
                        </span>
                    @endif
                    @php $primaryEmail = $contact->emailAddresses->where('is_primary', true)->first(); @endphp
                    @if($primaryEmail)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-envelope', 'w-3.5 h-3.5')
                            {{ $primaryEmail->email_address }}
                        </span>
                    @endif
                    @php $primaryPhone = $contact->phoneNumbers->where('is_primary', true)->first(); @endphp
                    @if($primaryPhone)
                        <a href="tel:{{ $primaryPhone->international }}" class="flex items-center gap-1 hover:text-[#ff7a59] transition">
                            @svg('heroicon-o-phone', 'w-3.5 h-3.5')
                            {{ $primaryPhone->national ?: $primaryPhone->raw_input }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex space-x-6">
                <button wire:click="$set('activeTab', 'stammdaten')" class="py-3 px-1 border-b-2 text-sm font-medium transition flex items-center gap-1.5 {{ $activeTab === 'stammdaten' ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-400 hover:text-gray-900 hover:border-gray-200' }}">
                    @svg('heroicon-o-user', 'w-4 h-4')
                    Stammdaten
                </button>
                <button wire:click="$set('activeTab', 'kontaktdaten')" class="py-3 px-1 border-b-2 text-sm font-medium transition flex items-center gap-1.5 {{ $activeTab === 'kontaktdaten' ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-400 hover:text-gray-900 hover:border-gray-200' }}">
                    @svg('heroicon-o-phone', 'w-4 h-4')
                    Kontaktdaten
                </button>
                <button wire:click="$set('activeTab', 'unternehmen')" class="py-3 px-1 border-b-2 text-sm font-medium transition flex items-center gap-1.5 {{ $activeTab === 'unternehmen' ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-400 hover:text-gray-900 hover:border-gray-200' }}">
                    @svg('heroicon-o-building-office', 'w-4 h-4')
                    Unternehmen
                </button>
                <button wire:click="$set('activeTab', 'engagements')" class="py-3 px-1 border-b-2 text-sm font-medium transition flex items-center gap-1.5 {{ $activeTab === 'engagements' ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-400 hover:text-gray-900 hover:border-gray-200' }}">
                    @svg('heroicon-o-clipboard-document-list', 'w-4 h-4')
                    Engagements
                    @if($this->engagements->count() > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $this->engagements->count() }}</span>
                    @endif
                </button>
            </nav>
        </div>

        {{-- Tab: Stammdaten --}}
        @if($activeTab === 'stammdaten')
            <div class="space-y-6">
                {{-- Persönliche Daten --}}
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Persönliche Daten</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Vorname</label>
                                <input type="text" name="contact.first_name" wire:model.live.debounce.500ms="contact.first_name" placeholder="Vorname eingeben..." required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('contact.first_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Nachname</label>
                                <input type="text" name="contact.last_name" wire:model.live.debounce.500ms="contact.last_name" placeholder="Nachname eingeben..." required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('contact.last_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Zweiter Vorname</label>
                                <input type="text" name="contact.middle_name" wire:model.live.debounce.500ms="contact.middle_name" placeholder="Zweiter Vorname (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('contact.middle_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Spitzname</label>
                                <input type="text" name="contact.nickname" wire:model.live.debounce.500ms="contact.nickname" placeholder="Spitzname (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('contact.nickname') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Geburtsdatum</label>
                                <input type="date" name="birthDate" wire:model.live.debounce.500ms="birthDate" placeholder="Geburtsdatum (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('birthDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Weitere Angaben --}}
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-900">Weitere Angaben</h3>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Anrede</label>
                                <select name="contact.salutation_id" wire:model.live="contact.salutation_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                                    <option value="">– Anrede auswählen –</option>
                                    @foreach($salutations as $opt)
                                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Akademischer Titel</label>
                                <select name="contact.academic_title_id" wire:model.live="contact.academic_title_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                                    <option value="">– Titel auswählen –</option>
                                    @foreach($academicTitles as $opt)
                                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Geschlecht</label>
                                <select name="contact.gender_id" wire:model.live="contact.gender_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                                    <option value="">– Geschlecht auswählen –</option>
                                    @foreach($genders as $opt)
                                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Sprache</label>
                                <select name="contact.language_id" wire:model.live="contact.language_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                                    <option value="">– Sprache auswählen –</option>
                                    @foreach($languages as $opt)
                                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                                <textarea name="contact.notes" wire:model.live.debounce.500ms="contact.notes" placeholder="Zusätzliche Notizen (optional)" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                                @error('contact.notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        @endif

        {{-- Tab: Kontaktdaten --}}
        @if($activeTab === 'kontaktdaten')
            <div class="space-y-6">
                {{-- Telefon & E-Mail nebeneinander --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Telefonnummern --}}
                    <section class="bg-white rounded-lg border border-gray-200">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-900">Telefonnummern</h3>
                                <button type="button" wire:click="addPhone" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                    Hinzufügen
                                </button>
                            </div>
                            @if($contact->phoneNumbers->count() > 0)
                                <div class="space-y-2">
                                    @foreach($contact->phoneNumbers as $phone)
                                        <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-200 hover:border-[#ff7a59]/20 cursor-pointer transition" wire:click="editPhone({{ $phone->id }})">
                                            <div class="flex items-center gap-2 min-w-0">
                                                @svg('heroicon-o-phone', 'w-4 h-4 text-gray-400 flex-shrink-0')
                                                <span class="text-sm truncate">{{ $phone->national ?: $phone->raw_input }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                @if($phone->is_primary)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Primär</span>
                                                @endif
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $phone->phoneType->name }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-400 py-3">Keine Telefonnummern vorhanden.</p>
                            @endif
                        </div>
                    </section>

                    {{-- E-Mail-Adressen --}}
                    <section class="bg-white rounded-lg border border-gray-200">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-gray-900">E-Mail-Adressen</h3>
                                <button type="button" wire:click="addEmail" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                    Hinzufügen
                                </button>
                            </div>
                            @if($contact->emailAddresses->count() > 0)
                                <div class="space-y-2">
                                    @foreach($contact->emailAddresses as $email)
                                        <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-200 hover:border-[#ff7a59]/20 cursor-pointer transition" wire:click="editEmail({{ $email->id }})">
                                            <div class="flex items-center gap-2 min-w-0">
                                                @svg('heroicon-o-envelope', 'w-4 h-4 text-gray-400 flex-shrink-0')
                                                <span class="text-sm truncate">{{ $email->email_address }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                @if($email->is_primary)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Primär</span>
                                                @endif
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $email->emailType->name }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-400 py-3">Keine E-Mail-Adressen vorhanden.</p>
                            @endif
                        </div>
                    </section>
                </div>

                {{-- Adressen --}}
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Adressen</h3>
                            <button type="button" wire:click="addAddress" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                Hinzufügen
                            </button>
                        </div>
                        @if($contact->postalAddresses->count() > 0)
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                @foreach($contact->postalAddresses as $address)
                                    <div class="flex items-start justify-between gap-2 p-3 rounded-lg border border-gray-200 hover:border-[#ff7a59]/20 cursor-pointer transition" wire:click="editAddress({{ $address->id }})">
                                        <div class="flex items-start gap-2 min-w-0">
                                            @svg('heroicon-o-map-pin', 'w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5')
                                            <div class="text-sm">
                                                <div>{{ $address->street }} {{ $address->house_number }}</div>
                                                <div class="text-gray-400">{{ $address->postal_code }} {{ $address->city }}</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            @if($address->is_primary)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Primär</span>
                                            @endif
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ $address->addressType->name }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 py-3">Keine Adressen vorhanden.</p>
                        @endif
                    </div>
                </section>
            </div>
        @endif

        {{-- Tab: Unternehmen --}}
        @if($activeTab === 'unternehmen')
            <section class="bg-white rounded-lg border border-gray-200">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Unternehmen</h3>
                        <button type="button" wire:click="addCompany" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                            Hinzufügen
                        </button>
                    </div>
                    @if($contact->contactRelations->count() > 0)
                        <div class="space-y-2">
                            @foreach($contact->contactRelations as $relation)
                                <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200 hover:border-[#ff7a59]/20 cursor-pointer transition" wire:click="editCompany({{ $relation->id }})">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @svg('heroicon-o-building-office', 'w-4 h-4 text-gray-400 flex-shrink-0')
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium">
                                                <a href="{{ route('crm.companies.show', ['company' => $relation->company->id]) }}"
                                                   class="hover:underline text-[#ff7a59]"
                                                   wire:navigate
                                                   @click.stop>
                                                    {{ $relation->company->display_name }}
                                                </a>
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                @if($relation->position)
                                                    {{ $relation->position }} &middot;
                                                @endif
                                                {{ $relation->relationType->name }}
                                                @if($relation->start_date)
                                                    &middot; seit {{ $relation->start_date->format('d.m.Y') }}
                                                    @if($relation->end_date)
                                                        bis {{ $relation->end_date->format('d.m.Y') }}
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        @if($relation->is_primary)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Primär</span>
                                        @endif
                                        @if($relation->is_current)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Aktiv</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Vergangen</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-3">Keine Unternehmen verknüpft.</p>
                    @endif
                </div>
            </section>
        @endif

        {{-- Tab: Engagements --}}
        @if($activeTab === 'engagements')
            <section class="bg-white rounded-lg border border-gray-200">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Engagements</h3>
                        <button type="button" wire:click="openEngagementCreateModal" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                            Hinzufügen
                        </button>
                    </div>
                    @if($this->engagements->count() > 0)
                        <div class="space-y-2">
                            @foreach($this->engagements as $eng)
                                <a href="{{ route('crm.engagements.show', $eng) }}" wire:navigate
                                   class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200 hover:border-[#ff7a59]/20 transition block">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @switch($eng->type)
                                            @case('note')
                                                <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                                    @svg('heroicon-o-pencil-square', 'w-4 h-4 text-blue-600')
                                                </div>
                                                @break
                                            @case('call')
                                                <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                                                    @svg('heroicon-o-phone', 'w-4 h-4 text-green-600')
                                                </div>
                                                @break
                                            @case('meeting')
                                                <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                                                    @svg('heroicon-o-calendar', 'w-4 h-4 text-purple-600')
                                                </div>
                                                @break
                                            @case('task')
                                                <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0">
                                                    @svg('heroicon-o-clipboard-document-check', 'w-4 h-4 text-amber-600')
                                                </div>
                                                @break
                                        @endswitch
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium truncate">{{ $eng->title }}</div>
                                            <div class="text-xs text-gray-400">
                                                {{ $eng->scheduled_at ? $eng->scheduled_at->format('d.m.Y H:i') : $eng->created_at->format('d.m.Y H:i') }}
                                                @if($eng->ownedByUser)
                                                    &middot; {{ $eng->ownedByUser->name }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        @if($eng->status)
                                            @php
                                                $statusVariant = match($eng->status) {
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    'in_progress' => 'warning',
                                                    default => 'secondary',
                                                };
                                                $statusLabel = match($eng->status) {
                                                    'open' => 'Offen',
                                                    'in_progress' => 'In Bearb.',
                                                    'completed' => 'Erledigt',
                                                    'cancelled' => 'Abgebr.',
                                                    default => $eng->status,
                                                };
                                                $statusBadgeClass = match($statusVariant) {
                                                    'success' => 'bg-green-100 text-green-800',
                                                    'danger' => 'bg-red-100 text-red-800',
                                                    'warning' => 'bg-amber-100 text-amber-800',
                                                    default => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-3">Keine Engagements vorhanden.</p>
                    @endif
                </div>
            </section>
        @endif

    </x-ui-page-container>

    {{-- Phone Create Modal --}}
    <x-ui-modal size="sm" model="phoneCreateModalShow">
        <x-slot name="header">Telefonnummer hinzufügen</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefonnummer</label>
                    <input type="text" name="phoneForm.raw_input" wire:model.live="phoneForm.raw_input" required placeholder="0151 1234567" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('phoneForm.raw_input') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select name="phoneForm.country_code" wire:model.live="phoneForm.country_code" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        @foreach($countries as $opt)
                            <option value="{{ $opt->code }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefon-Typ</label>
                <select name="phoneForm.phone_type_id" wire:model.live="phoneForm.phone_type_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($phoneTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="phoneForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Als primäre Telefonnummer markieren</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closePhoneCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="savePhone" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Phone Edit Modal --}}
    <x-ui-modal size="sm" model="phoneEditModalShow">
        <x-slot name="header">Telefonnummer bearbeiten</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefonnummer</label>
                    <input type="text" name="phoneForm.raw_input" wire:model.live="phoneForm.raw_input" required placeholder="0151 1234567" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('phoneForm.raw_input') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select name="phoneForm.country_code" wire:model.live="phoneForm.country_code" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        @foreach($countries as $opt)
                            <option value="{{ $opt->code }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefon-Typ</label>
                <select name="phoneForm.phone_type_id" wire:model.live="phoneForm.phone_type_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($phoneTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="phoneForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Als primäre Telefonnummer markieren</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deletePhoneAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="flex gap-2">
                    <button type="button" wire:click="closePhoneEditModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                    <button type="button" wire:click="savePhone" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Speichern</button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Email Create Modal --}}
    <x-ui-modal size="sm" model="emailCreateModalShow">
        <x-slot name="header">E-Mail-Adresse hinzufügen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail-Adresse</label>
                <input type="email" name="emailForm.email_address" wire:model.live="emailForm.email_address" required placeholder="max.mustermann@example.com" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('emailForm.email_address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail-Typ</label>
                <select name="emailForm.email_type_id" wire:model.live="emailForm.email_type_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($emailTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="emailForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Als primäre E-Mail markieren</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeEmailCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="saveEmail" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Email Edit Modal --}}
    <x-ui-modal size="sm" model="emailEditModalShow">
        <x-slot name="header">E-Mail-Adresse bearbeiten</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail-Adresse</label>
                <input type="email" name="emailForm.email_address" wire:model.live="emailForm.email_address" required placeholder="max.mustermann@example.com" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('emailForm.email_address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail-Typ</label>
                <select name="emailForm.email_type_id" wire:model.live="emailForm.email_type_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($emailTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="emailForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Als primäre E-Mail markieren</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteEmailAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="flex gap-2">
                    <button type="button" wire:click="closeEmailEditModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                    <button type="button" wire:click="saveEmail" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Speichern</button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Address Create Modal --}}
    <x-ui-modal size="lg" model="addressCreateModalShow">
        <x-slot name="header">Adresse hinzufügen</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Straße</label>
                    <input type="text" name="addressForm.street" wire:model.live="addressForm.street" required placeholder="Musterstraße" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.street') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Hausnummer</label>
                    <input type="text" name="addressForm.house_number" wire:model.live="addressForm.house_number" placeholder="123" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.house_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">PLZ</label>
                    <input type="text" name="addressForm.postal_code" wire:model.live="addressForm.postal_code" required placeholder="12345" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.postal_code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Stadt</label>
                    <input type="text" name="addressForm.city" wire:model.live="addressForm.city" required placeholder="Musterstadt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.city') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Zusätzliche Informationen</label>
                <input type="text" name="addressForm.additional_info" wire:model.live="addressForm.additional_info" placeholder="Apartment, Etage, etc." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('addressForm.additional_info') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select name="addressForm.country_id" wire:model.live="addressForm.country_id" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        @foreach($countries as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Bundesland</label>
                    <select name="addressForm.state_id" wire:model.live="addressForm.state_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">–</option>
                        @foreach($states as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Adresstyp</label>
                <select name="addressForm.address_type_id" wire:model.live="addressForm.address_type_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($addressTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="addressForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Als primäre Adresse markieren</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeAddressCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="saveAddress" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Address Edit Modal --}}
    <x-ui-modal size="lg" model="addressEditModalShow">
        <x-slot name="header">Adresse bearbeiten</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Straße</label>
                    <input type="text" name="addressForm.street" wire:model.live="addressForm.street" required placeholder="Musterstraße" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.street') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Hausnummer</label>
                    <input type="text" name="addressForm.house_number" wire:model.live="addressForm.house_number" placeholder="123" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.house_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">PLZ</label>
                    <input type="text" name="addressForm.postal_code" wire:model.live="addressForm.postal_code" required placeholder="12345" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.postal_code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Stadt</label>
                    <input type="text" name="addressForm.city" wire:model.live="addressForm.city" required placeholder="Musterstadt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.city') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Zusätzliche Informationen</label>
                <input type="text" name="addressForm.additional_info" wire:model.live="addressForm.additional_info" placeholder="Apartment, Etage, etc." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('addressForm.additional_info') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select name="addressForm.country_id" wire:model.live="addressForm.country_id" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        @foreach($countries as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Bundesland</label>
                    <select name="addressForm.state_id" wire:model.live="addressForm.state_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">–</option>
                        @foreach($states as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Adresstyp</label>
                <select name="addressForm.address_type_id" wire:model.live="addressForm.address_type_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($addressTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="addressForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Als primäre Adresse markieren</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteAddressAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="flex gap-2">
                    <button type="button" wire:click="closeAddressEditModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                    <button type="button" wire:click="saveAddress" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Speichern</button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Company Create Modal --}}
    <x-ui-modal size="lg" model="companyCreateModalShow">
        <x-slot name="header">Unternehmen hinzufügen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Unternehmen</label>
                <select name="companyRelationForm.company_id" wire:model.live="companyRelationForm.company_id" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    <option value="">– Unternehmen auswählen –</option>
                    @foreach($this->filteredCompanies as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Beziehungstyp</label>
                <select name="companyRelationForm.relation_type_id" wire:model.live="companyRelationForm.relation_type_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($relationTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Position</label>
                <input type="text" name="companyRelationForm.position" wire:model.live="companyRelationForm.position" placeholder="z.B. Geschäftsführer, Abteilungsleiter" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('companyRelationForm.position') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Startdatum</label>
                    <input type="date" name="companyRelationForm.start_date" wire:model.live="companyRelationForm.start_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('companyRelationForm.start_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Enddatum (optional)</label>
                    <input type="date" name="companyRelationForm.end_date" wire:model.live="companyRelationForm.end_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('companyRelationForm.end_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                <textarea name="companyRelationForm.notes" wire:model.live="companyRelationForm.notes" placeholder="Zusätzliche Informationen zur Beziehung" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                @error('companyRelationForm.notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="companyRelationForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Als primäres Unternehmen markieren</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeCompanyCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="saveCompany" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Engagement Create Modal --}}
    <x-ui-modal size="lg" model="engagementCreateModalShow">
        <x-slot name="header">Engagement anlegen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Typ</label>
                <select name="engagementForm.type" wire:model.live="engagementForm.type" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach(collect([['value' => 'note', 'label' => 'Notiz'], ['value' => 'call', 'label' => 'Anruf'], ['value' => 'meeting', 'label' => 'Meeting'], ['value' => 'task', 'label' => 'Aufgabe']]) as $opt)
                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Titel</label>
                <input type="text" name="engagementForm.title" wire:model.live="engagementForm.title" required placeholder="Titel eingeben..." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('engagementForm.title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Beschreibung</label>
                <textarea name="engagementForm.body" wire:model.live="engagementForm.body" placeholder="Beschreibung (optional)" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
            </div>
            @if(in_array($engagementForm['type'], ['call', 'meeting', 'task']))
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                    <select name="engagementForm.status" wire:model.live="engagementForm.status" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Status auswählen –</option>
                        @foreach(collect([['value' => 'open', 'label' => 'Offen'], ['value' => 'in_progress', 'label' => 'In Bearbeitung'], ['value' => 'completed', 'label' => 'Abgeschlossen'], ['value' => 'cancelled', 'label' => 'Abgebrochen']]) as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if(in_array($engagementForm['type'], ['meeting', 'task']))
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">{{ $engagementForm['type'] === 'task' ? 'Fällig am' : 'Geplant am' }}</label>
                    <input type="date" name="engagementForm.scheduled_at" wire:model.live="engagementForm.scheduled_at" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                </div>
            @endif
            @if($engagementForm['type'] === 'task')
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Priorität</label>
                    <select name="engagementForm.priority" wire:model.live="engagementForm.priority" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Priorität auswählen –</option>
                        @foreach(collect([['value' => 'low', 'label' => 'Niedrig'], ['value' => 'medium', 'label' => 'Mittel'], ['value' => 'high', 'label' => 'Hoch']]) as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeEngagementCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="createEngagementForContact" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Engagement anlegen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Company Edit Modal --}}
    <x-ui-modal size="lg" model="companyEditModalShow">
        <x-slot name="header">Unternehmen-Beziehung bearbeiten</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Unternehmen</label>
                <select name="companyRelationForm.company_id" wire:model.live="companyRelationForm.company_id" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    <option value="">– Unternehmen auswählen –</option>
                    @foreach($this->filteredCompanies as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->display_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Beziehungstyp</label>
                <select name="companyRelationForm.relation_type_id" wire:model.live="companyRelationForm.relation_type_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($relationTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Position</label>
                <input type="text" name="companyRelationForm.position" wire:model.live="companyRelationForm.position" placeholder="z.B. Geschäftsführer, Abteilungsleiter" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('companyRelationForm.position') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Startdatum</label>
                    <input type="date" name="companyRelationForm.start_date" wire:model.live="companyRelationForm.start_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('companyRelationForm.start_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Enddatum (optional)</label>
                    <input type="date" name="companyRelationForm.end_date" wire:model.live="companyRelationForm.end_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('companyRelationForm.end_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                <textarea name="companyRelationForm.notes" wire:model.live="companyRelationForm.notes" placeholder="Zusätzliche Informationen zur Beziehung" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                @error('companyRelationForm.notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="companyRelationForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Als primäres Unternehmen markieren</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteCompanyAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="flex gap-2">
                    @if($editingCompanyRelationId)
                        <a href="{{ route('crm.companies.show', ['company' => $companyRelationForm['company_id']]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                            @svg('heroicon-o-building-office', 'w-4 h-4')
                            Zum Unternehmen
                        </a>
                    @endif
                    <button type="button" wire:click="closeCompanyEditModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                    <button type="button" wire:click="saveCompany" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Speichern</button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

</x-ui-page>
