<?php

namespace Platform\Crm\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\NormalizesLookupIds;
use Platform\Crm\Models\CrmAddressType;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmCountry;
use Platform\Crm\Models\CrmState;
use Platform\Crm\Models\CrmPostalAddress;

class CreatePostalAddressTool implements ToolContract, ToolMetadataContract
{
    use NormalizesLookupIds;

    public function getName(): string
    {
        return 'crm.postal_addresses.POST';
    }

    public function getDescription(): string
    {
        return 'POST /crm/postal_addresses - Fügt einer Company oder einem Contact eine Postadresse hinzu. Parameter: entity_type (contact|company), entity_id, street/city/postal_code etc., country_id/state_id/address_type_id, is_primary.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'entity_type' => [
                    'type' => 'string',
                    'enum' => ['contact', 'company'],
                    'description' => 'Ziel-Entity: "contact" oder "company" (ERFORDERLICH).',
                ],
                'entity_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Contacts/der Company (ERFORDERLICH).',
                ],
                'street' => ['type' => 'string', 'description' => 'Optional: Straße.'],
                'house_number' => ['type' => 'string', 'description' => 'Optional: Hausnummer.'],
                'postal_code' => ['type' => 'string', 'description' => 'Optional: PLZ.'],
                'city' => ['type' => 'string', 'description' => 'Optional: Stadt.'],
                'additional_info' => ['type' => 'string', 'description' => 'Optional: Zusatz.'],
                'country_id' => ['type' => 'integer', 'description' => 'Optional: Land-ID (crm_countries.id).'],
                'country_code' => ['type' => 'string', 'description' => 'Optional: ISO2-Ländercode (z.B. "DE"). Wenn gesetzt, wird country_id automatisch aufgelöst (ohne Raten).'],
                'state_id' => ['type' => 'integer', 'description' => 'Optional: Bundesland-ID (crm_states.id).'],
                'state_code' => ['type' => 'string', 'description' => 'Optional: Bundesland-Code (crm_states.code). Wird (wenn möglich) innerhalb des Landes aufgelöst.'],
                'state_name' => ['type' => 'string', 'description' => 'Optional: Bundesland-Name (crm_states.name). Wird (wenn möglich) innerhalb des Landes aufgelöst.'],
                'address_type_id' => ['type' => 'integer', 'description' => 'Optional: Adresstyp-ID (crm_address_types.id).'],
                'address_type_code' => ['type' => 'string', 'description' => 'Optional: Adresstyp-Code (crm_address_types.code, z.B. BUSINESS/HEADQUARTERS). Wird automatisch aufgelöst (ohne Raten).'],
                'address_type_name' => ['type' => 'string', 'description' => 'Optional: Adresstyp-Name (crm_address_types.name). Wird automatisch aufgelöst (ohne Raten).'],
                'is_primary' => ['type' => 'boolean', 'description' => 'Optional: primär setzen.'],
                'is_active' => ['type' => 'boolean', 'description' => 'Optional: aktiv/inaktiv (default: true).'],
            ],
            'required' => ['entity_type', 'entity_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $arguments = $this->normalizeLookupIds($arguments, ['country_id', 'state_id', 'address_type_id']);

            $type = $arguments['entity_type'] ?? null;
            $id = $arguments['entity_id'] ?? null;
            if (!$type || !$id) {
                return ToolResult::error('VALIDATION_ERROR', 'entity_type und entity_id sind erforderlich.');
            }

            $entity = null;
            if ($type === 'contact') {
                $entity = CrmContact::with('postalAddresses')->find($id);
            } elseif ($type === 'company') {
                $entity = CrmCompany::with('postalAddresses')->find($id);
            } else {
                return ToolResult::error('VALIDATION_ERROR', 'entity_type muss "contact" oder "company" sein.');
            }

            if (!$entity) {
                return ToolResult::error('ENTITY_NOT_FOUND', 'Contact/Company wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $entity);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Entity nicht bearbeiten (Policy).');
            }

            $warnings = [];

            $countryId = $arguments['country_id'] ?? null;
            $countryCode = strtoupper(trim((string)($arguments['country_code'] ?? '')));
            if ($countryId === null && $countryCode !== '') {
                $resolved = CrmCountry::query()->where('code', $countryCode)->value('id');
                if ($resolved) {
                    $countryId = (int)$resolved;
                } else {
                    $warnings[] = "country_code '{$countryCode}' konnte nicht aufgelöst werden. country_id bleibt leer.";
                }
            }

            $stateId = $arguments['state_id'] ?? null;
            if ($stateId === null) {
                $stateCode = strtoupper(trim((string)($arguments['state_code'] ?? '')));
                $stateName = trim((string)($arguments['state_name'] ?? ''));
                if ($stateCode !== '' || $stateName !== '') {
                    $q = CrmState::query();
                    if ($countryId !== null) {
                        $q->where('country_id', $countryId);
                    }
                    if ($stateCode !== '') {
                        $q->where('code', $stateCode);
                    } else {
                        $q->where('name', $stateName);
                    }
                    $resolved = $q->value('id');
                    if ($resolved) {
                        $stateId = (int)$resolved;
                    } else {
                        $warnings[] = 'Bundesland konnte nicht aufgelöst werden (state_code/state_name). state_id bleibt leer.';
                    }
                }
            }

            $addressTypeId = $arguments['address_type_id'] ?? null;
            if ($addressTypeId === null) {
                $typeCode = strtoupper(trim((string)($arguments['address_type_code'] ?? '')));
                $typeName = trim((string)($arguments['address_type_name'] ?? ''));
                if ($typeCode !== '' || $typeName !== '') {
                    $q = CrmAddressType::query();
                    if ($typeCode !== '') {
                        $q->where('code', $typeCode);
                    } else {
                        $q->where('name', $typeName);
                    }
                    $resolved = $q->value('id');
                    if ($resolved) {
                        $addressTypeId = (int)$resolved;
                    } else {
                        $warnings[] = 'address_type konnte nicht aufgelöst werden (address_type_code/address_type_name). address_type_id bleibt leer.';
                    }
                }
            }
            if ($addressTypeId === null) {
                return ToolResult::error('VALIDATION_ERROR', 'address_type_id oder address_type_code/address_type_name ist erforderlich (crm_postal_addresses.address_type_id hat kein Default). Nutze crm.lookup.GET lookup=address_types.');
            }

            if ($countryId !== null && !CrmCountry::whereKey($countryId)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', 'country_id ist ungültig.');
            }
            if ($stateId !== null && !CrmState::whereKey($stateId)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', 'state_id ist ungültig.');
            }
            if ($addressTypeId !== null && !CrmAddressType::whereKey($addressTypeId)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', 'address_type_id ist ungültig.');
            }

            $isPrimary = (bool)($arguments['is_primary'] ?? false);
            if ($isPrimary) {
                $entity->postalAddresses()->update(['is_primary' => false]);
            }

            /** @var CrmPostalAddress $created */
            $created = $entity->postalAddresses()->create([
                'street' => $arguments['street'] ?? null,
                'house_number' => $arguments['house_number'] ?? null,
                'postal_code' => $arguments['postal_code'] ?? null,
                'city' => $arguments['city'] ?? null,
                'additional_info' => $arguments['additional_info'] ?? null,
                'country_id' => $countryId,
                'state_id' => $stateId,
                'address_type_id' => $addressTypeId,
                'is_primary' => $isPrimary,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            $created->load(['country', 'state', 'addressType']);

            return ToolResult::success([
                'id' => $created->id,
                'uuid' => $created->uuid,
                'entity_type' => $type,
                'entity_id' => (int)$id,
                'street' => $created->street,
                'house_number' => $created->house_number,
                'postal_code' => $created->postal_code,
                'city' => $created->city,
                'additional_info' => $created->additional_info,
                'country' => $created->country?->name,
                'country_id' => $created->country_id,
                'state' => $created->state?->name,
                'state_id' => $created->state_id,
                'address_type' => $created->addressType?->name,
                'address_type_id' => $created->address_type_id,
                'is_primary' => (bool)$created->is_primary,
                'is_active' => (bool)$created->is_active,
                'warnings' => $warnings,
                'message' => 'Postadresse wurde hinzugefügt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Hinzufügen der Postadresse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'postal', 'address', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


