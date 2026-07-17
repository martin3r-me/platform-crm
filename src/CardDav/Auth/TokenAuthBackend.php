<?php

namespace Platform\Crm\CardDav\Auth;

use Platform\Core\Services\TeamContext;
use Platform\Crm\CardDav\CardDavContext;
use Platform\Crm\Models\CrmCardDavSubscription;
use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\HTTP\Auth\Basic;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * CardDAV-Auth über HTTP Basic: Passwort = {@see CrmCardDavSubscription::$secret}.
 *
 * Der Benutzername wird ignoriert — das Abo wird eindeutig über das Secret
 * bestimmt. Bei Erfolg wird das Abo in den geteilten {@see CardDavContext}
 * geschrieben und der Team-Kontext gesetzt, damit alle nachgelagerten Scopes
 * (Sichtbarkeit, Billing) im richtigen Team laufen. Siehe docs/carddav.md.
 */
class TokenAuthBackend extends AbstractBasic
{
    protected $realm = 'CRM CardDAV';

    public function __construct(
        private readonly CardDavContext $context,
    ) {
    }

    /**
     * Löst das Secret auf und legt das Abo in den Kontext.
     */
    protected function validateUserPass($username, $password): bool
    {
        if (empty($password)) {
            return false;
        }

        $subscription = CrmCardDavSubscription::query()
            ->active()
            ->where('secret', $password)
            ->first();

        if (! $subscription || ! $subscription->user) {
            return false;
        }

        // Team-Kontext des Abos aktivieren (Sichtbarkeit/Billing/Scopes).
        TeamContext::set($subscription->team_id);

        $subscription->markUsed();

        $this->context->setSubscription($subscription);

        return true;
    }

    /**
     * Wie {@see AbstractBasic::check()}, aber mit deterministischem Principal
     * `principals/{userId}` — passend zum PrincipalBackend.
     */
    public function check(RequestInterface $request, ResponseInterface $response)
    {
        $auth = new Basic($this->realm, $request, $response);

        $userpass = $auth->getCredentials();
        if (! $userpass) {
            return [false, "No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured"];
        }

        if (! $this->validateUserPass($userpass[0], $userpass[1])) {
            return [false, 'Username or password was incorrect'];
        }

        return [true, $this->principalPrefix.$this->context->subscription()->user_id];
    }
}
