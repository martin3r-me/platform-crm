<?php

namespace Platform\Crm\Tools\Comms;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Services\Comms\NewsletterService;
use Platform\Crm\Tools\Concerns\ResolvesCrmTeam;

class SendNewsletterTool implements ToolContract, ToolMetadataContract
{
    use ResolvesCrmTeam;

    public function getName(): string
    {
        return 'crm.newsletters.send.POST';
    }

    public function getDescription(): string
    {
        return 'POST /newsletters/{id}/send - Newsletter sofort senden. Erfordert: id. Newsletter muss status=draft oder scheduled haben, einen Channel und eine Kontaktliste.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Newsletter-ID.'],
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

            app(NewsletterService::class)->send($newsletter);

            return ToolResult::success([
                'message' => 'Newsletter-Versand gestartet.',
                'id' => $newsletter->id,
                'status' => 'sending',
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
            'tags' => ['crm', 'newsletter', 'send'],
            'risk_level' => 'write',
            'idempotent' => false,
        ];
    }
}
