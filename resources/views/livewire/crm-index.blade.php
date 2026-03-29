<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'icon' => 'users'],
        ]">
            <x-slot name="left">
                <x-ui-tab
                    :tabs="[
                        ['value' => 'contacts', 'label' => 'Kontakte', 'count' => $this->contactCount],
                        ['value' => 'companies', 'label' => 'Unternehmen', 'count' => $this->companyCount],
                    ]"
                    model="activeTab"
                    :showCounts="true"
                />
            </x-slot>

            {{-- Search + Bulk Actions / Create Button --}}
            <div class="flex items-center gap-2">
                {{-- Search field with Cmd+K --}}
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
                    {{-- Bulk Actions --}}
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
                    {{-- Create Button --}}
                    @if($activeTab === 'contacts')
                        <x-ui-button variant="primary" size="sm" wire:click="openCreateModal('contact')">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Neuer Kontakt</span>
                        </x-ui-button>
                    @else
                        <x-ui-button variant="primary" size="sm" wire:click="openCreateModal('company')">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                            <span>Neues Unternehmen</span>
                        </x-ui-button>
                    @endif
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-ui-page-container>
        {{-- Filter Chips --}}
        <div class="flex items-center gap-2 mb-4">
            <x-ui-input-select
                name="statusFilter"
                :options="$contactStatuses"
                optionValue="id"
                optionLabel="name"
                :nullable="true"
                nullLabel="Alle Status"
                size="sm"
                wire:model.live="statusFilter"
            />
            @if($activeTab === 'contacts')
                <x-ui-input-select
                    name="blacklistFilter"
                    :options="collect([
                        ['value' => 'all', 'label' => 'Alle'],
                        ['value' => 'not_blacklisted', 'label' => 'Nicht blacklisted'],
                        ['value' => 'blacklisted', 'label' => 'Nur blacklisted'],
                    ])"
                    optionValue="value"
                    optionLabel="label"
                    :nullable="false"
                    size="sm"
                    wire:model.live="blacklistFilter"
                />
            @endif
        </div>

        {{-- Contacts Table --}}
        <div x-show="$wire.activeTab === 'contacts'" x-cloak>
            @if($activeTab === 'contacts')
                @if($this->contacts->count() === 0)
                    <div class="rounded-lg border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] p-6 text-sm text-[color:var(--ui-muted)]">
                        Keine Kontakte gefunden.
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
                                <x-ui-table-header-cell compact="true" sortable="true" sortField="last_name" :currentSort="$sortField" :sortDirection="$sortDirection">Name</x-ui-table-header-cell>
                                <x-ui-table-header-cell compact="true">Primäre Kontaktdaten</x-ui-table-header-cell>
                                <x-ui-table-header-cell compact="true">Unternehmen</x-ui-table-header-cell>
                                <x-ui-table-header-cell compact="true" sortable="true" sortField="contact_status_id" :currentSort="$sortField" :sortDirection="$sortDirection">Status</x-ui-table-header-cell>
                            </x-ui-table-header>

                            <x-ui-table-body>
                                @foreach($this->contacts as $contact)
                                    <x-ui-table-row
                                        compact="true"
                                        clickable="true"
                                        :href="route('crm.contacts.show', ['contact' => $contact->id])"
                                        :data-href="route('crm.contacts.show', ['contact' => $contact->id])"
                                        :class="'transition'"
                                    >
                                        <x-ui-table-cell compact="true" @click.stop>
                                            <input type="checkbox" value="{{ $contact->id }}" wire:model.live="selected"
                                                   class="rounded border-[var(--ui-border)] text-[var(--ui-primary)]" />
                                        </x-ui-table-cell>
                                        <x-ui-table-cell compact="true">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">{{ $contact->last_name }}, {{ $contact->first_name }}</span>
                                                @if($contact->is_blacklisted)
                                                    <x-ui-badge variant="danger" size="xs">
                                                        @svg('heroicon-s-no-symbol', 'w-3 h-3')
                                                        Blacklisted
                                                    </x-ui-badge>
                                                @endif
                                            </div>
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
                    </div>

                    {{-- Infinite Scroll Sentinel --}}
                    @if($this->contacts->count() >= $perPage * $page)
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
            @endif
        </div>

        {{-- Companies Table --}}
        <div x-show="$wire.activeTab === 'companies'" x-cloak>
            @if($activeTab === 'companies')
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
                                            <x-ui-badge variant="secondary" size="sm">
                                                {{ $company->contactStatus->name }}
                                            </x-ui-badge>
                                        </x-ui-table-cell>
                                    </x-ui-table-row>
                                @endforeach
                            </x-ui-table-body>
                        </x-ui-table>
                    </div>

                    {{-- Infinite Scroll Sentinel --}}
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
            @endif
        </div>
    </x-ui-page-container>

    {{-- Create Contact Modal --}}
    @if($createType === 'contact')
        <x-ui-modal wire:model="modalShow" size="lg">
            <x-slot name="header">Kontakt anlegen</x-slot>

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
                        <x-ui-input-text name="first_name" label="Vorname" wire:model.live="first_name" required placeholder="Vorname eingeben" />
                        <x-ui-input-text name="last_name" label="Nachname" wire:model.live="last_name" required placeholder="Nachname eingeben" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-text name="middle_name" label="Zweiter Vorname" wire:model.live="middle_name" placeholder="Zweiter Vorname (optional)" />
                        <x-ui-input-text name="nickname" label="Spitzname" wire:model.live="nickname" placeholder="Spitzname (optional)" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-date name="birth_date" label="Geburtsdatum" wire:model.live="birth_date" placeholder="Geburtsdatum (optional)" :nullable="true" />
                        <x-ui-input-select name="contact_status_id" label="Status" :options="$contactStatuses" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Status auswählen –" wire:model.live="contact_status_id" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-select name="salutation_id" label="Anrede" :options="$salutations" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Anrede auswählen –" wire:model.live="salutation_id" />
                        <x-ui-input-select name="academic_title_id" label="Akademischer Titel" :options="$academicTitles" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Titel auswählen –" wire:model.live="academic_title_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-select name="gender_id" label="Geschlecht" :options="$genders" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Geschlecht auswählen –" wire:model.live="gender_id" />
                        <x-ui-input-select name="language_id" label="Sprache" :options="$languages" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Sprache auswählen –" wire:model.live="language_id" />
                    </div>
                    <x-ui-input-textarea name="notes" label="Notizen" wire:model.live="notes" placeholder="Zusätzliche Notizen (optional)" rows="3" />
                </form>
            </div>

            <x-slot name="footer">
                <div class="d-flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">Abbrechen</x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="createContact">Kontakt anlegen</x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>
    @endif

    {{-- Create Company Modal --}}
    @if($createType === 'company')
        <x-ui-modal wire:model="modalShow" size="lg">
            <x-slot name="header">Neues Unternehmen anlegen</x-slot>

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
                        <x-ui-input-text name="company_name" label="Firmenname" wire:model.live="company_name" required placeholder="Firmenname eingeben" />
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
                        <x-ui-input-select name="company_contact_status_id" label="Status" :options="$contactStatuses" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Status auswählen –" wire:model.live="company_contact_status_id" required />
                        <x-ui-input-select name="country_id" label="Land" :options="$countries" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Land auswählen –" wire:model.live="country_id" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-select name="industry_id" label="Branche" :options="$industries" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Branche auswählen –" wire:model.live="industry_id" />
                        <x-ui-input-select name="legal_form_id" label="Rechtsform" :options="$legalForms" optionValue="id" optionLabel="name" :nullable="true" nullLabel="– Rechtsform auswählen –" wire:model.live="legal_form_id" />
                    </div>
                    <x-ui-input-textarea name="description" label="Beschreibung" wire:model.live="description" placeholder="Kurze Beschreibung des Unternehmens (optional)" rows="3" />
                    <x-ui-input-textarea name="company_notes" label="Notizen" wire:model.live="company_notes" placeholder="Interne Notizen (optional)" rows="3" />
                </form>
            </div>

            <x-slot name="footer">
                <div class="d-flex justify-end gap-2">
                    <x-ui-button type="button" variant="secondary-outline" @click="$wire.closeCreateModal()">Abbrechen</x-ui-button>
                    <x-ui-button type="button" variant="primary" wire:click="createCompany">Unternehmen anlegen</x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>
    @endif
</x-ui-page>
