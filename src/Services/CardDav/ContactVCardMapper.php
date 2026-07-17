<?php

namespace Platform\Crm\Services\CardDav;

use Platform\Crm\Models\CrmContact;
use Sabre\VObject\Component\VCard;

/**
 * Bildet einen {@see CrmContact} auf eine vCard 3.0 ab (read-only CardDAV-Feed).
 *
 * vCard 3.0 wird bewusst gewählt: maximale Kompatibilität mit iOS/macOS Contacts.
 * Der Mapper ist rein (kein DB-Zugriff, kein HTTP) und erwartet, dass die
 * relevanten Relationen bereits eager-geladen sind:
 *   emailAddresses.emailType, phoneNumbers.phoneType,
 *   postalAddresses.country, companyRelations.company,
 *   salutation, academicTitle.
 *
 * Siehe docs/carddav.md.
 */
class ContactVCardMapper
{
    /**
     * CRM-Typ-Code ({@see \Platform\Crm\Models\CrmEmailType} etc.) → vCard-TYPE.
     * Fällt bei unbekanntem Code auf null zurück (dann kein TYPE-Parameter).
     */
    private const TYPE_MAP = [
        'email' => [
            'PRIVATE'  => 'HOME',
            'BUSINESS' => 'WORK',
            'SUPPORT'  => 'WORK',
            'BILLING'  => 'WORK',
        ],
        'phone' => [
            'PRIVATE'   => 'HOME',
            'BUSINESS'  => 'WORK',
            'MOBILE'    => 'CELL',
            'FAX'       => 'FAX',
            'HOTLINE'   => 'WORK',
            'SUPPORT'   => 'WORK',
            'EMERGENCY' => 'VOICE',
        ],
        'address' => [
            'PRIVATE'      => 'HOME',
            'BUSINESS'     => 'WORK',
            'HEADQUARTERS' => 'WORK',
            'BRANCH'       => 'WORK',
            'BILLING'      => 'WORK',
            'SHIPPING'     => 'WORK',
            'PO_BOX'       => 'POSTAL',
        ],
    ];

    /**
     * Erzeugt die vCard-Komponente für einen Kontakt.
     */
    public function toVCard(CrmContact $contact): VCard
    {
        $card = new VCard([
            'VERSION' => '3.0',
            'UID'     => $contact->uuid,
            'PRODID'  => '-//Platform CRM//CardDAV//DE',
        ]);

        $this->addName($card, $contact);
        $this->addNickname($card, $contact);
        $this->addBirthday($card, $contact);
        $this->addOrganization($card, $contact);
        $this->addEmails($card, $contact);
        $this->addPhones($card, $contact);
        $this->addAddresses($card, $contact);
        $this->addNote($card, $contact);
        $this->addRevision($card, $contact);

        return $card;
    }

    /**
     * Serialisierte vCard (der Body, den CardDAV-Clients per GET/multiget abholen).
     */
    public function serialize(CrmContact $contact): string
    {
        return $this->toVCard($contact)->serialize();
    }

    /**
     * Stabiler ETag für Change-Detection. Wird identisch im CardDAV-Backend genutzt,
     * damit `getCards()` (ohne Body) und `getCard()` (mit Body) denselben ETag melden.
     */
    public static function etagFor(CrmContact $contact): string
    {
        return '"' . md5(($contact->updated_at?->getTimestamp() ?? 0) . ':' . $contact->getKey()) . '"';
    }

    private function addName(VCard $card, CrmContact $contact): void
    {
        $prefix = trim((string) ($contact->academicTitle->name ?? ''));

        // N = [Nachname; Vorname; Zusatzname; Prefix; Suffix]
        $card->add('N', [
            (string) ($contact->last_name ?? ''),
            (string) ($contact->first_name ?? ''),
            (string) ($contact->middle_name ?? ''),
            $prefix,
            '',
        ]);

        $fn = trim(implode(' ', array_filter([
            $prefix,
            $contact->first_name,
            $contact->middle_name,
            $contact->last_name,
        ])));

        // FN ist Pflicht in vCard – Fallbacks, damit nie leer.
        if ($fn === '') {
            $fn = trim((string) ($contact->nickname
                ?? optional($contact->companyRelations->first())->company->name
                ?? optional($contact->emailAddresses->first())->email_address
                ?? ('Kontakt ' . $contact->getKey())));
        }

        $card->add('FN', $fn);
    }

