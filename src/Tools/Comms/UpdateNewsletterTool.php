<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class UpdateNewsletterTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletters.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /newsletters/{id} - Newsletter aktualisieren. Nur im Status draft/scheduled möglich.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Newsletter-ID.'],
                'name' => ['type' => 'string', 'description' => 'Name des Newsletters.'],
                'subject' => ['type' => 'string', 'description' => 'E-Mail Betreffzeile.'],
                'preheader' => ['type' => 'string', 'description' => 'Vorschautext.'],
                'html_body' => ['type' => 'string', 'description' => 'HTML-Inhalt.'],
                'text_body' => ['type' => 'string', 'description' => 'Plaintext-Fallback.'],
                'comms_channel_id' => ['type' => 'integer', 'description' => 'E-Mail Kanal ID.'],
                'contact_list_id' => ['type' => 'integer', 'description' => 'Kontaktliste ID.'],
                'scheduled_at' => ['type' => 'string', 'description' => 'Geplanter Versandzeitpunkt (ISO 8601).'],
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

            $newsletter = CommsNewsletter::find($id);
            if (!$newsletter) {
                return ToolResult::error('NOT_FOUND', 'Newsletter nicht gefunden.');
            }

            $teamId = $this->normalizeToRootTeamId(null, $context->user) ?? $context->team?->id;
            if ((int) $newsletter->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Kein Zugriff.');
            }

            if (!$newsletter->canEdit()) {
                return ToolResult::error('STATUS_ERROR', "Newsletter im Status '{$newsletter->status}' kann nicht bearbeitet werden.");
            }

            $fillable = ['name', 'subject', 'preheader', 'html_body', 'text_body', 'comms_channel_id', 'contact_list_id', 'scheduled_at'];
            $updates = [];
            foreach ($fillable as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updates[$field] = $arguments[$field];
                }
            }

            if (!empty($updates)) {
                $newsletter->update($updates);
            }

            return ToolResult::success([
                'message' => 'Newsletter aktualisiert.',
                'id' => $newsletter->id,
                'status' => $newsletter->status,
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
            'tags' => ['crm', 'newsletter', 'update'],
            'risk_level' => 'write',
        ];
    }
}
