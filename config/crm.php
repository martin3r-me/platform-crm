<?php
return [
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