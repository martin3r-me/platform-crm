<?php

namespace Platform\Crm\Tools;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CrmContactRelation;

class DeleteContactRelationTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact_relations.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /crm/contact_relations/{id} - Löscht eine Contact↔Company Beziehung. Parameter: relation_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'relation_id' => [
                    'type' => 'integer',
                    'description' => 'Relation-ID (ERFORDERLICH).',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung.',
                ],
            ],
            'required' => ['relation_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $relationId = $arguments['relation_id'] ?? null;
            if (!$relationId) {
                return ToolResult::error('VALIDATION_ERROR', 'relation_id ist erforderlich.');
            }

            $relation = CrmContactRelation::with(['contact', 'company', 'relationType'])->find($relationId);
            if (!$relation) {
                return ToolResult::error('RELATION_NOT_FOUND', 'Die Beziehung wurde nicht gefunden.');
            }

            $contact = $relation->contact;
            $company = $relation->company;
            if (!$contact || !$company) {
                return ToolResult::error('RELATION_INVALID', 'Contact oder Company der Beziehung fehlt.');
            }

            try {
                Gate::forUser($context->user)->authorize('update', $contact);
                Gate::forUser($context->user)->authorize('update', $company);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst Contact/Company nicht bearbeiten (Policy).');
            }

            if ($relation->is_primary && !($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Diese Beziehung ist als primär markiert. Bitte bestätige mit confirm: true.');
            }

            $summary = trim(($relation->contact?->name ?? '') . ' ↔ ' . ($relation->company?->name ?? ''));
            $relation->delete();

            return ToolResult::success([
                'relation_id' => (int)$relationId,
                'summary' => $summary,
                'message' => 'Beziehung wurde gelöscht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Beziehung: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'contact', 'company', 'relation', 'delete'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => true,
        ];
    }
}


