<?php

namespace Platform\Crm\Livewire\Company;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmEmailType;
use Platform\Crm\Models\CrmPhoneType;
use Platform\Crm\Models\CrmAddressType;
use Platform\Crm\Models\CrmCountry;
use Platform\Crm\Models\CrmState;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactRelation;
use Platform\Crm\Models\CrmContactRelationType;
use Platform\Crm\Models\CrmLegalForm;
use Platform\Crm\Models\CrmFollowUp;
use Platform\Crm\Models\CrmEngagement;
use Platform\Crm\Models\CrmAccountPotential;
use Platform\Crm\Models\CrmCompanyLink;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;

class Company extends Component
{
    public CrmCompany $company;
    public string $activeTab = 'stammdaten';
    public $mode = 'show'; // 'show' oder 'edit'
    
    // Properties für die rechte Spalte
    public $contactStatuses;
    public $emailTypes = [];
    public $phoneTypes = [];
    public $addressTypes = [];
    public $countries = [];
    public $states = [];
    public $relationTypes = [];
    public $contacts = [];
    public $legalForms = [];
    public string $newNote = '';

    // Follow-up Form
    public $followUpForm = [
        'title' => '',
        'due_date' => null,
    ];

    // Prev/Next navigation
    public ?int $prevCompanyId = null;
    public ?int $nextCompanyId = null;
    
    // E-Mail Modals
    public $emailCreateModalShow = false;
    public $emailEditModalShow = false;
    public $editingEmailId = null;
    
    // Telefonnummer-Modals
    public $phoneCreateModalShow = false;
    public $phoneEditModalShow = false;
    public $editingPhoneId = null;
    
    // Adress-Modals
    public $addressCreateModalShow = false;
    public $addressEditModalShow = false;
    public $editingAddressId = null;
    
    // Kontakt-Beziehungs-Modals
    public $contactCreateModalShow = false;
    public $contactEditModalShow = false;
    public $editingContactRelationId = null;
    
    // E-Mail Form Properties
    public $emailForm = [
        'email_address' => '',
        'email_type_id' => 1,
        'is_primary' => false,
    ];
    
    // Telefonnummer-Form
    public $phoneForm = [
        'raw_input' => '',
        'country_code' => 'DE',
        'phone_type_id' => 1,
        'is_primary' => false,
    ];
    
    // Address Form Properties
    public $addressForm = [
        'street' => '',
        'house_number' => '',
        'postal_code' => '',
        'city' => '',
        'additional_info' => '',
        'country_id' => null,
        'state_id' => null,
        'address_type_id' => 1,
        'is_primary' => false,
    ];
    
    // Engagement Modal
    public bool $engagementCreateModalShow = false;
    public $engagementForm = [
        'type' => 'note',
        'title' => '',
        'body' => '',
        'status' => null,
        'priority' => null,
        'scheduled_at' => null,
    ];

    // Potenzial
    public $potentialForm = [
        'target_revenue' => null,
        'additional_potential' => null,
        'strategic_potential' => null,
        'confidence' => null,
        'notes' => '',
    ];
    public ?int $currentPotentialId = null;
    public bool $potentialCreateModalShow = false;
    public $potentialHistoryForm = [
        'year' => null,
        'target_revenue' => null,
        'additional_potential' => null,
        'strategic_potential' => null,
        'confidence' => null,
        'notes' => '',
    ];

    // Kontakt-Beziehungs-Form
    public $contactRelationForm = [
        'contact_id' => null,
        'relation_type_id' => 1,
        'position' => '',
        'start_date' => null,
        'end_date' => null,
        'is_primary' => false,
        'notes' => '',
    ];
    
