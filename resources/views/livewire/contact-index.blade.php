<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Kontakte'],
        ]">
            <div class="flex items-center gap-2">
                <div x-data @keydown.window.meta.k.prevent="$refs.search.focus()" @keydown.window.ctrl.k.prevent="$refs.search.focus()">
                    <input
                        type="text"
                        x-ref="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Suchen... (⌘K)"
                        name="search"
                        class="w-64 px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                        @keydown.escape="$refs.search.blur(); $wire.set('search', '')"
                    />
                </div>

                @if(count($selected) > 0)
                    <span class="text-sm text-gray-400">{{ count($selected) }} ausgewählt</span>
                    <select
                        name="bulkStatus"
                        wire:change="bulkChangeStatus($event.target.value)"
                        class="px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                    >
                        <option value="">Status ändern...</option>
                        @foreach($contactStatuses as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                    <button variant="danger" wire:click="bulkDelete" wire:confirm="Wirklich deaktivieren?" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-red-600 text-white text-[13px] font-medium hover:bg-red-700 transition-colors">
                        @svg('heroicon-o-trash', 'w-4 h-4')
                    </button>
                @else
                    <button wire:click="openCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Neuer Kontakt</span>
                    </button>
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">STATUS</label>
                    <select
                        name="statusFilter"
                        wire:model.live="statusFilter"
                        class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                    >
                        <option value="">– Alle –</option>
                        @foreach($contactStatuses as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">BLACKLIST</label>
                    <select
                        name="blacklistFilter"
                        wire:model.live="blacklistFilter"
                        class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                    >
                        <option value="all">Alle</option>
                        <option value="not_blacklisted">Nicht blacklisted</option>
                        <option value="blacklisted">Nur blacklisted</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">UNTERNEHMEN</label>
                    <select
                        name="companyFilter"
                        wire:model.live="companyFilter"
                        class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                    >
                        <option value="">– Alle –</option>
                        @foreach($companiesForFilter as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">GESCHLECHT</label>
                    <select
                        name="genderFilter"
                        wire:model.live="genderFilter"
                        class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                    >
                        <option value="">– Alle –</option>
                        @foreach($genders as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">SPRACHE</label>
                    <select
                        name="languageFilter"
                        wire:model.live="languageFilter"
                        class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                    >
                        <option value="">– Alle –</option>
                        @foreach($languages as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">ERSTELLT VON – BIS</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="createdFrom" wire:model.live="createdFrom" placeholder="Von" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        <input type="date" name="createdTo" wire:model.live="createdTo" placeholder="Bis" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">SORTIERUNG</label>
                    <div class="flex gap-2">
                        <select
                            name="sortField"
                            wire:model.live="sortField"
                            class="flex-1 px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                        >
                            <option value="last_name">Name</option>
                            <option value="contact_status_id">Status</option>
                            <option value="company">Unternehmen</option>
                            <option value="created_at">Erstellt</option>
                            <option value="updated_at">Aktualisiert</option>
                        </select>
                        <button wire:click="$set('sortDirection', '{{ $sortDirection === 'asc' ? 'desc' : 'asc' }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                            @if($sortDirection === 'asc')
                                @svg('heroicon-o-arrow-up', 'w-4 h-4')
                            @else
                                @svg('heroicon-o-arrow-down', 'w-4 h-4')
                            @endif
                        </button>
                    </div>
                </div>

                @if($this->hasActiveFilters)
                    <button wire:click="resetFilters" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        Filter zurücksetzen
                    </button>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
    <x-slot name="activity"></x-slot>

    <x-ui-page-container>

        {{-- Active filter chips --}}
        @if($this->hasActiveFilters)
            <div class="flex flex-wrap items-center gap-2 mb-4">
                @if(trim($search) !== '')
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Suche: "{{ $search }}"
                        <button wire:click="$set('search', '')" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($statusFilter))
                    @php $statusName = $contactStatuses->firstWhere('id', $statusFilter)?->name ?? $statusFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Status: {{ $statusName }}
                        <button wire:click="$set('statusFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if($blacklistFilter !== 'not_blacklisted')
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        {{ $blacklistFilter === 'blacklisted' ? 'Nur Blacklisted' : 'Alle (inkl. Blacklisted)' }}
                        <button wire:click="$set('blacklistFilter', 'not_blacklisted')" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($companyFilter))
                    @php $companyName = $companiesForFilter->firstWhere('id', $companyFilter)?->name ?? $companyFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Firma: {{ $companyName }}
                        <button wire:click="$set('companyFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($genderFilter))
                    @php $genderName = $genders->firstWhere('id', $genderFilter)?->name ?? $genderFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Geschlecht: {{ $genderName }}
                        <button wire:click="$set('genderFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($languageFilter))
                    @php $langName = $languages->firstWhere('id', $languageFilter)?->name ?? $languageFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Sprache: {{ $langName }}
                        <button wire:click="$set('languageFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($createdFrom) || !empty($createdTo))
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Erstellt: {{ $createdFrom ?? '...' }} – {{ $createdTo ?? '...' }}
                        <button wire:click="$set('createdFrom', null); $wire.set('createdTo', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
            </div>
        @endif

        @if($this->contacts->count() === 0)
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-400">
                Keine Kontakte gefunden.
            </div>
        @else
            <div x-data="{ activeRow: -1 }"
                 @keydown.arrow-down.prevent="activeRow = Math.min(activeRow+1, $el.querySelectorAll('tr[data-href]').length-1)"
                 @keydown.arrow-up.prevent="activeRow = Math.max(activeRow-1, 0)"
                 @keydown.enter.prevent="if(activeRow>=0) window.location=$el.querySelectorAll('tr[data-href]')[activeRow]?.dataset.href">
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide w-10">
                                    <input type="checkbox" wire:model.live="selectAll" wire:change="toggleSelectAll"
                                           class="rounded border-gray-200 text-[#ff7a59]" />
                                </th>
                                <th wire:click="$set('sortField', 'last_name')" class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-600">
                                    Name
                                    @if($sortField === 'last_name')
                                        @if($sortDirection === 'asc') @svg('heroicon-s-chevron-up', 'w-3 h-3 inline') @else @svg('heroicon-s-chevron-down', 'w-3 h-3 inline') @endif
                                    @endif
                                </th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Primäre Kontaktdaten</th>
                                <th wire:click="$set('sortField', 'company')" class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-600">
                                    Unternehmen
                                    @if($sortField === 'company')
                                        @if($sortDirection === 'asc') @svg('heroicon-s-chevron-up', 'w-3 h-3 inline') @else @svg('heroicon-s-chevron-down', 'w-3 h-3 inline') @endif
                                    @endif
                                </th>
                                <th wire:click="$set('sortField', 'contact_status_id')" class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-600">
                                    Status
                                    @if($sortField === 'contact_status_id')
                                        @if($sortDirection === 'asc') @svg('heroicon-s-chevron-up', 'w-3 h-3 inline') @else @svg('heroicon-s-chevron-down', 'w-3 h-3 inline') @endif
                                    @endif
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($this->contacts as $contact)
                                <tr
                                    wire:navigate
                                    href="{{ route('crm.contacts.show', ['contact' => $contact->id]) }}"
                                    data-href="{{ route('crm.contacts.show', ['contact' => $contact->id]) }}"
                                    class="hover:bg-orange-50/50 transition-colors cursor-pointer"
                                >
                                    <td class="px-4 py-3 text-[13px]" @click.stop>
                                        <input type="checkbox" value="{{ $contact->id }}" wire:model.live="selected"
                                               class="rounded border-gray-200 text-[#ff7a59]" />
                                    </td>
                                    <td class="px-4 py-3 text-[13px]">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-[10px] font-bold text-[#ff7a59] flex-shrink-0">
                                                {{ strtoupper(mb_substr($contact->first_name, 0, 1) . mb_substr($contact->last_name, 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-medium">{{ $contact->last_name }}, {{ $contact->first_name }}</span>
                                                    @if($contact->is_blacklisted)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            @svg('heroicon-s-no-symbol', 'w-3 h-3')
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($contact->nickname)
                                                    <div class="text-xs text-gray-400">"{{ $contact->nickname }}"</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[13px]">
                                        <div class="space-y-1">
                                            @if($contact->phoneNumbers->where('is_primary', true)->first())
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-phone', 'w-3 h-3 text-gray-400')
                                                    {{ $contact->phoneNumbers->where('is_primary', true)->first()->national }}
                                                </div>
                                            @endif
                                            @if($contact->emailAddresses->where('is_primary', true)->first())
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-envelope', 'w-3 h-3 text-gray-400')
                                                    {{ $contact->emailAddresses->where('is_primary', true)->first()->email_address }}
                                                </div>
                                            @endif
                                            @if($contact->postalAddresses->where('is_primary', true)->first())
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-map-pin', 'w-3 h-3 text-gray-400')
                                                    {{ $contact->postalAddresses->where('is_primary', true)->first()->city }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[13px]">
                                        @if($contact->contactRelations->count() > 0)
                                            <div class="space-y-1">
                                                @foreach($contact->contactRelations->take(2) as $relation)
                                                    <div class="text-xs flex items-center gap-1">
                                                        @svg('heroicon-o-building-office', 'w-3 h-3 text-gray-400')
                                                        {{ $relation->company?->name ?? '–' }}
                                                        @if($relation->position)
                                                            <span class="text-gray-400">({{ $relation->position }})</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                @if($contact->contactRelations->count() > 2)
                                                    <div class="text-xs text-gray-400">+{{ $contact->contactRelations->count() - 2 }} weitere</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[13px]">
                                        @if($contact->contactStatus)
                                            @php
                                                $variant = \Platform\Crm\Models\CrmContactStatus::getVariantForCode($contact->contactStatus->code ?? '');
                                                $badgeClasses = match($variant) {
                                                    'success' => 'bg-green-100 text-green-800',
                                                    'danger' => 'bg-red-100 text-red-800',
                                                    'warning' => 'bg-amber-100 text-amber-800',
                                                    'primary' => 'bg-orange-100 text-orange-800',
                                                    default => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                                {{ $contact->contactStatus->name }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">–</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($this->contacts->count() >= $perPage * $page)
                <div x-data="{
                    init() {
                        const observer = new IntersectionObserver((entries) => {
                            entries.forEach(entry => { if (entry.isIntersecting) $wire.loadMore() })
                        }, { rootMargin: '200px' });
                        observer.observe($el);
                    }
                }" class="h-8"></div>
            @endif
        @endif
    </x-ui-page-container>

    {{-- Create Contact Modal --}}
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Kontakt anlegen</x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createContact" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">VORNAME</label>
                        <input type="text" name="first_name" wire:model.live="first_name" required placeholder="Vorname eingeben" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">NACHNAME</label>
                        <input type="text" name="last_name" wire:model.live="last_name" required placeholder="Nachname eingeben" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">ZWEITER VORNAME</label>
                        <input type="text" name="middle_name" wire:model.live="middle_name" placeholder="Zweiter Vorname (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">SPITZNAME</label>
                        <input type="text" name="nickname" wire:model.live="nickname" placeholder="Spitzname (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">GEBURTSDATUM</label>
                        <input type="date" name="birth_date" wire:model.live="birth_date" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">STATUS</label>
                        <select name="contact_status_id" wire:model.live="contact_status_id" required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Status auswählen –</option>
                            @foreach($contactStatuses as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">ANREDE</label>
                        <select name="salutation_id" wire:model.live="salutation_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Anrede auswählen –</option>
                            @foreach($salutations as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">AKADEMISCHER TITEL</label>
                        <select name="academic_title_id" wire:model.live="academic_title_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Titel auswählen –</option>
                            @foreach($academicTitles as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">GESCHLECHT</label>
                        <select name="gender_id" wire:model.live="gender_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Geschlecht auswählen –</option>
                            @foreach($genders as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">SPRACHE</label>
                        <select name="language_id" wire:model.live="language_id" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Sprache auswählen –</option>
                            @foreach($languages as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Quick-create: Primary Email + Phone --}}
                <hr class="border-gray-200">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">PRIMÄRE E-MAIL</label>
                        <input type="email" name="primary_email" wire:model.live="primary_email" placeholder="email@example.com (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">PRIMÄRE TELEFONNUMMER</label>
                        <input type="text" name="primary_phone" wire:model.live="primary_phone" placeholder="+49 123 456789 (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">NOTIZEN</label>
                    <textarea name="notes" wire:model.live="notes" rows="3" placeholder="Zusätzliche Notizen (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors resize-none"></textarea>
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" @click="$wire.closeCreateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="createContact" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Kontakt anlegen</button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
