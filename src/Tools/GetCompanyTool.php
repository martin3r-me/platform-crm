<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmCompany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetCompanyTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.company.GET';
    }

    public function getDescription(): string
    {
        return 'GET /companies/{id} - Ruft eine einzelne CRM-Company inkl. E-Mails/Telefon/Adressen/Contact-Relations ab. REST-Parameter: company_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'company_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Company (ERFORDERLICH). Nutze "crm.companies.GET" um Companies zu finden.',
                ],
            ],
            'required' => ['company_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $arguments['company_id'] ?? null;
            if (!$id) {
                return ToolResult::error('VALIDATION_ERROR', 'company_id ist erforderlich.');
            }

            $company = CrmCompany::with([
                'industry',
                'legalForm',
                'contactStatus',
                'country',
                'createdByUser',
                'ownedByUser',
                'phoneNumbers.phoneType',
                'emailAddresses.emailType',
                'postalAddresses.country',
                'postalAddresses.state',
                'postalAddresses.addressType',
                'contactRelations.contact',
            ])->find($id);

            if (!$company) {
                return ToolResult::error('COMPANY_NOT_FOUND', 'Die angegebene Company wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $company);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diese Company (Policy).');
            }

            $emails = $company->emailAddresses->map(function ($e) {
                return [
                    'id' => $e->id,
                    'uuid' => $e->uuid,
                    'email_address' => $e->email_address,
                    'email_type' => $e->emailType?->name,
                    'email_type_id' => $e->email_type_id,
                    'is_primary' => (bool)$e->is_primary,
                    'is_active' => (bool)$e->is_active,
                    'is_verified' => (bool)$e->is_verified,
                ];
            })->values()->toArray();

            $phones = $company->phoneNumbers->map(function ($p) {
                return [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                    'raw_input' => $p->raw_input,
                    'international' => $p->international,
                    'national' => $p->national,
                    'country_code' => $p->country_code,
                    'phone_type' => $p->phoneType?->name,
                    'phone_type_id' => $p->phone_type_id,
                    'is_primary' => (bool)$p->is_primary,
                    'is_active' => (bool)$p->is_active,
                ];
            })->values()->toArray();

            $addresses = $company->postalAddresses->map(function ($a) {
                return [
                    'id' => $a->id,
                    'uuid' => $a->uuid,
                    'street' => $a->street,
                    'house_number' => $a->house_number,
                    'postal_code' => $a->postal_code,
                    'city' => $a->city,
                    'additional_info' => $a->additional_info,
                    'country' => $a->country?->name,
                    'country_id' => $a->country_id,
                    'state' => $a->state?->name,
                    'state_id' => $a->state_id,
                    'address_type' => $a->addressType?->name,
                    'address_type_id' => $a->address_type_id,
                    'is_primary' => (bool)$a->is_primary,
                    'is_active' => (bool)$a->is_active,
                ];
            })->values()->toArray();

            $contacts = $company->contactRelations->map(function ($rel) {
                return [
                    'relation_id' => $rel->id,
                    'contact_id' => $rel->contact_id,
                    'contact_name' => $rel->contact?->name,
                    'relation_type_id' => $rel->relation_type_id,
                    'position' => $rel->position,
                    'is_primary' => (bool)$rel->is_primary,
                    'start_date' => $rel->start_date?->toDateString(),
                    'end_date' => $rel->end_date?->toDateString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'id' => $company->id,
                'uuid' => $company->uuid,
                'team_id' => $company->team_id,
                'name' => $company->name,
                'legal_name' => $company->legal_name,
                'trading_name' => $company->trading_name,
                'registration_number' => $company->registration_number,
                'tax_number' => $company->tax_number,
                'vat_number' => $company->vat_number,
                'website' => $company->website,
                'description' => $company->description,
                'notes' => $company->notes,
                'industry' => $company->industry?->name,
                'industry_id' => $company->industry_id,
                'legal_form' => $company->legalForm?->name,
                'legal_form_id' => $company->legal_form_id,
                'contact_status' => $company->contactStatus?->name,
                'contact_status_id' => $company->contact_status_id,
                'country' => $company->country?->name,
                'country_id' => $company->country_id,
                'is_active' => (bool)$company->is_active,
                'created_by_user_id' => $company->created_by_user_id,
                'owned_by_user_id' => $company->owned_by_user_id,
                'emails' => $emails,
                'phones' => $phones,
                'postal_addresses' => $addresses,
                'contacts' => $contacts,
                'created_at' => $company->created_at?->toIso8601String(),
                'updated_at' => $company->updated_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Company: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['crm', 'company', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


