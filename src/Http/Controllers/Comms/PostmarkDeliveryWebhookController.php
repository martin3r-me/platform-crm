<?php

namespace Platform\Crm\Http\Controllers\Comms;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Platform\Crm\Models\CommsLog;
use Platform\Crm\Services\Comms\NewsletterService;

class PostmarkDeliveryWebhookController extends Controller
{
    public function __invoke(Request $request, NewsletterService $newsletterService)
    {
        $payload = $request->json()->all();

        try {
            $recordType = $payload['RecordType'] ?? null;
            $metadata = $payload['Metadata'] ?? [];
            $tag = $payload['Tag'] ?? null;

            // Only process newsletter-tagged events
            if ($tag !== 'newsletter' || empty($metadata['newsletter_id'])) {
                return response()->noContent();
            }

            $eventMap = [
                'Delivery' => 'Delivery',
                'Open' => 'Open',
                'Click' => 'Click',
                'Bounce' => $payload['Type'] ?? 'Bounce',
                'SpamComplaint' => 'SpamComplaint',
            ];

            $event = $eventMap[$recordType] ?? $recordType;

            if ($event) {
                $newsletterService->handleWebhookEvent($event, $metadata);
            }

            // Log the event
            try {
                CommsLog::log(
                    'newsletter.webhook.' . strtolower($recordType ?? 'unknown'),
                    'success',
                    "Newsletter delivery event: {$recordType}",
                    [
                        'record_type' => $recordType,
                        'newsletter_id' => $metadata['newsletter_id'] ?? null,
                        'recipient_id' => $metadata['recipient_id'] ?? null,
                        'recipient' => $payload['Recipient'] ?? $payload['Email'] ?? null,
                    ],
                    [
                        'team_id' => null,
                        'source' => 'postmark_webhook',
                    ]
                );
            } catch (\Throwable $e) {
                // Logging should never break the webhook
            }

            return response()->noContent();
        } catch (\Throwable $e) {
            Log::error('Postmark delivery webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->noContent();
        }
    }
}
