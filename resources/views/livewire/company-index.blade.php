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
                        <span>Neues Unternehmen</span>
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
                    name="industryFilter"
                    label="Branche"
                    :options="$industries"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="industryFilter"
                />
                <x-ui-input-select
                    name="legalFormFilter"
                    label="Rechtsform"
                    :options="$legalForms"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="legalFormFilter"
                />
                <x-ui-input-select
                    name="countryFilter"
                    label="Land"
                    :options="$countries"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Alle –"
                    size="sm"
                    wire:model.live="countryFilter"
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
                                ['value' => 'display_name', 'label' => 'Name'],
                                ['value' => 'contact_status_id', 'label' => 'Status'],
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
                @if(!empty($industryFilter))
                    @php $indName = $industries->firstWhere('id', $industryFilter)?->name ?? $industryFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Branche: {{ $indName }}
                        <button wire:click="$set('industryFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($legalFormFilter))
                    @php $lfName = $legalForms->firstWhere('id', $legalFormFilter)?->name ?? $legalFormFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Rechtsform: {{ $lfName }}
                        <button wire:click="$set('legalFormFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
                    </span>
                @endif
                @if(!empty($countryFilter))
                    @php $cName = $countries->firstWhere('id', $countryFilter)?->name ?? $countryFilter; @endphp
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[color:var(--ui-primary-10)] text-[color:var(--ui-primary)]">
                        Land: {{ $cName }}
                        <button wire:click="$set('countryFilter', null)" class="hover:opacity-70">@svg('heroicon-o-x-mark', 'w-3 h-3')</button>
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

        @if($this->companies->count() === 0)
            <div class="rounded-lg border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-6 text-sm text-[color:var(--ui-muted)]">
                Keine Unternehmen gefunden.
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
                        <x-ui-table-header-cell compact="true" sortable="true" sortField="display_name" :currentSort="$sortField" :sortDirection="$sortDirection">Name</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Primäre Kontaktdaten</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Kontakte</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true" sortable="true" sortField="contact_status_id" :currentSort="$sortField" :sortDirection="$sortDirection">Status</x-ui-table-header-cell>
                    </x-ui-table-header>

                    <x-ui-table-body>
                        @foreach($this->companies as $company)
                            <x-ui-table-row
                                compact="true"
                                clickable="true"
                                :href="route('crm.companies.show', ['company' => $company->id])"
                                :data-href="route('crm.companies.show', ['company' => $company->id])"
                                :class="'transition'"
                            >
                                <x-ui-table-cell compact="true" @click.stop>
                                    <input type="checkbox" value="{{ $company->id }}" wire:model.live="selected"
                                           class="rounded border-[var(--ui-border)] text-[var(--ui-primary)]" />
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    <div class="font-medium">{{ $company->display_name }}</div>
                                    @if($company->legalForm)
                                        <div class="text-xs text-[color:var(--ui-muted)]">{{ $company->legalForm->name }}</div>
                                    @endif
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    <div class="space-y-1">
                                        @if($company->phoneNumbers->where('is_primary', true)->first())
                                            <div class="text-xs flex items-center gap-1">
                                                @svg('heroicon-o-phone', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                {{ $company->phoneNumbers->where('is_primary', true)->first()->national }}
                                            </div>
                                        @endif
                                        @if($company->emailAddresses->where('is_primary', true)->first())
                                            <div class="text-xs flex items-center gap-1">
                                                @svg('heroicon-o-envelope', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                {{ $company->emailAddresses->where('is_primary', true)->first()->email_address }}
                                            </div>
                                        @endif
                                        @if($company->postalAddresses->where('is_primary', true)->first())
                                            <div class="text-xs flex items-center gap-1">
                                                @svg('heroicon-o-map-pin', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                {{ $company->postalAddresses->where('is_primary', true)->first()->city }}
                                            </div>
                                        @endif
                                    </div>
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    @if($company->contactRelations->count() > 0)
                                        <div class="space-y-1">
                                            @foreach($company->contactRelations->take(2) as $relation)
                                                <div class="text-xs flex items-center gap-1">
                                                    @svg('heroicon-o-user', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                                    {{ $relation->contact->full_name }}
                                                    @if($relation->position)
                                                        <span class="text-[color:var(--ui-muted)]">({{ $relation->position }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                            @if($company->contactRelations->count() > 2)
                                                <div class="text-xs text-[color:var(--ui-muted)]">+{{ $company->contactRelations->count() - 2 }} weitere</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-[color:var(--ui-muted)]">–</span>
                                    @endif
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    @if($company->contactStatus)
                                        <x-ui-badge variant="secondary" size="sm">
                                            {{ $company->contactStatus->name }}
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
                    <x-ui-input-text name="name" label="Firmenname" wire:model.live="name" required placeholder="Firmenname eingeben" />
                    <x-ui-input-text name="legal_name" label="Rechtlicher Name" wire:model.live="legal_name" placeholder="Rechtlicher Name (optional)" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text name="trading_name" label="Handelsname" wire:model.live="trading_name" placeholder="Handelsname (optional)" />
                    <x-ui-input-text name="website" label="Website" wire:model.live="website" type="url" placeholder="https://example.com (optional)" />
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <x-ui-input-text name="registration_number" label="Registrierungsnummer" wire:model.live="registration_number" placeholder="HRB 12345 (optional)" />
                    <x-ui-input-text name="tax_number" label="Steuernummer" wire:model.live="tax_number" placeholder="Steuernummer (optional)" />
                    <x-ui-input-text name="vat_number" label="USt-IdNr." wire:model.live="vat_number" placeholder="DE123456789 (optional)" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select name="contact_status_id" label="Status" :options="$contactStatuses" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Status auswählen –" wire:model.live="contact_status_id" required />
                    <x-ui-input-select name="country_id" label="Land" :options="$countries" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Land auswählen –" wire:model.live="country_id" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select name="industry_id" label="Branche" :options="$industries" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Branche auswählen –" wire:model.live="industry_id" />
                    <x-ui-input-select name="legal_form_id" label="Rechtsform" :options="$legalForms" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Rechtsform auswählen –" wire:model.live="legal_form_id" />
                </div>

                {{-- Quick-create: Primary Email + Phone --}}
                <hr class="border-[color:var(--ui-border)]">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text name="primary_email" label="Primäre E-Mail" wire:model.live="primary_email" type="email" placeholder="info@firma.de (optional)" />
                    <x-ui-input-text name="primary_phone" label="Primäre Telefonnummer" wire:model.live="primary_phone" placeholder="+49 123 456789 (optional)" />
                </div>

                <x-ui-input-textarea name="description" label="Beschreibung" wire:model.live="description" placeholder="Kurze Beschreibung des Unternehmens (optional)" rows="3" />
                <x-ui-input-textarea name="notes" label="Notizen" wire:model.live="notes" placeholder="Interne Notizen (optional)" rows="3" />
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createCompany">Unternehmen anlegen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
