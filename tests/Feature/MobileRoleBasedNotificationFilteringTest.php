<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CustomerCampaign;
use App\Models\Department;
use App\Models\Lead;
use App\Models\ProductionInitiation;
use App\Models\ProductionTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileRoleBasedNotificationFilteringTest extends TestCase
{
    private Company $company;
    private Department $devDept;
    private Department $designDept;
    private Department $dmDept;
    private Department $salesDept;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ],
        ]);
        DB::purge();

        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('notifications');
        Schema::dropIfExists('production_tasks');
        Schema::dropIfExists('customer_campaigns');
        Schema::dropIfExists('production_initiations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('role_mappings');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('user_mappings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('company_status')->default('active');
            $table->timestamps();
        });

        Schema::create('departments', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('dashboard_route')->nullable();
            $table->softDeletes();
            $table->timestamps();
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

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('designation')->nullable();
            $table->string('user_status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_mappings', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('manager_id');
            $table->foreignId('user_id');
            $table->timestamps();
        });

        Schema::create('product_categories', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('products', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('product_category_id')->nullable();
            $table->string('name')->nullable();
            $table->softDeletes();
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

        Schema::create('role_mappings', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('role_id')->nullable();
            $table->string('access_level')->default('user');
            $table->timestamps();
        });

        Schema::create('leads', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->date('lead_date')->nullable();
            $table->string('status')->default('new');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('production_initiations', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->date('project_delivery_date')->nullable();
            $table->string('project_execution_status')->default('in_progress');
            $table->string('project_allocation_status')->nullable();
            $table->string('status')->nullable();
            $table->json('project_allocated_tl_user_ids')->nullable();
            $table->json('project_allocated_employee_user_ids')->nullable();
            $table->text('tl_employee_allocations')->nullable();
            $table->foreignId('initiated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_campaigns', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('production_initiation_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('extended_from_id')->nullable();
            $table->string('status')->default('active');
            $table->date('end_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('production_tasks', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('assigned_to')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->date('task_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('notifications', function ($table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();

        // Seed common company and departments
        $this->company = Company::create(['company_name' => 'Test Company']);
        $this->devDept = Department::create(['name' => 'Development']);
        $this->designDept = Department::create(['name' => 'Designing']);
        $this->dmDept = Department::create(['name' => 'Digital Marketing']);
        $this->salesDept = Department::create(['name' => 'Sales']);
    }

    private function assignRole(User $user, string $roleName, ?Department $dept = null): void
    {
        $role = Role::create([
            'name'          => $roleName,
            'company_id'    => $user->company_id,
            'department_id' => $dept?->id,
            'guard_name'    => 'web',
            'display_name'  => ucwords(str_replace('_', ' ', $roleName)),
        ]);

        DB::table('model_has_roles')->insert([
            'role_id'    => $role->id,
            'model_type' => User::class,
            'model_id'   => $user->id,
        ]);
    }

    public function test_sales_role_receives_only_lead_notifications_and_zero_production_metrics(): void
    {
        $today = Carbon::now('Asia/Kolkata')->toDateString();

        $salesUser = User::create([
            'company_id'  => $this->company->id,
            'name'        => 'Sales Executive',
            'user_status' => 'active',
            'is_active'   => true,
        ]);
        $this->assignRole($salesUser, 'sales_executive', $this->salesDept);

        // Create 2 leads for today assigned to this sales user
        Lead::create([
            'company_id'  => $this->company->id,
            'assigned_to' => $salesUser->id,
            'lead_date'   => $today,
        ]);
        Lead::create([
            'company_id'  => $this->company->id,
            'assigned_to' => $salesUser->id,
            'lead_date'   => $today,
        ]);

        // Create development deliveries, overdue, allocation pending, and DM campaigns
        ProductionInitiation::create([
            'company_id'                 => $this->company->id,
            'department_id'              => $this->devDept->id,
            'project_delivery_date'      => $today,
            'project_execution_status'   => 'in_progress',
            'project_allocation_status'  => 'allocation_pending',
        ]);
        CustomerCampaign::create([
            'company_id' => $this->company->id,
            'status'     => 'active',
            'end_date'   => $today,
        ]);

        Sanctum::actingAs($salesUser);

        // 1. Leads today-count MUST succeed with count = 2
        $leadsRes = $this->getJson(route('mobile.leads.today-count'));
        $leadsRes->assertOk();
        $leadsRes->assertJson([
            'status' => true,
            'data'   => [
                'count' => 2,
            ],
        ]);

        // 2. Production notifications summary MUST return role_scope = 'sales' and ALL 0s
        $summaryRes = $this->getJson(route('mobile.projects.notifications.summary'));
        $summaryRes->assertOk();
        $summaryRes->assertJson([
            'status' => true,
            'data'   => [
                'role_scope'             => 'sales',
                'deliveries_today'       => 0,
                'deliveries_upcoming'    => 0,
                'deliveries_overdue'     => 0,
                'allocation_pending'     => 0,
                'renewals_today'         => 0,
                'campaigns_ending_today' => 0,
                'production_tasks_today' => 0,
                'design_overdue'         => 0,
            ],
        ]);
    }

    public function test_development_role_receives_only_assigned_production_and_no_leads(): void
    {
        $today = Carbon::now('Asia/Kolkata')->toDateString();

        $devUser = User::create([
            'company_id'  => $this->company->id,
            'name'        => 'Developer',
            'user_status' => 'active',
            'is_active'   => true,
        ]);
        $this->assignRole($devUser, 'developer', $this->devDept);

        // Create a lead
        Lead::create([
            'company_id'  => $this->company->id,
            'assigned_to' => $devUser->id,
            'lead_date'   => $today,
        ]);

        // Create an assigned development project delivery today
        ProductionInitiation::create([
            'company_id'                           => $this->company->id,
            'department_id'                        => $this->devDept->id,
            'project_delivery_date'                => $today,
            'project_execution_status'             => 'in_progress',
            'project_allocated_employee_user_ids'  => [$devUser->id],
        ]);

        // Create an unallocated company project (allocation pending)
        ProductionInitiation::create([
            'company_id'                => $this->company->id,
            'department_id'             => $this->devDept->id,
            'project_allocation_status' => 'allocation_pending',
        ]);

        // Create an active DM campaign ending today
        CustomerCampaign::create([
            'company_id' => $this->company->id,
            'status'     => 'active',
            'end_date'   => $today,
        ]);

        Sanctum::actingAs($devUser);

        // 1. Leads today-count MUST be 403 Forbidden for dev role
        $leadsRes = $this->getJson(route('mobile.leads.today-count'));
        $leadsRes->assertStatus(403);
        $leadsRes->assertJson(['status' => false]);

        // 2. Notifications summary MUST return role_scope = 'development'
        // deliveries_today = 1, but allocation_pending, DM renewals/campaigns, and design overdue MUST be 0!
        $summaryRes = $this->getJson(route('mobile.projects.notifications.summary'));
        $summaryRes->assertOk();
        $summaryRes->assertJson([
            'status' => true,
            'data'   => [
                'role_scope'             => 'development',
                'deliveries_today'       => 1,
                'allocation_pending'     => 0,
                'renewals_today'         => 0,
                'campaigns_ending_today' => 0,
                'design_overdue'         => 0,
            ],
        ]);
    }

    public function test_design_role_receives_only_design_metrics(): void
    {
        $today = Carbon::now('Asia/Kolkata')->toDateString();

        $designer = User::create([
            'company_id'  => $this->company->id,
            'name'        => 'UI Designer',
            'user_status' => 'active',
            'is_active'   => true,
        ]);
        $this->assignRole($designer, 'designer', $this->designDept);

        // Design task today
        ProductionTask::create([
            'company_id'  => $this->company->id,
            'assigned_to' => $designer->id,
            'task_date'   => $today,
        ]);

        // Design overdue project assigned to designer
        ProductionInitiation::create([
            'company_id'                          => $this->company->id,
            'department_id'                       => $this->designDept->id,
            'project_delivery_date'               => Carbon::yesterday()->toDateString(),
            'project_execution_status'            => 'in_progress',
            'project_allocated_employee_user_ids' => [$designer->id],
        ]);

        // Development delivery
        ProductionInitiation::create([
            'company_id'                          => $this->company->id,
            'department_id'                       => $this->devDept->id,
            'project_delivery_date'               => $today,
            'project_execution_status'            => 'in_progress',
            'project_allocated_employee_user_ids' => [$designer->id],
        ]);

        Sanctum::actingAs($designer);

        // Leads endpoint must be 403 Forbidden
        $this->getJson(route('mobile.leads.today-count'))->assertStatus(403);

        // Summary must show role_scope = 'design' with design task and overdue, but 0 deliveries/allocation/DM
        $summaryRes = $this->getJson(route('mobile.projects.notifications.summary'));
        $summaryRes->assertOk();
        $summaryRes->assertJson([
            'status' => true,
            'data'   => [
                'role_scope'             => 'design',
                'deliveries_today'       => 0,
                'allocation_pending'     => 0,
                'renewals_today'         => 0,
                'campaigns_ending_today' => 0,
                'production_tasks_today' => 1,
                'design_overdue'         => 1,
            ],
        ]);
    }

    public function test_digital_marketing_role_receives_only_dm_metrics(): void
    {
        $today = Carbon::now('Asia/Kolkata')->toDateString();

        $dmUser = User::create([
            'company_id'  => $this->company->id,
            'name'        => 'DM Executive',
            'user_status' => 'active',
            'is_active'   => true,
        ]);
        $this->assignRole($dmUser, 'digital_marketing_executive', $this->dmDept);

        // Campaign ending today created by DM user
        CustomerCampaign::create([
            'company_id' => $this->company->id,
            'created_by' => $dmUser->id,
            'status'     => 'active',
            'end_date'   => $today,
        ]);

        Sanctum::actingAs($dmUser);

        // Leads endpoint must be 403 Forbidden
        $this->getJson(route('mobile.leads.today-count'))->assertStatus(403);

        // Summary must show role_scope = 'digital_marketing' with DM campaign, but 0 dev deliveries, allocation, design
        $summaryRes = $this->getJson(route('mobile.projects.notifications.summary'));
        $summaryRes->assertOk();
        $summaryRes->assertJson([
            'status' => true,
            'data'   => [
                'role_scope'             => 'digital_marketing',
                'deliveries_today'       => 0,
                'allocation_pending'     => 0,
                'renewals_today'         => 1,
                'campaigns_ending_today' => 1,
                'design_overdue'         => 0,
            ],
        ]);
    }

    public function test_company_admin_and_cbo_receive_all_authorized_notifications(): void
    {
        $today = Carbon::now('Asia/Kolkata')->toDateString();

        $adminUser = User::create([
            'company_id'  => $this->company->id,
            'name'        => 'Company Admin',
            'user_status' => 'active',
            'is_active'   => true,
        ]);
        $this->assignRole($adminUser, 'company_admin');

        // Lead
        Lead::create([
            'company_id' => $this->company->id,
            'lead_date'  => $today,
        ]);

        // Development delivery
        ProductionInitiation::create([
            'company_id'               => $this->company->id,
            'department_id'            => $this->devDept->id,
            'project_delivery_date'    => $today,
            'project_execution_status' => 'in_progress',
        ]);

        // Allocation pending
        ProductionInitiation::create([
            'company_id'                => $this->company->id,
            'project_allocation_status' => 'allocation_pending',
        ]);

        // DM campaign ending today
        CustomerCampaign::create([
            'company_id' => $this->company->id,
            'status'     => 'active',
            'end_date'   => $today,
        ]);

        Sanctum::actingAs($adminUser);

        // Leads count must be authorized and return 1
        $leadsRes = $this->getJson(route('mobile.leads.today-count'));
        $leadsRes->assertOk();
        $leadsRes->assertJson([
            'status' => true,
            'data'   => [
                'count' => 1,
            ],
        ]);

        // Summary must show role_scope = 'admin' and all counts present
        $summaryRes = $this->getJson(route('mobile.projects.notifications.summary'));
        $summaryRes->assertOk();
        $summaryRes->assertJson([
            'status' => true,
            'data'   => [
                'role_scope'             => 'admin',
                'deliveries_today'       => 1,
                'allocation_pending'     => 1,
                'renewals_today'         => 1,
                'campaigns_ending_today' => 1,
            ],
        ]);
    }

    public function test_sales_tl_receives_only_lead_notifications_and_zero_production(): void
    {
        $today = Carbon::now('Asia/Kolkata')->toDateString();

        $salesTl = User::create([
            'company_id'  => $this->company->id,
            'name'        => 'Sales Team Lead',
            'user_status' => 'active',
            'is_active'   => true,
        ]);
        $this->assignRole($salesTl, 'sales_tl', $this->salesDept);

        // Create unallocated company project (535 projects case)
        ProductionInitiation::create([
            'company_id'                => $this->company->id,
            'project_allocation_status' => 'allocation_pending',
        ]);

        // Create DM campaign
        CustomerCampaign::create([
            'company_id' => $this->company->id,
            'status'     => 'active',
            'end_date'   => $today,
        ]);

        Sanctum::actingAs($salesTl);

        // Sales TL must be authorized for leads
        $leadsRes = $this->getJson(route('mobile.leads.today-count'));
        $leadsRes->assertOk();

        // Notifications summary MUST NOT fall to executive/admin or team_lead!
        // It must return role_scope = 'sales' and 0 allocation pending / DM / dev deliveries!
        $summaryRes = $this->getJson(route('mobile.projects.notifications.summary'));
        $summaryRes->assertOk();
        $summaryRes->assertJson([
            'status' => true,
            'data'   => [
                'role_scope'             => 'sales',
                'deliveries_today'       => 0,
                'allocation_pending'     => 0,
                'renewals_today'         => 0,
                'campaigns_ending_today' => 0,
                'design_overdue'         => 0,
            ],
        ]);
    }

    public function test_cbo_receives_all_authorized_notifications(): void
    {
        $today = Carbon::now('Asia/Kolkata')->toDateString();

        $cboUser = User::create([
            'company_id'  => $this->company->id,
            'name'        => 'Chief Business Officer',
            'user_status' => 'active',
            'is_active'   => true,
        ]);
        $this->assignRole($cboUser, 'cbo');

        // Lead
        Lead::create([
            'company_id' => $this->company->id,
            'lead_date'  => $today,
        ]);

        // Allocation pending
        ProductionInitiation::create([
            'company_id'                => $this->company->id,
            'project_allocation_status' => 'allocation_pending',
        ]);

        Sanctum::actingAs($cboUser);

        // Leads count authorized
        $this->getJson(route('mobile.leads.today-count'))->assertOk();

        // Summary role_scope = 'admin' and allocation_pending = 1
        $summaryRes = $this->getJson(route('mobile.projects.notifications.summary'));
        $summaryRes->assertOk();
        $summaryRes->assertJson([
            'status' => true,
            'data'   => [
                'role_scope'         => 'admin',
                'allocation_pending' => 1,
            ],
        ]);
    }

    public function test_in_app_notifications_feed_and_unread_count_filter_unauthorized_modules(): void
    {
        $salesUser = User::create([
            'company_id'  => $this->company->id,
            'name'        => 'Sales Executive',
            'user_status' => 'active',
            'is_active'   => true,
        ]);
        $this->assignRole($salesUser, 'sales_executive', $this->salesDept);

        // Insert two in-app notifications for sales user:
        // One CRM notification, one leaked production notification
        DB::table('notifications')->insert([
            'id'              => '11111111-1111-1111-1111-111111111111',
            'type'            => 'App\\Notifications\\CrmLeadAssignedNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $salesUser->id,
            'data'            => json_encode([
                'module'  => 'crm',
                'title'   => 'New Lead Assigned',
                'message' => 'Lead #100 has been assigned to you.',
            ]),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        DB::table('notifications')->insert([
            'id'              => '22222222-2222-2222-2222-222222222222',
            'type'            => 'App\\Notifications\\ProjectDeliveryNotification',
            'notifiable_type' => User::class,
            'notifiable_id'   => $salesUser->id,
            'data'            => json_encode([
                'module'  => 'projects',
                'title'   => 'Project Delivery',
                'message' => 'Project #50 is due today.',
            ]),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        Sanctum::actingAs($salesUser);

        // Unread count must only count the CRM notification (1), not the unauthorized projects notification (2)
        $unreadRes = $this->getJson('/api/mobile/notifications/unread-count');
        $unreadRes->assertOk();
        $unreadRes->assertJson([
            'success' => true,
            'data'    => [
                'unread_count' => 1,
            ],
        ]);

        // Notifications list must only contain the CRM notification
        $listRes = $this->getJson('/api/mobile/notifications');
        $listRes->assertOk();
        $this->assertCount(1, $listRes->json('data.notifications'));
        $this->assertEquals('crm', $listRes->json('data.notifications.0.module'));
    }
}
