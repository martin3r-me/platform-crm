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
                    <x-ui-input-text
                        x-ref="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Suchen... (⌘K)"
                        size="sm"
                        name="search"
                        class="w-64"
                        @keydown.escape="$refs.search.blur(); $wire.set('search', '')"
                    />
                </div>

                <x-ui-button variant="primary" size="sm" wire:click="openCreateModal">
                    @svg('heroicon-o-plus', 'w-4 h-4')
                    <span>Neues Engagement</span>
                </x-ui-button>
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4 space-y-4">
                <x-ui-input-select
                    name="typeFilter"
                    label="Typ"
                    :options="collect([
                        ['value' => 'note', 'label' => 'Notiz'],
                        ['value' => 'call', 'label' => 'Anruf'],
                        ['value' => 'meeting', 'label' => 'Meeting'],
                        ['value' => 'task', 'label' => 'Aufgabe'],
                    ])"
                    optionValue="value"
                    optionLabel="label"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="typeFilter"
                />
                <x-ui-input-select
                    name="statusFilter"
                    label="Status"
                    :options="collect([
                        ['value' => 'open', 'label' => 'Offen'],
                        ['value' => 'in_progress', 'label' => 'In Bearbeitung'],
                        ['value' => 'completed', 'label' => 'Abgeschlossen'],
                        ['value' => 'cancelled', 'label' => 'Abgebrochen'],
                    ])"
                    optionValue="value"
                    optionLabel="label"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="statusFilter"
                />

                <div>
                    <label class="block text-xs font-medium text-[color:var(--ui-muted)] mb-1">Sortierung</label>
                    <div class="flex gap-2">
                        <x-ui-input-select
                            name="sortField"
                            :options="collect([
                                ['value' => 'created_at', 'label' => 'Erstellt'],
                                ['value' => 'scheduled_at', 'label' => 'Geplant'],
                                ['value' => 'updated_at', 'label' => 'Aktualisiert'],
                                ['value' => 'title', 'label' => 'Titel'],
                            ])"
                            optionValue="value"
                            optionLabel="label"
                            :nullable="false"
                            size="sm"
                            wire:model.live="sortField"
                            class="flex-1"
                        />
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="$set('sortDirection', '{{ $sortDirection === 'asc' ? 'desc' : 'asc' }}')">
                            @if($sortDirection === 'asc')
                                @svg('heroicon-o-arrow-up', 'w-4 h-4')
                            @else
                                @svg('heroicon-o-arrow-down', 'w-4 h-4')
                            @endif
                        </x-ui-button>
                    </div>
                </div>

                @if($this->hasActiveFilters)
                    <x-ui-button variant="secondary-outline" size="sm" wire:click="resetFilters" class="w-full">
                        @svg('heroicon-o-x-mark', 'w-4 h-4')
                        Filter zurücksetzen
                    </x-ui-button>
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
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Suche: "{{ $search }}"
                        <button wire:click="$set('search', '')" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($typeFilter))
                    @php
                        $typeLabels = ['note' => 'Notiz', 'call' => 'Anruf', 'meeting' => 'Meeting', 'task' => 'Aufgabe'];
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Typ: {{ $typeLabels[$typeFilter] ?? $typeFilter }}
                        <button wire:click="$set('typeFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($statusFilter))
                    @php
                        $statusLabels = ['open' => 'Offen', 'in_progress' => 'In Bearbeitung', 'completed' => 'Abgeschlossen', 'cancelled' => 'Abgebrochen'];
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Status: {{ $statusLabels[$statusFilter] ?? $statusFilter }}
                        <button wire:click="$set('statusFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
            </div>
        @endif

        @if($this->engagements->count() === 0)
            <div class="rounded-lg border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-6 text-sm text-[color:var(--ui-muted)]">
                Keine Engagements gefunden.
            </div>
        @else
            <x-ui-table compact="true">
                <x-ui-table-header>
                    <x-ui-table-header-cell compact="true" width="w-10">Typ</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" sortable="true" sortField="title" :currentSort="$sortField" :sortDirection="$sortDirection">Titel</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Verknüpfungen</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true" sortable="true" sortField="scheduled_at" :currentSort="$sortField" :sortDirection="$sortDirection">Datum</x-ui-table-header-cell>
                    <x-ui-table-header-cell compact="true">Besitzer</x-ui-table-header-cell>
                </x-ui-table-header>

                <x-ui-table-body>
                    @foreach($this->engagements as $engagement)
                        <x-ui-table-row
                            compact="true"
                            clickable="true"
                            :href="route('crm.engagements.show', ['engagement' => $engagement->id])"
                            :data-href="route('crm.engagements.show', ['engagement' => $engagement->id])"
                        >
                            <x-ui-table-cell compact="true">
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
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="min-w-0">
                                    <div class="font-medium truncate">{{ $engagement->title }}</div>
                                    @if($engagement->body)
                                        <div class="text-xs text-[color:var(--ui-muted)] truncate max-w-xs">{{ \Illuminate\Support\Str::limit($engagement->body, 60) }}</div>
                                    @endif
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="space-y-1">
                                    @foreach($engagement->companyLinks->take(2) as $link)
                                        @if($link->company)
                                            <div class="text-xs flex items-center gap-1">
                                                @svg('heroicon-o-building-office', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                {{ $link->company->name }}
                                            </div>
                                        @endif
                                    @endforeach
                                    @foreach($engagement->contactLinks->take(2) as $link)
                                        @if($link->contact)
                                            <div class="text-xs flex items-center gap-1">
                                                @svg('heroicon-o-user', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                {{ $link->contact->full_name }}
                                            </div>
                                        @endif
                                    @endforeach
                                    @php $totalLinks = $engagement->companyLinks->count() + $engagement->contactLinks->count(); @endphp
                                    @if($totalLinks > 4)
                                        <div class="text-xs text-[color:var(--ui-muted)]">+{{ $totalLinks - 4 }} weitere</div>
                                    @endif
                                    @if($totalLinks === 0)
                                        <span class="text-xs text-[color:var(--ui-muted)]">–</span>
                                    @endif
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
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
                                    @endphp
                                    <x-ui-badge :variant="$statusVariant" size="sm">{{ $statusLabel }}</x-ui-badge>
                                @else
                                    <span class="text-xs text-[color:var(--ui-muted)]">–</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="text-xs text-[color:var(--ui-muted)]">
                                    @if($engagement->scheduled_at)
                                        {{ $engagement->scheduled_at->format('d.m.Y H:i') }}
                                    @else
                                        {{ $engagement->created_at->format('d.m.Y H:i') }}
                                    @endif
                                </div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs">{{ $engagement->ownedByUser?->name ?? '–' }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                    @endforeach
                </x-ui-table-body>
            </x-ui-table>

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
                <x-ui-input-select
                    name="engagementType"
                    label="Typ"
                    :options="collect([
                        ['value' => 'note', 'label' => 'Notiz'],
                        ['value' => 'call', 'label' => 'Anruf'],
                        ['value' => 'meeting', 'label' => 'Meeting'],
                        ['value' => 'task', 'label' => 'Aufgabe'],
                    ])"
                    optionValue="value"
                    optionLabel="label"
                    :nullable="false"
                    wire:model.live="engagementType"
                    required
                />

                <x-ui-input-text name="engagementTitle" label="Titel" wire:model.live="engagementTitle" required placeholder="Titel eingeben..." />

                <x-ui-input-textarea name="engagementBody" label="Beschreibung" wire:model.live="engagementBody" placeholder="Beschreibung (optional)" rows="3" />

                @if(in_array($engagementType, ['call', 'meeting', 'task']))
                    <x-ui-input-select
                        name="engagementStatus"
                        label="Status"
                        :options="collect([
                            ['value' => 'open', 'label' => 'Offen'],
                            ['value' => 'in_progress', 'label' => 'In Bearbeitung'],
                            ['value' => 'completed', 'label' => 'Abgeschlossen'],
                            ['value' => 'cancelled', 'label' => 'Abgebrochen'],
                        ])"
                        optionValue="value"
                        optionLabel="label"
                        :nullable="true"
                        nullLabel="– Status auswählen –"
                        wire:model.live="engagementStatus"
                    />
                @endif

                @if(in_array($engagementType, ['meeting', 'task']))
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-date name="engagementScheduledAt" label="{{ $engagementType === 'task' ? 'Fällig am' : 'Geplant am' }}" wire:model.live="engagementScheduledAt" :nullable="true" />
                        @if($engagementType === 'meeting')
                            <x-ui-input-date name="engagementEndedAt" label="Ende" wire:model.live="engagementEndedAt" :nullable="true" />
                        @endif
                    </div>
                @endif

                @if($engagementType === 'task')
                    <x-ui-input-select
                        name="engagementPriority"
                        label="Priorität"
                        :options="collect([
                            ['value' => 'low', 'label' => 'Niedrig'],
                            ['value' => 'medium', 'label' => 'Mittel'],
                            ['value' => 'high', 'label' => 'Hoch'],
                        ])"
                        optionValue="value"
                        optionLabel="label"
                        :nullable="true"
                        nullLabel="– Priorität auswählen –"
                        wire:model.live="engagementPriority"
                    />
                @endif

                <hr class="border-[color:var(--ui-border)]">

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select
                        name="selectedContactIds"
                        label="Kontakte verknüpfen"
                        :options="$contactsForSelect"
                        optionValue="id"
                        optionLabel="full_name"
                        :nullable="true"
                        nullLabel="– Kontakt auswählen –"
                        wire:model.live="selectedContactIds"
                        multiple
                    />
                    <x-ui-input-select
                        name="selectedCompanyIds"
                        label="Unternehmen verknüpfen"
                        :options="$companiesForSelect"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Unternehmen auswählen –"
                        wire:model.live="selectedCompanyIds"
                        multiple
                    />
                </div>
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createEngagement">Engagement anlegen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
