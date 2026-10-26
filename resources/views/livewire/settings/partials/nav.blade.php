{{-- Zweite Sidebar der CRM-Einstellungen. Erwartet: $aktiv — Schlüssel der offenen Seite. --}}
@php
    $gruppen = [
        'Stammdaten' => [
            ['key' => 'company-types', 'label' => 'Unternehmens-Typen', 'icon' => 'heroicon-o-building-office-2', 'route' => 'crm.settings.company-types.index'],
        ],
    ];

    $klassen = fn (bool $ist) => 'flex w-full items-center gap-3 rounded-md p-2 text-left text-sm font-medium transition-colors '
        . ($ist
            ? 'bg-[color:var(--nx-active)] font-semibold text-[color:var(--nx-text)]'
            : 'text-[color:var(--nx-text)] hover:bg-[color:var(--nx-hover)]');
@endphp

<div class="flex flex-col gap-4 p-3">
    @foreach ($gruppen as $label => $eintraege)
        <div @class(['flex flex-col gap-1', 'border-t border-[color:var(--nx-line)] pt-3' => ! $loop->first])>
            <span class="px-1 pb-1 text-xs font-medium tracking-wide text-[color:var(--nx-faint)]">{{ $label }}</span>
            @foreach ($eintraege as $e)
                <a href="{{ route($e['route']) }}" wire:navigate class="{{ $klassen(($aktiv ?? null) === $e['key']) }}">
                    @svg($e['icon'], 'w-4 h-4 shrink-0 text-[color:var(--nx-muted)]')
                    <span>{{ $e['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endforeach
</div>
