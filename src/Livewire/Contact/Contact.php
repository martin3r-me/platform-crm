<?php

namespace Platform\Crm\Livewire\Contact;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmSalutation;
use Platform\Crm\Models\CrmAcademicTitle;
use Platform\Crm\Models\CrmGender;
use Platform\Crm\Models\CrmLanguage;
use Platform\Crm\Models\CrmContactStatus;
use Platform\Crm\Models\CrmPhoneNumber;
use Platform\Crm\Models\CrmEmailAddress;
use Platform\Crm\Models\CrmEmailType;
use Platform\Crm\Models\CrmPostalAddress;
use Platform\Crm\Models\CrmAddressType;
use Platform\Crm\Models\CrmCountry;
use Platform\Crm\Models\CrmState;
use Platform\Crm\Models\CrmPhoneType;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmContactRelation;
use Platform\Crm\Models\CrmContactRelationType;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;

class Contact extends Component
{
    public CrmContact $contact;

    public $salutations = [];
    public $academicTitles = [];
    public $genders = [];
    public $languages = [];
    public $contactStatuses = [];
    public $emailTypes = [];
    public $addressTypes = [];
    public $countries = [];
    public $states = [];
    public $phoneTypes = [];
    public $companies = [];
    public $relationTypes = [];

    // E-Mail Modals
    public $emailCreateModalShow = false;
    public $emailEditModalShow = false;
    public $editingEmailId = null;
    
    // E-Mail Form Properties
    public $emailForm = [
        'email_address' => '',
        'email_type_id' => 1,
        'is_primary' => false,
    ];

    // Address Modals
    public $addressCreateModalShow = false;
    public $addressEditModalShow = false;
    public $editingAddressId = null;
    
    // Telefonnummer-Modals
    public $phoneCreateModalShow = false;
    public $phoneEditModalShow = false;
    public $editingPhoneId = null;
    
    // Kontakt-Beziehungs-Modals
    public $companyCreateModalShow = false;
    public $companyEditModalShow = false;
    public $editingCompanyRelationId = null;
    
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
    
    // Telefonnummer-Form
    public $phoneForm = [
        'raw_input' => '',
        'country_code' => 'DE',
        'phone_type_id' => 1,
        'is_primary' => false,
    ];
    
    // Kontakt-Beziehungs-Form
    public $companyRelationForm = [
        'company_id' => null,
        'relation_type_id' => 1,
        'position' => '',
        'start_date' => null,
        'end_date' => null,
        'is_primary' => false,
        'notes' => '',
    ];
    
    public $modalShow = false;

    // Telefon Modal
    public $phoneModalShow = false;

    // Adresse Modal
    public $addressModalShow = false;

    public function mount(CrmContact $contact)
    {
        $this->contact = $contact->load(['phoneNumbers', 'emailAddresses', 'postalAddresses', 'contactRelations.company', 'activities']);
        
        // Daten für die rechte Spalte laden
        $this->salutations = CrmSalutation::active()->get();
        $this->academicTitles = CrmAcademicTitle::active()->get();
        $this->genders = CrmGender::active()->get();
        $this->languages = CrmLanguage::active()->get();
        $this->contactStatuses = CrmContactStatus::active()->get();
        $this->emailTypes = CrmEmailType::active()->get();
        $this->addressTypes = CrmAddressType::active()->get();
        $this->countries = CrmCountry::active()->get();
        $this->states = CrmState::active()->get();
        $this->phoneTypes = CrmPhoneType::active()->get();
        $this->relationTypes = CrmContactRelationType::active()->get();
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;
        $this->companies = $teamId 
            ? CrmCompany::active()->where('team_id', $teamId)->orderBy('name')->get()
            : collect();

        // Setze Deutschland als Standard-Land für neue Adressen
        $germany = CrmCountry::where('code', 'DE')->first();
        if ($germany) {
            $this->addressForm['country_id'] = $germany->id;
        }
        
        // Setze aktuelles Datum als Standard-Startdatum
        $this->companyRelationForm['start_date'] = now()->toDateString();
    }

