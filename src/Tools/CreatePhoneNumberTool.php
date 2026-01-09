<?php

namespace Platform\Crm\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmContact;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;

class CreatePhoneNumberTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.phone_numbers.POST';
    }

    public function getDescription(): string
    {
        return 'POST /crm/phone_numbers - Fügt einer Company oder einem Contact eine Telefonnummer hinzu. Parameter: entity_type (contact|company), entity_id, raw_input, country_code (optional, default DE), phone_type_id (optional), is_primary (optional).';
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
                'raw_input' => [
                    'type' => 'string',
                    'description' => 'Telefonnummer (ERFORDERLICH).',
                ],
                'country_code' => [
                    'type' => 'string',
                    'description' => 'Optional: ISO2-Ländercode (z.B. "DE"). Default: "DE".',
                ],
                'phone_type_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Typ-ID (crm_phone_types.id).',
                ],
                'is_primary' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true, um als primär zu markieren.',
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
                    'description' => 'Optional: aktiv/inaktiv (default: true).',
                ],
            ],
            'required' => ['entity_type', 'entity_id', 'raw_input'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $type = $arguments['entity_type'] ?? null;
            $id = $arguments['entity_id'] ?? null;
            $raw = trim((string)($arguments['raw_input'] ?? ''));
            if (!$type || !$id || $raw === '') {
                return ToolResult::error('VALIDATION_ERROR', 'entity_type, entity_id und raw_input sind erforderlich.');
            }

            $entity = null;
            if ($type === 'contact') {
                $entity = CrmContact::with('phoneNumbers')->find($id);
            } elseif ($type === 'company') {
                $entity = CrmCompany::with('phoneNumbers')->find($id);
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

            $exists = $entity->phoneNumbers()->where('raw_input', $raw)->exists();
            if ($exists) {
                return ToolResult::error('VALIDATION_ERROR', 'Diese Telefonnummer existiert bereits bei dieser Entity.');
            }

            $country = strtoupper((string)($arguments['country_code'] ?? 'DE'));
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                $phoneNumber = $phoneUtil->parse($raw, $country);
                if (!$phoneUtil->isValidNumber($phoneNumber)) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültige Telefonnummer für das ausgewählte Land.');
                }
            } catch (NumberParseException $e) {
                return ToolResult::error('VALIDATION_ERROR', 'Ungültige Telefonnummer. Bitte Format prüfen.');
            }

            $isPrimary = (bool)($arguments['is_primary'] ?? false);
            if ($isPrimary) {
                $entity->phoneNumbers()->update(['is_primary' => false]);
            }

            $created = $entity->phoneNumbers()->create([
                'raw_input' => $raw,
                'international' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::E164),
                'national' => $phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL),
                'country_code' => $phoneUtil->getRegionCodeForNumber($phoneNumber),
                'phone_type_id' => $arguments['phone_type_id'] ?? 1,
                'is_primary' => $isPrimary,
                'notes' => $arguments['notes'] ?? null,
                'extension' => $arguments['extension'] ?? null,
                'is_active' => $arguments['is_active'] ?? true,
                'verified_at' => null,
            ]);

            $created->load('phoneType');

            return ToolResult::success([
                'id' => $created->id,
                'uuid' => $created->uuid,
                'entity_type' => $type,
                'entity_id' => (int)$id,
                'raw_input' => $created->raw_input,
                'international' => $created->international,
                'national' => $created->national,
                'country_code' => $created->country_code,
                'phone_type' => $created->phoneType?->name,
                'phone_type_id' => $created->phone_type_id,
                'is_primary' => (bool)$created->is_primary,
                'is_active' => (bool)$created->is_active,
                'message' => 'Telefonnummer wurde hinzugefügt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Hinzufügen der Telefonnummer: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'phone', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


