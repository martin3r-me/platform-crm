<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;
use Illuminate\Support\Facades\Gate;

/**
 * Tool zum Auflisten von Contacts im CRM-Modul
 */
class ListContactsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.contacts.GET';
    }

    public function getDescription(): string
    {
        return 'GET /contacts?team_id={id}&filters=[...]&search=...&sort=[...] - Listet Contacts auf. REST-Parameter: team_id (optional, integer) - Filter nach Team-ID. Wenn nicht angegeben, wird automatisch das aktuelle Team aus dem Kontext verwendet. filters (optional, array) - Filter-Array mit field, op, value. search (optional, string) - Suchbegriff. sort (optional, array) - Sortierung mit field, dir. limit/offset (optional) - Pagination.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'REST-Parameter (optional): Filter nach Team-ID. Wenn nicht angegeben, wird automatisch das aktuelle Team aus dem Kontext verwendet.'
                    ],
                    'is_active' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Filter nach aktiv/inaktiv Status.'
                    ],
                    'name_search' => [
                        'type' => 'string',
                        'description' => 'Optional: Suche nach Contact-Namen (Legacy - nutze stattdessen search Parameter).'
                    ],
                    'company_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Company-ID. Zeigt nur Contacts, die mit dieser Company verknüpft sind.'
                    ]
                ]
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            // Team-Filter bestimmen
            // WICHTIG: Behandle 0 als "nicht gesetzt" (OpenAI sendet manchmal 0 statt null)
            $teamIdArg = $arguments['team_id'] ?? null;
            if ($teamIdArg === 0 || $teamIdArg === '0') {
                $teamIdArg = null;
            }
            
            // CRM ist root-scoped: egal ob Root- oder Child-Team übergeben wird → immer Root-Team nutzen
            $teamIdArg = $this->normalizeToRootTeamId(
                is_numeric($teamIdArg) ? (int)$teamIdArg : null,
                $context->user
            ) ?? $context->team?->id;
            
            if (!$teamIdArg) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden. Nutze "core.teams.GET" um verfügbare Teams zu sehen, oder gib team_id explizit an.');
            }
            
            // Zugriff: Für CRM reicht es, wenn der User im Root-Team ODER in einem Kind-Team ist.
            $userHasAccess = $this->userHasAccessToCrmRootTeam($context->user, (int)$teamIdArg);
            if (!$userHasAccess) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamIdArg}. Nutze 'core.teams.GET' um verfügbare Teams zu sehen.");
            }
            
            // Query aufbauen - nur Contacts dieses Teams (CRM-UI arbeitet root-scoped)
            $query = CrmContact::query()
                ->where('team_id', $teamIdArg)
                ->with(['salutation', 'academicTitle', 'gender', 'language', 'contactStatus', 'createdByUser', 'ownedByUser']);

            // Standard-Operationen anwenden
            $this->applyStandardFilters($query, $arguments, [
                'first_name', 'last_name', 'nickname', 'notes', 'is_active', 'created_at', 'updated_at'
            ]);

            // Legacy: name_search
            if (!empty($arguments['name_search'])) {
                $query->where(function ($q) use ($arguments) {
                    $q->where('first_name', 'like', '%' . $arguments['name_search'] . '%')
                      ->orWhere('last_name', 'like', '%' . $arguments['name_search'] . '%')
                      ->orWhere('nickname', 'like', '%' . $arguments['name_search'] . '%');
                });
            }

            // is_active Filter
            if (isset($arguments['is_active'])) {
                $query->where('is_active', $arguments['is_active']);
            }

            // company_id Filter
            if (!empty($arguments['company_id'])) {
                $query->whereHas('contactRelations', function ($q) use ($arguments) {
                    $q->where('company_id', $arguments['company_id']);
                });
            }

            // Standard-Sortierung und Pagination
            $this->applyStandardSorting($query, $arguments, 'last_name', 'asc');
            $result = $this->applyStandardPaginationResult($query, $arguments);

            // Formatierung (Safety: nie Contacts zurückgeben, die Policy-seitig nicht sichtbar sind)
            $contacts = $result['data']->filter(fn($c) => Gate::forUser($context->user)->allows('view', $c))
                ->values()
                ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'uuid' => $contact->uuid,
                    'first_name' => $contact->first_name,
                    'last_name' => $contact->last_name,
                    'middle_name' => $contact->middle_name,
                    'nickname' => $contact->nickname,
                    'full_name' => trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')),
                    'birth_date' => $contact->birth_date?->toDateString(),
                    'salutation' => $contact->salutation?->name,
                    'academic_title' => $contact->academicTitle?->name,
                    'gender' => $contact->gender?->name,
                    'language' => $contact->language?->name,
                    'contact_status' => $contact->contactStatus?->name,
                    'is_active' => $contact->is_active,
                    'created_by' => $contact->createdByUser?->name,
                    'owned_by' => $contact->ownedByUser?->name,
                    'created_at' => $contact->created_at->toIso8601String(),
                    'updated_at' => $contact->updated_at->toIso8601String(),
                ];
            })->toArray();

            return ToolResult::success([
                'contacts' => $contacts,
                'pagination' => $result['pagination'],
                'team_id' => $teamIdArg,
                'message' => count($contacts) . ' Contact(s) gefunden (Team-ID: ' . $teamIdArg . ').'
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auflisten der Contacts: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['crm', 'contact', 'list'],
            'risk_level' => 'read',
        ];
    }
}

