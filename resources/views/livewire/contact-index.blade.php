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

                @if(count($selected) > 0)
                    <span class="text-sm text-[var(--ui-muted)]">{{ count($selected) }} ausgewählt</span>
                    <x-ui-input-select
                        name="bulkStatus"
                        :options="$contactStatuses"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="Status ändern..."
                        size="sm"
                        wire:change="bulkChangeStatus($event.target.value)"
                    />
                    <x-ui-button variant="danger" size="sm" wire:click="bulkDelete" wire:confirm="Wirklich deaktivieren?">
                        @svg('heroicon-o-trash', 'w-4 h-4')
                    </x-ui-button>
                @else
                    <x-ui-button variant="primary" size="sm" wire:click="openCreateModal">
                        @svg('heroicon-o-plus', 'w-4 h-4')
                        <span>Neuer Kontakt</span>
                    </x-ui-button>
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4 space-y-4">
                <x-ui-input-select
                    name="statusFilter"
                    label="Status"
                    :options="$contactStatuses"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="statusFilter"
                />
                <x-ui-input-select
                    name="blacklistFilter"
                    label="Blacklist"
                    :options="collect([
                        ['value' => 'all', 'label' => 'Alle'],
                        ['value' => 'not_blacklisted', 'label' => 'Nicht blacklisted'],
                        ['value' => 'blacklisted', 'label' => 'Nur blacklisted'],
                    ])"
                    optionValue="value"
                    optionLabel="label"
                    :nullable="false"
                    size="sm"
                    wire:model.live="blacklistFilter"
                />
                <x-ui-input-select
                    name="companyFilter"
                    label="Unternehmen"
                    :options="$companiesForFilter"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="companyFilter"
                />
                <x-ui-input-select
                    name="genderFilter"
                    label="Geschlecht"
                    :options="$genders"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="genderFilter"
                />
                <x-ui-input-select
                    name="languageFilter"
                    label="Sprache"
                    :options="$languages"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="languageFilter"
                />
                <div>
                    <label class="block text-xs font-medium text-[color:var(--ui-muted)] mb-1">Erstellt von – bis</label>
                    <div class="grid grid-cols-2 gap-2">
                        <x-ui-input-date name="createdFrom" wire:model.live="createdFrom" size="sm" placeholder="Von" :nullable="true" />
                        <x-ui-input-date name="createdTo" wire:model.live="createdTo" size="sm" placeholder="Bis" :nullable="true" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-[color:var(--ui-muted)] mb-1">Sortierung</label>
                    <div class="flex gap-2">
                        <x-ui-input-select
                            name="sortField"
                            :options="collect([
                                ['value' => 'last_name', 'label' => 'Name'],
                                ['value' => 'contact_status_id', 'label' => 'Status'],
                                ['value' => 'company', 'label' => 'Unternehmen'],
                                ['value' => 'created_at', 'label' => 'Erstellt'],
                                ['value' => 'updated_at', 'label' => 'Aktualisiert'],
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
                @if(!empty($statusFilter))
                    @php $statusName = $contactStatuses->firstWhere('id', $statusFilter)?->name ?? $statusFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Status: {{ $statusName }}
                        <button wire:click="$set('statusFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if($blacklistFilter !== 'not_blacklisted')
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        {{ $blacklistFilter === 'blacklisted' ? 'Nur Blacklisted' : 'Alle (inkl. Blacklisted)' }}
                        <button wire:click="$set('blacklistFilter', 'not_blacklisted')" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($companyFilter))
                    @php $companyName = $companiesForFilter->firstWhere('id', $companyFilter)?->name ?? $companyFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Firma: {{ $companyName }}
                        <button wire:click="$set('companyFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($genderFilter))
                    @php $genderName = $genders->firstWhere('id', $genderFilter)?->name ?? $genderFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Geschlecht: {{ $genderName }}
                        <button wire:click="$set('genderFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($languageFilter))
                    @php $langName = $languages->firstWhere('id', $languageFilter)?->name ?? $languageFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Sprache: {{ $langName }}
                        <button wire:click="$set('languageFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($createdFrom) || !empty($createdTo))
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Erstellt: {{ $createdFrom ?? '...' }} – {{ $createdTo ?? '...' }}
                        <button wire:click="$set('createdFrom', null); $wire.set('createdTo', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
            </div>
        @endif

        @if($this->contacts->count() === 0)
            <div class="rounded-lg border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-6 text-sm text-[color:var(--ui-muted)]">
                Keine Kontakte gefunden.
            </div>
        @else
            <div x-data="{ activeRow: -1 }"
                 @keydown.arrow-down.prevent="activeRow = Math.min(activeRow+1, $el.querySelectorAll('tr[data-href]').length-1)"
                 @keydown.arrow-up.prevent="activeRow = Math.max(activeRow-1, 0)"
                 @keydown.enter.prevent="if(activeRow>=0) window.location=$el.querySelectorAll('tr[data-href]')[activeRow]?.dataset.href">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true" width="w-10">
                            <input type="checkbox" wire:model.live="selectAll" wire:change="toggleSelectAll"
                                   class="rounded border-[var(--ui-border)] text-[var(--ui-primary)]" />
                        </x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true" sortable="true" sortField="last_name" :currentSort="$sortField" :sortDirection="$sortDirection">Name</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Primäre Kontaktdaten</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true" sortable="true" sortField="company" :currentSort="$sortField" :sortDirection="$sortDirection">Unternehmen</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true" sortable="true" sortField="contact_status_id" :currentSort="$sortField" :sortDirection="$sortDirection">Status</x-ui-table-header-cell>
                    </x-ui-table-header>

                    <x-ui-table-body>
                        @foreach($this->contacts as $contact)
                            <x-ui-table-row
                                compact="true"
                                clickable="true"
                                :href="route('crm.contacts.show', ['contact' => $contact->id])"
                                :data-href="route('crm.contacts.show', ['contact' => $contact->id])"
                                :class="'transition'"
                            >
                                <x-ui-table-cell compact="true" @click.stop>
                                    <input type="checkbox" value="{{ $contact->id }}" wire:model.live="selected"
                                           class="rounded border-[var(--ui-border)] text-[var(--ui-primary)]" />
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ $contact->last_name }}, {{ $contact->first_name }}</span>
                                        @if($contact->is_blacklisted)
                                            <x-ui-badge variant="danger" size="xs">
                                                @svg('heroicon-s-no-symbol', 'w-3 h-3')
                                                Blacklisted
                                            </x-ui-badge>
                                        @endif
                                    </div>
                                    @if($contact->nickname)
                                        <div class="text-xs text-[color:var(--ui-muted)]">"{{ $contact->nickname }}"</div>
                                    @endif
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    <div class="space-y-1">
                                        @if($contact->phoneNumbers->where('is_primary', true)->first())
                                            <div class="text-xs flex items-center gap-1">
                                                @svg('heroicon-o-phone', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                {{ $contact->phoneNumbers->where('is_primary', true)->first()->national }}
                                            </div>
                                        @endif
                                        @if($contact->emailAddresses->where('is_primary', true)->first())
                                            <div class="text-xs flex items-center gap-1">
                                                @svg('heroicon-o-envelope', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                {{ $contact->emailAddresses->where('is_primary', true)->first()->email_address }}
                                            </div>
                                        @endif
                                        @if($contact->postalAddresses->where('is_primary', true)->first())
                                            <div class="text-xs flex items-center gap-1">
                                                @svg('heroicon-o-map-pin', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                {{ $contact->postalAddresses->where('is_primary', true)->first()->city }}
                                            </div>
                                        @endif
                                    </div>
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    @if($contact->contactRelations->count() > 0)
                                        <div class="space-y-1">
                                            @foreach($contact->contactRelations->take(2) as $relation)
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-building-office', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                    {{ $relation->company?->name ?? '–' }}
                                                    @if($relation->position)
                                                        <span class="text-[color:var(--ui-muted)]">({{ $relation->position }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($contact->contactRelations->count() > 2)
                                                <div class="text-xs text-[color:var(--ui-muted)]">+{{ $contact->contactRelations->count() - 2 }} weitere</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-[color:var(--ui-muted)]">–</span>
                                    @endif
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    @if($contact->contactStatus)
                                        <x-ui-badge variant="secondary" size="sm">
                                            {{ $contact->contactStatus->name }}
                                        </x-ui-badge>
                                    @else
                                        <span class="text-xs text-[color:var(--ui-muted)]">–</span>
                                    @endif
                                </x-ui-table-cell>
                            </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
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
                    <x-ui-input-text name="first_name" label="Vorname" wire:model.live="first_name" required placeholder="Vorname eingeben" />
                    <x-ui-input-text name="last_name" label="Nachname" wire:model.live="last_name" required placeholder="Nachname eingeben" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text name="middle_name" label="Zweiter Vorname" wire:model.live="middle_name" placeholder="Zweiter Vorname (optional)" />
                    <x-ui-input-text name="nickname" label="Spitzname" wire:model.live="nickname" placeholder="Spitzname (optional)" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-date name="birth_date" label="Geburtsdatum" wire:model.live="birth_date" placeholder="Geburtsdatum (optional)" :nullable="true" />
                    <x-ui-input-select name="contact_status_id" label="Status" :options="$contactStatuses" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Status auswählen –" wire:model.live="contact_status_id" required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select name="salutation_id" label="Anrede" :options="$salutations" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Anrede auswählen –" wire:model.live="salutation_id" />
                    <x-ui-input-select name="academic_title_id" label="Akademischer Titel" :options="$academicTitles" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Titel auswählen –" wire:model.live="academic_title_id" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select name="gender_id" label="Geschlecht" :options="$genders" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Geschlecht auswählen –" wire:model.live="gender_id" />
                    <x-ui-input-select name="language_id" label="Sprache" :options="$languages" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Sprache auswählen –" wire:model.live="language_id" />
                </div>

                {{-- Quick-create: Primary Email + Phone --}}
                <hr class="border-[color:var(--ui-border)]">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text name="primary_email" label="Primäre E-Mail" wire:model.live="primary_email" type="email" placeholder="email@example.com (optional)" />
                    <x-ui-input-text name="primary_phone" label="Primäre Telefonnummer" wire:model.live="primary_phone" placeholder="+49 123 456789 (optional)" />
                </div>

                <x-ui-input-textarea name="notes" label="Notizen" wire:model.live="notes" placeholder="Zusätzliche Notizen (optional)" rows="3" />
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createContact">Kontakt anlegen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
