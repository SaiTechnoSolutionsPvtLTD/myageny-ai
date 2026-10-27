<?php

namespace Tests\Feature;

use App\Http\Requests\StoreEmployeeOnboardingRequest;
use App\Http\Requests\UpdateEmployeeOnboardingRequest;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeOnboardingOfficialContactTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        if (! Schema::hasTable('companies')) {
            Schema::create('companies', function (Blueprint $table) {
                $table->id();
                $table->string('company_name')->nullable();
                $table->string('company_status')->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('user_status')->default('active');
                $table->foreignId('company_id')->nullable();
                $table->foreignId('branch_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->foreignId('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('guard_name')->default('web');
                $table->string('display_name')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->foreignId('department_id')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->foreignId('permission_id');
                $table->foreignId('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('code')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_educations')) {
            Schema::create('employee_educations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_onboarding_id')->nullable();
                $table->string('qualification')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_family_details')) {
            Schema::create('employee_family_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_onboarding_id')->nullable();
                $table->string('name')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_onboardings')) {
            Schema::create('employee_onboardings', function (Blueprint $table) {
                $table->id();
                $table->string('employee_id')->nullable();
                $table->string('name')->nullable();
                $table->string('father_name')->nullable();
                $table->text('correspondence_address')->nullable();
                $table->text('permanent_address')->nullable();
                $table->string('mobile', 20)->nullable();
                $table->string('official_mobile', 20)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('official_email', 150)->nullable();
                $table->date('date_of_birth')->nullable();
                $table->date('joining_date')->nullable();
                $table->string('blood_group', 10)->nullable();
                $table->string('marital_status')->default('single');
                $table->date('date_of_marriage')->nullable();
                $table->string('aadhaar_card_no', 20)->nullable();
                $table->string('pan_card_no', 20)->nullable();
                $table->string('photograph')->nullable();
                $table->string('status')->default('active');
                $table->string('employee_type')->default('billable');
                $table->foreignId('portal_user_id')->nullable();
                $table->foreignId('role_id')->nullable();
                $table->foreignId('department_id')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /** @test */
    public function employee_onboarding_model_has_official_contact_fields_in_fillable()
    {
        $employee = new EmployeeOnboarding();
        $this->assertContains('official_mobile', $employee->getFillable());
        $this->assertContains('official_email', $employee->getFillable());
    }

    /** @test */
    public function store_request_validation_rules_include_official_contact_fields()
    {
        $request = new StoreEmployeeOnboardingRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('official_mobile', $rules);
        $this->assertArrayHasKey('official_email', $rules);
    }

    /** @test */
    public function update_request_validation_rules_include_official_contact_fields()
    {
        $request = new UpdateEmployeeOnboardingRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('official_mobile', $rules);
        $this->assertArrayHasKey('official_email', $rules);
    }

    /** @test */
    public function official_mobile_and_official_email_can_be_stored_and_retrieved()
    {
        $employee = EmployeeOnboarding::create([
            'employee_id' => 'TEST_EMP_' . uniqid(),
            'name' => 'Official Contact Test',
            'mobile' => '9876543210',
            'official_mobile' => '9123456780',
            'email' => 'personal_test@example.com',
            'official_email' => 'official_test@example.com',
            'date_of_birth' => '1995-05-15',
            'marital_status' => 'single',
            'status' => 'active',
        ]);

        $this->assertNotNull($employee->id);
        $fresh = EmployeeOnboarding::find($employee->id);
        $this->assertEquals('9123456780', $fresh->official_mobile);
        $this->assertEquals('official_test@example.com', $fresh->official_email);

        $fresh->update([
            'official_mobile' => '9988776655',
            'official_email' => 'updated_official@example.com',
        ]);

        $this->assertEquals('9988776655', $fresh->fresh()->official_mobile);
        $this->assertEquals('updated_official@example.com', $fresh->fresh()->official_email);
    }

    /** @test */
    public function create_view_contains_official_contact_input_fields()
    {
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->actingAs($user);

        $view = $this->view('pages.hrms.employee_onboarding.form', [
            'employee' => null,
            'generatedEmployeeId' => 'STS9999',
            'action' => route('employee-onboarding.store'),
            'method' => 'POST',
            'submitLabel' => 'Create Employee',
            'cancelRoute' => route('employee-onboarding.index'),
            'documentLabels' => [],
            'roles' => Role::all(),
            'departments' => Department::all(),
            'branches' => Branch::all(),
            'tlUsers' => collect(),
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ]);

        $view->assertSee('Official Mobile Number');
        $view->assertSee('name="official_mobile"', false);
        $view->assertSee('Official E-mail ID');
        $view->assertSee('name="official_email"', false);
    }

    /** @test */
    public function edit_view_contains_official_contact_values()
    {
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->actingAs($user);

        $employee = EmployeeOnboarding::create([
            'employee_id' => 'TEST_EDIT_' . uniqid(),
            'name' => 'Edit Contact Test',
            'mobile' => '9876543210',
            'official_mobile' => '9888877777',
            'email' => 'edit_personal@example.com',
            'official_email' => 'edit_official@example.com',
            'date_of_birth' => '1995-05-15',
            'marital_status' => 'single',
            'status' => 'active',
        ]);
        $employee->setRelation('educations', collect());
        $employee->setRelation('employments', collect());
        $employee->setRelation('familyDetails', collect());
        $employee->setRelation('portalUser', null);

        $view = $this->view('pages.hrms.employee_onboarding.form', [
            'employee' => $employee,
            'generatedEmployeeId' => $employee->employee_id,
            'action' => route('employee-onboarding.update', $employee),
            'method' => 'PUT',
            'submitLabel' => 'Save Changes',
            'cancelRoute' => route('employee-onboarding.show', $employee),
            'documentLabels' => [],
            'roles' => Role::all(),
            'departments' => Department::all(),
            'branches' => Branch::all(),
            'tlUsers' => collect(),
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ]);

        $view->assertSee('Official Mobile Number');
        $view->assertSee('value="9888877777"', false);
        $view->assertSee('Official E-mail ID');
        $view->assertSee('value="edit_official@example.com"', false);
    }

    /** @test */
    public function employee_onboarding_list_is_sorted_by_employee_id_descending_by_default()
    {
        $adminRole = Role::create(['name' => 'super_admin']);
        $user = User::create([
            'name' => 'HR Admin',
            'email' => 'hr_admin@example.com',
            'is_active' => true,
        ]);
        $user->setRelation('roles', new \Illuminate\Database\Eloquent\Collection([$adminRole]));

        EmployeeOnboarding::create([
            'employee_id' => 'EMP001',
            'name' => 'First Employee',
            'mobile' => '9876543211',
            'email' => 'emp1@test.com',
            'date_of_birth' => '1995-01-01',
            'marital_status' => 'single',
            'status' => 'active',
        ]);

        EmployeeOnboarding::create([
            'employee_id' => 'EMP003',
            'name' => 'Third Employee',
            'mobile' => '9876543213',
            'email' => 'emp3@test.com',
            'date_of_birth' => '1995-03-01',
            'marital_status' => 'single',
            'status' => 'active',
        ]);

        EmployeeOnboarding::create([
            'employee_id' => 'EMP002',
            'name' => 'Second Employee',
            'mobile' => '9876543212',
            'email' => 'emp2@test.com',
            'date_of_birth' => '1995-02-01',
            'marital_status' => 'single',
            'status' => 'active',
        ]);

        $controller = app(\App\Http\Controllers\EmployeeOnboardingController::class);
        $request = \Illuminate\Http\Request::create(route('employee-onboarding.index'), 'GET');
        auth()->setUser($user);
        $view = $controller->index($request);

        $employees = $view->getData()['employees'];
        $this->assertEquals(['EMP003', 'EMP002', 'EMP001'], $employees->pluck('employee_id')->take(3)->values()->all());
    }
}
