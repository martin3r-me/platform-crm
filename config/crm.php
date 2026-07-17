<?php
return [
    // Scope-Type: 'parent' = root-scoped (immer Root-Team-ID), 'single' = team-spezifisch
    'scope_type' => 'parent',
    
    'routing' => [
        'mode' => env('CRM_MODE', 'subdomain'),  // Standard: Subdomain
        'prefix' => 'crm',                       // Wird nur genutzt, wenn 'path'
    ],
    'guard' => 'web',

    // CardDAV-Server: abonnierbares Telefonbuch (read-only). Siehe docs/carddav.md
    'carddav' => [
        'enabled'         => env('CRM_CARDDAV_ENABLED', true),
        'path'            => env('CRM_CARDDAV_PATH', 'crm/dav'), // Basis-URL-Segment des DAV-Servers
        'secret_ttl_days' => env('CRM_CARDDAV_SECRET_TTL_DAYS'), // null = unbegrenzt gültig
    ],

    'navigation' => [
        'route' => 'crm.index',
        'icon'  => 'heroicon-o-user-group',
        'order' => 30,
    ],
    'billables' => [
        [
            'model' => \Platform\Crm\Models\CrmContact::class,
            'type' => 'per_item',
            'label' => 'Kontakt',
            'description' => 'Jeder angelegte Kontakt verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.0025, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
        [
            'model' => \Platform\Crm\Models\CrmCompany::class,
            'type' => 'per_item',
            'label' => 'Unternehmen',
            'description' => 'Jedes angelegte Unternehmen verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                ['cost_per_day' => 0.005, 'start_date' => '2025-01-01', 'end_date' => null]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
    ],
];