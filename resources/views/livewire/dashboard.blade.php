<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="CRM Dashboard" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary" size="sm" :href="route('crm.contacts.index')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-user','w-4 h-4')
                                Kontakte
                            </span>
                        </x-ui-button>
                        <x-ui-button variant="secondary" size="sm" :href="route('crm.companies.index')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-building-office','w-4 h-4')
                                Unternehmen
                            </span>
                        </x-ui-button>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Statistiken</h3>
                    <div class="space-y-3">
                        <div class="py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 flex items-center justify-between">
                            <span class="text-xs text-[var(--ui-muted)]">Gesamt Kontakte</span>
                            <span class="text-lg font-bold text-[var(--ui-secondary)]">{{ $this->totalContacts }}</span>
                        </div>
                        <div class="py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 flex items-center justify-between">
                            <span class="text-xs text-[var(--ui-muted)]">Gesamt Unternehmen</span>
                            <span class="text-lg font-bold text-[var(--ui-secondary)]">{{ $this->totalCompanies }}</span>
                        </div>
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
        

        <!-- Haupt-Statistiken (4x2 Grid) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Kontakte -->
        <x-ui-dashboard-tile
            title="Kontakte"
            :count="$this->totalContacts"
            icon="user"
            variant="primary"
            size="lg"
            :href="route('crm.contacts.index')"
        />
        
        <!-- Unternehmen -->
        <x-ui-dashboard-tile
            title="Unternehmen"
            :count="$this->totalCompanies"
            icon="building-office"
            variant="secondary"
            size="lg"
            :href="route('crm.companies.index')"
        />
        
        <!-- E-Mail Adressen -->
        <x-ui-dashboard-tile
            title="E-Mail Adressen"
            :count="$this->totalEmailAddresses"
            icon="envelope"
            variant="success"
            size="lg"
        />
        
        <!-- Telefonnummern -->
        <x-ui-dashboard-tile
            title="Telefonnummern"
            :count="$this->totalPhoneNumbers"
            icon="phone"
            variant="warning"
            size="lg"
        />
        </div>

    <!-- Detaillierte Statistiken (2x3 Grid) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Linke Spalte: Kommunikationsdaten -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Kommunikationsdaten</h3>
            
            <div class="grid grid-cols-2 gap-3">
                <x-ui-dashboard-tile
                    title="Primäre E-Mails"
                    :count="$this->primaryEmailAddresses"
                    icon="envelope"
                    variant="success"
                    size="sm"
                />
                
                <x-ui-dashboard-tile
                    title="Primäre Telefone"
                    :count="$this->primaryPhoneNumbers"
                    icon="phone"
                    variant="warning"
                    size="sm"
                />
                
                <x-ui-dashboard-tile
                    title="Adressen"
                    :count="$this->totalPostalAddresses"
                    icon="map-pin"
                    variant="info"
                    size="sm"
                />
                
                <x-ui-dashboard-tile
                    title="Beziehungen"
                    :count="$this->totalRelations"
                    icon="link"
                    variant="danger"
                    size="sm"
                />
            </div>
        </div>

        <!-- Rechte Spalte: Qualitätsmetriken -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Qualitätsmetriken</h3>
            
            <div class="grid grid-cols-2 gap-3">
                <x-ui-dashboard-tile
                    title="Kontakte ohne E-Mail"
                    :count="$this->contactsWithoutEmail"
                    icon="exclamation-triangle"
                    variant="warning"
                    size="sm"
                />
                
                <x-ui-dashboard-tile
                    title="Kontakte ohne Telefon"
                    :count="$this->contactsWithoutPhone"
                    icon="exclamation-triangle"
                    variant="warning"
                    size="sm"
                />
                
                <x-ui-dashboard-tile
                    title="Unternehmen ohne E-Mail"
                    :count="$this->companiesWithoutEmail"
                    icon="exclamation-triangle"
                    variant="warning"
                    size="sm"
                />
                
                <x-ui-dashboard-tile
                    title="Unternehmen ohne Telefon"
                    :count="$this->companiesWithoutPhone"
                    icon="exclamation-triangle"
                    variant="warning"
                    size="sm"
                />
            </div>
        </div>
        </div>
        {{-- Zuletzt aktualisiert (Kontakte & Unternehmen) im Planner-Stil --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui-panel title="Zuletzt aktualisierte Kontakte" subtitle="Top 5" >
                <div class="space-y-2">
                    @forelse(($recentContacts ?? collect())->take(5) as $contact)
                        <a href="{{ route('crm.contacts.show', ['contact' => $contact->id]) }}" wire:navigate
                           class="group flex items-center justify-between p-3 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-primary-5)] transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60 flex items-center justify-center text-xs font-semibold text-[var(--ui-secondary)]">
                                    {{ strtoupper(substr($contact->full_name ?? $contact->name ?? 'K', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $contact->full_name ?? $contact->name }}</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Aktualisiert: {{ optional($contact->updated_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                            @svg('heroicon-o-arrow-right', 'w-4 h-4 text-[var(--ui-muted)] group-hover:text-[var(--ui-primary)]')
                        </a>
                    @empty
                        <div class="text-sm text-[var(--ui-muted)] p-4 text-center">Keine Kontakte vorhanden.</div>
                    @endforelse
                </div>
            </x-ui-panel>

            <x-ui-panel title="Zuletzt aktualisierte Unternehmen" subtitle="Top 5" >
                <div class="space-y-2">
                    @forelse(($recentCompanies ?? collect())->take(5) as $company)
                        <a href="{{ route('crm.companies.show', ['company' => $company->id]) }}" wire:navigate
                           class="group flex items-center justify-between p-3 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-primary-5)] transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-md bg-[var(--ui-primary-5)] border border-[var(--ui-border)]/60 flex items-center justify-center text-xs font-semibold text-[var(--ui-primary)]">
                                    @svg('heroicon-o-building-office','w-4 h-4')
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $company->display_name ?? $company->name }}</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Aktualisiert: {{ optional($company->updated_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                            @svg('heroicon-o-arrow-right', 'w-4 h-4 text-[var(--ui-muted)] group-hover:text-[var(--ui-primary)]')
                        </a>
                    @empty
                        <div class="text-sm text-[var(--ui-muted)] p-4 text-center">Keine Unternehmen vorhanden.</div>
                    @endforelse
                </div>
            </x-ui-panel>
        </div>

    </x-ui-page-container>
</x-ui-page>