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

class DeletePostalAddressTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.postal_addresses.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /crm/postal_addresses/{id} - Löscht eine Postadresse von Contact/Company. Parameter: postal_address_id, entity_type, entity_id.';
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
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung.',
                ],
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

            $address = $entity->postalAddresses()->where('id', $addrId)->first();
            if (!$address) {
                return ToolResult::error('ADDRESS_NOT_FOUND', 'Die Postadresse wurde nicht gefunden oder gehört nicht zur Entity.');
            }

            if ($address->is_primary && !($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Diese Postadresse ist als primär markiert. Bitte bestätige mit confirm: true.');
            }

            $summary = trim(($address->street ?? '') . ' ' . ($address->house_number ?? '') . ', ' . ($address->postal_code ?? '') . ' ' . ($address->city ?? ''));
            $address->delete();

            return ToolResult::success([
                'postal_address_id' => (int)$addrId,
                'entity_type' => $type,
                'entity_id' => (int)$entityId,
                'summary' => $summary,
                'message' => 'Postadresse wurde gelöscht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Postadresse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'postal', 'address', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => true,
        ];
    }
}


