<?php

namespace Platform\Crm\Policies;

use Platform\Core\Models\User;
use Platform\Crm\Models\CrmContactList;

class CrmContactListPolicy
{
    public function view(User $user, CrmContactList $list): bool
    {
        return $user->teams()->where('teams.id', $list->team_id)->exists();
    }

    public function update(User $user, CrmContactList $list): bool
    {
        return $this->view($user, $list);
    }

    public function delete(User $user, CrmContactList $list): bool
    {
        if ($list->created_by_user_id && (int)$list->created_by_user_id === (int)$user->id) {
            return true;
        }

        if (!$list->created_by_user_id) {
            return $this->view($user, $list);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->currentTeamRelation !== null || $user->current_team_id !== null;
    }
}
