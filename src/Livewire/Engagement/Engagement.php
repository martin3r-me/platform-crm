<?php

namespace Platform\Crm\Livewire\Engagement;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Crm\Models\CrmEngagement;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCompany;

class Engagement extends Component
{
    public CrmEngagement $engagement;

    // Editable fields
    public string $title = '';
    public ?string $body = null;
    public ?string $status = null;
    public ?string $priority = null;
    public ?string $scheduledAt = null;
    public ?string $endedAt = null;
    public ?string $completedAt = null;

    // Link modals
    public bool $contactLinkModalShow = false;
    public bool $companyLinkModalShow = false;
    public $linkContactId = null;
    public $linkCompanyId = null;

    // Prev/Next navigation
    public ?int $prevEngagementId = null;
    public ?int $nextEngagementId = null;

    // Activity
    public string $newNote = '';

    public function mount(CrmEngagement $engagement)
    {
        $this->engagement = $engagement->load(['companyLinks.company', 'contactLinks.contact', 'ownedByUser', 'createdByUser', 'activities.user']);

        $this->title = $this->engagement->title ?? '';
        $this->body = $this->engagement->body;
        $this->status = $this->engagement->status;
        $this->priority = $this->engagement->priority;
        $this->scheduledAt = $this->engagement->scheduled_at?->toDateTimeLocalString();
        $this->endedAt = $this->engagement->ended_at?->toDateTimeLocalString();
        $this->completedAt = $this->engagement->completed_at?->toDateTimeLocalString();

        // Prev/Next navigation from index list
        $nav = session('crm.engagement_nav');
        if ($nav && !empty($nav['ids'])) {
            $ids = $nav['ids'];
            $pos = array_search($this->engagement->id, $ids);
            if ($pos !== false) {
                $this->prevEngagementId = $pos > 0 ? $ids[$pos - 1] : null;
                $this->nextEngagementId = $pos < count($ids) - 1 ? $ids[$pos + 1] : null;
            }
        }
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'status' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:50',
            'scheduledAt' => 'nullable|date',
            'endedAt' => 'nullable|date',
            'completedAt' => 'nullable|date',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $this->engagement->title = $this->title;
        $this->engagement->body = $this->body ?: null;
        $this->engagement->status = $this->status ?: null;
        $this->engagement->priority = $this->priority ?: null;
        $this->engagement->scheduled_at = $this->scheduledAt ?: null;
        $this->engagement->ended_at = $this->endedAt ?: null;
        $this->engagement->completed_at = $this->completedAt ?: null;

        $this->engagement->save();
        $this->engagement->refresh();

        // Re-sync local properties
        $this->scheduledAt = $this->engagement->scheduled_at?->toDateTimeLocalString();
        $this->endedAt = $this->engagement->ended_at?->toDateTimeLocalString();
        $this->completedAt = $this->engagement->completed_at?->toDateTimeLocalString();

        session()->flash('message', 'Engagement erfolgreich aktualisiert.');
    }

    public function delete(): void
    {
        $this->engagement->delete();
        session()->flash('message', 'Engagement erfolgreich gelöscht.');
        $this->redirect(route('crm.engagements.index'), navigate: true);
    }

    #[Computed]
    public function isDirty(): bool
    {
        return $this->title !== ($this->engagement->title ?? '')
            || ($this->body ?: null) !== $this->engagement->body
            || ($this->status ?: null) !== $this->engagement->status
            || ($this->priority ?: null) !== $this->engagement->priority
            || ($this->scheduledAt ?: null) !== $this->engagement->scheduled_at?->toDateTimeLocalString()
            || ($this->endedAt ?: null) !== $this->engagement->ended_at?->toDateTimeLocalString()
            || ($this->completedAt ?: null) !== $this->engagement->completed_at?->toDateTimeLocalString();
    }

    // Contact linking
    public function openContactLinkModal(): void
    {
        $this->linkContactId = null;
        $this->contactLinkModalShow = true;
    }

    public function attachContact(): void
    {
        if (!$this->linkContactId) return;

        $contact = CrmContact::find($this->linkContactId);
        if ($contact) {
            $this->engagement->attachContact($contact);
            $this->engagement->load('contactLinks.contact');
        }

        $this->contactLinkModalShow = false;
        $this->linkContactId = null;
    }

    public function detachContact(int $contactId): void
    {
        $contact = CrmContact::find($contactId);
        if ($contact) {
            $this->engagement->detachContact($contact);
            $this->engagement->load('contactLinks.contact');
        }
    }

    // Company linking
    public function openCompanyLinkModal(): void
    {
        $this->linkCompanyId = null;
        $this->companyLinkModalShow = true;
    }

    public function attachCompany(): void
    {
        if (!$this->linkCompanyId) return;

        $company = CrmCompany::find($this->linkCompanyId);
        if ($company) {
            $this->engagement->attachCompany($company);
            $this->engagement->load('companyLinks.company');
        }

        $this->companyLinkModalShow = false;
        $this->linkCompanyId = null;
    }

    public function detachCompany(int $companyId): void
    {
        $company = CrmCompany::find($companyId);
        if ($company) {
            $this->engagement->detachCompany($company);
            $this->engagement->load('companyLinks.company');
        }
    }

    #[Computed]
    public function availableContacts()
    {
        $linkedIds = $this->engagement->contactLinks->pluck('contact_id');
        $teamId = $this->getTeamId();

        return CrmContact::active()
            ->where('team_id', $teamId)
            ->whereNotIn('id', $linkedIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    #[Computed]
    public function availableCompanies()
    {
        $linkedIds = $this->engagement->companyLinks->pluck('company_id');
        $teamId = $this->getTeamId();

        return CrmCompany::active()
            ->where('team_id', $teamId)
            ->whereNotIn('id', $linkedIds)
            ->orderBy('name')
            ->get();
    }

    public function addNote(): void
    {
        $this->validate(['newNote' => 'required|string|max:1000']);
        $this->engagement->logActivity($this->newNote);
        $this->newNote = '';
        $this->engagement->load('activities.user');
    }

    public function deleteNote(int $activityId): void
    {
        $this->engagement->activities()
            ->where('id', $activityId)
            ->where('activity_type', 'manual')
            ->where('user_id', auth()->id())
            ->delete();
        $this->engagement->load('activities.user');
    }

    public function render()
    {
        return view('crm::livewire.engagement.engagement')
            ->layout('platform::layouts.app');
    }

    private function getTeamId(): int
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }
}
