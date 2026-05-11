<?php

namespace Platform\Crm\Services\Comms;

use Illuminate\Support\Facades\URL;
use Platform\Crm\Jobs\SendNewsletterChunkJob;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Models\CommsNewsletterRecipient;
use Platform\Crm\Models\CommsUnsubscribe;
use Platform\Crm\Models\CrmContact;

class NewsletterService
{
    /**
     * Prepare recipients from the newsletter's contact list.
     * Filters out blacklisted, inactive, and unsubscribed contacts.
     */
    public function prepareRecipients(CommsNewsletter $newsletter): int
    {
        $newsletter->loadMissing('contactList.members.contact.emailAddresses');

        if (!$newsletter->contactList) {
            throw new \RuntimeException('Newsletter has no contact list assigned.');
        }

        // Gather unsubscribed emails for this team
        $unsubscribed = CommsUnsubscribe::forTeam($newsletter->team_id)
            ->pluck('email_address')
            ->map(fn ($e) => strtolower($e))
            ->toArray();

        $created = 0;

        foreach ($newsletter->contactList->members as $member) {
            $contact = $member->contact;
            if (!$contact) {
                continue;
            }

            // Skip blacklisted or inactive contacts
            if ($contact->is_blacklisted ?? false) {
                continue;
            }
            if (method_exists($contact, 'scopeActive') && ($contact->contact_status_code === 'INACTIVE' || $contact->contact_status_code === 'inactive')) {
                continue;
            }

            // Get primary email address
            $email = $this->getPrimaryEmail($contact);
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Skip unsubscribed
            if (in_array(strtolower($email), $unsubscribed, true)) {
                continue;
            }

            // Skip if already exists for this newsletter
            $exists = CommsNewsletterRecipient::where('newsletter_id', $newsletter->id)
                ->where('email_address', $email)
                ->exists();
            if ($exists) {
                continue;
            }

            CommsNewsletterRecipient::create([
                'newsletter_id' => $newsletter->id,
                'contact_id' => $contact->id,
                'email_address' => $email,
                'status' => 'pending',
            ]);

            $created++;
        }

        $newsletter->updateStats();

        return $created;
    }

    /**
     * Start sending the newsletter by dispatching chunk jobs.
     */
    public function send(CommsNewsletter $newsletter): void
    {
        if (!in_array($newsletter->status, ['draft', 'scheduled'])) {
            throw new \RuntimeException("Cannot send newsletter with status '{$newsletter->status}'.");
        }

        if (!$newsletter->comms_channel_id) {
            throw new \RuntimeException('Newsletter has no channel assigned.');
        }

        if (!$newsletter->subject) {
            throw new \RuntimeException('Newsletter has no subject.');
        }

        if (!$newsletter->html_body) {
            throw new \RuntimeException('Newsletter has no HTML body.');
        }

        // Prepare recipients if none exist yet
        if ($newsletter->recipients()->count() === 0) {
            $this->prepareRecipients($newsletter);
        }

        $pendingCount = $newsletter->recipients()->where('status', 'pending')->count();
        if ($pendingCount === 0) {
            throw new \RuntimeException('No pending recipients to send to.');
        }

        $newsletter->update(['status' => 'sending']);

        // Dispatch in 50-recipient chunks
        $newsletter->recipients()
            ->where('status', 'pending')
            ->select('id')
            ->chunk(50, function ($chunk) use ($newsletter) {
                SendNewsletterChunkJob::dispatch(
                    $newsletter->id,
                    $chunk->pluck('id')->toArray()
                );
            });
    }

    /**
     * Generate a signed unsubscribe URL.
     */
    public function generateUnsubscribeUrl(int $teamId, string $email): string
    {
        return URL::signedRoute('crm.newsletter.unsubscribe', [
            'team' => $teamId,
            'email' => $email,
        ]);
    }

    /**
     * Handle an unsubscribe request.
     */
    public function handleUnsubscribe(int $teamId, string $email, ?string $reason = null): void
    {
        $email = strtolower(trim($email));

        CommsUnsubscribe::firstOrCreate(
            [
                'team_id' => $teamId,
                'email_address' => $email,
            ],
            [
                'reason' => $reason ?? 'user_unsubscribed',
                'unsubscribed_at' => now(),
            ]
        );

        // Also update any pending newsletter recipients
        CommsNewsletterRecipient::query()
            ->where('email_address', $email)
            ->where('status', 'pending')
            ->whereHas('newsletter', fn ($q) => $q->where('team_id', $teamId))
            ->update(['status' => 'unsubscribed']);
    }

    /**
     * Handle a Postmark webhook event for newsletter tracking.
     */
    public function handleWebhookEvent(string $event, array $metadata): void
    {
        $recipientId = (int) ($metadata['recipient_id'] ?? 0);
        $newsletterId = (int) ($metadata['newsletter_id'] ?? 0);

        if (!$recipientId || !$newsletterId) {
            return;
        }

        $recipient = CommsNewsletterRecipient::where('id', $recipientId)
            ->where('newsletter_id', $newsletterId)
            ->first();

        if (!$recipient) {
            return;
        }

        switch ($event) {
            case 'Delivery':
                $recipient->update(['status' => 'delivered']);
                break;

            case 'Open':
                if (!$recipient->opened_at) {
                    $recipient->update(['opened_at' => now(), 'status' => 'opened']);
                }
                break;

            case 'Click':
                if (!$recipient->clicked_at) {
                    $recipient->update(['clicked_at' => now(), 'status' => 'clicked']);
                }
                break;

            case 'Bounce':
            case 'HardBounce':
            case 'SoftBounce':
                $recipient->update([
                    'status' => 'bounced',
                    'bounced_at' => now(),
                ]);
                break;

            case 'SpamComplaint':
                $recipient->update(['status' => 'bounced', 'bounced_at' => now()]);
                // Auto-unsubscribe on spam complaint
                $newsletter = $recipient->newsletter;
                if ($newsletter) {
                    $this->handleUnsubscribe($newsletter->team_id, $recipient->email_address, 'spam_complaint');
                }
                break;
        }

        // Update newsletter aggregate stats
        $newsletter = CommsNewsletter::find($newsletterId);
        if ($newsletter) {
            $newsletter->updateStats();
        }
    }

    /**
     * Get the primary email address for a contact.
     */
    private function getPrimaryEmail(CrmContact $contact): ?string
    {
        $primary = $contact->emailAddresses()
            ->where('is_primary', true)
            ->first();

        if ($primary) {
            return $primary->email;
        }

        // Fallback to first email
        $first = $contact->emailAddresses()->first();
        return $first?->email;
    }
}
