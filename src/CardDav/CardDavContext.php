<?php

namespace Platform\Crm\CardDav;

use Platform\Crm\Models\CrmCardDavSubscription;
use Sabre\DAV\Exception\NotAuthenticated;

/**
 * Geteilter Request-Kontext zwischen Auth- und CardDAV-Backends.
 *
 * Die Backends werden gebaut, bevor die Authentifizierung gelaufen ist. Das
 * {@see TokenAuthBackend} schreibt das aufgelöste Abo hier hinein; Principal-
 * und CardDAV-Backend lesen es lazy, sobald sabre ihre Methoden aufruft
 * (nach der Auth). Siehe docs/carddav.md.
 */
class CardDavContext
{
    private ?CrmCardDavSubscription $subscription = null;

    public function setSubscription(CrmCardDavSubscription $subscription): void
    {
        $this->subscription = $subscription;
    }

    public function hasSubscription(): bool
    {
        return $this->subscription !== null;
    }

    public function subscription(): CrmCardDavSubscription
    {
        if ($this->subscription === null) {
            throw new NotAuthenticated('CardDAV: kein authentifiziertes Abo im Kontext.');
        }

        return $this->subscription;
    }
}
