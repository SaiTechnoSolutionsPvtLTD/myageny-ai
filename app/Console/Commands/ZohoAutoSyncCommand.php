<?php

namespace App\Console\Commands;

use App\Models\ZohoExpense;
use App\Models\ZohoIntegration;
use App\Models\ZohoInvoice;
use App\Models\ZohoQuotation;
use App\Services\ZohoBooksService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ZohoAutoSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoho:auto-sync 
                            {--type=all : Module to sync: quotations, invoices, expenses, or all}
                            {--chunk=100 : Number of records to migrate per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-sync Quotations, Invoices, and Expenses from Zoho Books every 20 minutes (GET sync only, strictly no auto-posting)';

    /**
     * Execute the console command.
     */
    public function handle(ZohoBooksService $service): int
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '1024M');

        $this->info("==================================================");
        $this->info("   ZOHO BOOKS AUTOMATIC 20-MIN AUTO-SYNC (GET)    ");
        $this->info("==================================================");

        $integrations = ZohoIntegration::where('is_connected', true)
            ->whereNotNull('refresh_token')
            ->get();

        if ($integrations->isEmpty()) {
            $this->line("Zoho Books is not currently connected on any company. Skipping auto-sync.");
            Log::info('[ZohoAutoSync] Skipped: No connected Zoho Books integration found.');
            return Command::SUCCESS;
        }

        $type = strtolower((string) ($this->option('type') ?: 'all'));
        $chunkSize = max(10, (int) ($this->option('chunk') ?: 100));

        foreach ($integrations as $integration) {
            $companyId = $integration->company_id ?: 1;
            $this->info("\n--- Processing Company ID: {$companyId} ({$integration->organization_name}) ---");

            $summary = [
                'quotes_synced'     => 0,
                'quotes_migrated'   => 0,
                'invoices_synced'   => 0,
                'invoices_migrated' => 0,
                'expenses_synced'   => 0,
                'expenses_migrated' => 0,
            ];

            // 1. QUOTATIONS (GET sync from Zoho Books -> Migrate to native CRM)
            if (in_array($type, ['all', 'quotations', 'estimates'])) {
                try {
                    $this->line("Fetching Quotations/Estimates from Zoho Books API (GET)...");
                    $qRes = $service->syncAllEstimates($integration, false);
                    $summary['quotes_synced'] = (int) ($qRes['total'] ?? 0);
                    $this->info("✓ Quotations Synced: {$summary['quotes_synced']} ({$qRes['synced_new']} new, {$qRes['updated']} updated)");

                    // Migrate newly synced unmigrated quotations into native CRM table
                    $unmigratedQuotes = ZohoQuotation::where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId)->orWhereNull('company_id');
                        })
                        ->where('is_migrated_to_native', false)
                        ->limit($chunkSize)
                        ->get();

                    foreach ($unmigratedQuotes as $zq) {
                        try {
                            $service->migrateQuotationToNative($zq, $integration->created_by ?: 1);
                            $summary['quotes_migrated']++;
                        } catch (\Throwable $me) {
                            Log::warning("[ZohoAutoSync] Quotation #{$zq->estimate_number} migration skipped: " . $me->getMessage());
                        }
                    }
                    $this->info("✓ Quotations Migrated to Native CRM: {$summary['quotes_migrated']}");
                } catch (\Throwable $qe) {
                    $this->error("Quotation sync error: " . $qe->getMessage());
                    Log::error("[ZohoAutoSync] Quotation sync failed for company {$companyId}: " . $qe->getMessage());
                }
            }

            // 2. INVOICES (GET sync from Zoho Books -> Migrate to native CRM)
            if (in_array($type, ['all', 'invoices'])) {
                try {
                    $this->line("Fetching Invoices from Zoho Books API (GET)...");
                    $iRes = $service->syncAllInvoices($integration, false);
                    $summary['invoices_synced'] = (int) ($iRes['total_synced'] ?? 0);
                    $this->info("✓ Invoices Synced: {$summary['invoices_synced']} ({$iRes['new_count']} new, {$iRes['updated_count']} updated)");

                    // Migrate newly synced unmigrated invoices into native CRM table
                    $unmigratedInvoices = ZohoInvoice::where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId)->orWhereNull('company_id');
                        })
                        ->where('is_migrated_to_native', false)
                        ->limit($chunkSize)
                        ->get();

                    foreach ($unmigratedInvoices as $zi) {
                        try {
                            $service->migrateInvoiceToNative($zi, $integration->created_by ?: 1);
                            $summary['invoices_migrated']++;
                        } catch (\Throwable $me) {
                            Log::warning("[ZohoAutoSync] Invoice #{$zi->invoice_number} migration skipped: " . $me->getMessage());
                        }
                    }
                    $this->info("✓ Invoices Migrated to Native CRM: {$summary['invoices_migrated']}");
                } catch (\Throwable $ie) {
                    $this->error("Invoice sync error: " . $ie->getMessage());
                    Log::error("[ZohoAutoSync] Invoice sync failed for company {$companyId}: " . $ie->getMessage());
                }
            }

            // 3. EXPENSES (GET sync from Zoho Books -> Migrate to native CRM)
            if (in_array($type, ['all', 'expenses'])) {
                try {
                    $this->line("Fetching Expenses from Zoho Books API (GET)...");
                    $eRes = $service->syncAllExpenses($integration);
                    $summary['expenses_synced'] = (int) ($eRes['total_synced'] ?? 0);
                    $this->info("✓ Expenses Synced: {$summary['expenses_synced']} ({$eRes['new_count']} new, {$eRes['updated_count']} updated)");

                    // Migrate newly synced unmigrated expenses into native CRM table
                    $unmigratedExpenses = ZohoExpense::where(function ($q) use ($companyId) {
                            $q->where('company_id', $companyId)->orWhereNull('company_id');
                        })
                        ->where('is_migrated_to_native', false)
                        ->limit($chunkSize)
                        ->get();

                    foreach ($unmigratedExpenses as $ze) {
                        try {
                            $service->migrateExpenseToNative($ze, $integration->created_by ?: 1);
                            $summary['expenses_migrated']++;
                        } catch (\Throwable $me) {
                            Log::warning("[ZohoAutoSync] Expense #{$ze->expense_id} migration skipped: " . $me->getMessage());
                        }
                    }
                    $this->info("✓ Expenses Migrated to Native CRM: {$summary['expenses_migrated']}");
                } catch (\Throwable $ee) {
                    $this->error("Expense sync error: " . $ee->getMessage());
                    Log::error("[ZohoAutoSync] Expense sync failed for company {$companyId}: " . $ee->getMessage());
                }
            }

            $integration->update([
                'last_synced_at' => now(),
                'last_error'     => null,
            ]);

            Log::info("[ZohoAutoSync] Auto-sync finished for company {$companyId}: " . json_encode($summary));
        }

        $this->info("\n==================================================");
        $this->info("   ZOHO BOOKS 20-MIN AUTO-SYNC COMPLETED          ");
        $this->info("==================================================");

        return Command::SUCCESS;
    }
}
