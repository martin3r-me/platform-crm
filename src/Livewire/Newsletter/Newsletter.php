<?php

namespace Platform\Crm\Livewire\Newsletter;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Platform\Crm\Models\CommsChannel;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Services\Comms\NewsletterService;

class Newsletter extends Component
{
    public CommsNewsletter $newsletter;

    // Editable fields
    public string $name = '';
    public string $subject = '';
    public ?string $preheader = null;
    public ?int $commsChannelId = null;
    public array $contactListIds = [];
    public ?string $scheduledAt = null;
    public ?string $htmlBody = null;
    public ?string $textBody = null;

    // UI state
    public string $activeTab = 'settings';

    // Prev/Next navigation
    public ?int $prevNewsletterId = null;
    public ?int $nextNewsletterId = null;

    public function mount(CommsNewsletter $newsletter)
    {
        $this->newsletter = $newsletter->load(['channel', 'contactLists', 'createdByUser']);

        $this->name = $this->newsletter->name ?? '';
        $this->subject = $this->newsletter->subject ?? '';
        $this->preheader = $this->newsletter->preheader;
        $this->commsChannelId = $this->newsletter->comms_channel_id;
        $this->contactListIds = $this->newsletter->contactLists->pluck('id')->toArray();
        $this->scheduledAt = $this->newsletter->scheduled_at?->format('Y-m-d\TH:i');
        $this->htmlBody = $this->newsletter->html_body;
        $this->textBody = $this->newsletter->text_body;

        // Prev/Next navigation from index list
        $nav = session('crm.newsletter_nav');
        if ($nav && !empty($nav['ids'])) {
            $ids = $nav['ids'];
            $pos = array_search($this->newsletter->id, $ids);
            if ($pos !== false) {
                $this->prevNewsletterId = $pos > 0 ? $ids[$pos - 1] : null;
                $this->nextNewsletterId = $pos < count($ids) - 1 ? $ids[$pos + 1] : null;
            }
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'preheader' => 'nullable|string|max:255',
            'commsChannelId' => 'nullable|integer',
            'contactListIds' => 'nullable|array',
            'contactListIds.*' => 'integer',
            'scheduledAt' => 'nullable|date',
            'htmlBody' => 'nullable|string',
            'textBody' => 'nullable|string',
        ]);

        if (!$this->newsletter->canEdit()) {
            session()->flash('error', 'Newsletter kann in diesem Status nicht bearbeitet werden.');
            return;
        }

        $this->newsletter->name = $this->name;
        $this->newsletter->subject = $this->subject;
        $this->newsletter->preheader = $this->preheader ?: null;
        $this->newsletter->comms_channel_id = $this->commsChannelId ?: null;
        $this->newsletter->scheduled_at = $this->scheduledAt ?: null;
        $this->newsletter->html_body = $this->htmlBody ?: null;
        $this->newsletter->text_body = $this->textBody ?: null;

        $this->newsletter->save();
        $this->newsletter->contactLists()->sync($this->contactListIds);
        $this->newsletter->refresh();

        $this->scheduledAt = $this->newsletter->scheduled_at?->format('Y-m-d\TH:i');

        session()->flash('message', 'Newsletter erfolgreich gespeichert.');
    }

    public function schedule(): void
    {
        $this->save();

        if (!$this->newsletter->scheduled_at) {
            session()->flash('error', 'Bitte einen Zeitpunkt für den Versand festlegen.');
            return;
        }

        $this->newsletter->update(['status' => 'scheduled']);
        $this->newsletter->refresh();

        session()->flash('message', 'Newsletter wurde eingeplant.');
    }

    public function sendNow(): void
    {
        $this->save();

        try {
            app(NewsletterService::class)->send($this->newsletter);
            $this->newsletter->refresh();
            session()->flash('message', 'Newsletter-Versand wurde gestartet.');
        } catch (\Throwable $e) {
            session()->flash('error', 'Fehler beim Versand: ' . $e->getMessage());
        }
    }

    public function cancelSchedule(): void
    {
        if ($this->newsletter->isScheduled()) {
            $this->newsletter->update(['status' => 'draft']);
            $this->newsletter->refresh();
            session()->flash('message', 'Zeitplan wurde abgebrochen.');
        }
    }

    public function cancelSending(): void
    {
        if ($this->newsletter->isSending()) {
            $this->newsletter->update(['status' => 'cancelled']);
            $this->newsletter->refresh();
            session()->flash('message', 'Versand wurde abgebrochen.');
        }
    }

    public function duplicate(): void
    {
        $copy = $this->newsletter->replicate();
        $copy->uuid = null;
        $copy->name = $this->newsletter->name . ' (Kopie)';
        $copy->status = 'draft';
        $copy->scheduled_at = null;
        $copy->sent_at = null;
        $copy->stats = null;
        $copy->save();
        $copy->contactLists()->sync($this->newsletter->contactLists->pluck('id'));

        $this->redirect(route('crm.newsletters.show', ['newsletter' => $copy->id]), navigate: true);
    }

    public function delete(): void
    {
        $this->newsletter->delete();
        session()->flash('message', 'Newsletter erfolgreich gelöscht.');
        $this->redirect(route('crm.newsletters.index'), navigate: true);
    }

    #[Computed]
    public function isDirty(): bool
    {
        return $this->name !== ($this->newsletter->name ?? '')
            || $this->subject !== ($this->newsletter->subject ?? '')
            || ($this->preheader ?: null) !== $this->newsletter->preheader
            || ($this->commsChannelId ?: null) != $this->newsletter->comms_channel_id
            || $this->contactListIds !== $this->newsletter->contactLists->pluck('id')->toArray()
            || ($this->scheduledAt ?: null) !== $this->newsletter->scheduled_at?->format('Y-m-d\TH:i')
            || ($this->htmlBody ?: null) !== $this->newsletter->html_body
            || ($this->textBody ?: null) !== $this->newsletter->text_body;
    }

    #[Computed]
    public function channels()
    {
        return CommsChannel::where('team_id', $this->getTeamId())
            ->where('type', 'email')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function contactLists()
    {
        return CrmContactList::forTeam($this->getTeamId())
            ->active()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function recipients()
    {
        return $this->newsletter->recipients()
            ->with('contact')
            ->orderBy('email_address')
            ->get();
    }

    #[Computed]
    public function stats(): array
    {
        return $this->newsletter->stats ?? [];
    }

    public function render()
    {
        return view('crm::livewire.newsletter.newsletter')
            ->layout('platform::layouts.app');
    }

    private function getTeamId(): int
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;

        return $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
    }
}
