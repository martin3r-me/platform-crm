<?php

namespace Platform\Crm\Livewire\ContactList;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Models\CrmContactListMember;
use Platform\Crm\Services\Comms\SubscriptionService;

class ContactList extends Component
{
    public CrmContactList $contactList;

    // Editable fields
    public string $name = '';
    public ?string $description = null;
    public ?string $color = null;
    public bool $isActive = true;
    public bool $requiresDoi = false;
    public ?string $doiConfirmationSubject = null;
    public ?string $doiConfirmationBody = null;

    // UI state
    public string $activeTab = 'settings';
    public string $memberSearch = '';
    public string $contactSearch = '';
    public bool $addMemberModal = false;

    // Prev/Next navigation
    public ?int $prevListId = null;
    public ?int $nextListId = null;

    public function mount(CrmContactList $contactList)
    {
        $this->contactList = $contactList->load(['createdByUser']);

        $this->name = $this->contactList->name ?? '';
        $this->description = $this->contactList->description;
        $this->color = $this->contactList->color;
        $this->isActive = (bool) $this->contactList->is_active;
        $this->requiresDoi = (bool) $this->contactList->requires_doi;
        $this->doiConfirmationSubject = $this->contactList->doi_confirmation_subject;
        $this->doiConfirmationBody = $this->contactList->doi_confirmation_body;

        // Prev/Next navigation from index list
        $nav = session('crm.list_nav');
        if ($nav && !empty($nav['ids'])) {
            $ids = $nav['ids'];
            $pos = array_search($this->contactList->id, $ids);
            if ($pos !== false) {
                $this->prevListId = $pos > 0 ? $ids[$pos - 1] : null;
                $this->nextListId = $pos < count($ids) - 1 ? $ids[$pos + 1] : null;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'isActive' => 'boolean',
            'requiresDoi' => 'boolean',
            'doiConfirmationSubject' => 'nullable|string|max:255',
            'doiConfirmationBody' => 'nullable|string',
        ]);

        $this->contactList->name = $this->name;
        $this->contactList->description = $this->description ?: null;
        $this->contactList->color = $this->color ?: null;
        $this->contactList->is_active = $this->isActive;
        $this->contactList->requires_doi = $this->requiresDoi;
        $this->contactList->doi_confirmation_subject = $this->doiConfirmationSubject ?: null;
        $this->contactList->doi_confirmation_body = $this->doiConfirmationBody ?: null;

        $this->contactList->save();
        $this->contactList->refresh();

        session()->flash('message', 'Kontaktliste erfolgreich gespeichert.');
    }

    public function delete(): void
    {
        $this->contactList->delete();
        session()->flash('message', 'Kontaktliste erfolgreich gelöscht.');
        $this->redirect(route('crm.lists.index'), navigate: true);
    }

    #[Computed]
    public function isDirty(): bool
    {
        return $this->name !== ($this->contactList->name ?? '')
            || ($this->description ?: null) !== $this->contactList->description
            || ($this->color ?: null) !== $this->contactList->color
            || $this->isActive !== (bool) $this->contactList->is_active
            || $this->requiresDoi !== (bool) $this->contactList->requires_doi
            || ($this->doiConfirmationSubject ?: null) !== $this->contactList->doi_confirmation_subject
            || ($this->doiConfirmationBody ?: null) !== $this->contactList->doi_confirmation_body;
    }

    #[Computed]
    public function members()
    {
        $search = trim($this->memberSearch);

        return $this->contactList->members()
            ->with('contact')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('contact', fn ($q) => $q
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('emailAddresses', fn ($eq) => $eq->where('email_address', 'like', "%{$search}%"))
                );
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function searchableContacts()
    {
        $search = trim($this->contactSearch);

        if ($search === '') {
            return collect();
        }

        $existingContactIds = $this->contactList->members()->pluck('contact_id')->toArray();

        return CrmContact::where('team_id', $this->getTeamId())
            ->where('is_active', true)
            ->whereNotIn('id', $existingContactIds)
            ->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhereHas('emailAddresses', fn ($eq) => $eq->where('email_address', 'like', "%{$search}%"))
            )
            ->orderBy('last_name')
            ->take(20)
            ->get();
    }

    public function removeMember(int $memberId): void
    {
        CrmContactListMember::where('id', $memberId)
            ->where('contact_list_id', $this->contactList->id)
            ->delete();

        $this->contactList->updateMemberCount();
        $this->contactList->refresh();

        unset($this->members);
    }

    public function addMember(int $contactId): void
    {
        $contact = CrmContact::find($contactId);
        if (!$contact) {
            return;
        }

        $member = app(SubscriptionService::class)->subscribe(
            $this->contactList,
            $contact,
            'manual_admin',
            auth()->id()
        );

        $this->contactList->refresh();

        $this->contactSearch = '';
        unset($this->members, $this->searchableContacts);
    }

    public function openAddMemberModal(): void
    {
        $this->contactSearch = '';
        $this->addMemberModal = true;
    }

    public function closeAddMemberModal(): void
    {
        $this->addMemberModal = false;
        $this->contactSearch = '';
    }

    public function render()
    {
        return view('crm::livewire.contact-list.contact-list')
            ->layout('platform::layouts.app');
    }

    private function getTeamId(): int
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }
}
