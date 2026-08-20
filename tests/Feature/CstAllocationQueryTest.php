<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Lead;
use App\Models\LeadProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CstAllocationQueryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name');
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->text('address')->nullable();
            $table->string('company_status')->default('active');
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('super_admin_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('user_status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('departments', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_onboardings', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('portal_user_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('customer_support_tl_id')->nullable();
            $table->foreignId('customer_support_executive_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->date('lead_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_products', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('lead_id');
            $table->foreignId('product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('product_status')->default('new');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('product_name')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('products');
        Schema::dropIfExists('lead_products');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('employee_onboardings');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');

        parent::tearDown();
    }

    /** @test */
    public function test_cst_allocation_index_queries_correctly()
    {
        $this->withoutExceptionHandling();

        $company = Company::create([
            'company_name' => 'Test Co',
            'company_status' => 'active'
        ]);
        
        $adminUser = User::create([
            'company_id' => $company->id,
            'name' => 'Super Admin',
            'email' => 'admin@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true,
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $company->id,
            'name' => 'super_admin',
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $adminUser->id,
        ]);

        // Lead 1: 50% paid progress (should match)
        $lead1 = Lead::create(['company_id' => $company->id, 'company_name' => 'Lead One']);
        $lp1 = new LeadProduct([
            'company_id' => $company->id,
            'lead_id' => $lead1->id,
            'product_status' => 'converted',
            'amount_paid' => 500,
        ]);
        $lp1->total_price = 1000;
        $lp1->save();

        // Lead 2: 30% paid progress (converted - should also match now)
        $lead2 = Lead::create(['company_id' => $company->id, 'company_name' => 'Lead Two']);
        $lp2 = new LeadProduct([
            'company_id' => $company->id,
            'lead_id' => $lead2->id,
            'product_status' => 'converted',
            'amount_paid' => 300,
        ]);
        $lp2->total_price = 1000;
        $lp2->save();

        // Lead 3: 100% paid but not converted (should be filtered out)
        $lead3 = Lead::create(['company_id' => $company->id, 'company_name' => 'Lead Three']);
        $lp3 = new LeadProduct([
            'company_id' => $company->id,
            'lead_id' => $lead3->id,
            'product_status' => 'new',
            'amount_paid' => 1000,
        ]);
        $lp3->total_price = 1000;
        $lp3->save();

        $this->actingAs($adminUser);

        $response = $this->get(route('cst-allocation.index'));
        $response->assertStatus(200);

        $pendingLeads = $response->viewData('pendingLeads');
        
        $this->assertCount(2, $pendingLeads);
        $this->assertEquals($lead1->id, $pendingLeads->first()->id);
        $this->assertEquals(50.0, $pendingLeads->first()->payment_progress_pct);
        $this->assertEquals(30.0, $pendingLeads->get(1)->payment_progress_pct);
    }

    /** @test */
    public function test_cst_allocation_filters_by_lead_account()
    {
        $company = Company::create(['company_name' => 'Test Co 3', 'company_status' => 'active']);
        $adminUser = User::create([
            'company_id' => $company->id,
            'name' => 'Admin User',
            'email' => 'admin3@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'is_active' => true,
        ]);
        $roleId = DB::table('roles')->insertGetId(['company_id' => $company->id, 'name' => 'super_admin']);
        DB::table('model_has_roles')->insert(['role_id' => $roleId, 'model_type' => User::class, 'model_id' => $adminUser->id]);

        $leadA = Lead::create(['company_id' => $company->id, 'company_name' => 'Alpha Corp']);
        $lpA = new LeadProduct(['company_id' => $company->id, 'lead_id' => $leadA->id, 'product_status' => 'converted', 'amount_paid' => 500]);
        $lpA->total_price = 1000;
        $lpA->save();

        $leadB = Lead::create(['company_id' => $company->id, 'company_name' => 'Beta Solutions']);
        $lpB = new LeadProduct(['company_id' => $company->id, 'lead_id' => $leadB->id, 'product_status' => 'converted', 'amount_paid' => 500]);
        $lpB->total_price = 1000;
        $lpB->save();

        $this->actingAs($adminUser);

        // Filter by lead_id
        $res1 = $this->get(route('cst-allocation.index', ['lead_id' => $leadA->id]));
        $res1->assertStatus(200);
        $pending1 = $res1->viewData('pendingLeads');
        $this->assertCount(1, $pending1);
        $this->assertEquals($leadA->id, $pending1->first()->id);

        // Filter by search string
        $res2 = $this->get(route('cst-allocation.index', ['lead_account' => 'Beta']));
        $res2->assertStatus(200);
        $pending2 = $res2->viewData('pendingLeads');
        $this->assertCount(1, $pending2);
        $this->assertEquals($leadB->id, $pending2->first()->id);
    }

    /** @test */
    public function test_auth()
    {
        $company = Company::create(['company_name' => 'Test Co 2']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'secret',
            'is_active' => true,
        ]);
        $this->actingAs($user);
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
    }

    /** @test */
    public function test_cst_allocation_permission_not_granted_by_default_for_new_company_user()
    {
        $company = Company::create(['company_name' => 'New Company']);
        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Company User',
            'email' => 'compuser@test.com',
            'password' => 'secret',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // User without cst_allocation.menuview permission should not pass Gate check
        $this->assertFalse($user->can('cst_allocation.menuview'));
    }
}
