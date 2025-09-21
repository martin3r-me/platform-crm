<?php

namespace Platform\Crm\Services;

use Platform\Crm\Models\CrmContact;

class CoreCrmContactResolver
{
    public function displayName(?int $contactId): ?string
    {
        if (!$contactId) {
            return null;
        }

        $contact = CrmContact::find($contactId);
        return $contact ? $contact->display_name : null;
    }

    public function url(?int $contactId): ?string
    {
        if (!$contactId) {
            return null;
        }

        return route('crm.contacts.show', $contactId);
    }
}
