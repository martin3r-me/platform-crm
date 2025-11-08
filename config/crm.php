<?php
return [
    // Scope-Type: 'parent' = root-scoped (immer Root-Team-ID), 'single' = team-spezifisch
    'scope_type' => 'parent',
    
    'routing' => [
        'mode' => env('CRM_MODE', 'subdomain'),  // Standard: Subdomain
        'prefix' => 'crm',                       // Wird nur genutzt, wenn 'path'
    ],
    'guard' => 'web',

    'navigation' => [
        'route' => 'crm.dashboard',
        'icon'  => 'heroicon-o-ticket',
        'order' => 30,
    ],
];