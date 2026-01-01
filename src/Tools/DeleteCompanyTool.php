<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmCompany;

/**
 * Tool zum Löschen von Companies im CRM-Modul
 */
class DeleteCompanyTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'crm.companies.DELETE';
    }

    public function getDescription(): string
    {
        return 'Löscht eine Company. RUF DIESES TOOL AUF, wenn der Nutzer eine Company löschen möchte. Die Company-ID ist erforderlich. Nutze "crm.companies.GET" um Companies zu finden. WICHTIG: Beim Löschen einer Company werden auch alle zugehörigen Kontakte und Beziehungen gelöscht. Frage den Nutzer nach Bestätigung, wenn die Company viele Kontakte hat.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'company_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu löschenden Company (ERFORDERLICH). Nutze "crm.companies.GET" um Companies zu finden.'
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Bestätigung, dass die Company wirklich gelöscht werden soll. Wenn die Company viele Kontakte hat, frage den Nutzer explizit nach Bestätigung.'
                ]
            ],
            'required' => ['company_id']
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            // Nutze standardisierte ID-Validierung
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'company_id',
                CrmCompany::class,
                'COMPANY_NOT_FOUND',
                'Die angegebene Company wurde nicht gefunden.'
            );
            
            if ($validation['error']) {
                return $validation['error'];
            }
            
            $company = $validation['model'];

            // Prüfe Zugriff (nur Owner kann löschen)
            $accessCheck = $this->checkAccess($company, $context, function($model, $ctx) {
                return $model->owned_by_user_id === $ctx->user->id;
            });
            
            if ($accessCheck) {
                return $accessCheck;
            }

            // Prüfe Anzahl der Kontakte (für Warnung)
            $contactsCount = $company->contactRelations()->count();

            // Bestätigung prüfen (wenn viele Kontakte vorhanden)
            if ($contactsCount > 5 && !($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', "Die Company hat {$contactsCount} Kontakt(e). Bitte bestätige die Löschung mit 'confirm: true'. Beim Löschen werden alle Kontakte und Beziehungen ebenfalls gelöscht.");
            }

            $companyName = $company->name;
            $companyId = $company->id;

            // Company löschen
            $company->delete();

            return ToolResult::success([
                'company_id' => $companyId,
                'company_name' => $companyName,
                'deleted_contacts_count' => $contactsCount,
                'message' => "Company '{$companyName}' und alle zugehörigen Kontakte wurden erfolgreich gelöscht."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Company: ' . $e->getMessage());
        }
    }
}

