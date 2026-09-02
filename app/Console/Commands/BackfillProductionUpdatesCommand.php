<?php

namespace App\Console\Commands;

use App\Models\ProductionInitiation;
use App\Models\User;
use App\Services\ProductionUpdateRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class BackfillProductionUpdatesCommand extends Command
{
    protected $signature = 'production:backfill-updates
                            {--id= : Specific ProductionInitiation ID to backfill}
                            {--force : Force generate updates even if already present}';

    protected $description = 'Backfill historical production initiation stages, person allocations, and form details into Production Updates';

    public function handle(ProductionUpdateRecorder $recorder): int
    {
        $specificId = $this->option('id');
        $force = (bool) $this->option('force');

        $query = ProductionInitiation::query()
            ->with([
                'department',
                'product',
                'lead',
                'initiatedBy',
                'reviewedBy',
                'productionApprovalReviewedBy',
                'projectAllocatedBy',
                'employeeAllocatedBy',
                'ovpAllocatedTo',
                'ovpAllocatedBy',
                'projectUpdates',
            ]);

        if ($specificId) {
            $query->where('id', $specificId);
        }

        $total = $query->count();
        if ($total === 0) {
            $this->warn('No production initiation records found.');
            return self::SUCCESS;
        }

        $this->info("Starting backfill for {$total} Production Initiation(s)..." . ($force ? ' [FORCE MODE]' : ''));

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $stats = [
            'initiations' => 0,
            'ovp_allocations' => 0,
            'ovp_reviews' => 0,
            'production_approvals' => 0,
            'tl_allocations' => 0,
            'team_allocations' => 0,
            'skipped' => 0,
        ];

        $query->chunk(100, function ($initiations) use ($recorder, $force, &$stats, $progressBar) {
            foreach ($initiations as $initiation) {
                $existingUpdates = $initiation->projectUpdates;
                $existingContent = $existingUpdates->pluck('content')->implode(' ');

                // 1. Production Initiation
                if ($force || !str_contains($existingContent, 'Production Initiation')) {
                    try {
                        $actor = $initiation->initiatedBy
                            ?: ($initiation->initiated_by ? User::withTrashed()->find($initiation->initiated_by) : null)
                            ?: ($initiation->lead?->createdBy ?: ($initiation->lead?->assignedTo ?: User::first()));
                        $recorder->recordProductionInitiation(
                            $initiation,
                            $actor,
                            $initiation->created_at ?: Carbon::now()
                        );
                        $stats['initiations']++;
                    } catch (\Throwable $e) {
                        Log::error("Backfill Initiation error on ID {$initiation->id}: " . $e->getMessage());
                    }
                } else {
                    $stats['skipped']++;
                }

                // 2. OVP Executive Allocation
                if ($initiation->ovp_allocated_to) {
                    if ($force || !str_contains($existingContent, 'OVP Executive Allocation')) {
                        try {
                            $actor = $initiation->ovpAllocatedBy
                                ?: ($initiation->ovp_allocated_by ? User::withTrashed()->find($initiation->ovp_allocated_by) : null)
                                ?: ($initiation->initiatedBy ?: User::first());
                            $recorder->recordOvpAllocation(
                                $initiation,
                                (int) $initiation->ovp_allocated_to,
                                $actor,
                                $initiation->ovp_allocated_at ?: ($initiation->created_at ?: Carbon::now())
                            );
                            $stats['ovp_allocations']++;
                        } catch (\Throwable $e) {
                            Log::error("Backfill OVP Allocation error on ID {$initiation->id}: " . $e->getMessage());
                        }
                    }
                }

                // 3. OVP Review (Approved or Rejected)
                $hasOvpReview = $initiation->reviewed_at
                    || in_array(strtolower((string) $initiation->status), ['approved', 'approval', 'rejected', 'reject'], true);
                if ($hasOvpReview) {
                    if ($force || !str_contains($existingContent, 'OVP Review')) {
                        try {
                            $isRejected = in_array(strtolower((string) $initiation->status), ['rejected', 'reject'], true);
                            $decision = $isRejected ? 'rejected' : 'approval';
                            $remarks = $isRejected ? ($initiation->production_approval_remarks ?: null) : null;
                            $actor = $initiation->reviewedBy
                                ?: ($initiation->reviewed_by ? User::withTrashed()->find($initiation->reviewed_by) : null)
                                ?: ($initiation->ovpAllocatedTo ?: ($initiation->ovpAllocatedBy ?: User::first()));
                            $recorder->recordOvpReview(
                                $initiation,
                                $decision,
                                $remarks,
                                $actor,
                                $initiation->reviewed_at ?: ($initiation->created_at ?: Carbon::now())
                            );
                            $stats['ovp_reviews']++;
                        } catch (\Throwable $e) {
                            Log::error("Backfill OVP Review error on ID {$initiation->id}: " . $e->getMessage());
                        }
                    }
                }

                // 4. Production Approval (Approved or Rejected)
                $hasProdApproval = $initiation->production_approval_reviewed_at
                    || in_array(strtolower((string) $initiation->production_approval_status), ['approval', 'approved', 'rejected', 'reject'], true);
                if ($hasProdApproval) {
                    if ($force || !str_contains($existingContent, 'Production Approval')) {
                        try {
                            $isRejected = in_array(strtolower((string) $initiation->production_approval_status), ['rejected', 'reject'], true);
                            $decision = $isRejected ? 'rejected' : 'approval';
                            $remarks = $initiation->production_approval_remarks ?: null;
                            $budgetData = null;
                            if (!$isRejected && $initiation->lead_budget_amount) {
                                $budgetData = [
                                    'lead_budget_amount' => $initiation->lead_budget_amount,
                                    'budget_amount_type' => $initiation->budget_amount_type,
                                ];
                            }
                            $actor = $initiation->productionApprovalReviewedBy
                                ?: ($initiation->production_approval_reviewed_by ? User::withTrashed()->find($initiation->production_approval_reviewed_by) : null)
                                ?: ($initiation->reviewedBy ?: User::first());
                            $recorder->recordProductionApproval(
                                $initiation,
                                $decision,
                                $remarks,
                                $actor,
                                $budgetData,
                                $initiation->production_approval_reviewed_at ?: ($initiation->updated_at ?: Carbon::now())
                            );
                            $stats['production_approvals']++;
                        } catch (\Throwable $e) {
                            Log::error("Backfill Production Approval error on ID {$initiation->id}: " . $e->getMessage());
                        }
                    }
                }

                // 5. TL Allocation
                $tlIds = (array) ($initiation->project_allocated_tl_user_ids ?? []);
                if (empty($tlIds) && !empty($initiation->tl_employee_allocations)) {
                    $tlIds = array_keys($initiation->tl_employee_allocations);
                }
                $tlIds = array_values(array_filter(array_map('intval', $tlIds)));

                if (!empty($tlIds)) {
                    if ($force || !str_contains($existingContent, 'Team Lead Allocation')) {
                        try {
                            $actor = $initiation->projectAllocatedBy
                                ?: ($initiation->project_allocated_by ? User::withTrashed()->find($initiation->project_allocated_by) : null)
                                ?: ($initiation->productionApprovalReviewedBy ?: User::first());
                            $recorder->recordTlAllocation(
                                $initiation,
                                $tlIds,
                                $actor,
                                $initiation->project_allocated_at ?: ($initiation->updated_at ?: Carbon::now())
                            );
                            $stats['tl_allocations']++;
                        } catch (\Throwable $e) {
                            Log::error("Backfill TL Allocation error on ID {$initiation->id}: " . $e->getMessage());
                        }
                    }
                }

                // 6. Team Member Allocation
                $empIds = (array) ($initiation->project_allocated_employee_user_ids ?? []);
                if (empty($empIds) && !empty($initiation->tl_employee_allocations)) {
                    foreach ($initiation->tl_employee_allocations as $alloc) {
                        $empIds = array_merge($empIds, (array) ($alloc['employee_user_ids'] ?? []));
                    }
                }
                $empIds = array_values(array_unique(array_filter(array_map('intval', $empIds))));

                if (!empty($empIds)) {
                    if ($force || !str_contains($existingContent, 'Team Member Allocation')) {
                        try {
                            $actor = $initiation->employeeAllocatedBy
                                ?: ($initiation->employee_allocated_by ? User::withTrashed()->find($initiation->employee_allocated_by) : null)
                                ?: ($initiation->projectAllocatedBy ?: User::first());
                            $recorder->recordTeamAllocation(
                                $initiation,
                                $empIds,
                                $actor,
                                $initiation->employee_allocated_at ?: ($initiation->updated_at ?: Carbon::now())
                            );
                            $stats['team_allocations']++;
                        } catch (\Throwable $e) {
                            Log::error("Backfill Team Allocation error on ID {$initiation->id}: " . $e->getMessage());
                        }
                    }
                }

                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->info('=== Backfill Completed Successfully ===');
        $this->table(
            ['Stage', 'Updates Created'],
            [
                ['Production Initiation Updates', $stats['initiations']],
                ['OVP Executive Allocations', $stats['ovp_allocations']],
                ['OVP Review Updates', $stats['ovp_reviews']],
                ['Production Approval Updates', $stats['production_approvals']],
                ['TL Allocation Updates', $stats['tl_allocations']],
                ['Team Member Allocation Updates', $stats['team_allocations']],
                ['Skipped (Already Existed)', $stats['skipped']],
            ]
        );

        return self::SUCCESS;
    }
}
