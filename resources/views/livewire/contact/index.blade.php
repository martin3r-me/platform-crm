<div class = "p-3">
    <h1 class="text-2xl font-bold mb-4">Kontakte</h1>

    <div class="d-flex justify-between mb-4">
        <x-ui-input-text 
            name="search" 
            placeholder="Suche Kontakte..." 
            class="w-64"
        />
        <x-ui-button variant="primary" wire:click="openCreateModal">
            Neuer Kontakt
        </x-ui-button>
    </div>
    
    <div class="bg-surface rounded-lg border border-muted overflow-hidden">
        <table class="w-full">
            <thead class="bg-muted-5">
                <tr>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">Name</th>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">E-Mail</th>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">Status</th>
                    <th class="p-4 text-left font-semibold text-secondary border-bottom-1 border-bottom-muted">Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contacts as $contact)
                    <tr class="border-bottom-1 border-bottom-muted hover:bg-muted-5 transition-colors">
                        <td class="p-4 text-body">{{ $contact->full_name }}</td>
                        <td class="p-4 text-body">{{ $contact->emailAddresses->first()?->email_address }}</td>
                        <td class="p-4">
                            <x-ui-badge variant="primary" size="sm">
                                {{ $contact->contactStatus->name }}
                            </x-ui-badge>
                        </td>
                        <td class="p-4">
                            <x-ui-button 
                                size="sm" 
                                variant="secondary" 
                                href="{{ route('crm.contacts.show', ['contact' => $contact->id]) }}?edit=1" 
                                wire:navigate
                            >
                                Bearbeiten
                            </x-ui-button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $contacts->links() }}
    </div>

    <!-- Create Contact Modal -->
    <x-ui-modal
        wire:model="modalShow"
        size="lg"
        header="Neuen Kontakt anlegen"
        :footer="null"
    >
        <form wire:submit.prevent="createContact" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="first_name"
                    label="Vorname"
                    wire:model.live="first_name"
                    required
                    placeholder="Vorname eingeben"
                />
                
                <x-ui-input-text
                    name="last_name"
                    label="Nachname"
                    wire:model.live="last_name"
                    required
                    placeholder="Nachname eingeben"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text
                    name="middle_name"
                    label="Zweiter Vorname"
                    wire:model.live="middle_name"
                    placeholder="Zweiter Vorname (optional)"
                />
                
                <x-ui-input-text
                    name="nickname"
                    label="Spitzname"
                    wire:model.live="nickname"
                    placeholder="Spitzname (optional)"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date
                    name="birth_date"
                    label="Geburtsdatum"
                    wire:model.live="birth_date"
                    placeholder="Geburtsdatum (optional)"
                />

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
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select
                    name="salutation_id"
                    label="Anrede"
                    :options="$salutations"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Anrede auswählen –"
                    wire:model.live="salutation_id"
                />

                <x-ui-input-select
                    name="academic_title_id"
                    label="Akademischer Titel"
                    :options="$academicTitles"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Titel auswählen –"
                    wire:model.live="academic_title_id"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select
                    name="gender_id"
                    label="Geschlecht"
                    :options="$genders"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Geschlecht auswählen –"
                    wire:model.live="gender_id"
                />

                <x-ui-input-select
                    name="language_id"
                    label="Sprache"
                    :options="$languages"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Sprache auswählen –"
                    wire:model.live="language_id"
                />
            </div>

            <x-ui-input-textarea
                name="notes"
                label="Notizen"
                wire:model.live="notes"
                placeholder="Zusätzliche Notizen (optional)"
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
                    Kontakt anlegen
                </x-ui-button>
            </div>
        </form>
    </x-ui-modal>
</div>