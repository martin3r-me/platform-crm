{{-- resources/views/vendor/crm/livewire/sidebar-content.blade.php --}}
<div>
    {{-- Modul Header --}}
    <x-sidebar-module-header module-name="CRM" />

    {{-- Abschnitt: Allgemein --}}
    <div>
        <h4 x-show="!collapsed" class="px-4 py-3 text-xs tracking-wide font-semibold text-gray-400 uppercase">Allgemein</h4>

        {{-- Kontakte --}}
        <a href="{{ route('crm.contacts.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/contacts')
                   ? 'bg-[#ff7a59] text-white shadow'
                   : 'text-gray-900 hover:bg-orange-50 hover:text-[#ff7a59]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-user-group class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Kontakte</span>
        </a>

        {{-- Engagements --}}
        <a href="{{ route('crm.engagements.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/engagements')
                   ? 'bg-[#ff7a59] text-white shadow'
                   : 'text-gray-900 hover:bg-orange-50 hover:text-[#ff7a59]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-clipboard-document-list class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Engagements</span>
        </a>

        {{-- Unternehmen --}}
        <a href="{{ route('crm.companies.index') }}"
           class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname.includes('/companies')
                   ? 'bg-[#ff7a59] text-white shadow'
                   : 'text-gray-900 hover:bg-orange-50 hover:text-[#ff7a59]',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-building-office class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Unternehmen</span>
        </a>
    </div>

    {{-- Abschnitt: Schnellzugriff --}}
    <div x-show="!collapsed">
        <h4 class="px-4 py-3 text-xs tracking-wide font-semibold text-gray-400 uppercase">Schnellzugriff</h4>

        {{-- Neueste Kontakte --}}
        @foreach($recentContacts ?? [] as $contact)
            <a href="{{ route('crm.contacts.show', ['contact' => $contact]) }}"
               class="relative flex items-center px-3 py-2 my-1 rounded-md font-medium transition gap-3"
               :class="[
                   window.location.pathname.includes('/contacts/{{ $contact->id }}/') ||
                   window.location.pathname.endsWith('/contacts/{{ $contact->id }}')
                       ? 'bg-[#ff7a59] text-white shadow'
                       : 'text-gray-900 hover:bg-orange-50 hover:text-[#ff7a59]'
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
                   window.location.pathname.endsWith('/companies/{{ $company->id }}')
                       ? 'bg-[#ff7a59] text-white shadow'
                       : 'text-gray-900 hover:bg-orange-50 hover:text-[#ff7a59]'
               ]"
               wire:navigate>
                <x-heroicon-o-building-office class="w-6 h-6 flex-shrink-0"/>
                <span class="truncate">{{ $company->display_name }}</span>
            </a>
        @endforeach
    </div>
</div>
