<?php

namespace Platform\Crm\Policies;

use Platform\Core\Models\User;
use Platform\Crm\Models\CommsNewsletter;

class CommsNewsletterPolicy
{
    public function view(User $user, CommsNewsletter $newsletter): bool
    {
        return $user->teams()->where('teams.id', $newsletter->team_id)->exists();
    }

    public function update(User $user, CommsNewsletter $newsletter): bool
    {
        return $this->view($user, $newsletter);
    }

    public function delete(User $user, CommsNewsletter $newsletter): bool
    {
        if ($newsletter->created_by_user_id && (int)$newsletter->created_by_user_id === (int)$user->id) {
            return true;
        }

        if (!$newsletter->created_by_user_id) {
            return $this->view($user, $newsletter);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->currentTeamRelation !== null || $user->current_team_id !== null;
    }
}
