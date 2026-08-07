<?php

namespace Platform\Crm\Organization;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Platform\Organization\Contracts\EntityLinkProvider;
use Platform\Organization\Contracts\HasMetricDefinitions;

class CrmEntityLinkProvider implements EntityLinkProvider, HasMetricDefinitions
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
        if ($morphAlias === 'crm_company') {
            $query->with(['postalAddresses', 'phoneNumbers', 'emailAddresses', 'contactStatus', 'industry']);
        } elseif ($morphAlias === 'crm_contact') {
            $query->with(['postalAddresses', 'phoneNumbers', 'emailAddresses', 'contactStatus']);
        }
    }

    /**
     * Generische Anzeige-Metadaten für den Graphen — damit andere Module (z. B. customer)
     * die CRM-Stammdaten zeigen können, OHNE das CRM-Model direkt zu kennen.
     */
    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        if ($model === null) {
            return [];
        }

        if ($morphAlias === 'crm_contact') {
            return $this->extractContactMetadata($model);
        }

        if ($morphAlias !== 'crm_company') {
            return [];
        }

        $addr  = $model->postalAddresses->firstWhere('is_primary', true) ?? $model->postalAddresses->first();
        $phone = $model->phoneNumbers->firstWhere('is_primary', true) ?? $model->phoneNumbers->first();
        $email = $model->emailAddresses->firstWhere('is_primary', true) ?? $model->emailAddresses->first();

        return array_filter([
            'name'       => $model->name,
            'legal_name' => $model->legal_name,
            'status'     => $model->contactStatus?->name,
            'industry'   => $model->industry?->name,
            'website'    => $model->website,
            'vat_number' => $model->vat_number,
            'address'    => $addr
                ? trim(trim(($addr->street . ' ' . $addr->house_number)) . ', ' . trim($addr->postal_code . ' ' . $addr->city), ', ')
                : null,
            'phone'      => $phone?->international ?? $phone?->national ?? $phone?->raw_input,
            'email'      => $email?->email_address,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Personen-Stammdaten eines crm_contact — damit people den Menschen am Personen-Knoten
     * anreichern kann, OHNE das CRM-Model direkt zu kennen.
     */
    protected function extractContactMetadata(mixed $model): array
    {
        $addr  = $model->postalAddresses->firstWhere('is_primary', true) ?? $model->postalAddresses->first();
        $phone = $model->phoneNumbers->firstWhere('is_primary', true) ?? $model->phoneNumbers->first();
        $email = $model->emailAddresses->firstWhere('is_primary', true) ?? $model->emailAddresses->first();

        return array_filter([
            'name'       => trim(($model->first_name ?? '') . ' ' . ($model->last_name ?? '')),
            'status'     => $model->contactStatus?->name,
            'birth_date' => $model->birth_date?->toDateString(),
            'address'    => $addr
                ? trim(trim(($addr->street . ' ' . $addr->house_number)) . ', ' . trim($addr->postal_code . ' ' . $addr->city), ', ')
                : null,
            'phone'      => $phone?->international ?? $phone?->national ?? $phone?->raw_input,
            'email'      => $email?->email_address,
        ], fn ($v) => $v !== null && $v !== '');
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

    public function metricDefinitions(): array
    {
        return [
            'crm_contacts_total'   => ['label' => 'Kontakte (gesamt)', 'group' => 'crm', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up'],
            'crm_contacts_active'  => ['label' => 'Kontakte (aktiv)', 'group' => 'crm', 'direction' => 'up', 'unit' => 'count', 'pair' => 'crm_contacts_total', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up'],
            'crm_companies_total'  => ['label' => 'Unternehmen (gesamt)', 'group' => 'crm', 'direction' => 'neutral', 'unit' => 'count', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up'],
            'crm_companies_active' => ['label' => 'Unternehmen (aktiv)', 'group' => 'crm', 'direction' => 'up', 'unit' => 'count', 'pair' => 'crm_companies_total', 'dimension' => 'org_capital', 'type' => 'stock', 'aggregation_mode' => 'rolled_up'],
        ];
    }
}
