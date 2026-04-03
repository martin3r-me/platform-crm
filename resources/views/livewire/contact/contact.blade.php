<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Kontakte', 'href' => route('crm.contacts.index')],
            ['label' => $contact->full_name],
        ]">
            <x-slot name="left">
                {{-- Status --}}
                <x-ui-input-select
                    name="contact.contact_status_id"
                    :options="$contactStatuses"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="true"
                    nullLabel="– Status –"
                    size="sm"
                    wire:model.live="contact.contact_status_id"
                />

                {{-- Blacklist --}}
                <x-ui-button
                    size="xs"
                    variant="{{ $contact->is_blacklisted ? 'danger' : 'secondary-outline' }}"
                    wire:click="toggleBlacklist"
                >
                    @svg('heroicon-s-no-symbol', 'w-3.5 h-3.5')
                    {{ $contact->is_blacklisted ? 'Blacklisted' : 'Blacklisten' }}
                </x-ui-button>
            </x-slot>

            {{-- Right side actions --}}
            <div class="flex items-center gap-2">
                <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />

                {{-- Prev/Next Navigation --}}
                @if($prevContactId || $nextContactId)
                    <div class="flex items-center gap-1">
                        @if($prevContactId)
                            <a href="{{ route('crm.contacts.show', $prevContactId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] hover:bg-[color:var(--ui-muted-5)] transition">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] opacity-30">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </span>
                        @endif
                        @if($nextContactId)
                            <a href="{{ route('crm.contacts.show', $nextContactId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] hover:bg-[color:var(--ui-muted-5)] transition">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] opacity-30">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </span>
                        @endif
                    </div>
                @endif

                @if($this->isDirty)
                    <x-ui-button variant="primary" size="sm" wire:click="save">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        <span>Speichern</span>
                    </x-ui-button>
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-5">
                {{-- Summary Card --}}
                <div class="p-3 bg-[color:var(--ui-muted-5)] rounded-lg">
                    <div class="space-y-1 text-sm">
                        <div class="font-semibold text-[color:var(--ui-secondary)]">{{ $contact->full_name }}</div>
                        @if($contact->nickname)
                            <div class="text-[color:var(--ui-muted)]">{{ $contact->nickname }}</div>
                        @endif
                        @if($contact->birth_date)
                            <div class="text-[color:var(--ui-muted)]">@svg('heroicon-o-cake', 'w-3.5 h-3.5 inline') {{ $contact->birth_date->format('d.m.Y') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Quick-Links --}}
                <div class="space-y-1">
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition {{ $activeTab === 'kontaktdaten' ? 'bg-[color:var(--ui-muted-5)] font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-phone', 'w-4 h-4 text-[color:var(--ui-muted)]')
                            Telefonnummern
                        </span>
                        <x-ui-badge variant="secondary" size="xs">{{ $contact->phoneNumbers->count() }}</x-ui-badge>
                    </button>
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition {{ $activeTab === 'kontaktdaten' ? 'bg-[color:var(--ui-muted-5)] font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-envelope', 'w-4 h-4 text-[color:var(--ui-muted)]')
                            E-Mail-Adressen
                        </span>
                        <x-ui-badge variant="secondary" size="xs">{{ $contact->emailAddresses->count() }}</x-ui-badge>
                    </button>
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition {{ $activeTab === 'kontaktdaten' ? 'bg-[color:var(--ui-muted-5)] font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-map-pin', 'w-4 h-4 text-[color:var(--ui-muted)]')
                            Adressen
                        </span>
                        <x-ui-badge variant="secondary" size="xs">{{ $contact->postalAddresses->count() }}</x-ui-badge>
                    </button>
                    <button wire:click="$set('activeTab', 'unternehmen')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition {{ $activeTab === 'unternehmen' ? 'bg-[color:var(--ui-muted-5)] font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-building-office', 'w-4 h-4 text-[color:var(--ui-muted)]')
                            Unternehmen
                        </span>
                        <x-ui-badge variant="secondary" size="xs">{{ $contact->contactRelations->count() }}</x-ui-badge>
                    </button>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="h-full flex flex-col">
                {{-- Timeline (scrollbar) --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    @forelse($contact->activities as $activity)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($activity->activity_type === 'manual')
                                    <div class="w-6 h-6 rounded-full bg-[color:var(--ui-primary-10)] flex items-center justify-center">
                                        @svg('heroicon-s-pencil', 'w-3 h-3 text-[color:var(--ui-primary)]')
                                    </div>
                                @elseif($activity->name === 'created')
                                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                                        @svg('heroicon-s-plus', 'w-3 h-3 text-green-600')
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full bg-[color:var(--ui-muted-10)] flex items-center justify-center">
                                        @svg('heroicon-s-cog-6-tooth', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow min-w-0">
                                @if($activity->activity_type === 'manual')
                                    <p class="text-sm">{{ $activity->message }}</p>
                                @elseif($activity->name === 'created')
                                    <p class="text-sm text-[color:var(--ui-muted)]">Kontakt erstellt</p>
                                @elseif($activity->name === 'updated' && is_array($activity->properties))
                                    <p class="text-sm text-[color:var(--ui-muted)]">
                                        {{ collect($activity->properties)->keys()->map(fn($k) => str_replace('_', ' ', ucfirst($k)))->implode(', ') }} geändert
                                    </p>
                                @else
                                    <p class="text-sm text-[color:var(--ui-muted)]">{{ $activity->name }}</p>
                                @endif
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-[color:var(--ui-muted)]">
                                        {{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                    @if($activity->activity_type === 'manual' && $activity->user_id === auth()->id())
                                        <button wire:click="deleteNote({{ $activity->id }})" wire:confirm="Notiz wirklich löschen?" class="text-xs text-red-400 hover:text-red-600">
                                            @svg('heroicon-o-trash', 'w-3 h-3')
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[color:var(--ui-muted)]">Keine Aktivitäten vorhanden.</p>
                    @endforelse
                </div>

                {{-- Notiz-Eingabe (fixed bottom) --}}
                <div class="flex-shrink-0 border-t border-[color:var(--ui-border)] p-3">
                    @error('newNote')
                        <p class="text-xs text-red-500 mb-2">{{ $message }}</p>
                    @enderror
                    <form wire:submit="addNote" class="flex items-end gap-2">
                        <textarea
                            wire:model="newNote"
                            rows="2"
                            class="flex-1 text-sm rounded-lg border border-[color:var(--ui-border)] bg-[color:var(--ui-bg)] p-2 focus:border-[color:var(--ui-primary)] focus:ring-1 focus:ring-[color:var(--ui-primary)] outline-none resize-none"
                            placeholder="Notiz hinzufügen..."
                        ></textarea>
                        <button type="submit" class="flex-shrink-0 w-8 h-8 rounded-lg bg-[color:var(--ui-primary)] text-white flex items-center justify-center hover:opacity-90 transition">
                            @svg('heroicon-s-arrow-up', 'w-4 h-4')
                        </button>
                    </form>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Tab Navigation --}}
        <div class="border-b border-[color:var(--ui-border)] mb-6">
            <nav class="flex space-x-6">
                <button wire:click="$set('activeTab', 'stammdaten')" class="py-3 px-1 border-b-2 text-sm font-medium transition {{ $activeTab === 'stammdaten' ? 'border-[color:var(--ui-primary)] text-[color:var(--ui-primary)]' : 'border-transparent text-[color:var(--ui-muted)] hover:text-[color:var(--ui-secondary)] hover:border-[color:var(--ui-border)]' }}">
                    Stammdaten
                </button>
                <button wire:click="$set('activeTab', 'kontaktdaten')" class="py-3 px-1 border-b-2 text-sm font-medium transition {{ $activeTab === 'kontaktdaten' ? 'border-[color:var(--ui-primary)] text-[color:var(--ui-primary)]' : 'border-transparent text-[color:var(--ui-muted)] hover:text-[color:var(--ui-secondary)] hover:border-[color:var(--ui-border)]' }}">
                    Kontaktdaten
                </button>
                <button wire:click="$set('activeTab', 'unternehmen')" class="py-3 px-1 border-b-2 text-sm font-medium transition {{ $activeTab === 'unternehmen' ? 'border-[color:var(--ui-primary)] text-[color:var(--ui-primary)]' : 'border-transparent text-[color:var(--ui-muted)] hover:text-[color:var(--ui-secondary)] hover:border-[color:var(--ui-border)]' }}">
                    Unternehmen
                </button>
            </nav>
        </div>

        {{-- Tab: Stammdaten --}}
        @if($activeTab === 'stammdaten')
            <div class="space-y-6">
                {{-- Persönliche Daten --}}
                <x-ui-panel title="Persönliche Daten">
                    <div class="grid grid-cols-2 gap-4">
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
                    <div class="grid grid-cols-2 gap-4 mt-4">
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
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <x-ui-input-date
                            name="birthDate"
                            label="Geburtsdatum"
                            wire:model.live.debounce.500ms="birthDate"
                            placeholder="Geburtsdatum (optional)"
                            :nullable="true"
                            :errorKey="'birthDate'"
                        />
                    </div>
                </x-ui-panel>

                {{-- Weitere Angaben --}}
                <x-ui-panel title="Weitere Angaben">
                    <div class="grid grid-cols-2 gap-4">
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
                    <div class="grid grid-cols-2 gap-4 mt-4">
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
                            rows="3"
                            :errorKey="'contact.notes'"
                        />
                    </div>
                </x-ui-panel>
            </div>
        @endif

        {{-- Tab: Kontaktdaten --}}
        @if($activeTab === 'kontaktdaten')
            <div class="space-y-6">
                {{-- Telefon & E-Mail nebeneinander --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Telefonnummern --}}
                    <x-ui-panel>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)]">Telefonnummern</h3>
                            <x-ui-button size="xs" variant="secondary-outline" wire:click="addPhone">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                Hinzufügen
                            </x-ui-button>
                        </div>
                        @if($contact->phoneNumbers->count() > 0)
                            <div class="space-y-2">
                                @foreach($contact->phoneNumbers as $phone)
                                    <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-[color:var(--ui-border)] hover:border-[color:var(--ui-primary-20)] cursor-pointer transition" wire:click="editPhone({{ $phone->id }})">
                                        <div class="flex items-center gap-2 min-w-0">
                                            @svg('heroicon-o-phone', 'w-4 h-4 text-[color:var(--ui-muted)] flex-shrink-0')
                                            <span class="text-sm truncate">{{ $phone->national ?: $phone->raw_input }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            @if($phone->is_primary)
                                                <x-ui-badge variant="success" size="xs">Primär</x-ui-badge>
                                            @endif
                                            <x-ui-badge variant="secondary" size="xs">{{ $phone->phoneType->name }}</x-ui-badge>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-[color:var(--ui-muted)] py-3">Keine Telefonnummern vorhanden.</p>
                        @endif
                    </x-ui-panel>

                    {{-- E-Mail-Adressen --}}
                    <x-ui-panel>
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)]">E-Mail-Adressen</h3>
                            <x-ui-button size="xs" variant="secondary-outline" wire:click="addEmail">
                                @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                                Hinzufügen
                            </x-ui-button>
                        </div>
                        @if($contact->emailAddresses->count() > 0)
                            <div class="space-y-2">
                                @foreach($contact->emailAddresses as $email)
                                    <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg border border-[color:var(--ui-border)] hover:border-[color:var(--ui-primary-20)] cursor-pointer transition" wire:click="editEmail({{ $email->id }})">
                                        <div class="flex items-center gap-2 min-w-0">
                                            @svg('heroicon-o-envelope', 'w-4 h-4 text-[color:var(--ui-muted)] flex-shrink-0')
                                            <span class="text-sm truncate">{{ $email->email_address }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            @if($email->is_primary)
                                                <x-ui-badge variant="success" size="xs">Primär</x-ui-badge>
                                            @endif
                                            <x-ui-badge variant="secondary" size="xs">{{ $email->emailType->name }}</x-ui-badge>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-[color:var(--ui-muted)] py-3">Keine E-Mail-Adressen vorhanden.</p>
                        @endif
                    </x-ui-panel>
                </div>

                {{-- Adressen --}}
                <x-ui-panel>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)]">Adressen</h3>
                        <x-ui-button size="xs" variant="secondary-outline" wire:click="addAddress">
                            @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                            Hinzufügen
                        </x-ui-button>
                    </div>
                    @if($contact->postalAddresses->count() > 0)
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            @foreach($contact->postalAddresses as $address)
                                <div class="flex items-start justify-between gap-2 p-3 rounded-lg border border-[color:var(--ui-border)] hover:border-[color:var(--ui-primary-20)] cursor-pointer transition" wire:click="editAddress({{ $address->id }})">
                                    <div class="flex items-start gap-2 min-w-0">
                                        @svg('heroicon-o-map-pin', 'w-4 h-4 text-[color:var(--ui-muted)] flex-shrink-0 mt-0.5')
                                        <div class="text-sm">
                                            <div>{{ $address->street }} {{ $address->house_number }}</div>
                                            <div class="text-[color:var(--ui-muted)]">{{ $address->postal_code }} {{ $address->city }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        @if($address->is_primary)
                                            <x-ui-badge variant="success" size="xs">Primär</x-ui-badge>
                                        @endif
                                        <x-ui-badge variant="secondary" size="xs">{{ $address->addressType->name }}</x-ui-badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-[color:var(--ui-muted)] py-3">Keine Adressen vorhanden.</p>
                    @endif
                </x-ui-panel>
            </div>
        @endif

        {{-- Tab: Unternehmen --}}
        @if($activeTab === 'unternehmen')
            <x-ui-panel>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)]">Unternehmen</h3>
                    <x-ui-button size="xs" variant="secondary-outline" wire:click="addCompany">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                        Hinzufügen
                    </x-ui-button>
                </div>
                @if($contact->contactRelations->count() > 0)
                    <div class="space-y-2">
                        @foreach($contact->contactRelations as $relation)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-[color:var(--ui-border)] hover:border-[color:var(--ui-primary-20)] cursor-pointer transition" wire:click="editCompany({{ $relation->id }})">
                                <div class="flex items-center gap-3 min-w-0">
                                    @svg('heroicon-o-building-office', 'w-4 h-4 text-[color:var(--ui-muted)] flex-shrink-0')
                                    <div class="min-w-0">
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
                                                {{ $relation->position }} &middot;
                                            @endif
                                            {{ $relation->relationType->name }}
                                            @if($relation->start_date)
                                                &middot; seit {{ $relation->start_date->format('d.m.Y') }}
                                                @if($relation->end_date)
                                                    bis {{ $relation->end_date->format('d.m.Y') }}
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
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
                    </div>
                @else
                    <p class="text-sm text-[color:var(--ui-muted)] py-3">Keine Unternehmen verknüpft.</p>
                @endif
            </x-ui-panel>
        @endif

    </x-ui-page-container>

    {{-- Phone Create Modal --}}
    <x-ui-modal size="sm" model="phoneCreateModalShow">
        <x-slot name="header">Telefonnummer hinzufügen</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text name="phoneForm.raw_input" label="Telefonnummer" wire:model.live="phoneForm.raw_input" required placeholder="0151 1234567" :errorKey="'phoneForm.raw_input'" />
                <x-ui-input-select name="phoneForm.country_code" label="Land" :options="$countries" optionValue="code" optionLabel="name" :nullable="false" wire:model.live="phoneForm.country_code" />
            </div>
            <x-ui-input-select name="phoneForm.phone_type_id" label="Telefon-Typ" :options="$phoneTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="phoneForm.phone_type_id" />
            <x-ui-input-checkbox model="phoneForm.is_primary" checked-label="Primäre Telefonnummer" unchecked-label="Als primäre Telefonnummer markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="closePhoneCreateModal">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="savePhone">Hinzufügen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Phone Edit Modal --}}
    <x-ui-modal size="sm" model="phoneEditModalShow">
        <x-slot name="header">Telefonnummer bearbeiten</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text name="phoneForm.raw_input" label="Telefonnummer" wire:model.live="phoneForm.raw_input" required placeholder="0151 1234567" :errorKey="'phoneForm.raw_input'" />
                <x-ui-input-select name="phoneForm.country_code" label="Land" :options="$countries" optionValue="code" optionLabel="name" :nullable="false" wire:model.live="phoneForm.country_code" />
            </div>
            <x-ui-input-select name="phoneForm.phone_type_id" label="Telefon-Typ" :options="$phoneTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="phoneForm.phone_type_id" />
            <x-ui-input-checkbox model="phoneForm.is_primary" checked-label="Primäre Telefonnummer" unchecked-label="Als primäre Telefonnummer markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deletePhoneAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="d-flex gap-2">
                    <x-ui-button type="button" variant="secondary-outline" wire:click="closePhoneEditModal">Abbrechen</x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="savePhone">Speichern</x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Email Create Modal --}}
    <x-ui-modal size="sm" model="emailCreateModalShow">
        <x-slot name="header">E-Mail-Adresse hinzufügen</x-slot>
        <div class="space-y-4">
            <x-ui-input-text name="emailForm.email_address" label="E-Mail-Adresse" wire:model.live="emailForm.email_address" type="email" required placeholder="max.mustermann@example.com" :errorKey="'emailForm.email_address'" />
            <x-ui-input-select name="emailForm.email_type_id" label="E-Mail-Typ" :options="$emailTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="emailForm.email_type_id" />
            <x-ui-input-checkbox model="emailForm.is_primary" checked-label="Primäre E-Mail-Adresse" unchecked-label="Als primäre E-Mail markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="closeEmailCreateModal">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveEmail">Hinzufügen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Email Edit Modal --}}
    <x-ui-modal size="sm" model="emailEditModalShow">
        <x-slot name="header">E-Mail-Adresse bearbeiten</x-slot>
        <div class="space-y-4">
            <x-ui-input-text name="emailForm.email_address" label="E-Mail-Adresse" wire:model.live="emailForm.email_address" type="email" required placeholder="max.mustermann@example.com" :errorKey="'emailForm.email_address'" />
            <x-ui-input-select name="emailForm.email_type_id" label="E-Mail-Typ" :options="$emailTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="emailForm.email_type_id" />
            <x-ui-input-checkbox model="emailForm.is_primary" checked-label="Primäre E-Mail-Adresse" unchecked-label="Als primäre E-Mail markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteEmailAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="d-flex gap-2">
                    <x-ui-button type="button" variant="secondary-outline" wire:click="closeEmailEditModal">Abbrechen</x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveEmail">Speichern</x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Address Create Modal --}}
    <x-ui-modal size="lg" model="addressCreateModalShow">
        <x-slot name="header">Adresse hinzufügen</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text name="addressForm.street" label="Straße" wire:model.live="addressForm.street" required placeholder="Musterstraße" :errorKey="'addressForm.street'" />
                <x-ui-input-text name="addressForm.house_number" label="Hausnummer" wire:model.live="addressForm.house_number" placeholder="123" :errorKey="'addressForm.house_number'" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text name="addressForm.postal_code" label="PLZ" wire:model.live="addressForm.postal_code" required placeholder="12345" :errorKey="'addressForm.postal_code'" />
                <x-ui-input-text name="addressForm.city" label="Stadt" wire:model.live="addressForm.city" required placeholder="Musterstadt" :errorKey="'addressForm.city'" />
            </div>
            <x-ui-input-text name="addressForm.additional_info" label="Zusätzliche Informationen" wire:model.live="addressForm.additional_info" placeholder="Apartment, Etage, etc." :errorKey="'addressForm.additional_info'" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select name="addressForm.country_id" label="Land" :options="$countries" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="addressForm.country_id" required />
                <x-ui-input-select name="addressForm.state_id" label="Bundesland" :options="$states" optionValue="id" optionLabel="name" :nullable="true" wire:model.live="addressForm.state_id" />
            </div>
            <x-ui-input-select name="addressForm.address_type_id" label="Adresstyp" :options="$addressTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="addressForm.address_type_id" />
            <x-ui-input-checkbox model="addressForm.is_primary" checked-label="Primäre Adresse" unchecked-label="Als primäre Adresse markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="closeAddressCreateModal">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveAddress">Hinzufügen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Address Edit Modal --}}
    <x-ui-modal size="lg" model="addressEditModalShow">
        <x-slot name="header">Adresse bearbeiten</x-slot>
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text name="addressForm.street" label="Straße" wire:model.live="addressForm.street" required placeholder="Musterstraße" :errorKey="'addressForm.street'" />
                <x-ui-input-text name="addressForm.house_number" label="Hausnummer" wire:model.live="addressForm.house_number" placeholder="123" :errorKey="'addressForm.house_number'" />
            </div>
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-text name="addressForm.postal_code" label="PLZ" wire:model.live="addressForm.postal_code" required placeholder="12345" :errorKey="'addressForm.postal_code'" />
                <x-ui-input-text name="addressForm.city" label="Stadt" wire:model.live="addressForm.city" required placeholder="Musterstadt" :errorKey="'addressForm.city'" />
            </div>
            <x-ui-input-text name="addressForm.additional_info" label="Zusätzliche Informationen" wire:model.live="addressForm.additional_info" placeholder="Apartment, Etage, etc." :errorKey="'addressForm.additional_info'" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-select name="addressForm.country_id" label="Land" :options="$countries" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="addressForm.country_id" required />
                <x-ui-input-select name="addressForm.state_id" label="Bundesland" :options="$states" optionValue="id" optionLabel="name" :nullable="true" wire:model.live="addressForm.state_id" />
            </div>
            <x-ui-input-select name="addressForm.address_type_id" label="Adresstyp" :options="$addressTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="addressForm.address_type_id" />
            <x-ui-input-checkbox model="addressForm.is_primary" checked-label="Primäre Adresse" unchecked-label="Als primäre Adresse markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteAddressAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="d-flex gap-2">
                    <x-ui-button type="button" variant="secondary-outline" wire:click="closeAddressEditModal">Abbrechen</x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveAddress">Speichern</x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Company Create Modal --}}
    <x-ui-modal size="lg" model="companyCreateModalShow">
        <x-slot name="header">Unternehmen hinzufügen</x-slot>
        <div class="space-y-4">
            <x-ui-input-select name="companyRelationForm.company_id" label="Unternehmen" :options="$this->filteredCompanies" optionValue="id" optionLabel="display_name" :nullable="true" nullLabel="– Unternehmen auswählen –" wire:model.live="companyRelationForm.company_id" required />
            <x-ui-input-select name="companyRelationForm.relation_type_id" label="Beziehungstyp" :options="$relationTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="companyRelationForm.relation_type_id" />
            <x-ui-input-text name="companyRelationForm.position" label="Position" wire:model.live="companyRelationForm.position" placeholder="z.B. Geschäftsführer, Abteilungsleiter" :errorKey="'companyRelationForm.position'" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date name="companyRelationForm.start_date" label="Startdatum" wire:model.live="companyRelationForm.start_date" :nullable="true" :errorKey="'companyRelationForm.start_date'" />
                <x-ui-input-date name="companyRelationForm.end_date" label="Enddatum (optional)" wire:model.live="companyRelationForm.end_date" :nullable="true" :errorKey="'companyRelationForm.end_date'" />
            </div>
            <x-ui-input-textarea name="companyRelationForm.notes" label="Notizen" wire:model.live="companyRelationForm.notes" placeholder="Zusätzliche Informationen zur Beziehung" rows="3" :errorKey="'companyRelationForm.notes'" />
            <x-ui-input-checkbox model="companyRelationForm.is_primary" checked-label="Primäres Unternehmen" unchecked-label="Als primäres Unternehmen markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="closeCompanyCreateModal">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveCompany">Hinzufügen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Company Edit Modal --}}
    <x-ui-modal size="lg" model="companyEditModalShow">
        <x-slot name="header">Unternehmen-Beziehung bearbeiten</x-slot>
        <div class="space-y-4">
            <x-ui-input-select name="companyRelationForm.company_id" label="Unternehmen" :options="$this->filteredCompanies" optionValue="id" optionLabel="display_name" :nullable="true" nullLabel="– Unternehmen auswählen –" wire:model.live="companyRelationForm.company_id" required />
            <x-ui-input-select name="companyRelationForm.relation_type_id" label="Beziehungstyp" :options="$relationTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="companyRelationForm.relation_type_id" />
            <x-ui-input-text name="companyRelationForm.position" label="Position" wire:model.live="companyRelationForm.position" placeholder="z.B. Geschäftsführer, Abteilungsleiter" :errorKey="'companyRelationForm.position'" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date name="companyRelationForm.start_date" label="Startdatum" wire:model.live="companyRelationForm.start_date" :nullable="true" :errorKey="'companyRelationForm.start_date'" />
                <x-ui-input-date name="companyRelationForm.end_date" label="Enddatum (optional)" wire:model.live="companyRelationForm.end_date" :nullable="true" :errorKey="'companyRelationForm.end_date'" />
            </div>
            <x-ui-input-textarea name="companyRelationForm.notes" label="Notizen" wire:model.live="companyRelationForm.notes" placeholder="Zusätzliche Informationen zur Beziehung" rows="3" :errorKey="'companyRelationForm.notes'" />
            <x-ui-input-checkbox model="companyRelationForm.is_primary" checked-label="Primäres Unternehmen" unchecked-label="Als primäres Unternehmen markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteCompanyAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="d-flex gap-2">
                    @if($editingCompanyRelationId)
                        <x-ui-button type="button" variant="secondary-outline" :href="route('crm.companies.show', ['company' => $companyRelationForm['company_id']])" wire:navigate>
                            @svg('heroicon-o-building-office', 'w-4 h-4')
                            Zum Unternehmen
                        </x-ui-button>
                    @endif
                    <x-ui-button type="button" variant="secondary-outline" wire:click="closeCompanyEditModal">Abbrechen</x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveCompany">Speichern</x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

</x-ui-page>
