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

class CreateContactRelationTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact_relations.POST';
    }

    public function getDescription(): string
    {
        return 'POST /crm/contact_relations - Verknüpft Contact und Company (wie UI). Parameter: contact_id, company_id, relation_type_id, position, start_date, end_date, is_primary, notes, is_active.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'contact_id' => ['type' => 'integer', 'description' => 'Contact-ID (ERFORDERLICH).'],
                'company_id' => ['type' => 'integer', 'description' => 'Company-ID (ERFORDERLICH).'],
                'relation_type_id' => ['type' => 'integer', 'description' => 'Beziehungstyp-ID (ERFORDERLICH).'],
                'position' => ['type' => 'string', 'description' => 'Optional: Position/Rolle.'],
                'start_date' => ['type' => 'string', 'description' => 'Optional: Startdatum (YYYY-MM-DD).'],
                'end_date' => ['type' => 'string', 'description' => 'Optional: Enddatum (YYYY-MM-DD).'],
                'is_primary' => ['type' => 'boolean', 'description' => 'Optional: als primär markieren.'],
                'notes' => ['type' => 'string', 'description' => 'Optional: Notiz.'],
                'is_active' => ['type' => 'boolean', 'description' => 'Optional: aktiv/inaktiv (default: true).'],
            ],
            'required' => ['contact_id', 'company_id', 'relation_type_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $contactId = $arguments['contact_id'] ?? null;
            $companyId = $arguments['company_id'] ?? null;
            $relationTypeId = $arguments['relation_type_id'] ?? null;
            if (!$contactId || !$companyId || !$relationTypeId) {
                return ToolResult::error('VALIDATION_ERROR', 'contact_id, company_id und relation_type_id sind erforderlich.');
            }

            $contact = CrmContact::find($contactId);
            if (!$contact) {
                return ToolResult::error('CONTACT_NOT_FOUND', 'Contact wurde nicht gefunden.');
            }
            $company = CrmCompany::find($companyId);
            if (!$company) {
                return ToolResult::error('COMPANY_NOT_FOUND', 'Company wurde nicht gefunden.');
            }

            if ($contact->team_id !== $company->team_id) {
                return ToolResult::error('VALIDATION_ERROR', 'Contact und Company müssen im selben Team (root) sein.');
            }

            if (!CrmContactRelationType::whereKey($relationTypeId)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', 'relation_type_id ist ungültig.');
            }

            // Policy: UI kann Relation bearbeiten, wenn man die jeweilige Seite bearbeiten darf
            try {
                Gate::forUser($context->user)->authorize('update', $contact);
                Gate::forUser($context->user)->authorize('update', $company);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst Contact/Company nicht bearbeiten (Policy).');
            }

            // Duplikat wie UI verhindern (mindestens company/contact unique)
            $existing = CrmContactRelation::query()
                ->where('contact_id', $contactId)
                ->where('company_id', $companyId)
                ->first();
            if ($existing) {
                return ToolResult::error('VALIDATION_ERROR', 'Dieser Kontakt ist bereits mit diesem Unternehmen verknüpft.');
            }

            $isPrimary = (bool)($arguments['is_primary'] ?? false);
            if ($isPrimary) {
                // Wie UI: Primär innerhalb der Kontakt- bzw. Company-Liste (je nachdem, wo man editiert).
                // Wir setzen beides konsistent, damit es egal ist, aus welchem Kontext es aufgerufen wurde.
                CrmContactRelation::where('contact_id', $contactId)->update(['is_primary' => false]);
                CrmContactRelation::where('company_id', $companyId)->update(['is_primary' => false]);
            }

            $startDate = null;
            if (array_key_exists('start_date', $arguments)) {
                $raw = $arguments['start_date'];
                if (is_string($raw)) {
                    $raw = trim($raw);
                }
                if ($raw !== null && $raw !== '') {
                    try {
                        $startDate = Carbon::parse($raw)->toDateString();
                    } catch (\Throwable $e) {
                        return ToolResult::error('VALIDATION_ERROR', 'start_date ist ungültig. Erwartet: YYYY-MM-DD.');
                    }
                }
            }

            $endDate = null;
            if (array_key_exists('end_date', $arguments)) {
                $raw = $arguments['end_date'];
                if (is_string($raw)) {
                    $raw = trim($raw);
                }
                if ($raw !== null && $raw !== '') {
                    try {
                        $endDate = Carbon::parse($raw)->toDateString();
                    } catch (\Throwable $e) {
                        return ToolResult::error('VALIDATION_ERROR', 'end_date ist ungültig. Erwartet: YYYY-MM-DD.');
                    }
                }
            }

            if ($startDate && $endDate && $endDate < $startDate) {
                return ToolResult::error('VALIDATION_ERROR', 'end_date muss >= start_date sein.');
            }

            $relation = CrmContactRelation::create([
                'contact_id' => $contactId,
                'company_id' => $companyId,
                'relation_type_id' => $relationTypeId,
                'position' => $arguments['position'] ?? null,
                'notes' => $arguments['notes'] ?? null,
                // Leere Strings (z.B. "") müssen als null gespeichert werden, sonst DB-Fehler (Incorrect date value).
                'start_date' => $startDate,
                'end_date' => $endDate,
                'is_primary' => $isPrimary,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

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
                'message' => 'Kontakt/Unternehmen verknüpft.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Beziehung: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'contact', 'company', 'relation', 'create'],
            'read_only' => false,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'medium',
            'idempotent' => false,
        ];
    }
}


