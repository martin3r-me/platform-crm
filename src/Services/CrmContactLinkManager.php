<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmContactLinkManagerInterface;
use Platform\Crm\Models\CrmContactLink;
use Platform\Crm\Models\CrmContact;
use Illuminate\Support\Facades\Auth;

class CrmContactLinkManager implements CrmContactLinkManagerInterface
{
    public function getLinkedContacts(string $linkableType, int $linkableId): array
    {
        $user = Auth::user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;

        if (!$teamId) {
            return [];
        }

        $links = CrmContactLink::where('linkable_type', $linkableType)
            ->where('linkable_id', $linkableId)
            ->where('team_id', $teamId)
            ->with(['contact.emailAddresses', 'contact.contactStatus'])
            ->get();

        return $links->map(function ($link) {
            $contact = $link->contact;
            if (!$contact) {
                return null;
            }

            $emailAddress = $contact->emailAddresses()
                ->where('is_primary', true)
                ->first()
                ?? $contact->emailAddresses()->first();

            return [
                'id' => $contact->id,
                'link_id' => $link->id,
                'name' => $contact->display_name,
                'email' => $emailAddress?->email_address,
            ];
        })
        ->filter()
        ->values()
        ->all();
    }

    public function syncContactLinks(string $linkableType, int $linkableId, array $selectedContactIds): void
    {
        $user = Auth::user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : null;

        if (!$teamId) {
            return;
        }

        $currentLinks = CrmContactLink::where('linkable_type', $linkableType)
            ->where('linkable_id', $linkableId)
            ->where('team_id', $teamId)
            ->get();

        $currentContactIds = $currentLinks->pluck('contact_id')->toArray();
        $selectedContactIds = array_map('intval', $selectedContactIds);

        // Zu entfernende Links
        $toRemove = array_diff($currentContactIds, $selectedContactIds);
        foreach ($toRemove as $contactId) {
            $link = $currentLinks->firstWhere('contact_id', $contactId);
            if ($link && $link->created_by_user_id === Auth::id()) {
                $link->delete();
            }
        }

        // Neue Links hinzufügen
        $toAdd = array_diff($selectedContactIds, $currentContactIds);
        foreach ($toAdd as $contactId) {
            $contact = CrmContact::find($contactId);
            if ($contact && $contact->team_id === $teamId && $contact->is_active) {
                CrmContactLink::create([
                    'contact_id' => $contactId,
                    'linkable_type' => $linkableType,
                    'linkable_id' => $linkableId,
                    'team_id' => $teamId,
                    'created_by_user_id' => Auth::id(),
                ]);
            }
        }
    }
}
