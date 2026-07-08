<?php

namespace App\Console\Commands;

use App\Models\LeadSource;
use App\Models\LeadStatus;
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
         $leads = DB::connection('mysql2')->table('leads')->take(1000)->get()->orderby('id', 'desc');

         // Get or create lead status for 'New'
         $newLeadStatus = LeadStatus::firstOrCreate(['name' => 'New']);

            foreach($leads as $lead)
                {
                    // Get lead source from source database and match with master
                    $sourceNameFromDb = $lead->leadSource ?? 'Online';

                    // Find matching lead source in master, if not found use 'Online'
                    $leadSourceRecord = LeadSource::where('name', $sourceNameFromDb)->first();
                    if (!$leadSourceRecord) {
                        $leadSourceRecord = LeadSource::firstOrCreate(['name' => 'Online']);
                    }

                    // Insert lead
                    $newLeadId = DB::connection('mysql')->table('leads')->insertGetId([
                                'company_name' => $lead->CompanyName,
                                'company_id' => 1,
                                'contact_name' => $lead->ClientName,
                                'lead_date' => $lead->EntryDate,
                                'mobile_number' => $lead->MobileNumber,
                                'email' => $lead->EmailID,
                                'lead_source' => $sourceNameFromDb,
                                'lead_status' => $newLeadStatus->id,
                                'remarks' => $lead->Remarks,
                                'branch_id' => 1,
                                'assigned_to' => 1,
                                'created_by' => 1,
                                'created_at' => $lead->created_at,

                    ]);

                    // Migrate lead products
                    $leadProducts = DB::connection('mysql2')->table('leadproducts')
                        ->where('leadid', $lead->id)
                        ->get();

                    foreach($leadProducts as $leadProduct) {
                        // Get product details from mysql2 products table
                        $sourceProduct = DB::connection('mysql2')->table('products')
                            ->where('productname', $leadProduct->productname)
                            ->first();

                        $unitPrice = $sourceProduct ? ($sourceProduct->rate ?? 0) : 0;

                        // Match product name from mysql2 with myagency products table
                        $productRecord = DB::connection('mysql')
                            ->table('products')
                            ->where('product_name', $leadProduct->productname)
                            ->first();

                        $productId = $productRecord ? $productRecord->id : null;

                        DB::connection('mysql')->table('lead_products')->insert([
                            'company_id' => 1,
                            'lead_id' => $newLeadId,
                            'product_id' => $productId ?? null,
                            'product_name' => $leadProduct->productname,
                            'unit_price' => $unitPrice,
                            'quantity' => 1,
                            // 'total_price' => $leadProduct->totalcost ?? 0,
                            'amount_paid' => $leadProduct->receivedcost ?? 0,
                            'payment_status' => 'pending',
                            'payment_date' => $leadProduct->paymentdate,
                            'product_status' => $leadProduct->status,
                            'lead_status_id' => $newLeadStatus->id,
                            'remarks' => $leadProduct->producttype,
                            'created_by' => 1,
                            'created_at' => $leadProduct->created_at,
                        ]);
                    }
                }
    }
}
