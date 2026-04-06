<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'CRM', 'href' => route('crm.dashboard'), 'icon' => 'users'],
        ]" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Schnellzugriff" width="w-72" :defaultOpen="true" side="left">
            <div class="p-4 space-y-5">
                {{-- Letzte Kontakte --}}
                <div>
                    <h4 class="text-xs font-bold text-[color:var(--ui-secondary)] uppercase tracking-wider mb-2">Letzte Kontakte</h4>
                    <div class="space-y-1">
                        @forelse($this->recentContacts->take(8) as $contact)
                            <a href="{{ route('crm.contacts.show', $contact) }}" wire:navigate
                               class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition group">
                                <div class="w-7 h-7 rounded-full bg-[color:var(--ui-primary-10)] flex items-center justify-center text-[10px] font-bold text-[color:var(--ui-primary)] flex-shrink-0">
                                    {{ strtoupper(mb_substr($contact->first_name, 0, 1) . mb_substr($contact->last_name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-[color:var(--ui-secondary)] truncate">{{ $contact->first_name }} {{ $contact->last_name }}</div>
                                    <div class="text-[10px] text-[color:var(--ui-muted)]">{{ $contact->updated_at->diffForHumans() }}</div>
                                </div>
                            </a>
                        @empty
                            <p class="text-xs text-[color:var(--ui-muted)] px-2">Keine Kontakte</p>
                        @endforelse
                    </div>
                </div>

                {{-- Letzte Unternehmen --}}
                <div>
                    <h4 class="text-xs font-bold text-[color:var(--ui-secondary)] uppercase tracking-wider mb-2">Letzte Unternehmen</h4>
                    <div class="space-y-1">
                        @forelse($this->recentCompanies->take(8) as $company)
                            <a href="{{ route('crm.companies.show', $company) }}" wire:navigate
                               class="flex items-center gap-2.5 px-2 py-1.5 rounded-lg text-sm hover:bg-[color:var(--ui-muted-5)] transition group">
                                <div class="w-7 h-7 rounded-md bg-[color:var(--ui-muted-10)] flex items-center justify-center text-[color:var(--ui-muted)] flex-shrink-0">
                                    @svg('heroicon-o-building-office', 'w-3.5 h-3.5')
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-[color:var(--ui-secondary)] truncate">{{ $company->display_name }}</div>
                                    <div class="text-[10px] text-[color:var(--ui-muted)]">{{ $company->updated_at->diffForHumans() }}</div>
                                </div>
                            </a>
                        @empty
                            <p class="text-xs text-[color:var(--ui-muted)] px-2">Keine Unternehmen</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="activityOpen" side="right">
            <div class="h-full flex flex-col">
                <div class="flex-1 overflow-y-auto p-4 space-y-3">
                    @forelse($this->recentActivities as $activity)
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
                                @if($activity->activityable)
                                    <p class="text-xs font-medium text-[color:var(--ui-secondary)] truncate">
                                        @if($activity->activityable instanceof \Platform\Crm\Models\CrmContact)
                                            {{ $activity->activityable->first_name }} {{ $activity->activityable->last_name }}
                                        @elseif($activity->activityable instanceof \Platform\Crm\Models\CrmCompany)
                                            {{ $activity->activityable->display_name }}
                                        @endif
                                    </p>
                                @endif
                                @if($activity->activity_type === 'manual')
                                    <p class="text-sm">{{ $activity->message }}</p>
                                @elseif($activity->name === 'created')
                                    <p class="text-sm text-[color:var(--ui-muted)]">Erstellt</p>
                                @elseif($activity->name === 'updated' && is_array($activity->properties))
                                    <p class="text-sm text-[color:var(--ui-muted)]">
                                        {{ collect($activity->properties)->keys()->map(fn($k) => str_replace('_', ' ', ucfirst($k)))->implode(', ') }} geändert
                                    </p>
                                @else
                                    <p class="text-sm text-[color:var(--ui-muted)]">{{ $activity->name }}</p>
                                @endif
                                <span class="text-xs text-[color:var(--ui-muted)]">
                                    {{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[color:var(--ui-muted)]">Keine Aktivitäten vorhanden.</p>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>

        {{-- Stat Tiles --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Kontakte --}}
            <a href="{{ route('crm.contacts.index') }}" wire:navigate class="block p-4 rounded-xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] hover:border-[color:var(--ui-primary)]/40 transition group">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-[color:var(--ui-muted)] uppercase tracking-wider">Kontakte</span>
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        @svg('heroicon-o-users', 'w-4 h-4 text-blue-500')
                    </div>
                </div>
                <div class="text-2xl font-bold text-[color:var(--ui-secondary)]">{{ $this->totalContacts }}</div>
                @if($this->newContactsThisWeek > 0)
                    <div class="text-xs text-green-600 mt-1">+{{ $this->newContactsThisWeek }} diese Woche</div>
                @endif
            </a>

            {{-- Unternehmen --}}
            <a href="{{ route('crm.companies.index') }}" wire:navigate class="block p-4 rounded-xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)] hover:border-[color:var(--ui-primary)]/40 transition group">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-[color:var(--ui-muted)] uppercase tracking-wider">Unternehmen</span>
                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                        @svg('heroicon-o-building-office', 'w-4 h-4 text-purple-500')
                    </div>
                </div>
                <div class="text-2xl font-bold text-[color:var(--ui-secondary)]">{{ $this->totalCompanies }}</div>
                @if($this->newCompaniesThisWeek > 0)
                    <div class="text-xs text-green-600 mt-1">+{{ $this->newCompaniesThisWeek }} diese Woche</div>
                @endif
            </a>

            {{-- Neu diese Woche --}}
            <div class="p-4 rounded-xl border border-[color:var(--ui-border)] bg-[color:var(--ui-surface)]">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-[color:var(--ui-muted)] uppercase tracking-wider">Neu diese Woche</span>
                    <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                        @svg('heroicon-o-arrow-trending-up', 'w-4 h-4 text-green-500')
                    </div>
                </div>
                <div class="text-2xl font-bold text-[color:var(--ui-secondary)]">{{ $this->newContactsThisWeek + $this->newCompaniesThisWeek }}</div>
                @php $lastWeekTotal = $this->newContactsLastWeek; @endphp
                @if($lastWeekTotal > 0)
                    @php $diff = ($this->newContactsThisWeek + $this->newCompaniesThisWeek) - $lastWeekTotal; @endphp
                    <div class="text-xs mt-1 {{ $diff >= 0 ? 'text-green-600' : 'text-red-500' }}">
                        @if($diff >= 0)
                            @svg('heroicon-s-arrow-up', 'w-3 h-3 inline') +{{ $diff }}
                        @else
                            @svg('heroicon-s-arrow-down', 'w-3 h-3 inline') {{ $diff }}
                        @endif
                        vs. letzte Woche
                    </div>
                @endif
            </div>

            {{-- Überfällige Wiedervorlagen --}}
            <div class="p-4 rounded-xl border {{ $this->overdueFollowUpsCount > 0 ? 'border-red-200 bg-red-50/30' : 'border-[color:var(--ui-border)] bg-[color:var(--ui-surface)]' }}">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-[color:var(--ui-muted)] uppercase tracking-wider">Überfällig</span>
                    <div class="w-8 h-8 rounded-lg {{ $this->overdueFollowUpsCount > 0 ? 'bg-red-100' : 'bg-amber-50' }} flex items-center justify-center">
                        @svg('heroicon-o-clock', 'w-4 h-4 {{ $this->overdueFollowUpsCount > 0 ? "text-red-500" : "text-amber-500" }}')
                    </div>
                </div>
                <div class="text-2xl font-bold {{ $this->overdueFollowUpsCount > 0 ? 'text-red-600' : 'text-[color:var(--ui-secondary)]' }}">{{ $this->overdueFollowUpsCount }}</div>
                <div class="text-xs text-[color:var(--ui-muted)] mt-1">Wiedervorlagen</div>
            </div>
        </div>

        {{-- Follow-ups + Status Breakdown --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Anstehende Wiedervorlagen --}}
            <x-ui-panel>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)]">Anstehende Wiedervorlagen</h3>
                    <x-ui-badge variant="secondary" size="xs">nächste 7 Tage</x-ui-badge>
                </div>
                @php $allFollowUps = $this->overdueFollowUps->merge($this->upcomingFollowUps); @endphp
                @if($allFollowUps->count() > 0)
                    <div class="space-y-2">
                        @foreach($allFollowUps as $followUp)
                            <div class="flex items-center gap-3 p-2.5 rounded-lg border {{ $followUp->isOverdue() ? 'border-red-200 bg-red-50/30' : ($followUp->isDueToday() ? 'border-amber-200 bg-amber-50/30' : 'border-[color:var(--ui-border)]') }}">
                                <button wire:click="toggleFollowUp({{ $followUp->id }})" class="flex-shrink-0">
                                    <div class="w-5 h-5 rounded border-2 {{ $followUp->isOverdue() ? 'border-red-400' : ($followUp->isDueToday() ? 'border-amber-400' : 'border-[color:var(--ui-border)]') }} hover:border-[color:var(--ui-primary)] transition"></div>
                                </button>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium truncate">{{ $followUp->title }}</div>
                                    <div class="text-xs text-[color:var(--ui-muted)]">
                                        @if($followUp->followupable)
                                            @if($followUp->followupable instanceof \Platform\Crm\Models\CrmContact)
                                                {{ $followUp->followupable->first_name }} {{ $followUp->followupable->last_name }}
                                            @else
                                                {{ $followUp->followupable->display_name }}
                                            @endif
                                            &middot;
                                        @endif
                                        <span class="{{ $followUp->isOverdue() ? 'text-red-600 font-medium' : ($followUp->isDueToday() ? 'text-amber-600' : '') }}">
                                            {{ $followUp->due_date->format('d.m.Y') }}
                                            @if($followUp->isOverdue()) (überfällig) @elseif($followUp->isDueToday()) (heute) @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[color:var(--ui-muted)] py-4 text-center">Keine anstehenden Wiedervorlagen</p>
                @endif
            </x-ui-panel>

            {{-- Kontakte nach Status --}}
            <x-ui-panel>
                <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)] mb-3">Kontakte nach Status</h3>
                @if($this->contactsByStatus->count() > 0)
                    @php $maxCount = $this->contactsByStatus->max('count'); @endphp
                    <div class="space-y-3">
                        @foreach($this->contactsByStatus as $status)
                            @php
                                $variant = \Platform\Crm\Models\CrmContactStatus::getVariantForCode($status->code ?? '');
                                $colorClasses = match($variant) {
                                    'success' => 'bg-green-500',
                                    'info' => 'bg-blue-500',
                                    'warning' => 'bg-amber-500',
                                    'danger' => 'bg-red-500',
                                    default => 'bg-[color:var(--ui-muted)]',
                                };
                            @endphp
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm text-[color:var(--ui-secondary)]">{{ $status->name }}</span>
                                    <span class="text-sm font-semibold text-[color:var(--ui-secondary)]">{{ $status->count }}</span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-[color:var(--ui-muted-10)]">
                                    <div class="h-2 rounded-full {{ $colorClasses }} transition-all" style="width: {{ $maxCount > 0 ? round(($status->count / $maxCount) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[color:var(--ui-muted)] py-4 text-center">Keine Kontakte vorhanden</p>
                @endif
            </x-ui-panel>
        </div>

        {{-- Letzte Kontakte & Unternehmen --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui-panel>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)]">Zuletzt bearbeitete Kontakte</h3>
                    <a href="{{ route('crm.contacts.index') }}" wire:navigate class="text-xs text-[color:var(--ui-primary)] hover:underline">Alle anzeigen</a>
                </div>
                <div class="space-y-2">
                    @forelse($this->recentContacts as $contact)
                        <a href="{{ route('crm.contacts.show', $contact) }}" wire:navigate
                           class="group flex items-center justify-between p-2.5 rounded-lg border border-[color:var(--ui-border)] hover:border-[color:var(--ui-primary)]/40 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-[color:var(--ui-primary-10)] flex items-center justify-center text-xs font-bold text-[color:var(--ui-primary)] flex-shrink-0">
                                    {{ strtoupper(mb_substr($contact->first_name, 0, 1) . mb_substr($contact->last_name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-[color:var(--ui-secondary)] truncate">{{ $contact->first_name }} {{ $contact->last_name }}</div>
                                    <div class="text-xs text-[color:var(--ui-muted)]">{{ $contact->updated_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            @if($contact->contactStatus)
                                <x-ui-badge variant="{{ \Platform\Crm\Models\CrmContactStatus::getVariantForCode($contact->contactStatus->code ?? '') }}" size="xs">
                                    {{ $contact->contactStatus->name }}
                                </x-ui-badge>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm text-[color:var(--ui-muted)] py-4 text-center">Keine Kontakte vorhanden.</p>
                    @endforelse
                </div>
            </x-ui-panel>

            <x-ui-panel>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-[color:var(--ui-secondary)]">Zuletzt bearbeitete Unternehmen</h3>
                    <a href="{{ route('crm.companies.index') }}" wire:navigate class="text-xs text-[color:var(--ui-primary)] hover:underline">Alle anzeigen</a>
                </div>
                <div class="space-y-2">
                    @forelse($this->recentCompanies as $company)
                        <a href="{{ route('crm.companies.show', $company) }}" wire:navigate
                           class="group flex items-center justify-between p-2.5 rounded-lg border border-[color:var(--ui-border)] hover:border-[color:var(--ui-primary)]/40 transition">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-md bg-[color:var(--ui-muted-10)] flex items-center justify-center text-[color:var(--ui-muted)] flex-shrink-0">
                                    @svg('heroicon-o-building-office', 'w-4 h-4')
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-[color:var(--ui-secondary)] truncate">{{ $company->display_name }}</div>
                                    <div class="text-xs text-[color:var(--ui-muted)]">{{ $company->updated_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            @if($company->contactStatus)
                                <x-ui-badge variant="{{ \Platform\Crm\Models\CrmContactStatus::getVariantForCode($company->contactStatus->code ?? '') }}" size="xs">
                                    {{ $company->contactStatus->name }}
                                </x-ui-badge>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm text-[color:var(--ui-muted)] py-4 text-center">Keine Unternehmen vorhanden.</p>
                    @endforelse
                </div>
            </x-ui-panel>
        </div>

    </x-ui-page-container>
</x-ui-page>
