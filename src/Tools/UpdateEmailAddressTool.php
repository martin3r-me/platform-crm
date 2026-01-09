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
use Platform\Crm\Models\CrmEmailAddress;

class UpdateEmailAddressTool implements ToolContract, ToolMetadataContract
{
    use NormalizesLookupIds;

    public function getName(): string
    {
        return 'crm.email_addresses.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /crm/email_addresses/{id} - Aktualisiert eine E-Mail-Adresse (Contact/Company). Parameter: email_address_id, entity_type, entity_id, email_address (optional), email_type_id (optional), is_primary (optional), is_active (optional).';
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
                'email_address' => [
                    'type' => 'string',
                    'description' => 'Optional: neue E-Mail-Adresse.',
                ],
                'email_type_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Typ-ID (crm_email_types.id).',
                ],
                'is_primary' => [
                    'type' => 'boolean',
                    'description' => 'Optional: primär setzen/entfernen.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Notiz.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: aktiv/inaktiv.',
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

            $arguments = $this->normalizeLookupIds($arguments, ['email_type_id']);

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

            /** @var CrmEmailAddress|null $email */
            $email = $entity->emailAddresses()->where('id', $emailId)->first();
            if (!$email) {
                return ToolResult::error('EMAIL_NOT_FOUND', 'Die E-Mail-Adresse wurde nicht gefunden oder gehört nicht zur Entity.');
            }

            $update = [];
            if (array_key_exists('email_address', $arguments)) {
                $newEmail = trim((string)$arguments['email_address']);
                if ($newEmail === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'email_address darf nicht leer sein.');
                }

                $exists = $entity->emailAddresses()
                    ->where('email_address', $newEmail)
                    ->where('id', '!=', $email->id)
                    ->exists();
                if ($exists) {
                    return ToolResult::error('VALIDATION_ERROR', 'Diese E-Mail-Adresse existiert bereits bei dieser Entity.');
                }
                $update['email_address'] = $newEmail;
            }

            if (array_key_exists('email_type_id', $arguments)) {
                // Default: 1 (UI-Standard). Wichtig: niemals 0 schreiben.
                $update['email_type_id'] = ($arguments['email_type_id'] ?? null) ?? 1;
            }

            if (array_key_exists('notes', $arguments)) {
                $update['notes'] = $arguments['notes'] ?? null;
            }

            if (array_key_exists('is_active', $arguments)) {
                $update['is_active'] = (bool)$arguments['is_active'];
            }

            if (array_key_exists('is_primary', $arguments)) {
                $isPrimary = (bool)$arguments['is_primary'];
                $update['is_primary'] = $isPrimary;
                if ($isPrimary) {
                    $entity->emailAddresses()->where('id', '!=', $email->id)->update(['is_primary' => false]);
                }
            }

            if (!empty($update)) {
                $email->update($update);
            }

            $email->refresh();
            $email->load('emailType');

            return ToolResult::success([
                'id' => $email->id,
                'uuid' => $email->uuid,
                'entity_type' => $type,
                'entity_id' => (int)$entityId,
                'email_address' => $email->email_address,
                'email_type' => $email->emailType?->name,
                'email_type_id' => $email->email_type_id,
                'is_primary' => (bool)$email->is_primary,
                'is_active' => (bool)$email->is_active,
                'message' => 'E-Mail-Adresse wurde aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der E-Mail-Adresse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'email', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


