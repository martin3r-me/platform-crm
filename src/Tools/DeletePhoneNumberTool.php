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

class DeletePhoneNumberTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.phone_numbers.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /crm/phone_numbers/{id} - Löscht eine Telefonnummer von Contact/Company. Parameter: phone_number_id, entity_type, entity_id.';
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
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung.',
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

            if ($phone->is_primary && !($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Diese Telefonnummer ist als primär markiert. Bitte bestätige mit confirm: true.');
            }

            $phoneValue = $phone->international ?: $phone->national ?: $phone->raw_input;
            $phone->delete();

            return ToolResult::success([
                'phone_number_id' => (int)$phoneId,
                'entity_type' => $type,
                'entity_id' => (int)$entityId,
                'phone' => $phoneValue,
                'message' => 'Telefonnummer wurde gelöscht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Telefonnummer: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'phone', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => true,
        ];
    }
}


