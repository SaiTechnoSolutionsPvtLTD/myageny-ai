<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Lead;

class SyncLeadBranch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leads:sync-branch 
                            {--lead-id= : Sync only this specific lead ID} 
                            {--force : Force execution without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Update leads' branch_id with the branch_id of their assigned user";

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
                $this->warn("Lead ID {$leadId} has no assigned user (assigned_to is null).");
                return self::SUCCESS;
            }

            $user = DB::table('users')->where('id', $lead->assigned_to)->first();

            if (!$user) {
                $this->error("Assigned user with ID {$lead->assigned_to} not found.");
                return self::FAILURE;
            }

            if (is_null($user->branch_id)) {
                $this->warn("Assigned user (ID {$user->id}) has no branch assigned (branch_id is null).");
                return self::SUCCESS;
            }

            if ($lead->branch_id === $user->branch_id) {
                $this->info("Lead ID {$leadId} already has the correct branch ID ({$user->branch_id}).");
                return self::SUCCESS;
            }

            DB::table('leads')
                ->where('id', $lead->id)
                ->update([
                    'branch_id' => $user->branch_id,
                    'updated_at' => now(),
                ]);

            $this->info("Successfully updated branch for Lead ID {$leadId} to {$user->branch_id}.");
            return self::SUCCESS;
        }

        // Sync all leads
        $query = Lead::whereNotNull('assigned_to');
        $totalLeads = $query->count();

        if ($totalLeads === 0) {
            $this->info("No leads found with a non-null assigned_to user.");
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm("Are you sure you want to sync the branch ID for all {$totalLeads} leads?")) {
            $this->info("Operation cancelled.");
            return self::SUCCESS;
        }

        $this->info("Syncing branch IDs for leads...");
        $bar = $this->output->createProgressBar($totalLeads);
        $bar->start();

        $updatedCount = 0;

        $query->chunk(100, function ($leads) use ($bar, &$updatedCount) {
            foreach ($leads as $lead) {
                // Get the user's branch_id directly from the users table
                $userBranchId = DB::table('users')
                    ->where('id', $lead->assigned_to)
                    ->value('branch_id');

                if ($userBranchId && $lead->branch_id !== $userBranchId) {
                    DB::table('leads')
                        ->where('id', $lead->id)
                        ->update([
                            'branch_id' => $userBranchId,
                            'updated_at' => now(),
                        ]);
                    $updatedCount++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Sync completed successfully!");
        $this->line("Total leads updated: {$updatedCount}");

        return self::SUCCESS;
    }
}
