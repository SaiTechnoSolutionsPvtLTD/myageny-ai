<?php

namespace App\Console\Commands;

use App\Models\LeadSource;
use App\Models\LeadStatus;
use App\Models\OutcomeCategory;
use App\Models\OutcomeSubCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeadAndLeadProductMigrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:lead-and-lead-product-migration-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $leads = DB::connection('mysql2')
            ->table('leads')
            ->orderBy('id', 'desc')
            ->take(1000)
            ->get();

        $leadIdMap = [];
        $leadProductIdMap = [];

        foreach($leads as $lead)
        {
            // Get lead source from source database and match with master
            $sourceNameFromDb = $lead->LeadSource ?? 'Online';
            
            // Find matching lead source in master, if not found use 'Online'
            $leadSourceRecord = LeadSource::where('name', $sourceNameFromDb)->first();
            if (!$leadSourceRecord) {
                $leadSourceRecord = LeadSource::firstOrCreate(['name' => 'Online']);
            }

            // Get lead status from source database and match with master
            $statusNameFromDb = $lead->status ?? 'New';

            // Find matching lead status in master, if not found use 'New'
            $leadStatusRecord = LeadStatus::where('name', $statusNameFromDb)->first();
            if (!$leadStatusRecord) {
                $leadStatusRecord = LeadStatus::firstOrCreate(['name' => 'New']);
            }

            // Get assigned user from mysql2 by ID and find in myagency users table
            $assignedUserId = 1; // Default to user 1
            if ($lead->assigned_to) {
                // Get user from mysql2 by ID
                $sourceUser = DB::connection('mysql2')->table('users')
                    ->where('id', $lead->assigned_to)
                    ->first();

                if ($sourceUser && $sourceUser->name) {
                    // Find matching user in myagency database by name
                    $targetUser = DB::connection('mysql')->table('users')
                        ->where('name', $sourceUser->name)
                        ->first();

                    if ($targetUser) {
                        $assignedUserId = $targetUser->id;
                    }
                }
            }

            // Insert lead
            $newLeadId = DB::connection('mysql')->table('leads')->insertGetId([
                'company_name' => $lead->CompanyName,
                'company_id' => 1,
                'contact_name' => $lead->ClientName,
                'lead_date' => $lead->EntryDate,
                'mobile_number' => $lead->MobileNumber,
                'email' => $lead->EmailID,
                'lead_source' => $leadSourceRecord->name,
                'lead_source_id' => $leadSourceRecord->id,
                'lead_status' => $leadStatusRecord->id,
                'remarks' => $lead->Remarks,
                'branch_id' => 1,
                'assigned_to' => $assignedUserId,
                'created_by' => 1,
                'created_at' => $lead->created_at,
            ]);

            $leadIdMap[$lead->id] = $newLeadId;

            // Migrate lead products
            $leadProducts = DB::connection('mysql2')->table('leadproducts')
                ->where('leadid', $lead->id)
                ->get();

            foreach($leadProducts as $leadProduct) {
                // Get product details from mysql2 products table
                $sourceProduct = DB::connection('mysql2')->table('products')
                    ->where('id', $leadProduct->productname)
                    ->first();

                $unitPrice = $sourceProduct ? ($sourceProduct->rate ?? 0) : 0;

                // Match product name from mysql2 with myagency products table
                $productRecord = DB::connection('mysql')
                    ->table('products')
                    ->where('product_name', $leadProduct->productname)
                    ->first();

                $productId = $productRecord ? $productRecord->id : null;

                $leadStatusId = LeadStatus::where('name', $leadProduct->status)->first()->id ?? 1;

                $newLeadProductId = DB::connection('mysql')->table('lead_products')->insertGetId([
                    'company_id' => 1,
                    'lead_id' => $newLeadId,
                    'product_id' => $productId ?? null,
                    'product_name' => $sourceProduct->productname. "/".$sourceProduct->id ?? null,
                    'unit_price' => $unitPrice,
                    'quantity' => 1,
                    // 'total_price' => $leadProduct->totalcost ?? 0,
                    'amount_paid' =>  0,
                    'payment_status' => 'pending',
                    'payment_date' => $leadProduct->paymentdate,
                    'product_status' => $leadProduct->status,
                    'lead_status_id' => $leadStatusId,
                    'lead_source_id' => $leadSourceRecord->id,
                    'remarks' => $leadProduct->producttype,
                    'created_by' => 1,
                    'created_at' => $leadProduct->created_at,
                ]);

                $leadProductIdMap[$leadProduct->id] = $newLeadProductId;
            }
        }

        // Migrate notes to lead_call_updates
        $this->migrateNotes($leadIdMap);

        // Migrate payments to lead_product_payments
        $this->migratePayments($leadIdMap, $leadProductIdMap);
    }

    /**
     * Migrate notes from mysql2 to lead_call_updates
     */
    private function migrateNotes(array $leadIdMap)
    {
        if (empty($leadIdMap)) {
            return;
        }

        $notes = DB::connection('mysql2')
            ->table('notes')
            ->whereIn('leadid', array_keys($leadIdMap))
            ->orderBy('id')
            ->get();

        $userMap = []; // In-memory cache for user mappings

        foreach ($notes as $note) {
            $newLeadId = $leadIdMap[$note->leadid] ?? null;
            if (!$newLeadId) {
                continue; // Skip if lead mapping is not available
            }

            // Get user from mysql2 and map to myagency
            $callUserId = 1; // Default to user 1
            if ($note->userid) {
                if (isset($userMap[$note->userid])) {
                    $callUserId = $userMap[$note->userid];
                } else {
                    $sourceUser = DB::connection('mysql2')->table('users')
                        ->where('id', $note->userid)
                        ->first();

                    if ($sourceUser && $sourceUser->name) {
                        $targetUser = DB::connection('mysql')->table('users')
                            ->where('name', $sourceUser->name)
                            ->first();

                        if ($targetUser) {
                            $callUserId = $targetUser->id;
                        }
                    }
                    $userMap[$note->userid] = $callUserId;
                }
            }

            // Map old callinfostatus to outcome category and subcategory names from master
            $mappedNames = $this->getOutcomeMappingNames($note->callinfostatus);
            $categoryName = $mappedNames['category'];
            $subCategoryName = $mappedNames['subcategory'] ?? null;

            // Find or create outcome category in myagency
            $outcomeCategoryId = null;
            if ($categoryName) {
                $categoryRecord = OutcomeCategory::firstOrCreate([
                    'name' => $categoryName,
                    'company_id' => 1
                ]);
                $outcomeCategoryId = $categoryRecord->id;
            }

            // Find or create outcome subcategory in myagency
            $outcomeSubCategoryId = null;
            if ($subCategoryName && $outcomeCategoryId) {
                $subCategoryRecord = OutcomeSubCategory::firstOrCreate([
                    'category_id' => $outcomeCategoryId,
                    'name' => $subCategoryName,
                    'company_id' => 1
                ]);
                $outcomeSubCategoryId = $subCategoryRecord->id;
            }

            // Create called_at timestamp from notification_date and notification_time
            $calledDate = $note->notification_date ?: ($note->created_at ? Carbon::parse($note->created_at)->toDateString() : today()->toDateString());
            $calledAt = $calledDate . ' ' . ($note->notification_time ?? '00:00:00');
            
            $nextFollowDate = Carbon::parse($calledDate)->addDays(2)->toDateString();

            // Insert into lead_call_updates
            DB::connection('mysql')->table('lead_call_updates')->insert([
                'company_id' => 1,
                'lead_id' => $newLeadId,
                'user_id' => 2,
                'called_at' => $calledAt,
                'call_type' => 'outgoing',
                'duration_minutes' => 0,
                'outcome' => 7,
                'notes' => $note->notes_data,
                'next_follow_up' => $nextFollowDate,
                'followup_time' => $calledAt,
                'outcome_subcategory' => 8,
                'created_at' => $note->created_at,
                'updated_at' => $note->created_at,
            ]);
        }
    }

    /**
     * Map callinfostatus to outcome category and subcategory names
     */
    private function getOutcomeMappingNames($callInfoStatus)
    {
        switch ((int) $callInfoStatus) {
            case 1:
                return ['category' => 'Interested', 'subcategory' => 'Buy Product'];
            case 2:
                return ['category' => 'Not Interested', 'subcategory' => null];
            case 3:
                return ['category' => 'Callback Request', 'subcategory' => null];
            case 4:
                return ['category' => 'Interested', 'subcategory' => 'Scheduled Meeting'];
            case 5:
            default:
                return ['category' => 'Follow-up', 'subcategory' => null];
        }
    }

    /**
     * Migrate payments from mysql2 to lead_product_payments
     */
    private function migratePayments(array $leadIdMap, array $leadProductIdMap)
    {
        
        if (empty($leadIdMap)) {
            return;
        }

        $payments = DB::connection('mysql2')
            ->table('leadpaymenthistories')
            ->whereIn('leadid', array_keys($leadIdMap))
            ->orderBy('id')
            ->get();
                
        $userMap = []; // Cache for user mappings

        foreach ($payments as $payment) {
            $newLeadId = $leadIdMap[$payment->leadid] ?? null;
            if (!$newLeadId) {
                continue;
            }

            $newLeadProductId = $leadProductIdMap[$payment->productid] ?? null;
            if (!$newLeadProductId) {
                continue;
            }

            $amount = (float) $payment->receivedamount;
            if ($amount <= 0) {
                continue;
            }

            // Map user ID
            $recordedBy = 1; // Default to user 1
            if ($payment->user_id) {
                if (isset($userMap[$payment->user_id])) {
                    $recordedBy = $userMap[$payment->user_id];
                } else {
                    $sourceUser = DB::connection('mysql2')->table('users')
                        ->where('id', $payment->user_id)
                        ->first();

                    if ($sourceUser && $sourceUser->name) {
                        $targetUser = DB::connection('mysql')->table('users')
                            ->where('name', $sourceUser->name)
                            ->first();

                        if ($targetUser) {
                            $recordedBy = $targetUser->id;
                        }
                    }
                    $userMap[$payment->user_id] = $recordedBy;
                }
            }

            $paymentDate = $payment->paymentdate ?: ($payment->created_at ? Carbon::parse($payment->created_at)->toDateString() : today()->toDateString());

            DB::connection('mysql')->table('lead_product_payments')->insert([
                'lead_product_id' => $newLeadProductId,
                'lead_id' => $newLeadId,
                'recorded_by' => $recordedBy,
                'amount' => $amount,
                'payment_mode' => 'cash',
                'payment_date' => $paymentDate,
                'reference_number' => null,
                'notes' => 'Migrated from legacy payments histories',
                'attachment_path' => null,
                'attachment_name' => null,
                'created_at' => $payment->created_at ?: now(),
                'updated_at' => $payment->created_at ?: now(),
            ]);

            // Update parent lead_products amount_paid and payment_status
            $totalPaid = DB::connection('mysql')->table('lead_product_payments')
                ->where('lead_product_id', $newLeadProductId)
                ->sum('amount');

            $leadProduct = DB::connection('mysql')->table('lead_products')
                ->where('id', $newLeadProductId)
                ->first();

            $totalPrice = $leadProduct ? $leadProduct->total_price : 0;
            $paymentStatus = 'pending';
            if ($totalPaid >= $totalPrice && $totalPrice > 0) {
                $paymentStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $paymentStatus = 'partially_paid';
            }

            DB::connection('mysql')->table('lead_products')
                ->where('id', $newLeadProductId)
                ->update([
                    'amount_paid' => $totalPaid,
                    'payment_status' => $paymentStatus,
                ]);
        }
    }

    /**
     * Map callinfostatus to call_type
     */
    private function mapCallType($callInfoStatus)
    {
        $mapping = [
            1 => 'inbound',
            2 => 'outbound',
            3 => 'meeting',
            4 => 'email',
            5 => 'note',
        ];

        return $mapping[$callInfoStatus] ?? 'note';
    }
}