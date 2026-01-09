<?php

namespace Platform\Crm\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\NormalizesLookupIds;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmContact;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;

class UpdatePhoneNumberTool implements ToolContract, ToolMetadataContract
{
    use NormalizesLookupIds;

    public function getName(): string
    {
        return 'crm.phone_numbers.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /crm/phone_numbers/{id} - Aktualisiert eine Telefonnummer (Contact/Company). Parameter: phone_number_id, entity_type, entity_id, raw_input (optional), country_code (optional), phone_type_id (optional), is_primary (optional), is_active (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'phone_number_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Telefonnummer (ERFORDERLICH).',
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
                'raw_input' => [
                    'type' => 'string',
                    'description' => 'Optional: neue Telefonnummer.',
                ],
                'country_code' => [
                    'type' => 'string',
                    'description' => 'Optional: ISO2-Ländercode (z.B. "DE").',
                ],
                'phone_type_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Typ-ID (crm_phone_types.id).',
                ],
                'phone_type_code' => [
                    'type' => 'string',
                    'description' => 'Optional: Typ-Code (crm_phone_types.code). Nutze crm.lookup.GET lookup=phone_types. Niemals raten.',
                ],
                'is_primary' => [
                    'type' => 'boolean',
                    'description' => 'Optional: primär setzen/entfernen.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Notiz.',
                ],
                'extension' => [
                    'type' => 'string',
                    'description' => 'Optional: Durchwahl.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: aktiv/inaktiv.',
                ],
            ],
            'required' => ['phone_number_id', 'entity_type', 'entity_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $arguments = $this->normalizeLookupIds($arguments, ['phone_type_id']);

            $phoneId = $arguments['phone_number_id'] ?? null;
            $type = $arguments['entity_type'] ?? null;
            $entityId = $arguments['entity_id'] ?? null;
            if (!$phoneId || !$type || !$entityId) {
                return ToolResult::error('VALIDATION_ERROR', 'phone_number_id, entity_type und entity_id sind erforderlich.');
            }

            $entity = null;
            if ($type === 'contact') {
                $entity = CrmContact::with('phoneNumbers')->find($entityId);
            } elseif ($type === 'company') {
                $entity = CrmCompany::with('phoneNumbers')->find($entityId);
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

            $phone = $entity->phoneNumbers()->where('id', $phoneId)->first();
            if (!$phone) {
                return ToolResult::error('PHONE_NOT_FOUND', 'Die Telefonnummer wurde nicht gefunden oder gehört nicht zur Entity.');
            }

            $update = [];

            $raw = array_key_exists('raw_input', $arguments) ? trim((string)$arguments['raw_input']) : null;
            $country = strtoupper((string)($arguments['country_code'] ?? ($phone->country_code ?? 'DE')));
            if ($raw !== null) {
                if ($raw === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'raw_input darf nicht leer sein.');
                }

                $exists = $entity->phoneNumbers()
                    ->where('raw_input', $raw)
                    ->where('id', '!=', $phone->id)
                    ->exists();
                if ($exists) {
                    return ToolResult::error('VALIDATION_ERROR', 'Diese Telefonnummer existiert bereits bei dieser Entity.');
                }

                $phoneUtil = PhoneNumberUtil::getInstance();
                try {
                    $phoneNumber = $phoneUtil->parse($raw, $country);
                    if (!$phoneUtil->isValidNumber($phoneNumber)) {
                        return ToolResult::error('VALIDATION_ERROR', 'Ungültige Telefonnummer für das ausgewählte Land.');
                    }
                } catch (NumberParseException $e) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültige Telefonnummer. Bitte Format prüfen.');
                }

                $update['raw_input'] = $raw;
                $update['international'] = $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164);
                $update['national'] = $phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL);
                $update['country_code'] = $phoneUtil->getRegionCodeForNumber($phoneNumber);
            }

            if (array_key_exists('phone_type_id', $arguments)) {
                $v = $arguments['phone_type_id'] ?? null;
                if ($v === null) {
                    return ToolResult::error('VALIDATION_ERROR', 'phone_type_id darf nicht 0/leer sein. Nutze crm.lookup.GET lookup=phone_types.');
                }
                $update['phone_type_id'] = (int)$v;
            } elseif (array_key_exists('phone_type_code', $arguments)) {
                $code = strtoupper(trim((string)($arguments['phone_type_code'] ?? '')));
                if ($code === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'phone_type_code darf nicht leer sein.');
                }
                $resolved = \Platform\Crm\Models\CrmPhoneType::query()->where('code', $code)->value('id');
                if (!$resolved) {
                    return ToolResult::error('VALIDATION_ERROR', "Unbekannter phone_type_code '{$code}'. Nutze crm.lookup.GET lookup=phone_types.");
                }
                $update['phone_type_id'] = (int)$resolved;
            }

            if (array_key_exists('notes', $arguments)) {
                $update['notes'] = $arguments['notes'] ?? null;
            }

            if (array_key_exists('extension', $arguments)) {
                $update['extension'] = $arguments['extension'] ?? null;
            }

            if (array_key_exists('is_active', $arguments)) {
                $update['is_active'] = (bool)$arguments['is_active'];
            }

            if (array_key_exists('is_primary', $arguments)) {
                $isPrimary = (bool)$arguments['is_primary'];
                $update['is_primary'] = $isPrimary;
                if ($isPrimary) {
                    $entity->phoneNumbers()->where('id', '!=', $phone->id)->update(['is_primary' => false]);
                }
            }

            if (!empty($update)) {
                $phone->update($update);
            }

            $phone->refresh();
            $phone->load('phoneType');

            return ToolResult::success([
                'id' => $phone->id,
                'uuid' => $phone->uuid,
                'entity_type' => $type,
                'entity_id' => (int)$entityId,
                'raw_input' => $phone->raw_input,
                'international' => $phone->international,
                'national' => $phone->national,
                'country_code' => $phone->country_code,
                'phone_type' => $phone->phoneType?->name,
                'phone_type_id' => $phone->phone_type_id,
                'is_primary' => (bool)$phone->is_primary,
                'is_active' => (bool)$phone->is_active,
                'message' => 'Telefonnummer wurde aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Telefonnummer: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'phone', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


