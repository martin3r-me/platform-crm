<?php

namespace Platform\Crm\Services;

use Platform\Core\Contracts\CrmEngagementManagerInterface;
use Platform\Crm\Models\CrmEngagement;
use Platform\Crm\Models\CrmContactLink;
use Platform\Crm\Models\CrmCompanyLink;

class CrmEngagementManager implements CrmEngagementManagerInterface
{
    public function createEngagement(array $data, array $contactIds = [], array $companyIds = []): ?string
    {
        $engagement = CrmEngagement::create($data);

        $teamId = $data['team_id'] ?? null;
        $createdBy = $data['created_by_user_id'] ?? null;

        foreach ($contactIds as $contactId) {
            CrmContactLink::create([
                'contact_id' => $contactId,
                'linkable_id' => $engagement->id,
                'linkable_type' => 'crm_engagement',
                'team_id' => $teamId,
                'created_by_user_id' => $createdBy,
            ]);
        }

        foreach ($companyIds as $companyId) {
            CrmCompanyLink::create([
                'company_id' => $companyId,
                'linkable_id' => $engagement->id,
                'linkable_type' => 'crm_engagement',
                'team_id' => $teamId,
                'created_by_user_id' => $createdBy,
            ]);
        }

        return $engagement->uuid;
    }
}
