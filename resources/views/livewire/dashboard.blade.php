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
        <x-ui-page-sidebar title="Schnellzugriff" width="w-80" :defaultOpen="true" side="left">
            <div class="p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Statistiken</h3>
                    <div class="space-y-3">
                        <div class="py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 flex items-center justify-between">
                            <span class="text-xs text-[var(--ui-muted)]">Gesamt Kontakte</span>
                            <span class="text-lg font-bold text-[var(--ui-secondary)]">{{ $this->totalContacts }}</span>
                        </div>
                        <div class="py-3 px-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 flex items-center justify-between">
                            <span class="text-xs text-[var(--ui-muted)]">Gesamt Unternehmen</span>
                            <span class="text-lg font-bold text-[var(--ui-secondary)]">{{ $this->totalCompanies }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
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
                                            {{ $activity->activityable->full_name }}
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
        

        <!-- Haupt-Statistiken (auf das Wesentliche reduziert) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <x-ui-dashboard-tile title="Kontakte" :count="$this->totalContacts" icon="user" variant="secondary" size="lg" :href="route('crm.contacts.index')" />
            <x-ui-dashboard-tile title="Unternehmen" :count="$this->totalCompanies" icon="building-office" variant="secondary" size="lg" :href="route('crm.companies.index')" />
        </div>
        {{-- Zuletzt aktualisiert (Kontakte & Unternehmen) im Planner-Stil --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <x-ui-panel title="Zuletzt aktualisierte Kontakte" subtitle="Top 5" >
                <div class="space-y-2">
                    @forelse(($recentContacts ?? collect())->take(5) as $contact)
                        <a href="{{ route('crm.contacts.show', ['contact' => $contact->id]) }}" wire:navigate
                           class="group flex items-center justify-between p-3 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-primary-5)] transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60 flex items-center justify-center text-xs font-semibold text-[var(--ui-secondary)]">
                                    {{ strtoupper(substr($contact->full_name ?? $contact->name ?? 'K', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $contact->full_name ?? $contact->name }}</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Aktualisiert: {{ optional($contact->updated_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                            @svg('heroicon-o-arrow-right', 'w-4 h-4 text-[var(--ui-muted)] group-hover:text-[var(--ui-primary)]')
                        </a>
                    @empty
                        <div class="text-sm text-[var(--ui-muted)] p-4 text-center">Keine Kontakte vorhanden.</div>
                    @endforelse
                </div>
            </x-ui-panel>

            <x-ui-panel title="Zuletzt aktualisierte Unternehmen" subtitle="Top 5" >
                <div class="space-y-2">
                    @forelse(($recentCompanies ?? collect())->take(5) as $company)
                        <a href="{{ route('crm.companies.show', ['company' => $company->id]) }}" wire:navigate
                           class="group flex items-center justify-between p-3 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)] hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-primary-5)] transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-md bg-[var(--ui-primary-5)] border border-[var(--ui-border)]/60 flex items-center justify-center text-xs font-semibold text-[var(--ui-primary)]">
                                    @svg('heroicon-o-building-office','w-4 h-4')
                                </div>
                                <div class="min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $company->display_name ?? $company->name }}</div>
                                    <div class="text-xs text-[var(--ui-muted)]">Aktualisiert: {{ optional($company->updated_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                            @svg('heroicon-o-arrow-right', 'w-4 h-4 text-[var(--ui-muted)] group-hover:text-[var(--ui-primary)]')
                        </a>
                    @empty
                        <div class="text-sm text-[var(--ui-muted)] p-4 text-center">Keine Unternehmen vorhanden.</div>
                    @endforelse
                </div>
            </x-ui-panel>
        </div>

    </x-ui-page-container>
</x-ui-page>