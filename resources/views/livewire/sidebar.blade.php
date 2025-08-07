{{-- resources/views/vendor/planner/livewire/sidebar-content.blade.php --}}
<div>
    <div>
        <h4 x-show="!collapsed" class="p-3 text-sm italic text-secondary uppercase">HR & Lohn</h4>

        {{-- Dashboard --}}
        <a href="{{ route('bhgdata.dashboard') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname === '{{ parse_url(route('bhgdata.dashboard'), PHP_URL_PATH) }}'
                   ? 'bg-primary text-on-primary shadow-md'
                   : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-home class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Dashboard</span>
        </a>

        {{-- Kontakte --}}
        <a href="{{ route('crm.contacts.index') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname === '{{ parse_url(route('crm.contacts.index'), PHP_URL_PATH) }}'
                   ? 'bg-primary text-on-primary shadow-md'
                   : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-user-group class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Kontakte</span>
        </a>

        {{-- Unternehmen --}}
        <a href="{{ route('crm.companies.index') }}"
           class="relative d-flex items-center p-2 my-1 rounded-md font-medium transition"
           :class="[
               window.location.pathname === '{{ parse_url(route('crm.companies.index'), PHP_URL_PATH) }}'
                   ? 'bg-primary text-on-primary shadow-md'
                   : 'text-black hover:bg-primary-10 hover:text-primary hover:shadow-md',
               collapsed ? 'justify-center' : 'gap-3'
           ]"
           wire:navigate>
            <x-heroicon-o-building-office class="w-6 h-6 flex-shrink-0"/>
            <span x-show="!collapsed" class="truncate">Unternehmen</span>
        </a>
    </div>
</div>