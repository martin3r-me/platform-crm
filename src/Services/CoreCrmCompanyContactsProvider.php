<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmCompanyContactsProviderInterface;
use Platform\Crm\Models\CrmContactRelation;
use Platform\Crm\Models\CrmCompany;

class CoreCrmCompanyContactsProvider implements CrmCompanyContactsProviderInterface
{
    public function contacts(?int $companyId): array
    {
        if (!$companyId) {
            return [];
        }

        $company = CrmCompany::find($companyId);
        if (!$company) {
            return [];
        }

        return $company->contactRelations()
            ->with(['contact.emailAddresses', 'contact.contactStatus', 'relationType'])
            ->active()
            ->current()
            ->get()
            ->map(function ($relation) {
                $contact = $relation->contact;
                if (!$contact || !$contact->isVisible()) {
                    return null;
                }

                $emailAddress = $contact->emailAddresses()
                    ->where('is_primary', true)
                    ->first()
                    ?? $contact->emailAddresses()->first();

                return [
                    'id' => $contact->id,
                    'name' => $contact->display_name,
                    'email' => $emailAddress?->email_address,
                    'position' => $relation->position,
                    'is_primary' => (bool) $relation->is_primary,
                    'relation_type' => $relation->relationType?->name,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}

