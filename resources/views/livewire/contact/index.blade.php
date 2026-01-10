<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Kontakte" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-input-text
                            name="searchLastName"
                            placeholder="Nachname suchen..."
                            class="w-full"
                            size="sm"
                            wire:model.live.debounce.300ms="searchLastName"
                        />
                        <x-ui-button variant="secondary" size="sm" wire:click="openCreateModal" class="w-full justify-start">
                            @svg('heroicon-o-plus','w-4 h-4')
                            <span class="ml-2">Neuer Kontakt</span>
                        </x-ui-button>
                    </div>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Filter</h3>
                    <div class="space-y-3">
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
        @if($contacts->count() === 0)
            <div class="rounded-lg border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-6 text-sm text-[color:var(--ui-muted)]">
                Keine Kontakte gefunden.
            </div>
        @else
        <x-ui-table compact="true">
        <x-ui-table-header>
            <x-ui-table-header-cell compact="true" sortable="true" sortField="last_name" :currentSort="$sortField" :sortDirection="$sortDirection">Name</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Primäre Kontaktdaten</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true">Unternehmen</x-ui-table-header-cell>
            <x-ui-table-header-cell compact="true" sortable="true" sortField="contact_status_id" :currentSort="$sortField" :sortDirection="$sortDirection">Status</x-ui-table-header-cell>
        </x-ui-table-header>
        
        <x-ui-table-body>
            @foreach($contacts as $contact)
                <x-ui-table-row 
                    compact="true"
                    clickable="true" 
                    :href="route('crm.contacts.show', ['contact' => $contact->id])"
                >
                    <x-ui-table-cell compact="true">
                        <div class="font-medium">{{ $contact->last_name }}, {{ $contact->first_name }}</div>
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
        @endif
    </x-ui-page-container>

    <!-- Create Contact Modal -->
    <x-ui-modal
        wire:model="modalShow"
        size="lg"
    >
        <x-slot name="header">
            Kontakt anlegen
        </x-slot>

        <div class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="d-flex items-center gap-2 mb-2">
                    @svg('heroicon-o-information-circle', 'w-5 h-5 text-blue-600')
                    <h4 class="font-medium text-blue-900">Hinweis</h4>
                </div>
                <p class="text-blue-700 text-sm">Rufnummern, Adressen und E-Mail-Adressen können nach dem Anlegen des Kontakts in der Kontakt-Detailansicht gepflegt werden.</p>
            </div>

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
                        :nullable="true"
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
                <x-ui-button type="button" variant="primary" wire:click="createContact">
                    Kontakt anlegen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>