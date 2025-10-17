<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="{{ $company->display_name }}" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                {{-- Navigation Buttons --}}
                <div class="flex flex-col gap-2">
                    <x-ui-button 
                        variant="secondary-outline" 
                        size="md" 
                        :href="route('crm.companies.index')" 
                        wire:navigate
                        class="w-full"
                    >
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            Zurück zu Unternehmen
                        </div>
                    </x-ui-button>
                </div>

                {{-- Aktionen --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        @if($this->isDirty)
                            <x-ui-button variant="primary" size="sm" wire:click="save" class="w-full">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-check','w-4 h-4')
                                    Speichern
                                </span>
                            </x-ui-button>
                        @endif
                    </div>
                </div>

                {{-- Kurze Übersicht --}}
                <div class="p-3 bg-[color:var(--ui-muted-5)] rounded-lg">
                    <h4 class="font-semibold mb-2 text-[color:var(--ui-secondary)]">Unternehmens-Übersicht</h4>
                    <div class="space-y-1 text-sm">
                        <div><strong>Name:</strong> {{ $company->display_name }}</div>
                        @if($company->legal_name)
                            <div><strong>Rechtlich:</strong> {{ $company->legal_name }}</div>
                        @endif
                        @if($company->trading_name)
                            <div><strong>Handelsname:</strong> {{ $company->trading_name }}</div>
                        @endif
                        @if($company->website)
                            <div><strong>Website:</strong> <a href="{{ $company->website }}" target="_blank" class="underline">{{ $company->website }}</a></div>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <x-ui-input-select
                    name="company.contact_status_id"
                    label="Status"
                    :options="$contactStatuses"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Status auswählen –"
                    wire:model.live="company.contact_status_id"
                    required
                />

                <hr>

                {{-- Telefonnummern --}}
                <div class="mb-2">
                    <h4 class="font-semibold mb-2">Telefonnummern</h4>
                    <div class="space-y-2">
                        @foreach($company->phoneNumbers as $phone)
                            <div class="flex items-center gap-2 p-2 bg-[color:var(--ui-muted-5)] rounded cursor-pointer" wire:click="editPhone({{ $phone->id }})">
                                <span class="flex-grow text-sm">{{ $phone->raw_input }}</span>
                                <div class="flex gap-1">
                                    @if($phone->is_primary)
                                        <x-ui-badge variant="success" size="xs">Primär</x-ui-badge>
                                    @endif
                                    <x-ui-badge variant="primary" size="xs">{{ $phone->phoneType->name }}</x-ui-badge>
                                </div>
                            </div>
                        @endforeach
                        @if($company->phoneNumbers->count() === 0)
                            <p class="text-sm text-[color:var(--ui-muted)]">Noch keine Telefonnummern vorhanden.</p>
                        @endif
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addPhone">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Telefonnummer hinzufügen
                            </div>
                        </x-ui-button>
                    </div>
                </div>

                {{-- E-Mail-Adressen --}}
                <div class="mb-2">
                    <h4 class="font-semibold mb-2">E-Mail-Adressen</h4>
                    <div class="space-y-2">
                        @foreach($company->emailAddresses as $email)
                            <div class="flex items-center gap-2 p-2 bg-[color:var(--ui-muted-5)] rounded cursor-pointer" wire:click="editEmail({{ $email->id }})">
                                <span class="flex-grow text-sm">{{ $email->email_address }}</span>
                                <div class="flex gap-1">
                                    @if($email->is_primary)
                                        <x-ui-badge variant="success" size="xs">Primär</x-ui-badge>
                                    @endif
                                    <x-ui-badge variant="primary" size="xs">{{ $email->emailType->name }}</x-ui-badge>
                                </div>
                            </div>
                        @endforeach
                        @if($company->emailAddresses->count() === 0)
                            <p class="text-sm text-[color:var(--ui-muted)]">Noch keine E-Mail-Adressen vorhanden.</p>
                        @endif
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addEmail">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                E-Mail-Adresse hinzufügen
                            </div>
                        </x-ui-button>
                    </div>
                </div>

                <hr>

                {{-- Adressen --}}
                <div class="mb-2">
                    <h4 class="font-semibold mb-2">Adressen</h4>
                    <div class="space-y-2">
                        @foreach($company->postalAddresses as $address)
                            <div class="flex items-center gap-2 p-2 bg-[color:var(--ui-muted-5)] rounded cursor-pointer" wire:click="editAddress({{ $address->id }})">
                                <span class="flex-grow text-sm">{{ $address->full_address }}</span>
                                <div class="flex gap-1">
                                    @if($address->is_primary)
                                        <x-ui-badge variant="success" size="xs">Primär</x-ui-badge>
                                    @endif
                                    <x-ui-badge variant="primary" size="xs">{{ $address->addressType->name }}</x-ui-badge>
                                </div>
                            </div>
                        @endforeach
                        @if($company->postalAddresses->count() === 0)
                            <p class="text-sm text-[color:var(--ui-muted)]">Noch keine Adressen vorhanden.</p>
                        @endif
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addAddress">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Adresse hinzufügen
                            </div>
                        </x-ui-button>
                    </div>
                </div>

                <hr>

                {{-- Kontakte --}}
                <div class="mb-2">
                    <h4 class="font-semibold mb-2">Kontakte</h4>
                    <div class="space-y-2">
                        @foreach($company->contactRelations as $relation)
                            <div class="flex items-center gap-2 p-2 bg-[color:var(--ui-muted-5)] rounded cursor-pointer" wire:click="editContact({{ $relation->id }})">
                                <div class="flex-grow">
                                    <div class="text-sm font-medium">
                                        <a href="{{ route('crm.contacts.show', ['contact' => $relation->contact->id]) }}" 
                                           class="hover:underline text-[color:var(--ui-primary)]" 
                                           wire:navigate
                                           @click.stop>
                                            {{ $relation->contact->full_name }}
                                        </a>
                                    </div>
                                    <div class="text-xs text-[color:var(--ui-muted)]">
                                        @if($relation->position)
                                            {{ $relation->position }} - 
                                        @endif
                                        {{ $relation->relationType->name }}
                                        @if($relation->start_date)
                                            ({{ $relation->start_date->format('d.m.Y') }}
                                            @if($relation->end_date)
                                                - {{ $relation->end_date->format('d.m.Y') }}
                                            @endif
                                            )
                                        @endif
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    @if($relation->is_primary)
                                        <x-ui-badge variant="success" size="xs">Primär</x-ui-badge>
                                    @endif
                                    @if($relation->is_current)
                                        <x-ui-badge variant="primary" size="xs">Aktiv</x-ui-badge>
                                    @else
                                        <x-ui-badge variant="secondary" size="xs">Vergangen</x-ui-badge>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if($company->contactRelations->count() === 0)
                            <p class="text-sm text-[color:var(--ui-muted)]">Noch keine Kontakte verknüpft.</p>
                        @endif
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addContact">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Kontakt hinzufügen
                            </div>
                        </x-ui-button>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 text-sm text-[var(--ui-muted)]">Keine Aktivitäten verfügbar</div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
            
            {{-- Unternehmensdaten --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Unternehmensdaten</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-text 
                        name="company.name"
                        label="Name"
                        wire:model.live.debounce.500ms="company.name"
                        placeholder="Unternehmensname eingeben..."
                        required
                        :errorKey="'company.name'"
                    />
                    <x-ui-input-text 
                        name="company.legal_name"
                        label="Rechtlicher Name"
                        wire:model.live.debounce.500ms="company.legal_name"
                        placeholder="z.B. Muster GmbH"
                        :errorKey="'company.legal_name'"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <x-ui-input-text 
                        name="company.trading_name"
                        label="Handelsname"
                        wire:model.live.debounce.500ms="company.trading_name"
                        placeholder="z.B. Muster Solutions"
                        :errorKey="'company.trading_name'"
                    />
                    <x-ui-input-text 
                        name="company.website"
                        label="Website"
                        wire:model.live.debounce.500ms="company.website"
                        placeholder="https://example.com"
                        :errorKey="'company.website'"
                    />
                </div>
            </div>

            {{-- Rechtliche Informationen --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Rechtliche Informationen</h3>
                <div class="grid grid-cols-2 gap-4">
                    <x-ui-input-select
                        name="company.legal_form_id"
                        label="Rechtsform"
                        :options="$legalForms"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Rechtsform auswählen –"
                        wire:model.live="company.legal_form_id"
                        :errorKey="'company.legal_form_id'"
                    />
                    <x-ui-input-text 
                        name="company.registration_number"
                        label="Handelsregisternummer"
                        wire:model.live.debounce.500ms="company.registration_number"
                        placeholder="HRB 12345"
                        :errorKey="'company.registration_number'"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4 mt-4">
                    <x-ui-input-text 
                        name="company.tax_number"
                        label="Steuernummer"
                        wire:model.live.debounce.500ms="company.tax_number"
                        placeholder="123/456/78901"
                        :errorKey="'company.tax_number'"
                    />
                    <x-ui-input-text 
                        name="company.vat_number"
                        label="USt-IdNr."
                        wire:model.live.debounce.500ms="company.vat_number"
                        placeholder="DE123456789"
                        :errorKey="'company.vat_number'"
                    />
                </div>
            </div>

            {{-- Beschreibung & Notizen --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-4 text-secondary">Beschreibung & Notizen</h3>
                <div class="space-y-4">
                    <x-ui-input-textarea 
                        name="company.description"
                        label="Beschreibung"
                        wire:model.live.debounce.500ms="company.description"
                        placeholder="Unternehmensbeschreibung..."
                        rows="4"
                        :errorKey="'company.description'"
                    />
                    <x-ui-input-textarea 
                        name="company.notes"
                        label="Notizen"
                        wire:model.live.debounce.500ms="company.notes"
                        placeholder="Interne Notizen..."
                        rows="4"
                        :errorKey="'company.notes'"
                    />
                </div>
            </div>
    </x-ui-page-container>

    <!-- Phone Create Modal -->
    <x-ui-modal
        size="sm"
        model="phoneCreateModalShow"
    >
        <x-slot name="header">
            Telefonnummer hinzufügen
        </x-slot>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="phoneForm.raw_input"
                    label="Telefonnummer"
                    wire:model.live="phoneForm.raw_input"
                    required
                    placeholder="0151 1234567"
                    :errorKey="'phoneForm.raw_input'"
                />

                <x-ui-input-select
                    name="phoneForm.country_code"
                    label="Land"
                    :options="$countries"
                    optionValue="code"
                    optionLabel="name"
                    :nullable="false"
                    wire:model.live="phoneForm.country_code"
                />
            </div>

            <x-ui-input-select
                name="phoneForm.phone_type_id"
                label="Telefon-Typ"
                :options="$phoneTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="phoneForm.phone_type_id"
            />

            <x-ui-input-checkbox
                model="phoneForm.is_primary"
                checked-label="Primäre Telefonnummer"
                unchecked-label="Als primäre Telefonnummer markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closePhoneCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="savePhone">
                    Hinzufügen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Phone Edit Modal -->
    <x-ui-modal
        size="sm"
        model="phoneEditModalShow"
    >
        <x-slot name="header">
            Telefonnummer bearbeiten
        </x-slot>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="phoneForm.raw_input"
                    label="Telefonnummer"
                    wire:model.live="phoneForm.raw_input"
                    required
                    placeholder="0151 1234567"
                    :errorKey="'phoneForm.raw_input'"
                />

                <x-ui-input-select
                    name="phoneForm.country_code"
                    label="Land"
                    :options="$countries"
                    optionValue="code"
                    optionLabel="name"
                    :nullable="false"
                    wire:model.live="phoneForm.country_code"
                />
            </div>

            <x-ui-input-select
                name="phoneForm.phone_type_id"
                label="Telefon-Typ"
                :options="$phoneTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="phoneForm.phone_type_id"
            />

            <x-ui-input-checkbox
                model="phoneForm.is_primary"
                checked-label="Primäre Telefonnummer"
                unchecked-label="Als primäre Telefonnummer markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-ui-confirm-button 
                        action="deletePhoneAndCloseModal" 
                        text="Löschen" 
                        confirmText="Wirklich löschen?" 
                        variant="danger-outline"
                        :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                    />
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <x-ui-button 
                        type="button" 
                        variant="secondary-outline" 
                        wire:click="closePhoneEditModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="savePhone">
                        Speichern
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- E-Mail Create Modal -->
    <x-ui-modal
        size="sm"
        model="emailCreateModalShow"
    >
        <x-slot name="header">
            E-Mail-Adresse hinzufügen
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-text
                name="emailForm.email_address"
                label="E-Mail-Adresse"
                wire:model.live="emailForm.email_address"
                type="email"
                required
                placeholder="max.mustermann@example.com"
                :errorKey="'emailForm.email_address'"
            />

            <x-ui-input-select
                name="emailForm.email_type_id"
                label="E-Mail-Typ"
                :options="$emailTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="emailForm.email_type_id"
            />

            <x-ui-input-checkbox
                model="emailForm.is_primary"
                checked-label="Primäre E-Mail-Adresse"
                unchecked-label="Als primäre E-Mail markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closeEmailCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveEmail">
                    Hinzufügen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- E-Mail Edit Modal -->
    <x-ui-modal
        size="sm"
        model="emailEditModalShow"
    >
        <x-slot name="header">
            E-Mail-Adresse bearbeiten
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-text
                name="emailForm.email_address"
                label="E-Mail-Adresse"
                wire:model.live="emailForm.email_address"
                type="email"
                required
                placeholder="max.mustermann@example.com"
                :errorKey="'emailForm.email_address'"
            />

            <x-ui-input-select
                name="emailForm.email_type_id"
                label="E-Mail-Typ"
                :options="$emailTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="emailForm.email_type_id"
            />

            <x-ui-input-checkbox
                model="emailForm.is_primary"
                checked-label="Primäre E-Mail-Adresse"
                unchecked-label="Als primäre E-Mail markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-ui-confirm-button 
                        action="deleteEmailAndCloseModal" 
                        text="Löschen" 
                        confirmText="Wirklich löschen?" 
                        variant="danger-outline"
                        :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                    />
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <x-ui-button 
                        type="button" 
                        variant="secondary-outline" 
                        wire:click="closeEmailEditModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveEmail">
                        Speichern
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Address Create Modal -->
    <x-ui-modal
        size="lg"
        model="addressCreateModalShow"
    >
        <x-slot name="header">
            Adresse hinzufügen
        </x-slot>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="addressForm.street"
                    label="Straße"
                    wire:model.live="addressForm.street"
                    required
                    placeholder="Musterstraße"
                    :errorKey="'addressForm.street'"
                />

                <x-ui-input-text
                    name="addressForm.house_number"
                    label="Hausnummer"
                    wire:model.live="addressForm.house_number"
                    placeholder="123"
                    :errorKey="'addressForm.house_number'"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="addressForm.postal_code"
                    label="PLZ"
                    wire:model.live="addressForm.postal_code"
                    required
                    placeholder="12345"
                    :errorKey="'addressForm.postal_code'"
                />

                <x-ui-input-text
                    name="addressForm.city"
                    label="Stadt"
                    wire:model.live="addressForm.city"
                    required
                    placeholder="Musterstadt"
                    :errorKey="'addressForm.city'"
                />
            </div>

            <x-ui-input-text
                name="addressForm.additional_info"
                label="Zusätzliche Informationen"
                wire:model.live="addressForm.additional_info"
                placeholder="Apartment, Etage, etc."
                :errorKey="'addressForm.additional_info'"
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select
                    name="addressForm.country_id"
                    label="Land"
                    :options="$countries"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="false"
                    wire:model.live="addressForm.country_id"
                    required
                />

                <x-ui-input-select
                    name="addressForm.state_id"
                    label="Bundesland"
                    :options="$states"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    wire:model.live="addressForm.state_id"
                />
            </div>

            <x-ui-input-select
                name="addressForm.address_type_id"
                label="Adresstyp"
                :options="$addressTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="addressForm.address_type_id"
            />

            <x-ui-input-checkbox
                model="addressForm.is_primary"
                checked-label="Primäre Adresse"
                unchecked-label="Als primäre Adresse markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closeAddressCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveAddress">
                    Hinzufügen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Address Edit Modal -->
    <x-ui-modal
        size="lg"
        model="addressEditModalShow"
    >
        <x-slot name="header">
            Adresse bearbeiten
        </x-slot>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="addressForm.street"
                    label="Straße"
                    wire:model.live="addressForm.street"
                    required
                    placeholder="Musterstraße"
                    :errorKey="'addressForm.street'"
                />

                <x-ui-input-text
                    name="addressForm.house_number"
                    label="Hausnummer"
                    wire:model.live="addressForm.house_number"
                    placeholder="123"
                    :errorKey="'addressForm.house_number'"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="addressForm.postal_code"
                    label="PLZ"
                    wire:model.live="addressForm.postal_code"
                    required
                    placeholder="12345"
                    :errorKey="'addressForm.postal_code'"
                />

                <x-ui-input-text
                    name="addressForm.city"
                    label="Stadt"
                    wire:model.live="addressForm.city"
                    required
                    placeholder="Musterstadt"
                    :errorKey="'addressForm.city'"
                />
            </div>

            <x-ui-input-text
                name="addressForm.additional_info"
                label="Zusätzliche Informationen"
                wire:model.live="addressForm.additional_info"
                placeholder="Apartment, Etage, etc."
                :errorKey="'addressForm.additional_info'"
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select
                    name="addressForm.country_id"
                    label="Land"
                    :options="$countries"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="false"
                    wire:model.live="addressForm.country_id"
                    required
                />

                <x-ui-input-select
                    name="addressForm.state_id"
                    label="Bundesland"
                    :options="$states"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    wire:model.live="addressForm.state_id"
                />
            </div>

            <x-ui-input-select
                name="addressForm.address_type_id"
                label="Adresstyp"
                :options="$addressTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="addressForm.address_type_id"
            />

            <x-ui-input-checkbox
                model="addressForm.is_primary"
                checked-label="Primäre Adresse"
                unchecked-label="Als primäre Adresse markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-ui-confirm-button 
                        action="deleteAddressAndCloseModal" 
                        text="Löschen" 
                        confirmText="Wirklich löschen?" 
                        variant="danger-outline"
                        :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                    />
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <x-ui-button 
                        type="button" 
                        variant="secondary-outline" 
                        wire:click="closeAddressEditModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveAddress">
                        Speichern
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Contact Create Modal -->
    <x-ui-modal
        size="lg"
        model="contactCreateModalShow"
    >
        <x-slot name="header">
            Kontakt hinzufügen
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-select
                name="contactRelationForm.contact_id"
                label="Kontakt"
                :options="$this->filteredContacts"
                optionValue="id"
                optionLabel="full_name"
                :nullable="true"
                nullLabel="– Kontakt auswählen –"
                wire:model.live="contactRelationForm.contact_id"
                required
            />

            <x-ui-input-select
                name="contactRelationForm.relation_type_id"
                label="Beziehungstyp"
                :options="$relationTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="contactRelationForm.relation_type_id"
            />

            <x-ui-input-text
                name="contactRelationForm.position"
                label="Position"
                wire:model.live="contactRelationForm.position"
                placeholder="z.B. Geschäftsführer, Abteilungsleiter"
                :errorKey="'contactRelationForm.position'"
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date
                    name="contactRelationForm.start_date"
                    label="Startdatum"
                    wire:model.live="contactRelationForm.start_date"
                    :nullable="true"
                    :errorKey="'contactRelationForm.start_date'"
                />

                <x-ui-input-date
                    name="contactRelationForm.end_date"
                    label="Enddatum (optional)"
                    wire:model.live="contactRelationForm.end_date"
                    :nullable="true"
                    :errorKey="'contactRelationForm.end_date'"
                />
            </div>

            <x-ui-input-textarea
                name="contactRelationForm.notes"
                label="Notizen"
                wire:model.live="contactRelationForm.notes"
                placeholder="Zusätzliche Informationen zur Beziehung"
                rows="3"
                :errorKey="'contactRelationForm.notes'"
            />

            <x-ui-input-checkbox
                model="contactRelationForm.is_primary"
                checked-label="Primärer Kontakt"
                unchecked-label="Als primären Kontakt markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closeContactCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveContact">
                    Hinzufügen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Contact Edit Modal -->
    <x-ui-modal
        size="lg"
        model="contactEditModalShow"
    >
        <x-slot name="header">
            Kontakt-Beziehung bearbeiten
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-select
                name="contactRelationForm.contact_id"
                label="Kontakt"
                :options="$this->filteredContacts"
                optionValue="id"
                optionLabel="full_name"
                :nullable="true"
                nullLabel="– Kontakt auswählen –"
                wire:model.live="contactRelationForm.contact_id"
                required
            />

            <x-ui-input-select
                name="contactRelationForm.relation_type_id"
                label="Beziehungstyp"
                :options="$relationTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="contactRelationForm.relation_type_id"
            />

            <x-ui-input-text
                name="contactRelationForm.position"
                label="Position"
                wire:model.live="contactRelationForm.position"
                placeholder="z.B. Geschäftsführer, Abteilungsleiter"
                :errorKey="'contactRelationForm.position'"
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date
                    name="contactRelationForm.start_date"
                    label="Startdatum"
                    wire:model.live="contactRelationForm.start_date"
                    :nullable="true"
                    :errorKey="'contactRelationForm.start_date'"
                />

                <x-ui-input-date
                    name="contactRelationForm.end_date"
                    label="Enddatum (optional)"
                    wire:model.live="contactRelationForm.end_date"
                    :nullable="true"
                    :errorKey="'contactRelationForm.end_date'"
                />
            </div>

            <x-ui-input-textarea
                name="contactRelationForm.notes"
                label="Notizen"
                wire:model.live="contactRelationForm.notes"
                placeholder="Zusätzliche Informationen zur Beziehung"
                rows="3"
                :errorKey="'contactRelationForm.notes'"
            />

            <x-ui-input-checkbox
                model="contactRelationForm.is_primary"
                checked-label="Primärer Kontakt"
                unchecked-label="Als primären Kontakt markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-ui-confirm-button 
                        action="deleteContactAndCloseModal" 
                        text="Löschen" 
                        confirmText="Wirklich löschen?" 
                        variant="danger-outline"
                        :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                    />
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    @if($editingContactRelationId)
                        <x-ui-button 
                            type="button" 
                            variant="secondary-outline" 
                            :href="route('crm.contacts.show', ['contact' => $contactRelationForm['contact_id']])"
                            wire:navigate
                        >
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-user', 'w-4 h-4')
                                Zum Kontakt
                            </div>
                        </x-ui-button>
                    @endif
                    <x-ui-button 
                        type="button" 
                        variant="secondary-outline" 
                        wire:click="closeContactEditModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveContact">
                        Speichern
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

</x-ui-page> 