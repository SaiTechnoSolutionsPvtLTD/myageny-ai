<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserEmployeeRoleSyncTest extends TestCase
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
                $table->unsignedInteger('number_of_accounts')->nullable()->default(50);
                $table->unsignedInteger('super_admin_user_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('code')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('display_name')->nullable();
                $table->text('description')->nullable();
                $table->foreignId('department_id')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name')->default('web');
                $table->string('display_name')->nullable();
                $table->string('module')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->foreignId('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
            });
        }

        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->foreignId('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->foreignId('permission_id');
                $table->foreignId('role_id');
            });
        }

        if (! Schema::hasTable('branch_user')) {
            Schema::create('branch_user', function (Blueprint $table) {
                $table->foreignId('branch_id');
                $table->foreignId('user_id');
            });
        }

        if (! Schema::hasTable('role_mappings')) {
            Schema::create('role_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id');
                $table->foreignId('company_id')->nullable();
                $table->string('access_level')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_mappings')) {
            Schema::create('user_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('manager_id')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('role_hierarchy_mappings')) {
            Schema::create('role_hierarchy_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_role_id')->nullable();
                $table->foreignId('child_role_id')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('password')->nullable();
                $table->string('phone')->nullable();
                $table->string('designation')->nullable();
                $table->string('user_status')->default('active');
                $table->foreignId('company_id')->nullable();
                $table->foreignId('branch_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->string('last_login_ip')->nullable();
                $table->string('avatar')->nullable();
                $table->string('photo')->nullable();
                $table->rememberToken();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_onboardings')) {
            Schema::create('employee_onboardings', function (Blueprint $table) {
                $table->id();
                $table->string('employee_id')->nullable();
                $table->foreignId('role_id')->nullable();
                $table->foreignId('department_id')->nullable();
                $table->foreignId('portal_user_id')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->string('name');
                $table->string('email');
                $table->string('official_email')->nullable();
                $table->string('mobile')->nullable();
                $table->string('official_mobile')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('marital_status')->nullable();
                $table->date('date_of_marriage')->nullable();
                $table->string('status')->default('active');
                $table->foreignId('created_by')->nullable();
                $table->foreignId('updated_by')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_educations')) {
            Schema::create('employee_educations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_onboarding_id');
                $table->string('qualification')->nullable();
                $table->string('institution_name')->nullable();
                $table->string('year_of_passing')->nullable();
                $table->string('percentage')->nullable();
                $table->string('specialization')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_employments')) {
            Schema::create('employee_employments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_onboarding_id');
                $table->string('organisation')->nullable();
                $table->string('designation')->nullable();
                $table->date('period_from')->nullable();
                $table->date('period_to')->nullable();
                $table->string('annual_ctc')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_family_details')) {
            Schema::create('employee_family_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_onboarding_id');
                $table->string('name')->nullable();
                $table->string('relation')->nullable();
                $table->string('occupation')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('mobile_no')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('intern_joining_forms')) {
            Schema::create('intern_joining_forms', function (Blueprint $table) {
                $table->id();
                $table->string('intern_id')->nullable();
                $table->foreignId('role_id')->nullable();
                $table->foreignId('department_id')->nullable();
                $table->foreignId('portal_user_id')->nullable();
                $table->foreignId('company_id')->nullable();
                $table->string('name');
                $table->string('email');
                $table->string('internship_status')->default('active');
                $table->timestamps();
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    /** @test */
    public function role_change_on_users_page_updates_employee_role()
    {
        $company = Company::create(['company_name' => 'Test Company']);
        $dept = Department::create(['name' => 'Tech', 'company_id' => $company->id]);
        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'company_id' => $company->id]);

        $role1 = Role::create([
            'name' => 'developer',
            'display_name' => 'Developer',
            'guard_name' => 'web',
            'department_id' => $dept->id,
            'company_id' => $company->id,
        ]);

        $role2 = Role::create([
            'name' => 'team_lead',
            'display_name' => 'Team Lead',
            'guard_name' => 'web',
            'department_id' => $dept->id,
            'company_id' => $company->id,
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $adminRole = Role::create(['name' => 'company_admin', 'display_name' => 'Company Admin', 'guard_name' => 'web', 'company_id' => $company->id]);
        $admin->syncRoles([$adminRole]);
        $perm = \App\Models\Permission::create(['name' => 'users.manage', 'guard_name' => 'web']);
        $admin->givePermissionTo($perm);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->syncRoles([$role1]);

        $employee = EmployeeOnboarding::create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'employee_id' => 'STS001',
            'portal_user_id' => $user->id,
            'role_id' => $role1->id,
            'department_id' => $dept->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->assertEquals($role1->id, $employee->fresh()->role_id);

        // Update User's role to $role2 via PUT /users/{user}
        $response = $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => 'John Doe',
                'email' => 'john@test.com',
                'role' => $role2->name,
                'branch_id' => $branch->id,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue($user->fresh()->hasRole('team_lead'));
        $this->assertEquals($role2->id, $employee->fresh()->role_id);
    }

    /** @test */
    public function role_assignment_on_user_show_page_updates_employee_role()
    {
        $company = Company::create(['company_name' => 'Test Company']);
        $dept = Department::create(['name' => 'Tech', 'company_id' => $company->id]);
        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'company_id' => $company->id]);

        $role1 = Role::create(['name' => 'developer', 'display_name' => 'Developer', 'guard_name' => 'web', 'department_id' => $dept->id, 'company_id' => $company->id]);
        $role2 = Role::create(['name' => 'team_lead', 'display_name' => 'Team Lead', 'guard_name' => 'web', 'department_id' => $dept->id, 'company_id' => $company->id]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $adminRole = Role::create(['name' => 'super_admin', 'display_name' => 'Super Admin', 'guard_name' => 'web']);
        $admin->syncRoles([$adminRole]);

        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@test.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $user->syncRoles([$role1]);

        $employee = EmployeeOnboarding::create([
            'name' => 'Jane Doe',
            'email' => 'jane@test.com',
            'employee_id' => 'STS002',
            'portal_user_id' => $user->id,
            'role_id' => $role1->id,
            'department_id' => $dept->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('auth.users.assign-role', $user), [
                'roles' => [$role2->name],
            ]);

        $this->assertTrue($user->fresh()->hasRole('team_lead'));
        $this->assertEquals($role2->id, $employee->fresh()->role_id);
    }

    /** @test */
    public function role_change_on_employee_page_updates_user_role()
    {
        $company = Company::create(['company_name' => 'Test Company']);
        $dept = Department::create(['name' => 'Tech', 'company_id' => $company->id]);
        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'company_id' => $company->id]);

        $role1 = Role::create(['name' => 'developer', 'display_name' => 'Developer', 'guard_name' => 'web', 'department_id' => $dept->id, 'company_id' => $company->id]);
        $role2 = Role::create(['name' => 'team_lead', 'display_name' => 'Team Lead', 'guard_name' => 'web', 'department_id' => $dept->id, 'company_id' => $company->id]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $adminRole = Role::create(['name' => 'super_admin', 'display_name' => 'Super Admin', 'guard_name' => 'web']);
        $admin->syncRoles([$adminRole]);

        $user = User::create([
            'name' => 'Alex Smith',
            'email' => 'alex@test.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->syncRoles([$role1]);

        $employee = EmployeeOnboarding::create([
            'name' => 'Alex Smith',
            'email' => 'alex@test.com',
            'mobile' => '9876543210',
            'employee_id' => 'STS003',
            'portal_user_id' => $user->id,
            'role_id' => $role1->id,
            'department_id' => $dept->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->assertTrue($user->fresh()->hasRole('developer'));

        // Update employee onboarding with new role
        $response = $this->actingAs($admin)
            ->put(route('employee-onboarding.update', $employee), [
                'name' => 'Alex Smith',
                'email' => 'alex@test.com',
                'mobile' => '9876543210',
                'employee_id' => 'STS003',
                'role_id' => $role2->id,
                'department_id' => $dept->id,
                'branch_id' => $branch->id,
                'portal_email' => 'alex@test.com',
                'status' => 'active',
                'tl_user_id' => $admin->id,
                'marital_status' => 'single',
                'date_of_birth' => '1995-05-15',
            ]);

        $this->assertEquals($role2->id, $employee->fresh()->role_id);
        $this->assertTrue($user->fresh()->hasRole('team_lead'));
    }

    /** @test */
    public function role_change_on_employee_page_links_and_updates_unlinked_user_with_matching_email()
    {
        $company = Company::create(['company_name' => 'Test Company']);
        $dept = Department::create(['name' => 'Tech', 'company_id' => $company->id]);
        $branch = Branch::create(['name' => 'HQ', 'code' => 'HQ', 'company_id' => $company->id]);

        $role1 = Role::create(['name' => 'developer', 'display_name' => 'Developer', 'guard_name' => 'web', 'department_id' => $dept->id, 'company_id' => $company->id]);
        $role2 = Role::create(['name' => 'team_lead', 'display_name' => 'Team Lead', 'guard_name' => 'web', 'department_id' => $dept->id, 'company_id' => $company->id]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'is_active' => true,
        ]);
        $adminRole = Role::create(['name' => 'super_admin', 'display_name' => 'Super Admin', 'guard_name' => 'web']);
        $admin->syncRoles([$adminRole]);

        // User exists with email 'sam@test.com' but employee has portal_user_id = null
        $user = User::create([
            'name' => 'Sam Wilson',
            'email' => 'sam@test.com',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);
        $user->syncRoles([$role1]);

        $employee = EmployeeOnboarding::create([
            'name' => 'Sam Wilson',
            'email' => 'sam@test.com',
            'mobile' => '9876543211',
            'employee_id' => 'STS004',
            'portal_user_id' => null, // unlinked!
            'role_id' => $role1->id,
            'department_id' => $dept->id,
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)
            ->put(route('employee-onboarding.update', $employee), [
                'name' => 'Sam Wilson',
                'email' => 'sam@test.com',
                'mobile' => '9876543211',
                'employee_id' => 'STS004',
                'role_id' => $role2->id,
                'department_id' => $dept->id,
                'branch_id' => $branch->id,
                'portal_email' => 'sam@test.com',
                'status' => 'active',
                'tl_user_id' => $admin->id,
                'marital_status' => 'single',
                'date_of_birth' => '1995-05-15',
            ]);

        $this->assertEquals($role2->id, $employee->fresh()->role_id);
        $this->assertEquals($user->id, $employee->fresh()->portal_user_id);
        $this->assertTrue($user->fresh()->hasRole('team_lead'));
    }
}
