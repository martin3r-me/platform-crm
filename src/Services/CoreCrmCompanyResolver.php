<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmCompanyResolverInterface;
use Platform\Crm\Models\CrmCompany;

class CoreCrmCompanyResolver implements CrmCompanyResolverInterface
{
    public function displayName(?int $companyId): ?string
    {
        return $companyId ? CrmCompany::find($companyId)?->display_name : null;
    }

    public function url(?int $companyId): ?string
    {
        return $companyId ? route('crm.companies.show', ['company' => $companyId]) : null;
    }
}


