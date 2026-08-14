<?php

namespace Platform\Crm\Flynk;

use Platform\Crm\Models\CrmCompany;
use Platform\FlynkConnector\Contracts\ProvidesFlynkContext;
use Platform\Organization\Models\OrganizationEntity;
use Platform\Organization\Services\EntityDimensionBridge;

/**
 * FLYNK-Kontext-Lieferant für die Firmen-/Rechtsdaten (Impressum + Kontakt).
 *
 * Besonderheit: Die CRM-Firma hängt in der Regel am KUNDEN-Knoten (Parent),
 * nicht an der einzelnen Website. Darum läuft resolveCompany() den Baum hoch,
 * bis es einen verknüpften 'crm_company'-Knoten findet. So teilen sich mehrere
 * Websites eines Kunden dasselbe Impressum.
 *
 * Der Connector prüft nur die PRÄSENZ dieser Felder (Füllgrad) — die Struktur
 * hier ist der Vertrag: context['crm'] = { legal:{…}, contact:{…}, company:{…} }.
 */
class CrmFlynkContextProvider implements ProvidesFlynkContext
{
    /** Wie weit maximal im Baum nach oben gesucht wird. */
    private const MAX_DEPTH = 10;

    public function contextKey(): string
    {
        return 'crm';
    }

    public function contextForEntity(OrganizationEntity $node): ?array
    {
        $company = $this->resolveCompany($node);
        if (! $company) {
            return null;
        }

        $address = $company->primaryAddress;             // postalAddresses()->primary()->first()
        $email   = $company->primaryEmail?->email_address;
        $phone   = $company->primaryPhone?->international;

        $relation = $company->primaryContacts()->with('contact')->first();
        $contact  = $relation?->contact;

        $context = array_filter([
            'legal'   => $this->legal($company, $address, $email, $phone, $contact, $relation),
            'contact' => $this->contact($company, $email, $contact),
            'company' => $this->company($company, $address),
        ], fn ($v) => $v !== null && $v !== []);

        return $context ?: null;
    }

    /** Läuft vom Knoten den Parent-Baum hoch bis zur ersten verknüpften Firma. */
    protected function resolveCompany(OrganizationEntity $node): ?CrmCompany
    {
        $current = $node;
        $depth = 0;

        while ($current && $depth < self::MAX_DEPTH) {
            $link = EntityDimensionBridge::linksForEntity($current->id)
                ->first(fn ($l) => $l->linkable_type === 'crm_company');

            if ($link) {
                return CrmCompany::find($link->linkable_id);
            }

            $current = $current->parent;   // belongsTo(parent_entity_id)
            $depth++;
        }

        return null;
    }

    /** Die dreizehn Impressums-Felder (Datenset-Doku §2). */
    protected function legal(CrmCompany $company, $address, ?string $email, ?string $phone, $contact, $relation): array
    {
        $representative = $contact
            ? trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''))
            : null;

        return array_filter([
            'company'             => $company->legal_name ?: $company->name,
            'representative'      => $representative ?: null,
            'representative_role' => $relation?->position,
            'address'             => $address ? trim(($address->street ?? '').' '.($address->house_number ?? '')) : null,
            'postal_code'         => $address?->postal_code,
            'city'                => $address?->city,
            'country'             => $address?->country?->code,
            'country_name'        => $address?->country?->name,
            'phone'               => $phone,
            'email'               => $email,
            'register_court'      => $company->register_court,
            'registration'        => $company->registration_number,
            'vat_id'              => $company->vat_number,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** Kontakt-/Mail-Felder (Datenset-Doku §3). */
    protected function contact(CrmCompany $company, ?string $email, $contact): array
    {
        return array_filter([
            'primary_email' => $email,
            'reply_email'   => $email,
            'client_name'   => $contact?->full_name ?: $company->display_name,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** Ableitbare Kontext-Felder (Branche, Ort). */
    protected function company(CrmCompany $company, $address): array
    {
        return array_filter([
            'industry' => $company->industry?->name,
            'location' => $address?->city,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
