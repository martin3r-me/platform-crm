<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContactList;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class CreateContactListTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.contact_lists.POST';
    }

    public function getDescription(): string
    {
        return 'POST /contact-lists - Erstellt eine neue Kontaktliste. Required: name. Optional: description, color (#RRGGBB), owned_by_user_id, is_active.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Kontaktliste (ERFORDERLICH).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung der Liste.',
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Optional: Farbe als Hex-Code (#RRGGBB), z.B. "#FF5733".',
                ],
                'owned_by_user_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: User-ID des Besitzers.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Aktiv-Status (Standard: true).',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Wenn nicht angegeben, wird das aktuelle Team verwendet.',
                ],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $name = trim($arguments['name'] ?? '');
            if (empty($name)) {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            // Team resolve
            $teamIdArg = $arguments['team_id'] ?? null;
            if ($teamIdArg === 0 || $teamIdArg === '0') {
                $teamIdArg = null;
            }

            $teamId = $this->normalizeToRootTeamId(
                is_numeric($teamIdArg) ? (int)$teamIdArg : null,
                $context->user
            ) ?? $context->team?->id;

            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team angegeben und kein Team im Kontext gefunden.');
            }

            if (!$this->userHasAccessToCrmRootTeam($context->user, (int)$teamId)) {
                return ToolResult::error('ACCESS_DENIED', "Du hast keinen Zugriff auf Team-ID {$teamId}.");
            }

            // Color validation
            $color = $arguments['color'] ?? null;
            if ($color && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                return ToolResult::error('VALIDATION_ERROR', 'color muss ein gültiger Hex-Code sein (#RRGGBB), z.B. "#FF5733".');
            }

            $list = CrmContactList::create([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'color' => $color,
                'is_active' => $arguments['is_active'] ?? true,
                'owned_by_user_id' => $arguments['owned_by_user_id'] ?? null,
                'created_by_user_id' => $context->user->id,
                'team_id' => $teamId,
            ]);

            return ToolResult::success([
                'id' => $list->id,
                'uuid' => $list->uuid,
                'name' => $list->name,
                'description' => $list->description,
                'color' => $list->color,
                'is_active' => $list->is_active,
                'team_id' => $list->team_id,
                'message' => "Kontaktliste '{$list->name}' wurde erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Kontaktliste: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'write',
            'tags' => ['crm', 'contact_list', 'create'],
            'read_only' => false,
            'risk_level' => 'low',
        ];
    }
}
