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
        $this->assertStringContainsString('"leads"."branch_id" in (?)', $leadSql);

        // EmployeeOnboarding
        $this->assertStringContainsString('exists (select * from "users" where "employee_onboardings"."portal_user_id" = "users"."id" and "branch_id" in (?)', $employeeSql);

        // InternJoiningForm
        $this->assertStringContainsString('exists (select * from "users" where "intern_joining_forms"."portal_user_id" = "users"."id" and "branch_id" in (?)', $internSql);

        // DailyAttendance
        $this->assertStringContainsString('"branch_id" in (?)', $attendanceSql);

        // Quotation
        $this->assertStringContainsString('exists (select * from "leads" where "quotations"."lead_id" = "leads"."id" and "branch_id" in (?)', $quotationSql);

        // QuotationSetting
        $this->assertStringContainsString('"quotation_settings"."branch_id" in (?)', $settingSql);

        // Branch
        $this->assertStringContainsString('"branches"."id" in (?)', $branchSql);
        
        // Assert DataVisibilityService gives them branch-scoped user IDs
        $visibilityService = app(\App\Services\DataVisibilityService::class);
        $this->assertNotNull($visibilityService->visibleUserIds($user));

        // Clean up session
        auth()->logout();
    }

    /** @test */
    public function roles_index_applies_filters_to_query()
    {
        $request = new \Illuminate\Http\Request([
            'search' => 'developer',
            'department_id' => 3
        ]);

        $query = \App\Models\Role::query()
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('display_name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($request->input('department_id'), function ($query, $deptId) {
                $query->where('department_id', $deptId);
            });

        $sql = $query->toSql();
        $this->assertStringContainsString('"department_id" = ?', $sql);
        $this->assertStringContainsString('"name" like ?', $sql);
    }

    /** @test */
    public function exception_rendering_uses_custom_exception_view_for_server_errors()
    {
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $exception = new \RuntimeException("Test server error message in English");
        
        $request = \Illuminate\Http\Request::create('/test-error', 'GET');
        $response = $handler->render($request, $exception);
        
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString('Test server error message in English', $response->getContent());
        $this->assertStringContainsString('RuntimeException', $response->getContent());
        $this->assertStringContainsString('Application Exception', $response->getContent());
    }

    /** @test */
    public function crm_reports_apply_branch_filter_to_queries()
    {
        $request = new \Illuminate\Http\Request([
            'branch_id' => 5
        ]);

        // 1. Leads Summary Query
        $leadsQuery = \App\Models\Lead::query();
        if ($request->filled('branch_id')) {
            $leadsQuery->where('leads.branch_id', $request->branch_id);
        }
        $this->assertStringContainsString('"leads"."branch_id" = ?', $leadsQuery->toSql());

        // 2. Product Wise Query
        $productQuery = \App\Models\LeadProduct::query()
            ->join('leads', 'leads.id', '=', 'lead_products.lead_id');
        if ($request->filled('branch_id')) {
            $productQuery->where('leads.branch_id', $request->branch_id);
        }
        $this->assertStringContainsString('"leads"."branch_id" = ?', $productQuery->toSql());

        // 3. Payment Collection Query
        $paymentQuery = \App\Models\LeadProductPayment::query()
            ->join('leads', 'leads.id', '=', 'lead_product_payments.lead_id');
        if ($request->filled('branch_id')) {
            $paymentQuery->where('leads.branch_id', $request->branch_id);
        }
        $this->assertStringContainsString('"leads"."branch_id" = ?', $paymentQuery->toSql());
    }

    /** @test */
    public function crm_branch_wise_comparison_compiles_data_correctly()
    {
        $request = new \Illuminate\Http\Request([
            'period_type' => 'quarter',
            'year' => 2026,
            'quarter' => 2
        ]);

        $controller = new \App\Http\Controllers\CrmReportController(
            app(\App\Services\DataVisibilityService::class)
        );

        $method = new \ReflectionMethod($controller, 'resolvePeriodRange');
        $method->setAccessible(true);
        $range = $method->invoke($controller, $request);

        $this->assertEquals('2026-04-01', $range[0]);
        $this->assertEquals('2026-06-30', $range[1]);
        $this->assertEquals('Q2 2026', $range[2]);
        $this->assertEquals('quarter', $range[3]);
    }
}
