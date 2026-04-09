<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.index'), 'icon' => 'users'],
            ['label' => 'Engagements', 'href' => route('crm.engagements.index')],
            ['label' => $engagement->title],
        ]">
            <div class="flex items-center gap-2">
                <x-ui-confirm-button action="delete" text="" confirmText="Wirklich löschen?" variant="danger-outline" size="sm" :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()" />

                {{-- Prev/Next Navigation --}}
                @if($prevEngagementId || $nextEngagementId)
                    <div class="flex items-center gap-1">
                        @if($prevEngagementId)
                            <a href="{{ route('crm.engagements.show', $prevEngagementId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] hover:bg-[color:var(--ui-muted-5)] transition">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] opacity-30">
                                @svg('heroicon-o-chevron-left', 'w-4 h-4')
                            </span>
                        @endif
                        @if($nextEngagementId)
                            <a href="{{ route('crm.engagements.show', $nextEngagementId) }}" wire:navigate class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] hover:bg-[color:var(--ui-muted-5)] transition">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </a>
                        @else
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-md border border-[color:var(--ui-border)] opacity-30">
                                @svg('heroicon-o-chevron-right', 'w-4 h-4')
                            </span>
                        @endif
                    </div>
                @endif

                @if($this->isDirty)
                    <x-ui-button variant="primary" size="sm" wire:click="save">
                        @svg('heroicon-o-check', 'w-4 h-4')
                        <span>Speichern</span>
                    </x-ui-button>
                @endif
            </div>
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Verknüpfungen" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-5">
                {{-- Linked Companies --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-[color:var(--ui-secondary)] uppercase tracking-wider">Unternehmen</h4>
                        <button wire:click="openCompanyLinkModal" class="text-[color:var(--ui-primary)] hover:opacity-70">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                        </button>
                    </div>
                    @forelse($engagement->companyLinks as $link)
                        @if($link->company)
                            <div class="flex items-center justify-between gap-2 p-2 rounded-lg border border-[color:var(--ui-border)] mb-1.5">
                                <a href="{{ route('crm.companies.show', $link->company) }}" wire:navigate class="flex items-center gap-2 min-w-0 hover:text-[color:var(--ui-primary)] transition text-sm">
                                    @svg('heroicon-o-building-office', 'w-4 h-4 text-[color:var(--ui-muted)] flex-shrink-0')
                                    <span class="truncate">{{ $link->company->name }}</span>
                                </a>
                                <button wire:click="detachCompany({{ $link->company->id }})" wire:confirm="Verknüpfung entfernen?" class="text-[color:var(--ui-muted)] hover:text-red-500 flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                                </button>
                            </div>
                        @endif
                    @empty
                        <p class="text-xs text-[color:var(--ui-muted)]">Keine Unternehmen verknüpft.</p>
                    @endforelse
                </div>

                {{-- Linked Contacts --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-xs font-bold text-[color:var(--ui-secondary)] uppercase tracking-wider">Kontakte</h4>
                        <button wire:click="openContactLinkModal" class="text-[color:var(--ui-primary)] hover:opacity-70">
                            @svg('heroicon-o-plus', 'w-4 h-4')
                        </button>
                    </div>
                    @forelse($engagement->contactLinks as $link)
                        @if($link->contact)
                            <div class="flex items-center justify-between gap-2 p-2 rounded-lg border border-[color:var(--ui-border)] mb-1.5">
                                <a href="{{ route('crm.contacts.show', $link->contact) }}" wire:navigate class="flex items-center gap-2 min-w-0 hover:text-[color:var(--ui-primary)] transition text-sm">
                                    @svg('heroicon-o-user', 'w-4 h-4 text-[color:var(--ui-muted)] flex-shrink-0')
                                    <span class="truncate">{{ $link->contact->full_name }}</span>
                                </a>
                                <button wire:click="detachContact({{ $link->contact->id }})" wire:confirm="Verknüpfung entfernen?" class="text-[color:var(--ui-muted)] hover:text-red-500 flex-shrink-0">
                                    @svg('heroicon-o-x-mark', 'w-3.5 h-3.5')
                                </button>
                            </div>
                        @endif
                    @empty
                        <p class="text-xs text-[color:var(--ui-muted)]">Keine Kontakte verknüpft.</p>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="h-full flex flex-col">
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    @forelse($engagement->activities as $activity)
                        <div class="flex gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($activity->activity_type === 'manual')
                                    <div class="w-6 h-6 rounded-full bg-[color:var(--ui-primary-10)] flex items-center justify-center">
                                        @svg('heroicon-s-pencil', 'w-3 h-3 text-[color:var(--ui-primary)]')
                                    </div>
                                @elseif($activity->name === 'created')
                                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center">
                                        @svg('heroicon-s-plus', 'w-3 h-3 text-green-600')
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full bg-[color:var(--ui-muted-10)] flex items-center justify-center">
                                        @svg('heroicon-s-cog-6-tooth', 'w-3 h-3 text-[color:var(--ui-muted)]')
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow min-w-0">
                                @if($activity->activity_type === 'manual')
                                    <p class="text-sm">{{ $activity->message }}</p>
                                @elseif($activity->name === 'created')
                                    <p class="text-sm text-[color:var(--ui-muted)]">Engagement erstellt</p>
                                @elseif($activity->name === 'updated' && is_array($activity->properties))
                                    <p class="text-sm text-[color:var(--ui-muted)]">
                                        {{ collect($activity->properties)->keys()->map(fn($k) => str_replace('_', ' ', ucfirst($k)))->implode(', ') }} geändert
                                    </p>
                                @else
                                    <p class="text-sm text-[color:var(--ui-muted)]">{{ $activity->name }}</p>
                                @endif
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-xs text-[color:var(--ui-muted)]">
                                        {{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}
                                    </span>
                                    @if($activity->activity_type === 'manual' && $activity->user_id === auth()->id())
                                        <button wire:click="deleteNote({{ $activity->id }})" wire:confirm="Notiz wirklich löschen?" class="text-xs text-red-400 hover:text-red-600">
                                            @svg('heroicon-o-trash', 'w-3 h-3')
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[color:var(--ui-muted)]">Keine Aktivitäten vorhanden.</p>
                    @endforelse
                </div>

                <div class="flex-shrink-0 border-t border-[color:var(--ui-border)] p-3">
                    @error('newNote')
                        <p class="text-xs text-red-500 mb-2">{{ $message }}</p>
                    @enderror
                    <form wire:submit="addNote" class="flex items-end gap-2">
                        <textarea
                            wire:model="newNote"
                            rows="2"
                            class="flex-1 text-sm rounded-lg border border-[color:var(--ui-border)] bg-[color:var(--ui-bg)] p-2 focus:border-[color:var(--ui-primary)] focus:ring-1 focus:ring-[color:var(--ui-primary)] outline-none resize-none"
                            placeholder="Notiz hinzufügen..."
                        ></textarea>
                        <button type="submit" class="flex-shrink-0 w-8 h-8 rounded-lg bg-[color:var(--ui-primary)] text-white flex items-center justify-center hover:opacity-90 transition">
                            @svg('heroicon-s-arrow-up', 'w-4 h-4')
                        </button>
                    </form>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        {{-- Hero Header --}}
        <div class="flex items-start gap-4 mb-6 p-4 rounded-xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)]">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0
                @switch($engagement->type)
                    @case('note') bg-blue-100 @break
                    @case('call') bg-green-100 @break
                    @case('meeting') bg-purple-100 @break
                    @case('task') bg-amber-100 @break
                @endswitch
            ">
                @switch($engagement->type)
                    @case('note') @svg('heroicon-o-pencil-square', 'w-6 h-6 text-blue-600') @break
                    @case('call') @svg('heroicon-o-phone', 'w-6 h-6 text-green-600') @break
                    @case('meeting') @svg('heroicon-o-calendar', 'w-6 h-6 text-purple-600') @break
                    @case('task') @svg('heroicon-o-clipboard-document-check', 'w-6 h-6 text-amber-600') @break
                @endswitch
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h1 class="text-lg font-bold text-[color:var(--ui-secondary)]">{{ $engagement->title }}</h1>
                    @php
                        $typeBadge = match($engagement->type) {
                            'note' => ['variant' => 'primary', 'label' => 'Notiz'],
                            'call' => ['variant' => 'success', 'label' => 'Anruf'],
                            'meeting' => ['variant' => 'warning', 'label' => 'Meeting'],
                            'task' => ['variant' => 'secondary', 'label' => 'Aufgabe'],
                            default => ['variant' => 'secondary', 'label' => $engagement->type],
                        };
                    @endphp
                    <x-ui-badge :variant="$typeBadge['variant']" size="sm">{{ $typeBadge['label'] }}</x-ui-badge>
                    @if($engagement->status)
                        @php
                            $statusVariant = match($engagement->status) {
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'in_progress' => 'warning',
                                default => 'secondary',
                            };
                            $statusLabel = match($engagement->status) {
                                'open' => 'Offen',
                                'in_progress' => 'In Bearbeitung',
                                'completed' => 'Abgeschlossen',
                                'cancelled' => 'Abgebrochen',
                                default => $engagement->status,
                            };
                        @endphp
                        <x-ui-badge :variant="$statusVariant" size="sm">{{ $statusLabel }}</x-ui-badge>
                    @endif
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-[color:var(--ui-muted)]">
                    @if($engagement->ownedByUser)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            {{ $engagement->ownedByUser->name }}
                        </span>
                    @endif
                    @if($engagement->scheduled_at)
                        <span class="flex items-center gap-1">
                            @svg('heroicon-o-clock', 'w-3.5 h-3.5')
                            {{ $engagement->scheduled_at->format('d.m.Y H:i') }}
                        </span>
                    @endif
                    <span class="flex items-center gap-1">
                        @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                        Erstellt {{ $engagement->created_at->format('d.m.Y H:i') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Details --}}
            <x-ui-panel title="Details">
                <div class="space-y-4">
                    <x-ui-input-text
                        name="title"
                        label="Titel"
                        wire:model.live.debounce.500ms="title"
                        placeholder="Titel eingeben..."
                        required
                        :errorKey="'title'"
                    />
                    <x-ui-input-textarea
                        name="body"
                        label="Beschreibung"
                        wire:model.live.debounce.500ms="body"
                        placeholder="Beschreibung (optional)"
                        rows="4"
                        :errorKey="'body'"
                    />
                    @if(in_array($engagement->type, ['call', 'meeting', 'task']))
                        <x-ui-input-select
                            name="status"
                            label="Status"
                            :options="collect([
                                ['value' => 'open', 'label' => 'Offen'],
                                ['value' => 'in_progress', 'label' => 'In Bearbeitung'],
                                ['value' => 'completed', 'label' => 'Abgeschlossen'],
                                ['value' => 'cancelled', 'label' => 'Abgebrochen'],
                            ])"
                            optionValue="value"
                            optionLabel="label"
                            :nullable="true"
                            nullLabel="– Status auswählen –"
                            wire:model.live="status"
                        />
                    @endif
                    @if($engagement->type === 'task')
                        <x-ui-input-select
                            name="priority"
                            label="Priorität"
                            :options="collect([
                                ['value' => 'low', 'label' => 'Niedrig'],
                                ['value' => 'medium', 'label' => 'Mittel'],
                                ['value' => 'high', 'label' => 'Hoch'],
                            ])"
                            optionValue="value"
                            optionLabel="label"
                            :nullable="true"
                            nullLabel="– Priorität auswählen –"
                            wire:model.live="priority"
                        />
                    @endif
                </div>
            </x-ui-panel>

            {{-- Zeitplanung --}}
            @if(in_array($engagement->type, ['meeting', 'task']))
                <x-ui-panel title="Zeitplanung">
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui-input-text
                            name="scheduledAt"
                            label="{{ $engagement->type === 'task' ? 'Fällig am' : 'Geplant am' }}"
                            type="datetime-local"
                            wire:model.live="scheduledAt"
                            :errorKey="'scheduledAt'"
                        />
                        @if($engagement->type === 'meeting')
                            <x-ui-input-text
                                name="endedAt"
                                label="Ende"
                                type="datetime-local"
                                wire:model.live="endedAt"
                                :errorKey="'endedAt'"
                            />
                        @endif
                        <x-ui-input-text
                            name="completedAt"
                            label="Abgeschlossen am"
                            type="datetime-local"
                            wire:model.live="completedAt"
                            :errorKey="'completedAt'"
                        />
                    </div>
                </x-ui-panel>
            @endif

            {{-- Metadata --}}
            @if($engagement->metadata)
                <x-ui-panel title="Metadata">
                    <pre class="text-xs text-[color:var(--ui-muted)] bg-[color:var(--ui-muted-5)] p-3 rounded-lg overflow-auto max-h-40">{{ json_encode($engagement->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Contact Link Modal --}}
    <x-ui-modal size="sm" model="contactLinkModalShow">
        <x-slot name="header">Kontakt verknüpfen</x-slot>
        <div class="space-y-4">
            <x-ui-input-select
                name="linkContactId"
                label="Kontakt"
                :options="$this->availableContacts"
                optionValue="id"
                optionLabel="full_name"
                :nullable="true"
                nullLabel="– Kontakt auswählen –"
                wire:model.live="linkContactId"
                required
            />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="$set('contactLinkModalShow', false)">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="attachContact">Verknüpfen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>

    {{-- Company Link Modal --}}
    <x-ui-modal size="sm" model="companyLinkModalShow">
        <x-slot name="header">Unternehmen verknüpfen</x-slot>
        <div class="space-y-4">
            <x-ui-input-select
                name="linkCompanyId"
                label="Unternehmen"
                :options="$this->availableCompanies"
                optionValue="id"
                optionLabel="name"
                :nullable="true"
                nullLabel="– Unternehmen auswählen –"
                wire:model.live="linkCompanyId"
                required
            />
        </div>
        <x-slot name="footer">
            <div class="d-flex justify-end gap-2">
                <x-ui-button type="button" variant="secondary-outline" wire:click="$set('companyLinkModalShow', false)">Abbrechen</x-ui-button>
                <x-ui-button type="button" variant="primary" wire:click="attachCompany">Verknüpfen</x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
</x-ui-page>
