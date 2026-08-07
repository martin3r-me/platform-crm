<?php

namespace Platform\Crm\Customer;

use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmContactStatus;
use Platform\Customer\Contracts\CompanyDirectoryProvider;

/**
 * CrmCompanyDirectory — stellt dem customer-Modul CRM-Firmen zum Suchen/Anlegen bereit
 * (Inversion). Wird nur registriert, wenn das customer-Contract vorhanden ist (guarded).
 */
class CrmCompanyDirectory implements CompanyDirectoryProvider
{
    public function search(int $teamId, string $query): array
    {
        return CrmCompany::query()
            ->where('team_id', $teamId)
            ->when($query !== '', fn ($q) => $q->where('name', 'like', '%' . $query . '%'))
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(fn ($c) => ['id' => (int) $c->id, 'label' => $c->name, 'subtitle' => $c->legal_name])
            ->all();
    }

    public function createCompany(int $teamId, string $name): ?int
    {
        $statusId = CrmContactStatus::query()->where('code', 'CUSTOMER')->value('id');

        $company = CrmCompany::create([
            'team_id'            => $teamId,
            'name'              => $name,
            'contact_status_id' => $statusId,
            'created_by_user_id' => auth()->id(),
            'owned_by_user_id'  => auth()->id(),
            'is_active'         => true,
        ]);

        return (int) $company->id;
    }
}
