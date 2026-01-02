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
        return 'GET /companies?team_id={id}&filters=[...]&search=...&sort=[...] - Listet Companies (Unternehmen) auf, auf die der aktuelle User Zugriff hat. REST-Parameter: team_id (optional, integer) - wenn nicht angegeben, wird aktuelles Team verwendet. filters (optional, array) - Filter-Array mit field, op, value. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung mit field, dir. limit/offset (optional) - Pagination. RUF DIESES TOOL AUF, wenn der Nutzer nach Companies fragt.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (optional): Filter nach Team-ID. Beispiel: team_id=9. Wenn nicht angegeben, wird aktuelles Team aus Kontext verwendet. Nutze "core.teams.GET" um verfügbare Team-IDs zu sehen.'
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

            // Team-Filter bestimmen
            // WICHTIG: Behandle 0 als "nicht gesetzt" (OpenAI sendet manchmal 0 statt null)
            $teamIdArg = $arguments['team_id'] ?? null;
            if ($teamIdArg === 0 || $teamIdArg === '0') {
                $teamIdArg = null;
            }
            
            // Wenn team_id nicht angegeben, verwende aktuelles Team aus Kontext
            if ($teamIdArg === null) {
                $teamIdArg = $context->team?->id;
            }
            
            if (!$teamIdArg) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden. Nutze "core.teams.GET" um verfügbare Teams zu sehen, oder gib team_id explizit an.');
            }
            
            // Prüfe, ob User Zugriff auf dieses Team hat
            $userHasAccess = $context->user->teams()->where('teams.id', $teamIdArg)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamIdArg}. Nutze 'core.teams.GET' um verfügbare Teams zu sehen.");
            }
            
            // Query aufbauen - nur Companies dieses Teams
            $query = CrmCompany::query()
                ->where('team_id', $teamIdArg)
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
                'team_id' => $teamIdArg,
                'message' => count($companies) . ' Company(s) gefunden (Team-ID: ' . $teamIdArg . ').'
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

