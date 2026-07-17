<?php

namespace Platform\Crm\Dav;

use Platform\Core\Contracts\DavModuleInterface;
use Platform\Core\Dav\DavContext;
use Platform\Core\Dav\PrincipalBackend;
use Platform\Crm\Services\CardDav\ContactVCardMapper;
use Sabre\CardDAV\AddressBookRoot;
use Sabre\CardDAV\Plugin as CardDavPlugin;
use Sabre\DAV\ICollection;

/**
 * Registriert die CRM-Kontaktlisten als CardDAV-Adressbücher an der Core-DAV-
 * Infrastruktur. Siehe modules/crm/docs/dav-core-extraction.md.
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

    public function rootNode(DavContext $context, PrincipalBackend $principals): ICollection
    {
        return new AddressBookRoot(
            $principals,
            new CrmCardDavBackend($context, new ContactVCardMapper()),
        );
    }

    public function plugins(): array
    {
        return [new CardDavPlugin()];
    }
}
