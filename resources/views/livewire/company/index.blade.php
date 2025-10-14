<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Unternehmen" icon="heroicon-o-building-office">
            <x-slot name="titleActions"></x-slot>
            <div class="flex items-center gap-2">
                <x-ui-input-text 
                    name="search" 
                    placeholder="Suche Unternehmen..." 
                    class="w-64"
                />
                <x-ui-button variant="primary" wire:click="openCreateModal">
                    Neues Unternehmen
                </x-ui-button>
            </div>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="CRM" width="w-72" defaultOpen="true" storeKey="sidebarOpen" side="left">
            @include('crm::livewire.sidebar')
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <x-ui-table compact="true">
        <x-ui-table-header>
            <x-ui-table-header-cell compact="true" sortable="true" sortField="display_name" :currentSort="$sortField" :sortDirection="$sortDirection">Name</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Primäre Kontaktdaten</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Kontakte</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true" sortable="true" sortField="contact_status_id" :currentSort="$sortField" :sortDirection="$sortDirection">Status</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true" align="right">Aktionen</x-ui-table-header-cell>
        </x-ui-table-header>
        
        <x-ui-table-body>
            @foreach($companies as $company)
                <x-ui-table-row 
                    compact="true"
                    clickable="true" 
                    :href="route('crm.companies.show', ['company' => $company->id])"
                >
                    <x-ui-table-cell compact="true">
                        <div class="font-medium">{{ $company->display_name }}</div>
                        @if($company->legal_form)
                            <div class="text-xs text-muted">{{ $company->legal_form->name }}</div>
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
                        <x-ui-badge variant="primary" size="sm">
                            {{ $company->contactStatus->name }}
                        </x-ui-badge>
                    </x-ui-table-cell>
                    <x-ui-table-cell compact="true" align="right">
                        <x-ui-button 
                            size="sm" 
                            variant="secondary" 
                            href="{{ route('crm.companies.show', ['company' => $company->id]) }}" 
                            wire:navigate
                        >
                            Bearbeiten
                        </x-ui-button>
                    </x-ui-table-cell>
                </x-ui-table-row>
            @endforeach
        </x-ui-table-body>
        </x-ui-table>
        <div class="mt-4">
            {{ $companies->links() }}
        </div>
    </x-ui-page-container>

    <!-- Create Company Modal -->
    <x-ui-modal
        wire:model="modalShow"
        size="lg"
    >
        <x-slot name="header">
            Neues Unternehmen anlegen
        </x-slot>

        <div class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="d-flex items-center gap-2 mb-2">
                    @svg('heroicon-o-information-circle', 'w-5 h-5 text-blue-600')
                    <h4 class="font-medium text-blue-900">Hinweis</h4>
                </div>
                <p class="text-blue-700 text-sm">Kontaktdaten, Adressen und weitere Details können nach dem Anlegen des Unternehmens in der Unternehmens-Detailansicht gepflegt werden.</p>
            </div>

            <form wire:submit.prevent="createCompany" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="name"
                        label="Firmenname"
                        wire:model.live="name"
                        required
                        placeholder="Firmenname eingeben"
                    />
                    
                    <x-ui-input-text
                        name="legal_name"
                        label="Rechtlicher Name"
                        wire:model.live="legal_name"
                        placeholder="Rechtlicher Name (optional)"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text
                        name="trading_name"
                        label="Handelsname"
                        wire:model.live="trading_name"
                        placeholder="Handelsname (optional)"
                    />
                    
                    <x-ui-input-text
                        name="website"
                        label="Website"
                        wire:model.live="website"
                        type="url"
                        placeholder="https://example.com (optional)"
                    />
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <x-ui-input-text
                        name="registration_number"
                        label="Registrierungsnummer"
                        wire:model.live="registration_number"
                        placeholder="HRB 12345 (optional)"
                    />
                    
                    <x-ui-input-text
                        name="tax_number"
                        label="Steuernummer"
                        wire:model.live="tax_number"
                        placeholder="Steuernummer (optional)"
                    />
                    
                    <x-ui-input-text
                        name="vat_number"
                        label="USt-IdNr."
                        wire:model.live="vat_number"
                        placeholder="DE123456789 (optional)"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select
                        name="contact_status_id"
                        label="Status"
                        :options="$contactStatuses"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Status auswählen –"
                        wire:model.live="contact_status_id"
                        required
                    />
                    
                    <x-ui-input-select
                        name="country_id"
                        label="Land"
                        :options="$countries"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Land auswählen –"
                        wire:model.live="country_id"
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select
                        name="industry_id"
                        label="Branche"
                        :options="$industries"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Branche auswählen –"
                        wire:model.live="industry_id"
                    />
                    
                    <x-ui-input-select
                        name="legal_form_id"
                        label="Rechtsform"
                        :options="$legalForms"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Rechtsform auswählen –"
                        wire:model.live="legal_form_id"
                    />
                </div>

                <x-ui-input-textarea
                    name="description"
                    label="Beschreibung"
                    wire:model.live="description"
                    placeholder="Kurze Beschreibung des Unternehmens (optional)"
                    rows="3"
                />

                <x-ui-input-textarea
                    name="notes"
                    label="Notizen"
                    wire:model.live="notes"
                    placeholder="Interne Notizen (optional)"
                    rows="3"
                />
            </form>
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    @click="$wire.closeCreateModal()"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="createCompany">
                    Unternehmen anlegen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>