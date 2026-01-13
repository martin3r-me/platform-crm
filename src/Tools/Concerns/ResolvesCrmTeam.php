<?php

namespace Platform\Crm\Tools\Concerns;

use Platform\Core\Models\User;
use Platform\Core\Models\Team;

trait ResolvesCrmTeam
{
    /**
     * CRM ist root-scoped (Parent-Team).
     *
     * Wichtig: In vielen Flows (z.B. Planner → AI-Worker) ist das "current team" ein Kind-Team.
     * CRM-Daten sollen aber immer unter der Root-Team-ID gespeichert/abgerufen werden.
     */
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

    /**
     * Normalisiert eine beliebige Team-ID (Root oder Child) auf die CRM-Root-Team-ID.
     */
    protected function normalizeToRootTeamId(?int $teamId, User $user): ?int
    {
        if (!$teamId) {
            return $this->resolveRootTeamId($user);
        }

        try {
            $team = Team::find((int)$teamId);
            if ($team) {
                return (int)$team->getRootTeam()->id;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Fallback: wenn Team nicht geladen werden kann, nutze zumindest den User-Kontext
        return $this->resolveRootTeamId($user) ?? (int)$teamId;
    }

    /**
     * CRM-Zugriff: User darf auf Root-Team arbeiten, wenn er entweder im Root-Team selbst ODER in einem Kind-Team ist.
     */
    protected function userHasAccessToCrmRootTeam(User $user, int $rootTeamId): bool
    {
        // Direct membership
        if ($user->teams()->where('teams.id', $rootTeamId)->exists()) {
            return true;
        }

        $rootTeam = Team::find($rootTeamId);
        if (!$rootTeam) {
            return false;
        }

        // Child membership (any team under the same root)
        try {
            $userTeams = $user->teams()->get();
            foreach ($userTeams as $t) {
                if ($t && $t->isChildOf($rootTeam)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return false;
    }
}


