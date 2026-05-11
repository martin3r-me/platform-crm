<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class CreateNewsletterTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletters.POST';
    }

    public function getDescription(): string
    {
        return 'POST /newsletters - Newsletter erstellen. Erforderlich: name, subject. Optional: preheader, html_body, text_body, comms_channel_id, contact_list_id, scheduled_at.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'Name des Newsletters (intern).'],
                'subject' => ['type' => 'string', 'description' => 'E-Mail Betreffzeile.'],
                'preheader' => ['type' => 'string', 'description' => 'Vorschautext in der Inbox.'],
                'html_body' => ['type' => 'string', 'description' => 'HTML-Inhalt des Newsletters.'],
                'text_body' => ['type' => 'string', 'description' => 'Plaintext-Fallback.'],
                'comms_channel_id' => ['type' => 'integer', 'description' => 'E-Mail Kanal ID.'],
                'contact_list_id' => ['type' => 'integer', 'description' => 'Kontaktliste ID.'],
                'scheduled_at' => ['type' => 'string', 'description' => 'Geplanter Versandzeitpunkt (ISO 8601).'],
            ],
            'required' => ['name', 'subject'],
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
            $subject = trim($arguments['subject'] ?? '');
            if ($name === '' || $subject === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name und subject sind erforderlich.');
            }

            $newsletter = CommsNewsletter::create([
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
                'name' => $name,
                'subject' => $subject,
                'preheader' => $arguments['preheader'] ?? null,
                'html_body' => $arguments['html_body'] ?? null,
                'text_body' => $arguments['text_body'] ?? null,
                'comms_channel_id' => $arguments['comms_channel_id'] ?? null,
                'contact_list_id' => $arguments['contact_list_id'] ?? null,
                'scheduled_at' => $arguments['scheduled_at'] ?? null,
                'status' => 'draft',
            ]);

            return ToolResult::success([
                'message' => 'Newsletter erstellt.',
                'id' => $newsletter->id,
                'uuid' => $newsletter->uuid,
                'status' => 'draft',
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
            'tags' => ['crm', 'newsletter', 'create'],
            'risk_level' => 'write',
        ];
    }
}
