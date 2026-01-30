<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

/**
 * Übersicht über verfügbare Lookup-Tabellen im CRM.
 *
 * Zweck: Agenten sollen Lookup-IDs NICHT raten, sondern deterministisch nachschlagen.
 */
class CrmLookupsTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.lookups.GET';
    }

    public function getDescription(): string
    {
        return 'GET /crm/lookups - Listet alle CRM-Lookup-Typen (Codes/IDs) auf, damit der Agent keine IDs raten muss. Nutze danach "crm.lookup.GET" mit lookup=<typ> um Einträge zu suchen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        return ToolResult::success([
            'lookups' => [
                [
                    'key' => 'academic_titles',
                    'description' => 'Akademische Titel (z.B. Dr., Prof.)',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'salutations',
                    'description' => 'Anreden (z.B. Herr, Frau)',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'genders',
                    'description' => 'Geschlechter',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'languages',
                    'description' => 'Sprachen',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'contact_statuses',
                    'description' => 'Kontakt-Status',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'industries',
                    'description' => 'Branchen (Companies)',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'legal_forms',
                    'description' => 'Rechtsformen (Companies)',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'countries',
                    'description' => 'Länder (ISO2 code)',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'states',
                    'description' => 'Bundesländer/States (code + country filter möglich)',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'address_types',
                    'description' => 'Adresstypen (z.B. BUSINESS, HEADQUARTERS)',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'email_types',
                    'description' => 'E-Mail-Typen',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'phone_types',
                    'description' => 'Telefon-Typen',
                    'tool' => 'crm.lookup.GET',
                ],
                [
                    'key' => 'contact_relation_types',
                    'description' => 'Beziehungstypen Contact↔Company',
                    'tool' => 'crm.lookup.GET',
                ],
            ],
            'how_to' => [
                'step_1' => 'Nutze crm.lookups.GET um den passenden lookup-key zu finden.',
                'step_2' => 'Nutze crm.lookup.GET mit lookup=<key> und search=<text> oder code=<code>.',
                'step_3' => 'Verwende die gefundene id in Write-Tools. Niemals raten.',
            ],
        ]);
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['crm', 'lookup', 'help', 'overview'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


