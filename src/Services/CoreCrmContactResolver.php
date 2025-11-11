<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmContactResolverInterface;
use Platform\Crm\Models\CrmContact;

class CoreCrmContactResolver implements CrmContactResolverInterface
{
    public function displayName(?int $contactId): ?string
    {
        if (!$contactId) {
            return null;
        }

        $contact = CrmContact::find($contactId);
        return $contact ? $contact->display_name : null;
    }

    public function email(?int $contactId): ?string
    {
        if (!$contactId) {
            return null;
        }

        $contact = CrmContact::find($contactId);
        if (!$contact) {
            return null;
        }

        // Erste E-Mail-Adresse zurückgeben
        $emailAddress = $contact->emailAddresses()->where('is_primary', true)->first()
            ?? $contact->emailAddresses()->first();

        return $emailAddress?->email_address;
    }

    public function url(?int $contactId): ?string
    {
        if (!$contactId) {
            return null;
        }

        return route('crm.contacts.show', $contactId);
    }
}
