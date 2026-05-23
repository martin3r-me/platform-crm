<?php

namespace Platform\Crm\Events;

use Platform\Crm\Models\CrmContactListMember;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContactListSubscriptionChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CrmContactListMember $member,
        public string $action,
    ) {}
}
