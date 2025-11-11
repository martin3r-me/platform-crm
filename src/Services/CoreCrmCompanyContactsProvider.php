<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmCompanyContactsProviderInterface;
use Platform\Crm\Models\CrmContactRelation;
use Platform\Crm\Models\CrmCompany;
use Illuminate\Support\Facades\Auth;

class CoreCrmCompanyContactsProvider implements CrmCompanyContactsProviderInterface
{
    public function contacts(?int $companyId): array
    {
        if (!$companyId) {
            return [];
        }

        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $baseTeam = $user->currentTeamRelation;
        if (!$baseTeam) {
            return [];
        }

        // CRM ist Root-Scoped - verwende Root-Team
        $teamId = $baseTeam->getRootTeam()->id;

        $company = CrmCompany::find($companyId);
        if (!$company) {
            return [];
        }

        return $company->contactRelations()
            ->with(['contact.emailAddresses', 'contact.contactStatus', 'relationType'])
            ->active()
            ->current()
            ->get()
            ->map(function ($relation) use ($teamId) {
                $contact = $relation->contact;
                if (!$contact) {
                    return null;
                }

                // Prüfe Team-Zugehörigkeit direkt (Root-Team)
                if ($contact->team_id !== $teamId) {
                    return null;
                }

                // Prüfe ob Kontakt aktiv ist
                if (!$contact->is_active) {
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

