<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmCompanyOptionsProviderInterface;
use Platform\Crm\Models\CrmCompany;
use Illuminate\Support\Facades\Auth;

class CoreCrmCompanyOptionsProvider implements CrmCompanyOptionsProviderInterface
{
    public function options(?string $query = null, int $limit = 20): array
    {
        $teamId = Auth::user()?->currentTeam?->id;

        $q = CrmCompany::query()
            ->when($teamId, fn($qq) => $qq->where('team_id', $teamId))
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


