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
    protected $signature = 'app:lead-and-lead-product-migration-command {--limit=1000 : Limit the number of leads to migrate (use 0 for all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate leads, leadproducts, notes, and payments from sts force (mysql2) to myagency (mysql)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');

        $this->info("Testing connection to source database (mysql2)...");
        try {
            DB::connection('mysql2')->getPdo();
            $this->info("Successfully connected to mysql2 database.");
        } catch (\Throwable $e) {
            $this->error("CONNECTION ERROR: Could not connect to the source database (mysql2).");
            $this->error("Reason: " . $e->getMessage());
            $this->warn("\nPossible causes:");
            $this->line("1. Wrong host, port, database, username or password in .env for mysql2.");
            $this->line("2. The source database server firewall is blocking connections from this VPS IP address.");
            $this->line("3. The database port (3306) is not opened on the source server.");
            return self::FAILURE;
        }

        // Get first user in target database to use as a fallback default
        $defaultUserId = DB::connection('mysql')->table('users')->orderBy('id')->value('id') ?: 1;

        $this->info("Fetching leads count from source database...");
        $totalLeads = DB::connection('mysql2')->table('leads')->count();
        $this->info("Found {$totalLeads} total leads in source database.");

        if ($limit > 0) {
            $this->info("Limit set: Only migrating the first {$limit} leads.");
        } else {
            $this->info("No limit set: Migrating all leads.");
        }

        $leadIdMap = [];
        $leadProductIdMap = [];

        $leadsMigrated = 0;
        $leadProductsMigrated = 0;

        if ($totalLeads > 0) {
            $this->info("Migrating Leads and Lead Products (Chunked to save memory)...");
            $barMax = $limit > 0 ? min($totalLeads, $limit) : $totalLeads;
            $bar = $this->output->createProgressBar($barMax);
            $bar->start();

            DB::connection('mysql2')
                ->table('leads')
                ->orderBy('id', 'desc')
                ->chunk(100, function ($leads) use (&$leadIdMap, &$leadProductIdMap, &$leadsMigrated, &$leadProductsMigrated, $bar, $limit, $defaultUserId) {
                    foreach ($leads as $lead) {
                        if ($limit > 0 && $leadsMigrated >= $limit) {
                            return false; // Stop chunking
                        }

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
                        $assignedUserId = $defaultUserId;
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
                            'created_by' => $defaultUserId,
                            'created_at' => $lead->created_at,
                            'product_name' => $lead->leadid
                        ]);

                        $leadIdMap[$lead->id] = $newLeadId;
                        $leadsMigrated++;

                        // Migrate lead products
                        $leadProducts = DB::connection('mysql2')->table('leadproducts')
                            ->where('leadid', $lead->id)
                            ->get();

                        foreach ($leadProducts as $leadProduct) {
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
                                'product_name' => $sourceProduct->productname ?? null,
                                'unit_price' => $leadProduct->totalcost ?? 0,
                                'quantity' => 1,
                                'amount_paid' => 0,
                                'payment_status' => 'pending',
                                'payment_date' => $leadProduct->paymentdate,
                                'product_status' => $leadProduct->status,
                                'lead_status_id' => $leadStatusId,
                                'lead_source_id' => $leadSourceRecord->id ?? null,
                                'remarks' => $leadProduct->producttype,
                                'deal_name' => "Lead Migration From STS Force",
                                'created_by' => $defaultUserId,
                                'created_at' => $leadProduct->created_at,
                            ]);

                            $leadProductIdMap[$leadProduct->id] = $newLeadProductId;
                            $leadProductsMigrated++;
                        }

                        $bar->advance();
                    }

                    if ($limit > 0 && $leadsMigrated >= $limit) {
                        return false; // Stop chunking
                    }
                });

            $bar->finish();
            $this->newLine(2);
        }

        // Migrate notes to lead_call_updates
        $notesCount = $this->migrateNotes($leadIdMap, $defaultUserId);

        // Migrate payments to lead_product_payments
        $paymentsCount = $this->migratePayments($leadIdMap, $leadProductIdMap, $defaultUserId);

        $this->newLine();
        $this->info("Migration completed successfully!");
        $this->table(
            ['Entity', 'Migrated Count'],
            [
                ['Leads', $leadsMigrated],
                ['Lead Products', $leadProductsMigrated],
                ['Call Updates (Notes)', $notesCount],
                ['Payments', $paymentsCount],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Migrate notes from mysql2 to lead_call_updates
     */
    private function migrateNotes(array $leadIdMap, int $defaultUserId): int
    {
        if (empty($leadIdMap)) {
            return 0;
        }

        $leadIds = array_keys($leadIdMap);
        $leadIdChunks = array_chunk($leadIds, 1000);
        $totalNotes = 0;

        $this->info("Counting lead notes from source database...");
        foreach ($leadIdChunks as $chunk) {
            $totalNotes += DB::connection('mysql2')
                ->table('notes')
                ->whereIn('leadid', $chunk)
                ->count();
        }

        $this->info("Migrating {$totalNotes} Lead Notes...");

        $notesMigrated = 0;
        $skippedNoLead = 0;

        if ($totalNotes > 0) {
            $bar = $this->output->createProgressBar($totalNotes);
            $bar->start();

            $userMap = []; // In-memory cache for user mappings

            foreach ($leadIdChunks as $chunk) {
                $notes = DB::connection('mysql2')
                    ->table('notes')
                    ->whereIn('leadid', $chunk)
                    ->orderBy('id')
                    ->get();

                foreach ($notes as $note) {
                    $newLeadId = $leadIdMap[$note->leadid] ?? null;
                    if (!$newLeadId) {
                        $skippedNoLead++;
                        $bar->advance();
                        continue; // Skip if lead mapping is not available
                    }

                    // Get user from mysql2 and map to myagency
                    $callUserId = $defaultUserId;
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
                        'user_id' => $callUserId,
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

                    $notesMigrated++;
                    $bar->advance();
                }
            }
            $bar->finish();
            $this->newLine(2);

            $this->info("Notes migration breakdown:");
            $this->line("- Migrated: {$notesMigrated}");
            $this->line("- Skipped (Lead not migrated/found): {$skippedNoLead}");
            $this->newLine();
        }

        return $notesMigrated;
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
    private function migratePayments(array $leadIdMap, array $leadProductIdMap, int $defaultUserId): int
    {
        if (empty($leadIdMap)) {
            return 0;
        }

        $leadIds = array_keys($leadIdMap);
        $leadIdChunks = array_chunk($leadIds, 1000);
        $totalPayments = 0;

        $this->info("Counting lead payments from source database...");
        foreach ($leadIdChunks as $chunk) {
            $totalPayments += DB::connection('mysql2')
                ->table('leadpaymenthistories')
                ->whereIn('leadid', $chunk)
                ->count();
        }

        $this->info("Migrating {$totalPayments} Lead Payments...");

        $paymentsMigrated = 0;
        $skippedNoLead = 0;
        $skippedNoProduct = 0;
        $skippedZeroAmount = 0;

        if ($totalPayments > 0) {
            $bar = $this->output->createProgressBar($totalPayments);
            $bar->start();

            $userMap = []; // Cache for user mappings

            foreach ($leadIdChunks as $chunk) {
                $payments = DB::connection('mysql2')
                    ->table('leadpaymenthistories')
                    ->whereIn('leadid', $chunk)
                    ->orderBy('id')
                    ->get();

                foreach ($payments as $payment) {
                    $newLeadId = $leadIdMap[$payment->leadid] ?? null;
                    if (!$newLeadId) {
                        $skippedNoLead++;
                        $bar->advance();
                        continue;
                    }

                    $newLeadProductId = $leadProductIdMap[$payment->productid] ?? null;
                    if (!$newLeadProductId) {
                        $skippedNoProduct++;
                        $bar->advance();
                        continue;
                    }

                    $amount = (float) $payment->receivedamount;
                    if ($amount <= 0) {
                        $skippedZeroAmount++;
                        $bar->advance();
                        continue;
                    }

                    // Map user ID
                    $recordedBy = $defaultUserId;
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

                    $paymentsMigrated++;

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

                    $bar->advance();
                }
            }
            $bar->finish();
            $this->newLine(2);

            $this->info("Payments migration breakdown:");
            $this->line("- Migrated: {$paymentsMigrated}");
            $this->line("- Skipped (Lead not migrated/found): {$skippedNoLead}");
            $this->line("- Skipped (Lead Product not migrated/found): {$skippedNoProduct}");
            $this->line("- Skipped (Zero or invalid received amount): {$skippedZeroAmount}");
            $this->newLine();
        }

        return $paymentsMigrated;
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
