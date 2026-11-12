<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContactSource;

/**
 * Tool zum Aktualisieren einer Kontakt-Quelle (Lookup "contact_sources").
 */
class UpdateContactSourceTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact_sources.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /crm/contact-sources/{id} - Aktualisiert eine Kontakt-Quelle (Lookup). Parameter: id (required). name, code, is_active (optional). Nutze "crm.lookup.GET" (lookup=contact_sources) für die ID. is_active=false deaktiviert eine Quelle, ohne sie zu löschen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID der Kontakt-Quelle.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: neuer Anzeigename.',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: neuer eindeutiger Code.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: aktiv/inaktiv setzen.',
                ],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $id = $arguments['id'] ?? null;
            if (!is_numeric($id)) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $source = CrmContactSource::find((int) $id);
            if (!$source) {
                return ToolResult::error('NOT_FOUND', 'Kontakt-Quelle nicht gefunden. Nutze "crm.lookup.GET" (lookup=contact_sources).');
            }

            $updated = [];

            if (array_key_exists('name', $arguments)) {
                $name = trim((string) $arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                $source->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('code', $arguments)) {
                $code = trim((string) $arguments['code']);
                if ($code === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'code darf nicht leer sein.');
                }
                if (CrmContactSource::where('code', $code)->where('id', '!=', $source->id)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', "Eine andere Kontakt-Quelle mit code '{$code}' existiert bereits.");
                }
                $source->code = $code;
                $updated[] = 'code';
            }

            if (array_key_exists('is_active', $arguments)) {
                $source->is_active = (bool) $arguments['is_active'];
                $updated[] = 'is_active';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            $source->save();

            return ToolResult::success([
                'id' => $source->id,
                'uuid' => $source->uuid,
                'name' => $source->name,
                'code' => $source->code,
                'is_active' => $source->is_active,
                'updated_fields' => $updated,
                'message' => "Kontakt-Quelle '{$source->name}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Kontakt-Quelle: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'contact_source', 'update'],
            'risk_level' => 'write',
        ];
    }
}
