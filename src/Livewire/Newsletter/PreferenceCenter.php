<?php

namespace Platform\Crm\Livewire\Newsletter;

use Livewire\Component;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Models\CrmContactListMember;
use Platform\Crm\Services\Comms\SubscriptionService;

class PreferenceCenter extends Component
{
    public int $teamId;
    public string $email;
    public bool $isGloballyUnsubscribed = false;
    public array $listSubscriptions = [];

    public function mount(int $team, string $email): void
    {
        $this->teamId = $team;
        $this->email = $email;

        $this->loadData();
    }

    public function toggleList(int $listId): void
    {
        $service = app(SubscriptionService::class);

        $subscription = collect($this->listSubscriptions)->firstWhere('list_id', $listId);
        if (!$subscription) {
            return;
        }

        if ($subscription['status'] === 'subscribed') {
            // Unsubscribe
            $service->unsubscribeFromList($this->teamId, $this->email, $listId, 'preference_center');
            session()->flash('message', 'Sie wurden erfolgreich abgemeldet.');
        } else {
            // Subscribe (or re-subscribe)
            $list = CrmContactList::find($listId);
            $contact = $this->findContact();

            if ($list && $contact) {
                $member = $service->subscribe($list, $contact, 'preference_center');

                if ($member->isPendingDoi()) {
                    session()->flash('message', 'Wir haben Ihnen eine Bestätigungs-E-Mail gesendet. Bitte prüfen Sie Ihr Postfach.');
                } else {
                    session()->flash('message', 'Sie wurden erfolgreich angemeldet.');
                }
            }
        }

        $this->loadData();
    }

    public function globalUnsubscribe(): void
    {
        app(SubscriptionService::class)->globalUnsubscribe($this->teamId, $this->email, 'preference_center');

        session()->flash('message', 'Sie wurden von allen Newslettern abgemeldet.');

        $this->loadData();
    }

    public function resubscribeGlobal(): void
    {
        app(SubscriptionService::class)->resubscribeGlobal($this->teamId, $this->email);

        session()->flash('message', 'Ihre globale Abmeldung wurde aufgehoben. Sie können nun einzelne Listen wieder aktivieren.');

        $this->loadData();
    }

    public function render()
    {
        return view('crm::livewire.newsletter.preference-center')
            ->layout('crm::layouts.guest', ['title' => 'Newsletter-Einstellungen']);
    }

    private function loadData(): void
    {
        $data = app(SubscriptionService::class)->getPreferenceCenterData($this->teamId, $this->email);

        $this->isGloballyUnsubscribed = $data['is_globally_unsubscribed'];
        $this->listSubscriptions = $data['list_subscriptions'];
    }

    private function findContact(): ?CrmContact
    {
        $emailLower = strtolower(trim($this->email));

        return CrmContact::where('team_id', $this->teamId)
            ->whereHas('emailAddresses', fn ($q) => $q->whereRaw('LOWER(email) = ?', [$emailLower]))
            ->first();
    }
}
