<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmCompanyType;
use Illuminate\Support\Str;

/**
 * Tool zum Anlegen eines Company-Typs (Lookup "company_types").
 *
 * Company-Typen beschreiben die fachliche Rolle einer Company. Die Liste ist
 * bewusst datengetrieben (kein Hardcoding im Code): fehlende Werte werden hierüber
 * als Daten angelegt, nicht per Seeder/Enum.
 */
class CreateCompanyTypeTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.company_types.POST';
    }

    public function getDescription(): string
    {
        return 'POST /crm/company-types - Legt einen neuen Company-Typ (Lookup) an. Parameter: name (required). code (optional, sonst aus name abgeleitet, muss eindeutig sein). is_active (optional, Standard true). Prüfe vorher mit "crm.lookup.GET" (lookup=company_types), ob der Typ schon existiert.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Required: Anzeigename des Company-Typs (z.B. "Wholesaler / Distributor").',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: eindeutiger technischer Code. Wird ohne Angabe aus dem Namen abgeleitet (z.B. "wholesaler_distributor").',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob der Typ aktiv/auswählbar ist. Standard: true.',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $name = trim((string) ($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            // Code ableiten, falls nicht angegeben
            $code = trim((string) ($arguments['code'] ?? ''));
            if ($code === '') {
                $code = Str::slug($name, '_');
            }
            if ($code === '') {
                return ToolResult::error('VALIDATION_ERROR', 'code konnte nicht aus dem Namen abgeleitet werden – bitte code explizit angeben.');
            }

            if (CrmCompanyType::where('code', $code)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', "Ein Company-Typ mit code '{$code}' existiert bereits. Nutze crm.company_types.PUT zum Ändern.");
            }

            $type = CrmCompanyType::create([
                'name' => $name,
                'code' => $code,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            return ToolResult::success([
                'id' => $type->id,
                'uuid' => $type->uuid,
                'name' => $type->name,
                'code' => $type->code,
                'is_active' => $type->is_active,
                'message' => "Company-Typ '{$type->name}' (code: {$type->code}) erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Company-Typs: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'company_type', 'create'],
            'risk_level' => 'write',
        ];
    }
}
