<?php

namespace Platform\Crm\Dav;

use Platform\Core\Contracts\DavModuleInterface;
use Platform\Core\Dav\DavContext;
use Platform\Crm\Services\CardDav\ContactVCardMapper;

/**
 * Stellt die CRM-Kontaktlisten als CardDAV-Adressbücher an der Core-DAV-
 * Infrastruktur bereit. Siehe modules/crm/docs/dav-core-extraction.md.
 */
class CrmCardDavModule implements DavModuleInterface
{
    public function key(): string
    {
        return 'crm';
    }

    public function type(): string
    {
        return 'carddav';
    }

    public function backend(DavContext $context): object
    {
        return new CrmCardDavBackend($context, new ContactVCardMapper());
    }
}
