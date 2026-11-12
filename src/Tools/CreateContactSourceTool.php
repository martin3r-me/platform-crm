<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContactSource;
use Illuminate\Support\Str;

/**
 * Tool zum Anlegen einer Kontakt-Quelle (Lookup "contact_sources").
 *
 * Datengetrieben (kein Hardcoding): fehlende Werte werden hierüber als Daten
 * angelegt, nicht per Seeder/Enum.
 */
class CreateContactSourceTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact_sources.POST';
    }

    public function getDescription(): string
    {
        return 'POST /crm/contact-sources - Legt eine neue Kontakt-Quelle (Lookup) an. Parameter: name (required). code (optional, sonst aus name abgeleitet, muss eindeutig sein). is_active (optional, Standard true). Prüfe vorher mit "crm.lookup.GET" (lookup=contact_sources), ob die Quelle schon existiert.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Required: Anzeigename der Quelle (z.B. "Empfehlung").',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Optional: eindeutiger technischer Code. Wird ohne Angabe aus dem Namen abgeleitet (z.B. "empfehlung").',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Ob die Quelle aktiv/auswählbar ist. Standard: true.',
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

            if (CrmContactSource::where('code', $code)->exists()) {
                return ToolResult::error('VALIDATION_ERROR', "Eine Kontakt-Quelle mit code '{$code}' existiert bereits. Nutze crm.contact_sources.PUT zum Ändern.");
            }

            $source = CrmContactSource::create([
                'name' => $name,
                'code' => $code,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            return ToolResult::success([
                'id' => $source->id,
                'uuid' => $source->uuid,
                'name' => $source->name,
                'code' => $source->code,
                'is_active' => $source->is_active,
                'message' => "Kontakt-Quelle '{$source->name}' (code: {$source->code}) erstellt.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Kontakt-Quelle: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'contact_source', 'create'],
            'risk_level' => 'write',
        ];
    }
}
