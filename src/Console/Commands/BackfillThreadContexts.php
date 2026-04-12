<?php

namespace Platform\Crm\Console\Commands;

use Illuminate\Console\Command;
use Platform\Crm\Models\CommsEmailThread;
use Platform\Crm\Models\CommsThreadContext;
use Platform\Crm\Models\CommsWhatsAppThread;

class BackfillThreadContexts extends Command
{
    protected $signature = 'comms:backfill-thread-contexts {--dry-run : Show what would be backfilled without writing}';

    protected $description = 'Backfill comms_thread_contexts pivot table from legacy context_model/context_model_id columns';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $created = 0;
        $skipped = 0;

        if ($dryRun) {
            $this->info('DRY RUN — no records will be created.');
        }

        foreach ([CommsEmailThread::class, CommsWhatsAppThread::class] as $threadClass) {
            $label = class_basename($threadClass);
            $this->info("Processing {$label}...");

            $threadClass::query()
                ->whereNotNull('context_model')
                ->whereNotNull('context_model_id')
                ->chunkById(500, function ($threads) use ($threadClass, $dryRun, &$created, &$skipped) {
                    foreach ($threads as $thread) {
                        $exists = CommsThreadContext::query()
                            ->where('thread_type', $threadClass)
                            ->where('thread_id', $thread->id)
                            ->where('context_model', $thread->context_model)
                            ->where('context_model_id', $thread->context_model_id)
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }

                        if (!$dryRun) {
                            CommsThreadContext::create([
                                'thread_type' => $threadClass,
                                'thread_id' => $thread->id,
                                'context_model' => $thread->context_model,
                                'context_model_id' => $thread->context_model_id,
                                'source' => 'backfill',
                            ]);
                        }

                        $created++;
                    }
                });
        }

        $this->info("Done. Created: {$created}, Skipped (already exists): {$skipped}");

        if ($dryRun) {
            $this->info("Run without --dry-run to apply.");
        }

        return self::SUCCESS;
    }
}
