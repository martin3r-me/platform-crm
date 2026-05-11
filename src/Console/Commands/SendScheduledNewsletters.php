<?php

namespace Platform\Crm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Platform\Crm\Models\CommsNewsletter;
use Platform\Crm\Services\Comms\NewsletterService;

class SendScheduledNewsletters extends Command
{
    protected $signature = 'crm:send-scheduled-newsletters';

    protected $description = 'Send newsletters that are scheduled and due.';

    public function handle(NewsletterService $newsletterService): int
    {
        $newsletters = CommsNewsletter::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($newsletters->isEmpty()) {
            $this->info('No scheduled newsletters due.');
            return self::SUCCESS;
        }

        foreach ($newsletters as $newsletter) {
            try {
                $this->info("Sending newsletter #{$newsletter->id}: {$newsletter->name}");
                $newsletterService->send($newsletter);
                $this->info("  -> Dispatched successfully.");
            } catch (\Throwable $e) {
                $this->error("  -> Failed: {$e->getMessage()}");
                Log::error('[SendScheduledNewsletters] Failed', [
                    'newsletter_id' => $newsletter->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed {$newsletters->count()} newsletter(s).");
        return self::SUCCESS;
    }
}
