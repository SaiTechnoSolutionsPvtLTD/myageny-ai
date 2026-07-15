<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProductionDataMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:production-data-migration 
                            {--dry-run : Preview changes without persisting} 
                            {--limit= : Limit the number of records migrated for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate production data (initiations) and updates from legacy mysql2 to myagency mysql database';

    // In-memory caches for mappings
    private $userMappingCache = [];
    private $productMappingCache = [];
    private $leadIdMap = [];
    private $leadProductIdMap = [];

    // Maps to link legacy production/projects to new production_initiations
    private $projectIdToNewIdMap = [];
    private $leadIdToNewIdMap = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting production data migration...");

        // Ensure database connections are valid
        try {
            DB::connection('mysql2')->getPdo();
            DB::connection('mysql')->getPdo();
        } catch (\Exception $e) {
            $this->error("Failed to establish database connections. Details: " . $e->getMessage());
            return self::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        if ($dryRun) {
            $this->warn("=========================================");
            $this->warn("       DRY RUN MODE - NO CHANGES WILL BE SAVED");
            $this->warn("=========================================");
        }

        // 1. Fetch production-ready lead products from mysql2 (is_production_moved = 1)
        $query = DB::connection('mysql2')->table('leadproducts')
            ->where('is_production_moved', 1)
            ->orderBy('id', 'asc');

        if ($limit = $this->option('limit')) {
            $query->limit((int)$limit);
            $this->info("Limited processing to {$limit} records.");
        }

        $legacyLeadProducts = $query->get();

        if ($legacyLeadProducts->isEmpty()) {
            $this->warn("No lead products found with is_production_moved = 1.");
            return self::SUCCESS;
        }

        $this->info("Found " . $legacyLeadProducts->count() . " production-ready lead products to process.");

        // Progress bar for initiations
        $bar = $this->output->createProgressBar($legacyLeadProducts->count());
        $bar->start();

        $initiatedCount = 0;
        $skippedCount = 0;

        // Default workflow snapshot template
        $defaultWorkflowSnapshot = '{"stages": [{"key": "production_initiation", "step": "Stage 1", "notes": [], "title": "Production Initiation", "groups": [{"label": "Initiation Roles", "roles": ["Branch Admin", "Designer Intern", "Company Admin"]}], "description": "The entry point where the production request is initiated by the business or intake team."}, {"key": "ovp_team_review", "step": "Stage 2", "notes": [], "title": "OVP Team Review", "groups": [{"label": "OVP Review Roles", "roles": ["Junior Web Developer"]}, {"label": "Business Team Roles", "roles": []}], "description": "The stage where the OVP team reviews and verifies the incoming request."}, {"key": "production_approval_team", "step": "Stage 3", "notes": [], "title": "Production Approval Team", "groups": [{"label": "Approval Roles", "roles": ["Company Admin"]}], "description": "The stage for approval-side roles and checklist verification after OVP review."}, {"key": "project_coordinator", "step": "Stage 4", "notes": [], "title": "Project Coordinator", "groups": [{"label": "Project Coordinator Roles", "roles": ["Junior Web Developer"]}], "description": "The stage where the approved request is received and the project scope and timeline are coordinated."}, {"key": "allocation_multiple_tl_split", "step": "Stage 5 & 6", "notes": [], "title": "Allocation and Multiple TL Split", "groups": [{"label": "TL Roles", "roles": ["Senior Mobile App Developer", "Customer Support Team TL"]}, {"label": "Developer Roles", "roles": []}], "description": "Configure the required roles for the Project Coordinator to TL to Developer allocation flow."}]}';

        foreach ($legacyLeadProducts as $legacyLp) {
            DB::connection('mysql')->beginTransaction();
            try {
                // 1. Resolve/Migrate Lead
                $newLeadId = $this->getOrMigrateLead($legacyLp->leadid);
                if (!$newLeadId) {
                    $this->error("\nCould not resolve lead for legacy leadid: {$legacyLp->leadid}. Skipping.");
                    $skippedCount++;
                    DB::connection('mysql')->rollBack();
                    $bar->advance();
                    continue;
                }

                // 2. Resolve/Migrate Lead Product
                $newLeadProductId = $this->getOrMigrateLeadProduct($legacyLp->id, $newLeadId);
                if (!$newLeadProductId) {
                    $this->error("\nCould not resolve lead product for legacy ID: {$legacyLp->id}. Skipping.");
                    $skippedCount++;
                    DB::connection('mysql')->rollBack();
                    $bar->advance();
                    continue;
                }

                // 3. Fetch corresponding production row from mysql2
                $legacyProduction = DB::connection('mysql2')->table('production')
                    ->where('leadproductid', $legacyLp->id)
                    ->first();

                // 4. Map user allocations if production record exists
                $mappedTlUserId = null;
                $mappedExecutiveUserId = null;
                $mappedCstTlUserId = null;
                $mappedCstTmUserId = null;

                $clientName = '';
                $companyName = '';
                $workingDays = 0;
                $deliveryDate = null;
                $createdAt = $legacyLp->created_at ?: now();
                $updatedAt = $legacyLp->updated_at ?: now();
                $projectExecutionStatus = 'ontrack';
                $projectType = 'DEVELOPMENT';
                $productNameFromProduction = null;
                $remarks = '';

                if ($legacyProduction) {
                    $mappedTlUserId = $this->mapUserId($legacyProduction->assigned_tl);
                    $mappedExecutiveUserId = $this->mapUserId($legacyProduction->assigned_to);
                    $mappedCstTlUserId = $this->mapUserId($legacyProduction->assigned_cst_tl);
                    $mappedCstTmUserId = $this->mapUserId($legacyProduction->assigned_cst_tm);

                    $clientName = $legacyProduction->client_name;
                    $companyName = $legacyProduction->company_name;
                    $workingDays = (int)$legacyProduction->working_days;
                    $deliveryDate = $legacyProduction->delivery_date;
                    $projectType = $legacyProduction->project_type ?: 'DEVELOPMENT';
                    $productNameFromProduction = $legacyProduction->product_name;
                    $remarks = $legacyProduction->remarks ?: '';

                    if ($legacyProduction->created_at) $createdAt = $legacyProduction->created_at;
                    if ($legacyProduction->updated_at) $updatedAt = $legacyProduction->updated_at;

                    if ($legacyProduction->overall_stat) {
                        $projectExecutionStatus = strtolower(trim($legacyProduction->overall_stat));
                        // normalize if needed, e.g. "ontrack"
                        if ($projectExecutionStatus === 'on track') {
                            $projectExecutionStatus = 'ontrack';
                        }
                    }
                }

                $productId = $this->mapProductId($legacyLp->productname);
                
                // Fallback to name from mysql2 products table
                $productRecord = DB::connection('mysql2')->table('products')->where('id', $legacyLp->productname)->first();
                $productName = $productNameFromProduction ?: ($productRecord ? $productRecord->productname : 'Unknown Product');

                // Determine department
                $departmentId = $this->resolveDepartmentId($projectType);

                // Build TL and employee allocation snapshots
                $projectAllocatedTlUserIds = array_values(array_filter(array_unique([$mappedTlUserId, $mappedCstTlUserId])));
                $projectAllocatedEmployeeUserIds = array_values(array_filter(array_unique([$mappedExecutiveUserId, $mappedCstTmUserId])));

                $tlAllocations = [];
                if ($mappedTlUserId) {
                    $tlAllocations[$mappedTlUserId] = [
                        'status' => 'allocated',
                        'tl_user_id' => $mappedTlUserId,
                        'allocated_at' => $createdAt,
                        'allocated_by' => 1,
                        'employee_user_ids' => $mappedExecutiveUserId ? [$mappedExecutiveUserId] : []
                    ];
                }
                if ($mappedCstTlUserId) {
                    $tlAllocations[$mappedCstTlUserId] = [
                        'status' => 'allocated',
                        'tl_user_id' => $mappedCstTlUserId,
                        'allocated_at' => $createdAt,
                        'allocated_by' => 1,
                        'employee_user_ids' => $mappedCstTmUserId ? [$mappedCstTmUserId] : []
                    ];
                }

                $customForm = [
                    ['type' => 'text', 'label' => 'Company Name', 'value' => $companyName, 'field_id' => 1, 'field_name' => 'ovp_company_name'],
                    ['type' => 'text', 'label' => 'Working Days', 'value' => (string)$workingDays, 'field_id' => 3, 'field_name' => 'ovp_working_days']
                ];

                $allocationStatus = !empty($projectAllocatedTlUserIds) ? 'allocated' : 'pending';
                $employeeAllocationStatus = !empty($projectAllocatedEmployeeUserIds) ? 'allocated' : 'pending';

                // Insert production initiation row
                $prodInitiationData = [
                    'company_id' => 1,
                    'lead_id' => $newLeadId,
                    'lead_product_id' => $newLeadProductId,
                    'product_id' => $productId,
                    'department_id' => $departmentId,
                    'product_name' => $productName,
                    'total_working_days' => $workingDays,
                    'ui_available' => 0,
                    'requirements' => $remarks ?: 'Submitted via production customization form.',
                    'attachment_path' => null,
                    'attachment_name' => null,
                    'workflow_snapshot' => $defaultWorkflowSnapshot,
                    'custom_form_data' => json_encode($customForm),
                    'status' => 'approved',
                    'ovp_allocation_status' => 'submitted',
                    'ovp_allocated_to' => 1,
                    'ovp_allocated_by' => 1,
                    'ovp_allocated_at' => $createdAt,
                    'welcome_call_date' => null,
                    'welcome_call_time' => null,
                    'client_name' => $clientName,
                    'company_name' => $companyName,
                    'reviewed_at' => $createdAt,
                    'reviewed_by' => 1,
                    'production_approval_status' => 'approve',
                    'production_approval_remarks' => 'Database migration',
                    'production_approval_reviewed_at' => $createdAt,
                    'production_approval_reviewed_by' => 1,
                    'project_allocation_status' => $allocationStatus,
                    'project_allocated_at' => $allocationStatus === 'allocated' ? $createdAt : null,
                    'project_allocated_by' => $allocationStatus === 'allocated' ? 1 : null,
                    'project_allocated_tl_user_ids' => json_encode($projectAllocatedTlUserIds),
                    'tl_employee_allocations' => json_encode($tlAllocations),
                    'employee_allocation_status' => $employeeAllocationStatus,
                    'employee_allocated_at' => $employeeAllocationStatus === 'allocated' ? $createdAt : null,
                    'employee_allocated_by' => $employeeAllocationStatus === 'allocated' ? 1 : null,
                    'project_delivery_date' => $deliveryDate,
                    'project_execution_status' => $projectExecutionStatus,
                    'project_allocated_employee_user_ids' => json_encode($projectAllocatedEmployeeUserIds),
                    'initiated_by' => 1,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];

                $newProductionInitiationId = null;
                if (!$dryRun) {
                    $newProductionInitiationId = DB::connection('mysql')->table('production_initiations')->insertGetId($prodInitiationData);
                } else {
                    $newProductionInitiationId = $initiatedCount + 1; // dummy ID for dry run
                }

                // Store mappings to help map updates
                if ($legacyProduction && $legacyProduction->project_id) {
                    $this->projectIdToNewIdMap[$legacyProduction->project_id] = $newProductionInitiationId;
                }
                $this->leadIdToNewIdMap[$legacyLp->leadid] = $newProductionInitiationId;

                $initiatedCount++;
                DB::connection('mysql')->commit();
            } catch (\Exception $e) {
                DB::connection('mysql')->rollBack();
                $this->error("\nError migrating lead product ID {$legacyLp->id}: " . $e->getMessage());
                $skippedCount++;
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info("Production Initiations migration finished. Migrated: {$initiatedCount}, Skipped: {$skippedCount}.");

        // 2. Fetch legacy production updates and migrate
        $this->newLine();
        $this->info("Migrating production updates...");

        // Fetch all legacy updates
        $legacyUpdates = DB::connection('mysql2')->table('production_update')
            ->orderBy('id', 'asc')
            ->get();

        if ($legacyUpdates->isEmpty()) {
            $this->warn("No legacy updates found to migrate.");
        } else {
            $updateBar = $this->output->createProgressBar($legacyUpdates->count());
            $updateBar->start();

            $updatesMigratedCount = 0;
            $updatesSkippedCount = 0;

            foreach ($legacyUpdates as $legacyUpdate) {
                DB::connection('mysql')->beginTransaction();
                try {
                    $newProdInitId = null;

                    // 1. Try mapping by project_id (e.g. PRO0001)
                    if (!empty($legacyUpdate->project_id)) {
                        $newProdInitId = $this->projectIdToNewIdMap[$legacyUpdate->project_id] ?? null;
                    }

                    // 2. Fallback to mapping by legacy lead_id
                    if (!$newProdInitId && !empty($legacyUpdate->lead_id)) {
                        $newProdInitId = $this->leadIdToNewIdMap[$legacyUpdate->lead_id] ?? null;
                    }

                    // If still not found, we can't link it to any production initiation, so skip
                    if (!$newProdInitId) {
                        $updatesSkippedCount++;
                        DB::connection('mysql')->rollBack();
                        $updateBar->advance();
                        continue;
                    }

                    $mappedUserId = $this->mapUserId($legacyUpdate->user_id) ?? 1;
                    $updateType = $this->mapUpdateType($legacyUpdate->update_type);

                    $projectUpdateData = [
                        'production_initiation_id' => $newProdInitId,
                        'type' => $updateType,
                        'content' => $legacyUpdate->prod_update,
                        'created_by' => $mappedUserId,
                        'created_at' => $legacyUpdate->created_at ?: now(),
                        'updated_at' => $legacyUpdate->updated_at ?: now(),
                    ];

                    if (!$dryRun) {
                        DB::connection('mysql')->table('project_updates')->insert($projectUpdateData);
                    }

                    $updatesMigratedCount++;
                    DB::connection('mysql')->commit();
                } catch (\Exception $e) {
                    DB::connection('mysql')->rollBack();
                    $this->error("\nError migrating update ID {$legacyUpdate->id}: " . $e->getMessage());
                    $updatesSkippedCount++;
                }
                $updateBar->advance();
            }
            $updateBar->finish();
            $this->newLine();
            $this->info("Updates migration finished. Migrated: {$updatesMigratedCount}, Skipped/Unmatched: {$updatesSkippedCount}.");
        }

        if ($dryRun) {
            $this->warn("=========================================");
            $this->warn("   DRY RUN COMPLETE - NO DATABASE EDITS SAVED");
            $this->warn("=========================================");
        } else {
            $this->info("Migration completed successfully!");
        }

        return self::SUCCESS;
    }

    /**
     * Map legacy user ID to target user ID
     */
    private function mapUserId($mysql2UserId)
    {
        if (!$mysql2UserId) {
            return null;
        }

        if (isset($this->userMappingCache[$mysql2UserId])) {
            return $this->userMappingCache[$mysql2UserId];
        }

        $legacyUser = DB::connection('mysql2')->table('users')->where('id', $mysql2UserId)->first();
        if ($legacyUser) {
            // Match by email first
            if (!empty($legacyUser->email)) {
                $targetUser = DB::connection('mysql')->table('users')
                    ->where('email', $legacyUser->email)
                    ->first();
                if ($targetUser) {
                    $this->userMappingCache[$mysql2UserId] = $targetUser->id;
                    return $targetUser->id;
                }
            }

            // Match by name
            if (!empty($legacyUser->name)) {
                $targetUser = DB::connection('mysql')->table('users')
                    ->where('name', $legacyUser->name)
                    ->first();
                if ($targetUser) {
                    $this->userMappingCache[$mysql2UserId] = $targetUser->id;
                    return $targetUser->id;
                }
            }
        }

        // Default fallback if not found: return null so caller can handle default (typically user 1)
        return null;
    }

    /**
     * Map legacy product ID to target product ID
     */
    private function mapProductId($mysql2ProductId)
    {
        if (!$mysql2ProductId) {
            return null;
        }

        if (isset($this->productMappingCache[$mysql2ProductId])) {
            return $this->productMappingCache[$mysql2ProductId];
        }

        $legacyProduct = DB::connection('mysql2')->table('products')->where('id', $mysql2ProductId)->first();
        if ($legacyProduct) {
            $targetProduct = DB::connection('mysql')->table('products')
                ->where('product_name', $legacyProduct->productname)
                ->first();
            if ($targetProduct) {
                $this->productMappingCache[$mysql2ProductId] = $targetProduct->id;
                return $targetProduct->id;
            }
        }

        return null;
    }

    /**
     * Get or migrate legacy lead to active database
     */
    private function getOrMigrateLead($legacyLeadId)
    {
        if (!$legacyLeadId) {
            return null;
        }

        if (isset($this->leadIdMap[$legacyLeadId])) {
            return $this->leadIdMap[$legacyLeadId];
        }

        $legacyLead = DB::connection('mysql2')->table('leads')->where('id', $legacyLeadId)->first();
        if (!$legacyLead) {
            return null;
        }

        // Check if lead already exists in target DB (by mobile number, email, or client name)
        $query = DB::connection('mysql')->table('leads');
        if (!empty($legacyLead->MobileNumber)) {
            $query->where('mobile_number', $legacyLead->MobileNumber);
        } elseif (!empty($legacyLead->EmailID)) {
            $query->where('email', $legacyLead->EmailID);
        } else {
            $query->where('contact_name', $legacyLead->ClientName);
        }

        $existingLead = $query->first();
        if ($existingLead) {
            $this->leadIdMap[$legacyLeadId] = $existingLead->id;
            return $existingLead->id;
        }

        // Migrate and insert new lead
        $assignedUserId = $this->mapUserId($legacyLead->assigned_to) ?? 1;

        // Resolve source and status
        $sourceName = $legacyLead->LeadSource ?? 'Online';
        $sourceRecord = DB::connection('mysql')->table('lead_sources')->where('name', $sourceName)->first();
        $sourceId = $sourceRecord ? $sourceRecord->id : 1;

        $statusName = $legacyLead->Status ?? 'New';
        $statusRecord = DB::connection('mysql')->table('lead_statuses')->where('name', $statusName)->first();
        $statusId = $statusRecord ? $statusRecord->id : 1;

        $newLeadId = DB::connection('mysql')->table('leads')->insertGetId([
            'company_name' => $legacyLead->CompanyName ?: 'NA',
            'company_id' => 1,
            'contact_name' => $legacyLead->ClientName ?: 'NA',
            'lead_date' => $legacyLead->EntryDate,
            'mobile_number' => $legacyLead->MobileNumber,
            'email' => $legacyLead->EmailID,
            'lead_source' => $sourceName,
            'lead_source_id' => $sourceId,
            'lead_status' => $statusId,
            'remarks' => $legacyLead->Remarks,
            'branch_id' => 1,
            'assigned_to' => $assignedUserId,
            'created_by' => 1,
            'created_at' => $legacyLead->created_at ?: now(),
            'updated_at' => $legacyLead->updated_at ?: now(),
        ]);

        $this->leadIdMap[$legacyLeadId] = $newLeadId;
        return $newLeadId;
    }

    /**
     * Get or migrate legacy lead product to active database
     */
    private function getOrMigrateLeadProduct($legacyLpId, $newLeadId)
    {
        if (!$legacyLpId) {
            return null;
        }

        if (isset($this->leadProductIdMap[$legacyLpId])) {
            return $this->leadProductIdMap[$legacyLpId];
        }

        $legacyLp = DB::connection('mysql2')->table('leadproducts')->where('id', $legacyLpId)->first();
        if (!$legacyLp) {
            return null;
        }

        $productId = $this->mapProductId($legacyLp->productname);

        // Check if lead product already exists in target DB
        $existingLp = DB::connection('mysql')->table('lead_products')
            ->where('lead_id', $newLeadId)
            ->where('product_id', $productId)
            ->first();

        if ($existingLp) {
            $this->leadProductIdMap[$legacyLpId] = $existingLp->id;
            return $existingLp->id;
        }

        $productRecord = DB::connection('mysql2')->table('products')->where('id', $legacyLp->productname)->first();
        $productName = $productRecord ? $productRecord->productname : 'Unknown Product';

        $statusName = $legacyLp->status ?? 'converted';
        $statusRecord = DB::connection('mysql')->table('lead_statuses')->where('name', $statusName)->first();
        $statusId = $statusRecord ? $statusRecord->id : 1;

        // Insert new lead product
        $newLpId = DB::connection('mysql')->table('lead_products')->insertGetId([
            'company_id' => 1,
            'lead_id' => $newLeadId,
            'product_id' => $productId,
            'product_name' => $productName,
            'unit_price' => $legacyLp->totalcost ?? 0,
            'quantity' => 1,
            'amount_paid' => $legacyLp->receivedcost ?? 0,
            'payment_status' => (strtolower($statusName) === 'converted' || $legacyLp->pendingcost <= 0) ? 'paid' : 'pending',
            'payment_date' => $legacyLp->paymentdate,
            'created_at' => $legacyLp->created_at ?: now(),
            'updated_at' => $legacyLp->updated_at ?: now(),
            'product_status' => $legacyLp->status ?? 'converted',
            'lead_status_id' => $statusId,
            'remarks' => $legacyLp->producttype,
            'created_by' => 1,
        ]);

        $this->leadProductIdMap[$legacyLpId] = $newLpId;
        return $newLpId;
    }

    /**
     * Map project_type to department ID
     */
    private function resolveDepartmentId($projectType)
    {
        $type = strtoupper(trim((string)$projectType));
        if (str_contains($type, 'DEV') || str_contains($type, 'PROG')) {
            return 1; // Development
        }
        if (str_contains($type, 'DESIGN') || str_contains($type, 'UI') || str_contains($type, 'UX')) {
            return 2; // Designing
        }
        if (str_contains($type, 'DM') || str_contains($type, 'MARKET')) {
            return 3; // Digital Marketing
        }
        if (str_contains($type, 'SALES')) {
            return 4; // Sales
        }
        if (str_contains($type, 'CST') || str_contains($type, 'SUPPORT')) {
            return 5; // Customer Support Team
        }
        return 1; // Default fallback to 1
    }

    /**
     * Map legacy update type (int/string) to active database update type (string)
     */
    private function mapUpdateType($legacyType)
    {
        switch (trim((string)$legacyType)) {
            case '2':
            case '3':
                return 'meeting_update';
            case '1':
            case '4':
            default:
                return 'production_update';
        }
    }
}
