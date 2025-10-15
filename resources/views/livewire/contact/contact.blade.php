<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="{{ $contact->full_name }}" icon="heroicon-o-user">
            <x-ui-button 
                variant="primary" 
                size="sm"
                wire:click="save"
                :disabled="!$this->isDirty"
            >
                <div class="flex items-center gap-2">
                    @svg('heroicon-o-check', 'w-4 h-4')
                    Speichern
                </div>
            </x-ui-button>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Einstellungen" width="w-72" defaultOpen="true" storeKey="sidebarOpen" side="left">
            <div class="p-4 space-y-4">
                {{-- Navigation Buttons --}}
                <div class="flex flex-col gap-2 mb-4">
                    <x-ui-button 
                        variant="secondary-outline" 
                        size="md" 
                        :href="route('crm.contacts.index')" 
                        wire:navigate
                        class="w-full"
                    >
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-arrow-left', 'w-4 h-4')
                            Zurück zu Kontakten
                        </div>
                    </x-ui-button>
                </div>

                {{-- Kurze Übersicht --}}
                <div class="p-3 bg-[color:var(--ui-muted-5)] rounded-lg">
                    <h4 class="font-semibold mb-2 text-[color:var(--ui-secondary)]">Kontakt</h4>
                    <div class="space-y-1 text-sm">
                        <div><strong>Name:</strong> {{ $contact->full_name }}</div>
                        @if($contact->academicTitle)
                            <div><strong>Titel:</strong> {{ $contact->academicTitle->name }}</div>
                        @endif
                        @if($contact->salutation)
                            <div><strong>Anrede:</strong> {{ $contact->salutation->name }}</div>
                        @endif
                        @if($contact->nickname)
                            <div><strong>Spitzname:</strong> {{ $contact->nickname }}</div>
                        @endif
                    </div>
                </div>

                {{-- Status --}}
                <x-ui-input-select
                    name="contact.contact_status_id"
                    label="Status"
                    :options="$contactStatuses"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Status auswählen –"
                    wire:model.live="contact.contact_status_id"
                    required
                />

                <hr>

                {{-- Telefonnummern --}}
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Telefonnummern</h4>
                    <div class="space-y-2">
                        @foreach($contact->phoneNumbers as $phone)
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
                        @if($contact->phoneNumbers->count() === 0)
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
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">E-Mail-Adressen</h4>
                    <div class="space-y-2">
                        @foreach($contact->emailAddresses as $email)
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
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Adressen</h4>
                    <div class="space-y-2">
                        @foreach($contact->postalAddresses as $address)
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
                        @if($contact->postalAddresses->count() === 0)
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

                {{-- Unternehmen --}}
                <div class="mb-4">
                    <h4 class="font-semibold mb-2">Unternehmen</h4>
                    <div class="space-y-2">
                        @foreach($contact->contactRelations as $relation)
                            <div class="flex items-center gap-2 p-2 bg-[color:var(--ui-muted-5)] rounded cursor-pointer" wire:click="editCompany({{ $relation->id }})">
                                <div class="flex-grow">
                                    <div class="text-sm font-medium">
                                        <a href="{{ route('crm.companies.show', ['company' => $relation->company->id]) }}" 
                                           class="hover:underline text-[color:var(--ui-primary)]" 
                                           wire:navigate
                                           @click.stop>
                                            {{ $relation->company->display_name }}
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
                        @if($contact->contactRelations->count() === 0)
                            <p class="text-sm text-[color:var(--ui-muted)]">Noch keine Unternehmen verknüpft.</p>
                        @endif
                        <x-ui-button size="sm" variant="secondary-outline" wire:click="addCompany">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Unternehmen hinzufügen
                            </div>
                        </x-ui-button>
                    </div>
                </div>

                <hr>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-4">
                <p class="text-sm text-[color:var(--ui-muted)]">Aktivitäten werden hier angezeigt...</p>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
            <div class="min-w-0 space-y-6">
            {{-- Persönliche Daten --}}
            <x-ui-panel title="Persönliche Daten">
                <div class="grid grid-cols-1 gap-4">
                    <x-ui-input-text 
                        name="contact.first_name"
                        label="Vorname"
                        wire:model.live.debounce.500ms="contact.first_name"
                        placeholder="Vorname eingeben..."
                        required
                        :errorKey="'contact.first_name'"
                    />
                    <x-ui-input-text 
                        name="contact.last_name"
                        label="Nachname"
                        wire:model.live.debounce.500ms="contact.last_name"
                        placeholder="Nachname eingeben..."
                        required
                        :errorKey="'contact.last_name'"
                    />
                </div>
                <div class="grid grid-cols-1 gap-4 mt-4">
                    <x-ui-input-text 
                        name="contact.middle_name"
                        label="Zweiter Vorname"
                        wire:model.live.debounce.500ms="contact.middle_name"
                        placeholder="Zweiter Vorname (optional)"
                        :errorKey="'contact.middle_name'"
                    />
                    <x-ui-input-text 
                        name="contact.nickname"
                        label="Spitzname"
                        wire:model.live.debounce.500ms="contact.nickname"
                        placeholder="Spitzname (optional)"
                        :errorKey="'contact.nickname'"
                    />
                </div>
                <div class="mt-4">
                    <x-ui-input-date 
                        name="contact.birth_date"
                        label="Geburtsdatum"
                        wire:model.live.debounce.500ms="contact.birth_date"
                        placeholder="Geburtsdatum (optional)"
                        :nullable="true"
                        :errorKey="'contact.birth_date'"
                    />
                </div>
            </x-ui-panel>

            {{-- Anrede & Titel --}}
            <x-ui-panel title="Anrede & Titel">
                <div class="grid grid-cols-1 gap-4">
                    <x-ui-input-select
                        name="contact.salutation_id"
                        label="Anrede"
                        :options="$salutations"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Anrede auswählen –"
                        wire:model.live="contact.salutation_id"
                    />
                    <x-ui-input-select
                        name="contact.academic_title_id"
                        label="Akademischer Titel"
                        :options="$academicTitles"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Titel auswählen –"
                        wire:model.live="contact.academic_title_id"
                    />
                </div>
            </x-ui-panel>

            {{-- Weitere Informationen --}}
            <x-ui-panel title="Weitere Informationen">
                <div class="grid grid-cols-1 gap-4">
                    <x-ui-input-select
                        name="contact.gender_id"
                        label="Geschlecht"
                        :options="$genders"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Geschlecht auswählen –"
                        wire:model.live="contact.gender_id"
                    />
                    <x-ui-input-select
                        name="contact.language_id"
                        label="Sprache"
                        :options="$languages"
                        optionValue="id"
                        optionLabel="name"
                        :nullable="true"
                        nullLabel="– Sprache auswählen –"
                        wire:model.live="contact.language_id"
                    />
                </div>
                <div class="mt-4">
                    <x-ui-input-textarea 
                        name="contact.notes"
                        label="Notizen"
                        wire:model.live.debounce.500ms="contact.notes"
                        placeholder="Zusätzliche Notizen (optional)"
                        rows="4"
                        :errorKey="'contact.notes'"
                    />
                </div>
            </x-ui-panel>
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

    <!-- Company Create Modal -->
    <x-ui-modal
        size="lg"
        model="companyCreateModalShow"
    >
        <x-slot name="header">
            Unternehmen hinzufügen
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-select
                name="companyRelationForm.company_id"
                label="Unternehmen"
                :options="$this->filteredCompanies"
                optionValue="id"
                optionLabel="display_name"
                :nullable="true"
                nullLabel="– Unternehmen auswählen –"
                wire:model.live="companyRelationForm.company_id"
                required
            />

            <x-ui-input-select
                name="companyRelationForm.relation_type_id"
                label="Beziehungstyp"
                :options="$relationTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="companyRelationForm.relation_type_id"
            />

            <x-ui-input-text
                name="companyRelationForm.position"
                label="Position"
                wire:model.live="companyRelationForm.position"
                placeholder="z.B. Geschäftsführer, Abteilungsleiter"
                :errorKey="'companyRelationForm.position'"
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date
                    name="companyRelationForm.start_date"
                    label="Startdatum"
                    wire:model.live="companyRelationForm.start_date"
                    :nullable="true"
                    :errorKey="'companyRelationForm.start_date'"
                />

                <x-ui-input-date
                    name="companyRelationForm.end_date"
                    label="Enddatum (optional)"
                    wire:model.live="companyRelationForm.end_date"
                    :nullable="true"
                    :errorKey="'companyRelationForm.end_date'"
                />
            </div>

            <x-ui-input-textarea
                name="companyRelationForm.notes"
                label="Notizen"
                wire:model.live="companyRelationForm.notes"
                placeholder="Zusätzliche Informationen zur Beziehung"
                rows="3"
                :errorKey="'companyRelationForm.notes'"
            />

            <x-ui-input-checkbox
                model="companyRelationForm.is_primary"
                checked-label="Primäres Unternehmen"
                unchecked-label="Als primäres Unternehmen markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button 
                    type="button" 
                    variant="secondary-outline" 
                    wire:click="closeCompanyCreateModal"
                >
                    Abbrechen
                </x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveCompany">
                    Hinzufügen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Company Edit Modal -->
    <x-ui-modal
        size="lg"
        model="companyEditModalShow"
    >
        <x-slot name="header">
            Unternehmen-Beziehung bearbeiten
        </x-slot>

        <div class="space-y-4">
            <x-ui-input-select
                name="companyRelationForm.company_id"
                label="Unternehmen"
                :options="$this->filteredCompanies"
                optionValue="id"
                optionLabel="display_name"
                :nullable="true"
                nullLabel="– Unternehmen auswählen –"
                wire:model.live="companyRelationForm.company_id"
                required
            />

            <x-ui-input-select
                name="companyRelationForm.relation_type_id"
                label="Beziehungstyp"
                :options="$relationTypes"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                wire:model.live="companyRelationForm.relation_type_id"
            />

            <x-ui-input-text
                name="companyRelationForm.position"
                label="Position"
                wire:model.live="companyRelationForm.position"
                placeholder="z.B. Geschäftsführer, Abteilungsleiter"
                :errorKey="'companyRelationForm.position'"
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date
                    name="companyRelationForm.start_date"
                    label="Startdatum"
                    wire:model.live="companyRelationForm.start_date"
                    :nullable="true"
                    :errorKey="'companyRelationForm.start_date'"
                />

                <x-ui-input-date
                    name="companyRelationForm.end_date"
                    label="Enddatum (optional)"
                    wire:model.live="companyRelationForm.end_date"
                    :nullable="true"
                    :errorKey="'companyRelationForm.end_date'"
                />
            </div>

            <x-ui-input-textarea
                name="companyRelationForm.notes"
                label="Notizen"
                wire:model.live="companyRelationForm.notes"
                placeholder="Zusätzliche Informationen zur Beziehung"
                rows="3"
                :errorKey="'companyRelationForm.notes'"
            />

            <x-ui-input-checkbox
                model="companyRelationForm.is_primary"
                checked-label="Primäres Unternehmen"
                unchecked-label="Als primäres Unternehmen markieren"
                size="md"
                block="true"
            />
        </div>

        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <div class="flex-shrink-0">
                    <x-ui-confirm-button 
                        action="deleteCompanyAndCloseModal" 
                        text="Löschen" 
                        confirmText="Wirklich löschen?" 
                        variant="danger-outline"
                        :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                    />
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    @if($editingCompanyRelationId)
                        <x-ui-button 
                            type="button" 
                            variant="secondary-outline" 
                            :href="route('crm.companies.show', ['company' => $companyRelationForm['company_id']])"
                            wire:navigate
                        >
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-building-office', 'w-4 h-4')
                                Zum Unternehmen
                            </div>
                        </x-ui-button>
                    @endif
                    <x-ui-button 
                        type="button" 
                        variant="secondary-outline" 
                        wire:click="closeCompanyEditModal"
                    >
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveCompany">
                        Speichern
                    </x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

</x-ui-page>