<?php

namespace Platform\Crm\Console\Commands;

use Illuminate\Console\Command;
use Platform\Crm\Models\CrmCompanyLink;
use Platform\Crm\Models\CrmContactLink;
use Platform\Sales\Models\SalesBoard;
use Platform\Sales\Models\SalesDeal;
use Platform\Sales\Models\SalesDealBillable;

class PurgeHubspotDeals extends Command
{
    protected $signature = 'crm:purge-hubspot-deals
                            {--team-id= : Team ID (required)}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Delete all HubSpot-imported deals, their billables, CRM links, and empty boards/slots';

    public function handle(): int
    {
        $teamId = (int) $this->option('team-id');
        $dryRun = $this->option('dry-run');

        if (!$teamId) {
            $this->error('--team-id is required.');
            return 1;
        }

        $this->info("Purge HubSpot Deals: team_id={$teamId}" . ($dryRun ? ' [DRY RUN]' : ''));

        // Find all imported deals
        $deals = SalesDeal::where('team_id', $teamId)
            ->where('notes', 'LIKE', '%[HubSpot Import] ID:%')
            ->get();

        $this->info("Found {$deals->count()} imported deals");

        if ($deals->isEmpty()) {
            $this->info('Nothing to purge.');
            return 0;
        }

        $dealIds = $deals->pluck('id')->toArray();
        $boardIds = $deals->pluck('sales_board_id')->filter()->unique()->toArray();

        // Count what will be deleted
        $billableCount = SalesDealBillable::whereIn('sales_deal_id', $dealIds)->count();
        $companyLinkCount = CrmCompanyLink::whereIn('linkable_id', $dealIds)
            ->where('linkable_type', SalesDeal::class)
            ->count();
        $contactLinkCount = CrmContactLink::whereIn('linkable_id', $dealIds)
            ->where('linkable_type', SalesDeal::class)
            ->count();

        $this->table(['Entity', 'Count'], [
            ['Deals', count($dealIds)],
            ['Billables', $billableCount],
            ['Company Links', $companyLinkCount],
            ['Contact Links', $contactLinkCount],
        ]);

        if ($dryRun) {
            $this->info('[DRY RUN] Nothing deleted.');
            return 0;
        }

        if (!$this->confirm('Delete all listed records?')) {
            $this->info('Aborted.');
            return 0;
        }

        // Delete in correct order: links → billables → deals
        CrmCompanyLink::whereIn('linkable_id', $dealIds)
            ->where('linkable_type', SalesDeal::class)
            ->delete();
        $this->info("  Deleted {$companyLinkCount} company links");

        CrmContactLink::whereIn('linkable_id', $dealIds)
            ->where('linkable_type', SalesDeal::class)
            ->delete();
        $this->info("  Deleted {$contactLinkCount} contact links");

        SalesDealBillable::whereIn('sales_deal_id', $dealIds)->forceDelete();
        $this->info("  Deleted {$billableCount} billables");

        SalesDeal::whereIn('id', $dealIds)->forceDelete();
        $this->info("  Deleted " . count($dealIds) . " deals");

        // Clean up empty boards + slots
        $emptyBoards = 0;
        foreach ($boardIds as $boardId) {
            $board = SalesBoard::find($boardId);
            if ($board && $board->deals()->count() === 0) {
                $board->slots()->delete();
                $board->delete();
                $emptyBoards++;
            }
        }
        if ($emptyBoards > 0) {
            $this->info("  Deleted {$emptyBoards} empty boards (with slots)");
        }

        $this->newLine();
        $this->info('Purge complete.');

        return 0;
    }
}
