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

class Dashboard extends Component
{
    public $perspective = 'personal'; // 'personal' oder 'team'

    public function togglePerspective()
    {
        $this->perspective = $this->perspective === 'personal' ? 'team' : 'personal';
    }

    #[Computed]
    public function totalContacts()
    {
        return CrmContact::active()->count();
    }

    #[Computed]
    public function totalCompanies()
    {
        return CrmCompany::active()->count();
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
        return CrmContact::active()
            ->whereDoesntHave('emailAddresses')
            ->count();
    }

    #[Computed]
    public function contactsWithoutPhone()
    {
        return CrmContact::active()
            ->whereDoesntHave('phoneNumbers')
            ->count();
    }

    #[Computed]
    public function companiesWithoutEmail()
    {
        return CrmCompany::active()
            ->whereDoesntHave('emailAddresses')
            ->count();
    }

    #[Computed]
    public function companiesWithoutPhone()
    {
        return CrmCompany::active()
            ->whereDoesntHave('phoneNumbers')
            ->count();
    }

    #[Computed]
    public function recentContacts()
    {
        return CrmContact::active()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentCompanies()
    {
        return CrmCompany::active()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function topContactStatuses()
    {
        return CrmContact::where('crm_contacts.is_active', true)
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