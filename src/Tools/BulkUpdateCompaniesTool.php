<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmCompany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-Tool zum Aktualisieren mehrerer Companies im CRM-Modul
 */
class BulkUpdateCompaniesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'crm.companies.bulk.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /companies/bulk - Aktualisiert mehrere Companies gleichzeitig. Maximal 50 Companies pro Aufruf. Jeder Eintrag im items-Array benötigt eine company_id und die zu ändernden Felder.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'items' => [
                    'type' => 'array',
                    'description' => 'Array von Update-Objekten (max. 50). Jedes Objekt benötigt company_id und die zu ändernden Felder.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'company_id' => [
                                'type' => 'integer',
                                'description' => 'ID der zu aktualisierenden Company (ERFORDERLICH).'
                            ],
                            'name' => ['type' => 'string', 'description' => 'Optional: Neuer Name.'],
                            'legal_name' => ['type' => 'string', 'description' => 'Optional: Neuer rechtlicher Name.'],
                            'trading_name' => ['type' => 'string', 'description' => 'Optional: Neuer Handelsname.'],
                            'website' => ['type' => 'string', 'description' => 'Optional: Neue Website-URL.'],
                            'description' => ['type' => 'string', 'description' => 'Optional: Neue Beschreibung.'],
                            'notes' => ['type' => 'string', 'description' => 'Optional: Neue Notizen.'],
                            'industry_id' => ['type' => 'integer', 'description' => 'Optional: Neue Branchen-ID.'],
                            'legal_form_id' => ['type' => 'integer', 'description' => 'Optional: Neue Rechtsform-ID.'],
                            'contact_status_id' => ['type' => 'integer', 'description' => 'Optional: Neue Kontaktstatus-ID.'],
                            'country_id' => ['type' => 'integer', 'description' => 'Optional: Neue Länder-ID.'],
                            'registration_number' => ['type' => 'string', 'description' => 'Optional: Neue Handelsregisternummer.'],
                            'tax_number' => ['type' => 'string', 'description' => 'Optional: Neue Steuernummer.'],
                            'vat_number' => ['type' => 'string', 'description' => 'Optional: Neue Umsatzsteuer-ID.'],
                            'owned_by_user_id' => ['type' => 'integer', 'description' => 'Optional: Neue Owner-User-ID.'],
                            'is_active' => ['type' => 'boolean', 'description' => 'Optional: Aktiv/Inaktiv Status.'],
                        ],
                        'required' => ['company_id']
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

            $updated = [];
            $errors = [];

            DB::transaction(function () use ($items, $context, &$updated, &$errors) {
                foreach ($items as $index => $item) {
                    try {
                        $companyId = $item['company_id'] ?? null;
                        if (!$companyId || !is_numeric($companyId)) {
                            $errors[] = ['index' => $index, 'error' => 'company_id ist erforderlich und muss numerisch sein.'];
                            continue;
                        }

                        $company = CrmCompany::find((int) $companyId);
                        if (!$company) {
                            $errors[] = ['index' => $index, 'company_id' => $companyId, 'error' => 'Company nicht gefunden.'];
                            continue;
                        }

                        try {
                            Gate::forUser($context->user)->authorize('update', $company);
                        } catch (AuthorizationException $e) {
                            $errors[] = ['index' => $index, 'company_id' => $companyId, 'error' => 'Keine Berechtigung zum Bearbeiten.'];
                            continue;
                        }

                        $updateData = [];
                        $fields = ['name', 'legal_name', 'trading_name', 'website', 'description', 'notes',
                                  'industry_id', 'legal_form_id', 'contact_status_id', 'country_id',
                                  'registration_number', 'tax_number', 'vat_number', 'is_active'];

                        foreach ($fields as $field) {
                            if (isset($item[$field])) {
                                $v = $item[$field];
                                if (in_array($field, ['industry_id', 'legal_form_id', 'contact_status_id', 'country_id'], true)) {
                                    if ($v === 0 || $v === '0') { $v = null; }
                                }
                                $updateData[$field] = $v;
                            }
                        }

                        // Owner
                        if (isset($item['owned_by_user_id'])) {
                            $owned = $item['owned_by_user_id'];
                            if ($owned === 0 || $owned === 1 || $owned === '0' || $owned === '1') { $owned = null; }
                            if ($owned !== null) {
                                $updateData['owned_by_user_id'] = $owned;
                            }
                        }

                        if (!empty($updateData)) {
                            $company->update($updateData);
                            $company->refresh();
                        }

                        $updated[] = [
                            'index' => $index,
                            'id' => $company->id,
                            'name' => $company->name,
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = ['index' => $index, 'company_id' => $item['company_id'] ?? null, 'error' => $e->getMessage()];
                    }
                }
            });

            return ToolResult::success([
                'updated_count' => count($updated),
                'error_count' => count($errors),
                'total_requested' => count($items),
                'updated' => $updated,
                'errors' => $errors ?: null,
                'message' => count($updated) . ' von ' . count($items) . ' Companies erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Aktualisieren von Companies: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'company', 'bulk', 'update'],
            'risk_level' => 'write',
        ];
    }
}
