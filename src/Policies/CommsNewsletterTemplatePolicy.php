<?php

namespace Platform\Crm\Policies;

use Platform\Core\Models\User;
use Platform\Crm\Models\CommsNewsletterTemplate;

class CommsNewsletterTemplatePolicy
{
    public function view(User $user, CommsNewsletterTemplate $template): bool
    {
        return $user->teams()->where('teams.id', $template->team_id)->exists();
    }

    public function update(User $user, CommsNewsletterTemplate $template): bool
    {
        return $this->view($user, $template);
    }

    public function delete(User $user, CommsNewsletterTemplate $template): bool
    {
        if ($template->created_by_user_id && (int)$template->created_by_user_id === (int)$user->id) {
            return true;
        }

        if (!$template->created_by_user_id) {
            return $this->view($user, $template);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->currentTeamRelation !== null || $user->current_team_id !== null;
    }
}
