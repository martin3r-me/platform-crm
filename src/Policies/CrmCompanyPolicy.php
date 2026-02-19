<?php

namespace Platform\Crm\Policies;

use Platform\Core\Models\User;
use Platform\Crm\Models\CrmCompany;

class CrmCompanyPolicy
{
    public function view(User $user, CrmCompany $company): bool
    {
        return $user->teams()->where('teams.id', $company->team_id)->exists();
    }

    public function update(User $user, CrmCompany $company): bool
    {
        return $this->view($user, $company);
    }

    public function delete(User $user, CrmCompany $company): bool
    {
        // Owner darf immer löschen
        if ($company->owned_by_user_id && (int)$company->owned_by_user_id === (int)$user->id) {
            return true;
        }

        // Ownerlose Einträge (z.B. automatisch erstellt) dürfen von Team-Mitgliedern gelöscht werden
        if (!$company->owned_by_user_id) {
            return $this->view($user, $company);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->currentTeamRelation !== null || $user->current_team_id !== null;
    }
}


