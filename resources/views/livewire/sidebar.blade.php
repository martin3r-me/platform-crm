{{-- resources/views/vendor/crm/livewire/sidebar-content.blade.php --}}
<div>
    {{-- Modul Header --}}
    <x-sidebar-module-header module-name="CRM" />
    
    {{-- Abschnitt: Allgemein --}}
    <div>
        <h4 x-show="!collapsed" class="px-4 py-3 text-xs tracking-wide font-semibold text-[color:var(--ui-muted)] uppercase">Allgemein</h4>

        {{-- Dashboard --}}
        <a href="{{ route('crm.dashboard') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname === '/' || 
               window.location.pathname.endsWith('/crm') || 
               window.location.pathname.endsWith('/crm/') ||
               (window.location.pathname.split('/').length === 1 && window.location.pathname === '/')
                   ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                   : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-chart-bar class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Dashboard</span>
        </a>

        {{-- Kontakte --}}
        <a href="{{ route('crm.contacts.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/contacts') || 
               window.location.pathname.endsWith('/contacts') ||
               window.location.pathname.endsWith('/contacts/')
                   ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                   : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-user-group class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Kontakte</span>
        </a>

        {{-- Unternehmen --}}
        <a href="{{ route('crm.companies.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/companies') || 
               window.location.pathname.endsWith('/companies') ||
               window.location.pathname.endsWith('/companies/')
                   ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                   : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-building-office class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Unternehmen</span>
        </a>
    </div>

    {{-- Abschnitt: Schnellzugriff --}}
    <div x-show="!collapsed">
        <h4 class="px-4 py-3 text-xs tracking-wide font-semibold text-[color:var(--ui-muted)] uppercase">Schnellzugriff</h4>

        {{-- Neueste Kontakte --}}
        @foreach($recentContacts ?? [] as $contact)
            <a href="{{ route('crm.contacts.show', ['contact' => $contact]) }}"
               class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition gap-3"
               :class="[
                   window.location.pathname.includes('/contacts/{{ $contact->id }}/') || 
                   window.location.pathname.endsWith('/contacts/{{ $contact->id }}') ||
                   (window.location.pathname.split('/').length === 2 && window.location.pathname.endsWith('/{{ $contact->id }}'))
                       ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                       : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]'
               ]"
               wire:navigate>
                <x-heroicon-o-user class="w-6 h-6 flex-shrink-0"/>
                <span class="truncate">{{ $contact->full_name }}</span>
            </a>
        @endforeach

        {{-- Neueste Unternehmen --}}
        @foreach($recentCompanies ?? [] as $company)
            <a href="{{ route('crm.companies.show', ['company' => $company]) }}"
               class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition gap-3"
               :class="[
                   window.location.pathname.includes('/companies/{{ $company->id }}/') || 
                   window.location.pathname.endsWith('/companies/{{ $company->id }}') ||
                   (window.location.pathname.split('/').length === 2 && window.location.pathname.endsWith('/{{ $company->id }}'))
                       ? 'bg-[color:var(--ui-primary)] text-[color:var(--ui-on-primary)] shadow'
                       : 'text-[color:var(--ui-secondary)] hover:bg-[color:var(--ui-primary-5)] hover:text-[color:var(--ui-primary)]'
               ]"
               wire:navigate>
                <x-heroicon-o-building-office class="w-6 h-6 flex-shrink-0"/>
                <span class="truncate">{{ $company->display_name }}</span>
            </a>
        @endforeach
    </div>

    {{-- Company-spezifische Inhalte --}}
    @if(isset($company))
        <div x-show="!collapsed">
            <h4 class="px-4 py-3 text-xs tracking-wide font-semibold text-[color:var(--ui-muted)] uppercase">Unternehmens-Einstellungen</h4>

            {{-- Navigation Button --}}
            <div class="px-3 mb-4">
                <a href="{{ route('crm.companies.index') }}" 
                   class="flex items-center gap-2 px-3 py-2 text-sm text-[color:var(--ui-secondary)] hover:text-[color:var(--ui-primary)] hover:bg-[color:var(--ui-primary-5)] rounded transition"
                   wire:navigate>
                    @svg('heroicon-o-arrow-left', 'w-4 h-4')
                    Zurück zu Unternehmen
                </a>
            </div>

            {{-- Kurze Übersicht --}}
            <div class="px-3 mb-4">
                <div class="p-3 bg-[color:var(--ui-muted-5)] rounded-lg">
                    <h4 class="font-semibold mb-2 text-[color:var(--ui-secondary)] text-sm">Übersicht</h4>
                    <div class="space-y-1 text-xs">
                        <div><strong>Name:</strong> {{ $company->display_name }}</div>
                        @if($company->legal_name)
                            <div><strong>Rechtlich:</strong> {{ $company->legal_name }}</div>
                        @endif
                        @if($company->trading_name)
                            <div><strong>Handelsname:</strong> {{ $company->trading_name }}</div>
                        @endif
                        @if($company->website)
                            <div><strong>Website:</strong> <a href="{{ $company->website }}" target="_blank" class="underline text-[color:var(--ui-primary)]">{{ $company->website }}</a></div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="px-3 mb-4">
                <label class="block text-xs font-medium text-[color:var(--ui-secondary)] mb-1">Status</label>
                <select wire:model.live="company.contact_status_id" 
                        class="w-full text-xs px-2 py-1 border border-[color:var(--ui-border)] rounded bg-[color:var(--ui-surface)] text-[color:var(--ui-body-color)]">
                    <option value="">– Status auswählen –</option>
                    @foreach($contactStatuses ?? [] as $status)
                        <option value="{{ $status->id }}">{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Telefonnummern --}}
            <div class="px-3 mb-4">
                <h4 class="font-semibold mb-2 text-[color:var(--ui-secondary)] text-sm">Telefonnummern</h4>
                <div class="space-y-1">
                    @foreach($company->phoneNumbers ?? [] as $phone)
                        <div class="flex items-center gap-1 p-1 bg-[color:var(--ui-muted-5)] rounded text-xs cursor-pointer" wire:click="editPhone({{ $phone->id }})">
                            <span class="flex-grow truncate">{{ $phone->raw_input }}</span>
                            <div class="flex gap-1">
                                @if($phone->is_primary)
                                    <span class="px-1 py-0.5 bg-green-100 text-green-800 rounded text-xs">Primär</span>
                                @endif
                                <span class="px-1 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">{{ $phone->phoneType->name }}</span>
                            </div>
                        </div>
                    @endforeach
                    @if(($company->phoneNumbers ?? collect())->count() === 0)
                        <p class="text-xs text-[color:var(--ui-muted)]">Noch keine Telefonnummern.</p>
                    @endif
                    <button wire:click="addPhone" class="w-full text-xs px-2 py-1 border border-[color:var(--ui-border)] rounded hover:bg-[color:var(--ui-primary-5)] transition">
                        @svg('heroicon-o-plus', 'w-3 h-3 inline mr-1')
                        Telefonnummer hinzufügen
                    </button>
                </div>
            </div>

            {{-- E-Mail-Adressen --}}
            <div class="px-3 mb-4">
                <h4 class="font-semibold mb-2 text-[color:var(--ui-secondary)] text-sm">E-Mail-Adressen</h4>
                <div class="space-y-1">
                    @foreach($company->emailAddresses ?? [] as $email)
                        <div class="flex items-center gap-1 p-1 bg-[color:var(--ui-muted-5)] rounded text-xs cursor-pointer" wire:click="editEmail({{ $email->id }})">
                            <span class="flex-grow truncate">{{ $email->email_address }}</span>
                            <div class="flex gap-1">
                                @if($email->is_primary)
                                    <span class="px-1 py-0.5 bg-green-100 text-green-800 rounded text-xs">Primär</span>
                                @endif
                                <span class="px-1 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">{{ $email->emailType->name }}</span>
                            </div>
                        </div>
                    @endforeach
                    @if(($company->emailAddresses ?? collect())->count() === 0)
                        <p class="text-xs text-[color:var(--ui-muted)]">Noch keine E-Mail-Adressen.</p>
                    @endif
                    <button wire:click="addEmail" class="w-full text-xs px-2 py-1 border border-[color:var(--ui-border)] rounded hover:bg-[color:var(--ui-primary-5)] transition">
                        @svg('heroicon-o-plus', 'w-3 h-3 inline mr-1')
                        E-Mail-Adresse hinzufügen
                    </button>
                </div>
            </div>

            {{-- Adressen --}}
            <div class="px-3 mb-4">
                <h4 class="font-semibold mb-2 text-[color:var(--ui-secondary)] text-sm">Adressen</h4>
                <div class="space-y-1">
                    @foreach($company->postalAddresses ?? [] as $address)
                        <div class="flex items-center gap-1 p-1 bg-[color:var(--ui-muted-5)] rounded text-xs cursor-pointer" wire:click="editAddress({{ $address->id }})">
                            <span class="flex-grow truncate">{{ $address->full_address }}</span>
                            <div class="flex gap-1">
                                @if($address->is_primary)
                                    <span class="px-1 py-0.5 bg-green-100 text-green-800 rounded text-xs">Primär</span>
                                @endif
                                <span class="px-1 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">{{ $address->addressType->name }}</span>
                            </div>
                        </div>
                    @endforeach
                    @if(($company->postalAddresses ?? collect())->count() === 0)
                        <p class="text-xs text-[color:var(--ui-muted)]">Noch keine Adressen.</p>
                    @endif
                    <button wire:click="addAddress" class="w-full text-xs px-2 py-1 border border-[color:var(--ui-border)] rounded hover:bg-[color:var(--ui-primary-5)] transition">
                        @svg('heroicon-o-plus', 'w-3 h-3 inline mr-1')
                        Adresse hinzufügen
                    </button>
                </div>
            </div>

            {{-- Kontakte --}}
            <div class="px-3 mb-4">
                <h4 class="font-semibold mb-2 text-[color:var(--ui-secondary)] text-sm">Kontakte</h4>
                <div class="space-y-1">
                    @foreach($company->contactRelations ?? [] as $relation)
                        <div class="flex items-center gap-1 p-1 bg-[color:var(--ui-muted-5)] rounded text-xs cursor-pointer" wire:click="editContact({{ $relation->id }})">
                            <div class="flex-grow">
                                <div class="font-medium">
                                    <a href="{{ route('crm.contacts.show', ['contact' => $relation->contact->id]) }}" 
                                       class="hover:underline text-[color:var(--ui-primary)]" 
                                       wire:navigate
                                       @click.stop>
                                        {{ $relation->contact->full_name }}
                                    </a>
                                </div>
                                <div class="text-xs text-[color:var(--ui-muted)]">
                                    @if($relation->position)
                                        {{ $relation->position }} - 
                                    @endif
                                    {{ $relation->relationType->name }}
                                </div>
                            </div>
                            <div class="flex gap-1">
                                @if($relation->is_primary)
                                    <span class="px-1 py-0.5 bg-green-100 text-green-800 rounded text-xs">Primär</span>
                                @endif
                                @if($relation->is_current)
                                    <span class="px-1 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">Aktiv</span>
                                @else
                                    <span class="px-1 py-0.5 bg-gray-100 text-gray-800 rounded text-xs">Vergangen</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if(($company->contactRelations ?? collect())->count() === 0)
                        <p class="text-xs text-[color:var(--ui-muted)]">Noch keine Kontakte verknüpft.</p>
                    @endif
                    <button wire:click="addContact" class="w-full text-xs px-2 py-1 border border-[color:var(--ui-border)] rounded hover:bg-[color:var(--ui-primary-5)] transition">
                        @svg('heroicon-o-plus', 'w-3 h-3 inline mr-1')
                        Kontakt hinzufügen
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>