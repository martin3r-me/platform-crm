<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Vorlagen'],
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
                    <span>Neue Vorlage</span>
                </button>
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Kategorie</label>
                    <select wire:model.live="categoryFilter" name="categoryFilter" class="w-full appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                        <option value="">– Alle –</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Sortierung</label>
                    <div class="flex gap-2">
                        <select wire:model.live="sortField" name="sortField" class="flex-1 appearance-none px-3 py-2 pr-10 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg+xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22+viewBox%3D%220+0+20+20%22+fill%3D%22%236b7280%22%3E%3Cpath+fill-rule%3D%22evenodd%22+d%3D%22M5.23+7.21a.75.75+0+011.06.02L10+11.168l3.71-3.938a.75.75+0+111.08+1.04l-4.25+4.5a.75.75+0+01-1.08+0l-4.25-4.5a.75.75+0+01.02-1.06z%22+clip-rule%3D%22evenodd%22%2F%3E%3C%2Fsvg%3E')] bg-[length:20px_20px] bg-[position:right_8px_center] bg-no-repeat focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors">
                            <option value="created_at">Erstellt</option>
                            <option value="updated_at">Aktualisiert</option>
                            <option value="name">Name</option>
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
                @if(!empty($categoryFilter))
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-orange-100 text-[#ff7a59]">
                        Kategorie: {{ $categoryFilter }}
                        <button wire:click="$set('categoryFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
            </div>
        @endif

        @if($this->templates->count() === 0)
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-400">
                Keine Vorlagen gefunden.
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
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Kategorie</th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Status</th>
                            <th wire:click="sortBy('created_at')" class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide cursor-pointer hover:text-gray-600">
                                <span class="inline-flex items-center gap-1">
                                    Erstellt
                                    @if($sortField === 'created_at')
                                        @if($sortDirection === 'asc') @svg('heroicon-o-chevron-up', 'w-3 h-3') @else @svg('heroicon-o-chevron-down', 'w-3 h-3') @endif
                                    @endif
                                </span>
                            </th>
                            <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Erstellt von</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        @foreach($this->templates as $tpl)
                            <tr wire:key="template-{{ $tpl->id }}" onclick="window.location='{{ route('crm.newsletter-templates.show', ['newsletterTemplate' => $tpl->id]) }}'" class="hover:bg-orange-50/50 transition-colors cursor-pointer">
                                <td class="px-4 py-3 text-[13px]">
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ $tpl->name }}</div>
                                        @if($tpl->description)
                                            <div class="text-xs text-gray-400 truncate max-w-xs">{{ $tpl->description }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    @if($tpl->category)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">{{ $tpl->category }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    @if($tpl->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktiv</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Inaktiv</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    <div class="text-xs text-gray-400">{{ $tpl->created_at->format('d.m.Y H:i') }}</div>
                                </td>
                                <td class="px-4 py-3 text-[13px]">
                                    <span class="text-xs">{{ $tpl->createdByUser?->name ?? '–' }}</span>
                                </td>
                                <td class="px-4 py-3 text-right" onclick="event.stopPropagation()">
                                    <button wire:click="deleteTemplate({{ $tpl->id }})" wire:confirm="Vorlage wirklich löschen?" class="text-gray-400 hover:text-red-500 transition-colors">
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($this->templates->count() >= $perPage * $page)
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

    {{-- Create Template Modal --}}
    <x-ui-modal wire:model="modalShow" size="lg">
        <x-slot name="header">Vorlage anlegen</x-slot>

        <div class="space-y-4">
            <form wire:submit.prevent="createTemplate" class="space-y-4">
                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Name</label>
                    <input type="text" name="templateName" wire:model.live="templateName" required placeholder="z.B. Standard-Newsletter" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                </div>

                <div>
                    <label class="block text-[11px] font-medium text-gray-500 mb-1">Kategorie (optional)</label>
                    <input type="text" name="templateCategory" wire:model.live="templateCategory" placeholder="z.B. Marketing, Update, Transaktional" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-2">
                <button type="button" @click="$wire.closeCreateModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Abbrechen</button>
                <button type="button" wire:click="createTemplate" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">Vorlage anlegen</button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
