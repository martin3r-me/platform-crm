<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Crm\Models\CrmEngagement;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;

class EngagementIndex extends Component
{
    public string $search = '';
    public $typeFilter = null;
    public $statusFilter = null;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    public int $perPage = 50;
    public int $page = 1;

    // Create Modal
    public bool $modalShow = false;
    public string $engagementType = 'note';
    public string $engagementTitle = '';
    public string $engagementBody = '';
    public ?string $engagementStatus = null;
    public ?string $engagementPriority = null;
    public ?string $engagementScheduledAt = null;
    public ?string $engagementEndedAt = null;
    public array $selectedContactIds = [];
    public array $selectedCompanyIds = [];

    public function updatedSearch(): void { $this->page = 1; }
    public function updatedTypeFilter(): void { $this->page = 1; }
    public function updatedStatusFilter(): void { $this->page = 1; }

    public function resetFilters(): void
    {
        $this->reset(['typeFilter', 'statusFilter', 'search']);
        $this->page = 1;
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return !empty($this->typeFilter)
            || !empty($this->statusFilter)
            || trim($this->search) !== '';
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    #[Computed]
    public function engagements()
    {
        $search = trim($this->search);

        return CrmEngagement::with(['companyLinks.company', 'contactLinks.contact', 'ownedByUser'])
            ->where('team_id', $this->getTeamId())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($sub) => $sub
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                );
            })
            ->when(!empty($this->typeFilter), fn ($q) => $q->where('type', $this->typeFilter))
            ->when(!empty($this->statusFilter), fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->sortField === 'scheduled_at', fn ($q) => $q->orderByRaw('scheduled_at IS NULL, scheduled_at ' . $this->sortDirection))
            ->when($this->sortField !== 'scheduled_at', fn ($q) => $q->orderBy($this->sortField, $this->sortDirection))
            ->take($this->perPage * $this->page)
            ->get();
    }

    public function openCreateModal(string $type = 'note'): void
    {
        $this->engagementType = $type;
        $this->modalShow = true;
    }

    public function closeCreateModal(): void
    {
        $this->modalShow = false;
        $this->resetForm();
    }

    public function createEngagement(): void
    {
        $rules = [
            'engagementType' => 'required|in:note,call,meeting,task',
            'engagementTitle' => 'required|string|max:255',
            'engagementBody' => 'nullable|string',
        ];

        if (in_array($this->engagementType, ['call', 'meeting', 'task'])) {
            $rules['engagementStatus'] = 'nullable|string|max:50';
        }
        if (in_array($this->engagementType, ['meeting', 'task'])) {
            $rules['engagementScheduledAt'] = 'nullable|date';
        }
        if ($this->engagementType === 'meeting') {
            $rules['engagementEndedAt'] = 'nullable|date';
        }
        if ($this->engagementType === 'task') {
            $rules['engagementPriority'] = 'nullable|string|max:50';
        }

        $this->validate($rules);

        $engagement = CrmEngagement::create([
            'type' => $this->engagementType,
            'title' => $this->engagementTitle,
            'body' => $this->engagementBody ?: null,
            'status' => $this->engagementStatus ?: null,
            'priority' => $this->engagementPriority ?: null,
            'scheduled_at' => $this->engagementScheduledAt ?: null,
            'ended_at' => $this->engagementEndedAt ?: null,
            'owned_by_user_id' => auth()->id(),
            'created_by_user_id' => auth()->id(),
            'team_id' => $this->getTeamId(),
        ]);

        // Attach contacts
        foreach ($this->selectedContactIds as $contactId) {
            $contact = CrmContact::find($contactId);
            if ($contact) {
                $engagement->attachContact($contact);
            }
        }

        // Attach companies
        foreach ($this->selectedCompanyIds as $companyId) {
            $company = CrmCompany::find($companyId);
            if ($company) {
                $engagement->attachCompany($company);
            }
        }

        $this->resetForm();
        $this->modalShow = false;
        session()->flash('message', 'Engagement erfolgreich erstellt!');
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function render()
    {
        $this->storeNavigationContext();

        $teamId = $this->getTeamId();

        return view('crm::livewire.engagement-index', [
            'contactsForSelect' => CrmContact::active()->where('team_id', $teamId)->orderBy('first_name')->orderBy('last_name')->get(),
            'companiesForSelect' => CrmCompany::active()->where('team_id', $teamId)->orderBy('name')->get(),
        ])->layout('platform::layouts.app');
    }

    private function storeNavigationContext(): void
    {
        $ids = $this->engagements->pluck('id')->toArray();
        session()->put('crm.engagement_nav', [
            'ids' => $ids,
            'sort' => $this->sortField,
            'dir' => $this->sortDirection,
        ]);
    }

    private function getTeamId(): int
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }

    private function resetForm(): void
    {
        $this->reset([
            'engagementType', 'engagementTitle', 'engagementBody',
            'engagementStatus', 'engagementPriority',
            'engagementScheduledAt', 'engagementEndedAt',
            'selectedContactIds', 'selectedCompanyIds',
        ]);
        $this->engagementType = 'note';
    }
}
