<?php

namespace Platform\Crm\Services;

use Illuminate\Support\Collection;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactLink;
use Platform\Crm\Contracts\ContactLinkableInterface;

class ContactLinkService
{
    /**
     * Finde Kontakte basierend auf E-Mail-Adressen
     */
    public function findContactsByEmailAddresses(array $emails): Collection
    {
        return CrmContact::whereHas('emailAddresses', function ($query) use ($emails) {
            $query->whereIn('email_address', $emails);
        })
        ->with('emailAddresses', 'contactStatus')
        ->get()
        ->filter(fn($contact) => $contact->isVisible());
    }

    /**
     * Finde Kontakt anhand ID
     */
    public function findContactById(int $id): ?CrmContact
    {
        $contact = CrmContact::with('emailAddresses')->find($id);
        return $contact && $contact->isVisible() ? $contact : null;
    }

    /**
     * Hole alle sichtbaren Kontakte
     */
    public function getAllVisibleContacts(int $limit = 20): Collection
    {
        return CrmContact::query()
            ->with('emailAddresses')
            ->limit($limit)
            ->get()
            ->filter(fn($contact) => $contact->isVisible());
    }

    /**
     * Suche Kontakte
     */
    public function searchContacts(string $search, int $limit = 10): Collection
    {
        if (empty($search)) {
            return $this->getAllVisibleContacts($limit);
        }

        return CrmContact::query()
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhereHas('emailAddresses', function ($q) use ($search) {
                          $q->where('email_address', 'like', '%' . $search . '%');
                      });
            })
            ->with('emailAddresses')
            ->limit($limit)
            ->get()
            ->filter(fn($contact) => $contact->isVisible());
    }

    /**
     * Erstelle neuen Kontakt
     */
    public function createContact(array $data): CrmContact
    {
        $user = auth()->user();
        $baseTeam = $user->currentTeamRelation;
        $teamId = $baseTeam ? $baseTeam->getRootTeam()->id : $user->current_team_id;
        
        $contact = CrmContact::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'team_id' => $teamId,
            'created_by_user_id' => auth()->id(),
            'owned_by_user_id' => $data['team_visible'] ?? true ? null : auth()->id(),
        ]);

        // E-Mail-Adresse hinzufügen falls vorhanden
        if (!empty($data['email'])) {
            $contact->emailAddresses()->create([
                'email_address' => $data['email'],
                'email_type_id' => 1, // Default E-Mail-Typ
            ]);
        }

        return $contact->load('emailAddresses');
    }

    /**
     * Füge E-Mail-Adresse zu Kontakt hinzu
     */
    public function addEmailToContact(CrmContact $contact, string $email): bool
    {
        if (!$contact->emailAddresses()->where('email_address', $email)->exists()) {
            $contact->emailAddresses()->create([
                'email_address' => $email,
                'email_type_id' => 1, // Default E-Mail-Typ
            ]);
            return true;
        }
        return false;
    }

    /**
     * Automatisches Verlinken von Kontakten basierend auf E-Mail-Adressen
     */
    public function autoLinkContacts(ContactLinkableInterface $linkable): void
    {
        $emails = $linkable->getEmailAddresses();
        
        if (empty($emails)) {
            return;
        }

        $contacts = $this->findContactsByEmailAddresses($emails);
        
        foreach ($contacts as $contact) {
            $this->createContactLink($linkable, $contact);
        }
    }

    /**
     * Erstelle einen Contact-Link
     */
    public function createContactLink(ContactLinkableInterface $linkable, CrmContact $contact): bool
    {
        // Prüfe ob Link bereits existiert
        $existingLink = CrmContactLink::where([
            'contact_id' => $contact->id,
            'linkable_id' => $linkable->getContactLinkableId(),
            'linkable_type' => $linkable->getContactLinkableType(),
            'team_id' => $linkable->getTeamId(),
        ])->exists();

        if ($existingLink) {
            return false;
        }

        // Erstelle neuen Link
        CrmContactLink::create([
            'contact_id' => $contact->id,
            'linkable_id' => $linkable->getContactLinkableId(),
            'linkable_type' => $linkable->getContactLinkableType(),
            'team_id' => $linkable->getTeamId(),
            'created_by_user_id' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Entferne einen Contact-Link
     */
    public function removeContactLink(ContactLinkableInterface $linkable, CrmContact $contact): bool
    {
        $link = CrmContactLink::where([
            'contact_id' => $contact->id,
            'linkable_id' => $linkable->getContactLinkableId(),
            'linkable_type' => $linkable->getContactLinkableType(),
            'team_id' => $linkable->getTeamId(),
        ])->first();

        if ($link && $link->created_by_user_id === auth()->id()) {
            return $link->delete();
        }

        return false;
    }

    /**
     * Entferne alle Contact-Links für ein Objekt
     */
    public function removeAllContactLinks(ContactLinkableInterface $linkable): int
    {
        return CrmContactLink::where([
            'linkable_id' => $linkable->getContactLinkableId(),
            'linkable_type' => $linkable->getContactLinkableType(),
            'team_id' => $linkable->getTeamId(),
            'created_by_user_id' => auth()->id(),
        ])->delete();
    }

    /**
     * Hole alle verlinkten Kontakte für ein Objekt
     */
    public function getLinkedContacts(ContactLinkableInterface $linkable): Collection
    {
        return CrmContactLink::where([
            'linkable_id' => $linkable->getContactLinkableId(),
            'linkable_type' => $linkable->getContactLinkableType(),
            'team_id' => $linkable->getTeamId(),
        ])
        ->with('contact.emailAddresses', 'contact.contactStatus')
        ->get()
        ->pluck('contact')
        ->filter(fn($contact) => $contact && $contact->isVisible());
    }
} 