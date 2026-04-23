<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Unternehmen'],
        ]">
            <div class="flex items-center gap-2">
                <div x-data @keydown.window.meta.k.prevent="$refs.search.focus()" @keydown.window.ctrl.k.prevent="$refs.search.focus()">
                    <input
                        type="text"
                        x-ref="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Suchen... (&#8984;K)"
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
                        class="appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                    >
                        <option value="">Status ändern...</option>
                        @foreach($contactStatuses as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                    <button wire:click="bulkDelete" wire:confirm="Wirklich deaktivieren?" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-red-600 text-white text-[13px] font-medium hover:bg-red-700 transition-colors">
                        @svg('heroicon-o-trash', 'w-4 h-4')
                    </button>
                @else
                    <button wire:click="openCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Neues Unternehmen</span>
                    </button>
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                    <select name="statusFilter" wire:model.live="statusFilter" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Alle –</option>
                        @foreach($contactStatuses as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Branche</label>
                    <select name="industryFilter" wire:model.live="industryFilter" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Alle –</option>
                        @foreach($industries as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Rechtsform</label>
                    <select name="legalFormFilter" wire:model.live="legalFormFilter" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Alle –</option>
                        @foreach($legalForms as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                    <select name="countryFilter" wire:model.live="countryFilter" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Alle –</option>
                        @foreach($countries as $opt)
                            <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Erstellt von – bis</label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" name="createdFrom" wire:model.live="createdFrom" placeholder="Von" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        <input type="date" name="createdTo" wire:model.live="createdTo" placeholder="Bis" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Sortierung</label>
                    <div class="flex gap-2">
                        <select name="sortField" wire:model.live="sortField" class="flex-1 appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            @foreach(collect([
                                ['value' => 'display_name', 'label' => 'Name'],
                                ['value' => 'contact_status_id', 'label' => 'Status'],
                                ['value' => 'created_at', 'label' => 'Erstellt'],
                                ['value' => 'updated_at', 'label' => 'Aktualisiert'],
                            ]) as $opt)
                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                            @endforeach
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
                @if(!empty($industryFilter))
                    @php $indName = $industries->firstWhere('id', $industryFilter)?->name ?? $industryFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Branche: {{ $indName }}
                        <button wire:click="$set('industryFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($legalFormFilter))
                    @php $lfName = $legalForms->firstWhere('id', $legalFormFilter)?->name ?? $legalFormFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Rechtsform: {{ $lfName }}
                        <button wire:click="$set('legalFormFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($countryFilter))
                    @php $cName = $countries->firstWhere('id', $countryFilter)?->name ?? $countryFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Land: {{ $cName }}
                        <button wire:click="$set('countryFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
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

        @if($this->companies->count() === 0)
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-400">
                Keine Unternehmen gefunden.
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
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer" wire:click="$set('sortField', 'display_name')">
                                    <span class="inline-flex items-center gap-1">
                                        Name
                                        @if($sortField === 'display_name')
                                            @if($sortDirection === 'asc')
                                                @svg('heroicon-o-chevron-up', 'w-3 h-3')
                                            @else
                                                @svg('heroicon-o-chevron-down', 'w-3 h-3')
                                            @endif
                                        @endif
                                    </span>
                                </th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Primäre Kontaktdaten</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Kontakte</th>
                                <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer" wire:click="$set('sortField', 'contact_status_id')">
                                    <span class="inline-flex items-center gap-1">
                                        Status
                                        @if($sortField === 'contact_status_id')
                                            @if($sortDirection === 'asc')
                                                @svg('heroicon-o-chevron-up', 'w-3 h-3')
                                            @else
                                                @svg('heroicon-o-chevron-down', 'w-3 h-3')
                                            @endif
                                        @endif
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($this->companies as $company)
                                <tr class="hover:bg-orange-50/50 transition-colors cursor-pointer"
                                    onclick="window.location='{{ route('crm.companies.show', ['company' => $company->id]) }}'"
                                    data-href="{{ route('crm.companies.show', ['company' => $company->id]) }}">
                                    <td class="px-4 py-3 text-[13px]" @click.stop>
                                        <input type="checkbox" value="{{ $company->id }}" wire:model.live="selected"
                                               class="rounded border-gray-200 text-[#ff7a59]" />
                                    </td>
                                    <td class="px-4 py-3 text-[13px]">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-md bg-gray-100 flex items-center justify-center text-gray-400 flex-shrink-0">
                                                @svg('heroicon-o-building-office', 'w-4 h-4')
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-medium truncate">{{ $company->display_name }}</div>
                                                @if($company->legalForm)
                                                    <div class="text-xs text-gray-400">{{ $company->legalForm->name }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[13px]">
                                        <div class="space-y-1">
                                            @if($company->phoneNumbers->where('is_primary', true)->first())
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-phone', 'w-3 h-3 text-gray-400')
                                                    {{ $company->phoneNumbers->where('is_primary', true)->first()->national }}
                                                </div>
                                            @endif
                                            @if($company->emailAddresses->where('is_primary', true)->first())
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-envelope', 'w-3 h-3 text-gray-400')
                                                    {{ $company->emailAddresses->where('is_primary', true)->first()->email_address }}
                                                </div>
                                            @endif
                                            @if($company->postalAddresses->where('is_primary', true)->first())
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-map-pin', 'w-3 h-3 text-gray-400')
                                                    {{ $company->postalAddresses->where('is_primary', true)->first()->city }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-[13px]">
                                        @if($company->contactRelations->count() > 0)
                                            <div class="space-y-1">
                                                @foreach($company->contactRelations->take(2) as $relation)
                                                    <div class="text-xs flex items-center gap-1">
                                                        @svg('heroicon-o-user', 'w-3 h-3 text-gray-400')
                                                        {{ $relation->contact->full_name }}
                                                        @if($relation->position)
                                                            <span class="text-gray-400">({{ $relation->position }})</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                @if($company->contactRelations->count() > 2)
                                                    <div class="text-xs text-gray-400">+{{ $company->contactRelations->count() - 2 }} weitere</div>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">–</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-[13px]">
                                        @if($company->contactStatus)
                                            @php
                                                $variant = \Platform\Crm\Models\CrmContactStatus::getVariantForCode($company->contactStatus->code ?? '');
                                                $badgeClasses = match($variant) {
                                                    'success' => 'bg-green-100 text-green-800',
                                                    'danger' => 'bg-red-100 text-red-800',
                                                    'warning' => 'bg-amber-100 text-amber-800',
                                                    'primary' => 'bg-orange-100 text-orange-800',
                                                    default => 'bg-gray-100 text-gray-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                                {{ $company->contactStatus->name }}
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

            @if($this->companies->count() >= $perPage * $page)
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

    {{-- Create Company Modal --}}
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Neues Unternehmen anlegen</x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createCompany" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Firmenname</label>
                        <input type="text" name="name" wire:model.live="name" required placeholder="Firmenname eingeben" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Rechtlicher Name</label>
                        <input type="text" name="legal_name" wire:model.live="legal_name" placeholder="Rechtlicher Name (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Handelsname</label>
                        <input type="text" name="trading_name" wire:model.live="trading_name" placeholder="Handelsname (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Website</label>
                        <input type="url" name="website" wire:model.live="website" placeholder="https://example.com (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Registrierungsnummer</label>
                        <input type="text" name="registration_number" wire:model.live="registration_number" placeholder="HRB 12345 (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Steuernummer</label>
                        <input type="text" name="tax_number" wire:model.live="tax_number" placeholder="Steuernummer (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">USt-IdNr.</label>
                        <input type="text" name="vat_number" wire:model.live="vat_number" placeholder="DE123456789 (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                        <select name="contact_status_id" wire:model.live="contact_status_id" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Status auswählen –</option>
                            @foreach($contactStatuses as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Land</label>
                        <select name="country_id" wire:model.live="country_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Land auswählen –</option>
                            @foreach($countries as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Branche</label>
                        <select name="industry_id" wire:model.live="industry_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Branche auswählen –</option>
                            @foreach($industries as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Rechtsform</label>
                        <select name="legal_form_id" wire:model.live="legal_form_id" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Rechtsform auswählen –</option>
                            @foreach($legalForms as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Quick-create: Primary Email + Phone --}}
                <hr class="border-gray-200">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Primäre E-Mail</label>
                        <input type="email" name="primary_email" wire:model.live="primary_email" placeholder="info@firma.de (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Primäre Telefonnummer</label>
                        <input type="text" name="primary_phone" wire:model.live="primary_phone" placeholder="+49 123 456789 (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Beschreibung</label>
                    <textarea name="description" wire:model.live="description" placeholder="Kurze Beschreibung des Unternehmens (optional)" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Notizen</label>
                    <textarea name="notes" wire:model.live="notes" placeholder="Interne Notizen (optional)" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" @click="$wire.closeCreateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="createCompany" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Unternehmen anlegen</button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
