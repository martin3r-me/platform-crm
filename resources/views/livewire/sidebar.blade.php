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
</div>