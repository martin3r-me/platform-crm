<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="CRM Dashboard" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="CRM" width="w-72" defaultOpen="true" storeKey="sidebarOpen" side="left">
            @include('crm::livewire.sidebar')
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6 text-sm text-[var(--ui-muted)]">Keine Aktivitäten verfügbar</div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        

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