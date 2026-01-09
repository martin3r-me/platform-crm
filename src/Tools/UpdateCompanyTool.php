<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmCompany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Tool zum Bearbeiten von Companies im CRM-Modul
 */
class UpdateCompanyTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'crm.companies.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /companies/{id} - Aktualisiert eine bestehende Company. REST-Parameter: id (required, integer) - Company-ID. name (optional, string) - Name. description (optional, string) - Beschreibung. is_active (optional, boolean) - Status.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'company_id' => [
                    'type' => 'integer',
                    'description' => 'ID der zu bearbeitenden Company (ERFORDERLICH). Nutze "crm.companies.GET" um Companies zu finden.'
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name der Company.'
                ],
                'legal_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer rechtlicher Name.'
                ],
                'trading_name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Handelsname.'
                ],
                'website' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Website-URL.'
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.'
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Notizen.'
                ],
                'industry_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Branchen-ID.'
                ],
                'legal_form_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Rechtsform-ID.'
                ],
                'contact_status_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neuer Kontaktstatus-ID.'
                ],
                'country_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Länder-ID.'
                ],
                'registration_number' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Handelsregisternummer.'
                ],
                'tax_number' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Steuernummer.'
                ],
                'vat_number' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Umsatzsteuer-ID.'
                ],
                'owned_by_user_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Owner-User-ID.'
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Aktiv/Inaktiv Status.'
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

            // Policy: update
            try {
                Gate::forUser($context->user)->authorize('update', $company);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du darfst diese Company nicht bearbeiten (Policy).');
            }

            // Update-Daten sammeln
            $updateData = [];

            $fields = ['name', 'legal_name', 'trading_name', 'website', 'description', 'notes',
                      'industry_id', 'legal_form_id', 'contact_status_id', 'country_id',
                      'registration_number', 'tax_number', 'vat_number', 'is_active'];

            foreach ($fields as $field) {
                if (isset($arguments[$field])) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (isset($arguments['owned_by_user_id'])) {
                $updateData['owned_by_user_id'] = $arguments['owned_by_user_id'];
            }

            // Company aktualisieren
            if (!empty($updateData)) {
                $company->update($updateData);
            }

            // Aktualisierte Company laden
            $company->refresh();
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
                'owned_by' => $company->ownedByUser?->name,
                'updated_at' => $company->updated_at->toIso8601String(),
                'message' => "Company '{$company->name}' erfolgreich aktualisiert."
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Company: ' . $e->getMessage());
        }
    }
}

