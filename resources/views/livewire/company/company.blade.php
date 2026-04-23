<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Unternehmen', 'href' => route('crm.companies.index')],
            ['label' => $company->display_name],
        ]">
            <div class="flex items-center gap-2">
                <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />

                {{-- Prev/Next Navigation --}}
                @if($prevCompanyId || $nextCompanyId)
                    <div class="flex items-center gap-1">
                        @if($prevCompanyId)
                            <a href="{{ route('crm.companies.show', $prevCompanyId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 transition">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </span>
                        @endif
                        @if($nextCompanyId)
                            <a href="{{ route('crm.companies.show', $nextCompanyId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 transition">
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
                    <button type="button" wire:click="save" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">
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
                        <div class="font-semibold text-gray-900">{{ $company->display_name }}</div>
                        @if($company->legalForm)
                            <div class="text-gray-400">{{ $company->legalForm->name }}</div>
                        @endif
                        @if($company->website)
                            <div class="text-gray-400">
                                @svg('heroicon-o-globe-alt', 'w-3.5 h-3.5 inline')
                                <a href="{{ $company->website }}" target="_blank" class="underline">{{ $company->website }}</a>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                    <select wire:model.live="company.contact_status_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Status auswählen –</option>
                        @foreach($contactStatuses as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Follow-ups --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-2">Wiedervorlagen</h4>
                    @php
                        $openFollowUps = $company->followUps->whereNull('completed_at')->sortBy('due_date');
                        $completedFollowUps = $company->followUps->whereNotNull('completed_at')->sortByDesc('completed_at')->take(3);
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
                            <input type="text" wire:model="followUpForm.title" placeholder="Wiedervorlage..." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                            @error('followUpForm.title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end gap-1.5">
                            <div class="flex-1">
                                <input type="date" wire:model="followUpForm.due_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('followUpForm.due_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <button type="submit" class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">
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
                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $company->phoneNumbers->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'kontaktdaten' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-envelope', 'w-4 h-4 text-gray-400')
                            E-Mail-Adressen
                        </span>
                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $company->emailAddresses->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'kontaktdaten' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-map-pin', 'w-4 h-4 text-gray-400')
                            Adressen
                        </span>
                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $company->postalAddresses->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'kontakte')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'kontakte' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-users', 'w-4 h-4 text-gray-400')
                            Kontakte
                        </span>
                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $company->contactRelations->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'engagements')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'engagements' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-clipboard-document-list', 'w-4 h-4 text-gray-400')
                            Engagements
                        </span>
                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $this->engagements->count() }}</span>
                    </button>
                    <button wire:click="$set('activeTab', 'potenzial')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-gray-50 transition {{ $activeTab === 'potenzial' ? 'bg-gray-50 font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-chart-bar', 'w-4 h-4 text-gray-400')
                            Potenzial
                        </span>
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
                    @forelse($company->activities as $activity)
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
                                    <p class="text-sm text-gray-400">Unternehmen erstellt</p>
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
            <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400 flex-shrink-0">
                @svg('heroicon-o-building-office', 'w-6 h-6')
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold text-gray-900">{{ $company->display_name }}</h1>
                    @if($company->contactStatus)
                        @php
                            $heroVariant = \Platform\Crm\Models\CrmContactStatus::getVariantForCode($company->contactStatus->code ?? '');
                            $heroBadgeClass = match($heroVariant) {
                                'success' => 'bg-green-100 text-green-800',
                                'danger' => 'bg-red-100 text-red-800',
                                'warning' => 'bg-amber-100 text-amber-800',
                                'primary' => 'bg-orange-100 text-orange-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $heroBadgeClass }}">
                            {{ $company->contactStatus->name }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-400">
                    @if($company->legalForm)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-scale', 'w-3.5 h-3.5')
                            {{ $company->legalForm->name }}
                        </span>
                    @endif
                    @if($company->website)
                        <a href="{{ $company->website }}" target="_blank" class="flex items-center gap-1 hover:text-[#ff7a59] transition">
                            @svg('heroicon-o-globe-alt', 'w-3.5 h-3.5')
                            {{ $company->website }}
                        </a>
                    @endif
                    @php $primaryEmail = $company->emailAddresses->where('is_primary', true)->first(); @endphp
                    @if($primaryEmail)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-envelope', 'w-3.5 h-3.5')
                            {{ $primaryEmail->email_address }}
                        </span>
                    @endif
                    @php $primaryPhone = $company->phoneNumbers->where('is_primary', true)->first(); @endphp
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
                    @svg('heroicon-o-building-office', 'w-4 h-4')
                    Stammdaten
                </button>
                <button wire:click="$set('activeTab', 'kontaktdaten')" class="py-3 px-1 border-b-2 text-sm font-medium transition flex items-center gap-1.5 {{ $activeTab === 'kontaktdaten' ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-400 hover:text-gray-900 hover:border-gray-200' }}">
                    @svg('heroicon-o-phone', 'w-4 h-4')
                    Kontaktdaten
                </button>
                <button wire:click="$set('activeTab', 'kontakte')" class="py-3 px-1 border-b-2 text-sm font-medium transition flex items-center gap-1.5 {{ $activeTab === 'kontakte' ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-400 hover:text-gray-900 hover:border-gray-200' }}">
                    @svg('heroicon-o-users', 'w-4 h-4')
                    Kontakte
                </button>
                <button wire:click="$set('activeTab', 'engagements')" class="py-3 px-1 border-b-2 text-sm font-medium transition flex items-center gap-1.5 {{ $activeTab === 'engagements' ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-400 hover:text-gray-900 hover:border-gray-200' }}">
                    @svg('heroicon-o-clipboard-document-list', 'w-4 h-4')
                    Engagements
                    @if($this->engagements->count() > 0)
                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $this->engagements->count() }}</span>
                    @endif
                </button>
                <button wire:click="$set('activeTab', 'potenzial')" class="py-3 px-1 border-b-2 text-sm font-medium transition flex items-center gap-1.5 {{ $activeTab === 'potenzial' ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-400 hover:text-gray-900 hover:border-gray-200' }}">
                    @svg('heroicon-o-chart-bar', 'w-4 h-4')
                    Potenzial
                </button>
            </nav>
        </div>

        {{-- Tab: Stammdaten --}}
        @if($activeTab === 'stammdaten')
            <div class="space-y-6">
                {{-- Unternehmensdaten --}}
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Unternehmensdaten</h3></div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Name</label>
                                <input type="text" wire:model.live.debounce.500ms="company.name" placeholder="Unternehmensname eingeben..." required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('company.name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Rechtlicher Name</label>
                                <input type="text" wire:model.live.debounce.500ms="company.legal_name" placeholder="z.B. Muster GmbH" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('company.legal_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Handelsname</label>
                                <input type="text" wire:model.live.debounce.500ms="company.trading_name" placeholder="z.B. Muster Solutions" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('company.trading_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Website</label>
                                <input type="text" wire:model.live.debounce.500ms="company.website" placeholder="https://example.com" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('company.website') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Rechtliche Informationen --}}
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Rechtliche Informationen</h3></div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Rechtsform</label>
                                <select wire:model.live="company.legal_form_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                                    <option value="">– Rechtsform auswählen –</option>
                                    @foreach($legalForms as $opt)
                                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                                    @endforeach
                                </select>
                                @error('company.legal_form_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Handelsregisternummer</label>
                                <input type="text" wire:model.live.debounce.500ms="company.registration_number" placeholder="HRB 12345" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('company.registration_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Steuernummer</label>
                                <input type="text" wire:model.live.debounce.500ms="company.tax_number" placeholder="123/456/78901" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('company.tax_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">USt-IdNr.</label>
                                <input type="text" wire:model.live.debounce.500ms="company.vat_number" placeholder="DE123456789" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('company.vat_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Beschreibung & Notizen --}}
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Beschreibung & Notizen</h3></div>
                    <div class="p-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Beschreibung</label>
                                <textarea wire:model.live.debounce.500ms="company.description" placeholder="Unternehmensbeschreibung..." rows="4" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors resize-y"></textarea>
                                @error('company.description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                                <textarea wire:model.live.debounce.500ms="company.notes" placeholder="Interne Notizen..." rows="4" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors resize-y"></textarea>
                                @error('company.notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
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
                                <button type="button" wire:click="addPhone" class="inline-flex items-center gap-1.5 px-2 py-1 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                    Hinzufügen
                                </button>
                            </div>
                            @if($company->phoneNumbers->count() > 0)
                                <div class="space-y-2">
                                    @foreach($company->phoneNumbers as $phone)
                                        <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-200 hover:border-[#ff7a59]/20 cursor-pointer transition" wire:click="editPhone({{ $phone->id }})">
                                            <div class="flex items-center gap-2 min-w-0">
                                                @svg('heroicon-o-phone', 'w-4 h-4 text-gray-400 flex-shrink-0')
                                                <span class="text-sm truncate">{{ $phone->national ?: $phone->raw_input }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                @if($phone->is_primary)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">Primär</span>
                                                @endif
                                                <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $phone->phoneType->name }}</span>
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
                                <button type="button" wire:click="addEmail" class="inline-flex items-center gap-1.5 px-2 py-1 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                                    @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                    Hinzufügen
                                </button>
                            </div>
                            @if($company->emailAddresses->count() > 0)
                                <div class="space-y-2">
                                    @foreach($company->emailAddresses as $email)
                                        <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-gray-200 hover:border-[#ff7a59]/20 cursor-pointer transition" wire:click="editEmail({{ $email->id }})">
                                            <div class="flex items-center gap-2 min-w-0">
                                                @svg('heroicon-o-envelope', 'w-4 h-4 text-gray-400 flex-shrink-0')
                                                <span class="text-sm truncate">{{ $email->email_address }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 flex-shrink-0">
                                                @if($email->is_primary)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">Primär</span>
                                                @endif
                                                <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $email->emailType->name }}</span>
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
                            <button type="button" wire:click="addAddress" class="inline-flex items-center gap-1.5 px-2 py-1 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                Hinzufügen
                            </button>
                        </div>
                        @if($company->postalAddresses->count() > 0)
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                                @foreach($company->postalAddresses as $address)
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
                                                <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">Primär</span>
                                            @endif
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $address->addressType->name }}</span>
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

        {{-- Tab: Kontakte --}}
        @if($activeTab === 'kontakte')
            <section class="bg-white rounded-lg border border-gray-200">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900">Kontakte</h3>
                        <button type="button" wire:click="addContact" class="inline-flex items-center gap-1.5 px-2 py-1 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                            Hinzufügen
                        </button>
                    </div>
                    @if($company->contactRelations->count() > 0)
                        <div class="space-y-2">
                            @foreach($company->contactRelations as $relation)
                                <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200 hover:border-[#ff7a59]/20 cursor-pointer transition" wire:click="editContact({{ $relation->id }})">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @svg('heroicon-o-user', 'w-4 h-4 text-gray-400 flex-shrink-0')
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium">
                                                <a href="{{ route('crm.contacts.show', ['contact' => $relation->contact->id]) }}"
                                                   class="hover:underline text-[#ff7a59]"
                                                   wire:navigate
                                                   @click.stop>
                                                    {{ $relation->contact->full_name }}
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
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">Primär</span>
                                        @endif
                                        @if($relation->is_current)
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-orange-100 text-orange-800">Aktiv</span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">Vergangen</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 py-3">Keine Kontakte verknüpft.</p>
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
                        <button type="button" wire:click="openEngagementCreateModal" class="inline-flex items-center gap-1.5 px-2 py-1 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
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
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full {{ $statusBadgeClass }}">{{ $statusLabel }}</span>
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

        {{-- Tab: Potenzial --}}
        @if($activeTab === 'potenzial')
            <div class="space-y-6">
                {{-- Aktuelles Jahr --}}
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Potenzial {{ now()->year }}</h3></div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Zielumsatz</label>
                                <input type="number" wire:model.live.debounce.500ms="potentialForm.target_revenue" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('potentialForm.target_revenue') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Zusatzpotenzial</label>
                                <input type="number" wire:model.live.debounce.500ms="potentialForm.additional_potential" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('potentialForm.additional_potential') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Strategisches Potenzial</label>
                                <input type="number" wire:model.live.debounce.500ms="potentialForm.strategic_potential" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                                @error('potentialForm.strategic_potential') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Konfidenz</label>
                                <select wire:model.live="potentialForm.confidence" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                                    <option value="">– Konfidenz auswählen –</option>
                                    @foreach(\Platform\Crm\Models\CrmAccountPotential::confidenceOptions() as $opt)
                                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('potentialForm.confidence') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                                <textarea wire:model.live.debounce.500ms="potentialForm.notes" placeholder="Anmerkungen zum Potenzial..." rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors resize-y"></textarea>
                                @error('potentialForm.notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                            <div class="text-sm">
                                <span class="text-gray-400">Gesamtpotenzial:</span>
                                <span class="font-semibold text-gray-900 ml-1">
                                    {{ number_format(
                                        (float) ($potentialForm['target_revenue'] ?? 0)
                                        + (float) ($potentialForm['additional_potential'] ?? 0),
                                        2, ',', '.'
                                    ) }} &euro;
                                </span>
                            </div>
                            <button type="button" wire:click="savePotential" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">
                                @svg('heroicon-o-check', 'w-4 h-4')
                                Speichern
                            </button>
                        </div>
                    </div>
                </section>

                {{-- Historie --}}
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-gray-900">Historie</h3>
                            <button type="button" wire:click="openPotentialHistoryModal" class="inline-flex items-center gap-1.5 px-2 py-1 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                Jahr hinzufügen
                            </button>
                        </div>
                        @if($this->potentialHistory->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200">
                                            <th class="text-left py-2 pr-3 font-medium text-gray-400">Jahr</th>
                                            <th class="text-right py-2 px-3 font-medium text-gray-400">Zielumsatz</th>
                                            <th class="text-right py-2 px-3 font-medium text-gray-400">Zusatz</th>
                                            <th class="text-right py-2 px-3 font-medium text-gray-400">Strategisch</th>
                                            <th class="text-right py-2 px-3 font-medium text-gray-400">Gesamt</th>
                                            <th class="text-left py-2 px-3 font-medium text-gray-400">Konfidenz</th>
                                            <th class="text-right py-2 pl-3 font-medium text-gray-400"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($this->potentialHistory as $entry)
                                            <tr class="border-b border-gray-200 last:border-0">
                                                <td class="py-2 pr-3 font-medium">{{ $entry->year }}</td>
                                                <td class="py-2 px-3 text-right">{{ $entry->target_revenue ? number_format((float) $entry->target_revenue, 2, ',', '.') . ' €' : '–' }}</td>
                                                <td class="py-2 px-3 text-right">{{ $entry->additional_potential ? number_format((float) $entry->additional_potential, 2, ',', '.') . ' €' : '–' }}</td>
                                                <td class="py-2 px-3 text-right">{{ $entry->strategic_potential ? number_format((float) $entry->strategic_potential, 2, ',', '.') . ' €' : '–' }}</td>
                                                <td class="py-2 px-3 text-right font-medium">{{ number_format($entry->total_potential, 2, ',', '.') }} &euro;</td>
                                                <td class="py-2 px-3">
                                                    @if($entry->confidence_label)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-700">{{ $entry->confidence_label }}</span>
                                                    @else
                                                        <span class="text-gray-400">–</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 pl-3 text-right">
                                                    <button wire:click="deletePotentialEntry({{ $entry->id }})" wire:confirm="Eintrag für {{ $entry->year }} wirklich löschen?" class="text-gray-400 hover:text-red-500 transition">
                                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 py-3">Keine historischen Einträge vorhanden.</p>
                        @endif
                    </div>
                </section>
            </div>
        @endif

    </x-ui-page-container>

    <!-- Phone Create Modal -->
    <x-ui-modal size="sm" model="phoneCreateModalShow">
        <x-slot name="header">Telefonnummer hinzufügen</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefonnummer</label>
                    <input type="text" wire:model.live="phoneForm.raw_input" required placeholder="0151 1234567" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('phoneForm.raw_input') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select wire:model.live="phoneForm.country_code" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        @foreach($countries as $opt)
                            <option value="{{ $opt->code }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefon-Typ</label>
                <select wire:model.live="phoneForm.phone_type_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($phoneTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="phoneForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Primäre Telefonnummer</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closePhoneCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="savePhone" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Phone Edit Modal -->
    <x-ui-modal size="sm" model="phoneEditModalShow">
        <x-slot name="header">Telefonnummer bearbeiten</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefonnummer</label>
                    <input type="text" wire:model.live="phoneForm.raw_input" required placeholder="0151 1234567" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('phoneForm.raw_input') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select wire:model.live="phoneForm.country_code" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        @foreach($countries as $opt)
                            <option value="{{ $opt->code }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Telefon-Typ</label>
                <select wire:model.live="phoneForm.phone_type_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($phoneTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="phoneForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Primäre Telefonnummer</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deletePhoneAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="flex gap-2">
                    <button type="button" wire:click="closePhoneEditModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                    <button type="button" wire:click="savePhone" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Speichern</button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- E-Mail Create Modal -->
    <x-ui-modal size="sm" model="emailCreateModalShow">
        <x-slot name="header">E-Mail-Adresse hinzufügen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail-Adresse</label>
                <input type="email" wire:model.live="emailForm.email_address" required placeholder="max.mustermann@example.com" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('emailForm.email_address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail-Typ</label>
                <select wire:model.live="emailForm.email_type_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($emailTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="emailForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Primäre E-Mail-Adresse</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeEmailCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="saveEmail" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- E-Mail Edit Modal -->
    <x-ui-modal size="sm" model="emailEditModalShow">
        <x-slot name="header">E-Mail-Adresse bearbeiten</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail-Adresse</label>
                <input type="email" wire:model.live="emailForm.email_address" required placeholder="max.mustermann@example.com" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('emailForm.email_address') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">E-Mail-Typ</label>
                <select wire:model.live="emailForm.email_type_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($emailTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="emailForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Primäre E-Mail-Adresse</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteEmailAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="flex gap-2">
                    <button type="button" wire:click="closeEmailEditModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                    <button type="button" wire:click="saveEmail" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Speichern</button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Address Create Modal -->
    <x-ui-modal size="lg" model="addressCreateModalShow">
        <x-slot name="header">Adresse hinzufügen</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Straße</label>
                    <input type="text" wire:model.live="addressForm.street" required placeholder="Musterstraße" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.street') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Hausnummer</label>
                    <input type="text" wire:model.live="addressForm.house_number" placeholder="123" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.house_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">PLZ</label>
                    <input type="text" wire:model.live="addressForm.postal_code" required placeholder="12345" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.postal_code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Stadt</label>
                    <input type="text" wire:model.live="addressForm.city" required placeholder="Musterstadt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.city') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Zusätzliche Informationen</label>
                <input type="text" wire:model.live="addressForm.additional_info" placeholder="Apartment, Etage, etc." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('addressForm.additional_info') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select wire:model.live="addressForm.country_id" required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        @foreach($countries as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Bundesland</label>
                    <select wire:model.live="addressForm.state_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">–</option>
                        @foreach($states as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Adresstyp</label>
                <select wire:model.live="addressForm.address_type_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($addressTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="addressForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Primäre Adresse</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeAddressCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="saveAddress" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Address Edit Modal -->
    <x-ui-modal size="lg" model="addressEditModalShow">
        <x-slot name="header">Adresse bearbeiten</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Straße</label>
                    <input type="text" wire:model.live="addressForm.street" required placeholder="Musterstraße" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.street') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Hausnummer</label>
                    <input type="text" wire:model.live="addressForm.house_number" placeholder="123" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.house_number') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">PLZ</label>
                    <input type="text" wire:model.live="addressForm.postal_code" required placeholder="12345" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.postal_code') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Stadt</label>
                    <input type="text" wire:model.live="addressForm.city" required placeholder="Musterstadt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('addressForm.city') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Zusätzliche Informationen</label>
                <input type="text" wire:model.live="addressForm.additional_info" placeholder="Apartment, Etage, etc." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('addressForm.additional_info') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select wire:model.live="addressForm.country_id" required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        @foreach($countries as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Bundesland</label>
                    <select wire:model.live="addressForm.state_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">–</option>
                        @foreach($states as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Adresstyp</label>
                <select wire:model.live="addressForm.address_type_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($addressTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="addressForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Primäre Adresse</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteAddressAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="flex gap-2">
                    <button type="button" wire:click="closeAddressEditModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                    <button type="button" wire:click="saveAddress" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Speichern</button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Contact Create Modal -->
    <x-ui-modal size="lg" model="contactCreateModalShow">
        <x-slot name="header">Kontakt hinzufügen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Kontakt</label>
                <select wire:model.live="contactRelationForm.contact_id" required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    <option value="">– Kontakt auswählen –</option>
                    @foreach($this->filteredContacts as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Beziehungstyp</label>
                <select wire:model.live="contactRelationForm.relation_type_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($relationTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Position</label>
                <input type="text" wire:model.live="contactRelationForm.position" placeholder="z.B. Geschäftsführer, Abteilungsleiter" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('contactRelationForm.position') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Startdatum</label>
                    <input type="date" wire:model.live="contactRelationForm.start_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('contactRelationForm.start_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Enddatum (optional)</label>
                    <input type="date" wire:model.live="contactRelationForm.end_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('contactRelationForm.end_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                <textarea wire:model.live="contactRelationForm.notes" placeholder="Zusätzliche Informationen zur Beziehung" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors resize-y"></textarea>
                @error('contactRelationForm.notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="contactRelationForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Primärer Kontakt</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closeContactCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="saveContact" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Engagement Create Modal --}}
    <x-ui-modal size="lg" model="engagementCreateModalShow">
        <x-slot name="header">Engagement anlegen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Typ</label>
                <select wire:model.live="engagementForm.type" required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach(collect([['value' => 'note', 'label' => 'Notiz'], ['value' => 'call', 'label' => 'Anruf'], ['value' => 'meeting', 'label' => 'Meeting'], ['value' => 'task', 'label' => 'Aufgabe']]) as $opt)
                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Titel</label>
                <input type="text" wire:model.live="engagementForm.title" required placeholder="Titel eingeben..." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('engagementForm.title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Beschreibung</label>
                <textarea wire:model.live="engagementForm.body" placeholder="Beschreibung (optional)" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors resize-y"></textarea>
            </div>
            @if(in_array($engagementForm['type'], ['call', 'meeting', 'task']))
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                    <select wire:model.live="engagementForm.status" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
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
                    <input type="date" wire:model.live="engagementForm.scheduled_at" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                </div>
            @endif
            @if($engagementForm['type'] === 'task')
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Priorität</label>
                    <select wire:model.live="engagementForm.priority" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
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
                <button type="button" wire:click="closeEngagementCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="createEngagementForCompany" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Engagement anlegen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Potential History Modal -->
    <x-ui-modal size="lg" model="potentialCreateModalShow">
        <x-slot name="header">Historischen Potenzial-Eintrag hinzufügen</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Jahr</label>
                <input type="number" wire:model.live="potentialHistoryForm.year" min="2000" max="{{ now()->year }}" required placeholder="z.B. {{ now()->year - 1 }}" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('potentialHistoryForm.year') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Zielumsatz</label>
                    <input type="number" wire:model.live="potentialHistoryForm.target_revenue" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('potentialHistoryForm.target_revenue') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Zusatzpotenzial</label>
                    <input type="number" wire:model.live="potentialHistoryForm.additional_potential" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('potentialHistoryForm.additional_potential') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Strategisches Potenzial</label>
                    <input type="number" wire:model.live="potentialHistoryForm.strategic_potential" step="0.01" min="0" placeholder="0.00" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('potentialHistoryForm.strategic_potential') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Konfidenz</label>
                    <select wire:model.live="potentialHistoryForm.confidence" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Konfidenz auswählen –</option>
                        @foreach(\Platform\Crm\Models\CrmAccountPotential::confidenceOptions() as $opt)
                            <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    @error('potentialHistoryForm.confidence') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                <textarea wire:model.live="potentialHistoryForm.notes" placeholder="Anmerkungen..." rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors resize-y"></textarea>
                @error('potentialHistoryForm.notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" wire:click="closePotentialHistoryModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="savePotentialHistory" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Hinzufügen</button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Contact Edit Modal -->
    <x-ui-modal size="lg" model="contactEditModalShow">
        <x-slot name="header">Kontakt-Beziehung bearbeiten</x-slot>
        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Kontakt</label>
                <select wire:model.live="contactRelationForm.contact_id" required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    <option value="">– Kontakt auswählen –</option>
                    @foreach($this->filteredContacts as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Beziehungstyp</label>
                <select wire:model.live="contactRelationForm.relation_type_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                    @foreach($relationTypes as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Position</label>
                <input type="text" wire:model.live="contactRelationForm.position" placeholder="z.B. Geschäftsführer, Abteilungsleiter" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                @error('contactRelationForm.position') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Startdatum</label>
                    <input type="date" wire:model.live="contactRelationForm.start_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('contactRelationForm.start_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Enddatum (optional)</label>
                    <input type="date" wire:model.live="contactRelationForm.end_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    @error('contactRelationForm.end_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                <textarea wire:model.live="contactRelationForm.notes" placeholder="Zusätzliche Informationen zur Beziehung" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors resize-y"></textarea>
                @error('contactRelationForm.notes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" wire:model.live="contactRelationForm.is_primary" class="w-4 h-4 rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59] focus:ring-offset-0" />
                <span class="text-[13px] text-gray-700">Primärer Kontakt</span>
            </label>
        </div>
        <x-slot name="footer">
            <div class="flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteContactAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="flex gap-2">
                    @if($editingContactRelationId)
                        <a href="{{ route('crm.contacts.show', ['contact' => $contactRelationForm['contact_id']]) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">
                            @svg('heroicon-o-user', 'w-4 h-4')
                            Zum Kontakt
                        </a>
                    @endif
                    <button type="button" wire:click="closeContactEditModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors">Abbrechen</button>
                    <button type="button" wire:click="saveContact" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-[#ff7a59] text-white hover:bg-[#e8604a] transition-colors">Speichern</button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

</x-ui-page>
