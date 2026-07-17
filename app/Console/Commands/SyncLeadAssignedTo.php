<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Lead;

class SyncLeadAssignedTo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:sync-assigned-to 
                            {--lead-id= : Sync only this specific lead ID} 
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Update user_id in lead_call_updates and recorded_by in lead_product_payments with leads' assigned_to user ID";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $leadId = $this->option('lead-id');
        $force = $this->option('force');

        if ($leadId) {
            $lead = Lead::find($leadId);

            if (!$lead) {
                $this->error("Lead with ID {$leadId} not found.");
                return self::FAILURE;
            }

            if (is_null($lead->assigned_to)) {
                $this->warn("Lead ID {$leadId} has no assigned user (assigned_to is null). No updates will be performed.");
                return self::SUCCESS;
            }

            $results = $this->syncLeadRelations($lead);
            $this->info("Successfully synchronized relations for Lead ID {$leadId}.");
            $this->line("  - lead_call_updates rows updated: {$results['call_updates']}");
            $this->line("  - lead_product_payments rows updated: {$results['payments']}");
            return self::SUCCESS;
        }

        // Sync all leads
        $query = Lead::whereNotNull('assigned_to');
        $totalLeads = $query->count();

        if ($totalLeads === 0) {
            $this->info("No leads found with a non-null assigned_to user.");
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm("Are you sure you want to sync relations for all {$totalLeads} leads? This will update lead_call_updates and lead_product_payments tables.")) {
            $this->info("Operation cancelled.");
            return self::SUCCESS;
        }

        $this->info("Syncing relations for {$totalLeads} leads...");
        $bar = $this->output->createProgressBar($totalLeads);
        $bar->start();

        $totalCallUpdatesUpdated = 0;
        $totalPaymentsUpdated = 0;

        $query->chunk(100, function ($leads) use ($bar, &$totalCallUpdatesUpdated, &$totalPaymentsUpdated) {
            foreach ($leads as $lead) {
                $results = $this->syncLeadRelations($lead);
                $totalCallUpdatesUpdated += $results['call_updates'];
                $totalPaymentsUpdated += $results['payments'];
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync completed successfully!");
        $this->table(
            ['Relation Table', 'Target User Field', 'Rows Updated'],
            [
                ['lead_call_updates', 'user_id', $totalCallUpdatesUpdated],
                ['lead_product_payments', 'recorded_by', $totalPaymentsUpdated],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Sync relations for a single lead.
     *
     * @param Lead $lead
     * @return array
     */
    protected function syncLeadRelations(Lead $lead): array
    {
        // Update user_id in lead_call_updates
        $callUpdates = DB::table('lead_call_updates')
            ->where('lead_id', $lead->id)
            ->update([
                'user_id' => $lead->assigned_to,
                'updated_at' => now(),
            ]);

        // Update recorded_by in lead_product_payments
        $payments = DB::table('lead_product_payments')
            ->where('lead_id', $lead->id)
            ->update([
                'recorded_by' => $lead->assigned_to,
                'updated_at' => now(),
            ]);

        return [
            'call_updates' => $callUpdates,
            'payments' => $payments,
        ];
    }
}
