<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Platform\Crm\Models\CrmAcademicTitle;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactStatus;
use Platform\Crm\Models\CrmCountry;
use Platform\Crm\Models\CrmGender;
use Platform\Crm\Models\CrmIndustry;
use Platform\Crm\Models\CrmLanguage;
use Platform\Crm\Models\CrmLegalForm;
use Platform\Crm\Models\CrmSalutation;

class CrmIndex extends Component
{
    // Tab
    #[Url(as: 'tab')]
    public string $activeTab = 'contacts';

    // Search
    public string $search = '';

    // Filters
    public $statusFilter = null;
    public string $blacklistFilter = 'not_blacklisted';

    // Sorting (tab-dependent defaults set in mount)
    public string $sortField = 'last_name';
    public string $sortDirection = 'asc';

    // Infinite scroll
    public int $perPage = 50;
    public int $page = 1;

    // Bulk selection
    public array $selected = [];
    public bool $selectAll = false;

    // Modal state
    public bool $modalShow = false;
    public string $createType = 'contact';

    // Contact form fields
    public string $first_name = '';
    public string $last_name = '';
    public string $middle_name = '';
    public string $nickname = '';
    public string $birth_date = '';
    public string $notes = '';
    public $salutation_id = '';
    public $academic_title_id = '';
    public $gender_id = '';
    public $language_id = '';
    public $contact_status_id = '';

    // Company form fields
    public string $company_name = '';
    public string $legal_name = '';
    public string $trading_name = '';
    public string $registration_number = '';
    public string $tax_number = '';
    public string $vat_number = '';
    public string $website = '';
    public string $description = '';
    public string $company_notes = '';
    public $industry_id = '';
    public $legal_form_id = '';
    public $company_contact_status_id = '';
    public $country_id = '';

    public function mount(): void
    {
        if ($this->activeTab === 'companies') {
            $this->sortField = 'display_name';
        }
    }

    public function updatedActiveTab(): void
    {
        $this->search = '';
        $this->statusFilter = null;
        $this->blacklistFilter = 'not_blacklisted';
        $this->sortField = $this->activeTab === 'contacts' ? 'last_name' : 'display_name';
        $this->sortDirection = 'asc';
        $this->page = 1;
        $this->selected = [];
        $this->selectAll = false;
    }

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

    public function updatedBlacklistFilter(): void
    {
        $this->page = 1;
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    #[Computed]
    public function contacts()
    {
        $search = trim($this->search);

        return CrmContact::with(['contactStatus', 'emailAddresses', 'phoneNumbers', 'postalAddresses', 'contactRelations.company'])
            ->where('team_id', $this->getTeamId())
            ->when($search !== '', function ($query) use ($search) {
                $query->where(fn ($sub) => $sub
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('nickname', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhereHas('emailAddresses', fn ($e) => $e->where('email_address', 'like', "%{$search}%"))
                    ->orWhereHas('phoneNumbers', fn ($p) => $p->where('national', 'like', "%{$search}%")->orWhere('international', 'like', "%{$search}%"))
                    ->orWhereHas('contactRelations', fn ($r) => $r->whereHas('company', fn ($c) => $c->where('name', 'like', "%{$search}%")))
                );
            })
            ->when(! empty($this->statusFilter), fn ($q) => $q->where('contact_status_id', $this->statusFilter))
            ->when($this->blacklistFilter === 'not_blacklisted', fn ($q) => $q->where('is_blacklisted', false))
            ->when($this->blacklistFilter === 'blacklisted', fn ($q) => $q->where('is_blacklisted', true))
            ->when($this->sortField === 'last_name', fn ($q) => $q->orderBy('last_name', $this->sortDirection)->orderBy('first_name', $this->sortDirection))
            ->when($this->sortField === 'contact_status_id', fn ($q) => $q
                ->join('crm_contact_statuses', 'crm_contacts.contact_status_id', '=', 'crm_contact_statuses.id')
                ->select('crm_contacts.*')
                ->orderBy('crm_contact_statuses.name', $this->sortDirection))
            ->when(! in_array($this->sortField, ['last_name', 'contact_status_id']), fn ($q) => $q->orderBy($this->sortField, $this->sortDirection))
            ->take($this->perPage * $this->page)
            ->get();
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

    #[Computed]
    public function contactCount(): int
    {
        return CrmContact::where('team_id', $this->getTeamId())->count();
    }

    #[Computed]
    public function companyCount(): int
    {
        return CrmCompany::where('team_id', $this->getTeamId())->count();
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $items = $this->activeTab === 'contacts' ? $this->contacts : $this->companies;
            $this->selected = $items->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function bulkChangeStatus($statusId): void
    {
        if (empty($this->selected)) {
            return;
        }

        $model = $this->activeTab === 'contacts' ? CrmContact::class : CrmCompany::class;
        $model::whereIn('id', $this->selected)
            ->where('team_id', $this->getTeamId())
            ->update(['contact_status_id' => $statusId]);

        $this->selected = [];
        $this->selectAll = false;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $model = $this->activeTab === 'contacts' ? CrmContact::class : CrmCompany::class;
        $model::whereIn('id', $this->selected)
            ->where('team_id', $this->getTeamId())
            ->update(['is_active' => false]);

        $this->selected = [];
        $this->selectAll = false;
        session()->flash('message', count($this->selected) . ' Einträge deaktiviert.');
    }

    public function openCreateModal(string $type = 'contact'): void
    {
        $this->createType = $type;
        $this->modalShow = true;
    }

    public function closeCreateModal(): void
    {
        $this->modalShow = false;
        $this->resetContactForm();
        $this->resetCompanyForm();
    }

    public function createContact(): void
    {
        $this->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'nickname' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'salutation_id' => 'nullable|exists:crm_salutations,id',
            'academic_title_id' => 'nullable|exists:crm_academic_titles,id',
            'gender_id' => 'nullable|exists:crm_genders,id',
            'language_id' => 'nullable|exists:crm_languages,id',
            'contact_status_id' => 'required|exists:crm_contact_statuses,id',
        ]);

        CrmContact::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'nickname' => $this->nickname,
            'birth_date' => $this->birth_date ?: null,
            'notes' => $this->notes,
            'salutation_id' => $this->salutation_id ?: null,
            'academic_title_id' => $this->academic_title_id ?: null,
            'gender_id' => $this->gender_id ?: null,
            'language_id' => $this->language_id ?: null,
            'contact_status_id' => $this->contact_status_id,
            'team_id' => $this->getTeamId(),
            'created_by_user_id' => auth()->id(),
        ]);

        $this->resetContactForm();
        $this->modalShow = false;
        session()->flash('message', 'Kontakt erfolgreich erstellt!');
    }

