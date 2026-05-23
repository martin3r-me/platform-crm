<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsNewsletterTemplate;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class GetNewsletterTemplateTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletter_templates.{id}.GET';
    }

    public function getDescription(): string
    {
        return 'GET /newsletter-templates/{id} - Zeigt Details einer Newsletter-Vorlage.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Template-ID.'],
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

            $template = CommsNewsletterTemplate::with('createdByUser')->find($id);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Vorlage nicht gefunden.');
            }

            $teamId = $this->normalizeToRootTeamId(null, $context->user) ?? $context->team?->id;
            if ((int) $template->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff auf diese Vorlage.');
            }

            return ToolResult::success([
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'description' => $template->description,
                'category' => $template->category,
                'is_active' => $template->is_active,
                'default_subject' => $template->default_subject,
                'default_preheader' => $template->default_preheader,
                'html_body' => $template->html_body,
                'text_body' => $template->text_body,
                'sort_order' => $template->sort_order,
                'created_by' => $template->createdByUser?->name,
                'created_at' => $template->created_at->toIso8601String(),
                'updated_at' => $template->updated_at->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['crm', 'newsletter', 'template', 'detail'],
            'risk_level' => 'read',
        ];
    }
}
