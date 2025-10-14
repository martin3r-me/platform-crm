<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="CRM Dashboard" icon="heroicon-o-chart-bar">
            <div class="flex items-center gap-2">
                <div class="flex bg-gray-100 rounded-lg p-1">
                    <button 
                        wire:click="$set('perspective', 'personal')"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective }}' === 'personal' 
                            ? 'bg-[color:var(--ui-success)]/10 text-[color:var(--ui-success)] shadow-sm' 
                            : 'text-[color:var(--ui-muted)] hover:text-[color:var(--ui-secondary)]'"
                    >
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-user', 'w-4 h-4')
                            <span>Persönlich</span>
                        </div>
                    </button>
                    <button 
                        wire:click="$set('perspective', 'team')"
                        class="px-3 py-1.5 rounded-md text-sm font-medium transition"
                        :class="'{{ $perspective }}' === 'team' 
                            ? 'bg-[color:var(--ui-success)]/10 text-[color:var(--ui-success)] shadow-sm' 
                            : 'text-[color:var(--ui-muted)] hover:text-[color:var(--ui-secondary)]'"
                    >
                        <div class="flex items-center gap-2">
                            @svg('heroicon-o-users', 'w-4 h-4')
                            <span>Team</span>
                        </div>
                    </button>
                </div>
                <div class="text-xs text-[color:var(--ui-muted)]">{{ now()->format('l') }}, {{ now()->format('d.m.Y') }}</div>
            </div>
        </x-ui-page-navbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="CRM" width="w-72" defaultOpen="true" storeKey="sidebarOpen" side="left">
            @include('crm::livewire.sidebar')
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <!-- Perspektive-spezifische Statistiken -->
        @if($perspective === 'personal')
        <!-- Persönliche Perspektive -->
        <div class="mb-4">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    @svg('heroicon-o-user', 'w-5 h-5 text-blue-600')
                    <h3 class="text-lg font-semibold text-blue-900">Persönliche Übersicht</h3>
                </div>
                <p class="text-blue-700 text-sm">Deine persönlichen Kontakte und zugewiesenen Unternehmen.</p>
            </div>
        </div>
        @else
        <!-- Team-Perspektive -->
        <div class="mb-4">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center gap-2 mb-2">
                    @svg('heroicon-o-users', 'w-5 h-5 text-green-600')
                    <h3 class="text-lg font-semibold text-green-900">Team-Übersicht</h3>
                </div>
                <p class="text-green-700 text-sm">Alle Kontakte und Unternehmen des Teams.</p>
            </div>
        </div>
        @endif

        <!-- Haupt-Statistiken (4x2 Grid) -->
        <div class="grid grid-cols-4 gap-4 mb-8">
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
        <div class="grid grid-cols-2 gap-6 mb-8">
        <!-- Linke Spalte: Kommunikationsdaten -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Kommunikationsdaten</h3>
            
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
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Qualitätsmetriken</h3>
            
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
    </x-ui-page-container>
</x-ui-page>
    </div>

    <!-- Aktuelle Aktivitäten -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Neueste Kontakte -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Neueste Kontakte</h3>
                <p class="text-sm text-gray-600 mt-1">Die 5 zuletzt erstellten Kontakte</p>
            </div>
            <div class="p-6">
                @if($this->recentContacts->count() > 0)
                    <div class="space-y-4">
                        @foreach($this->recentContacts as $contact)
                            <div class="d-flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="d-flex items-center gap-4">
                                    <div class="w-10 h-10 bg-primary text-on-primary rounded-lg d-flex items-center justify-center">
                                        <x-heroicon-o-user class="w-5 h-5"/>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $contact->full_name }}</h4>
                                        <p class="text-sm text-gray-600">{{ $contact->created_at->format('d.m.Y H:i') }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('crm.contacts.show', ['contact' => $contact->id]) }}" 
                                   class="inline-flex items-center gap-2 px-3 py-2 bg-primary text-on-primary rounded-md hover:bg-primary-dark transition text-sm"
                                   wire:navigate>
                                    <div class="d-flex items-center gap-2">
                                        @svg('heroicon-o-arrow-right', 'w-4 h-4')
                                        <span>Anzeigen</span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-user class="w-12 h-12 text-gray-400 mx-auto mb-4"/>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Keine Kontakte</h4>
                        <p class="text-gray-600">Es wurden noch keine Kontakte erstellt.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Neueste Unternehmen -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Neueste Unternehmen</h3>
                <p class="text-sm text-gray-600 mt-1">Die 5 zuletzt erstellten Unternehmen</p>
            </div>
            <div class="p-6">
                @if($this->recentCompanies->count() > 0)
                    <div class="space-y-4">
                        @foreach($this->recentCompanies as $company)
                            <div class="d-flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="d-flex items-center gap-4">
                                    <div class="w-10 h-10 bg-secondary text-on-secondary rounded-lg d-flex items-center justify-center">
                                        <x-heroicon-o-building-office class="w-5 h-5"/>
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $company->display_name }}</h4>
                                        <p class="text-sm text-gray-600">{{ $company->created_at->format('d.m.Y H:i') }}</p>
                                    </div>
                                </div>
                                <a href="{{ route('crm.companies.show', ['company' => $company->id]) }}" 
                                   class="inline-flex items-center gap-2 px-3 py-2 bg-secondary text-on-secondary rounded-md hover:bg-secondary-dark transition text-sm"
                                   wire:navigate>
                                    <div class="d-flex items-center gap-2">
                                        @svg('heroicon-o-arrow-right', 'w-4 h-4')
                                        <span>Anzeigen</span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-heroicon-o-building-office class="w-12 h-12 text-gray-400 mx-auto mb-4"/>
                        <h4 class="text-lg font-medium text-gray-900 mb-2">Keine Unternehmen</h4>
                        <p class="text-gray-600">Es wurden noch keine Unternehmen erstellt.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Kontakt-Status -->
    @if($this->topContactStatuses->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Top Kontakt-Status</h3>
                <p class="text-sm text-gray-600 mt-1">Verteilung der Kontakte nach Status</p>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($this->topContactStatuses as $status)
                        <div class="d-flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="d-flex items-center gap-4">
                                <div class="w-10 h-10 bg-primary text-on-primary rounded-lg d-flex items-center justify-center">
                                    <x-heroicon-o-user-group class="w-5 h-5"/>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $status->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $status->count }} Kontakte</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-primary">{{ $status->count }}</div>
                                <div class="text-sm text-gray-600">
                                    {{ round(($status->count / $this->totalContacts) * 100, 1) }}%
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>