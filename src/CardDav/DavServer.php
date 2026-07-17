<?php

namespace Platform\Crm\CardDav;

use Platform\Crm\CardDav\Auth\TokenAuthBackend;
use Platform\Crm\Services\CardDav\ContactVCardMapper;
use Sabre\CardDAV\AddressBookRoot;
use Sabre\CardDAV\Plugin as CardDavPlugin;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Server;
use Sabre\DAVACL\Plugin as AclPlugin;
use Sabre\DAVACL\PrincipalCollection;

/**
 * Baut einen konfigurierten, read-only CardDAV-`Server`.
 *
 * Auth-, Principal- und CardDAV-Backend teilen sich einen {@see CardDavContext}:
 * die Auth schreibt das Abo hinein, die Backends lesen es. Siehe docs/carddav.md.
 */
class DavServer
{
    public static function make(string $baseUri): Server
    {
        $context = new CardDavContext();

        $authBackend = new TokenAuthBackend($context);
        $principalBackend = new PrincipalBackend($context);
        $carddavBackend = new CrmCardDavBackend($context, new ContactVCardMapper());

        $tree = [
            new PrincipalCollection($principalBackend),
            new AddressBookRoot($principalBackend, $carddavBackend),
        ];

        // CapturingSapi fängt den Output ab -> exec() im Controller liefert eine
        // Laravel-Response statt direkt zu schreiben.
        $server = new Server($tree, new CapturingSapi());
        $server->setBaseUri($baseUri);

        // Auth zuerst — erzwingt gültiges Abo-Secret (401 sonst).
        $server->addPlugin(new AuthPlugin($authBackend));
        $server->addPlugin(new CardDavPlugin());

        $aclPlugin = new AclPlugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $server->addPlugin($aclPlugin);

        return $server;
    }
}
