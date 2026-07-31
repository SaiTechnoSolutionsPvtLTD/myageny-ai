<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class UserDashboardRouteTest extends TestCase
{
    /** @test */
    public function sales_executive_user_without_department_gets_crm_dashboard()
    {
        $department = new Department();
        $department->name = 'Sales';
        $department->dashboard_route = '';

        $role = new Role();
        $role->name = 'company_9__sales_executive';
        $role->setRelation('department', $department);

        $user = new User();
        $user->company_id = 9;
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertEquals('dashboard.admin', $user->dashboardRoute());
    }

    /** @test */
    public function general_executive_user_without_department_gets_hrms_dashboard()
    {
        $department = new Department();
        $department->name = 'Development';
        $department->dashboard_route = '';

        $role = new Role();
        $role->name = 'company_9__executive';
        $role->setRelation('department', $department);

        $user = new User();
        $user->company_id = 9;
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertEquals('hrms.dashboard', $user->dashboardRoute());
    }

    /** @test */
    public function digital_marketing_user_can_access_crm_module()
    {
        $department = new Department();
        $department->name = 'Digital Marketing';

        $role = new Role();
        $role->name = 'digital_marketing_tl';
        $role->setRelation('department', $department);

        $user = new User();
        $user->company_id = 1;
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->canAccessCrmModule());
    }
}
