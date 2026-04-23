<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Crm\Models\CrmContactList;

class UpdateContactListTool implements ToolContract
{
    use HasStandardizedWriteOperations;

    public function getName(): string
    {
        return 'crm.contact_list.PATCH';
    }

    public function getDescription(): string
    {
        return 'PATCH /contact-lists/{id} - Aktualisiert eine Kontaktliste. Required: contact_list_id. Alle anderen Felder optional.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'contact_list_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Kontaktliste (ERFORDERLICH).',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung. Leerer String zum Entfernen.',
                ],
                'color' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Farbe (#RRGGBB). Leerer String zum Entfernen.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Aktiv-Status.',
                ],
                'owned_by_user_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Besitzer-User-ID. 0 oder null zum Entfernen.',
                ],
            ],
            'required' => ['contact_list_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $validation = $this->validateAndFindModel(
                $arguments,
                $context,
                'contact_list_id',
                CrmContactList::class,
                'NOT_FOUND',
                'Die angegebene Kontaktliste wurde nicht gefunden.'
            );

            if ($validation['error']) {
                return $validation['error'];
            }

            $list = $validation['model'];

            $updatable = [];

            if (array_key_exists('name', $arguments)) {
                $name = trim($arguments['name']);
                if (empty($name)) {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                $updatable['name'] = $name;
            }

            if (array_key_exists('description', $arguments)) {
                $updatable['description'] = $arguments['description'] ?: null;
            }

            if (array_key_exists('color', $arguments)) {
                $color = $arguments['color'] ?: null;
                if ($color && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                    return ToolResult::error('VALIDATION_ERROR', 'color muss ein gültiger Hex-Code sein (#RRGGBB).');
                }
                $updatable['color'] = $color;
            }

            if (array_key_exists('is_active', $arguments)) {
                $updatable['is_active'] = (bool)$arguments['is_active'];
            }

            if (array_key_exists('owned_by_user_id', $arguments)) {
                $val = $arguments['owned_by_user_id'];
                $updatable['owned_by_user_id'] = ($val === 0 || $val === '0' || $val === '' || $val === null) ? null : (int)$val;
            }

            if (empty($updatable)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Felder zum Aktualisieren angegeben.');
            }

            $list->update($updatable);

            return ToolResult::success([
                'id' => $list->id,
                'uuid' => $list->uuid,
                'name' => $list->name,
                'description' => $list->description,
                'color' => $list->color,
                'is_active' => $list->is_active,
                'owned_by_user_id' => $list->owned_by_user_id,
                'member_count' => $list->member_count,
                'message' => "Kontaktliste '{$list->name}' wurde aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Kontaktliste: ' . $e->getMessage());
        }
    }
}
