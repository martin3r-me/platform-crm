<div class="p-3">
    {{-- Kopfzeile mit Aktionsbuttons --}}
    <div class="d-flex justify-between items-center">
        <h1 class="text-2xl font-bold">
            {{ $edit ? 'Kontakt bearbeiten' : $contact->full_name }}
        </h1>

        <div class="d-flex gap-2">
            @if ($edit)
                <x-ui-button variant="secondary-outline" wire:click="cancelEdit">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" wire:click="save">
                    Speichern
                </x-ui-button>
            @else
                <x-ui-button variant="primary" wire:click="toggleEdit">
                    Bearbeiten
                </x-ui-button>
            @endif
            <x-ui-button 
                size="sm" 
                variant="secondary" 
                href="{{ route('crm.contacts.index') }}" 
                wire:navigate
            >
                Schließen
            </x-ui-button>
        </div>
    </div>

    {{-- Formular / Anzeige --}}
    <form wire:submit.prevent="save" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            @if($edit)
                <x-ui-input-text label="Vorname" name="contact.first_name" wire:model.live="contact.first_name" required />
                <x-ui-input-text label="Nachname" name="contact.last_name" wire:model.live="contact.last_name" required />
            @else
                <div><strong>Vorname:</strong> {{ $contact->first_name }}</div>
                <div><strong>Nachname:</strong> {{ $contact->last_name }}</div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4">
            @if($edit)
                <x-ui-input-text label="Zweiter Vorname" name="contact.middle_name" wire:model.live="contact.middle_name" />
                <x-ui-input-text label="Spitzname" name="contact.nickname" wire:model.live="contact.nickname" />
            @else
                <div><strong>Zweiter Vorname:</strong> {{ $contact->middle_name ?: '–' }}</div>
                <div><strong>Spitzname:</strong> {{ $contact->nickname ?: '–' }}</div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4">
            @if($edit)
                <x-ui-input-date label="Geburtsdatum" name="contact.birth_date" wire:model.live="contact.birth_date" />
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
            @else
                <div><strong>Geburtsdatum:</strong> {{ $contact->birth_date ?: '–' }}</div>
                <div><strong>Status:</strong> {{ $contact->contactStatus->name ?? '–' }}</div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4">
            @if($edit)
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
            @else
                <div><strong>Anrede:</strong> {{ $contact->salutation->name ?? '–' }}</div>
                <div><strong>Titel:</strong> {{ $contact->academicTitle->name ?? '–' }}</div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4">
            @if($edit)
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
            @else
                <div><strong>Geschlecht:</strong> {{ $contact->gender->name ?? '–' }}</div>
                <div><strong>Sprache:</strong> {{ $contact->language->name ?? '–' }}</div>
            @endif
        </div>

        <div>
            @if($edit)
                <x-ui-input-textarea
                    name="contact.notes"
                    label="Notizen"
                    wire:model.live="contact.notes"
                    rows="4"
                    placeholder="Zusätzliche Notizen (optional)"
                />
            @else
                <div><strong>Notizen:</strong><br>{{ $contact->notes ?: '–' }}</div>
            @endif
        </div>
    </form>
</div>