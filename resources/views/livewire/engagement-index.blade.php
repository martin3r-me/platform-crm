<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Engagements'],
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

                <button wire:click="openCreateModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neues Engagement</span>
                </button>
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Typ</label>
                    <select wire:model.live="typeFilter" name="typeFilter" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Alle –</option>
                        <option value="note">Notiz</option>
                        <option value="call">Anruf</option>
                        <option value="meeting">Meeting</option>
                        <option value="task">Aufgabe</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                    <select wire:model.live="statusFilter" name="statusFilter" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Alle –</option>
                        <option value="open">Offen</option>
                        <option value="in_progress">In Bearbeitung</option>
                        <option value="completed">Abgeschlossen</option>
                        <option value="cancelled">Abgebrochen</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Sortierung</label>
                    <div class="flex gap-2">
                        <select wire:model.live="sortField" name="sortField" class="flex-1 appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="created_at">Erstellt</option>
                            <option value="scheduled_at">Geplant</option>
                            <option value="updated_at">Aktualisiert</option>
                            <option value="title">Titel</option>
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
                    <button wire:click="resetFilters" class="w-full inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">
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
                @if(!empty($typeFilter))
                    @php
                        $typeLabels = ['note' => 'Notiz', 'call' => 'Anruf', 'meeting' => 'Meeting', 'task' => 'Aufgabe'];
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Typ: {{ $typeLabels[$typeFilter] ?? $typeFilter }}
                        <button wire:click="$set('typeFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($statusFilter))
                    @php
                        $statusLabels = ['open' => 'Offen', 'in_progress' => 'In Bearbeitung', 'completed' => 'Abgeschlossen', 'cancelled' => 'Abgebrochen'];
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Status: {{ $statusLabels[$statusFilter] ?? $statusFilter }}
                        <button wire:click="$set('statusFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
            </div>
        @endif

        @if($this->engagements->count() === 0)
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-400">
                Keine Engagements gefunden.
            </div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide w-10">Typ</th>
                            <th wire:click="$set('sortField', 'title')" class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-600">
                                <span class="inline-flex items-center gap-1">
                                    Titel
                                    @if($sortField === 'title')
                                        @if($sortDirection === 'asc')
                                            @svg('heroicon-o-chevron-up', 'w-3 h-3')
                                        @else
                                            @svg('heroicon-o-chevron-down', 'w-3 h-3')
                                        @endif
                                    @endif
                                </span>
                            </th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Verknüpfungen</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Status</th>
                            <th wire:click="$set('sortField', 'scheduled_at')" class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-600">
                                <span class="inline-flex items-center gap-1">
                                    Datum
                                    @if($sortField === 'scheduled_at')
                                        @if($sortDirection === 'asc')
                                            @svg('heroicon-o-chevron-up', 'w-3 h-3')
                                        @else
                                            @svg('heroicon-o-chevron-down', 'w-3 h-3')
                                        @endif
                                    @endif
                                </span>
                            </th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Besitzer</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @foreach($this->engagements as $engagement)
                            <tr wire:key="engagement-{{ $engagement->id }}" onclick="window.location='{{ route('crm.engagements.show', ['engagement' => $engagement->id]) }}'" class="hover:bg-orange-50/50 transition-colors cursor-pointer">
                                <td class="px-4 py-3 text-[13px]">
                                    @switch($engagement->type)
                                        @case('note')
                                            <div class="w-7 h-7 rounded-lg bg-blue-100 flex items-center justify-center">
                                                @svg('heroicon-o-pencil-square', 'w-4 h-4 text-blue-600')
                                            </div>
                                            @break
                                        @case('call')
                                            <div class="w-7 h-7 rounded-lg bg-green-100 flex items-center justify-center">
                                                @svg('heroicon-o-phone', 'w-4 h-4 text-green-600')
                                            </div>
                                            @break
                                        @case('meeting')
                                            <div class="w-7 h-7 rounded-lg bg-purple-100 flex items-center justify-center">
                                                @svg('heroicon-o-calendar', 'w-4 h-4 text-purple-600')
                                            </div>
                                            @break
                                        @case('task')
                                            <div class="w-7 h-7 rounded-lg bg-amber-100 flex items-center justify-center">
                                                @svg('heroicon-o-clipboard-document-check', 'w-4 h-4 text-amber-600')
                                            </div>
                                            @break
                                    @endswitch
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ $engagement->title }}</div>
                                        @if($engagement->body)
                                            <div class="text-xs text-gray-400 truncate max-w-xs">{{ \Illuminate\Support\Str::limit($engagement->body, 60) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    <div class="space-y-1">
                                        @foreach($engagement->companyLinks->take(2) as $link)
                                            @if($link->company)
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-building-office', 'w-3 h-3 text-gray-400')
                                                    {{ $link->company->name }}
                                                </div>
                                            @endif
                                        @endforeach
                                        @foreach($engagement->contactLinks->take(2) as $link)
                                            @if($link->contact)
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-user', 'w-3 h-3 text-gray-400')
                                                    {{ $link->contact->full_name }}
                                                </div>
                                            @endif
                                        @endforeach
                                        @php $totalLinks = $engagement->companyLinks->count() + $engagement->contactLinks->count(); @endphp
                                        @if($totalLinks > 4)
                                            <div class="text-xs text-gray-400">+{{ $totalLinks - 4 }} weitere</div>
                                        @endif
                                        @if($totalLinks === 0)
                                            <span class="text-xs text-gray-400">–</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    @if($engagement->status)
                                        @php
                                            $statusClasses = match($engagement->status) {
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'in_progress' => 'bg-amber-100 text-amber-800',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                            $statusLabel = match($engagement->status) {
                                                'open' => 'Offen',
                                                'in_progress' => 'In Bearbeitung',
                                                'completed' => 'Abgeschlossen',
                                                'cancelled' => 'Abgebrochen',
                                                default => $engagement->status,
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">{{ $statusLabel }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    <div class="text-xs text-gray-400">
                                        @if($engagement->scheduled_at)
                                            {{ $engagement->scheduled_at->format('d.m.Y H:i') }}
                                        @else
                                            {{ $engagement->created_at->format('d.m.Y H:i') }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    <span class="text-xs">{{ $engagement->ownedByUser?->name ?? '–' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($this->engagements->count() >= $perPage * $page)
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

    {{-- Create Engagement Modal --}}
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Engagement anlegen</x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createEngagement" class="space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Typ</label>
                    <select wire:model.live="engagementType" name="engagementType" required class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="note">Notiz</option>
                        <option value="call">Anruf</option>
                        <option value="meeting">Meeting</option>
                        <option value="task">Aufgabe</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Titel</label>
                    <input type="text" name="engagementTitle" wire:model.live="engagementTitle" required placeholder="Titel eingeben..." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Beschreibung</label>
                    <textarea name="engagementBody" wire:model.live="engagementBody" placeholder="Beschreibung (optional)" rows="3" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                </div>

                @if(in_array($engagementType, ['call', 'meeting', 'task']))
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                        <select wire:model.live="engagementStatus" name="engagementStatus" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Status auswählen –</option>
                            <option value="open">Offen</option>
                            <option value="in_progress">In Bearbeitung</option>
                            <option value="completed">Abgeschlossen</option>
                            <option value="cancelled">Abgebrochen</option>
                        </select>
                    </div>
                @endif

                @if(in_array($engagementType, ['meeting', 'task']))
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">{{ $engagementType === 'task' ? 'Fällig am' : 'Geplant am' }}</label>
                            <input type="date" name="engagementScheduledAt" wire:model.live="engagementScheduledAt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        </div>
                        @if($engagementType === 'meeting')
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Ende</label>
                                <input type="date" name="engagementEndedAt" wire:model.live="engagementEndedAt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                            </div>
                        @endif
                    </div>
                @endif

                @if($engagementType === 'task')
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Priorität</label>
                        <select wire:model.live="engagementPriority" name="engagementPriority" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="">– Priorität auswählen –</option>
                            <option value="low">Niedrig</option>
                            <option value="medium">Mittel</option>
                            <option value="high">Hoch</option>
                        </select>
                    </div>
                @endif

                <hr class="border-gray-200">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Kontakte verknüpfen</label>
                        <select multiple wire:model.live="selectedContactIds" name="selectedContactIds" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            @foreach($contactsForSelect as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Unternehmen verknüpfen</label>
                        <select multiple wire:model.live="selectedCompanyIds" name="selectedCompanyIds" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            @foreach($companiesForSelect as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" @click="$wire.closeCreateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="createEngagement" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Engagement anlegen</button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
