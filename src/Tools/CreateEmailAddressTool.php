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

class CreateEmailAddressTool implements ToolContract, ToolMetadataContract
{
    use NormalizesLookupIds;

    public function getName(): string
    {
        return 'crm.email_addresses.POST';
    }

    public function getDescription(): string
    {
        return 'POST /crm/email_addresses - Fügt einer Company oder einem Contact eine E-Mail-Adresse hinzu. Parameter: entity_type (contact|company), entity_id, email_address, email_type_id (optional), is_primary (optional).';
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
                'email_address' => [
                    'type' => 'string',
                    'description' => 'E-Mail-Adresse (ERFORDERLICH).',
                ],
                'email_type_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Typ-ID (crm_email_types.id).',
                ],
                'is_primary' => [
                    'type' => 'boolean',
                    'description' => 'Optional: true, um als primär zu markieren.',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Notiz.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: aktiv/inaktiv (default: true).',
                ],
            ],
            'required' => ['entity_type', 'entity_id', 'email_address'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $arguments = $this->normalizeLookupIds($arguments, ['email_type_id']);

            $type = $arguments['entity_type'] ?? null;
            $id = $arguments['entity_id'] ?? null;
            $email = trim((string)($arguments['email_address'] ?? ''));
            if (!$type || !$id || $email === '') {
                return ToolResult::error('VALIDATION_ERROR', 'entity_type, entity_id und email_address sind erforderlich.');
            }

            $entity = null;
            if ($type === 'contact') {
                $entity = CrmContact::with('emailAddresses')->find($id);
            } elseif ($type === 'company') {
                $entity = CrmCompany::with('emailAddresses')->find($id);
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

            $exists = $entity->emailAddresses()
                ->where('email_address', $email)
                ->exists();
            if ($exists) {
                return ToolResult::error('VALIDATION_ERROR', 'Diese E-Mail-Adresse existiert bereits bei dieser Entity.');
            }

            $isPrimary = (bool)($arguments['is_primary'] ?? false);
            if ($isPrimary) {
                $entity->emailAddresses()->update(['is_primary' => false]);
            }

            /** @var CrmEmailAddress $created */
            $created = $entity->emailAddresses()->create([
                'email_address' => $email,
                // Default: 1 (UI-Standard). Wichtig: niemals 0 schreiben.
                'email_type_id' => ($arguments['email_type_id'] ?? null) ?? 1,
                'is_primary' => $isPrimary,
                'notes' => $arguments['notes'] ?? null,
                'is_active' => $arguments['is_active'] ?? true,
                'is_verified' => false,
                'verified_at' => null,
            ]);

            $created->load('emailType');

            return ToolResult::success([
                'id' => $created->id,
                'uuid' => $created->uuid,
                'entity_type' => $type,
                'entity_id' => (int)$id,
                'email_address' => $created->email_address,
                'email_type' => $created->emailType?->name,
                'email_type_id' => $created->email_type_id,
                'is_primary' => (bool)$created->is_primary,
                'is_active' => (bool)$created->is_active,
                'message' => 'E-Mail-Adresse wurde hinzugefügt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Hinzufügen der E-Mail-Adresse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'email', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


