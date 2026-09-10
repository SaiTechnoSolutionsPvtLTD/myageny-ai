<?php

namespace Tests\Feature;

use App\Models\CustomerCampaign;
use App\Models\Department;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductionInitiation;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerCampaignTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('company_status')->default('active');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('designation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('departments', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function ($table) {
            $table->foreignId('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->foreignId('company_id')->nullable();
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function ($table) {
            $table->foreignId('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->foreignId('company_id')->nullable();
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function ($table) {
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('products', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->string('product_name')->nullable();
            $table->string('package_name')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2)->default(0);
            $table->string('tax_type')->default('percentage');
            $table->decimal('tax_value', 12, 2)->default(0);
            $table->string('discount_type')->default('percentage');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->boolean('is_budget_approval_needed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('email')->nullable();
            $table->date('lead_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_products', function ($table) {
            $table->id();
            $table->foreignId('lead_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('production_initiations', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('lead_id')->nullable();
            $table->foreignId('lead_product_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('status')->default('initiated');
            $table->string('production_approval_status')->default('pending');
            $table->decimal('lead_budget_amount', 12, 2)->nullable();
            $table->string('budget_amount_type')->nullable();
            $table->json('project_allocated_tl_user_ids')->nullable();
            $table->json('project_allocated_employee_user_ids')->nullable();
            $table->json('tl_employee_allocations')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_campaigns', function ($table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->foreignId('lead_id');
            $table->foreignId('lead_product_id')->nullable();
            $table->foreignId('production_initiation_id')->nullable();
            $table->foreignId('extended_from_id')->nullable();
            $table->string('campaign_name');
            $table->string('ad_account_name')->nullable();
            $table->string('platform')->nullable();
            $table->string('status')->default('active');
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->string('budget_type')->nullable();
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('last_resumed_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->date('stop_date')->nullable();
            $table->unsignedInteger('total_paused_days')->default(0);
            $table->json('pause_history')->nullable();
            $table->text('remarks')->nullable();
            $table->text('stop_reason')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function test_customer_campaign_crud_and_lead_relation()
    {
        // 1. Create Lead
        $lead = Lead::create([
            'company_name' => 'Acme Digital Agency',
            'contact_name' => 'Jane Smith',
            'mobile_number' => '9876543210',
            'email' => 'jane@acmedigital.com',
        ]);

        // 2. Create Product with budget approval needed
        $product = Product::create([
            'product_name' => 'Performance Marketing Package',
            'is_budget_approval_needed' => true,
        ]);

        // 3. Create Department
        $dept = Department::create([
            'name' => 'Digital Marketing',
        ]);

        // 4. Create Production Initiation
        $initiation = ProductionInitiation::create([
            'lead_id' => $lead->id,
            'product_id' => $product->id,
            'department_id' => $dept->id,
            'product_name' => 'Performance Marketing Package',
            'lead_budget_amount' => 25000,
            'budget_amount_type' => 'Monthly',
        ]);

        // 5. Create Customer Campaigns for this lead
        $campaign1 = CustomerCampaign::create([
            'lead_id' => $lead->id,
            'production_initiation_id' => $initiation->id,
            'campaign_name' => 'Meta Lead Gen Campaign',
            'ad_account_name' => 'Meta Ads - Acme Client',
            'platform' => 'Facebook / Meta',
            'status' => 'active',
            'budget_amount' => 15000,
            'budget_type' => 'Monthly',
        ]);

        $campaign2 = CustomerCampaign::create([
            'lead_id' => $lead->id,
            'production_initiation_id' => $initiation->id,
            'campaign_name' => 'Google Search Branding',
            'ad_account_name' => 'Google Ads - Acme 456',
            'platform' => 'Google Ads',
            'status' => 'paused',
            'budget_amount' => 10000,
            'budget_type' => 'Monthly',
        ]);

        // Assert relationships
        $this->assertEquals(2, $lead->customerCampaigns()->count());
        $this->assertEquals(1, $lead->activeCustomerCampaigns()->count());
        $this->assertEquals('Meta Lead Gen Campaign', $lead->activeCustomerCampaigns()->first()->campaign_name);
        $this->assertEquals('Acme Digital Agency', $campaign1->lead->company_name);
        $this->assertEquals('Meta Ads - Acme Client', $campaign1->ad_account_name);

        // Test status update
        $campaign2->update(['status' => 'active']);
        $this->assertEquals(2, $lead->activeCustomerCampaigns()->count());

        // Test delete
        $campaign1->delete();
        $this->assertEquals(1, $lead->customerCampaigns()->count());
    }

    public function test_customer_campaign_controller_actions()
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->withoutMiddleware();

        $dept = Department::create([
            'name' => 'Digital Marketing',
        ]);

        $role = \Illuminate\Support\Facades\DB::table('roles')->insertGetId([
            'name' => 'digital_marketing_executive',
            'department_id' => $dept->id,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cboRole = \Illuminate\Support\Facades\DB::table('roles')->insertGetId([
            'name' => 'company_1__chief_business_officer',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'name' => 'Marketing Specialist',
            'email' => 'marketer@example.com',
        ]);

        $cboUser = User::create([
            'name' => 'Chief Business Officer',
            'email' => 'cbo@example.com',
        ]);

        \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
            ['role_id' => $role, 'model_type' => User::class, 'model_id' => $user->id, 'company_id' => null],
            ['role_id' => $cboRole, 'model_type' => User::class, 'model_id' => $cboUser->id, 'company_id' => null],
        ]);

        $lead = Lead::create([
            'company_name' => 'Alpha Digital Client',
            'contact_name' => 'Alice Green',
            'mobile_number' => '9123456780',
            'email' => 'alice@alphaclient.com',
        ]);

        $this->actingAs($user);

        // 1. Store campaign
        $response = $this->post(route('projects.campaigns.store', $lead->id), [
            'campaign_name' => 'Instagram Brand Awareness',
            'ad_account_name' => 'IG Ads - Alpha Client',
            'platform' => 'Instagram',
            'status' => 'active',
            'budget_amount' => 12000,
            'budget_type' => 'Monthly',
            'start_date' => now()->subDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(20)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('projects.campaigns.show', $lead->id));
        $this->assertDatabaseHas('customer_campaigns', [
            'lead_id' => $lead->id,
            'campaign_name' => 'Instagram Brand Awareness',
            'ad_account_name' => 'IG Ads - Alpha Client',
            'status' => 'active',
        ]);

        $campaign = CustomerCampaign::where('lead_id', $lead->id)->first();

        // 2. Pause with manual date selection
        $pauseDate = now()->subDays(5)->format('Y-m-d');
        $pauseResponse = $this->post(route('projects.campaigns.pause', $campaign->id), [
            'pause_date' => $pauseDate,
            'remarks' => 'Budget review pause',
        ]);
        $pauseResponse->assertRedirect(route('projects.campaigns.show', $lead->id));
        $this->assertEquals('paused', $campaign->fresh()->status);
        $this->assertEquals($pauseDate, $campaign->fresh()->paused_at->format('Y-m-d'));

        // 3. Resume with manual date selection
        $resumeDate = now()->format('Y-m-d');
        $resumeResponse = $this->post(route('projects.campaigns.resume', $campaign->id), [
            'resume_date' => $resumeDate,
            'remarks' => 'Resumed after budget topup',
        ]);
        $resumeResponse->assertRedirect(route('projects.campaigns.show', $lead->id));
        $this->assertEquals('active', $campaign->fresh()->status);
        $this->assertNull($campaign->fresh()->paused_at);
        $this->assertGreaterThan(0, $campaign->fresh()->total_paused_days);

        // 4. Extend / Renew Campaign
        $extendResponse = $this->post(route('projects.campaigns.extend', $campaign->id), [
            'campaign_name' => 'Instagram Brand Awareness (Renewal Month 2)',
            'ad_account_name' => 'IG Ads - Alpha Client',
            'platform' => 'Instagram',
            'status' => 'active',
            'budget_amount' => 15000,
            'budget_type' => 'Monthly',
            'start_date' => now()->addDays(21)->format('Y-m-d'),
            'end_date' => now()->addDays(51)->format('Y-m-d'),
            'remarks' => 'Extended from parent campaign',
        ]);
        $extendResponse->assertRedirect(route('projects.campaigns.show', $lead->id));
        $this->assertDatabaseHas('customer_campaigns', [
            'lead_id' => $lead->id,
            'extended_from_id' => $campaign->id,
            'campaign_name' => 'Instagram Brand Awareness (Renewal Month 2)',
        ]);

        // 5. Stop Campaign with Refund & CBO Mail
        $stopResponse = $this->post(route('projects.campaigns.stop', $campaign->id), [
            'stop_date' => now()->format('Y-m-d'),
            'refund_amount' => 3500.50,
            'stop_reason' => 'Client requested early stop, balance to be refunded.',
        ]);
        $stopResponse->assertRedirect(route('projects.campaigns.show', $lead->id));
        $this->assertEquals('stopped', $campaign->fresh()->status);
        $this->assertEquals('3500.50', (string) $campaign->fresh()->refund_amount);
        $this->assertTrue($campaign->fresh()->isStopped());

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\CampaignStoppedRefundNotificationMail::class);

        // 6. Destroy campaign
        $deleteResponse = $this->delete(route('projects.campaigns.destroy', $campaign->id));
        $deleteResponse->assertRedirect(route('projects.campaigns.show', $lead->id));
        $this->assertSoftDeleted('customer_campaigns', ['id' => $campaign->id]);
    }

    public function test_index_filters_only_dm_products_with_budget_approval_needed()
    {
        $this->withoutMiddleware();

        $dmDept = Department::create(['name' => 'Digital Marketing']);
        $devDept = Department::create(['name' => 'Development']);

        $role = \Illuminate\Support\Facades\DB::table('roles')->insertGetId([
            'name' => 'digital_marketing_tl',
            'department_id' => $dmDept->id,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::create([
            'name' => 'DM Team Lead',
            'email' => 'dmtl@example.com',
        ]);

        \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
            'role_id' => $role,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        $this->actingAs($user);

        // Product 1: DM Dept + Budget Approval Needed (SHOULD MATCH)
        $prod1 = Product::create([
            'product_name' => 'DM Lead Generation',
            'is_budget_approval_needed' => true,
        ]);
        $lead1 = Lead::create([
            'company_name' => 'Match Company A',
            'contact_name' => 'Client A',
        ]);
        ProductionInitiation::create([
            'lead_id' => $lead1->id,
            'product_id' => $prod1->id,
            'department_id' => $dmDept->id,
            'product_name' => 'DM Lead Generation',
            'lead_budget_amount' => 50000,
            'budget_amount_type' => 'Monthly',
        ]);

        // Product 2: DM Dept + Budget Approval FALSE (SHOULD NOT MATCH)
        $prod2 = Product::create([
            'product_name' => 'DM Organic SEO',
            'is_budget_approval_needed' => false,
        ]);
        $lead2 = Lead::create([
            'company_name' => 'No Budget Needed Co',
            'contact_name' => 'Client B',
        ]);
        ProductionInitiation::create([
            'lead_id' => $lead2->id,
            'product_id' => $prod2->id,
            'department_id' => $dmDept->id,
            'product_name' => 'DM Organic SEO',
        ]);

        // Product 3: Development Dept + Budget Approval Needed (SHOULD NOT MATCH)
        $prod3 = Product::create([
            'product_name' => 'Web App Development',
            'is_budget_approval_needed' => true,
        ]);
        $lead3 = Lead::create([
            'company_name' => 'Dev Company C',
            'contact_name' => 'Client C',
        ]);
        ProductionInitiation::create([
            'lead_id' => $lead3->id,
            'product_id' => $prod3->id,
            'department_id' => $devDept->id,
            'product_name' => 'Web App Development',
        ]);

        $response = $this->get(route('projects.campaigns.index'));
        $response->assertStatus(200);

        $leads = $response->viewData('leads');
        $this->assertCount(1, $leads);
        $this->assertEquals('Match Company A', $leads->first()->company_name);
        $this->assertEquals(50000, $leads->first()->lead_budget_amount);
    }

    public function test_campaigns_show_displays_only_latest_extension_in_table_and_all_in_history(): void
    {
        $this->withoutMiddleware();

        $dmDept = Department::create(['name' => 'Digital Marketing']);
        $user = User::create([
            'name' => 'DM Admin',
            'email' => 'dmadmin@example.com',
        ]);
        $role = \Illuminate\Support\Facades\DB::table('roles')->insertGetId([
            'name' => 'company_1__company_admin',
            'guard_name' => 'web',
        ]);
        \Illuminate\Support\Facades\DB::table('model_has_roles')->insert([
            'role_id' => $role,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
        $this->actingAs($user);

        $lead = Lead::create([
            'company_name' => 'Acme Corp Lineage Test',
            'contact_name' => 'John Doe',
        ]);

        // Campaign 1: Original Base
        $camp1 = CustomerCampaign::create([
            'lead_id' => $lead->id,
            'campaign_name' => 'Meta Lead Gen',
            'status' => 'completed',
            'budget_amount' => 10000,
        ]);

        // Campaign 2: Renewal #1 (extended from Camp 1)
        $camp2 = CustomerCampaign::create([
            'lead_id' => $lead->id,
            'extended_from_id' => $camp1->id,
            'campaign_name' => 'Meta Lead Gen (Renewal)',
            'status' => 'completed',
            'budget_amount' => 12000,
        ]);

        // Campaign 3: Renewal #2 (extended from Camp 2) - LATEST in chain
        $camp3 = CustomerCampaign::create([
            'lead_id' => $lead->id,
            'extended_from_id' => $camp2->id,
            'campaign_name' => 'Meta Lead Gen (Renewal #2)',
            'status' => 'active',
            'budget_amount' => 15000,
        ]);

        // Independent Campaign 4: Never extended
        $camp4 = CustomerCampaign::create([
            'lead_id' => $lead->id,
            'campaign_name' => 'Google Ads Search',
            'status' => 'active',
            'budget_amount' => 20000,
        ]);

        $response = $this->get(route('projects.campaigns.show', $lead->id));
        $response->assertStatus(200);

        $displayedCampaigns = $response->viewData('campaigns');
        $allCampaigns = $response->viewData('allCampaigns');

        // All campaigns count = 4
        $this->assertCount(4, $allCampaigns);

        // Displayed in main table = 2 (only Camp 3 and Camp 4, past Camp 1 and Camp 2 are excluded from main table)
        $this->assertCount(2, $displayedCampaigns);
        $this->assertTrue($displayedCampaigns->contains('id', $camp3->id));
        $this->assertTrue($displayedCampaigns->contains('id', $camp4->id));
        $this->assertFalse($displayedCampaigns->contains('id', $camp1->id));
        $this->assertFalse($displayedCampaigns->contains('id', $camp2->id));
    }
}
