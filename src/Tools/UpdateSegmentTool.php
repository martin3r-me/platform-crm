<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmSegment;

/**
 * Tool zum Aktualisieren eines Segments (Lookup "segments").
 */
class UpdateSegmentTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.segments.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /crm/segments/{id} - Aktualisiert ein Segment (Lookup). Parameter: id (required). name, code, is_active (optional). Nutze "crm.lookup.GET" (lookup=segments) für die ID. is_active=false deaktiviert ein Segment, ohne es zu löschen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des Segments.',
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

            $segment = CrmSegment::find((int) $id);
            if (!$segment) {
                return ToolResult::error('NOT_FOUND', 'Segment nicht gefunden. Nutze "crm.lookup.GET" (lookup=segments).');
            }

            $updated = [];

            if (array_key_exists('name', $arguments)) {
                $name = trim((string) $arguments['name']);
                if ($name === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'name darf nicht leer sein.');
                }
                $segment->name = $name;
                $updated[] = 'name';
            }

            if (array_key_exists('code', $arguments)) {
                $code = trim((string) $arguments['code']);
                if ($code === '') {
                    return ToolResult::error('VALIDATION_ERROR', 'code darf nicht leer sein.');
                }
                if (CrmSegment::where('code', $code)->where('id', '!=', $segment->id)->exists()) {
                    return ToolResult::error('VALIDATION_ERROR', "Ein anderes Segment mit code '{$code}' existiert bereits.");
                }
                $segment->code = $code;
                $updated[] = 'code';
            }

            if (array_key_exists('is_active', $arguments)) {
                $segment->is_active = (bool) $arguments['is_active'];
                $updated[] = 'is_active';
            }

            if (empty($updated)) {
                return ToolResult::error('VALIDATION_ERROR', 'Keine Änderungen angegeben.');
            }

            $segment->save();

            return ToolResult::success([
                'id' => $segment->id,
                'uuid' => $segment->uuid,
                'name' => $segment->name,
                'code' => $segment->code,
                'is_active' => $segment->is_active,
                'updated_fields' => $updated,
                'message' => "Segment '{$segment->name}' aktualisiert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Segments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'segment', 'update'],
            'risk_level' => 'write',
        ];
    }
}
