<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class EmployeeOnboardingAccessTest extends TestCase
{
    /** @test */
    public function super_admin_role_identifies_as_hr_or_admin()
    {
        $role = new Role();
        $role->name = 'super_admin';

        $user = new User();
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->isHrOrAdmin());
    }

    /** @test */
    public function company_admin_role_identifies_as_hr_or_admin()
    {
        $role = new Role();
        $role->name = 'company_admin';

        $user = new User();
        $user->company_id = 1;
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->isHrOrAdmin());
    }

    /** @test */
    public function hr_role_identifies_as_hr_or_admin()
    {
        $role = new Role();
        $role->name = 'hr';

        $user = new User();
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->isHrOrAdmin());
    }

    /** @test */
    public function hr_department_user_identifies_as_hr_or_admin()
    {
        $department = new Department();
        $department->name = 'HR';

        $role = new Role();
        $role->name = 'custom_role';
        $role->setRelation('department', $department);

        $user = new User();
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->isHrOrAdmin());
    }

    /** @test */
    public function sales_executive_user_does_not_identify_as_hr_or_admin()
    {
        $department = new Department();
        $department->name = 'Sales';

        $role = new Role();
        $role->name = 'sales_executive';
        $role->setRelation('department', $department);

        $user = new User();
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertFalse($user->isHrOrAdmin());
    }
}