    public function createCompany(): void
    {
        $this->validate([
            'company_name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'registration_number' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string',
            'company_notes' => 'nullable|string',
            'industry_id' => 'nullable|exists:crm_industries,id',
            'legal_form_id' => 'nullable|exists:crm_legal_forms,id',
            'company_contact_status_id' => 'required|exists:crm_contact_statuses,id',
            'country_id' => 'nullable|exists:crm_countries,id',
        ]);

        CrmCompany::create([
            'name' => $this->company_name,
            'legal_name' => $this->legal_name ?: null,
            'trading_name' => $this->trading_name ?: null,
            'registration_number' => $this->registration_number ?: null,
            'tax_number' => $this->tax_number ?: null,
            'vat_number' => $this->vat_number ?: null,
            'website' => $this->website ?: null,
            'description' => $this->description ?: null,
            'notes' => $this->company_notes ?: null,
            'industry_id' => $this->industry_id ?: null,
            'legal_form_id' => $this->legal_form_id ?: null,
            'contact_status_id' => $this->company_contact_status_id,
            'country_id' => $this->country_id ?: null,
            'team_id' => $this->getTeamId(),
            'created_by_user_id' => auth()->id(),
        ]);

        $this->resetCompanyForm();
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
        $contactStatuses = CrmContactStatus::active()->get();

        $viewData = [
            'contactStatuses' => $contactStatuses,
        ];

        if ($this->activeTab === 'contacts') {
            $viewData['salutations'] = CrmSalutation::active()->get();
            $viewData['academicTitles'] = CrmAcademicTitle::active()->get();
            $viewData['genders'] = CrmGender::active()->get();
            $viewData['languages'] = CrmLanguage::active()->get();
        } else {
            $viewData['industries'] = CrmIndustry::active()->get();
            $viewData['legalForms'] = CrmLegalForm::active()->get();
            $viewData['countries'] = CrmCountry::active()->get();
        }

        return view('crm::livewire.crm-index', $viewData)
            ->layout('platform::layouts.app');
    }

    private function getTeamId(): int
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }

    private function resetContactForm(): void
    {
        $this->reset([
            'first_name', 'last_name', 'middle_name', 'nickname',
            'birth_date', 'notes', 'salutation_id', 'academic_title_id',
            'gender_id', 'language_id', 'contact_status_id',
        ]);
    }

    private function resetCompanyForm(): void
    {
        $this->reset([
            'company_name', 'legal_name', 'trading_name', 'registration_number',
            'tax_number', 'vat_number', 'website', 'description', 'company_notes',
            'industry_id', 'legal_form_id', 'company_contact_status_id', 'country_id',
        ]);
    }
}
