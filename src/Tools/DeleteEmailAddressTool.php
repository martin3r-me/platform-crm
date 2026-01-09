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

class DeleteEmailAddressTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.email_addresses.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /crm/email_addresses/{id} - Löscht eine E-Mail-Adresse von Contact/Company. Parameter: email_address_id, entity_type, entity_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'email_address_id' => [
                    'type' => 'integer',
                    'description' => 'ID der E-Mail-Adresse (ERFORDERLICH).',
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
            'required' => ['email_address_id', 'entity_type', 'entity_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $emailId = $arguments['email_address_id'] ?? null;
            $type = $arguments['entity_type'] ?? null;
            $entityId = $arguments['entity_id'] ?? null;
            if (!$emailId || !$type || !$entityId) {
                return ToolResult::error('VALIDATION_ERROR', 'email_address_id, entity_type und entity_id sind erforderlich.');
            }

            $entity = null;
            if ($type === 'contact') {
                $entity = CrmContact::with('emailAddresses')->find($entityId);
            } elseif ($type === 'company') {
                $entity = CrmCompany::with('emailAddresses')->find($entityId);
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

            $email = $entity->emailAddresses()->where('id', $emailId)->first();
            if (!$email) {
                return ToolResult::error('EMAIL_NOT_FOUND', 'Die E-Mail-Adresse wurde nicht gefunden oder gehört nicht zur Entity.');
            }

            // Optional: Bestätigung, wenn primär
            if ($email->is_primary && !($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Diese E-Mail-Adresse ist als primär markiert. Bitte bestätige mit confirm: true.');
            }

            $emailValue = $email->email_address;
            $email->delete();

            return ToolResult::success([
                'email_address_id' => (int)$emailId,
                'entity_type' => $type,
                'entity_id' => (int)$entityId,
                'email_address' => $emailValue,
                'message' => 'E-Mail-Adresse wurde gelöscht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der E-Mail-Adresse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'email', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => true,
        ];
    }
}