    protected $rules = [
        'company.name' => 'required|string|max:255',
        'company.legal_name' => 'nullable|string|max:255',
        'company.trading_name' => 'nullable|string|max:255',
        'company.registration_number' => 'nullable|string|max:255',
        'company.tax_number' => 'nullable|string|max:255',
        'company.vat_number' => 'nullable|string|max:255',
        'company.website' => 'nullable|url|max:255',
        'company.description' => 'nullable|string',
        'company.notes' => 'nullable|string',
        'company.legal_form_id' => 'nullable|integer|exists:crm_legal_forms,id',
        'company.contact_status_id' => 'required|integer|exists:crm_contact_statuses,id',
    ];

    public function mount(CrmCompany $company = null, $mode = 'show')
    {
        if ($company) {
            $this->company = $company->load(['phoneNumbers', 'emailAddresses', 'postalAddresses', 'contactRelations.contact', 'activities.user', 'followUps']);
        }
        $this->mode = $mode;
        
        // Daten für die rechte Spalte laden
        $this->contactStatuses = \Platform\Crm\Models\CrmContactStatus::active()->get();
        $this->emailTypes = CrmEmailType::active()->get();
        $this->phoneTypes = CrmPhoneType::active()->get();
        $this->addressTypes = CrmAddressType::active()->get();
        $this->countries = CrmCountry::active()->get();
        $this->states = CrmState::active()->get();
        $this->relationTypes = CrmContactRelationType::active()->get();
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;
        $this->contacts = $teamId 
            ? CrmContact::active()->where('team_id', $teamId)->orderBy('first_name')->orderBy('last_name')->get()
            : collect();
        $this->legalForms = CrmLegalForm::active()->get();
        
        // Setze Deutschland als Standard-Land für neue Adressen
        $germany = CrmCountry::where('code', 'DE')->first();
        if ($germany) {
            $this->addressForm['country_id'] = $germany->id;
        }
        
        // Setze aktuelles Datum als Standard-Startdatum
        $this->contactRelationForm['start_date'] = now()->toDateString();

        // Prev/Next navigation from index list
        $nav = session('crm.company_nav');
        if ($nav && !empty($nav['ids'])) {
            $ids = $nav['ids'];
            $pos = array_search($this->company->id, $ids);
            if ($pos !== false) {
                $this->prevCompanyId = $pos > 0 ? $ids[$pos - 1] : null;
                $this->nextCompanyId = $pos < count($ids) - 1 ? $ids[$pos + 1] : null;
            }
        }
    }

    public function rendered()
    {
        $this->dispatch('comms', [
            'model' => get_class($this->company),
            'modelId' => $this->company->id,
            'subject' => $this->company->name,
            'description' => '',
            'url' => route('crm.companies.show', $this->company),
            'source' => 'crm.companies.view'
        ]);

        $this->dispatch('terminal:app:activity');
    }

    protected function prepareForValidation($attributes)
    {
        // Leere Strings in null umwandeln für nullable Felder
        $nullableFields = [
            'company.legal_name',
            'company.trading_name',
            'company.registration_number',
            'company.tax_number',
            'company.vat_number',
            'company.website',
            'company.description',
            'company.notes',
            'company.legal_form_id',
        ];

        foreach ($nullableFields as $field) {
            if (data_get($attributes, $field) === '') {
                data_set($attributes, $field, null);
            }
        }

        return $attributes;
    }

    public function delete(): void
    {
        $this->company->delete();
        session()->flash('message', 'Unternehmen erfolgreich gelöscht.');
        $this->redirect(route('crm.companies.index'), navigate: true);
    }

    public function save(): void
    {
        $this->validate();

        $this->company->save();

        session()->flash('message', 'Unternehmen erfolgreich aktualisiert.');
    }

    // E-Mail-Methoden
    public function addEmail(): void
    {
        $this->emailForm = [
            'email_address' => '',
            'email_type_id' => 1,
            'is_primary' => $this->company->emailAddresses()->count() === 0,
        ];
        $this->editingEmailId = null;
        $this->emailCreateModalShow = true;
    }
    
