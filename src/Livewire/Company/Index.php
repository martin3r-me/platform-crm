<?php

namespace Platform\Crm\Livewire\Company;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmIndustry;
use Platform\Crm\Models\CrmLegalForm;
use Platform\Crm\Models\CrmContactStatus;
use Platform\Crm\Models\CrmCountry;

class Index extends Component
{
    use WithPagination;

    // Modal State
    public $modalShow = false;
    
    // Form Data
    public $name = '';
    public $legal_name = '';
    public $trading_name = '';
    public $registration_number = '';
    public $tax_number = '';
    public $vat_number = '';
    public $website = '';
    public $description = '';
    public $notes = '';
    public $industry_id = '';
    public $legal_form_id = '';
    public $contact_status_id = '';
    public $country_id = '';

    protected $rules = [
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
    ];

    public function render()
    {
        $companies = CrmCompany::with(['industry', 'legalForm', 'contactStatus'])
            ->paginate(10);
            
        $industries = CrmIndustry::active()->get();
        $legalForms = CrmLegalForm::active()->get();
        $contactStatuses = CrmContactStatus::active()->get();
        $countries = CrmCountry::active()->get();

        return view('crm::livewire.company.index', [
            'companies' => $companies,
            'industries' => $industries,
            'legalForms' => $legalForms,
            'contactStatuses' => $contactStatuses,
            'countries' => $countries,
        ])->layout('platform::layouts.app');
    }

    public function createCompany()
    {
        $this->validate();
        
        $company = CrmCompany::create([
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
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->resetForm();
        $this->modalShow = false;
        
        session()->flash('message', 'Unternehmen erfolgreich erstellt!');
    }

    public function resetForm()
    {
        $this->reset([
            'name', 'legal_name', 'trading_name', 'registration_number',
            'tax_number', 'vat_number', 'website', 'description', 'notes',
            'industry_id', 'legal_form_id', 'contact_status_id', 'country_id'
        ]);
    }

    public function openCreateModal()
    {
        $this->modalShow = true;
    }

    public function closeCreateModal()
    {
        $this->modalShow = false;
        $this->resetForm();
    }
}