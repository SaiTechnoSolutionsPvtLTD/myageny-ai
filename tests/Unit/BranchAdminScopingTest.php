<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use App\Models\Lead;
use App\Models\EmployeeOnboarding;
use App\Models\InternJoiningForm;
use App\Models\DailyAttendance;
use App\Models\Quotation;
use App\Models\QuotationSetting;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class BranchAdminScopingTest extends TestCase
{
    /** @test */
    public function branch_admin_global_scopes_are_applied_to_sql_queries()
    {
        // 1. Create a branch admin user in memory
        $role = new Role();
        $role->name = 'company_9__branch_admin';

        $user = new User();
        $user->id = 123;
        $user->company_id = 9;
        $user->branch_id = 456;
        $user->setRelation('roles', new EloquentCollection([$role]));

        // Log in the user
        auth()->login($user);

        // 2. Assert query scopes are correctly compiled into SQL (using SQLite double-quote styling)
        $leadSql = Lead::query()->toSql();
        $employeeSql = EmployeeOnboarding::query()->toSql();
        $internSql = InternJoiningForm::query()->toSql();
        $attendanceSql = DailyAttendance::query()->toSql();
        $quotationSql = Quotation::query()->toSql();
        $settingSql = QuotationSetting::query()->toSql();
        $branchSql = Branch::query()->toSql();

        // Lead
        $this->assertStringContainsString('"leads"."branch_id" = ?', $leadSql);

        // EmployeeOnboarding
        $this->assertStringContainsString('exists (select * from "users" where "employee_onboardings"."portal_user_id" = "users"."id" and "branch_id" = ?', $employeeSql);

        // InternJoiningForm
        $this->assertStringContainsString('exists (select * from "users" where "intern_joining_forms"."portal_user_id" = "users"."id" and "branch_id" = ?', $internSql);

        // DailyAttendance
        $this->assertStringContainsString('"branch_id" = ?', $attendanceSql);

        // Quotation
        $this->assertStringContainsString('exists (select * from "leads" where "quotations"."lead_id" = "leads"."id" and "branch_id" = ?', $quotationSql);

        // QuotationSetting
        $this->assertStringContainsString('"quotation_settings"."branch_id" = ?', $settingSql);

        // Branch
        $this->assertStringContainsString('"branches"."id" = ?', $branchSql);
        
        // Assert DataVisibilityService gives them ACCESS_COMPANY level so they see all executives in their branch
        $visibilityService = app(\App\Services\DataVisibilityService::class);
        $this->assertEquals(\App\Models\RoleMapping::ACCESS_COMPANY, $visibilityService->accessLevelFor($user));
        $this->assertNull($visibilityService->visibleUserIds($user));

        // Clean up session
        auth()->logout();
    }
}
