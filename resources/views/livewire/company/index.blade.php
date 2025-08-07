<div class = "p-3">
    <h1 class="text-2xl font-bold mb-4">Unternehmen</h1>
    
    <div class="d-flex justify-between mb-4">
        <x-ui-input-text 
            name="search" 
            placeholder="Suche Unternehmen..." 
            class="w-64"
        />
        <x-ui-button variant="primary" wire:click="openCreateModal">
            Neues Unternehmen
        </x-ui-button>
    </div>
    
    <div class="bg-surface rounded-lg border border-muted overflow-hidden">
        <table class="w-full">
            <thead class="bg-muted-5">
                <tr>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">Name</th>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">Branche</th>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">Rechtsform</th>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">Status</th>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($companies as $company)
                    <tr class="border-bottom-1 border-bottom-muted hover:bg-muted-5 transition-colors">
                        <td class="p-4 text-body">{{ $company->display_name }}</td>
                        <td class="p-4 text-body">{{ $company->industry?->name }}</td>
                        <td class="p-4 text-body">{{ $company->legalForm?->name }}</td>
                        <td class="p-4">
                            <x-ui-badge variant="primary" size="sm">
                                {{ $company->contactStatus->name }}
                            </x-ui-badge>
                        </td>
                        <td class="p-4">
                            <x-ui-button size="sm" variant="secondary">Bearbeiten</x-ui-button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $companies->links() }}
    </div>

    <!-- Create Company Modal -->
    <x-ui-modal
        wire:model="modalShow"
        size="lg"
        header="Neues Unternehmen anlegen"
        :footer="null"
    >
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

            <div class="d-flex justify-end gap-2 pt-4">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    @click="$wire.closeCreateModal()"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="submit" variant="primary">
                    Unternehmen anlegen
                </x-ui-button>
            </div>
        </form>
    </x-ui-modal>
</div>