<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsNewsletterTemplate;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class UpdateNewsletterTemplateTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletter_templates.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /newsletter-templates/{id} - Newsletter-Vorlage aktualisieren.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Template-ID.'],
                'name' => ['type' => 'string', 'description' => 'Name der Vorlage.'],
                'description' => ['type' => 'string', 'description' => 'Beschreibung.'],
                'category' => ['type' => 'string', 'description' => 'Kategorie.'],
                'html_body' => ['type' => 'string', 'description' => 'HTML-Inhalt.'],
                'text_body' => ['type' => 'string', 'description' => 'Plaintext-Fallback.'],
                'default_subject' => ['type' => 'string', 'description' => 'Standard-Betreff.'],
                'default_preheader' => ['type' => 'string', 'description' => 'Standard-Preheader.'],
                'is_active' => ['type' => 'boolean', 'description' => 'Aktiv/Inaktiv.'],
                'sort_order' => ['type' => 'integer', 'description' => 'Sortierung.'],
            ],
            'required' => ['id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $id = (int) ($arguments['id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'id ist erforderlich.');
            }

            $template = CommsNewsletterTemplate::find($id);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Vorlage nicht gefunden.');
            }

            $teamId = $this->normalizeToRootTeamId(null, $context->user) ?? $context->team?->id;
            if ((int) $template->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff.');
            }

            $fillable = ['name', 'description', 'category', 'html_body', 'text_body', 'default_subject', 'default_preheader', 'is_active', 'sort_order'];
            $updates = [];
            foreach ($fillable as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updates[$field] = $arguments[$field];
                }
            }

            if (!empty($updates)) {
                $template->update($updates);
            }

            return ToolResult::success([
                'message' => 'Vorlage aktualisiert.',
                'id' => $template->id,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['crm', 'newsletter', 'template', 'update'],
            'risk_level' => 'write',
        ];
    }
}
