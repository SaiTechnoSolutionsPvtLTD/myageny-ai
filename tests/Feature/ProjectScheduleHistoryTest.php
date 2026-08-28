<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\ProductionInitiation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectScheduleHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        // Directly construct the tables needed for testing in memory
        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name');
            $table->string('email');
            $table->string('mobile_number');
            $table->text('address');
            $table->string('company_status')->default('active');
            $table->date('expiry_date')->nullable();
            $table->string('facebook_client_id')->nullable();
            $table->string('facebook_client_secret')->nullable();
            $table->timestamps();
        });

        Schema::create('branches', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
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
            $table->string('employee_id')->nullable();
            $table->string('avatar')->nullable();
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
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_number')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_products', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('lead_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_product_payments', function ($table) {
            $table->id();
            $table->foreignId('lead_product_id');
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
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
            $table->string('product_name');
            $table->unsignedInteger('total_working_days')->default(0);
            $table->boolean('ui_available')->default(false);
            $table->longText('requirements')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('status')->default('ovp_pending');
            $table->string('production_approval_status')->default('pending');
            $table->string('project_allocation_status')->nullable();
            $table->json('project_allocated_tl_user_ids')->nullable();
            $table->date('project_delivery_date')->nullable();
            $table->string('project_execution_status')->nullable();
            $table->foreignId('initiated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('project_updates', function ($table) {
            $table->id();
            $table->foreignId('production_initiation_id');
            $table->string('type');
            $table->longText('content');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        // Construct mock Spatie tables to satisfy role/permission queries
        Schema::create('roles', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('guard_name')->default('web');
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
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('lead_product_payments');
        Schema::dropIfExists('lead_products');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('project_updates');
        Schema::dropIfExists('production_initiations');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('companies');

        parent::tearDown();
    }

    /** @test */
    public function project_schedule_update_tracks_history_when_delivery_date_or_status_changes()
    {
        $this->withoutExceptionHandling();

        // 1. Create a dummy company, branch, and user
        $company = Company::create([
            'company_name' => 'Test Company',
            'email' => 'test@company.com',
            'mobile_number' => '1234567890',
            'address' => 'Test Address',
            'company_status' => 'active',
            'expiry_date' => Carbon::now()->addYears(5),
            'facebook_client_id' => 'fb_id',
            'facebook_client_secret' => 'fb_secret',
        ]);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Test Branch', 'code' => 'TBR']);
        $user = User::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'Project Manager',
            'email' => 'pm_test@myagency.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'user_status' => 'active',
        ]);

        // Assign 'super_admin' role in Spatie DB mock
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

        // Insert a dummy department
        DB::table('departments')->insert([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Development',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert a dummy product category
        DB::table('product_categories')->insert([
            'id' => 1,
            'company_id' => $company->id,
            'name' => 'Dummy Category',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert a dummy product
        DB::table('products')->insert([
            'id' => 1,
            'company_id' => $company->id,
            'product_category_id' => 1,
            'name' => 'Dummy Product',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert a dummy lead
        DB::table('leads')->insert([
            'id' => 1,
            'company_id' => $company->id,
            'company_name' => 'Test Client Company',
            'contact_name' => 'Test Lead Contact',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Insert a dummy lead product
        DB::table('lead_products')->insert([
            'id' => 1,
            'company_id' => $company->id,
            'lead_id' => 1,
            'product_id' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // 2. Create a dummy production initiation (project)
        $project = ProductionInitiation::create([
            'company_id' => $company->id,
            'lead_id' => 1,
            'lead_product_id' => 1,
            'product_id' => 1,
            'department_id' => 1,
            'product_name' => 'Test Product',
            'total_working_days' => 5,
            'ui_available' => true,
            'requirements' => 'Some reqs',
            'attachment_path' => '',
            'attachment_name' => '',
            'status' => 'approved',
            'production_approval_status' => 'approved',
            'project_allocation_status' => 'allocated',
            'project_delivery_date' => '2026-07-06',
            'project_execution_status' => 'ontrack',
        ]);

        // 3. Act: Simulate request to update schedule
        $this->actingAs($user);
        
        $response = $this->post(route('projects.schedule.update', $project), [
            'project_delivery_date' => '2026-07-10',
            'project_execution_status' => 'hold',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // 4. Assert: Check Project is updated
        $project->refresh();
        $this->assertEquals('2026-07-10', Carbon::parse($project->project_delivery_date)->toDateString());
        $this->assertEquals('hold', $project->project_execution_status);

        // 5. Assert: Check a project update with schedule_history type was created
        $historyUpdate = $project->projectUpdates()->where('type', 'schedule_history')->first();
        $this->assertNotNull($historyUpdate);
        $this->assertStringContainsString('Delivery Date updated from', $historyUpdate->content);
        $this->assertStringContainsString('Project Status updated from', $historyUpdate->content);
        $this->assertEquals($user->id, $historyUpdate->created_by);
    }

    public function test_tl_user_can_update_project_schedule(): void
    {
        $company = Company::create([
            'company_name' => 'TL Test Co',
            'email' => 'tl@example.com',
            'mobile_number' => '1234567890',
            'address' => 'Test Address',
        ]);

        $tlUser = User::create([
            'company_id' => $company->id,
            'name' => 'TL User',
            'email' => 'tluser@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Attach TL role
        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $company->id,
            'name' => 'team_lead',
            'display_name' => 'Team Lead',
            'guard_name' => 'web',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => User::class,
            'model_id' => $tlUser->id,
        ]);

        $project = ProductionInitiation::create([
            'company_id' => $company->id,
            'lead_id' => 1,
            'lead_product_id' => 1,
            'product_id' => 1,
            'department_id' => 1,
            'product_name' => 'TL Test Product',
            'total_working_days' => 5,
            'ui_available' => true,
            'requirements' => 'TL test reqs',
            'attachment_path' => '',
            'attachment_name' => '',
            'status' => 'approved',
            'production_approval_status' => 'approved',
            'project_allocation_status' => 'allocated',
            'project_allocated_tl_user_ids' => [$tlUser->id],
            'project_delivery_date' => '2026-08-01',
            'project_execution_status' => 'ontrack',
        ]);

        $this->actingAs($tlUser);

        $response = $this->post(route('projects.schedule.update', $project), [
            'project_delivery_date' => '2026-08-15',
            'project_execution_status' => 'delivered',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $project->refresh();
        $this->assertEquals('2026-08-15', Carbon::parse($project->project_delivery_date)->toDateString());
        $this->assertEquals('delivered', $project->project_execution_status);
    }
}
