<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Kontaktlisten', 'href' => route('crm.lists.index')],
            ['label' => $contactList->name],
        ]">
            <div class="flex items-center gap-2">
                <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />

                {{-- Prev/Next Navigation --}}
                @if($prevListId || $nextListId)
                    <div class="flex items-center gap-1">
                        @if($prevListId)
                            <a href="{{ route('crm.lists.show', $prevListId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 transition">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </span>
                        @endif
                        @if($nextListId)
                            <a href="{{ route('crm.lists.show', $nextListId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 hover:bg-gray-50 transition">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-gray-200 opacity-30">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </span>
                        @endif
                    </div>
                @endif

                @if($this->isDirty)
                    <button type="button" wire:click="save" class="inline-flex items-center gap-1.5 px-3 h-8 whitespace-nowrap rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        Speichern
                    </button>
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar"></x-slot>
    <x-slot name="activity"></x-slot>

    <x-ui-page-container>

        {{-- Flash Messages --}}
        @if(session('message'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('message') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        {{-- Hero Header --}}
        <div class="flex items-start gap-4 mb-6 p-4 rounded-xl border border-gray-200 bg-white">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background-color: {{ ($contactList->color ?? '#6366f1') . '20' }}">
                @svg('heroicon-o-list-bullet', 'w-6 h-6', ['style' => 'color: ' . ($contactList->color ?? '#6366f1')])
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold text-gray-900">{{ $contactList->name }}</h1>
                    @if($contactList->color)
                        <span class="inline-block w-3 h-3 rounded-full" style="background-color: {{ $contactList->color }}"></span>
                    @endif
                    @if($contactList->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktiv</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Inaktiv</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-400">
                    <span class="flex items-center gap-1">
                        @svg('heroicon-o-users', 'w-3.5 h-3.5')
                        {{ $contactList->member_count ?? 0 }} Mitglieder
                    </span>
                    @if($contactList->createdByUser)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            {{ $contactList->createdByUser->name }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1">
                        @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                        Erstellt {{ $contactList->created_at->format('d.m.Y H:i') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex gap-6">
                @foreach([
                    'settings' => 'Einstellungen',
                    'members' => 'Mitglieder',
                ] as $tab => $label)
                    <button wire:click="$set('activeTab', '{{ $tab }}')" class="pb-3 text-[13px] font-medium border-b-2 transition-colors {{ $activeTab === $tab ? 'border-[#ff7a59] text-[#ff7a59]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Tab: Einstellungen --}}
        @if($activeTab === 'settings')
            <div class="space-y-6">
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Grundeinstellungen</h3></div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Name</label>
                            <input type="text" wire:model.live.debounce.500ms="name" placeholder="Listenname" required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Beschreibung</label>
                            <textarea wire:model.live.debounce.500ms="description" rows="3" placeholder="Kurze Beschreibung der Liste (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Farbe</label>
                            <div class="flex items-center gap-3">
                                <input type="color" wire:model.live="color" class="w-10 h-10 rounded border border-gray-300 cursor-pointer" />
                                <span class="text-[13px] text-gray-500">{{ $color ?? 'Keine Farbe' }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="isActive" class="rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59]/20" />
                                <span class="text-[13px] text-gray-900">Liste ist aktiv</span>
                            </label>
                            <p class="text-[11px] text-gray-400 mt-1 ml-6">Inaktive Listen werden bei der Newsletter-Erstellung nicht angezeigt.</p>
                        </div>
                    </div>
                </section>
            </div>
        @endif

        {{-- Tab: Mitglieder --}}
        @if($activeTab === 'members')
            <section class="bg-white rounded-lg border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">Mitglieder ({{ $contactList->member_count ?? 0 }})</h3>
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="memberSearch"
                                placeholder="Mitglieder suchen..."
                                class="w-48 px-3 py-1.5 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                            />
                            <button wire:click="openAddMemberModal" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md bg-[#ff7a59] text-white text-[13px] font-medium hover:bg-[#e8604a] transition-colors">
                                @svg('heroicon-o-plus', 'w-4 h-4')
                                Kontakt hinzufügen
                            </button>
                        </div>
                    </div>
                </div>

                @if($this->members->count() === 0)
                    <div class="p-6 text-sm text-gray-400 text-center">
                        @if(trim($memberSearch) !== '')
                            Keine Mitglieder gefunden für "{{ $memberSearch }}".
                        @else
                            Noch keine Mitglieder. Fügen Sie Kontakte über den Button oben hinzu.
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Kontakt</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">E-Mail</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-medium text-gray-400 uppercase tracking-wide">Hinzugefügt</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-medium text-gray-400 uppercase tracking-wide"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($this->members as $member)
                                    <tr wire:key="member-{{ $member->id }}">
                                        <td class="px-4 py-2 text-[13px]">
                                            @if($member->contact)
                                                <a href="{{ route('crm.contacts.show', $member->contact_id) }}" wire:navigate class="font-medium text-gray-900 hover:text-[#ff7a59] transition-colors">
                                                    {{ $member->contact->full_name }}
                                                </a>
                                            @else
                                                <span class="text-gray-400">–</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 text-[13px] text-gray-500">
                                            {{ $member->contact?->emails?->first()?->email ?? '–' }}
                                        </td>
                                        <td class="px-4 py-2 text-xs text-gray-400">
                                            {{ $member->created_at->format('d.m.Y H:i') }}
                                        </td>
                                        <td class="px-4 py-2 text-right">
                                            <button wire:click="removeMember({{ $member->id }})" wire:confirm="Kontakt wirklich aus der Liste entfernen?" class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs text-red-600 hover:bg-red-50 transition-colors">
                                                @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                                                Entfernen
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        @endif
    </x-ui-page-container>

    {{-- Add Member Modal --}}
    <x-ui-modal wire:model="addMemberModal" size="lg">
        <x-slot name="header">Kontakt hinzufügen</x-slot>

        <div class="space-y-4">
            <div>
                <label class="block text-[11px] font-medium text-gray-500 mb-1">Kontakt suchen</label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="contactSearch"
                    placeholder="Name oder E-Mail eingeben..."
                    autofocus
                    class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"
                />
            </div>

            @if(trim($contactSearch) !== '')
                @if($this->searchableContacts->count() === 0)
                    <div class="text-sm text-gray-400 text-center py-4">
                        Keine passenden Kontakte gefunden.
                    </div>
                @else
                    <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-md divide-y divide-gray-100">
                        @foreach($this->searchableContacts as $contact)
                            <button
                                wire:key="add-contact-{{ $contact->id }}"
                                wire:click="addMember({{ $contact->id }})"
                                class="w-full flex items-center justify-between px-3 py-2 text-left hover:bg-orange-50/50 transition-colors"
                            >
                                <div>
                                    <div class="text-[13px] font-medium text-gray-900">{{ $contact->full_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $contact->emails?->first()?->email ?? 'Keine E-Mail' }}</div>
                                </div>
                                <span class="text-xs text-[#ff7a59]">@svg('heroicon-o-plus', 'w-4 h-4')</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="text-sm text-gray-400 text-center py-4">
                    Geben Sie einen Suchbegriff ein, um Kontakte zu finden.
                </div>
            @endif
        </div>

        <x-slot name="footer">
            <div class="flex justify-end">
                <button type="button" @click="$wire.closeAddMemberModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md border border-gray-300 bg-white text-gray-700 text-[13px] font-medium hover:bg-gray-50 transition-colors">Schließen</button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
