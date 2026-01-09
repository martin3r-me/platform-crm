<?php

namespace Platform\Crm\Policies;

use Platform\Core\Models\User;
use Platform\Crm\Models\CrmContact;

class CrmContactPolicy
{
    public function view(User $user, CrmContact $contact): bool
    {
        return $user->teams()->where('teams.id', $contact->team_id)->exists();
    }

    public function update(User $user, CrmContact $contact): bool
    {
        return $this->view($user, $contact);
    }

    public function delete(User $user, CrmContact $contact): bool
    {
        // konservativ wie bestehende Tools: löschen nur der Owner
        return (int)($contact->owned_by_user_id ?? 0) === (int)$user->id;
    }

    public function create(User $user): bool
    {
        return $user->currentTeamRelation !== null || $user->current_team_id !== null;
    }
}


