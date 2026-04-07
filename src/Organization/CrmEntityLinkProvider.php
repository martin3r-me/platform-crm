<?php

namespace Platform\Crm\Organization;

use Illuminate\Database\Eloquent\Builder;
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
        return [];
    }

    public function activityChildren(string $morphAlias, array $linkableIds): array
    {
        return [];
    }
}
