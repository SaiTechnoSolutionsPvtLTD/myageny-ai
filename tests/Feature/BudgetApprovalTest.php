<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ProductionInitiation;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BudgetApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name');
            $table->string('email');
            $table->string('mobile_number');
            $table->text('address');
            $table->string('company_status')->default('active');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->string('user_status')->nullable();
            $table->string('designation')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_categories', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->string('package_name')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2)->default(0);
            $table->string('tax_type')->default('percentage');
            $table->decimal('tax_value', 12, 2)->default(0);
            $table->string('discount_type')->default('percentage');
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->longText('description')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->boolean('count_wise_report')->default(false);
            $table->boolean('is_this_renewal_product')->default(false);
            $table->boolean('is_budget_approval_needed')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('production_initiations', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('lead_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->string('product_name');
            $table->string('status')->default('ovp_pending');
            $table->string('production_approval_status')->default('pending');
            $table->string('production_approval_remarks')->nullable();
            $table->dateTime('production_approval_reviewed_at')->nullable();
            $table->foreignId('production_approval_reviewed_by')->nullable();
            $table->string('project_allocation_status')->nullable();
            $table->dateTime('project_allocated_at')->nullable();
            $table->foreignId('project_allocated_by')->nullable();
            $table->decimal('lead_budget_amount', 12, 2)->nullable();
            $table->string('budget_amount_type')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
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
        Schema::dropIfExists('roles');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('production_initiations');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');

        parent::tearDown();
    }

    /** @test */
    public function test_budget_approval_validation_and_saving()
    {
        $this->withoutExceptionHandling();

        $company = Company::create([
            'company_name' => 'Test Company',
            'email' => 'test@company.com',
            'mobile_number' => '1234567890',
            'address' => 'Test Address',
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Company Admin User',
            'email' => 'admin@company.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $company->id,
            'name' => 'super_admin',
            'guard_name' => 'web',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        
        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        $permissionId = DB::table('permissions')->insertGetId([
            'company_id' => $company->id,
            'name' => 'production_approval_module.menuview',
            'guard_name' => 'web',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('role_has_permissions')->insert([
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);

        $product = Product::create([
            'company_id' => $company->id,
            'package_name' => 'Gold Plan',
            'sku' => 'GOLD-123',
            'base_price' => 1000,
            'final_price' => 1000,
            'is_budget_approval_needed' => true,
        ]);

        $initiation = ProductionInitiation::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'product_name' => 'Gold Plan Project',
            'status' => 'approval',
            'production_approval_status' => 'pending',
        ]);

        $this->actingAs($user);

        // When is_budget_approval_needed is true and decision is approval, budget amount and type are required
        try {
            $this->post(route('production-approvals.review', $initiation), [
                'decision' => 'approval',
                'production_approval_remarks' => 'Approved project',
            ]);
            $this->fail('Validation should have failed for budget fields');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('lead_budget_amount', $e->errors());
            $this->assertArrayHasKey('budget_amount_type', $e->errors());
        }

        // Post valid request
        $response = $this->post(route('production-approvals.review', $initiation), [
            'decision' => 'approval',
            'production_approval_remarks' => 'Approved project',
            'lead_budget_amount' => 15000,
            'budget_amount_type' => 'Monthly',
        ]);

        $response->assertRedirect();
        
        $initiation->refresh();
        $this->assertEquals('approval', $initiation->production_approval_status);
        $this->assertEquals(15000, $initiation->lead_budget_amount);
        $this->assertEquals('Monthly', $initiation->budget_amount_type);

        // Test custom type validation
        $initiation2 = ProductionInitiation::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'product_name' => 'Gold Plan Project 2',
            'status' => 'approval',
            'production_approval_status' => 'pending',
        ]);

        try {
            $this->post(route('production-approvals.review', $initiation2), [
                'decision' => 'approval',
                'production_approval_remarks' => 'Approved project 2',
                'lead_budget_amount' => 5000,
                'budget_amount_type' => 'custom',
            ]);
            $this->fail('Validation should have failed for custom type text');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('budget_amount_type_custom', $e->errors());
        }

        // Post valid request with custom type
        $response2 = $this->post(route('production-approvals.review', $initiation2), [
            'decision' => 'approval',
            'production_approval_remarks' => 'Approved project 2',
            'lead_budget_amount' => 5000,
            'budget_amount_type' => 'custom',
            'budget_amount_type_custom' => 'Per 15 days',
        ]);

        $response2->assertRedirect();
        
        $initiation2->refresh();
        $this->assertEquals('approval', $initiation2->production_approval_status);
        $this->assertEquals(5000, $initiation2->lead_budget_amount);
        $this->assertEquals('Per 15 days', $initiation2->budget_amount_type);
    }

    /** @test */
    public function test_can_view_budget_approval_details_role_based_permissions()
    {
        $company = Company::create([
            'company_name' => 'Test Company',
            'email' => 'test@company.com',
            'mobile_number' => '1234567890',
            'address' => 'Test Address',
        ]);

        // 1. Super Admin
        $superAdminUser = User::create([
            'company_id' => $company->id,
            'name' => 'Super Admin User',
            'email' => 'superadmin@company.com',
            'password' => Hash::make('password'),
        ]);
        $roleSuperAdmin = DB::table('roles')->insertGetId(['company_id' => $company->id, 'name' => 'super_admin']);
        DB::table('model_has_roles')->insert(['role_id' => $roleSuperAdmin, 'model_type' => User::class, 'model_id' => $superAdminUser->id]);

        $this->assertTrue($superAdminUser->canViewBudgetApprovalDetails());

        // 2. Company Admin
        $companyAdminUser = User::create([
            'company_id' => $company->id,
            'name' => 'Company Admin User',
            'email' => 'companyadmin@company.com',
            'password' => Hash::make('password'),
        ]);
        $roleCompanyAdmin = DB::table('roles')->insertGetId(['company_id' => $company->id, 'name' => 'company_admin']);
        DB::table('model_has_roles')->insert(['role_id' => $roleCompanyAdmin, 'model_type' => User::class, 'model_id' => $companyAdminUser->id]);

        $this->assertTrue($companyAdminUser->canViewBudgetApprovalDetails());

        // 3. COO
        $cooUser = User::create([
            'company_id' => $company->id,
            'name' => 'COO User',
            'email' => 'coo@company.com',
            'password' => Hash::make('password'),
        ]);
        $roleCoo = DB::table('roles')->insertGetId(['company_id' => $company->id, 'name' => 'coo']);
        DB::table('model_has_roles')->insert(['role_id' => $roleCoo, 'model_type' => User::class, 'model_id' => $cooUser->id]);

        $this->assertTrue($cooUser->canViewBudgetApprovalDetails());

        // 4. Digital Marketing TL (Mock department check)
        $dmTlUser = User::create([
            'company_id' => $company->id,
            'name' => 'DM TL User',
            'email' => 'dmtl@company.com',
            'password' => Hash::make('password'),
        ]);
        
        $roleDmTl = DB::table('roles')->insertGetId(['company_id' => $company->id, 'name' => 'digital_marketing_team_leader']);
        DB::table('model_has_roles')->insert(['role_id' => $roleDmTl, 'model_type' => User::class, 'model_id' => $dmTlUser->id]);

        $this->assertTrue($dmTlUser->canViewBudgetApprovalDetails());

        // 5. Normal Executive (Unauthorized)
        $execUser = User::create([
            'company_id' => $company->id,
            'name' => 'Executive User',
            'email' => 'exec@company.com',
            'password' => Hash::make('password'),
        ]);
        $roleExec = DB::table('roles')->insertGetId(['company_id' => $company->id, 'name' => 'sales_executive']);
        DB::table('model_has_roles')->insert(['role_id' => $roleExec, 'model_type' => User::class, 'model_id' => $execUser->id]);

        $this->assertFalse($execUser->canViewBudgetApprovalDetails());
    }
}
