<?php

namespace Platform\Crm\Listeners;

use Illuminate\Support\Facades\Log;
use Platform\Core\Services\EntityLinkService;
use Platform\Crm\Events\CommsInboundReceived;
use Platform\Crm\Events\CommsWhatsAppInboundReceived;
use Platform\Crm\Jobs\EnrichCrmContactJob;
use Platform\Crm\Models\CommsContactEnrichmentLog;
use Platform\Crm\Models\CommsThreadContext;
use Platform\Crm\Models\CrmContact;

class EnrichContactOnInbound
{
    public function handleEmail(CommsInboundReceived $event): void
    {
        try {
            $thread = $event->thread;

            $contact = $this->resolveContact($thread->contact_type, $thread->contact_id);
            if (!$contact) {
                return;
            }

            $this->linkContactToContextEntities($contact, get_class($thread), $thread->id);

            if (!$this->needsEnrichment($contact)) {
                return;
            }

            EnrichCrmContactJob::dispatch(
                $contact->id,
                'email',
                $thread->id,
                'email_inbound',
            );
        } catch (\Throwable $e) {
            Log::debug('[EnrichContactOnInbound] Email-Listener Fehler', ['error' => $e->getMessage()]);
        }
    }

    public function handleWhatsApp(CommsWhatsAppInboundReceived $event): void
    {
        try {
            $thread = $event->thread;

            $contact = $this->resolveContact($thread->contact_type, $thread->contact_id);
            if (!$contact) {
                return;
            }

            $this->linkContactToContextEntities($contact, get_class($thread), $thread->id);

            if (!$this->needsEnrichment($contact)) {
                return;
            }

            EnrichCrmContactJob::dispatch(
                $contact->id,
                'whatsapp',
                $thread->id,
                'whatsapp_inbound',
            );
        } catch (\Throwable $e) {
            Log::debug('[EnrichContactOnInbound] WhatsApp-Listener Fehler', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Verknüpft den CRM-Kontakt mit allen Kontext-Entitäten des Threads (z.B. Tickets).
     */
    private function linkContactToContextEntities(CrmContact $contact, string $threadType, int $threadId): void
    {
        try {
            $contexts = CommsThreadContext::where('thread_type', $threadType)
                ->where('thread_id', $threadId)
                ->get();

            if ($contexts->isEmpty()) {
                return;
            }

            $linkService = app(EntityLinkService::class);

            foreach ($contexts as $ctx) {
                $linkService->link(
                    teamId: $contact->team_id,
                    sourceType: $ctx->context_model,
                    sourceId: $ctx->context_model_id,
                    targetType: CrmContact::class,
                    targetId: $contact->id,
                    linkType: 'crm_contact',
                );
            }
        } catch (\Throwable $e) {
            Log::debug('[EnrichContactOnInbound] Entity-Link Fehler', ['error' => $e->getMessage()]);
        }
    }

    private function resolveContact(?string $contactType, ?int $contactId): ?CrmContact
    {
        if (!$contactId || !$contactType) {
            return null;
        }

        if (!is_a($contactType, CrmContact::class, true)) {
            return null;
        }

        return CrmContact::find($contactId);
    }

    private function needsEnrichment(CrmContact $contact): bool
    {
        if ($contact->first_name === 'Unbekannt') {
            return true;
        }

        $hasRecentEnrichment = CommsContactEnrichmentLog::where('crm_contact_id', $contact->id)
            ->where('type', 'run_completed')
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        return !$hasRecentEnrichment;
    }
}
