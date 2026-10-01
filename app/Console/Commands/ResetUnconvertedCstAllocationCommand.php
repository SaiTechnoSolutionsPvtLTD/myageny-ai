<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetUnconvertedCstAllocationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:reset-unconverted-cst {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set customer_support_tl_id and customer_support_executive_id to NULL for leads that have no converted products';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $force = $this->option('force');

        // 1. Identify all lead IDs that have converted products or converted lead status
        $convertedLeadIdsFromProducts = DB::table('lead_products')
            ->where(function ($q) {
                $q->whereRaw('LOWER(product_status) = ?', ['converted'])
                  ->orWhereNotNull('converted_at');
            })
            ->whereNotNull('lead_id')
            ->pluck('lead_id')
            ->unique()
            ->toArray();

        $convertedStatusIds = DB::table('lead_statuses')
            ->whereRaw('LOWER(name) = ?', ['converted'])
            ->pluck('id')
            ->toArray();

        $convertedLeadIdsFromLeads = DB::table('leads')
            ->where(function ($q) use ($convertedStatusIds) {
                if (!empty($convertedStatusIds)) {
                    $q->whereIn('lead_status_id', $convertedStatusIds);
                }
                $q->orWhereRaw('LOWER(lead_status) = ?', ['converted']);
            })
            ->pluck('id')
            ->unique()
            ->toArray();

        $convertedLeadIds = array_unique(array_merge($convertedLeadIdsFromProducts, $convertedLeadIdsFromLeads));

        // 2. Query for unconverted leads that currently have CST assigned
        $query = DB::table('leads')
            ->where(function ($q) {
                $q->whereNotNull('customer_support_tl_id')
                  ->orWhereNotNull('customer_support_executive_id');
            })
            ->whereNotIn('id', $convertedLeadIds);

        $totalTargetLeads = $query->count();

        if ($totalTargetLeads === 0) {
            $this->info('No unconverted leads with assigned CST found.');
            return self::SUCCESS;
        }

        $this->info("Found {$totalTargetLeads} unconverted lead(s) with CST assigned.");

        if (! $force && ! $this->confirm("Are you sure you want to set customer_support_tl_id and customer_support_executive_id to NULL for these {$totalTargetLeads} lead(s)?", true)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $this->info('Updating leads in database...');

        $updatedCount = DB::table('leads')
            ->where(function ($q) {
                $q->whereNotNull('customer_support_tl_id')
                  ->orWhereNotNull('customer_support_executive_id');
            })
            ->whereNotIn('id', $convertedLeadIds)
            ->update([
                'customer_support_tl_id'        => null,
                'customer_support_executive_id' => null,
                'customer_support_allocated_at' => null,
                'updated_at'                    => now(),
            ]);

        $this->newLine();
        $this->info('CST reset completed successfully!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Converted Leads Preserved (Untouched)', count($convertedLeadIds)],
                ['Unconverted Leads Reset (CST set to NULL)', $updatedCount],
            ]
        );

        return self::SUCCESS;
    }
}
