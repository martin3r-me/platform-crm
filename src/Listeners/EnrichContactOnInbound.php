<?php

namespace Platform\Crm\Listeners;

use Illuminate\Support\Facades\Log;
use Platform\Crm\Events\CommsInboundReceived;
use Platform\Crm\Events\CommsWhatsAppInboundReceived;
use Platform\Crm\Jobs\EnrichCrmContactJob;
use Platform\Crm\Models\CommsContactEnrichmentLog;
use Platform\Crm\Models\CrmContact;

class EnrichContactOnInbound
{
    public function handleEmail(CommsInboundReceived $event): void
    {
        try {
            $thread = $event->thread;

            $contact = $this->resolveContact($thread->contact_type, $thread->contact_id);
            if (!$contact || !$this->needsEnrichment($contact)) {
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
            if (!$contact || !$this->needsEnrichment($contact)) {
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

    private function resolveContact(?string $contactType, ?int $contactId): ?CrmContact
    {
        if (!$contactId || !$contactType) {
            return null;
        }

        // Accept both FQCN and morph alias
        if (!is_a($contactType, CrmContact::class, true)) {
            return null;
        }

        return CrmContact::find($contactId);
    }

    private function needsEnrichment(CrmContact $contact): bool
    {
        // Contact has placeholder name
        if ($contact->first_name === 'Unbekannt') {
            return true;
        }

        // No completed enrichment in the last 24h
        $hasRecentEnrichment = CommsContactEnrichmentLog::where('crm_contact_id', $contact->id)
            ->where('type', 'run_completed')
            ->where('created_at', '>=', now()->subDay())
            ->exists();

        return !$hasRecentEnrichment;
    }
}
