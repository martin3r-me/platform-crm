<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmSegment;

/**
 * Tool zum Löschen eines Segments (Lookup "segments").
 *
 * Hinweis: Noch keine FK-Nutzung an Companies/Contacts – sobald Segmente an
 * Datensätzen hängen, hier analog zu CompanyType ein In-Use-Schutz ergänzen.
 */
class DeleteSegmentTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.segments.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /crm/segments/{id} - Löscht ein Segment (Lookup). Alternativ via crm.segments.PUT auf is_active=false deaktivieren. Nutze "crm.lookup.GET" (lookup=segments) für die ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID des zu löschenden Segments.',
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

            $name = $segment->name;
            $segment->delete();

            return ToolResult::success([
                'id' => (int) $id,
                'name' => $name,
                'message' => "Segment '{$name}' gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Segments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'segment', 'delete'],
            'risk_level' => 'write',
        ];
    }
}
