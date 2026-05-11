<?php

namespace Platform\Crm\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Models\CommsNewsletterRecipient;
use Platform\Crm\Services\Comms\NewsletterService;
use Platform\Crm\Services\Comms\PostmarkEmailService;

class SendNewsletterChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public int $newsletterId,
        public array $recipientIds,
    ) {
        $this->onQueue('newsletters');
    }

    public function handle(PostmarkEmailService $postmarkService, NewsletterService $newsletterService): void
    {
        $newsletter = CommsNewsletter::with('channel')->find($this->newsletterId);
        if (!$newsletter || !$newsletter->channel) {
            Log::error('[SendNewsletterChunkJob] Newsletter or channel not found', [
                'newsletter_id' => $this->newsletterId,
            ]);
            return;
        }

        if ($newsletter->status === 'cancelled') {
            return;
        }

        $channel = $newsletter->channel;

        foreach ($this->recipientIds as $recipientId) {
            $recipient = CommsNewsletterRecipient::find($recipientId);
            if (!$recipient || $recipient->status !== 'pending') {
                continue;
            }

            try {
                // Generate unsubscribe link
                $unsubscribeUrl = $newsletterService->generateUnsubscribeUrl(
                    $newsletter->team_id,
                    $recipient->email_address
                );

                // Inject unsubscribe link into HTML body
                $htmlBody = $newsletter->html_body;
                $unsubscribeHtml = '<div style="text-align:center;margin-top:24px;padding-top:16px;border-top:1px solid #e0e0e0;font-size:12px;color:#999;">'
                    . '<a href="' . e($unsubscribeUrl) . '" style="color:#999;text-decoration:underline;">Newsletter abbestellen</a>'
                    . '</div>';
                $htmlBody .= $unsubscribeHtml;

                // Preheader injection
                if ($newsletter->preheader) {
                    $preheaderHtml = '<div style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">'
                        . e($newsletter->preheader)
                        . '</div>';
                    $htmlBody = $preheaderHtml . $htmlBody;
                }

                $messageId = $postmarkService->sendRaw(
                    $channel,
                    $recipient->email_address,
                    $newsletter->subject,
                    $htmlBody,
                    $newsletter->text_body,
                    [
                        'tag' => 'newsletter',
                        'track_opens' => true,
                        'track_links' => 'HtmlAndText',
                        'metadata' => [
                            'newsletter_id' => (string) $newsletter->id,
                            'recipient_id' => (string) $recipient->id,
                        ],
                        'list_unsubscribe' => $unsubscribeUrl,
                    ]
                );

                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[SendNewsletterChunkJob] Failed to send to recipient', [
                    'newsletter_id' => $newsletter->id,
                    'recipient_id' => $recipientId,
                    'email' => $recipient->email_address,
                    'error' => $e->getMessage(),
                ]);

                $recipient->update([
                    'status' => 'failed',
                    'error_message' => mb_substr($e->getMessage(), 0, 500),
                ]);
            }
        }

        // Update stats after chunk
        $newsletter->refresh();
        $newsletter->updateStats();

        // Check if all recipients are processed (no more pending)
        $pendingCount = $newsletter->recipients()->where('status', 'pending')->count();
        if ($pendingCount === 0 && $newsletter->status === 'sending') {
            $newsletter->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }
    }
}
