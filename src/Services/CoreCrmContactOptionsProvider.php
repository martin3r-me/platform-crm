<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmContactOptionsProviderInterface;
use Platform\Crm\Models\CrmContact;
use Illuminate\Support\Facades\Auth;

class CoreCrmContactOptionsProvider implements CrmContactOptionsProviderInterface
{
    public function options(?string $query = null, int $limit = 20): array
    {
        $teamId = Auth::user()?->currentTeam?->id;

        $q = CrmContact::query()
            ->when($teamId, fn($qq) => $qq->where('team_id', $teamId))
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($query) {
            $like = '%'.$query.'%';
            $q->where(function ($w) use ($like) {
                $w->where('first_name', 'like', $like)
                  ->orWhere('last_name', 'like', $like)
                  ->orWhereHas('emailAddresses', function ($emailQuery) use ($like) {
                      $emailQuery->where('email_address', 'like', $like);
                  });
            });
        }

        return $q->with('emailAddresses')
            ->limit($limit)
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn($c) => [
                'value' => $c->id,
                'label' => $c->display_name,
            ])
            ->all();
    }
}
