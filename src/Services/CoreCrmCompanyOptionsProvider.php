<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmCompanyOptionsProviderInterface;
use Platform\Crm\Models\CrmCompany;
use Illuminate\Support\Facades\Auth;

class CoreCrmCompanyOptionsProvider implements CrmCompanyOptionsProviderInterface
{
    public function options(?string $query = null, int $limit = 20): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        $baseTeam = $user->currentTeamRelation;
        if (!$baseTeam) {
            return [];
        }

        // CRM ist immer Root-Scoped - verwende Root-Team
        $teamId = $baseTeam->getRootTeam()->id;

        $q = CrmCompany::query()
            ->where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($query) {
            $like = '%'.$query.'%';
            $q->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)
                  ->orWhere('legal_name', 'like', $like)
                  ->orWhere('trading_name', 'like', $like);
            });
        }

        return $q->limit($limit)
            ->get(['id', 'name', 'legal_name', 'trading_name'])
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->display_name,
            ])
            ->all();
    }
}


