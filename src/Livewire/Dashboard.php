<?php

namespace Platform\Crm\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmEmailAddress;
use Platform\Crm\Models\CrmPhoneNumber;
use Platform\Crm\Models\CrmPostalAddress;
use Platform\Crm\Models\CrmContactRelation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $perspective = 'personal'; // 'personal' oder 'team'

    public function togglePerspective()
    {
        $this->perspective = $this->perspective === 'personal' ? 'team' : 'personal';
    }

    private function getTeamId(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        $baseTeam = $user->currentTeamRelation;
        return $baseTeam ? $baseTeam->getRootTeam()->id : null;
    }

    #[Computed]
    public function totalContacts()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return 0;
        }
        return CrmContact::active()->where('team_id', $teamId)->count();
    }

    #[Computed]
    public function totalCompanies()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return 0;
        }
        return CrmCompany::active()->where('team_id', $teamId)->count();
    }

    #[Computed]
    public function totalEmailAddresses()
    {
        return CrmEmailAddress::where('is_active', true)->count();
    }

    #[Computed]
    public function totalPhoneNumbers()
    {
        return CrmPhoneNumber::where('is_active', true)->count();
    }

    #[Computed]
    public function totalPostalAddresses()
    {
        return CrmPostalAddress::where('is_active', true)->count();
    }

    #[Computed]
    public function totalRelations()
    {
        return CrmContactRelation::where('is_active', true)->count();
    }

    #[Computed]
    public function primaryEmailAddresses()
    {
        return CrmEmailAddress::where('is_active', true)
            ->where('is_primary', true)
            ->count();
    }

    #[Computed]
    public function primaryPhoneNumbers()
    {
        return CrmPhoneNumber::where('is_active', true)
            ->where('is_primary', true)
            ->count();
    }

    #[Computed]
    public function primaryPostalAddresses()
    {
        return CrmPostalAddress::where('is_active', true)
            ->where('is_primary', true)
            ->count();
    }

    #[Computed]
    public function contactsWithoutEmail()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return 0;
        }
        return CrmContact::active()
            ->where('team_id', $teamId)
            ->whereDoesntHave('emailAddresses')
            ->count();
    }

    #[Computed]
    public function contactsWithoutPhone()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return 0;
        }
        return CrmContact::active()
            ->where('team_id', $teamId)
            ->whereDoesntHave('phoneNumbers')
            ->count();
    }

    #[Computed]
    public function companiesWithoutEmail()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return 0;
        }
        return CrmCompany::active()
            ->where('team_id', $teamId)
            ->whereDoesntHave('emailAddresses')
            ->count();
    }

    #[Computed]
    public function companiesWithoutPhone()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return 0;
        }
        return CrmCompany::active()
            ->where('team_id', $teamId)
            ->whereDoesntHave('phoneNumbers')
            ->count();
    }

    #[Computed]
    public function recentContacts()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return collect();
        }
        return CrmContact::active()
            ->where('team_id', $teamId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentCompanies()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return collect();
        }
        return CrmCompany::active()
            ->where('team_id', $teamId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function topContactStatuses()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) {
            return collect();
        }
        return CrmContact::where('crm_contacts.is_active', true)
            ->where('crm_contacts.team_id', $teamId)
            ->join('crm_contact_statuses', 'crm_contacts.contact_status_id', '=', 'crm_contact_statuses.id')
            ->select('crm_contact_statuses.name', DB::raw('count(*) as count'))
            ->groupBy('crm_contact_statuses.id', 'crm_contact_statuses.name')
            ->orderBy('count', 'desc')
            ->take(3)
            ->get();
    }

    public function render()
    {
        return view('crm::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}