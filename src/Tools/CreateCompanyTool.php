<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Erstellen von Companies im CRM-Modul
 */
class CreateCompanyTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.companies.POST';
    }

    public function getDescription(): string
    {
        return 'POST /companies - Erstellt eine neue Company. REST-Parameter: name (required, string) - Name der Company. team_id (optional, integer) - wenn nicht angegeben, wird aktuelles Team verwendet. description (optional, string) - Beschreibung. is_active (optional, boolean) - Status.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Company (ERFORDERLICH). Frage den Nutzer explizit nach dem Namen, wenn er nicht angegeben wurde.'
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Teams, in dem die Company erstellt werden soll. Wenn nicht angegeben, wird das aktuelle Team aus dem Kontext verwendet.'
                ],
                'legal_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Rechtlicher Name der Company.'
                ],
                'trading_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Handelsname der Company.'
                ],
                'website' => [
                    'type' => 'string',
                    'description' => 'Optional: Website-URL der Company.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Company.'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Notizen zur Company.'
                ],
                'industry_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID der Branche. Frage nach, wenn der Nutzer eine Branche angibt.'
                ],
                'legal_form_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID der Rechtsform. Frage nach, wenn der Nutzer eine Rechtsform angibt.'
                ],
                'contact_status_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Kontaktstatus. Frage nach, wenn der Nutzer einen Status angibt.'
                ],
                'country_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Landes. Frage nach, wenn der Nutzer ein Land angibt.'
                ],
                'registration_number' => [
                    'type' => 'string',
                    'description' => 'Optional: Handelsregisternummer.'
                ],
                'tax_number' => [
                    'type' => 'string',
                    'description' => 'Optional: Steuernummer.'
                ],
                'vat_number' => [
                    'type' => 'string',
                    'description' => 'Optional: Umsatzsteuer-ID.'
                ],
                'owned_by_user_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID des Users, der die Company besitzt. Wenn nicht angegeben, wird automatisch der aktuelle Nutzer verwendet. Verwende NIEMALS hardcoded IDs wie 1 oder 0.'
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob die Company aktiv ist. Standard: true.'
                ]
            ],
            'required' => ['name']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Validierung
            if (empty($arguments['name'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Name ist erforderlich.');
            }

            // Team bestimmen
            $teamId = $arguments['team_id'] ?? $this->resolveRootTeamId($context->user) ?? $context->team?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden. Nutze das Tool "core.teams.GET" um alle verfügbaren Teams zu sehen.');
            }

            // Zugriff: User muss im Team sein
            $userHasAccess = $context->user->teams()->where('teams.id', $teamId)->exists();
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamId}.");
            }

            // Policy: create
            try {
                Gate::forUser($context->user)->authorize('create', CrmCompany::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Companies erstellen (Policy).');
            }

            // Owner bestimmen (behandle 1/0 als null)
            $ownedByUserId = $arguments['owned_by_user_id'] ?? null;
            if ($ownedByUserId === 1 || $ownedByUserId === 0 || $ownedByUserId === '1' || $ownedByUserId === '0') {
                $ownedByUserId = null;
            }
            if (!$ownedByUserId) {
                $ownedByUserId = $context->user->id;
            }

            // FK-IDs: 0/"0" ist KEIN gültiger FK-Wert → als null behandeln (verhindert FK-Constraint Errors)
            $industryId = $arguments['industry_id'] ?? null;
            if ($industryId === 0 || $industryId === '0') { $industryId = null; }
            $legalFormId = $arguments['legal_form_id'] ?? null;
            if ($legalFormId === 0 || $legalFormId === '0') { $legalFormId = null; }
            $contactStatusId = $arguments['contact_status_id'] ?? null;
            if ($contactStatusId === 0 || $contactStatusId === '0') { $contactStatusId = null; }
            $countryId = $arguments['country_id'] ?? null;
            if ($countryId === 0 || $countryId === '0') { $countryId = null; }

            // Company erstellen
            $company = CrmCompany::create([
                'name' => $arguments['name'],
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
                'owned_by_user_id' => $ownedByUserId,
                'legal_name' => $arguments['legal_name'] ?? null,
                'trading_name' => $arguments['trading_name'] ?? null,
                'website' => $arguments['website'] ?? null,
                'description' => $arguments['description'] ?? null,
                'notes' => $arguments['notes'] ?? null,
                'industry_id' => $industryId,
                'legal_form_id' => $legalFormId,
                'contact_status_id' => $contactStatusId,
                'country_id' => $countryId,
                'registration_number' => $arguments['registration_number'] ?? null,
                'tax_number' => $arguments['tax_number'] ?? null,
                'vat_number' => $arguments['vat_number'] ?? null,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            // Beziehungen laden
            $company->load(['industry', 'legalForm', 'contactStatus', 'country', 'createdByUser', 'ownedByUser']);

            return ToolResult::success([
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
                'message' => "Company '{$company->name}' erfolgreich erstellt."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Company: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'company', 'create'],
            'risk_level' => 'write',
        ];
    }
}

