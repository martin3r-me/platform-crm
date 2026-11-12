<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmSegment;
use Illuminate\Support\Str;

/**
 * Tool zum Anlegen eines Segments (Lookup "segments").
 *
 * Segmente bilden die mandantenspezifische Segmentierung/Verticals ab. Die Liste
 * ist datengetrieben (kein Hardcoding): fehlende Werte werden hierüber als Daten
 * angelegt, nicht per Seeder/Enum.
 */
class CreateSegmentTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.segments.POST';
    }

    public function getDescription(): string
    {
        return 'POST /crm/segments - Legt ein neues Segment (Lookup) an. Parameter: name (required). code (optional, sonst aus name abgeleitet, muss eindeutig sein). is_active (optional, Standard true). Prüfe vorher mit "crm.lookup.GET" (lookup=segments), ob das Segment schon existiert.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Required: Anzeigename des Segments (z.B. "Fine Dining").',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: eindeutiger technischer Code. Wird ohne Angabe aus dem Namen abgeleitet (z.B. "fine_dining").',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob das Segment aktiv/auswählbar ist. Standard: true.',
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

            $name = trim((string) ($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $code = trim((string) ($arguments['code'] ?? ''));
            if ($code === '') {
                $code = Str::slug($name, '_');
            }
            if ($code === '') {
                return ToolResult::error('VALIDATION_ERROR', 'code konnte nicht aus dem Namen abgeleitet werden – bitte code explizit angeben.');
            }

            if (CrmSegment::where('code', $code)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', "Ein Segment mit code '{$code}' existiert bereits. Nutze crm.segments.PUT zum Ändern.");
            }

            $segment = CrmSegment::create([
                'name' => $name,
                'code' => $code,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            return ToolResult::success([
                'id' => $segment->id,
                'uuid' => $segment->uuid,
                'name' => $segment->name,
                'code' => $segment->code,
                'is_active' => $segment->is_active,
                'message' => "Segment '{$segment->name}' (code: {$segment->code}) erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Segments: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'segment', 'create'],
            'risk_level' => 'write',
        ];
    }
}