    protected function prepareForValidation($attributes)
    {
        // Leere Strings in null umwandeln für nullable Felder
        $nullableFields = [
            'contact.middle_name',
            'contact.nickname', 
            'contact.birth_date',
            'contact.notes',
            'contact.salutation_id',
            'contact.academic_title_id',
            'contact.gender_id',
            'contact.language_id',
        ];

        foreach ($nullableFields as $field) {
            if (data_get($attributes, $field) === '') {
                data_set($attributes, $field, null);
            }
        }

        return $attributes;
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
            'contact.salutation_id' => 'nullable|integer|exists:crm_salutations,id',
            'contact.academic_title_id' => 'nullable|integer|exists:crm_academic_titles,id',
            'contact.gender_id' => 'nullable|integer|exists:crm_genders,id',
            'contact.language_id' => 'nullable|integer|exists:crm_languages,id',
            'contact.contact_status_id' => 'required|integer|exists:crm_contact_statuses,id',
        ];
    }

    public function save(): void
    {
        $this->validate();
        $this->contact->save();

        session()->flash('message', 'Kontakt erfolgreich aktualisiert.');
    }

    public function addPhone(): void
    {
        // Finde Deutschland als Standard-Land
        $germany = $this->countries->where('code', 'DE')->first();
        
        $this->phoneForm = [
            'raw_input' => '',
            'country_code' => $germany ? $germany->code : 'DE',
            'phone_type_id' => 1,
            'is_primary' => $this->contact->phoneNumbers()->count() === 0,
        ];
        $this->editingPhoneId = null;
        $this->phoneCreateModalShow = true;
    }
    
    public function editPhone($phoneId): void
    {
        $phone = $this->contact->phoneNumbers()->findOrFail($phoneId);
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
        
        // Prüfe auf doppelte Telefonnummern (pro Kontakt)
        $existingPhone = $this->contact->phoneNumbers()
            ->where('raw_input', $this->phoneForm['raw_input'])
            ->when($this->editingPhoneId, function($query) {
                return $query->where('id', '!=', $this->editingPhoneId);
            })
            ->first();

        if ($existingPhone) {
            $this->addError('phoneForm.raw_input', 'Diese Telefonnummer existiert bereits bei diesem Kontakt.');
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
                $this->contact->phoneNumbers()->update(['is_primary' => false]);
            }
            
            if ($this->editingPhoneId) {
                // Bearbeiten
                $this->contact->phoneNumbers()->where('id', $this->editingPhoneId)->update($phoneData);
                $this->closePhoneEditModal();
            } else {
                // Erstellen
                $this->contact->phoneNumbers()->create($phoneData);
                $this->closePhoneCreateModal();
            }
            
            $this->contact->load('phoneNumbers');
            
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
        $this->contact->phoneNumbers()->where('id', $phoneId)->delete();
        $this->contact->load('phoneNumbers');
    }
    
    public function deletePhoneAndCloseModal(): void
    {
        $this->deletePhone($this->editingPhoneId);
        $this->closePhoneEditModal();
    }

    public function addEmail(): void
    {
        $this->emailForm = [
            'email_address' => '',
            'email_type_id' => 1,
            'is_primary' => $this->contact->emailAddresses()->count() === 0,
        ];
        $this->editingEmailId = null;
        $this->emailCreateModalShow = true;
    }

    public function editEmail($emailId): void
    {
        $email = $this->contact->emailAddresses()->find($emailId);
        if ($email) {
            $this->emailForm = [
                'email_address' => $email->email_address,
                'email_type_id' => $email->email_type_id,
                'is_primary' => $email->is_primary,
            ];
            $this->editingEmailId = $emailId;
            $this->emailEditModalShow = true;
        }
    }

    public function saveEmail(): void
    {
        $this->validate([
            'emailForm.email_address' => 'required|email|max:255',
            'emailForm.email_type_id' => 'required|exists:crm_email_types,id',
        ]);

        // Prüfe auf doppelte E-Mail-Adressen
        $existingEmail = $this->contact->emailAddresses()
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
            $this->contact->emailAddresses()->update(['is_primary' => false]);
        }

        if ($this->editingEmailId) {
            // Bearbeite bestehende E-Mail
            $email = $this->contact->emailAddresses()->find($this->editingEmailId);
            if ($email) {
                $email->update([
                    'email_address' => $this->emailForm['email_address'],
                    'email_type_id' => $this->emailForm['email_type_id'],
                    'is_primary' => $this->emailForm['is_primary'],
                ]);
            }
        } else {
            // Erstelle neue E-Mail
            $this->contact->emailAddresses()->create([
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
        $this->contact->refresh();
        session()->flash('message', 'E-Mail-Adresse erfolgreich gespeichert.');
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
        $email = $this->contact->emailAddresses()->find($emailId);
        if ($email) {
            $email->delete();
            $this->contact->refresh();
        }
    }

    public function deleteEmailAndCloseModal(): void
    {
        if ($this->editingEmailId) {
            $this->deleteEmail($this->editingEmailId);
            $this->closeEmailEditModal();
        }
    }

    // Address Methods
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
            'is_primary' => $this->contact->postalAddresses()->count() === 0,
        ];
        $this->editingAddressId = null;
        $this->addressCreateModalShow = true;
    }

    public function editAddress($addressId): void
    {
        $address = $this->contact->postalAddresses()->find($addressId);
        if ($address) {
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
    }

    public function saveAddress(): void
    {
        $this->validate([
            'addressForm.street' => 'required|string|max:255',
            'addressForm.house_number' => 'nullable|string|max:20',
            'addressForm.postal_code' => 'required|string|max:20',
            'addressForm.city' => 'required|string|max:255',
            'addressForm.additional_info' => 'nullable|string|max:500',
            'addressForm.country_id' => 'required|exists:crm_countries,id',
            'addressForm.state_id' => 'nullable|exists:crm_states,id',
            'addressForm.address_type_id' => 'required|exists:crm_address_types,id',
        ]);

        if ($this->addressForm['is_primary']) {
            // Setze alle anderen Adressen auf nicht primär
            $this->contact->postalAddresses()->update(['is_primary' => false]);
        }

        if ($this->editingAddressId) {
            // Bearbeite bestehende Adresse
            $address = $this->contact->postalAddresses()->find($this->editingAddressId);
            if ($address) {
                $address->update([
                    'street' => $this->addressForm['street'],
                    'house_number' => $this->addressForm['house_number'],
                    'postal_code' => $this->addressForm['postal_code'],
                    'city' => $this->addressForm['city'],
                    'additional_info' => $this->addressForm['additional_info'],
                    'country_id' => $this->addressForm['country_id'],
                    'state_id' => $this->addressForm['state_id'],
                    'address_type_id' => $this->addressForm['address_type_id'],
                    'is_primary' => $this->addressForm['is_primary'],
                ]);
            }
        } else {
            // Erstelle neue Adresse
            $this->contact->postalAddresses()->create([
                'street' => $this->addressForm['street'],
                'house_number' => $this->addressForm['house_number'],
                'postal_code' => $this->addressForm['postal_code'],
                'city' => $this->addressForm['city'],
                'additional_info' => $this->addressForm['additional_info'],
                'country_id' => $this->addressForm['country_id'],
                'state_id' => $this->addressForm['state_id'],
                'address_type_id' => $this->addressForm['address_type_id'],
                'is_primary' => $this->addressForm['is_primary'],
                'is_active' => true,
            ]);
        }

        if ($this->editingAddressId) {
            $this->closeAddressEditModal();
        } else {
            $this->closeAddressCreateModal();
        }
        $this->contact->refresh();
        session()->flash('message', 'Adresse erfolgreich gespeichert.');
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
        $address = $this->contact->postalAddresses()->find($addressId);
        if ($address) {
            $address->delete();
            $this->contact->refresh();
        }
    }

    public function deleteAddressAndCloseModal(): void
    {
        $this->deleteAddress($this->editingAddressId);
        $this->closeAddressEditModal();
    }

    // Kontakt-Beziehungs-Methoden
    public function addCompany(): void
    {
        $this->companyRelationForm = [
            'company_id' => null,
            'relation_type_id' => 1,
            'position' => '',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => $this->contact->contactRelations()->count() === 0,
            'notes' => '',
        ];
        $this->editingCompanyRelationId = null;
        $this->companyCreateModalShow = true;
    }
    
    public function editCompany($relationId): void
    {
        $relation = $this->contact->contactRelations()->findOrFail($relationId);
        $this->companyRelationForm = [
            'company_id' => $relation->company_id,
            'relation_type_id' => $relation->relation_type_id,
            'position' => $relation->position,
            'start_date' => $relation->start_date?->toDateString(),
            'end_date' => $relation->end_date?->toDateString(),
            'is_primary' => $relation->is_primary,
            'notes' => $relation->notes,
        ];
        $this->editingCompanyRelationId = $relationId;
        $this->companyEditModalShow = true;
    }
    
    public function saveCompany(): void
    {
        $this->validate([
            'companyRelationForm.company_id' => 'required|exists:crm_companies,id',
            'companyRelationForm.relation_type_id' => 'required|exists:crm_contact_relation_types,id',
            'companyRelationForm.position' => 'nullable|string|max:255',
            'companyRelationForm.start_date' => 'nullable|date',
            'companyRelationForm.end_date' => 'nullable|date|after_or_equal:companyRelationForm.start_date',
            'companyRelationForm.notes' => 'nullable|string',
        ]);

        // Prüfe auf doppelte Kontakt-Beziehungen
        $existingRelation = $this->contact->contactRelations()
            ->where('company_id', $this->companyRelationForm['company_id'])
            ->when($this->editingCompanyRelationId, function($query) {
                return $query->where('id', '!=', $this->editingCompanyRelationId);
            })
            ->first();

        if ($existingRelation) {
            $this->addError('companyRelationForm.company_id', 'Dieser Kontakt ist bereits mit diesem Unternehmen verknüpft.');
            return;
        }

        // Wenn als primär markiert, alle anderen als nicht-primär setzen
        if ($this->companyRelationForm['is_primary']) {
            $this->contact->contactRelations()->update(['is_primary' => false]);
        }

        if ($this->editingCompanyRelationId) {
            // Bearbeiten
            $this->contact->contactRelations()->where('id', $this->editingCompanyRelationId)->update($this->companyRelationForm);
            $this->closeCompanyEditModal();
        } else {
            // Erstellen
            $this->contact->contactRelations()->create($this->companyRelationForm);
            $this->closeCompanyCreateModal();
        }

        $this->contact->load('contactRelations.company');
    }
    
    public function closeCompanyCreateModal(): void
    {
        $this->companyCreateModalShow = false;
        $this->companyRelationForm = [
            'company_id' => null,
            'relation_type_id' => 1,
            'position' => '',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => false,
            'notes' => '',
        ];
        $this->editingCompanyRelationId = null;
    }
    
    public function closeCompanyEditModal(): void
    {
        $this->companyEditModalShow = false;
        $this->companyRelationForm = [
            'company_id' => null,
            'relation_type_id' => 1,
            'position' => '',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'is_primary' => false,
            'notes' => '',
        ];
        $this->editingCompanyRelationId = null;
    }
    
    public function deleteCompany($relationId): void
    {
        $this->contact->contactRelations()->where('id', $relationId)->delete();
        $this->contact->load('contactRelations.company');
    }
    
    public function deleteCompanyAndCloseModal(): void
    {
        $this->deleteCompany($this->editingCompanyRelationId);
        $this->closeCompanyEditModal();
    }

    /**
     * Gefilterte Unternehmen (ohne bereits verknüpfte)
     */
    #[Computed]
    public function filteredCompanies()
    {
        $linkedCompanyIds = $this->contact->contactRelations()
            ->when($this->editingCompanyRelationId, function($query) {
                return $query->where('id', '!=', $this->editingCompanyRelationId);
            })
            ->pluck('company_id');
        
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;
        
        if (!$teamId) {
            return collect();
        }
        
        return CrmCompany::active()
            ->where('team_id', $teamId)
            ->whereNotIn('id', $linkedCompanyIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Prüft, ob es ungespeicherte Änderungen gibt
     */
    #[Computed]
    public function isDirty()
    {
        // Prüfe ob das Contact-Model geändert wurde
        return $this->contact->isDirty();
    }

    public function render()
    {
        return view('crm::livewire.contact.contact')
            ->layout('platform::layouts.app');
    }
}