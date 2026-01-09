<?php

namespace Platform\Crm\Tools\Concerns;

use Platform\Core\Models\User;

trait ResolvesCrmTeam
{
    protected function resolveRootTeamId(User $user): ?int
    {
        try {
            $baseTeam = $user->currentTeamRelation;
            $root = $baseTeam ? $baseTeam->getRootTeam() : null;
            return $root?->id ?? $user->current_team_id;
        } catch (\Throwable $e) {
            return $user->current_team_id;
        }
    }
}


