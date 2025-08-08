<?php

namespace Platform\Crm\Livewire;

use Livewire\Component;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;

class Sidebar extends Component
{
    public function createContact()
    {
        return redirect()->route('crm.contacts.index');
    }

    public function createCompany()
    {
        return redirect()->route('crm.companies.index');
    }

    public function render()
    {
        // Neueste Kontakte für Schnellzugriff
        $recentContacts = CrmContact::active()
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // Neueste Unternehmen für Schnellzugriff
        $recentCompanies = CrmCompany::active()
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return view('crm::livewire.sidebar', [
            'recentContacts' => $recentContacts,
            'recentCompanies' => $recentCompanies,
        ])->layout('platform::layouts.app');
    }
}