<?php

namespace Platform\Crm\Tools;

use Illuminate\Database\Eloquent\Builder;
use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CrmAcademicTitle;
use Platform\Crm\Models\CrmAddressType;
use Platform\Crm\Models\CrmContactRelationType;
use Platform\Crm\Models\CrmContactStatus;
use Platform\Crm\Models\CrmCountry;
use Platform\Crm\Models\CrmEmailType;
use Platform\Crm\Models\CrmGender;
use Platform\Crm\Models\CrmIndustry;
use Platform\Crm\Models\CrmLegalForm;
use Platform\Crm\Models\CrmLanguage;
use Platform\Crm\Models\CrmPhoneType;
use Platform\Crm\Models\CrmSalutation;
use Platform\Crm\Models\CrmState;

/**
 * Generisches Lookup-GET für CRM, damit der Agent IDs nicht raten muss.
 *
 * Beispiel:
 * - crm.lookup.GET { "lookup": "address_types", "search": "Geschäft" }
 * - crm.lookup.GET { "lookup": "countries", "code": "DE" }
 */
class GetLookupTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;

    public function getName(): string
    {
        return 'crm.lookup.GET';
    }

    public function getDescription(): string
    {
        return 'GET /crm/lookup - Listet Einträge aus einer CRM-Lookup-Tabelle (id/name/code/is_active). Nutze crm.lookups.GET für verfügbare lookup keys. Unterstützt Suche/Filter/Sort/Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(['lookup', 'is_active', 'code', 'country_code', 'country_id']),
            [
                'properties' => [
                    'lookup' => [
                        'type' => 'string',
                        'description' => 'ERFORDERLICH. Lookup-Key. Nutze crm.lookups.GET um die Keys zu sehen.',
                        'enum' => [
                            'academic_titles',
                            'salutations',
                            'genders',
                            'languages',
                            'contact_statuses',
                            'industries',
                            'legal_forms',
                            'countries',
                            'states',
                            'address_types',
                            'email_types',
                            'phone_types',
                            'contact_relation_types',
                        ],
                    ],
                    'code' => [
                        'type' => 'string',
                        'description' => 'Optional: Exakter code-Filter (z.B. DE, BUSINESS, DR).',
                    ],
                    'country_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Nur für lookup=states. Filtert states nach country_id.',
                    ],
                    'country_code' => [
                        'type' => 'string',
                        'description' => 'Optional: Nur für lookup=states. Filtert states nach country_code (ISO2, z.B. DE).',
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Filtert nach is_active.',
                    ],
                ],
                'required' => ['lookup'],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $lookup = (string)($arguments['lookup'] ?? '');
            if ($lookup === '') {
                return ToolResult::error('VALIDATION_ERROR', 'lookup ist erforderlich. Nutze crm.lookups.GET.');
            }

            [$modelClass, $searchFields] = $this->resolveLookup($lookup);
            if ($modelClass === null) {
                return ToolResult::error('VALIDATION_ERROR', 'Unbekannter lookup. Nutze crm.lookups.GET.');
            }

            /** @var Builder $q */
            $q = $modelClass::query();

            // Common filters
            if (array_key_exists('is_active', $arguments)) {
                $q->where('is_active', (bool)$arguments['is_active']);
            }
            if (isset($arguments['code']) && $arguments['code'] !== '') {
                $q->where('code', strtoupper(trim((string)$arguments['code'])));
            }

            // Special: states can be filtered by country
            if ($lookup === 'states') {
                $countryId = $arguments['country_id'] ?? null;
                if ($countryId) {
                    $q->where('country_id', (int)$countryId);
                } else {
                    $cc = strtoupper(trim((string)($arguments['country_code'] ?? '')));
                    if ($cc !== '') {
                        $cid = CrmCountry::query()->where('code', $cc)->value('id');
                        if ($cid) {
                            $q->where('country_id', (int)$cid);
                        }
                    }
                }
            }

            // Standard ops
            $this->applyStandardFilters($q, $arguments, ['is_active', 'code']);
            $this->applyStandardSearch($q, $arguments, $searchFields);
            $this->applyStandardSort($q, $arguments, ['name', 'code', 'created_at'], 'name', 'asc');

            $paginationResult = $this->applyStandardPaginationResult($q, $arguments);
            $items = $paginationResult['data']->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name ?? null,
                    'code' => $m->code ?? null,
                    'is_active' => (bool)($m->is_active ?? true),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'lookup' => $lookup,
                'items' => $items,
                'pagination' => $paginationResult['pagination'],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Lookups: ' . $e->getMessage());
        }
    }

    private function resolveLookup(string $lookup): array
    {
        return match ($lookup) {
            'academic_titles' => [CrmAcademicTitle::class, ['name', 'code']],
            'salutations' => [CrmSalutation::class, ['name', 'code']],
            'genders' => [CrmGender::class, ['name', 'code']],
            'languages' => [CrmLanguage::class, ['name', 'code']],
            'contact_statuses' => [CrmContactStatus::class, ['name', 'code']],
            'industries' => [CrmIndustry::class, ['name', 'code']],
            'legal_forms' => [CrmLegalForm::class, ['name', 'code']],
            'countries' => [CrmCountry::class, ['name', 'code']],
            'states' => [CrmState::class, ['name', 'code']],
            'address_types' => [CrmAddressType::class, ['name', 'code']],
            'email_types' => [CrmEmailType::class, ['name', 'code']],
            'phone_types' => [CrmPhoneType::class, ['name', 'code']],
            'contact_relation_types' => [CrmContactRelationType::class, ['name', 'code']],
            default => [null, []],
        };
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'read',
            'tags' => ['crm', 'lookup', 'list'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => false,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}


