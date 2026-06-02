<?php

namespace Platform\Crm\Listeners;

use Platform\Core\Contracts\CrmEngagementManagerInterface;
use Platform\Crm\Events\NewsletterSent;

class CreateNewsletterEngagement
{
    public function __construct(
        protected CrmEngagementManagerInterface $engagementManager,
    ) {}

    public function handle(NewsletterSent $event): void
    {
        $newsletter = $event->newsletter;

        $contactIds = $newsletter->recipients()
            ->whereNotNull('contact_id')
            ->pluck('contact_id')
            ->unique()
            ->values()
            ->all();

        $this->engagementManager->createEngagement(
            data: [
                'type' => 'newsletter',
                'title' => "Newsletter: {$newsletter->subject}",
                'status' => 'completed',
                'completed_at' => now(),
                'team_id' => $newsletter->team_id,
                'metadata' => [
                    'newsletter_id' => $newsletter->id,
                    'newsletter_uuid' => $newsletter->uuid,
                    'recipient_count' => count($contactIds),
                ],
            ],
            contactIds: $contactIds,
        );
    }
}
