<?php

namespace App\Console\Commands;

use App\Models\LeadProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncLeadProductConvertedAtCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lead-products:sync-converted-at 
                            {--lead-id= : Sync only products for this specific lead ID}
                            {--product-id= : Sync only this specific lead product ID}
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync converted_at date for converted lead products from their earliest payment date or creation timestamp';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $leadId = $this->option('lead-id');
        $productId = $this->option('product-id');
        $force = $this->option('force');

        $query = LeadProduct::query()
            ->where(function ($q) {
                $q->where('product_status', 'converted')
                  ->orWhereHas('leadStatus', fn ($sq) => $sq->whereRaw('LOWER(name) = ?', ['converted']));
            });

        if ($productId) {
            $query->where('id', (int) $productId);
        }

        if ($leadId) {
            $query->where('lead_id', (int) $leadId);
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No converted lead products found matching the criteria.');
            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("Are you sure you want to sync converted_at for {$totalCount} converted lead product(s)?", true)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $this->info("Syncing converted_at dates from payment records...");
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $updatedCount = 0;

        $query->with(['payments' => fn ($pq) => $pq->orderBy('payment_date', 'asc')])
            ->chunk(100, function ($products) use ($bar, &$updatedCount) {
                foreach ($products as $lp) {
                    $firstPayment = $lp->payments->first();
                    $targetDate = $firstPayment?->payment_date
                        ?: ($lp->converted_at ?: ($lp->updated_at ?: $lp->created_at));

                    if ($targetDate) {
                        $lp->updateQuietly([
                            'converted_at' => $targetDate,
                        ]);
                        $updatedCount++;
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync completed successfully!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Converted Products Checked', $totalCount],
                ['Converted Products Synced', $updatedCount],
            ]
        );

        return self::SUCCESS;
    }
}
