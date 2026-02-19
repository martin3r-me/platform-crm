<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmCompany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-Tool zum Löschen mehrerer Companies im CRM-Modul
 */
class BulkDeleteCompaniesTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.companies.bulk.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /companies/bulk - Löscht mehrere Companies gleichzeitig. Maximal 50 Companies pro Aufruf. Beim Löschen werden auch alle zugehörigen Kontakte und Beziehungen gelöscht.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'ids' => [
                    'type' => 'array',
                    'description' => 'Array von Company-IDs zum Löschen (max. 50). Nutze "crm.companies.GET" um Companies zu finden.',
                    'items' => [
                        'type' => 'integer',
                        'description' => 'Company-ID'
                    ]
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Bestätigung für das Bulk-Löschen (ERFORDERLICH). Frage den Nutzer explizit nach Bestätigung.',
                    'default' => false
                ]
            ],
            'required' => ['ids', 'confirm']
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $ids = $arguments['ids'] ?? [];
            if (empty($ids)) {
                return ToolResult::error('VALIDATION_ERROR', 'ids-Array darf nicht leer sein.');
            }
            if (count($ids) > 50) {
                return ToolResult::error('VALIDATION_ERROR', 'Maximal 50 Companies pro Bulk-Aufruf erlaubt.');
            }

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bulk-Löschung erfordert confirm: true. ' . count($ids) . ' Company/Companies würden gelöscht.');
            }

            $deleted = [];
            $errors = [];

            DB::transaction(function () use ($ids, $context, &$deleted, &$errors) {
                foreach ($ids as $index => $id) {
                    try {
                        if (!$id || !is_numeric($id)) {
                            $errors[] = ['index' => $index, 'id' => $id, 'error' => 'Ungültige Company-ID.'];
                            continue;
                        }

                        $company = CrmCompany::find((int) $id);
                        if (!$company) {
                            $errors[] = ['index' => $index, 'id' => $id, 'error' => 'Company nicht gefunden.'];
                            continue;
                        }

                        try {
                            Gate::forUser($context->user)->authorize('delete', $company);
                        } catch (AuthorizationException $e) {
                            $errors[] = ['index' => $index, 'id' => $id, 'error' => 'Keine Berechtigung zum Löschen.'];
                            continue;
                        }

                        $companyName = $company->name;
                        $contactsCount = $company->contactRelations()->count();
                        $company->delete();

                        $deleted[] = [
                            'id' => (int) $id,
                            'name' => $companyName,
                            'deleted_contacts_count' => $contactsCount,
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = ['index' => $index, 'id' => $id, 'error' => $e->getMessage()];
                    }
                }
            });

            return ToolResult::success([
                'deleted_count' => count($deleted),
                'error_count' => count($errors),
                'total_requested' => count($ids),
                'deleted' => $deleted,
                'errors' => $errors ?: null,
                'message' => count($deleted) . ' von ' . count($ids) . ' Companies erfolgreich gelöscht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Löschen von Companies: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'company', 'bulk', 'delete'],
            'risk_level' => 'destructive',
        ];
    }
}
