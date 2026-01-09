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

class UpdatePostalAddressTool implements ToolContract, ToolMetadataContract
{
    use NormalizesLookupIds;

    public function getName(): string
    {
        return 'crm.postal_addresses.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /crm/postal_addresses/{id} - Aktualisiert eine Postadresse (Contact/Company). Parameter: postal_address_id, entity_type, entity_id, Felder optional, is_primary/is_active optional.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'postal_address_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Postadresse (ERFORDERLICH).',
                ],
                'entity_type' => [
                    'type' => 'string',
                    'enum' => ['contact', 'company'],
                    'description' => 'Ziel-Entity: "contact" oder "company" (ERFORDERLICH).',
                ],
                'entity_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Contacts/der Company (ERFORDERLICH).',
                ],
                'street' => ['type' => 'string'],
                'house_number' => ['type' => 'string'],
                'postal_code' => ['type' => 'string'],
                'city' => ['type' => 'string'],
                'additional_info' => ['type' => 'string'],
                'country_id' => ['type' => 'integer'],
                'country_code' => ['type' => 'string', 'description' => 'Optional: ISO2-Ländercode (z.B. "DE"). Wenn gesetzt, wird country_id automatisch aufgelöst (ohne Raten).'],
                'state_id' => ['type' => 'integer'],
                'state_code' => ['type' => 'string', 'description' => 'Optional: Bundesland-Code (crm_states.code). Wird (wenn möglich) innerhalb des Landes aufgelöst.'],
                'state_name' => ['type' => 'string', 'description' => 'Optional: Bundesland-Name (crm_states.name). Wird (wenn möglich) innerhalb des Landes aufgelöst.'],
                'address_type_id' => ['type' => 'integer'],
                'address_type_code' => ['type' => 'string', 'description' => 'Optional: Adresstyp-Code (crm_address_types.code). Wird automatisch aufgelöst (ohne Raten).'],
                'address_type_name' => ['type' => 'string', 'description' => 'Optional: Adresstyp-Name (crm_address_types.name). Wird automatisch aufgelöst (ohne Raten).'],
                'is_primary' => ['type' => 'boolean'],
                'is_active' => ['type' => 'boolean'],
            ],
            'required' => ['postal_address_id', 'entity_type', 'entity_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $arguments = $this->normalizeLookupIds($arguments, ['country_id', 'state_id', 'address_type_id']);
            $warnings = [];

            $addrId = $arguments['postal_address_id'] ?? null;
            $type = $arguments['entity_type'] ?? null;
            $entityId = $arguments['entity_id'] ?? null;
            if (!$addrId || !$type || !$entityId) {
                return ToolResult::error('VALIDATION_ERROR', 'postal_address_id, entity_type und entity_id sind erforderlich.');
            }

            $entity = null;
            if ($type === 'contact') {
                $entity = CrmContact::with('postalAddresses')->find($entityId);
            } elseif ($type === 'company') {
                $entity = CrmCompany::with('postalAddresses')->find($entityId);
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

            /** @var CrmPostalAddress|null $address */
            $address = $entity->postalAddresses()->where('id', $addrId)->first();
            if (!$address) {
                return ToolResult::error('ADDRESS_NOT_FOUND', 'Die Postadresse wurde nicht gefunden oder gehört nicht zur Entity.');
            }

            $update = [];
            foreach (['street', 'house_number', 'postal_code', 'city', 'additional_info'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $update[$field] = $arguments[$field] ?? null;
                }
            }

            // Optional: resolve codes/names to IDs (deterministic, no guessing)
            if (!array_key_exists('country_id', $arguments)) {
                $countryCode = strtoupper(trim((string)($arguments['country_code'] ?? '')));
                if ($countryCode !== '') {
                    $resolved = CrmCountry::query()->where('code', $countryCode)->value('id');
                    if ($resolved) {
                        $arguments['country_id'] = (int)$resolved;
                    } else {
                        $warnings[] = "country_code '{$countryCode}' konnte nicht aufgelöst werden.";
                    }
                }
            }
            if (!array_key_exists('state_id', $arguments)) {
                $stateCode = strtoupper(trim((string)($arguments['state_code'] ?? '')));
                $stateName = trim((string)($arguments['state_name'] ?? ''));
                if ($stateCode !== '' || $stateName !== '') {
                    $q = CrmState::query();
                    if (array_key_exists('country_id', $arguments) && $arguments['country_id'] !== null) {
                        $q->where('country_id', $arguments['country_id']);
                    }
                    if ($stateCode !== '') $q->where('code', $stateCode);
                    else $q->where('name', $stateName);
                    $resolved = $q->value('id');
                    if ($resolved) {
                        $arguments['state_id'] = (int)$resolved;
                    } else {
                        $warnings[] = 'Bundesland konnte nicht aufgelöst werden (state_code/state_name).';
                    }
                }
            }
            if (!array_key_exists('address_type_id', $arguments)) {
                $typeCode = strtoupper(trim((string)($arguments['address_type_code'] ?? '')));
                $typeName = trim((string)($arguments['address_type_name'] ?? ''));
                if ($typeCode !== '' || $typeName !== '') {
                    $q = CrmAddressType::query();
                    if ($typeCode !== '') $q->where('code', $typeCode);
                    else $q->where('name', $typeName);
                    $resolved = $q->value('id');
                    if ($resolved) {
                        $arguments['address_type_id'] = (int)$resolved;
                    } else {
                        $warnings[] = 'address_type konnte nicht aufgelöst werden (address_type_code/address_type_name).';
                    }
                }
            }

            if (array_key_exists('country_id', $arguments)) {
                $countryId = $arguments['country_id'];
                if ($countryId !== null && !CrmCountry::whereKey($countryId)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', 'country_id ist ungültig.');
                }
                $update['country_id'] = $countryId;
            }
            if (array_key_exists('state_id', $arguments)) {
                $stateId = $arguments['state_id'];
                if ($stateId !== null && !CrmState::whereKey($stateId)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', 'state_id ist ungültig.');
                }
                $update['state_id'] = $stateId;
            }
            if (array_key_exists('address_type_id', $arguments)) {
                $typeId = $arguments['address_type_id'];
                if ($typeId !== null && !CrmAddressType::whereKey($typeId)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', 'address_type_id ist ungültig.');
                }
                $update['address_type_id'] = $typeId;
            }

            if (array_key_exists('is_active', $arguments)) {
                $update['is_active'] = (bool)$arguments['is_active'];
            }

            if (array_key_exists('is_primary', $arguments)) {
                $isPrimary = (bool)$arguments['is_primary'];
                $update['is_primary'] = $isPrimary;
                if ($isPrimary) {
                    $entity->postalAddresses()->where('id', '!=', $address->id)->update(['is_primary' => false]);
                }
            }

            if (!empty($update)) {
                $address->update($update);
            }

            $address->refresh();
            $address->load(['country', 'state', 'addressType']);

            return ToolResult::success([
                'id' => $address->id,
                'uuid' => $address->uuid,
                'entity_type' => $type,
                'entity_id' => (int)$entityId,
                'street' => $address->street,
                'house_number' => $address->house_number,
                'postal_code' => $address->postal_code,
                'city' => $address->city,
                'additional_info' => $address->additional_info,
                'country' => $address->country?->name,
                'country_id' => $address->country_id,
                'state' => $address->state?->name,
                'state_id' => $address->state_id,
                'address_type' => $address->addressType?->name,
                'address_type_id' => $address->address_type_id,
                'is_primary' => (bool)$address->is_primary,
                'is_active' => (bool)$address->is_active,
                'warnings' => $warnings,
                'message' => 'Postadresse wurde aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Postadresse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'postal', 'address', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


