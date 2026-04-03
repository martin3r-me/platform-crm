<?php

namespace Platform\Crm\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Platform\Crm\Models\CrmAcademicTitle;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactStatus;
use Platform\Crm\Models\CrmGender;
use Platform\Crm\Models\CrmLanguage;
use Platform\Crm\Models\CrmSalutation;
use Platform\Crm\Models\CrmCompany;

class ContactIndex extends Component
{
    public string $search = '';
    public $statusFilter = null;
    public string $blacklistFilter = 'not_blacklisted';
    public $companyFilter = null;
    public $languageFilter = null;
    public $genderFilter = null;
    public ?string $createdFrom = null;
    public ?string $createdTo = null;
    public string $sortField = 'last_name';
    public string $sortDirection = 'asc';
    public int $perPage = 50;
    public int $page = 1;
    public array $selected = [];
    public bool $selectAll = false;
    public bool $modalShow = false;

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

    // Quick-create: optional primary email + phone
    public string $primary_email = '';
    public string $primary_phone = '';

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedStatusFilter(): void { $this->page = 1; }
    public function updatedBlacklistFilter(): void { $this->page = 1; }
    public function updatedCompanyFilter(): void { $this->page = 1; }
    public function updatedLanguageFilter(): void { $this->page = 1; }
    public function updatedGenderFilter(): void { $this->page = 1; }
    public function updatedCreatedFrom(): void { $this->page = 1; }
    public function updatedCreatedTo(): void { $this->page = 1; }

    public function resetFilters(): void
    {
        $this->reset(['statusFilter', 'blacklistFilter', 'companyFilter', 'languageFilter', 'genderFilter', 'createdFrom', 'createdTo', 'search']);
        $this->blacklistFilter = 'not_blacklisted';
        $this->page = 1;
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return !empty($this->statusFilter)
            || $this->blacklistFilter !== 'not_blacklisted'
            || !empty($this->companyFilter)
            || !empty($this->languageFilter)
            || !empty($this->genderFilter)
            || !empty($this->createdFrom)
            || !empty($this->createdTo)
            || trim($this->search) !== '';
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    #[Computed]
    public function contacts()
    {
        $search = trim($this->search);

        return CrmContact::with(['contactStatus', 'emailAddresses', 'phoneNumbers', 'postalAddresses', 'contactRelations.company', 'gender', 'language'])
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
            ->when(! empty($this->companyFilter), fn ($q) => $q->whereHas('contactRelations', fn ($r) => $r->where('company_id', $this->companyFilter)))
            ->when(! empty($this->languageFilter), fn ($q) => $q->where('language_id', $this->languageFilter))
            ->when(! empty($this->genderFilter), fn ($q) => $q->where('gender_id', $this->genderFilter))
            ->when(! empty($this->createdFrom), fn ($q) => $q->whereDate('crm_contacts.created_at', '>=', $this->createdFrom))
            ->when(! empty($this->createdTo), fn ($q) => $q->whereDate('crm_contacts.created_at', '<=', $this->createdTo))
            ->when($this->sortField === 'last_name', fn ($q) => $q->orderBy('last_name', $this->sortDirection)->orderBy('first_name', $this->sortDirection))
            ->when($this->sortField === 'contact_status_id', fn ($q) => $q
                ->join('crm_contact_statuses', 'crm_contacts.contact_status_id', '=', 'crm_contact_statuses.id')
                ->select('crm_contacts.*')
                ->orderBy('crm_contact_statuses.name', $this->sortDirection))
            ->when($this->sortField === 'company', fn ($q) => $q
                ->leftJoin('crm_contact_relations', fn ($j) => $j->on('crm_contacts.id', '=', 'crm_contact_relations.contact_id')->where('crm_contact_relations.is_primary', true))
                ->leftJoin('crm_companies', 'crm_contact_relations.company_id', '=', 'crm_companies.id')
                ->select('crm_contacts.*')
                ->orderBy('crm_companies.name', $this->sortDirection))
            ->when(! in_array($this->sortField, ['last_name', 'contact_status_id', 'company']), fn ($q) => $q->orderBy($this->sortField, $this->sortDirection))
            ->take($this->perPage * $this->page)
            ->get();
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selected = $this->contacts->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function bulkChangeStatus($statusId): void
    {
        if (empty($this->selected)) return;

        CrmContact::whereIn('id', $this->selected)
            ->where('team_id', $this->getTeamId())
            ->update(['contact_status_id' => $statusId]);

        $this->selected = [];
        $this->selectAll = false;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) return;

        CrmContact::whereIn('id', $this->selected)
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
            'primary_email' => 'nullable|email|max:255',
            'primary_phone' => 'nullable|string|max:255',
        ]);

        $contact = CrmContact::create([
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

        if (trim($this->primary_email) !== '') {
            $contact->emailAddresses()->create([
                'email_address' => $this->primary_email,
                'email_type_id' => 1,
                'is_primary' => true,
                'is_active' => true,
            ]);
        }

        if (trim($this->primary_phone) !== '') {
            try {
                $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
                $phoneNumber = $phoneUtil->parse($this->primary_phone, 'DE');
                $contact->phoneNumbers()->create([
                    'raw_input' => $this->primary_phone,
                    'international' => $phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::E164),
                    'national' => $phoneUtil->format($phoneNumber, \libphonenumber\PhoneNumberFormat::NATIONAL),
                    'country_code' => $phoneUtil->getRegionCodeForNumber($phoneNumber) ?: 'DE',
                    'phone_type_id' => 1,
                    'is_primary' => true,
                ]);
            } catch (\Throwable $e) {
                // Store raw if parsing fails
                $contact->phoneNumbers()->create([
                    'raw_input' => $this->primary_phone,
                    'international' => $this->primary_phone,
                    'national' => $this->primary_phone,
                    'country_code' => 'DE',
                    'phone_type_id' => 1,
                    'is_primary' => true,
                ]);
            }
        }

        $this->resetForm();
        $this->modalShow = false;
        session()->flash('message', 'Kontakt erfolgreich erstellt!');
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
        // Store contact IDs for prev/next navigation on detail pages
        $this->storeNavigationContext();

        $teamId = $this->getTeamId();

        return view('crm::livewire.contact-index', [
            'contactStatuses' => CrmContactStatus::active()->get(),
            'salutations' => CrmSalutation::active()->get(),
            'academicTitles' => CrmAcademicTitle::active()->get(),
            'genders' => CrmGender::active()->get(),
            'languages' => CrmLanguage::active()->get(),
            'companiesForFilter' => CrmCompany::active()->where('team_id', $teamId)->orderBy('name')->get(),
        ])->layout('platform::layouts.app');
    }

    private function storeNavigationContext(): void
    {
        $ids = $this->contacts->pluck('id')->toArray();
        session()->put('crm.contact_nav', [
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
            'first_name', 'last_name', 'middle_name', 'nickname',
            'birth_date', 'notes', 'salutation_id', 'academic_title_id',
            'gender_id', 'language_id', 'contact_status_id',
            'primary_email', 'primary_phone',
        ]);
    }
}