    public function editEmail($emailId): void
    {
        $email = $this->company->emailAddresses()->findOrFail($emailId);
        $this->emailForm = [
            'email_address' => $email->email_address,
            'email_type_id' => $email->email_type_id,
            'is_primary' => $email->is_primary,
        ];
        $this->editingEmailId = $emailId;
        $this->emailEditModalShow = true;
    }
    
    public function saveEmail(): void
    {
        $this->validate([
            'emailForm.email_address' => 'required|email|max:255',
            'emailForm.email_type_id' => 'required|exists:crm_email_types,id',
        ]);

        // Prüfe auf doppelte E-Mail-Adressen
        $existingEmail = $this->company->emailAddresses()
            ->where('email_address', $this->emailForm['email_address'])
            ->when($this->editingEmailId, function($query) {
                return $query->where('id', '!=', $this->editingEmailId);
            })
            ->first();

        if ($existingEmail) {
            $this->addError('emailForm.email_address', 'Diese E-Mail-Adresse existiert bereits.');
            return;
        }

        if ($this->emailForm['is_primary']) {
            // Setze alle anderen E-Mails auf nicht primär
            $this->company->emailAddresses()->update(['is_primary' => false]);
        }

        if ($this->editingEmailId) {
            // Bearbeite bestehende E-Mail
            $email = $this->company->emailAddresses()->find($this->editingEmailId);
            if ($email) {
                $email->update([
                    'email_address' => $this->emailForm['email_address'],
                    'email_type_id' => $this->emailForm['email_type_id'],
                    'is_primary' => $this->emailForm['is_primary'],
                ]);
            }
        } else {
            // Erstelle neue E-Mail
            $this->company->emailAddresses()->create([
                'email_address' => $this->emailForm['email_address'],
                'email_type_id' => $this->emailForm['email_type_id'],
                'is_primary' => $this->emailForm['is_primary'],
                'is_active' => true,
            ]);
        }

        if ($this->editingEmailId) {
            $this->closeEmailEditModal();
        } else {
            $this->closeEmailCreateModal();
        }

        $this->company->load('emailAddresses');
    }
    
    public function closeEmailCreateModal(): void
    {
        $this->emailCreateModalShow = false;
        $this->emailForm = [
            'email_address' => '',
            'email_type_id' => 1,
            'is_primary' => false,
        ];
        $this->editingEmailId = null;
    }
    
    public function closeEmailEditModal(): void
    {
        $this->emailEditModalShow = false;
        $this->emailForm = [
            'email_address' => '',
            'email_type_id' => 1,
            'is_primary' => false,
        ];
        $this->editingEmailId = null;
    }
    
    public function deleteEmail($emailId): void
    {
        $this->company->emailAddresses()->where('id', $emailId)->delete();
        $this->company->load('emailAddresses');
    }
    
    public function deleteEmailAndCloseModal(): void
    {
        $this->deleteEmail($this->editingEmailId);
        $this->closeEmailEditModal();
    }

    // Telefonnummer-Methoden
    public function addPhone(): void
    {
        // Finde Deutschland als Standard-Land
        $germany = $this->countries->where('code', 'DE')->first();
        
        $this->phoneForm = [
            'raw_input' => '',
            'country_code' => $germany ? $germany->code : 'DE',
            'phone_type_id' => 1,
            'is_primary' => $this->company->phoneNumbers()->count() === 0,
        ];
        $this->editingPhoneId = null;
        $this->phoneCreateModalShow = true;
    }
    
    public function editPhone($phoneId): void
    {
        $phone = $this->company->phoneNumbers()->findOrFail($phoneId);
        $this->phoneForm = [
            'raw_input' => $phone->raw_input,
            'country_code' => $phone->country_code ?? 'DE',
            'phone_type_id' => $phone->phone_type_id,
            'is_primary' => $phone->is_primary,
        ];
        $this->editingPhoneId = $phoneId;
        $this->phoneEditModalShow = true;
    }
    
