<?php

namespace Platform\Crm\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactLink;

trait HasContactLinksTrait
{
    /**
     * Kontakte, die diesem Modell zugeordnet sind (nur sichtbare).
     */
    public function contactLinks(): MorphMany
    {
        return $this->morphMany(CrmContactLink::class, 'linkable')
            ->forCurrentTeam();
    }

    /**
     * Alle Kontakte (auch unsichtbare) - für Admin-Zwecke.
     */
    public function allContactLinks(): MorphMany
    {
        return $this->morphMany(CrmContactLink::class, 'linkable')
            ->forCurrentTeam();
    }

    /**
     * Direkter Zugriff auf die Kontakte (Collection von CrmContact).
     */
    public function contacts(): Collection
    {
        return $this->contactLinks()
            ->with('contact')
            ->get()
            ->pluck('contact')
            ->filter(function ($contact) {
                return $contact && $contact->isVisible();
            });
    }

    /**
     * Einen Kontakt anhängen (wenn noch nicht vorhanden).
     */
    public function attachContact(CrmContact $contact): bool
    {
        // Prüfen ob Kontakt im aktuellen Team sichtbar ist
        if (!$contact->isVisible()) {
            return false;
        }

        if (! $this->hasContact($contact)) {
            $this->contactLinks()->create([
                'contact_id' => $contact->id,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * Mehrere Kontakte anhängen.
     */
    public function attachContacts(Collection|array $contacts): void
    {
        $visibleContacts = collect($contacts)->filter(function ($contact) {
            return $contact->isVisible();
        });

        $contactIds = $visibleContacts->pluck('id');
        $existingIds = $this->contactLinks()->pluck('contact_id');
        $newIds = $contactIds->diff($existingIds);
        
        foreach ($newIds as $contactId) {
            $this->contactLinks()->create([
                'contact_id' => $contactId,
                'team_id' => auth()->user()->current_team_id,
                'created_by_user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Einen Kontakt entfernen (nur wenn man der Ersteller ist).
     */
    public function detachContact(CrmContact $contact): bool
    {
        $link = $this->contactLinks()
            ->where('contact_id', $contact->id)
            ->first();

        if ($link && $link->created_by_user_id === auth()->id()) {
            return $link->delete();
        }

        return false;
    }

    /**
     * Alle Kontakte entfernen (nur eigene).
     */
    public function detachAllContacts(): int
    {
        return $this->contactLinks()
            ->where('created_by_user_id', auth()->id())
            ->delete();
    }

    /**
     * Kontakte synchronisieren (alle entfernen und neue hinzufügen).
     */
    public function syncContacts(Collection|array $contacts): void
    {
        $this->detachAllContacts();
        $this->attachContacts($contacts);
    }

    /**
     * Prüfen, ob ein bestimmter Kontakt verlinkt ist.
     */
    public function hasContact(CrmContact $contact): bool
    {
        return $this->contactLinks()
            ->where('contact_id', $contact->id)
            ->exists();
    }

    /**
     * Anzahl der verlinkten Kontakte.
     */
    public function contactsCount(): int
    {
        return $this->contacts()->count();
    }
} 