<?php

namespace App\Console\Commands;

use App\Models\CustomerCampaign;
use App\Models\Department;
use App\Models\ProductionInitiation;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigrateProductionCampaignsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:migrate-production-initiations
                            {--id= : Specific ProductionInitiation ID to migrate}
                            {--dry-run : Simulate migration without making database changes}
                            {--all : Include older historical ads and meta projects as well}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate campaign-related Production Initiation projects to CustomerCampaigns so they appear on the Campaigns page';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificId = $this->option('id');
        $dryRun = (bool) $this->option('dry-run');
        $includeAll = (bool) $this->option('all');

        $this->info('================================================================');
        $this->info('  Migrating Production Initiation Projects to Customer Campaigns');
        $this->info('================================================================');

        if ($dryRun) {
            $this->warn('DRY RUN MODE: No database changes will be persisted.');
        }

        // 1. Resolve Digital Marketing Department ID
        $dmDepartment = Department::query()
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%digital%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
            })
            ->first();

        $dmDeptId = $dmDepartment ? $dmDepartment->id : 3;
        $this->line("Digital Marketing Department ID: <comment>{$dmDeptId}</comment> ({$dmDepartment?->name})");

        // 2. Query Candidate Production Initiations
        $query = ProductionInitiation::query()
            ->with(['lead', 'leadProduct.product', 'department', 'product']);

        if ($specificId) {
            $query->where('id', (int) $specificId);
        } else {
            $query->where(function ($q) use ($dmDeptId, $includeAll) {
                // Criteria A: In Digital Marketing department
                $q->where('department_id', $dmDeptId)
                    // Criteria B: Has budget amount set
                    ->orWhere('lead_budget_amount', '>', 0)
                    // Criteria C: Has product marked with budget approval needed
                    ->orWhereHas('product', fn ($pq) => $pq->where('is_budget_approval_needed', true))
                    ->orWhereHas('leadProduct.product', fn ($pq) => $pq->where('is_budget_approval_needed', true));

                // Criteria D: If --all flag is passed or campaign-specific keywords
                if ($includeAll) {
                    $q->orWhereRaw('LOWER(product_name) LIKE ?', ['%campaign%'])
                        ->orWhereRaw('LOWER(product_name) LIKE ?', ['%google ads%'])
                        ->orWhereRaw('LOWER(product_name) LIKE ?', ['%meta%'])
                        ->orWhereRaw('LOWER(product_name) LIKE ?', ['%lead generation%'])
                        ->orWhereRaw('LOWER(product_name) LIKE ?', ['%digital marketing%']);
                }
            });
        }

        $initiations = $query->orderBy('id')->get();
        $totalCandidates = $initiations->count();

        $this->line("Found <comment>{$totalCandidates}</comment> candidate Production Initiation projects.\n");

        if ($totalCandidates === 0) {
            $this->info('No campaign-related production initiation projects to migrate.');
            return self::SUCCESS;
        }

        $createdCount = 0;
        $linkedCount = 0;
        $alreadyMigratedCount = 0;
        $claimedCampaignIds = [];
        $tableRows = [];

        foreach ($initiations as $pi) {
            $lead = $pi->lead;
            $companyName = trim((string) ($lead?->company_name ?: ($pi->company_name ?: ($pi->client_name ?: 'No Company'))));
            $productName = trim((string) ($pi->product_name ?: ($pi->leadProduct?->product?->product_name ?: 'Digital Marketing Campaign')));
            $budgetAmount = (float) ($pi->lead_budget_amount ?: ($pi->leadProduct?->total_price ?: 0));
            $budgetType = trim((string) ($pi->budget_amount_type ?: 'Weekly'));

            // Ensure department is Digital Marketing
            $needsDeptUpdate = (int) $pi->department_id !== (int) $dmDeptId;
            if ($needsDeptUpdate && ! $dryRun) {
                $pi->department_id = $dmDeptId;
                if (! $pi->product_id && $pi->leadProduct?->product_id) {
                    $pi->product_id = $pi->leadProduct->product_id;
                }
                $pi->save();
            }

            // Check if already linked to a CustomerCampaign via production_initiation_id
            $existingCampaign = CustomerCampaign::query()
                ->where('production_initiation_id', $pi->id)
                ->first();

            if ($existingCampaign) {
                $claimedCampaignIds[] = $existingCampaign->id;
                $alreadyMigratedCount++;
                $tableRows[] = [
                    'PI #' . $pi->id,
                    'LD-' . ($pi->lead_id ? str_pad((string) $pi->lead_id, 4, '0', STR_PAD_LEFT) : '—'),
                    \Illuminate\Support\Str::limit($companyName, 22),
                    \Illuminate\Support\Str::limit($productName, 22),
                    $budgetAmount > 0 ? '₹' . number_format($budgetAmount, 2) . ' (' . $budgetType . ')' : '—',
                    'CC #' . $existingCampaign->id,
                    '<info>Already Migrated</info>',
                ];
                continue;
            }

            // Check if there is an unlinked campaign on the same lead (not yet claimed)
            $unlinkedQuery = CustomerCampaign::query()
                ->where('lead_id', $pi->lead_id)
                ->whereNull('production_initiation_id')
                ->whereNotIn('id', $claimedCampaignIds);

            // Try exact budget match first if budget exists
            $unlinkedCampaign = null;
            if ($budgetAmount > 0) {
                $unlinkedCampaign = (clone $unlinkedQuery)
                    ->where('budget_amount', $budgetAmount)
                    ->first();
            }

            if (! $unlinkedCampaign) {
                $unlinkedCampaign = $unlinkedQuery->first();
            }

            if ($unlinkedCampaign) {
                $claimedCampaignIds[] = $unlinkedCampaign->id;

                if (! $dryRun) {
                    $unlinkedCampaign->update([
                        'production_initiation_id' => $pi->id,
                        'lead_product_id' => $pi->lead_product_id ?: $unlinkedCampaign->lead_product_id,
                        'budget_amount' => $unlinkedCampaign->budget_amount ?: ($budgetAmount > 0 ? $budgetAmount : null),
                        'budget_type' => $unlinkedCampaign->budget_type ?: $budgetType,
                    ]);
                }

                $linkedCount++;
                $tableRows[] = [
                    'PI #' . $pi->id,
                    'LD-' . ($pi->lead_id ? str_pad((string) $pi->lead_id, 4, '0', STR_PAD_LEFT) : '—'),
                    \Illuminate\Support\Str::limit($companyName, 22),
                    \Illuminate\Support\Str::limit($productName, 22),
                    $budgetAmount > 0 ? '₹' . number_format($budgetAmount, 2) . ' (' . $budgetType . ')' : '—',
                    'CC #' . $unlinkedCampaign->id,
                    '<comment>Linked to Lead CC</comment>',
                ];
                continue;
            }

            // Detect Platform
            $lowerProd = strtolower($productName);
            $platform = 'Facebook / Meta';
            if (str_contains($lowerProd, 'google')) {
                $platform = 'Google Ads';
            } elseif (str_contains($lowerProd, 'linkedin')) {
                $platform = 'LinkedIn';
            } elseif (str_contains($lowerProd, 'youtube')) {
                $platform = 'YouTube';
            }

            // Detect Status
            $approvalStatus = strtolower(trim((string) $pi->production_approval_status));
            $campaignStatus = in_array($approvalStatus, ['rejected', 'cancelled'], true) ? 'stopped' : 'active';

            // Dates
            $startDate = $pi->created_at ? $pi->created_at->toDateString() : Carbon::today()->toDateString();
            $endDate = null;
            if ($pi->total_working_days && (int) $pi->total_working_days > 0) {
                $endDate = Carbon::parse($startDate)->addDays((int) $pi->total_working_days)->toDateString();
            }

            $creatorId = $pi->initiated_by ?: ($pi->production_approval_reviewed_by ?: 1);

            $newCampaign = null;
            if (! $dryRun) {
                $newCampaign = CustomerCampaign::create([
                    'company_id' => $pi->company_id ?: ($lead?->company_id ?: 1),
                    'lead_id' => $pi->lead_id,
                    'lead_product_id' => $pi->lead_product_id,
                    'production_initiation_id' => $pi->id,
                    'campaign_name' => $productName,
                    'ad_account_name' => $companyName !== 'No Company' ? $companyName : null,
                    'platform' => $platform,
                    'status' => $campaignStatus,
                    'budget_amount' => $budgetAmount > 0 ? $budgetAmount : null,
                    'budget_type' => $budgetType ?: 'Weekly',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'remarks' => "Migrated from Production Initiation #{$pi->id}",
                    'created_by' => $creatorId,
                ]);
            }

            $createdCount++;
            $tableRows[] = [
                'PI #' . $pi->id,
                'LD-' . ($pi->lead_id ? str_pad((string) $pi->lead_id, 4, '0', STR_PAD_LEFT) : '—'),
                \Illuminate\Support\Str::limit($companyName, 22),
                \Illuminate\Support\Str::limit($productName, 22),
                $budgetAmount > 0 ? '₹' . number_format($budgetAmount, 2) . ' (' . $budgetType . ')' : '—',
                $newCampaign ? ('CC #' . $newCampaign->id) : '[New Campaign]',
                '<question>Created New</question>',
            ];
        }

        // Display summary table
        $this->table(
            ['PI ID', 'Lead ID', 'Company Name', 'Product', 'Budget', 'Campaign ID', 'Action'],
            $tableRows
        );

        $this->newLine();
        $this->info("Migration Summary:");
        $this->line("- Newly Created Campaigns: <comment>{$createdCount}</comment>");
        $this->line("- Linked to Existing Lead Campaigns: <comment>{$linkedCount}</comment>");
        $this->line("- Already Migrated Campaigns: <comment>{$alreadyMigratedCount}</comment>");
        $this->line("- Total Processed: <comment>" . ($createdCount + $linkedCount + $alreadyMigratedCount) . "</comment>");

        if ($dryRun) {
            $this->warn("\n[DRY RUN] No database records were modified. Run without --dry-run to apply changes.");
        } else {
            $this->info("\n[SUCCESS] Migration completed successfully. All projects now appear on the Campaigns page (/projects/campaigns).");
        }

        return self::SUCCESS;
    }
}