    public function savePhone(): void
    {
        $this->validate([
            'phoneForm.raw_input' => 'required|string|max:255',
            'phoneForm.country_code' => 'required|string|max:2',
            'phoneForm.phone_type_id' => 'required|exists:crm_phone_types,id',
        ]);
        
        // Prüfe auf doppelte Telefonnummern (pro Unternehmen)
        $existingPhone = $this->company->phoneNumbers()
            ->where('raw_input', $this->phoneForm['raw_input'])
            ->when($this->editingPhoneId, function($query) {
                return $query->where('id', '!=', $this->editingPhoneId);
            })
            ->first();

        if ($existingPhone) {
            $this->addError('phoneForm.raw_input', 'Diese Telefonnummer existiert bereits bei diesem Unternehmen.');
            return;
        }
        
        // Telefonnummer validieren und formatieren mit libphonenumber
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $phoneNumber = $phoneUtil->parse($this->phoneForm['raw_input'], $this->phoneForm['country_code']);
            
            if (!$phoneUtil->isValidNumber($phoneNumber)) {
                throw new \Exception('Invalid phone number');
            }
            
            $phoneData = [
                'raw_input' => $this->phoneForm['raw_input'],
                'international' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164),
                'national' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL),
                'country_code' => $phoneUtil->getRegionCodeForNumber($phoneNumber),
                'phone_type_id' => $this->phoneForm['phone_type_id'],
                'is_primary' => $this->phoneForm['is_primary'],
            ];
            
            // Wenn als primär markiert, alle anderen als nicht-primär setzen
            if ($this->phoneForm['is_primary']) {
                $this->company->phoneNumbers()->update(['is_primary' => false]);
            }
            
            if ($this->editingPhoneId) {
                // Bearbeiten
                $this->company->phoneNumbers()->where('id', $this->editingPhoneId)->update($phoneData);
                $this->closePhoneEditModal();
            } else {
                // Erstellen
                $this->company->phoneNumbers()->create($phoneData);
                $this->closePhoneCreateModal();
            }
            
            $this->company->load('phoneNumbers');
            
        } catch (NumberParseException $e) {
            $this->addError('phoneForm.raw_input', 'Ungültige Telefonnummer. Bitte überprüfen Sie das Format.');
        } catch (\Exception $e) {
            $this->addError('phoneForm.raw_input', 'Ungültige Telefonnummer für das ausgewählte Land. Bitte überprüfen Sie das Format.');
        }
    }
    
    public function closePhoneCreateModal(): void
    {
        $this->phoneCreateModalShow = false;
        $this->phoneForm = [
            'raw_input' => '',
            'country_code' => 'DE',
            'phone_type_id' => 1,
            'is_primary' => false,
        ];
        $this->editingPhoneId = null;
    }
    
    public function closePhoneEditModal(): void
    {
        $this->phoneEditModalShow = false;
        $this->phoneForm = [
            'raw_input' => '',
            'country_code' => 'DE',
            'phone_type_id' => 1,
            'is_primary' => false,
        ];
        $this->editingPhoneId = null;
    }
    
    public function deletePhone($phoneId): void
    {
        $this->company->phoneNumbers()->where('id', $phoneId)->delete();
        $this->company->load('phoneNumbers');
    }
    
    public function deletePhoneAndCloseModal(): void
    {
        $this->deletePhone($this->editingPhoneId);
        $this->closePhoneEditModal();
    }

    // Adress-Methoden
    public function addAddress(): void
    {
        // Finde Deutschland als Standard-Land
        $germany = CrmCountry::where('code', 'DE')->first();
        
        $this->addressForm = [
            'street' => '',
            'house_number' => '',
            'postal_code' => '',
            'city' => '',
            'additional_info' => '',
            'country_id' => $germany ? $germany->id : null,
            'state_id' => null,
            'address_type_id' => 1,
            'is_primary' => $this->company->postalAddresses()->count() === 0,
        ];
        $this->editingAddressId = null;
        $this->addressCreateModalShow = true;
    }
    
    public function editAddress($addressId): void
    {
        $address = $this->company->postalAddresses()->findOrFail($addressId);
        $this->addressForm = [
            'street' => $address->street,
            'house_number' => $address->house_number,
            'postal_code' => $address->postal_code,
            'city' => $address->city,
            'additional_info' => $address->additional_info,
            'country_id' => $address->country_id,
            'state_id' => $address->state_id,
            'address_type_id' => $address->address_type_id,
            'is_primary' => $address->is_primary,
        ];
        $this->editingAddressId = $addressId;
        $this->addressEditModalShow = true;
    }
    
    public function saveAddress(): void
    {
        $this->validate([
            'addressForm.street' => 'required|string|max:255',
            'addressForm.postal_code' => 'required|string|max:20',
            'addressForm.city' => 'required|string|max:255',
            'addressForm.country_id' => 'required|exists:crm_countries,id',
            'addressForm.address_type_id' => 'required|exists:crm_address_types,id',
        ]);

        // Wenn als primär markiert, alle anderen als nicht-primär setzen
        if ($this->addressForm['is_primary']) {
            $this->company->postalAddresses()->update(['is_primary' => false]);
        }

        if ($this->editingAddressId) {
            // Bearbeiten
            $this->company->postalAddresses()->where('id', $this->editingAddressId)->update($this->addressForm);
            $this->closeAddressEditModal();
        } else {
            // Erstellen
            $this->company->postalAddresses()->create($this->addressForm);
            $this->closeAddressCreateModal();
        }

        $this->company->load('postalAddresses');
    }
    
    public function closeAddressCreateModal(): void
    {
        $this->addressCreateModalShow = false;
        $this->addressForm = [
            'street' => '',
            'house_number' => '',
            'postal_code' => '',
            'city' => '',
            'additional_info' => '',
            'country_id' => null,
            'state_id' => null,
            'address_type_id' => 1,
            'is_primary' => false,
        ];
        $this->editingAddressId = null;
    }
    
    public function closeAddressEditModal(): void
    {
        $this->addressEditModalShow = false;
        $this->addressForm = [
            'street' => '',
            'house_number' => '',
            'postal_code' => '',
            'city' => '',
            'additional_info' => '',
            'country_id' => null,
            'state_id' => null,
            'address_type_id' => 1,
            'is_primary' => false,
        ];
        $this->editingAddressId = null;
    }
    
    public function deleteAddress($addressId): void
    {
        $this->company->postalAddresses()->where('id', $addressId)->delete();
        $this->company->load('postalAddresses');
    }
    
    public function deleteAddressAndCloseModal(): void
    {
        $this->deleteAddress($this->editingAddressId);
        $this->closeAddressEditModal();
    }

    // Kontakt-Beziehungs-Methoden
    public function addContact(): void
    {
        $this->contactRelationForm = [
            'contact_id' => null,
            'relation_type_id' => 1,
            'position' => '',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => $this->company->contactRelations()->count() === 0,
            'notes' => '',
        ];
        $this->editingContactRelationId = null;
        $this->contactCreateModalShow = true;
    }
    
    public function editContact($relationId): void
    {
        $relation = $this->company->contactRelations()->findOrFail($relationId);
        $this->contactRelationForm = [
            'contact_id' => $relation->contact_id,
            'relation_type_id' => $relation->relation_type_id,
            'position' => $relation->position,
            'start_date' => $relation->start_date?->toDateString(),
            'end_date' => $relation->end_date?->toDateString(),
            'is_primary' => $relation->is_primary,
            'notes' => $relation->notes,
        ];
        $this->editingContactRelationId = $relationId;
        $this->contactEditModalShow = true;
    }
    
    public function saveContact(): void
    {
        $this->validate([
            'contactRelationForm.contact_id' => 'required|exists:crm_contacts,id',
            'contactRelationForm.relation_type_id' => 'required|exists:crm_contact_relation_types,id',
            'contactRelationForm.position' => 'nullable|string|max:255',
            'contactRelationForm.start_date' => 'nullable|date',
            'contactRelationForm.end_date' => 'nullable|date|after_or_equal:contactRelationForm.start_date',
            'contactRelationForm.notes' => 'nullable|string',
        ]);

        // Prüfe auf doppelte Kontakt-Beziehungen
        $existingRelation = $this->company->contactRelations()
            ->where('contact_id', $this->contactRelationForm['contact_id'])
            ->when($this->editingContactRelationId, function($query) {
                return $query->where('id', '!=', $this->editingContactRelationId);
            })
            ->first();

        if ($existingRelation) {
            $this->addError('contactRelationForm.contact_id', 'Dieser Kontakt ist bereits mit diesem Unternehmen verknüpft.');
            return;
        }

        // Wenn als primär markiert, alle anderen als nicht-primär setzen
        if ($this->contactRelationForm['is_primary']) {
            $this->company->contactRelations()->update(['is_primary' => false]);
        }

        if ($this->editingContactRelationId) {
            // Bearbeiten
            $this->company->contactRelations()->where('id', $this->editingContactRelationId)->update($this->contactRelationForm);
            $this->closeContactEditModal();
        } else {
            // Erstellen
            $this->company->contactRelations()->create($this->contactRelationForm);
            $this->closeContactCreateModal();
        }

        $this->company->load('contactRelations.contact');
    }
    
    public function closeContactCreateModal(): void
    {
        $this->contactCreateModalShow = false;
        $this->contactRelationForm = [
            'contact_id' => null,
            'relation_type_id' => 1,
            'position' => '',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => false,
            'notes' => '',
        ];
        $this->editingContactRelationId = null;
    }
    
    public function closeContactEditModal(): void
    {
        $this->contactEditModalShow = false;
        $this->contactRelationForm = [
            'contact_id' => null,
            'relation_type_id' => 1,
            'position' => '',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => false,
            'notes' => '',
        ];
        $this->editingContactRelationId = null;
    }
    
    public function deleteContact($relationId): void
    {
        $this->company->contactRelations()->where('id', $relationId)->delete();
        $this->company->load('contactRelations.contact');
    }
    
    public function deleteContactAndCloseModal(): void
    {
        $this->deleteContact($this->editingContactRelationId);
        $this->closeContactEditModal();
    }

    /**
     * Gefilterte Kontakte (ohne bereits verknüpfte)
     */
    #[Computed]
    public function filteredContacts()
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;
        
        if (!$teamId) {
            return collect();
        }
        
        $linkedContactIds = $this->company->contactRelations()
            ->when($this->editingContactRelationId, function($query) {
                return $query->where('id', '!=', $this->editingContactRelationId);
            })
            ->pluck('contact_id');
        
        return CrmContact::active()
            ->where('team_id', $teamId)
            ->whereNotIn('id', $linkedContactIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Prüft, ob es ungespeicherte Änderungen gibt
     */
    #[Computed]
    public function isDirty()
    {
        // Prüfe ob das Company-Model geändert wurde
        return $this->company->isDirty();
    }

    // Follow-up Methods
    public function addFollowUp(): void
    {
        $this->validate([
            'followUpForm.title' => 'required|string|max:255',
            'followUpForm.due_date' => 'required|date',
        ]);

        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;

        $this->company->followUps()->create([
            'title' => $this->followUpForm['title'],
            'due_date' => $this->followUpForm['due_date'],
            'created_by_user_id' => $user->id,
            'team_id' => $teamId,
        ]);

        $this->followUpForm = ['title' => '', 'due_date' => null];
        $this->company->load('followUps');
    }

    public function toggleFollowUp(int $id): void
    {
        $followUp = $this->company->followUps()->findOrFail($id);
        $followUp->update([
            'completed_at' => $followUp->completed_at ? null : now(),
        ]);
        $this->company->load('followUps');
    }

    public function deleteFollowUp(int $id): void
    {
        $this->company->followUps()->where('id', $id)->delete();
        $this->company->load('followUps');
    }

    public function addNote(): void
    {
        $this->validate(['newNote' => 'required|string|max:1000']);
        $this->company->logActivity($this->newNote);
        $this->newNote = '';
        $this->company->load('activities.user');
    }

    public function deleteNote(int $activityId): void
    {
        $this->company->activities()
            ->where('id', $activityId)
            ->where('activity_type', 'manual')
            ->where('user_id', auth()->id())
            ->delete();
        $this->company->load('activities.user');
    }

    // Engagement Methods
    #[Computed]
    public function engagements()
    {
        return CrmEngagement::whereHas('companyLinks', function ($q) {
            $q->where('company_id', $this->company->id);
        })->with(['ownedByUser', 'companyLinks.company', 'contactLinks.contact'])
          ->orderByDesc('created_at')
          ->get();
    }

    public function openEngagementCreateModal(string $type = 'note'): void
    {
        $this->engagementForm = [
            'type' => $type,
            'title' => '',
            'body' => '',
            'status' => null,
            'priority' => null,
            'scheduled_at' => null,
        ];
        $this->engagementCreateModalShow = true;
    }

    public function closeEngagementCreateModal(): void
    {
        $this->engagementCreateModalShow = false;
        $this->engagementForm = [
            'type' => 'note',
            'title' => '',
            'body' => '',
            'status' => null,
            'priority' => null,
            'scheduled_at' => null,
        ];
    }

    public function createEngagementForCompany(): void
    {
        $this->validate([
            'engagementForm.type' => 'required|in:note,call,meeting,task',
            'engagementForm.title' => 'required|string|max:255',
            'engagementForm.body' => 'nullable|string',
            'engagementForm.status' => 'nullable|string|max:50',
            'engagementForm.priority' => 'nullable|string|max:50',
            'engagementForm.scheduled_at' => 'nullable|date',
        ]);

        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;

        $engagement = CrmEngagement::create([
            'type' => $this->engagementForm['type'],
            'title' => $this->engagementForm['title'],
            'body' => $this->engagementForm['body'] ?: null,
            'status' => $this->engagementForm['status'] ?: null,
            'priority' => $this->engagementForm['priority'] ?: null,
            'scheduled_at' => $this->engagementForm['scheduled_at'] ?: null,
            'owned_by_user_id' => $user->id,
            'created_by_user_id' => $user->id,
            'team_id' => $teamId,
        ]);

        $engagement->attachCompany($this->company);

        $this->closeEngagementCreateModal();
        unset($this->engagements);
        session()->flash('message', 'Engagement erfolgreich erstellt!');
    }

    // Potenzial Methods
    #[Computed]
    public function currentYearPotential()
    {
        return $this->company->accountPotentials()
            ->forYear(now()->year)
            ->first();
    }

    #[Computed]
    public function potentialHistory()
    {
        return $this->company->accountPotentials()
            ->where('year', '<', now()->year)
            ->orderByDesc('year')
            ->get();
    }

    public function updatedActiveTab($value): void
    {
        if ($value === 'potenzial') {
            $this->initPotentialForm();
        }
    }

    public function initPotentialForm(): void
    {
        $potential = $this->company->accountPotentials()
            ->forYear(now()->year)
            ->first();

        if ($potential) {
            $this->currentPotentialId = $potential->id;
            $this->potentialForm = [
                'target_revenue' => $potential->target_revenue,
                'additional_potential' => $potential->additional_potential,
                'strategic_potential' => $potential->strategic_potential,
                'confidence' => $potential->confidence,
                'notes' => $potential->notes ?? '',
            ];
        } else {
            $this->currentPotentialId = null;
            $this->potentialForm = [
                'target_revenue' => null,
                'additional_potential' => null,
                'strategic_potential' => null,
                'confidence' => null,
                'notes' => '',
            ];
        }
    }

    public function savePotential(): void
    {
        $this->validate([
            'potentialForm.target_revenue' => 'nullable|numeric|min:0',
            'potentialForm.additional_potential' => 'nullable|numeric|min:0',
            'potentialForm.strategic_potential' => 'nullable|numeric|min:0',
            'potentialForm.confidence' => 'nullable|in:low,medium,high,very_high',
            'potentialForm.notes' => 'nullable|string',
        ]);

        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;

        $this->company->accountPotentials()->updateOrCreate(
            ['year' => now()->year],
            [
                'target_revenue' => $this->potentialForm['target_revenue'] ?: null,
                'additional_potential' => $this->potentialForm['additional_potential'] ?: null,
                'strategic_potential' => $this->potentialForm['strategic_potential'] ?: null,
                'confidence' => $this->potentialForm['confidence'] ?: null,
                'notes' => $this->potentialForm['notes'] ?: null,
                'created_by_user_id' => $user->id,
                'team_id' => $teamId,
            ]
        );

        unset($this->currentYearPotential);
        unset($this->potentialHistory);
        $this->initPotentialForm();
        session()->flash('message', 'Potenzial gespeichert.');
    }

    public function openPotentialHistoryModal(): void
    {
        $this->potentialHistoryForm = [
            'year' => now()->year - 1,
            'target_revenue' => null,
            'additional_potential' => null,
            'strategic_potential' => null,
            'confidence' => null,
            'notes' => '',
        ];
        $this->potentialCreateModalShow = true;
    }

    public function closePotentialHistoryModal(): void
    {
        $this->potentialCreateModalShow = false;
        $this->potentialHistoryForm = [
            'year' => null,
            'target_revenue' => null,
            'additional_potential' => null,
            'strategic_potential' => null,
            'confidence' => null,
            'notes' => '',
        ];
    }

    public function savePotentialHistory(): void
    {
        $this->validate([
            'potentialHistoryForm.year' => 'required|integer|min:2000|max:' . now()->year,
            'potentialHistoryForm.target_revenue' => 'nullable|numeric|min:0',
            'potentialHistoryForm.additional_potential' => 'nullable|numeric|min:0',
            'potentialHistoryForm.strategic_potential' => 'nullable|numeric|min:0',
            'potentialHistoryForm.confidence' => 'nullable|in:low,medium,high,very_high',
            'potentialHistoryForm.notes' => 'nullable|string',
        ]);

        $exists = $this->company->accountPotentials()
            ->forYear($this->potentialHistoryForm['year'])
            ->exists();

        if ($exists) {
            $this->addError('potentialHistoryForm.year', 'Für dieses Jahr existiert bereits ein Eintrag.');
            return;
        }

        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;

        $this->company->accountPotentials()->create([
            'year' => $this->potentialHistoryForm['year'],
            'target_revenue' => $this->potentialHistoryForm['target_revenue'] ?: null,
            'additional_potential' => $this->potentialHistoryForm['additional_potential'] ?: null,
            'strategic_potential' => $this->potentialHistoryForm['strategic_potential'] ?: null,
            'confidence' => $this->potentialHistoryForm['confidence'] ?: null,
            'notes' => $this->potentialHistoryForm['notes'] ?: null,
            'created_by_user_id' => $user->id,
            'team_id' => $teamId,
        ]);

        $this->closePotentialHistoryModal();
        unset($this->potentialHistory);
    }

    public function deletePotentialEntry(int $id): void
    {
        $this->company->accountPotentials()->where('id', $id)->delete();
        unset($this->currentYearPotential);
        unset($this->potentialHistory);
        $this->initPotentialForm();
    }

    public function render()
    {
        return view('crm::livewire.company.company')
            ->layout('platform::layouts.app');
    }
} 