<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Newsletter'],
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
                    <span>Neuer Newsletter</span>
                </button>
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
                    <select wire:model.live="statusFilter" name="statusFilter" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Alle –</option>
                        <option value="draft">Entwurf</option>
                        <option value="scheduled">Geplant</option>
                        <option value="sending">Wird gesendet</option>
                        <option value="sent">Gesendet</option>
                        <option value="cancelled">Abgebrochen</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Sortierung</label>
                    <div class="flex gap-2">
                        <select wire:model.live="sortField" name="sortField" class="flex-1 appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="created_at">Erstellt</option>
                            <option value="updated_at">Aktualisiert</option>
                            <option value="name">Name</option>
                            <option value="scheduled_at">Geplant</option>
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
                @if(!empty($statusFilter))
                    @php
                        $statusLabels = ['draft' => 'Entwurf', 'scheduled' => 'Geplant', 'sending' => 'Wird gesendet', 'sent' => 'Gesendet', 'cancelled' => 'Abgebrochen'];
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Status: {{ $statusLabels[$statusFilter] ?? $statusFilter }}
                        <button wire:click="$set('statusFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
            </div>
        @endif

        @if($this->newsletters->count() === 0)
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-400">
                Keine Newsletter gefunden.
            </div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th wire:click="sortBy('name')" class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-600">
                                <span class="inline-flex items-center gap-1">
                                    Name
                                    @if($sortField === 'name')
                                        @if($sortDirection === 'asc') @svg('heroicon-o-chevron-up', 'w-3 h-3') @else @svg('heroicon-o-chevron-down', 'w-3 h-3') @endif
                                    @endif
                                </span>
                            </th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Status</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Empfänger</th>
                            <th wire:click="sortBy('created_at')" class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-600">
                                <span class="inline-flex items-center gap-1">
                                    Datum
                                    @if($sortField === 'created_at')
                                        @if($sortDirection === 'asc') @svg('heroicon-o-chevron-up', 'w-3 h-3') @else @svg('heroicon-o-chevron-down', 'w-3 h-3') @endif
                                    @endif
                                </span>
                            </th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Erstellt von</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @foreach($this->newsletters as $nl)
                            <tr wire:key="newsletter-{{ $nl->id }}" onclick="window.location='{{ route('crm.newsletters.show', ['newsletter' => $nl->id]) }}'" class="hover:bg-orange-50/50 transition-colors cursor-pointer">
                                <td class="px-4 py-3 text-[13px]">
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ $nl->name }}</div>
                                        @if($nl->subject)
                                            <div class="text-xs text-gray-400 truncate max-w-xs">{{ $nl->subject }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    @php
                                        $statusClasses = match($nl->status) {
                                            'draft' => 'bg-gray-100 text-gray-700',
                                            'scheduled' => 'bg-blue-100 text-blue-800',
                                            'sending' => 'bg-amber-100 text-amber-800',
                                            'sent' => 'bg-green-100 text-green-800',
                                            'cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                        $statusLabel = match($nl->status) {
                                            'draft' => 'Entwurf',
                                            'scheduled' => 'Geplant',
                                            'sending' => 'Wird gesendet',
                                            'sent' => 'Gesendet',
                                            'cancelled' => 'Abgebrochen',
                                            default => $nl->status,
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    @php $stats = $nl->stats ?? []; @endphp
                                    <span class="text-xs text-gray-500">{{ $stats['total'] ?? 0 }}</span>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    <div class="text-xs text-gray-400">
                                        @if($nl->sent_at)
                                            {{ $nl->sent_at->format('d.m.Y H:i') }}
                                        @elseif($nl->scheduled_at)
                                            {{ $nl->scheduled_at->format('d.m.Y H:i') }}
                                        @else
                                            {{ $nl->created_at->format('d.m.Y H:i') }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    <span class="text-xs">{{ $nl->createdByUser?->name ?? '–' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($this->newsletters->count() >= $perPage * $page)
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

    {{-- Create Newsletter Modal --}}
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Newsletter anlegen</x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createNewsletter" class="space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Name</label>
                    <input type="text" name="newsletterName" wire:model.live="newsletterName" required placeholder="z.B. Mai-Newsletter 2026" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Betreff</label>
                    <input type="text" name="newsletterSubject" wire:model.live="newsletterSubject" required placeholder="E-Mail Betreffzeile" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" @click="$wire.closeCreateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="createNewsletter" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Newsletter anlegen</button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
