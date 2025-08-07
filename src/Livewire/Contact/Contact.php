<?php

namespace Platform\Crm\Livewire\Contact;

use Livewire\Component;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmSalutation;
use Platform\Crm\Models\CrmAcademicTitle;
use Platform\Crm\Models\CrmGender;
use Platform\Crm\Models\CrmLanguage;
use Platform\Crm\Models\CrmContactStatus;

class Contact extends Component
{
    public CrmContact $contact;
    public bool $edit = false;

    public $salutations = [];
    public $academicTitles = [];
    public $genders = [];
    public $languages = [];
    public $contactStatuses = [];

    public function mount(CrmContact $contact)
    {
        $this->contact = $contact;
        $this->edit = request()->boolean('edit');

        $this->salutations = CrmSalutation::active()->get();
        $this->academicTitles = CrmAcademicTitle::active()->get();
        $this->genders = CrmGender::active()->get();
        $this->languages = CrmLanguage::active()->get();
        $this->contactStatuses = CrmContactStatus::active()->get();
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->contact),
            'modelId' => $this->contact->id,
            'subject' => $this->contact->name,
            'description' => '',
            'url' => route('crm.contacts.show', $this->contact),
            'source' => 'crm.contacts.view'
        ]);
    }

    public function rules(): array
    {
        return [
            'contact.first_name' => 'required|string|max:255',
            'contact.last_name' => 'required|string|max:255',
            'contact.middle_name' => 'nullable|string|max:255',
            'contact.nickname' => 'nullable|string|max:255',
            'contact.birth_date' => 'nullable|date',
            'contact.notes' => 'nullable|string|max:1000',
            'contact.salutation_id' => 'nullable|exists:crm_salutations,id',
            'contact.academic_title_id' => 'nullable|exists:crm_academic_titles,id',
            'contact.gender_id' => 'nullable|exists:crm_genders,id',
            'contact.language_id' => 'nullable|exists:crm_languages,id',
            'contact.contact_status_id' => 'required|exists:crm_contact_statuses,id',
        ];
    }

    public function toggleEdit(): void
    {
        $this->edit = !$this->edit;
    }

    public function cancelEdit(): void
    {
        $this->edit = false;
    }

    public function save(): void
    {
        $this->validate();
        $this->contact->save();

        $this->edit = false;
        session()->flash('message', 'Kontakt erfolgreich aktualisiert.');
    }

    public function render()
    {
        return view('crm::livewire.contact.contact')
            ->layout('platform::layouts.app');
    }
}