<?php

namespace Platform\Crm\Livewire\Contact;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmSalutation;
use Platform\Crm\Models\CrmAcademicTitle;
use Platform\Crm\Models\CrmGender;
use Platform\Crm\Models\CrmLanguage;
use Platform\Crm\Models\CrmContactStatus;

class Index extends Component
{
    use WithPagination;

    // Modal State
    public $modalShow = false;
    
    // Form Data
    public $first_name = '';
    public $last_name = '';
    public $middle_name = '';
    public $nickname = '';
    public $birth_date = '';
    public $notes = '';
    public $salutation_id = '';
    public $academic_title_id = '';
    public $gender_id = '';
    public $language_id = '';
    public $contact_status_id = '';

    protected $rules = [
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
    ];

    public function render()
    {
        $contacts = CrmContact::with(['contactStatus', 'emailAddresses'])
            ->paginate(10);
            
        $salutations = CrmSalutation::active()->get();
        $academicTitles = CrmAcademicTitle::active()->get();
        $genders = CrmGender::active()->get();
        $languages = CrmLanguage::active()->get();
        $contactStatuses = CrmContactStatus::active()->get();

        return view('crm::livewire.contact.index', [
            'contacts' => $contacts,
            'salutations' => $salutations,
            'academicTitles' => $academicTitles,
            'genders' => $genders,
            'languages' => $languages,
            'contactStatuses' => $contactStatuses,
        ])->layout('platform::layouts.app');
    }

    public function createContact()
    {
        $this->validate();
        
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
            'team_id' => auth()->user()->current_team_id,
            'created_by_user_id' => auth()->id(),
        ]);

        $this->resetForm();
        $this->modalShow = false;
        
        session()->flash('message', 'Kontakt erfolgreich erstellt!');
    }

    public function resetForm()
    {
        $this->reset([
            'first_name', 'last_name', 'middle_name', 'nickname',
            'birth_date', 'notes', 'salutation_id', 'academic_title_id',
            'gender_id', 'language_id', 'contact_status_id'
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