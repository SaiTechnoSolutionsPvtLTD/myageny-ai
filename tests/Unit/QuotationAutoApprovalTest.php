<?php

namespace Tests\Unit;

use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class QuotationAutoApprovalTest extends TestCase
{
    public function test_company_admin_can_auto_approve_quotation(): void
    {
        $role = new Role();
        $role->name = 'company_admin';

        $user = new User();
        $user->company_id = 1;
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->isCompanyAdminRole());
        $this->assertTrue($user->canAutoApproveQuotation());
    }

    public function test_tenant_prefixed_company_admin_can_auto_approve_quotation(): void
    {
        $role = new Role();
        $role->name = 'company_1__company_admin';
        $role->display_name = 'Company Admin';

        $user = new User();
        $user->company_id = 1;
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->isCompanyAdminRole());
        $this->assertTrue($user->canAutoApproveQuotation());
    }

    public function test_cbo_role_can_auto_approve_quotation(): void
    {
        $role = new Role();
        $role->name = 'cbo';

        $user = new User();
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->isCbo());
        $this->assertTrue($user->canAutoApproveQuotation());
    }

    public function test_chief_business_officer_role_can_auto_approve_quotation(): void
    {
        $role = new Role();
        $role->name = 'company_1__chief_business_officer';
        $role->display_name = 'Chief Business Officer';

        $user = new User();
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertTrue($user->isCbo());
        $this->assertTrue($user->canAutoApproveQuotation());
    }

    public function test_sales_executive_cannot_auto_approve_quotation(): void
    {
        $role = new Role();
        $role->name = 'sales_executive';
        $role->display_name = 'Sales Executive';

        $user = new User();
        $user->setRelation('roles', new EloquentCollection([$role]));

        $this->assertFalse($user->isCompanyAdminRole());
        $this->assertFalse($user->isCbo());
        $this->assertFalse($user->canAutoApproveQuotation());
    }
}
