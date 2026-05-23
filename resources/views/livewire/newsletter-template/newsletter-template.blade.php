<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Vorlagen', 'href' => route('crm.newsletter-templates.index')],
            ['label' => $newsletterTemplate->name],
        ]">
            <div class="flex items-center gap-2">
                <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />

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
            <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                @svg('heroicon-o-document-duplicate', 'w-6 h-6 text-indigo-600')
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold text-gray-900">{{ $newsletterTemplate->name }}</h1>
                    @if($newsletterTemplate->is_active)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktiv</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">Inaktiv</span>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-400">
                    @if($newsletterTemplate->category)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-tag', 'w-3.5 h-3.5')
                            {{ $newsletterTemplate->category }}
                        </span>
                    @endif
                    @if($newsletterTemplate->createdByUser)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            {{ $newsletterTemplate->createdByUser->name }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1">
                        @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                        Erstellt {{ $newsletterTemplate->created_at->format('d.m.Y H:i') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex gap-6">
                @foreach([
                    'settings' => 'Einstellungen',
                    'content' => 'Inhalt',
                    'preview' => 'Vorschau',
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
                            <input type="text" wire:model.live.debounce.500ms="name" placeholder="Vorlagen-Name" required class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Beschreibung</label>
                            <textarea wire:model.live.debounce.500ms="description" rows="3" placeholder="Optionale Beschreibung der Vorlage..." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Kategorie</label>
                            <input type="text" wire:model.live.debounce.500ms="category" placeholder="z.B. Marketing, Update, Transaktional" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="isActive" id="isActive" class="rounded border-gray-300 text-[#ff7a59] focus:ring-[#ff7a59]/20" />
                            <label for="isActive" class="text-[13px] text-gray-900">Aktiv</label>
                        </div>
                    </div>
                </section>

                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Standard-Werte</h3></div>
                    <div class="p-4 space-y-4">
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Standard-Betreff</label>
                            <input type="text" wire:model.live.debounce.500ms="defaultSubject" placeholder="Wird beim Erstellen eines Newsletters vorausgefüllt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Standard-Preheader</label>
                            <input type="text" wire:model.live.debounce.500ms="defaultPreheader" placeholder="Wird beim Erstellen eines Newsletters vorausgefüllt" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors" />
                        </div>
                    </div>
                </section>
            </div>
        @endif

        {{-- Tab: Inhalt --}}
        @if($activeTab === 'content')
            <div class="space-y-6">
                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">HTML Body</h3></div>
                    <div class="p-4">
                        <textarea wire:model.live.debounce.500ms="htmlBody" rows="20" placeholder="HTML-Inhalt der Vorlage..." class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 font-mono focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                    </div>
                </section>

                <section class="bg-white rounded-lg border border-gray-200">
                    <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Text Body (Fallback)</h3></div>
                    <div class="p-4">
                        <textarea wire:model.live.debounce.500ms="textBody" rows="10" placeholder="Plaintext-Version (optional)" class="w-full px-3 py-2 text-[13px] rounded-md border border-gray-300 bg-white text-gray-900 placeholder-gray-400 font-mono focus:outline-none focus:ring-2 focus:ring-[#ff7a59]/20 focus:border-[#ff7a59] transition-colors"></textarea>
                    </div>
                </section>
            </div>
        @endif

        {{-- Tab: Vorschau --}}
        @if($activeTab === 'preview')
            <section class="bg-white rounded-lg border border-gray-200">
                <div class="px-4 py-3 border-b border-gray-200"><h3 class="text-sm font-semibold text-gray-900">Vorschau</h3></div>
                <div class="p-4">
                    @if($newsletterTemplate->html_body)
                        <iframe
                            srcdoc="{!! str_replace('"', '&quot;', $newsletterTemplate->html_body) !!}"
                            sandbox="allow-same-origin"
                            class="w-full border border-gray-200 rounded-lg"
                            style="min-height: 600px;"
                        ></iframe>
                    @else
                        <div class="text-sm text-gray-400 py-12 text-center">
                            Kein HTML-Inhalt vorhanden. Wechseln Sie zum Tab "Inhalt", um die Vorlage zu bearbeiten.
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </x-ui-page-container>
</x-ui-page>
