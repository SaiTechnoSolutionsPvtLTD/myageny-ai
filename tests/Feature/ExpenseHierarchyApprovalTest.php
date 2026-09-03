<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ExpenseCategory;
use App\Models\ExpensePipeline;
use App\Models\ExpenseRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpenseHierarchyApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('expense_requests');
        Schema::dropIfExists('expense_pipelines');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('company_status')->default('active');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->foreignId('department_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('user_status')->nullable()->default('active');
            $table->string('designation')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('model_has_roles', function ($table) {
            $table->foreignId('role_id');
            $table->string('model_type');
            $table->foreignId('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('expense_categories', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expense_pipelines', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('role_id');
            $table->json('approval_chain');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_requests', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('user_id');
            $table->foreignId('expense_category_id');
            $table->decimal('amount', 12, 2);
            $table->text('description');
            $table->string('attachment')->nullable();
            $table->integer('current_step')->default(1);
            $table->foreignId('current_approver_role_id')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approver_id')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('stage_history')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->foreignId('lead_id')->nullable();
            $table->string('lead_title')->nullable();
            $table->string('module')->default('General');
            $table->string('action');
            $table->text('description');
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable()->default('GET');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function test_multi_stage_hierarchy_approval_flow()
    {
        $company = Company::create([
            'company_name'   => 'Tech Corp',
            'company_status' => 'active',
            'expiry_date'    => now()->addYear(),
        ]);

        // 1. Create Roles: App Developer, Dev Coordinator, HR
        $appDevRole = Role::create([
            'company_id'   => $company->id,
            'name'         => 'company_' . $company->id . '__app_developer',
            'display_name' => 'App Developer',
        ]);

        $devCoordRole = Role::create([
            'company_id'   => $company->id,
            'name'         => 'company_' . $company->id . '__development_project_coordinator',
            'display_name' => 'Development Project Coordinator',
        ]);

        $hrRole = Role::create([
            'company_id'   => $company->id,
            'name'         => 'company_' . $company->id . '__hr',
            'display_name' => 'HR Manager',
        ]);

        // 2. Create Users
        $appDevUser = User::create([
            'company_id' => $company->id,
            'name'       => 'Alex (App Developer)',
            'email'      => 'alex@example.com',
            'is_active'  => true,
        ]);
        \DB::table('model_has_roles')->insert([
            'role_id'    => $appDevRole->id,
            'model_type' => User::class,
            'model_id'   => $appDevUser->id,
        ]);

        $devCoordUser = User::create([
            'company_id' => $company->id,
            'name'       => 'Karthik (Dev Coordinator)',
            'email'      => 'karthik@example.com',
            'is_active'  => true,
        ]);
        \DB::table('model_has_roles')->insert([
            'role_id'    => $devCoordRole->id,
            'model_type' => User::class,
            'model_id'   => $devCoordUser->id,
        ]);

        $hrUser = User::create([
            'company_id' => $company->id,
            'name'       => 'Priya (HR Manager)',
            'email'      => 'priya@example.com',
            'is_active'  => true,
        ]);
        \DB::table('model_has_roles')->insert([
            'role_id'    => $hrRole->id,
            'model_type' => User::class,
            'model_id'   => $hrUser->id,
        ]);

        // 3. Configure Expense Pipeline for App Developer: [Dev Coordinator (Stage 1), HR (Stage 2)]
        ExpensePipeline::create([
            'company_id'     => $company->id,
            'role_id'        => $appDevRole->id,
            'approval_chain' => [$devCoordRole->id, $hrRole->id],
            'is_active'      => true,
        ]);

        $category = ExpenseCategory::create([
            'company_id' => $company->id,
            'name'       => 'Software License',
        ]);

        // 4. App Developer submits Expense Request
        $expenseRequest = ExpenseRequest::create([
            'company_id'               => $company->id,
            'user_id'                  => $appDevUser->id,
            'expense_category_id'      => $category->id,
            'amount'                   => 2500.00,
            'description'              => 'IDE & API Subscription Renewal',
            'current_step'             => 1,
            'current_approver_role_id' => $devCoordRole->id,
            'status'                   => 'pending',
            'stage_history'            => [],
        ]);

        // Assert Stage 1 is active
        $this->assertEquals(1, $expenseRequest->current_step);
        $this->assertEquals($devCoordRole->id, $expenseRequest->current_approver_role_id);
        $this->assertEquals('pending', $expenseRequest->status);

        // Verification: App Developer cannot approve own request
        $this->assertFalse($expenseRequest->canUserAction($appDevUser));

        // Verification: HR cannot approve Stage 1 (must wait for Dev Coordinator)
        $this->assertFalse($expenseRequest->canUserAction($hrUser));

        // Verification: Dev Coordinator CAN approve Stage 1
        $this->assertTrue($expenseRequest->canUserAction($devCoordUser));

        // 5. Dev Coordinator approves Stage 1 via Controller
        $response = $this->actingAs($devCoordUser)
            ->post(route('hrms.expense-requests.approve', $expenseRequest));
        $response->assertRedirect(route('hrms.expense-requests.index'));

        $expenseRequest->refresh();

        // Assert Request moved to Stage 2 (HR)
        $this->assertEquals(2, $expenseRequest->current_step);
        $this->assertEquals($hrRole->id, $expenseRequest->current_approver_role_id);
        $this->assertEquals('pending', $expenseRequest->status);
        $this->assertCount(1, $expenseRequest->stage_history);
        $this->assertEquals('approved', $expenseRequest->stage_history[0]['action']);
        $this->assertEquals($devCoordUser->id, $expenseRequest->stage_history[0]['user_id']);

        // Verification: Dev Coordinator cannot approve Stage 2 (already approved Stage 1)
        $this->assertFalse($expenseRequest->canUserAction($devCoordUser));

        // Verification: HR CAN now approve Stage 2
        $this->assertTrue($expenseRequest->canUserAction($hrUser));

        // 6. HR approves Stage 2
        $response2 = $this->actingAs($hrUser)
            ->post(route('hrms.expense-requests.approve', $expenseRequest));
        $response2->assertRedirect(route('hrms.expense-requests.index'));

        $expenseRequest->refresh();

        // Assert Final Approval
        $this->assertEquals('approved', $expenseRequest->status);
        $this->assertEquals($hrUser->id, $expenseRequest->approver_id);
        $this->assertNotNull($expenseRequest->actioned_at);
        $this->assertCount(2, $expenseRequest->stage_history);
        $this->assertEquals('approved', $expenseRequest->stage_history[1]['action']);
        $this->assertEquals($hrUser->id, $expenseRequest->stage_history[1]['user_id']);

        // Verify stages visualization attribute
        $stages = $expenseRequest->approval_stages;
        $this->assertCount(2, $stages);
        $this->assertEquals('completed', $stages[0]['status']);
        $this->assertEquals('completed', $stages[1]['status']);
    }

    public function test_multi_stage_hierarchy_rejection_flow()
    {
        $company = Company::create([
            'company_name'   => 'Tech Corp',
            'company_status' => 'active',
            'expiry_date'    => now()->addYear(),
        ]);

        $appDevRole = Role::create([
            'company_id'   => $company->id,
            'name'         => 'company_' . $company->id . '__app_developer',
            'display_name' => 'App Developer',
        ]);

        $devCoordRole = Role::create([
            'company_id'   => $company->id,
            'name'         => 'company_' . $company->id . '__development_project_coordinator',
            'display_name' => 'Development Project Coordinator',
        ]);

        $appDevUser = User::create([
            'company_id' => $company->id,
            'name'       => 'Alex (App Developer)',
            'email'      => 'alex@example.com',
            'is_active'  => true,
        ]);
        \DB::table('model_has_roles')->insert([
            'role_id'    => $appDevRole->id,
            'model_type' => User::class,
            'model_id'   => $appDevUser->id,
        ]);

        $devCoordUser = User::create([
            'company_id' => $company->id,
            'name'       => 'Karthik (Dev Coordinator)',
            'email'      => 'karthik@example.com',
            'is_active'  => true,
        ]);
        \DB::table('model_has_roles')->insert([
            'role_id'    => $devCoordRole->id,
            'model_type' => User::class,
            'model_id'   => $devCoordUser->id,
        ]);

        $category = ExpenseCategory::create([
            'company_id' => $company->id,
            'name'       => 'Travel Expense',
        ]);

        $expenseRequest = ExpenseRequest::create([
            'company_id'               => $company->id,
            'user_id'                  => $appDevUser->id,
            'expense_category_id'      => $category->id,
            'amount'                   => 500.00,
            'description'              => 'Cab fare for client demo',
            'current_step'             => 1,
            'current_approver_role_id' => $devCoordRole->id,
            'status'                   => 'pending',
            'stage_history'            => [],
        ]);

        // Dev Coordinator rejects at Stage 1
        $response = $this->actingAs($devCoordUser)
            ->post(route('hrms.expense-requests.reject', $expenseRequest), [
                'rejection_reason' => 'Receipt not attached for review.',
            ]);

        $response->assertRedirect(route('hrms.expense-requests.index'));

        $expenseRequest->refresh();
        $this->assertEquals('rejected', $expenseRequest->status);
        $this->assertEquals('Receipt not attached for review.', $expenseRequest->rejection_reason);
        $this->assertEquals($devCoordUser->id, $expenseRequest->approver_id);
        $this->assertCount(1, $expenseRequest->stage_history);
        $this->assertEquals('rejected', $expenseRequest->stage_history[0]['action']);
    }
}
