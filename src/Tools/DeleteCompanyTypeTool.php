<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmCompanyType;

/**
 * Tool zum Löschen eines Company-Typs (Lookup "company_types").
 *
 * Schützt vor dem Löschen eines Typs, der noch von Companies verwendet wird –
 * in dem Fall is_active=false (deaktivieren) statt löschen.
 */
class DeleteCompanyTypeTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.company_types.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /crm/company-types/{id} - Löscht einen Company-Typ (Lookup). Wird der Typ noch von Companies verwendet, schlägt das Löschen fehl – dann stattdessen via crm.company_types.PUT auf is_active=false setzen. Nutze "crm.lookup.GET" (lookup=company_types) für die ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des zu löschenden Company-Typs.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $arguments['id'] ?? null;
            if (!is_numeric($id)) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $type = CrmCompanyType::find((int) $id);
            if (!$type) {
                return ToolResult::error('NOT_FOUND', 'Company-Typ nicht gefunden. Nutze "crm.lookup.GET" (lookup=company_types).');
            }

            $inUse = CrmCompany::where('company_type_id', $type->id)->count();
            if ($inUse > 0) {
                return ToolResult::error(
                    'CONFLICT',
                    "Company-Typ '{$type->name}' wird noch von {$inUse} Company(s) verwendet und kann nicht gelöscht werden. Setze ihn stattdessen über crm.company_types.PUT auf is_active=false."
                );
            }

            $name = $type->name;
            $type->delete();

            return ToolResult::success([
                'id' => (int) $id,
                'name' => $name,
                'message' => "Company-Typ '{$name}' gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Company-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'company_type', 'delete'],
            'risk_level' => 'write',
        ];
    }
}
