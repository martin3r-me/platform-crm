<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsNewsletterTemplate;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class CreateNewsletterTemplateTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletter_templates.POST';
    }

    public function getDescription(): string
    {
        return 'POST /newsletter-templates - Newsletter-Vorlage erstellen. Erforderlich: name. Optional: description, category, html_body, text_body, default_subject, default_preheader, is_active.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'Name der Vorlage.'],
                'description' => ['type' => 'string', 'description' => 'Beschreibung.'],
                'category' => ['type' => 'string', 'description' => 'Kategorie (z.B. Marketing, Update, Transaktional).'],
                'html_body' => ['type' => 'string', 'description' => 'HTML-Inhalt der Vorlage.'],
                'text_body' => ['type' => 'string', 'description' => 'Plaintext-Fallback.'],
                'default_subject' => ['type' => 'string', 'description' => 'Standard-Betreff für neue Newsletter.'],
                'default_preheader' => ['type' => 'string', 'description' => 'Standard-Preheader für neue Newsletter.'],
                'is_active' => ['type' => 'boolean', 'description' => 'Aktiv (default: true).'],
            ],
            'required' => ['name'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $teamId = $this->normalizeToRootTeamId(null, $context->user) ?? $context->team?->id;
            if (!$teamId) {
                return ToolResult::error('MISSING_TEAM', 'Kein Team im Kontext.');
            }

            $name = trim($arguments['name'] ?? '');
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $template = CommsNewsletterTemplate::create([
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'category' => $arguments['category'] ?? null,
                'html_body' => $arguments['html_body'] ?? null,
                'text_body' => $arguments['text_body'] ?? null,
                'default_subject' => $arguments['default_subject'] ?? null,
                'default_preheader' => $arguments['default_preheader'] ?? null,
                'is_active' => $arguments['is_active'] ?? true,
            ]);

            return ToolResult::success([
                'message' => 'Vorlage erstellt.',
                'id' => $template->id,
                'uuid' => $template->uuid,
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
            'tags' => ['crm', 'newsletter', 'template', 'create'],
            'risk_level' => 'write',
        ];
    }
}
