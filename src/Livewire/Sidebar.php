<?php

namespace Platform\Crm\Livewire;

use Livewire\Component;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;
use Illuminate\Support\Facades\Auth;

class Sidebar extends Component
{
    public function render()
    {
        $user = Auth::user();
        $baseTeam = $user?->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;

        // Neueste Kontakte für Schnellzugriff
        $recentContacts = $teamId
            ? CrmContact::active()
                ->where('team_id', $teamId)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get()
            : collect();

        // Neueste Unternehmen für Schnellzugriff
        $recentCompanies = $teamId
            ? CrmCompany::active()
                ->where('team_id', $teamId)
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get()
            : collect();

        return view('crm::livewire.sidebar', [
            'recentContacts' => $recentContacts,
            'recentCompanies' => $recentCompanies,
        ])->layout('platform::layouts.app');
    }
}
