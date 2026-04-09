<?php

namespace Platform\Crm\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Platform\Crm\Models\CrmCompany;
use Platform\Crm\Models\CrmCompanyLink;
use Platform\Crm\Models\CrmContact;
use Platform\Crm\Models\CrmContactLink;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesBoardSlot;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealBillable;

class ImportHubspotDeals extends Command
{
    protected $signature = 'crm:import-hubspot-deals
                            {path : Path to directory containing deals.csv and pipelines_deals.csv}
                            {--team-id= : Team ID to import into (required)}
                            {--user-id= : User ID for ownership (required)}
                            {--dry-run : Show what would be imported without actually importing}';

    protected $description = 'Import HubSpot deals from CSV export into Sales module (Boards, Slots, Deals + CRM links)';

    /** pipeline_id → SalesBoard */
    private array $boardCache = [];

    /** stage_id → SalesBoardSlot */
    private array $slotCache = [];

    /** stage_id → bool (is closed stage?) */
    private array $closedStages = [];

    /** HubSpot external ID → CRM company ID */
    private array $companyMap = [];

    /** HubSpot external ID → CRM contact ID */
    private array $contactMap = [];

    public function handle(): int
    {
        $path = rtrim($this->argument('path'), '/');
        $teamId = (int) $this->option('team-id');
        $userId = (int) $this->option('user-id');
        $dryRun = $this->option('dry-run');

        if (!$teamId || !$userId) {
            $this->error('--team-id and --user-id are required.');
            return 1;
        }

        if (!file_exists($path . '/deals.csv')) {
            $this->error("deals.csv not found in: {$path}");
            return 1;
        }

        if (!file_exists($path . '/pipelines_deals.csv')) {
            $this->error("pipelines_deals.csv not found in: {$path}");
            return 1;
        }

        $this->info("HubSpot Deals Import: team_id={$teamId}, user_id={$userId}" . ($dryRun ? ' [DRY RUN]' : ''));

        // 1. Build CRM lookup maps from existing imported companies/contacts
        $this->buildCrmMaps($teamId);

        // 2. Import pipelines → SalesBoard + SalesBoardSlot
        $this->importPipelines($path . '/pipelines_deals.csv', $teamId, $userId, $dryRun);

        // 3. Import deals
        $dealCount = $this->importDeals($path . '/deals.csv', $teamId, $userId, $dryRun);

        $this->newLine();
        $this->info('Import complete.');
        $this->table(['Entity', 'Count'], [
            ['Boards', count($this->boardCache)],
            ['Slots', count($this->slotCache)],
            ['Deals', $dealCount],
            ['Company map entries', count($this->companyMap)],
            ['Contact map entries', count($this->contactMap)],
        ]);

        return 0;
    }

    /**
     * Parse [HubSpot Import] ID: xxx from notes to build HS→CRM maps.
     */
    private function buildCrmMaps(int $teamId): void
    {
        $this->info('Building CRM lookup maps from existing imports...');

        // Companies
        CrmCompany::where('team_id', $teamId)
            ->where('notes', 'LIKE', '%[HubSpot Import] ID:%')
            ->select(['id', 'notes'])
            ->chunk(500, function ($companies) {
                foreach ($companies as $company) {
                    $hsId = $this->parseHubspotIdFromNotes($company->notes);
                    if ($hsId) {
                        $this->companyMap[$hsId] = $company->id;
                    }
                }
            });

        // Contacts
        CrmContact::where('team_id', $teamId)
            ->where('notes', 'LIKE', '%[HubSpot Import] ID:%')
            ->select(['id', 'notes'])
            ->chunk(500, function ($contacts) {
                foreach ($contacts as $contact) {
                    $hsId = $this->parseHubspotIdFromNotes($contact->notes);
                    if ($hsId) {
                        $this->contactMap[$hsId] = $contact->id;
                    }
                }
            });

        $this->info("  Found {$this->countCompanyMap()} companies, {$this->countContactMap()} contacts in CRM");
    }

    private function countCompanyMap(): int { return count($this->companyMap); }
    private function countContactMap(): int { return count($this->contactMap); }

