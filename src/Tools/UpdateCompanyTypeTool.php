<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmCompanyType;

/**
 * Tool zum Aktualisieren eines Company-Typs (Lookup "company_types").
 */
class UpdateCompanyTypeTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.company_types.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /crm/company-types/{id} - Aktualisiert einen Company-Typ (Lookup). Parameter: id (required). name, code, is_active (optional). Nutze "crm.lookup.GET" (lookup=company_types) für die ID. is_active=false deaktiviert einen Typ, ohne ihn zu löschen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Company-Typs.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: neuer Anzeigename.',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: neuer eindeutiger Code.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: aktiv/inaktiv setzen.',
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

            $updated = [];

            if (array_key_exists('name', $arguments)) {
                $name = trim((string) $arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                $type->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('code', $arguments)) {
                $code = trim((string) $arguments['code']);
                if ($code === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'code darf nicht leer sein.');
                }
                if (CrmCompanyType::where('code', $code)->where('id', '!=', $type->id)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', "Ein anderer Company-Typ mit code '{$code}' existiert bereits.");
                }
                $type->code = $code;
                $updated[] = 'code';
            }

            if (array_key_exists('is_active', $arguments)) {
                $type->is_active = (bool) $arguments['is_active'];
                $updated[] = 'is_active';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            $type->save();

            return ToolResult::success([
                'id' => $type->id,
                'uuid' => $type->uuid,
                'name' => $type->name,
                'code' => $type->code,
                'is_active' => $type->is_active,
                'updated_fields' => $updated,
                'message' => "Company-Typ '{$type->name}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Company-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'company_type', 'update'],
            'risk_level' => 'write',
        ];
    }
}
