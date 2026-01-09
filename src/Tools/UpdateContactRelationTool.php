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
use Platform\Crm\Models\CrmContactRelation;
use Platform\Crm\Models\CrmContactRelationType;
use Carbon\Carbon;

class UpdateContactRelationTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact_relations.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /crm/contact_relations/{id} - Aktualisiert eine Contact↔Company Beziehung. Parameter: relation_id (required), position/start_date/end_date/is_primary/notes/is_active/relation_type_id optional.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'relation_id' => ['type' => 'integer', 'description' => 'Relation-ID (ERFORDERLICH).'],
                'relation_type_id' => ['type' => 'integer', 'description' => 'Optional: Beziehungstyp-ID.'],
                'position' => ['type' => 'string', 'description' => 'Optional: Position/Rolle.'],
                'start_date' => ['type' => 'string', 'description' => 'Optional: Startdatum (YYYY-MM-DD).'],
                'end_date' => ['type' => 'string', 'description' => 'Optional: Enddatum (YYYY-MM-DD).'],
                'is_primary' => ['type' => 'boolean', 'description' => 'Optional: primär setzen/entfernen.'],
                'notes' => ['type' => 'string', 'description' => 'Optional: Notiz.'],
                'is_active' => ['type' => 'boolean', 'description' => 'Optional: aktiv/inaktiv.'],
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

            $update = [];

            if (array_key_exists('relation_type_id', $arguments)) {
                $typeId = $arguments['relation_type_id'];
                if ($typeId !== null && !CrmContactRelationType::whereKey($typeId)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', 'relation_type_id ist ungültig.');
                }
                $update['relation_type_id'] = $typeId;
            }

            if (array_key_exists('position', $arguments)) {
                $update['position'] = $arguments['position'] ?? null;
            }

            if (array_key_exists('notes', $arguments)) {
                $update['notes'] = $arguments['notes'] ?? null;
            }

            if (array_key_exists('is_active', $arguments)) {
                $update['is_active'] = (bool)$arguments['is_active'];
            }

            if (array_key_exists('start_date', $arguments)) {
                $raw = $arguments['start_date'];
                $update['start_date'] = $raw ? Carbon::parse($raw)->toDateString() : null;
            }

            if (array_key_exists('end_date', $arguments)) {
                $raw = $arguments['end_date'];
                $update['end_date'] = $raw ? Carbon::parse($raw)->toDateString() : null;
            }

            if (!empty($update) && isset($update['start_date'], $update['end_date']) && $update['start_date'] && $update['end_date']) {
                if ($update['end_date'] < $update['start_date']) {
                    return ToolResult::error('VALIDATION_ERROR', 'end_date muss >= start_date sein.');
                }
            }

            if (array_key_exists('is_primary', $arguments)) {
                $isPrimary = (bool)$arguments['is_primary'];
                $update['is_primary'] = $isPrimary;
                if ($isPrimary) {
                    // wie UI: primär innerhalb der jeweiligen Liste
                    CrmContactRelation::where('contact_id', $relation->contact_id)->where('id', '!=', $relation->id)->update(['is_primary' => false]);
                    CrmContactRelation::where('company_id', $relation->company_id)->where('id', '!=', $relation->id)->update(['is_primary' => false]);
                }
            }

            if (!empty($update)) {
                $relation->update($update);
            }

            $relation->refresh();
            $relation->load(['contact', 'company', 'relationType']);

            return ToolResult::success([
                'id' => $relation->id,
                'uuid' => $relation->uuid,
                'contact_id' => $relation->contact_id,
                'company_id' => $relation->company_id,
                'contact_name' => $relation->contact?->name,
                'company_name' => $relation->company?->name,
                'relation_type' => $relation->relationType?->name,
                'relation_type_id' => $relation->relation_type_id,
                'position' => $relation->position,
                'start_date' => $relation->start_date?->toDateString(),
                'end_date' => $relation->end_date?->toDateString(),
                'is_primary' => (bool)$relation->is_primary,
                'is_active' => (bool)$relation->is_active,
                'notes' => $relation->notes,
                'message' => 'Beziehung wurde aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Beziehung: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'contact', 'company', 'relation', 'update'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


