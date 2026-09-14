<?php

namespace App\Console\Commands;

use App\Models\ProductionInitiation;
use App\Models\ProjectUpdate;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateWelcomeCallCompletedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:update-welcome-call-completed
                            {--id= : Specific ProductionInitiation ID to update}
                            {--force : Update or add even if Welcome Call is already marked completed}
                            {--type=both : Type of update to create: "both" (recommended), "welcome_call_update", or "production_update"}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Welcome Call Completed update to all current active/approved projects';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificId = $this->option('id');
        $force = (bool) $this->option('force');
        $updateTypeOption = strtolower((string) $this->option('type'));

        $query = ProductionInitiation::query()
            ->with([
                'lead:id,company_name,contact_name,mobile_number',
                'department:id,name',
                'product:id,product_name',
                'ovpAllocatedTo:id,name,email',
                'initiatedBy:id,name',
                'reviewedBy:id,name',
                'productionApprovalReviewedBy:id,name',
                'projectUpdates:id,production_initiation_id,type,content,created_at',
            ])
            ->whereIn('production_approval_status', ['approval', 'approved']);

        if ($specificId) {
            $query->where('id', $specificId);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->warn('No approved production initiation projects found.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} approved project(s). Processing Welcome Call Completed updates..." . ($force ? ' [FORCE MODE]' : ''));

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $defaultUser = User::where('name', 'like', '%Super Admin%')
            ->orWhere('name', 'like', '%Admin%')
            ->first() ?: User::first();

        $updatedCount = 0;
        $skippedCount = 0;

        $query->chunk(100, function ($projects) use (&$updatedCount, &$skippedCount, $force, $updateTypeOption, $defaultUser, $progressBar) {
            foreach ($projects as $project) {
                // Check if project already has a Welcome Call Completed update
                $hasWelcomeCallUpdate = $project->projectUpdates->contains(function ($u) {
                    if ($u->type === 'welcome_call_update') {
                        return true;
                    }
                    $lowerContent = strtolower((string) $u->content);
                    return str_contains($lowerContent, 'welcome call status: completed')
                        || str_contains($lowerContent, 'welcome call completed')
                        || str_contains($lowerContent, 'welcome call status: done');
                });

                if ($hasWelcomeCallUpdate && !$force) {
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                // Determine actor for the update
                $actor = $project->ovpAllocatedTo
                    ?: ($project->reviewedBy
                        ?: ($project->productionApprovalReviewedBy
                            ?: ($project->initiatedBy ?: $defaultUser)));

                $actorName = $actor?->name ?: 'Customer Success Team';
                $actorId = $actor?->id ?: ($defaultUser?->id ?: null);

                // Determine timestamp (use approved time, reviewed time, or created time)
                $timestamp = $project->production_approval_reviewed_at
                    ?: ($project->reviewed_at
                        ?: ($project->ovp_allocated_at
                            ?: ($project->created_at ?: Carbon::now())));

                $timestampFormatted = $timestamp->format('d M Y, h:i A');

                $companyName = htmlspecialchars(
                    $project->company_name
                    ?: ($project->lead?->company_name
                        ?: ($project->client_name
                            ?: ($project->lead?->client_name
                                ?: ($project->lead?->contact_name ?: 'N/A'))))
                );

                $productName = htmlspecialchars($project->product_name ?: ($project->product?->product_name ?: 'N/A'));
                $deptName = htmlspecialchars($project->department?->name ?: 'N/A');
                $phone = htmlspecialchars($project->lead?->mobile_number ?: 'N/A');

                // Build rich HTML card content
                $htmlContent = <<<HTML
<div style="display: flex; flex-direction: column; gap: 14px; font-family: inherit;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <span style="background: #f0fdf4; color: #047857; border: 1px solid #a7f3d0; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;">
                📞 Welcome Call Completed
            </span>
            <span style="color: #0f172a; font-size: 14px; font-weight: 700;">Completed by {$actorName}</span>
        </div>
        <span style="color: #64748b; font-size: 12px; font-weight: 500;">📅 {$timestampFormatted}</span>
    </div>

    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;">
        <div style="font-size: 12px; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            📋 Welcome Call Details
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
            <div>
                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Company / Client</span>
                <span style="display: block; font-size: 13px; color: #0f172a; font-weight: 700; margin-top: 2px;">{$companyName}</span>
            </div>
            <div>
                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Product & Department</span>
                <span style="display: block; font-size: 13px; color: #0f172a; font-weight: 600; margin-top: 2px;">{$productName} ({$deptName})</span>
            </div>
            <div>
                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Contact Number</span>
                <span style="display: block; font-size: 13px; color: #0f172a; font-weight: 600; margin-top: 2px;">{$phone}</span>
            </div>
            <div>
                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Welcome Call Status</span>
                <span style="display: inline-block; font-size: 12px; color: #065f46; background: #d1fae5; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 4px; font-weight: 700; margin-top: 2px;">Completed</span>
            </div>
        </div>
        <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #e2e8f0; font-size: 12px; color: #475569;">
            <strong>Remarks:</strong> Welcome call completed successfully with the client. Project requirements confirmed and moved to production.
        </div>
    </div>
</div>
HTML;

                try {
                    // Create ProjectUpdate record(s)
                    if ($updateTypeOption === 'welcome_call_update' || $updateTypeOption === 'both') {
                        $update = new ProjectUpdate();
                        $update->production_initiation_id = $project->id;
                        $update->type = 'welcome_call_update';
                        $update->content = $htmlContent;
                        $update->created_by = $actorId;
                        $update->timestamps = false;
                        $update->created_at = $timestamp;
                        $update->updated_at = $timestamp;
                        $update->save();
                    }

                    if ($updateTypeOption === 'production_update') {
                        $update = new ProjectUpdate();
                        $update->production_initiation_id = $project->id;
                        $update->type = 'production_update';
                        $update->content = $htmlContent;
                        $update->created_by = $actorId;
                        $update->timestamps = false;
                        $update->created_at = $timestamp;
                        $update->updated_at = $timestamp;
                        $update->save();
                    }

                    // Also set welcome_call_date on the project if not present
                    if (!$project->welcome_call_date) {
                        $project->timestamps = false;
                        $project->welcome_call_date = $timestamp->toDateString();
                        $project->welcome_call_time = $timestamp->format('H:i:s');
                        $project->save();
                    }

                    $updatedCount++;
                } catch (\Throwable $e) {
                    Log::error("Failed updating Welcome Call Completed on Project #{$project->id}: " . $e->getMessage());
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->info('=== Welcome Call Update Completed ===');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Approved Projects', $total],
                ['Updated With Welcome Call Completed', $updatedCount],
                ['Skipped (Already Had Welcome Call)', $skippedCount],
            ]
        );

        return self::SUCCESS;
    }
}