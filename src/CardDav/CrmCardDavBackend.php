<?php

namespace Platform\Crm\CardDav;

use Illuminate\Database\Eloquent\Builder;
use Platform\Crm\Models\CrmCardDavSubscription;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Services\CardDav\ContactVCardMapper;
use Sabre\CardDAV\Backend\AbstractBackend;
use Sabre\CardDAV\Plugin as CardDavPlugin;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\PropPatch;

/**
 * Read-only CardDAV-Backend. Jede sichtbare {@see CrmContactList} = ein Adressbuch,
 * jeder Member-Kontakt = eine vCard.
 *
 * Sichtbarkeit ist an das aktive Abo gebunden: nur Kontakte/Listen im Team des
 * Abos, die entweder öffentlich (kein Owner) oder dem User des Abos gehören.
 * Alle Schreib-Operationen werfen {@see Forbidden}. Siehe docs/carddav.md.
 */
class CrmCardDavBackend extends AbstractBackend
{
    public function __construct(
        private readonly CardDavContext $context,
        private readonly ContactVCardMapper $mapper,
    ) {
    }

    private function sub(): CrmCardDavSubscription
    {
        return $this->context->subscription();
    }

    // ----------------------------------------------------------------
    // Adressbücher
    // ----------------------------------------------------------------

    public function getAddressBooksForUser($principalUri)
    {
        if ($principalUri !== 'principals/'.$this->sub()->user_id) {
            return [];
        }

        return $this->visibleListsQuery()
            ->get()
            ->map(fn (CrmContactList $list) => $this->addressBookArray($list))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function addressBookArray(CrmContactList $list): array
    {
        return [
            'id' => $list->id,
            'uri' => $list->uuid,
            'principaluri' => 'principals/'.$this->sub()->user_id,
            '{DAV:}displayname' => $list->name,
            '{'.CardDavPlugin::NS_CARDDAV.'}addressbook-description' => $list->description,
            '{http://calendarserver.org/ns/}getctag' => $this->computeCtag($list->id),
        ];
    }

    public function updateAddressBook($addressBookId, PropPatch $propPatch)
    {
        // Read-only: keine Property-Änderungen übernehmen.
    }

    public function createAddressBook($principalUri, $url, array $properties)
    {
        throw new Forbidden('Das CRM-Adressbuch ist schreibgeschützt.');
    }

    public function deleteAddressBook($addressBookId)
    {
        throw new Forbidden('Das CRM-Adressbuch ist schreibgeschützt.');
    }

    // ----------------------------------------------------------------
    // Karten (vCards)
    // ----------------------------------------------------------------

    public function getCards($addressbookId)
    {
        $this->assertAllowedList($addressbookId);

        return $this->visibleContactsQuery($addressbookId)
            ->get(['crm_contacts.id', 'crm_contacts.uuid', 'crm_contacts.updated_at'])
            ->map(fn (CrmContact $contact) => [
                'id' => $contact->id,
                'uri' => $contact->uuid.'.vcf',
                'etag' => ContactVCardMapper::etagFor($contact),
                'lastmodified' => $contact->updated_at?->getTimestamp() ?? 0,
            ])
            ->all();
    }

    public function getCard($addressBookId, $cardUri)
    {
        $this->assertAllowedList($addressBookId);

        $contact = $this->visibleContactsQuery($addressBookId)
            ->with([
                'emailAddresses.emailType',
                'phoneNumbers.phoneType',
                'postalAddresses.country',
                'postalAddresses.addressType',
                'companyRelations.company',
                'academicTitle',
            ])
            ->where('crm_contacts.uuid', $this->uuidFromUri($cardUri))
            ->first();

        if (! $contact) {
            return false;
        }

        $carddata = $this->mapper->serialize($contact);

        return [
            'id' => $contact->id,
            'uri' => $cardUri,
            'etag' => ContactVCardMapper::etagFor($contact),
            'lastmodified' => $contact->updated_at?->getTimestamp() ?? 0,
            'size' => strlen($carddata),
            'carddata' => $carddata,
        ];
    }

    public function createCard($addressBookId, $cardUri, $cardData)
    {
        throw new Forbidden('Das CRM-Adressbuch ist schreibgeschützt.');
    }

    public function updateCard($addressBookId, $cardUri, $cardData)
    {
        throw new Forbidden('Das CRM-Adressbuch ist schreibgeschützt.');
    }

    public function deleteCard($addressBookId, $cardUri)
    {
        throw new Forbidden('Das CRM-Adressbuch ist schreibgeschützt.');
    }

    // ----------------------------------------------------------------
    // Sichtbarkeit / Scoping
    // ----------------------------------------------------------------

    /**
     * Listen, die das Abo freigibt: die abonnierte Liste — oder, wenn keine
     * gesetzt ist, alle für den User sichtbaren Listen des Teams.
     */
    private function visibleListsQuery(): Builder
    {
        $query = CrmContactList::query()
            ->where('team_id', $this->sub()->team_id)
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('owned_by_user_id')
                    ->orWhere('owned_by_user_id', $this->sub()->user_id);
            });

        if ($this->sub()->contact_list_id !== null) {
            $query->where('id', $this->sub()->contact_list_id);
        }

        return $query;
    }

    /**
     * Sichtbare, aktive Kontakte einer Liste (Team- + Owner-Scope).
     */
    private function visibleContactsQuery(int $listId): Builder
    {
        return CrmContact::query()
            ->where('crm_contacts.team_id', $this->sub()->team_id)
            ->where('crm_contacts.is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('crm_contacts.owned_by_user_id')
                    ->orWhere('crm_contacts.owned_by_user_id', $this->sub()->user_id);
            })
            ->whereHas('contactLists', function (Builder $q) use ($listId) {
                $q->where('crm_contact_lists.id', $listId);
            });
    }

    private function assertAllowedList(int $addressBookId): void
    {
        if (! $this->visibleListsQuery()->where('id', $addressBookId)->exists()) {
            throw new NotFound('Adressbuch nicht gefunden.');
        }
    }

    /**
     * CTag ändert sich bei Kontakt-Edit (max updated_at) und bei Mitglieder-
     * Änderung (count). Damit erkennen Clients, wann neu synchronisiert werden muss.
     */
    private function computeCtag(int $listId): string
    {
        $agg = $this->visibleContactsQuery($listId)
            ->selectRaw('COUNT(*) as cnt, MAX(crm_contacts.updated_at) as maxu')
            ->first();

        $max = $agg?->maxu ? strtotime((string) $agg->maxu) : 0;

        return $max.'-'.($agg?->cnt ?? 0);
    }

    private function uuidFromUri(string $cardUri): string
    {
        return preg_replace('/\.vcf$/i', '', $cardUri);
    }
}
