<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmContactStatus;
use Platform\Crm\Models\CrmCountry;
use Platform\Crm\Models\CrmIndustry;
use Platform\Crm\Models\CrmLegalForm;

class CompanyIndex extends Component
{
    public string $search = '';
    public $statusFilter = null;
    public string $sortField = 'display_name';
    public string $sortDirection = 'asc';
    public int $perPage = 50;
    public int $page = 1;
    public array $selected = [];
    public bool $selectAll = false;
    public bool $modalShow = false;

    // Company form fields
    public string $name = '';
    public string $legal_name = '';
    public string $trading_name = '';
    public string $registration_number = '';
    public string $tax_number = '';
    public string $vat_number = '';
    public string $website = '';
    public string $description = '';
    public string $notes = '';
    public $industry_id = '';
    public $legal_form_id = '';
    public $contact_status_id = '';
    public $country_id = '';

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedStatusFilter(): void
    {
        $this->page = 1;
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    #[Computed]
    public function companies()
    {
        $search = trim($this->search);

        return CrmCompany::with(['industry', 'legalForm', 'contactStatus', 'emailAddresses', 'phoneNumbers', 'postalAddresses', 'contactRelations.contact'])
            ->where('team_id', $this->getTeamId())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($sub) => $sub
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('trading_name', 'like', "%{$search}%")
                    ->orWhere('website', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('emailAddresses', fn ($e) => $e->where('email_address', 'like', "%{$search}%"))
                    ->orWhereHas('phoneNumbers', fn ($p) => $p->where('national', 'like', "%{$search}%")->orWhere('international', 'like', "%{$search}%"))
                );
            })
            ->when(! empty($this->statusFilter), fn ($q) => $q->where('contact_status_id', $this->statusFilter))
            ->when($this->sortField === 'display_name', fn ($q) => $q->orderBy('name', $this->sortDirection))
            ->when($this->sortField === 'contact_status_id', fn ($q) => $q
                ->join('crm_contact_statuses', 'crm_companies.contact_status_id', '=', 'crm_contact_statuses.id')
                ->select('crm_companies.*')
                ->orderBy('crm_contact_statuses.name', $this->sortDirection))
            ->when(! in_array($this->sortField, ['display_name', 'contact_status_id']), fn ($q) => $q->orderBy($this->sortField, $this->sortDirection))
            ->take($this->perPage * $this->page)
            ->get();
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selected = $this->companies->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function bulkChangeStatus($statusId): void
    {
        if (empty($this->selected)) return;

        CrmCompany::whereIn('id', $this->selected)
            ->where('team_id', $this->getTeamId())
            ->update(['contact_status_id' => $statusId]);

        $this->selected = [];
        $this->selectAll = false;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) return;

        CrmCompany::whereIn('id', $this->selected)
            ->where('team_id', $this->getTeamId())
            ->update(['is_active' => false]);

        $this->selected = [];
        $this->selectAll = false;
    }

    public function openCreateModal(): void
    {
        $this->modalShow = true;
    }

    public function closeCreateModal(): void
    {
        $this->modalShow = false;
        $this->resetForm();
    }

    public function createCompany(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'industry_id' => 'nullable|exists:crm_industries,id',
            'legal_form_id' => 'nullable|exists:crm_legal_forms,id',
            'contact_status_id' => 'required|exists:crm_contact_statuses,id',
            'country_id' => 'nullable|exists:crm_countries,id',
        ]);

        CrmCompany::create([
            'name' => $this->name,
            'legal_name' => $this->legal_name ?: null,
            'trading_name' => $this->trading_name ?: null,
            'registration_number' => $this->registration_number ?: null,
            'tax_number' => $this->tax_number ?: null,
            'vat_number' => $this->vat_number ?: null,
            'website' => $this->website ?: null,
            'description' => $this->description ?: null,
            'notes' => $this->notes ?: null,
            'industry_id' => $this->industry_id ?: null,
            'legal_form_id' => $this->legal_form_id ?: null,
            'contact_status_id' => $this->contact_status_id,
            'country_id' => $this->country_id ?: null,
            'team_id' => $this->getTeamId(),
            'created_by_user_id' => auth()->id(),
        ]);

        $this->resetForm();
        $this->modalShow = false;
        session()->flash('message', 'Unternehmen erfolgreich erstellt!');
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function render()
    {
        return view('crm::livewire.company-index', [
            'contactStatuses' => CrmContactStatus::active()->get(),
            'industries' => CrmIndustry::active()->get(),
            'legalForms' => CrmLegalForm::active()->get(),
            'countries' => CrmCountry::active()->get(),
        ])->layout('platform::layouts.app');
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
            'name', 'legal_name', 'trading_name', 'registration_number',
            'tax_number', 'vat_number', 'website', 'description', 'notes',
            'industry_id', 'legal_form_id', 'contact_status_id', 'country_id',
        ]);
    }
}
