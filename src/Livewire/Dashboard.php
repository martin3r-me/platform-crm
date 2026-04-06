<?php

namespace Platform\Crm\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmFollowUp;
use Platform\Crm\Models\CrmContactStatus;
use Platform\ActivityLog\Models\ActivityLogActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Dashboard extends Component
{
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
        if (!$teamId) return 0;
        return CrmContact::active()->where('team_id', $teamId)->count();
    }

    #[Computed]
    public function totalCompanies()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return CrmCompany::active()->where('team_id', $teamId)->count();
    }

    #[Computed]
    public function newContactsThisWeek()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return CrmContact::active()->where('team_id', $teamId)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
    }

    #[Computed]
    public function newContactsLastWeek()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return CrmContact::active()->where('team_id', $teamId)
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();
    }

    #[Computed]
    public function newCompaniesThisWeek()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return CrmCompany::active()->where('team_id', $teamId)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
    }

    #[Computed]
    public function overdueFollowUpsCount()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return 0;
        return CrmFollowUp::forTeam($teamId)->overdue()->count();
    }

    #[Computed]
    public function overdueFollowUps()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();
        return CrmFollowUp::forTeam($teamId)->overdue()
            ->with('followupable')
            ->orderBy('due_date')
            ->take(10)
            ->get();
    }

    #[Computed]
    public function upcomingFollowUps()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();
        return CrmFollowUp::forTeam($teamId)->upcoming(7)
            ->with('followupable')
            ->orderBy('due_date')
            ->take(10)
            ->get();
    }

    #[Computed]
    public function contactsByStatus()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();
        return CrmContact::where('crm_contacts.is_active', true)
            ->where('crm_contacts.team_id', $teamId)
            ->join('crm_contact_statuses', 'crm_contacts.contact_status_id', '=', 'crm_contact_statuses.id')
            ->select('crm_contact_statuses.name', 'crm_contact_statuses.code', DB::raw('count(*) as count'))
            ->groupBy('crm_contact_statuses.id', 'crm_contact_statuses.name', 'crm_contact_statuses.code')
            ->orderBy('count', 'desc')
            ->get();
    }

    #[Computed]
    public function recentContacts()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();
        return CrmContact::active()
            ->where('team_id', $teamId)
            ->with('contactStatus')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentCompanies()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();
        return CrmCompany::active()
            ->where('team_id', $teamId)
            ->with('contactStatus')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function recentActivities()
    {
        $teamId = $this->getTeamId();
        if (!$teamId) return collect();

        $morphTypes = [
            (new CrmContact)->getMorphClass(),
            (new CrmCompany)->getMorphClass(),
        ];

        $activities = ActivityLogActivity::query()
            ->whereIn('activityable_type', $morphTypes)
            ->where(function ($q) use ($teamId, $morphTypes) {
                foreach ($morphTypes as $type) {
                    $model = app($type);
                    $q->orWhere(function ($sub) use ($type, $teamId, $model) {
                        $sub->where('activityable_type', $type)
                            ->whereIn('activityable_id', $model::query()->where('team_id', $teamId)->select('id'));
                    });
                }
            })
            ->with('user')
            ->latest()
            ->take(20)
            ->get();

        $activities->groupBy('activityable_type')->each(function ($group, $type) {
            $ids = $group->pluck('activityable_id')->unique();
            $models = $type::whereIn('id', $ids)->get()->keyBy('id');
            $group->each(fn ($a) => $a->setRelation('activityable', $models->get($a->activityable_id)));
        });

        return $activities;
    }

    public function toggleFollowUp(int $id): void
    {
        $teamId = $this->getTeamId();
        $followUp = CrmFollowUp::forTeam($teamId)->findOrFail($id);
        $followUp->update([
            'completed_at' => $followUp->completed_at ? null : now(),
        ]);
    }

    public function render()
    {
        return view('crm::livewire.dashboard')
            ->layout('platform::layouts.app');
    }
}
