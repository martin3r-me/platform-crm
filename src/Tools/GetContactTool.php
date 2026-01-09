<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContact;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;

class GetContactTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact.GET';
    }

    public function getDescription(): string
    {
        return 'GET /contacts/{id} - Ruft einen einzelnen CRM-Contact inkl. E-Mails/Telefon/Adressen/Company-Relations ab. REST-Parameter: contact_id (required, integer).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'contact_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Contacts (ERFORDERLICH). Nutze "crm.contacts.GET" um Contacts zu finden.',
                ],
            ],
            'required' => ['contact_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $arguments['contact_id'] ?? null;
            if (!$id) {
                return ToolResult::error('VALIDATION_ERROR', 'contact_id ist erforderlich.');
            }

            $contact = CrmContact::with([
                'salutation',
                'academicTitle',
                'gender',
                'language',
                'contactStatus',
                'createdByUser',
                'ownedByUser',
                'phoneNumbers.phoneType',
                'emailAddresses.emailType',
                'postalAddresses.country',
                'postalAddresses.state',
                'postalAddresses.addressType',
                'contactRelations.company',
            ])->find($id);

            if (!$contact) {
                return ToolResult::error('CONTACT_NOT_FOUND', 'Der angegebene Contact wurde nicht gefunden.');
            }

            try {
                Gate::forUser($context->user)->authorize('view', $contact);
            } catch (AuthorizationException $e) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Contact (Policy).');
            }

            $emails = $contact->emailAddresses->map(function ($e) {
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

            $phones = $contact->phoneNumbers->map(function ($p) {
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

            $addresses = $contact->postalAddresses->map(function ($a) {
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

            $companies = $contact->contactRelations->map(function ($rel) {
                return [
                    'relation_id' => $rel->id,
                    'company_id' => $rel->company_id,
                    'company_name' => $rel->company?->name,
                    'relation_type_id' => $rel->relation_type_id,
                    'position' => $rel->position,
                    'is_primary' => (bool)$rel->is_primary,
                    'start_date' => $rel->start_date?->toDateString(),
                    'end_date' => $rel->end_date?->toDateString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'id' => $contact->id,
                'uuid' => $contact->uuid,
                'team_id' => $contact->team_id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'middle_name' => $contact->middle_name,
                'nickname' => $contact->nickname,
                'full_name' => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
                'birth_date' => $contact->birth_date?->toDateString(),
                'notes' => $contact->notes,
                'contact_status' => $contact->contactStatus?->name,
                'contact_status_id' => $contact->contact_status_id,
                'is_active' => (bool)$contact->is_active,
                'created_by_user_id' => $contact->created_by_user_id,
                'owned_by_user_id' => $contact->owned_by_user_id,
                'emails' => $emails,
                'phones' => $phones,
                'postal_addresses' => $addresses,
                'companies' => $companies,
                'created_at' => $contact->created_at?->toIso8601String(),
                'updated_at' => $contact->updated_at?->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Contacts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['crm', 'contact', 'get'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


