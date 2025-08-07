<?php

namespace Platform\Crm\Livewire;

use Livewire\Component;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;

class Dashboard extends Component
{
    public function render()
    {
        $stats = [
            'contacts' => CrmContact::count(),
            'companies' => CrmCompany::count(),
            'recent_contacts' => CrmContact::latest()->take(5)->get(),
            'recent_companies' => CrmCompany::latest()->take(5)->get(),
        ];

        return view('crm::livewire.dashboard', [
            'stats' => $stats
        ])->layout('platform::layouts.app');
    }
}