    private function addNickname(VCard $card, CrmContact $contact): void
    {
        if (! empty($contact->nickname)) {
            $card->add('NICKNAME', $contact->nickname);
        }
    }

    private function addBirthday(VCard $card, CrmContact $contact): void
    {
        if ($contact->birth_date) {
            $card->add('BDAY', $contact->birth_date->format('Y-m-d'));
        }
    }

    private function addOrganization(VCard $card, CrmContact $contact): void
    {
        $relation = $contact->companyRelations
            ->sortByDesc(fn ($r) => (bool) ($r->is_primary))
            ->first();

        if (! $relation) {
            return;
        }

        if ($name = $relation->company->name ?? null) {
            $card->add('ORG', $name);
        }

        if (! empty($relation->position)) {
            $card->add('TITLE', $relation->position);
        }
    }

    private function addEmails(VCard $card, CrmContact $contact): void
    {
        foreach ($contact->emailAddresses as $email) {
            if (empty($email->email_address)) {
                continue;
            }

            $card->add('EMAIL', $email->email_address, $this->typeParams(
                'email',
                $email->emailType->code ?? null,
                (bool) $email->is_primary,
                'INTERNET',
            ));
        }
    }

    private function addPhones(VCard $card, CrmContact $contact): void
    {
        foreach ($contact->phoneNumbers as $phone) {
            // Bevorzugt E.164 (international), sonst national, sonst Rohwert.
            $number = $phone->international ?: ($phone->national ?: $phone->raw_input);
            if (empty($number)) {
                continue;
            }

            $card->add('TEL', $number, $this->typeParams(
                'phone',
                $phone->phoneType->code ?? null,
                (bool) $phone->is_primary,
            ));
        }
    }

    private function addAddresses(VCard $card, CrmContact $contact): void
    {
        foreach ($contact->postalAddresses as $address) {
            $street = trim(implode(' ', array_filter([$address->street, $address->house_number])));

            // ADR = [Postfach; Zusatz; Straße; Ort; Region; PLZ; Land]
            $card->add('ADR', [
                '',
                (string) ($address->additional_info ?? ''),
                $street,
                (string) ($address->city ?? ''),
                '',
                (string) ($address->postal_code ?? ''),
                (string) ($address->country->name ?? ''),
            ], $this->typeParams(
                'address',
                $address->addressType->code ?? null,
                (bool) $address->is_primary,
            ));
        }
    }

    private function addNote(VCard $card, CrmContact $contact): void
    {
        if (! empty($contact->notes)) {
            $card->add('NOTE', $contact->notes);
        }
    }

    private function addRevision(VCard $card, CrmContact $contact): void
    {
        if ($contact->updated_at) {
            $card->add('REV', $contact->updated_at->format('Ymd\THis\Z'));
        }
    }

    /**
     * Baut den TYPE-Parameter (plus optional PREF) für eine vCard-Property.
     *
     * @return array{TYPE?: string[]}
     */
    private function typeParams(string $group, ?string $code, bool $isPrimary, ?string $extra = null): array
    {
        $types = [];

        if ($extra !== null) {
            $types[] = $extra;
        }

        $mapped = $code !== null ? (self::TYPE_MAP[$group][$code] ?? null) : null;
        if ($mapped !== null) {
            $types[] = $mapped;
        }

        if ($isPrimary) {
            $types[] = 'PREF';
        }

        return $types === [] ? [] : ['TYPE' => $types];
    }
}
