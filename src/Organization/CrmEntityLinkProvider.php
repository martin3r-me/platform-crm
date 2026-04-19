<?php

namespace Platform\Crm\Organization;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Platform\Organization\Contracts\EntityLinkProvider;

class CrmEntityLinkProvider implements EntityLinkProvider
{
    public function morphAliases(): array
    {
        return ['crm_contact', 'crm_company'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'crm_contact' => [
                'label' => 'Kontakte',
                'singular' => 'Kontakt',
                'icon' => 'user',
                'route' => 'crm.contacts.show',
            ],
            'crm_company' => [
                'label' => 'Unternehmen',
                'singular' => 'Unternehmen',
                'icon' => 'building-office',
                'route' => 'crm.companies.show',
            ],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        // Keine speziellen Eager-Loadings nötig.
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [];
    }

    public function metadataDisplayRules(): array
    {
        return [];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }

    public function metrics(string $morphAlias, array $linksByEntity): array
    {
        return match ($morphAlias) {
            'crm_contact' => $this->contactMetrics($linksByEntity),
            'crm_company' => $this->companyMetrics($linksByEntity),
            default => [],
        };
    }

    protected function contactMetrics(array $linksByEntity): array
    {
        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        $activeIds = DB::table('crm_contacts')
            ->whereIn('id', $allIds)
            ->where('is_active', true)
            ->pluck('id')
            ->flip()
            ->all();

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = count($ids);
            $active = 0;
            foreach ($ids as $id) {
                if (isset($activeIds[$id])) {
                    $active++;
                }
            }

            $result[$entityId] = [
                'crm_contacts_total' => $total,
                'crm_contacts_active' => $active,
            ];
        }

        return $result;
    }

    protected function companyMetrics(array $linksByEntity): array
    {
        $allIds = [];
        foreach ($linksByEntity as $ids) {
            $allIds = array_merge($allIds, $ids);
        }
        $allIds = array_values(array_unique($allIds));

        if (empty($allIds)) {
            return [];
        }

        $activeIds = DB::table('crm_companies')
            ->whereIn('id', $allIds)
            ->where('is_active', true)
            ->pluck('id')
            ->flip()
            ->all();

        $result = [];
        foreach ($linksByEntity as $entityId => $ids) {
            $total = count($ids);
            $active = 0;
            foreach ($ids as $id) {
                if (isset($activeIds[$id])) {
                    $active++;
                }
            }

            $result[$entityId] = [
                'crm_companies_total' => $total,
                'crm_companies_active' => $active,
            ];
        }

        return $result;
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }
}
