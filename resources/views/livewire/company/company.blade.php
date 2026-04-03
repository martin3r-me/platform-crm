<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Unternehmen', 'href' => route('crm.companies.index')],
            ['label' => $company->display_name],
        ]">
            <div class="flex items-center gap-2">
                <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />

                {{-- Prev/Next Navigation --}}
                @if($prevCompanyId || $nextCompanyId)
                    <div class="flex items-center gap-1">
                        @if($prevCompanyId)
                            <a href="{{ route('crm.companies.show', $prevCompanyId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] hover:bg-[color:var(--ui-muted-5)] transition">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] opacity-30">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </span>
                        @endif
                        @if($nextCompanyId)
                            <a href="{{ route('crm.companies.show', $nextCompanyId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] hover:bg-[color:var(--ui-muted-5)] transition">
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
                        <div class="font-semibold text-[color:var(--ui-secondary)]">{{ $company->display_name }}</div>
                        @if($company->legalForm)
                            <div class="text-[color:var(--ui-muted)]">{{ $company->legalForm->name }}</div>
                        @endif
                        @if($company->website)
                            <div class="text-[color:var(--ui-muted)]">
                                @svg('heroicon-o-globe-alt', 'w-3.5 h-3.5 inline')
                                <a href="{{ $company->website }}" target="_blank" class="underline">{{ $company->website }}</a>
                            </div>
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

                {{-- Quick-Links --}}
                <div class="space-y-1">
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition {{ $activeTab === 'kontaktdaten' ? 'bg-[color:var(--ui-muted-5)] font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-phone', 'w-4 h-4 text-[color:var(--ui-muted)]')
                            Telefonnummern
                        </span>
                        <x-ui-badge variant="secondary" size="xs">{{ $company->phoneNumbers->count() }}</x-ui-badge>
                    </button>
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition {{ $activeTab === 'kontaktdaten' ? 'bg-[color:var(--ui-muted-5)] font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-envelope', 'w-4 h-4 text-[color:var(--ui-muted)]')
                            E-Mail-Adressen
                        </span>
                        <x-ui-badge variant="secondary" size="xs">{{ $company->emailAddresses->count() }}</x-ui-badge>
                    </button>
                    <button wire:click="$set('activeTab', 'kontaktdaten')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition {{ $activeTab === 'kontaktdaten' ? 'bg-[color:var(--ui-muted-5)] font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-map-pin', 'w-4 h-4 text-[color:var(--ui-muted)]')
                            Adressen
                        </span>
                        <x-ui-badge variant="secondary" size="xs">{{ $company->postalAddresses->count() }}</x-ui-badge>
                    </button>
                    <button wire:click="$set('activeTab', 'kontakte')" class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition {{ $activeTab === 'kontakte' ? 'bg-[color:var(--ui-muted-5)] font-medium' : '' }}">
                        <span class="flex items-center gap-2">
                            @svg('heroicon-o-users', 'w-4 h-4 text-[color:var(--ui-muted)]')
                            Kontakte
                        </span>
                        <x-ui-badge variant="secondary" size="xs">{{ $company->contactRelations->count() }}</x-ui-badge>
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
                    @forelse($company->activities as $activity)
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
                                    <p class="text-sm text-[color:var(--ui-muted)]">Unternehmen erstellt</p>
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
                <button wire:click="$set('activeTab', 'kontakte')" class="py-3 px-1 border-b-2 text-sm font-medium transition {{ $activeTab === 'kontakte' ? 'border-[color:var(--ui-primary)] text-[color:var(--ui-primary)]' : 'border-transparent text-[color:var(--ui-muted)] hover:text-[color:var(--ui-secondary)] hover:border-[color:var(--ui-border)]' }}">
                    Kontakte
                </button>
            </nav>
        </div>

        {{-- Tab: Stammdaten --}}
        @if($activeTab === 'stammdaten')
            <div class="space-y-6">
                {{-- Unternehmensdaten --}}
                <x-ui-panel title="Unternehmensdaten">
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
                </x-ui-panel>

                {{-- Rechtliche Informationen --}}
                <x-ui-panel title="Rechtliche Informationen">
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
                </x-ui-panel>

                {{-- Beschreibung & Notizen --}}
                <x-ui-panel title="Beschreibung & Notizen">
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
                        @if($company->phoneNumbers->count() > 0)
                            <div class="space-y-2">
                                @foreach($company->phoneNumbers as $phone)
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
                        @if($company->emailAddresses->count() > 0)
                            <div class="space-y-2">
                                @foreach($company->emailAddresses as $email)
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
                    @if($company->postalAddresses->count() > 0)
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                            @foreach($company->postalAddresses as $address)
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

        {{-- Tab: Kontakte --}}
        @if($activeTab === 'kontakte')
            <x-ui-panel>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)]">Kontakte</h3>
                    <x-ui-button size="xs" variant="secondary-outline" wire:click="addContact">
                        @svg('heroicon-o-plus', 'w-3.5 h-3.5')
                        Hinzufügen
                    </x-ui-button>
                </div>
                @if($company->contactRelations->count() > 0)
                    <div class="space-y-2">
                        @foreach($company->contactRelations as $relation)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-[color:var(--ui-border)] hover:border-[color:var(--ui-primary-20)] cursor-pointer transition" wire:click="editContact({{ $relation->id }})">
                                <div class="flex items-center gap-3 min-w-0">
                                    @svg('heroicon-o-user', 'w-4 h-4 text-[color:var(--ui-muted)] flex-shrink-0')
                                    <div class="min-w-0">
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
                    <p class="text-sm text-[color:var(--ui-muted)] py-3">Keine Kontakte verknüpft.</p>
                @endif
            </x-ui-panel>
        @endif

    </x-ui-page-container>

    <!-- Phone Create Modal -->
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

    <!-- Phone Edit Modal -->
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

    <!-- E-Mail Create Modal -->
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

    <!-- E-Mail Edit Modal -->
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

    <!-- Address Create Modal -->
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

    <!-- Address Edit Modal -->
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

    <!-- Contact Create Modal -->
    <x-ui-modal size="lg" model="contactCreateModalShow">
        <x-slot name="header">Kontakt hinzufügen</x-slot>
        <div class="space-y-4">
            <x-ui-input-select name="contactRelationForm.contact_id" label="Kontakt" :options="$this->filteredContacts" optionValue="id" optionLabel="full_name" :nullable="true" nullLabel="– Kontakt auswählen –" wire:model.live="contactRelationForm.contact_id" required />
            <x-ui-input-select name="contactRelationForm.relation_type_id" label="Beziehungstyp" :options="$relationTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="contactRelationForm.relation_type_id" />
            <x-ui-input-text name="contactRelationForm.position" label="Position" wire:model.live="contactRelationForm.position" placeholder="z.B. Geschäftsführer, Abteilungsleiter" :errorKey="'contactRelationForm.position'" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date name="contactRelationForm.start_date" label="Startdatum" wire:model.live="contactRelationForm.start_date" :nullable="true" :errorKey="'contactRelationForm.start_date'" />
                <x-ui-input-date name="contactRelationForm.end_date" label="Enddatum (optional)" wire:model.live="contactRelationForm.end_date" :nullable="true" :errorKey="'contactRelationForm.end_date'" />
            </div>
            <x-ui-input-textarea name="contactRelationForm.notes" label="Notizen" wire:model.live="contactRelationForm.notes" placeholder="Zusätzliche Informationen zur Beziehung" rows="3" :errorKey="'contactRelationForm.notes'" />
            <x-ui-input-checkbox model="contactRelationForm.is_primary" checked-label="Primärer Kontakt" unchecked-label="Als primären Kontakt markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="closeContactCreateModal">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="saveContact">Hinzufügen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    <!-- Contact Edit Modal -->
    <x-ui-modal size="lg" model="contactEditModalShow">
        <x-slot name="header">Kontakt-Beziehung bearbeiten</x-slot>
        <div class="space-y-4">
            <x-ui-input-select name="contactRelationForm.contact_id" label="Kontakt" :options="$this->filteredContacts" optionValue="id" optionLabel="full_name" :nullable="true" nullLabel="– Kontakt auswählen –" wire:model.live="contactRelationForm.contact_id" required />
            <x-ui-input-select name="contactRelationForm.relation_type_id" label="Beziehungstyp" :options="$relationTypes" optionValue="id" optionLabel="name" :nullable="false" wire:model.live="contactRelationForm.relation_type_id" />
            <x-ui-input-text name="contactRelationForm.position" label="Position" wire:model.live="contactRelationForm.position" placeholder="z.B. Geschäftsführer, Abteilungsleiter" :errorKey="'contactRelationForm.position'" />
            <div class="grid grid-cols-2 gap-4">
                <x-ui-input-date name="contactRelationForm.start_date" label="Startdatum" wire:model.live="contactRelationForm.start_date" :nullable="true" :errorKey="'contactRelationForm.start_date'" />
                <x-ui-input-date name="contactRelationForm.end_date" label="Enddatum (optional)" wire:model.live="contactRelationForm.end_date" :nullable="true" :errorKey="'contactRelationForm.end_date'" />
            </div>
            <x-ui-input-textarea name="contactRelationForm.notes" label="Notizen" wire:model.live="contactRelationForm.notes" placeholder="Zusätzliche Informationen zur Beziehung" rows="3" :errorKey="'contactRelationForm.notes'" />
            <x-ui-input-checkbox model="contactRelationForm.is_primary" checked-label="Primärer Kontakt" unchecked-label="Als primären Kontakt markieren" size="md" block="true" />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-between items-center gap-4">
                <x-ui-confirm-button action="deleteContactAndCloseModal" text="Löschen" confirmText="Wirklich löschen?" variant="danger-outline" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />
                <div class="d-flex gap-2">
                    @if($editingContactRelationId)
                        <x-ui-button type="button" variant="secondary-outline" :href="route('crm.contacts.show', ['contact' => $contactRelationForm['contact_id']])" wire:navigate>
                            @svg('heroicon-o-user', 'w-4 h-4')
                            Zum Kontakt
                        </x-ui-button>
                    @endif
                    <x-ui-button type="button" variant="secondary-outline" wire:click="closeContactEditModal">Abbrechen</x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="saveContact">Speichern</x-ui-button>
                </div>
            </div>
        </x-slot>
    </x-ui-modal>

</x-ui-page>