    private function parseHubspotIdFromNotes(?string $notes): ?string
    {
        if (!$notes) return null;

        if (preg_match('/\[HubSpot Import\] ID:\s*(\S+)/', $notes, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Read pipelines_deals.csv and create SalesBoard + SalesBoardSlot records.
     */
    private function importPipelines(string $file, int $teamId, int $userId, bool $dryRun): void
    {
        $this->newLine();
        $this->info('Importing Pipelines → Sales Boards + Slots...');

        $rows = $this->readCsv($file);

        // Group by pipeline
        $pipelines = [];
        foreach ($rows as $row) {
            $pipelineId = $row['pipeline_id'] ?? '';
            if (!isset($pipelines[$pipelineId])) {
                $pipelines[$pipelineId] = [
                    'label' => $row['pipeline_label'] ?? $pipelineId,
                    'order' => (int) ($row['pipeline_displayOrder'] ?? 0),
                    'stages' => [],
                ];
            }
            $pipelines[$pipelineId]['stages'][] = $row;
        }

        foreach ($pipelines as $pipelineId => $pipeline) {
            $this->info("  Pipeline: {$pipeline['label']}");

            if ($dryRun) {
                $this->boardCache[$pipelineId] = 0;
                foreach ($pipeline['stages'] as $stage) {
                    $stageId = $stage['stage_id'] ?? '';
                    $isClosed = strtolower($stage['stage_isClosed'] ?? '') === 'true';
                    $this->closedStages[$stageId] = $isClosed;
                    if (!$isClosed) {
                        $this->slotCache[$stageId] = 0;
                    }
                }
                continue;
            }

            $board = SalesBoard::firstOrCreate(
                ['name' => $pipeline['label'], 'team_id' => $teamId],
                ['user_id' => $userId, 'order' => $pipeline['order']]
            );
            $this->boardCache[$pipelineId] = $board->id;

            foreach ($pipeline['stages'] as $stage) {
                $stageId = $stage['stage_id'] ?? '';
                $stageLabel = $stage['stage_label'] ?? $stageId;
                $isClosed = strtolower($stage['stage_isClosed'] ?? '') === 'true';
                $this->closedStages[$stageId] = $isClosed;

                if ($isClosed) {
                    $this->info("    [closed] {$stageLabel} → skip slot");
                    continue;
                }

                $slot = SalesBoardSlot::firstOrCreate(
                    ['sales_board_id' => $board->id, 'name' => $stageLabel],
                    ['order' => (int) ($stage['stage_displayOrder'] ?? 0), 'color' => 'blue']
                );
                $this->slotCache[$stageId] = $slot->id;
                $this->info("    Slot: {$stageLabel} (order={$slot->order})");
            }
        }
    }

    private function importDeals(string $file, int $teamId, int $userId, bool $dryRun): int
    {
        $this->newLine();
        $this->info('Importing Deals...');

        $rows = $this->readCsv($file);
        $this->info("  Found {$rows->count()} deals in CSV");

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $linksCreated = 0;
        $billablesCreated = 0;
        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $hsId = $row['hs_object_id'] ?? $row['id'] ?? '';
            $dealname = trim($row['dealname'] ?? '');

            if ($dealname === '') {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Resolve board + slot
            $pipelineId = $row['pipeline'] ?? '';
            $stageId = $row['dealstage'] ?? '';
            $boardId = $this->boardCache[$pipelineId] ?? null;
            $slotId = $this->slotCache[$stageId] ?? null;
            $isClosed = $this->closedStages[$stageId] ?? false;

            // Determine if won or lost
            $isClosedWon = strtolower($row['hs_is_closed_won'] ?? '') === 'true';
            $isClosedLost = strtolower($row['hs_is_closed_lost'] ?? '') === 'true';
            $isDone = $isClosed || $isClosedWon || $isClosedLost;

            // Parse fields
            $amount = isset($row['amount']) && $row['amount'] !== '' ? (float) $row['amount'] : null;
            $closeDate = $this->parseTimestamp($row['closedate'] ?? '');
            $description = trim($row['description'] ?? '') ?: null;
            $probability = isset($row['hs_deal_stage_probability']) && $row['hs_deal_stage_probability'] !== ''
                ? (int) round((float) $row['hs_deal_stage_probability'] * 100)
                : null;

            // Priority mapping
            $priority = strtolower(trim($row['hs_priority'] ?? ''));
            $isHot = in_array($priority, ['high', 'urgent']);

            $notes = "[HubSpot Import] ID: {$hsId}";

            if ($dryRun) {
                $created++;
                $bar->advance();
                continue;
            }

            try {
                // Idempotency: check if deal already exists
                $existing = SalesDeal::where('team_id', $teamId)
                    ->where('notes', 'LIKE', "%[HubSpot Import] ID: {$hsId}%")
                    ->first();

                $syncData = [
                    'title' => $dealname,
                    'description' => $description,
                    'close_date' => $closeDate ? \Carbon\Carbon::parse($closeDate)->toDateString() : null,
                    'probability_percent' => $probability,
                    'sales_board_id' => $boardId,
                    'sales_board_slot_id' => $isDone ? null : $slotId,
                    'is_done' => $isDone,
                    'is_hot' => $isHot,
                ];

                if ($existing) {
                    $existing->update($syncData);
                    $deal = $existing;
                    $updated++;
                } else {
                    $deal = SalesDeal::create(array_merge($syncData, [
                        'user_id' => $userId,
                        'team_id' => $teamId,
                        'notes' => $notes,
                    ]));
                    $created++;
                }

                // Billable (one_time) - only create if amount exists and no billable yet
                if ($amount !== null && $amount > 0 && $deal->billables()->count() === 0) {
                    SalesDealBillable::create([
                        'sales_deal_id' => $deal->id,
                        'name' => $dealname,
                        'amount' => $amount,
                        'probability_percent' => $probability ?? 0,
                        'billing_type' => 'one_time',
                        'is_active' => true,
                        'order' => 0,
                    ]);
                    $billablesCreated++;
                }

                // CRM Links
                $linksCreated += $this->createDealLinks($deal, $row, $teamId, $userId);
            } catch (\Throwable $e) {
                $errors++;
                Log::warning("HubSpot Deals Import: error HS={$hsId}", ['error' => $e->getMessage()]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("  Deals: {$created} created, {$updated} updated, {$skipped} skipped, {$errors} errors");
        $this->info("  Billables: {$billablesCreated} created, CRM links: {$linksCreated}");

        return $created + $updated;
    }

    private function createDealLinks(SalesDeal $deal, array $row, int $teamId, int $userId): int
    {
        $count = 0;

        // Company links
        foreach ($this->parseAssocIds($row['assoc_companies'] ?? '') as $hsCompanyId) {
            $crmCompanyId = $this->companyMap[$hsCompanyId] ?? null;
            if (!$crmCompanyId) continue;

            try {
                CrmCompanyLink::firstOrCreate([
                    'company_id' => $crmCompanyId,
                    'linkable_id' => $deal->id,
                    'linkable_type' => SalesDeal::class,
                    'team_id' => $teamId,
                ], ['created_by_user_id' => $userId]);
                $count++;
            } catch (\Throwable $e) {
                Log::debug("HubSpot Deals Import: CompanyLink error", [
                    'deal' => $deal->id, 'company' => $crmCompanyId, 'error' => $e->getMessage(),
                ]);
            }
        }

        // Contact links
        foreach ($this->parseAssocIds($row['assoc_contacts'] ?? '') as $hsContactId) {
            $crmContactId = $this->contactMap[$hsContactId] ?? null;
            if (!$crmContactId) continue;

            try {
                CrmContactLink::firstOrCreate([
                    'contact_id' => $crmContactId,
                    'linkable_id' => $deal->id,
                    'linkable_type' => SalesDeal::class,
                    'team_id' => $teamId,
                ], ['created_by_user_id' => $userId]);
                $count++;
            } catch (\Throwable $e) {
                Log::debug("HubSpot Deals Import: ContactLink error", [
                    'deal' => $deal->id, 'contact' => $crmContactId, 'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    private function parseAssocIds(string $value): array
    {
        $value = trim($value);
        if ($value === '') return [];

        return array_filter(array_map('trim', explode(';', $value)));
    }

    private function parseTimestamp(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function readCsv(string $file): \Illuminate\Support\Collection
    {
        $rows = collect();
        if (($handle = fopen($file, 'r')) !== false) {
            $headers = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== false) {
                if (count($data) === count($headers)) {
                    $rows->push(array_combine($headers, $data));
                }
            }
            fclose($handle);
        }

        return $rows;
    }
}
