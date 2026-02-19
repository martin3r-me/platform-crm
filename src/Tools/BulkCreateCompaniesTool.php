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
use Illuminate\Support\Facades\DB;

/**
 * Bulk-Tool zum Erstellen mehrerer Companies im CRM-Modul
 */
class BulkCreateCompaniesTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.companies.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /companies/bulk - Erstellt mehrere Companies gleichzeitig. Maximal 50 Companies pro Aufruf. Jeder Eintrag im items-Array entspricht dem Schema von crm.companies.POST.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID für alle Companies. Wenn nicht angegeben, wird das aktuelle Team verwendet.'
                ],
                'items' => [
                    'type' => 'array',
                    'description' => 'Array von Company-Objekten (max. 50). Jedes Objekt hat die gleichen Felder wie crm.companies.POST.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => [
                                'type' => 'string',
                                'description' => 'Name der Company (ERFORDERLICH).'
                            ],
                            'legal_name' => ['type' => 'string', 'description' => 'Optional: Rechtlicher Name.'],
                            'trading_name' => ['type' => 'string', 'description' => 'Optional: Handelsname.'],
                            'website' => ['type' => 'string', 'description' => 'Optional: Website-URL.'],
                            'description' => ['type' => 'string', 'description' => 'Optional: Beschreibung.'],
                            'notes' => ['type' => 'string', 'description' => 'Optional: Notizen.'],
                            'industry_id' => ['type' => 'integer', 'description' => 'Optional: Branchen-ID.'],
                            'legal_form_id' => ['type' => 'integer', 'description' => 'Optional: Rechtsform-ID.'],
                            'contact_status_id' => ['type' => 'integer', 'description' => 'Optional: Kontaktstatus-ID.'],
                            'country_id' => ['type' => 'integer', 'description' => 'Optional: Länder-ID.'],
                            'registration_number' => ['type' => 'string', 'description' => 'Optional: Handelsregisternummer.'],
                            'tax_number' => ['type' => 'string', 'description' => 'Optional: Steuernummer.'],
                            'vat_number' => ['type' => 'string', 'description' => 'Optional: Umsatzsteuer-ID.'],
                            'owned_by_user_id' => ['type' => 'integer', 'description' => 'Optional: Owner-User-ID.'],
                            'is_active' => ['type' => 'boolean', 'description' => 'Optional: Aktiv-Status. Standard: true.'],
                        ],
                        'required' => ['name']
                    ]
                ]
            ],
            'required' => ['items']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $items = $arguments['items'] ?? [];
            if (empty($items)) {
                return ToolResult::error('VALIDATION_ERROR', 'items-Array darf nicht leer sein.');
            }
            if (count($items) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Companies pro Bulk-Aufruf erlaubt.');
            }

            // Team bestimmen (global für alle Items)
            $requestedTeamId = $arguments['team_id'] ?? null;
            $teamId = $this->normalizeToRootTeamId(
                is_numeric($requestedTeamId) ? (int)$requestedTeamId : null,
                $context->user
            ) ?? $context->team?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }

            if (!$this->userHasAccessToCrmRootTeam($context->user, (int)$teamId)) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamId}.");
            }

            try {
                Gate::forUser($context->user)->authorize('create', CrmCompany::class);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst keine Companies erstellen (Policy).');
            }

            $created = [];
            $errors = [];

            DB::transaction(function () use ($items, $teamId, $context, &$created, &$errors) {
                foreach ($items as $index => $item) {
                    try {
                        if (empty($item['name'])) {
                            $errors[] = ['index' => $index, 'error' => 'Name ist erforderlich.'];
                            continue;
                        }

                        // Owner bestimmen
                        $ownedByUserId = $item['owned_by_user_id'] ?? null;
                        if ($ownedByUserId === 1 || $ownedByUserId === 0 || $ownedByUserId === '1' || $ownedByUserId === '0') {
                            $ownedByUserId = null;
                        }
                        if (!$ownedByUserId) {
                            $ownedByUserId = $context->user->id;
                        }

                        // FK-IDs: 0/"0" als null behandeln
                        $industryId = $item['industry_id'] ?? null;
                        if ($industryId === 0 || $industryId === '0') { $industryId = null; }
                        $legalFormId = $item['legal_form_id'] ?? null;
                        if ($legalFormId === 0 || $legalFormId === '0') { $legalFormId = null; }
                        $contactStatusId = $item['contact_status_id'] ?? null;
                        if ($contactStatusId === 0 || $contactStatusId === '0') { $contactStatusId = null; }
                        $countryId = $item['country_id'] ?? null;
                        if ($countryId === 0 || $countryId === '0') { $countryId = null; }

                        $company = CrmCompany::create([
                            'name' => $item['name'],
                            'team_id' => $teamId,
                            'created_by_user_id' => $context->user->id,
                            'owned_by_user_id' => $ownedByUserId,
                            'legal_name' => $item['legal_name'] ?? null,
                            'trading_name' => $item['trading_name'] ?? null,
                            'website' => $item['website'] ?? null,
                            'description' => $item['description'] ?? null,
                            'notes' => $item['notes'] ?? null,
                            'industry_id' => $industryId,
                            'legal_form_id' => $legalFormId,
                            'contact_status_id' => $contactStatusId,
                            'country_id' => $countryId,
                            'registration_number' => $item['registration_number'] ?? null,
                            'tax_number' => $item['tax_number'] ?? null,
                            'vat_number' => $item['vat_number'] ?? null,
                            'is_active' => $item['is_active'] ?? true,
                        ]);

                        $created[] = [
                            'index' => $index,
                            'id' => $company->id,
                            'name' => $company->name,
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = ['index' => $index, 'error' => $e->getMessage()];
                    }
                }
            });

            return ToolResult::success([
                'created_count' => count($created),
                'error_count' => count($errors),
                'total_requested' => count($items),
                'created' => $created,
                'errors' => $errors ?: null,
                'message' => count($created) . ' von ' . count($items) . ' Companies erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Erstellen von Companies: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'company', 'bulk', 'create'],
            'risk_level' => 'write',
        ];
    }
}
