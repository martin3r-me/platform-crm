<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CrmCompany;

/**
 * Tool zum Auflisten von Companies im CRM-Modul
 */
class ListCompaniesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'crm.companies.GET';
    }

    public function getDescription(): string
    {
        return 'Listet alle Companies (Unternehmen) auf, auf die der aktuelle User Zugriff hat. RUF DIESES TOOL AUF, wenn der Nutzer nach Companies fragt, wenn du prüfen musst, ob eine Company existiert, oder wenn du eine Company finden musst, bevor du sie bearbeitest oder löschst. Wenn der Nutzer nur einen Company-Namen angibt, nutze dieses Tool, um die Company-ID zu finden.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Team-ID. Wenn nicht angegeben, wird das aktuelle Team aus dem Kontext verwendet.'
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Filter nach aktiv/inaktiv Status.'
                    ],
                    'name_search' => [
                        'type' => 'string',
                        'description' => 'Optional: Suche nach Company-Namen (Legacy - nutze stattdessen search Parameter).'
                    ]
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Team bestimmen
            $teamId = $arguments['team_id'] ?? $context->team?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden. Nutze das Tool "core.teams.GET" um alle verfügbaren Teams zu sehen.');
            }

            // Query aufbauen
            $query = CrmCompany::query()
                ->where('team_id', $teamId)
                ->with(['industry', 'legalForm', 'contactStatus', 'country', 'createdByUser', 'ownedByUser']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'name', 'legal_name', 'trading_name', 'website', 'description', 'is_active', 'created_at', 'updated_at'
            ]);

            // Legacy: name_search
            if (!empty($arguments['name_search'])) {
                $query->where(function ($q) use ($arguments) {
                    $q->where('name', 'like', '%' . $arguments['name_search'] . '%')
                      ->orWhere('legal_name', 'like', '%' . $arguments['name_search'] . '%')
                      ->orWhere('trading_name', 'like', '%' . $arguments['name_search'] . '%');
                });
            }

            // is_active Filter
            if (isset($arguments['is_active'])) {
                $query->where('is_active', $arguments['is_active']);
            }

            // Standard-Sortierung und Pagination
            $this->applyStandardSorting($query, $arguments, 'name', 'asc');
            $result = $this->applyStandardPagination($query, $arguments);

            // Formatierung
            $companies = $result['data']->map(function ($company) {
                return [
                    'id' => $company->id,
                    'uuid' => $company->uuid,
                    'name' => $company->name,
                    'legal_name' => $company->legal_name,
                    'trading_name' => $company->trading_name,
                    'website' => $company->website,
                    'description' => $company->description,
                    'industry' => $company->industry?->name,
                    'legal_form' => $company->legalForm?->name,
                    'contact_status' => $company->contactStatus?->name,
                    'country' => $company->country?->name,
                    'is_active' => $company->is_active,
                    'created_by' => $company->createdByUser?->name,
                    'owned_by' => $company->ownedByUser?->name,
                    'created_at' => $company->created_at->toIso8601String(),
                    'updated_at' => $company->updated_at->toIso8601String(),
                ];
            })->toArray();

            return ToolResult::success([
                'companies' => $companies,
                'pagination' => $result['pagination'],
                'message' => count($companies) . ' Company(s) gefunden.'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auflisten der Companies: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['crm', 'company', 'list'],
            'risk_level' => 'read',
        ];
    }
}

