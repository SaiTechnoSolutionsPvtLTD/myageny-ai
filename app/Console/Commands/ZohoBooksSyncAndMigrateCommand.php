<?php

namespace App\Console\Commands;

use App\Models\ZohoExpense;
use App\Models\ZohoIntegration;
use App\Models\ZohoInvoice;
use App\Models\ZohoPayment;
use App\Models\ZohoQuotation;
use App\Services\ZohoBooksService;
use Illuminate\Console\Command;

class ZohoBooksSyncAndMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'zoho:migrate 
                            {--type=all : Module to migrate: invoices, expenses, payments, quotations, or all}
                            {--sync : Whether to sync from Zoho Books API first before migrating}
                            {--chunk=100 : Number of records to process per chunk}
                            {--limit=0 : Optional maximum number of records to migrate (0 for all)}';

    /**
     * The console command description.
     */
    protected $description = 'Safely sync and migrate Zoho Books Invoices, Expenses, Payments, and Quotations without server timeouts';

    public function handle(ZohoBooksService $service): int
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $this->info("==================================================");
        $this->info("   ZOHO BOOKS LARGE DATASET MIGRATION RUNNER      ");
        $this->info("==================================================");

        $integration = ZohoIntegration::first();
        if (! $integration || ! $integration->is_connected) {
            $this->error("Error: Zoho Books is not connected. Please connect via Settings in the web application first.");
            return Command::FAILURE;
        }

        $type = strtolower((string) $this->option('type'));
        $shouldSync = (bool) $this->option('sync');
        $chunkSize = max(10, (int) $this->option('chunk'));
        $limit = (int) $this->option('limit');

        if ($shouldSync) {
            $this->info("\n[1/2] Syncing raw data from Zoho Books API (Fast Page Mode)...");
            if (in_array($type, ['all', 'invoices'])) {
                $this->line("Fetching Invoices from Zoho Books...");
                $res = $service->syncAllInvoices($integration, false);
                $this->info("✓ Invoices Synced: {$res['total_synced']} ({$res['new_count']} new)");
            }
            if (in_array($type, ['all', 'expenses'])) {
                $this->line("Fetching Expenses from Zoho Books...");
                $res = $service->syncAllExpenses($integration);
                $this->info("✓ Expenses Synced: {$res['total_synced']} ({$res['new_count']} new)");
            }
            if (in_array($type, ['all', 'payments'])) {
                $this->line("Fetching Payments from Zoho Books...");
                $res = $service->syncAllPayments($integration, false);
                $this->info("✓ Payments Synced: {$res['total_synced']} ({$res['new_count']} new)");
            }
            if (in_array($type, ['all', 'quotations', 'estimates'])) {
                $this->line("Fetching Quotations from Zoho Books...");
                $res = $service->syncAllEstimates($integration, false);
                $this->info("✓ Quotations Synced: {$res['total']} ({$res['synced_new']} new)");
            }
        }

        $this->info("\n[2/2] Migrating into Native CRM Database...");

        // 1. Invoices
        if (in_array($type, ['all', 'invoices'])) {
            $this->migrateInvoices($service, $chunkSize, $limit);
        }

        // 2. Expenses
        if (in_array($type, ['all', 'expenses'])) {
            $this->migrateExpenses($service, $chunkSize, $limit);
        }

        // 3. Payments
        if (in_array($type, ['all', 'payments'])) {
            $this->migratePayments($service, $chunkSize, $limit);
        }

        // 4. Quotations
        if (in_array($type, ['all', 'quotations', 'estimates'])) {
            $this->migrateQuotations($service, $chunkSize, $limit);
        }

        $this->info("\n==================================================");
        $this->info("   ZOHO BOOKS MIGRATION COMPLETED SUCCESSFULLY!   ");
        $this->info("==================================================");

        return Command::SUCCESS;
    }

    protected function migrateInvoices(ZohoBooksService $service, int $chunkSize, int $limit): void
    {
        $query = ZohoInvoice::where('is_migrated_to_native', false);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("\n--- Migrating Invoices ({$total} unmigrated) ---");

        if ($total === 0) {
            $this->line("No unmigrated invoices to process.");
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $failed = 0;

        while (true) {
            $batch = ZohoInvoice::where('is_migrated_to_native', false)->limit($chunkSize)->get();
            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $inv) {
                try {
                    $service->migrateInvoiceToNative($inv, 1);
                    $migrated++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("\nInvoice #{$inv->invoice_number} failed: " . $e->getMessage());
                }
                $bar->advance();

                if ($limit > 0 && ($migrated + $failed) >= $limit) {
                    break 2;
                }
            }
        }

        $bar->finish();
        $this->line("\nInvoices Done: {$migrated} migrated successfully, {$failed} failed.");
    }

    protected function migrateExpenses(ZohoBooksService $service, int $chunkSize, int $limit): void
    {
        $query = ZohoExpense::where('is_migrated_to_native', false);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("\n--- Migrating Expenses ({$total} unmigrated) ---");

        if ($total === 0) {
            $this->line("No unmigrated expenses to process.");
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $failed = 0;

        while (true) {
            $batch = ZohoExpense::where('is_migrated_to_native', false)->limit($chunkSize)->get();
            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $exp) {
                try {
                    $service->migrateExpenseToNative($exp, 1);
                    $migrated++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("\nExpense #{$exp->expense_id} failed: " . $e->getMessage());
                }
                $bar->advance();

                if ($limit > 0 && ($migrated + $failed) >= $limit) {
                    break 2;
                }
            }
        }

        $bar->finish();
        $this->line("\nExpenses Done: {$migrated} migrated successfully, {$failed} failed.");
    }

    protected function migratePayments(ZohoBooksService $service, int $chunkSize, int $limit): void
    {
        $query = ZohoPayment::where('is_migrated_to_native', false);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("\n--- Migrating Customer Payments ({$total} unmigrated) ---");

        if ($total === 0) {
            $this->line("No unmigrated payments to process.");
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $failed = 0;

        while (true) {
            $batch = ZohoPayment::where('is_migrated_to_native', false)->limit($chunkSize)->get();
            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $pmt) {
                try {
                    $service->migratePaymentToNative($pmt, 1);
                    $migrated++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("\nPayment #{$pmt->payment_number} failed: " . $e->getMessage());
                }
                $bar->advance();

                if ($limit > 0 && ($migrated + $failed) >= $limit) {
                    break 2;
                }
            }
        }

        $bar->finish();
        $this->line("\nPayments Done: {$migrated} migrated successfully, {$failed} failed.");
    }

    protected function migrateQuotations(ZohoBooksService $service, int $chunkSize, int $limit): void
    {
        $query = ZohoQuotation::where('is_migrated_to_native', false);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $total = $query->count();
        $this->info("\n--- Migrating Quotations & Estimates ({$total} unmigrated) ---");

        if ($total === 0) {
            $this->line("No unmigrated quotations to process.");
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $migrated = 0;
        $failed = 0;

        while (true) {
            $batch = ZohoQuotation::where('is_migrated_to_native', false)->limit($chunkSize)->get();
            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $quote) {
                try {
                    $service->migrateQuotationToNative($quote, 1);
                    $migrated++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("\nQuotation #{$quote->estimate_number} failed: " . $e->getMessage());
                }
                $bar->advance();

                if ($limit > 0 && ($migrated + $failed) >= $limit) {
                    break 2;
                }
            }
        }

        $bar->finish();
        $this->line("\nQuotations Done: {$migrated} migrated successfully, {$failed} failed.");
    }
}
