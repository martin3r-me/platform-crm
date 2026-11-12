<?php

namespace Platform\Crm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Crm\Models\CrmContactSource;

/**
 * Tool zum Löschen einer Kontakt-Quelle (Lookup "contact_sources").
 *
 * Hinweis: Noch keine FK-Nutzung an Contacts – sobald Quellen an Datensätzen
 * hängen, hier analog zu CompanyType einen In-Use-Schutz ergänzen.
 */
class DeleteContactSourceTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'crm.contact_sources.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /crm/contact-sources/{id} - Löscht eine Kontakt-Quelle (Lookup). Alternativ via crm.contact_sources.PUT auf is_active=false deaktivieren. Nutze "crm.lookup.GET" (lookup=contact_sources) für die ID.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Required: ID der zu löschenden Kontakt-Quelle.',
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

            $name = $source->name;
            $source->delete();

            return ToolResult::success([
                'id' => (int) $id,
                'name' => $name,
                'message' => "Kontakt-Quelle '{$name}' gelöscht.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen der Kontakt-Quelle: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'lookup', 'contact_source', 'delete'],
            'risk_level' => 'write',
        ];
    }
}
