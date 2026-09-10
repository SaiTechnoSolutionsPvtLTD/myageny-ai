<?php

use App\Http\Controllers\AccessMappingController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetEntryController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CrmReportController;
use App\Http\Controllers\CustomerCampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignSettingController;
use App\Http\Controllers\DynamicFormController;
use App\Http\Controllers\EmployeeExitController;
use App\Http\Controllers\EmployeeOnboardingController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\FacebookIntegrationController;
use App\Http\Controllers\FacilityManagementController;
use App\Http\Controllers\FacilityTitleController;
use App\Http\Controllers\HolidayCalendarController;
use App\Http\Controllers\HouseKeepingAttendanceController;
use App\Http\Controllers\HouseKeepingCategoryController;
use App\Http\Controllers\HouseKeepingEmployeeController;
use App\Http\Controllers\HouseKeepingManagementController;
use App\Http\Controllers\HouseKeepingWorkController;
use App\Http\Controllers\HrmsAnnouncementController;
use App\Http\Controllers\InternJoiningFormController;
use App\Http\Controllers\LeadCallUpdateController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadProductPriceRequestController;
use App\Http\Controllers\LeadShowController;
use App\Http\Controllers\LeadSourceController;
use App\Http\Controllers\LeadStatusController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\MastersController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OutcomeCategoryController;
use App\Http\Controllers\OutcomeSubCategoryController;
use App\Http\Controllers\OdRequestController;
use App\Http\Controllers\OvpModuleController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollSettingController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionApprovalController;
use App\Http\Controllers\ProductionTaskController;
use App\Http\Controllers\ProductOvpFormController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuotationSettingsController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\RecruitmentReminderController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SalesTargetSettingController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorManagementController;
use App\Models\EmployeeOnboarding;
use App\Models\InternJoiningForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

Route::get('/quotations/{id}/pdf',  [QuotationController::class, 'downloadPdf'])->name('quotation.pdf');
Route::get('/quotations/{quotation}/response/{response}', [QuotationController::class, 'customerResponse'])
    ->middleware('signed')
    ->name('quotations.customer-response');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/visitor-entry', [VisitorManagementController::class, 'publicCreate'])->name('visitor-entry.create');
Route::post('/visitor-entry', [VisitorManagementController::class, 'publicStore'])->name('visitor-entry.store');
Route::get('/facility-entry', [FacilityManagementController::class, 'publicCreate'])->name('facility-entry.create');
Route::post('/facility-entry', [FacilityManagementController::class, 'publicStore'])->name('facility-entry.store');

// Forgot password placeholder
Route::get('/forgot-password', function () {
    return redirect()->route('login')->with('error', 'Password reset is coming soon. Please contact your administrator.');
})->name('password.request');


Route::get('/lead/form-customization', fn() => redirect()->route('settings.form-customization.index'))->middleware('auth');
Route::middleware(['auth'])->group(function () {

  // Dashboard
    Route::get('/', fn() => redirect()->route('dashboard'));
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

     // Super Admin Dashboard (API-integrated blade)
    Route::get('/dashboard/admin', [DashboardController::class, 'index'])
        ->name('dashboard.admin');

    Route::get('/product-dashboard/admin', [SuperAdminDashboardController::class, 'adminProductindex'])
        ->middleware('can:dashboard.view');

    // Customer Success Dashboard
    Route::get('/dashboard/customer-success', [\App\Http\Controllers\CustomerSuccessDashboardController::class, 'index'])
        ->name('dashboard.customer-success');
    Route::get('/api/customer-success/data', [\App\Http\Controllers\CustomerSuccessDashboardController::class, 'data'])
        ->name('api.customer-success.data');
    Route::get('/api/customer-success/filters', [\App\Http\Controllers\CustomerSuccessDashboardController::class, 'filters'])
        ->name('api.customer-success.filters');

    // CST Allocation Module
    Route::get('/cst-allocation', [\App\Http\Controllers\CstAllocationController::class, 'index'])
        ->name('cst-allocation.index');
    Route::post('/cst-allocation/{lead}/allocate-tl', [\App\Http\Controllers\CstAllocationController::class, 'allocateTl'])
        ->name('cst-allocation.allocate-tl');
    Route::post('/cst-allocation/{lead}/allocate-executive', [\App\Http\Controllers\CstAllocationController::class, 'allocateExecutive'])
        ->name('cst-allocation.allocate-executive');

    // Default redirect by role
    Route::get('/dashboard', function () {
        $user = auth()->user();

        abort_unless($user, 403);

        $user->resolvedRoles(withDepartment: true);

        return redirect()->route($user->dashboardRoute());
    })->name('dashboard');

    // Masters
    Route::get('/masters', [MastersController::class, 'index'])
        ->middleware('can:masters.view')
        ->name('masters.index');
    Route::get('/hrms/masters', [MastersController::class, 'hrmsIndex'])
        ->middleware('can:masters.view')
        ->name('hrms.masters.index');

    Route::prefix('masters')->name('masters.')->group(function () {
        Route::resource('lead-statuses', LeadStatusController::class)
            ->middleware('can:lead_status.view')
            ->except(['create', 'show']);

        Route::resource('lead-sources', LeadSourceController::class)
            ->middleware('can:lead_source.view')
            ->except(['create', 'show']);

        Route::resource('outcome-categories', OutcomeCategoryController::class)
            ->middleware('can:outcome_category.view')
            ->except(['create', 'show']);

        Route::resource('outcome-sub-categories', OutcomeSubCategoryController::class)
            ->middleware('can:outcome_sub_category.view')
            ->except(['create', 'show']);

        Route::resource('product-category', ProductCategoryController::class)
            ->middleware('can:product_category.view')
            ->except(['create', 'show']);

        Route::resource('product-attribute', ProductAttributeController::class)
            ->middleware('can:product_attributes.view')
            ->except(['create', 'show']);
    });

    Route::prefix('hrms/masters')->name('hrms.masters.')->group(function () {
        Route::resource('departments', DepartmentController::class)
            ->middleware('can:departments.menuview');

        Route::resource('leave-types', LeaveTypeController::class)
            ->middleware('can:settings.manage');

        Route::resource('facility-titles', FacilityTitleController::class)
            ->middleware('can:settings.manage')
            ->except(['show']);

        Route::resource('asset-categories', AssetCategoryController::class)
            ->middleware('can:settings.manage')
            ->except(['show']);

        Route::resource('expense-categories', ExpenseCategoryController::class)
            ->except(['create', 'show', 'edit']);
        Route::patch('expense-categories/{expenseCategory}/toggle', [ExpenseCategoryController::class, 'toggleStatus'])
            ->name('expense-categories.toggle-status');

        Route::get('payroll', [PayrollSettingController::class, 'index'])
            ->middleware('can:payroll_settings.menuview')
            ->name('payroll.index');
        Route::post('payroll', [PayrollSettingController::class, 'update'])
            ->middleware('can:payroll_settings.manage')
            ->name('payroll.update');
    });

    Route::get('/authentications', [UserController::class, 'authIndex'])
        ->middleware('can:authentication.menuview')
        ->name('auth.index');

    Route::get('/ovp-module', [OvpModuleController::class, 'index'])
        ->middleware('can:ovp_module.menuview')
        ->name('ovp-module.index');
    Route::get('/reports/crm', [CrmReportController::class, 'index'])
        ->name('reports.crm.index');
    Route::get('/reports/crm/leads-summary', [CrmReportController::class, 'leadsSummary'])
        ->name('reports.crm.leads-summary');
    Route::get('/reports/crm/leads-summary/export', [CrmReportController::class, 'exportLeadsSummary'])
        ->name('reports.crm.leads-summary.export');
    Route::get('/reports/crm/product-wise', [CrmReportController::class, 'productWise'])
        ->name('reports.crm.product-wise');
    Route::get('/reports/crm/product-wise/export', [CrmReportController::class, 'exportProductWise'])
        ->name('reports.crm.product-wise.export');
    Route::get('/reports/crm/revenue-comparison', [CrmReportController::class, 'revenueComparison'])
        ->name('reports.crm.revenue-comparison');
    Route::get('/reports/crm/revenue-comparison/export', [CrmReportController::class, 'exportRevenueComparison'])
        ->name('reports.crm.revenue-comparison.export');
    Route::get('/reports/crm/payment-collection', [CrmReportController::class, 'paymentCollection'])
        ->name('reports.crm.payment-collection');
    Route::get('/reports/crm/payment-collection/export', [CrmReportController::class, 'exportPaymentCollection'])
        ->name('reports.crm.payment-collection.export');
    Route::get('/reports/crm/branch-comparison', [CrmReportController::class, 'branchComparison'])
        ->name('reports.crm.branch-comparison');
    Route::get('/reports/crm/branch-comparison/export', [CrmReportController::class, 'exportBranchComparison'])
        ->name('reports.crm.branch-comparison.export');
    Route::get('/reports/crm/smm', [CrmReportController::class, 'smmReport'])
        ->name('reports.crm.smm');
    Route::get('/reports/crm/smm/export', [CrmReportController::class, 'exportSmmReport'])
        ->name('reports.crm.smm.export');
    Route::get('/reports/crm/sales-comparison', [CrmReportController::class, 'salesComparison'])
        ->name('reports.crm.sales-comparison');
    Route::get('/reports/crm/sales-comparison/export', [CrmReportController::class, 'exportSalesComparison'])
        ->name('reports.crm.sales-comparison.export');
    Route::post('/ovp-module/{productionInitiation}/allocate', [OvpModuleController::class, 'allocate'])
        ->middleware('can:ovp_module.menuview')
        ->name('ovp-module.allocate');
    Route::post('/ovp-module/{productionInitiation}/review', [OvpModuleController::class, 'review'])
        ->middleware('can:ovp_module.menuview')
        ->name('ovp-module.review');

    Route::get('/production-approvals', [ProductionApprovalController::class, 'index'])
        ->middleware('can:production_approval_module.menuview')
        ->name('production-approvals.index');
    Route::post('/production-approvals/{productionInitiation}/review', [ProductionApprovalController::class, 'review'])
        ->middleware('can:production_approval_module.menuview')
        ->name('production-approvals.review');
    Route::group(['middleware' => function ($request, $next) {
        abort_unless(auth()->user()?->canAccessProjectsModule(), 403);

        return $next($request);
    }], function () {
        Route::get('/projects/dashboard', [ProjectController::class, 'dashboard'])
            ->name('projects.dashboard');
        Route::get('/projects/my-accounts', [ProjectController::class, 'myAccounts'])
            ->name('projects.my-accounts');
        Route::get('/projects/my-accounts/{lead}', [ProjectController::class, 'showMyAccount'])
            ->name('projects.my-accounts.show');
        Route::get('/projects/campaigns', [CustomerCampaignController::class, 'index'])
            ->name('projects.campaigns.index');
        Route::get('/projects/campaigns/{lead}', [CustomerCampaignController::class, 'show'])
            ->name('projects.campaigns.show');
        Route::post('/projects/campaigns/{lead}', [CustomerCampaignController::class, 'store'])
            ->name('projects.campaigns.store');
        Route::put('/projects/campaigns/{campaign}', [CustomerCampaignController::class, 'update'])
            ->name('projects.campaigns.update');
        Route::delete('/projects/campaigns/{campaign}', [CustomerCampaignController::class, 'destroy'])
            ->name('projects.campaigns.destroy');
        Route::post('/projects/campaigns/{campaign}/toggle-status', [CustomerCampaignController::class, 'toggleStatus'])
            ->name('projects.campaigns.toggle-status');
        Route::post('/projects/campaigns/{campaign}/pause', [CustomerCampaignController::class, 'pause'])
            ->name('projects.campaigns.pause');
        Route::post('/projects/campaigns/{campaign}/resume', [CustomerCampaignController::class, 'resume'])
            ->name('projects.campaigns.resume');
        Route::post('/projects/campaigns/{campaign}/extend', [CustomerCampaignController::class, 'extend'])
            ->name('projects.campaigns.extend');
        Route::post('/projects/campaigns/{campaign}/stop', [CustomerCampaignController::class, 'stop'])
            ->name('projects.campaigns.stop');
        Route::post('/projects/dashboard/update-planned-task', [ProjectController::class, 'updatePlannedTask'])
            ->name('projects.dashboard.update-planned-task');
        Route::post('/projects/dashboard/allocate-task', [ProjectController::class, 'allocateDailyTask'])
            ->name('projects.dashboard.allocate-task');

        Route::get('/projects/tasks', [ProductionTaskController::class, 'index'])
            ->middleware('can:tasks.view')
            ->name('projects.tasks.index');
        Route::get('/projects/tasks/create', [ProductionTaskController::class, 'create'])
            ->middleware('can:tasks.create')
            ->name('projects.tasks.create');
        Route::post('/projects/tasks', [ProductionTaskController::class, 'store'])
            ->middleware('can:tasks.create')
            ->name('projects.tasks.store');
        Route::patch('/projects/tasks/{task}/status', [ProductionTaskController::class, 'updateStatus'])
            ->middleware('can:tasks.edit')
            ->name('projects.tasks.update-status');
        Route::delete('/projects/tasks/{task}', [ProductionTaskController::class, 'destroy'])
            ->middleware('can:tasks.delete')
            ->name('projects.tasks.destroy');

        Route::get('/projects/timesheets', [ProjectController::class, 'timesheets'])
            ->middleware('can:timesheets.view')
            ->name('projects.timesheets');
        Route::post('/projects/timesheets', [ProjectController::class, 'storeTimesheet'])
            ->middleware('can:timesheets.create')
            ->name('projects.timesheets.store');
        Route::patch('/projects/timesheets/{timesheet}/status', [ProjectController::class, 'updateTimesheetStatus'])
            ->middleware('can:timesheets.edit')
            ->name('projects.timesheets.update-status');
        Route::get('/projects-details', [ProjectController::class, 'index'])
            ->name('projects.index');
        Route::get('/projects-details/{productionInitiation}', [ProjectController::class, 'show'])
            ->name('projects.show');
        Route::post('/projects-details/{productionInitiation}/allocate', [ProjectController::class, 'allocate'])
            ->name('projects.allocate');
        Route::post('/projects-details/{productionInitiation}/employee-allocate', [ProjectController::class, 'allocateEmployees'])
            ->name('projects.employee-allocate');
        Route::post('/projects-details/{productionInitiation}/schedule', [ProjectController::class, 'updateSchedule'])
            ->name('projects.schedule.update');
        Route::post('/projects-details/{productionInitiation}/updates', [ProjectController::class, 'storeUpdate'])
            ->name('projects.updates.store');
        Route::post('/projects-details/{productionInitiation}/move-to-testing', [ProjectController::class, 'moveToTesting'])
            ->name('projects.move-to-testing');
        Route::post('/projects-details/{productionInitiation}/bugs', [ProjectController::class, 'storeBug'])
            ->name('projects.bugs.store');
        Route::get('/testing-details/{productionInitiation}', [ProjectController::class, 'testingDetails'])
            ->name('projects.testing-details');
        Route::post('/testing-details/{productionInitiation}/status', [ProjectController::class, 'updateTestingStatus'])
            ->name('projects.testing-details.update-status');
        Route::patch('/testing-details/bugs/{bug}/status', [ProjectController::class, 'updateBugStatus'])
            ->name('projects.bugs.update-status');
        Route::post('/projects-details/updates/quick', [ProjectController::class, 'storeQuickUpdate'])
            ->name('projects.updates.quick-store');
        Route::patch('/projects-details/{productionInitiation}/content-calendar-sheet', [ProjectController::class, 'updateContentCalendarSheet'])
            ->name('projects.content-calendar-sheet.update');
        Route::patch('/projects-details/{productionInitiation}/content-calendar-approve', [ProjectController::class, 'approveContentCalendar'])
            ->name('projects.content-calendar.approve');
        Route::get('/projects-details/{productionInitiation}/content-calendar-data', [ProjectController::class, 'fetchContentCalendarData'])
            ->name('projects.content-calendar-data');
    });

    Route::prefix('authentications')->name('auth.')->group(function () {
        Route::get('/roles', [RolePermissionController::class, 'rolesIndex'])->middleware('can:roles.view')->name('roles.index');
        Route::get('/roles/create', [RolePermissionController::class, 'rolesCreate'])->middleware('can:roles.manage')->name('roles.create');
        Route::post('/roles', [RolePermissionController::class, 'rolesStore'])->middleware('can:roles.manage')->name('roles.store');
        Route::get('/roles/{role}/edit', [RolePermissionController::class, 'rolesEdit'])->middleware('can:roles.manage')->name('roles.edit');
        Route::put('/roles/{role}', [RolePermissionController::class, 'rolesUpdate'])->middleware('can:roles.manage')->name('roles.update');
        Route::get('/roles/{role}/permissions', [RolePermissionController::class, 'rolesPermissionsEdit'])->middleware('can:roles.manage')->name('roles.permissions.edit');
        Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'rolesPermissionsUpdate'])->middleware('can:roles.manage')->name('roles.permissions.update');
        Route::delete('/roles/{role}', [RolePermissionController::class, 'rolesDestroy'])->middleware('can:roles.manage')->name('roles.destroy');

        Route::get('/permissions', [RolePermissionController::class, 'permissionsIndex'])->middleware('can:permissions.view')->name('permissions.index');
        Route::get('/permissions/create', [RolePermissionController::class, 'permissionsCreate'])->middleware('can:permissions.manage')->name('permissions.create');
        Route::post('/permissions', [RolePermissionController::class, 'permissionsStore'])->middleware('can:permissions.manage')->name('permissions.store');
        Route::get('/permissions/{permission}/edit', [RolePermissionController::class, 'permissionsEdit'])->middleware('can:permissions.manage')->name('permissions.edit');
        Route::put('/permissions/{permission}', [RolePermissionController::class, 'permissionsUpdate'])->middleware('can:permissions.manage')->name('permissions.update');
        Route::delete('/permissions/{permission}', [RolePermissionController::class, 'permissionsDestroy'])->middleware('can:permissions.manage')->name('permissions.destroy');

        Route::get('/role-mappings', [AccessMappingController::class, 'roleIndex'])->middleware('can:roles.view')->name('role-mappings.index');
        Route::put('/role-mappings', [AccessMappingController::class, 'roleUpdate'])->middleware('can:roles.manage')->name('role-mappings.update');
        Route::get('/user-mappings', [AccessMappingController::class, 'userIndex'])->middleware('can:users.view')->name('user-mappings.index');
        Route::get('/production-mappings', [AccessMappingController::class, 'productionIndex'])->middleware('can:users.view')->name('production-mappings.index');
        Route::get('/production-mappings/{department}', [AccessMappingController::class, 'productionShow'])->middleware('can:users.view')->name('production-mappings.show');
        Route::put('/production-mappings/{department}', [AccessMappingController::class, 'productionUpdate'])->middleware('can:users.manage')->name('production-mappings.update');
        Route::post('/user-mappings', [AccessMappingController::class, 'userUpdate'])->middleware('can:users.manage')->name('user-mappings.update');
        Route::delete('/user-mappings/{mapping}', [AccessMappingController::class, 'userDestroy'])->middleware('can:users.manage')->name('user-mappings.destroy');

        Route::post('/users/{user}/assign-role', [RolePermissionController::class, 'assignUserRole'])->middleware('can:users.manage')->name('users.assign-role');
    });

    Route::prefix('users')->name('users.')->middleware(['auth'])->group(function () {

    // List, Create, Store, Show, Edit, Update, Delete
    Route::get('/',              [UserController::class, 'index'])->middleware('can:users.view')->name('index');
    Route::get('/create',        [UserController::class, 'create'])->middleware('can:users.manage')->name('create');
    Route::post('/',             [UserController::class, 'store'])->middleware('can:users.manage')->name('store');
    Route::get('/{user}',        [UserController::class, 'show'])->middleware('can:users.view')->name('show');
    Route::get('/{user}/edit',   [UserController::class, 'edit'])->middleware('can:users.manage')->name('edit');
    Route::put('/{user}',        [UserController::class, 'update'])->middleware('can:users.manage')->name('update');
    Route::delete('/{user}',     [UserController::class, 'destroy'])->middleware('can:users.manage')->name('destroy');

    // Extra actions
    Route::patch('/{user}/toggle-status',  [UserController::class, 'toggleStatus'])->middleware('can:users.manage')->name('toggle-status');
    Route::post('/{user}/reset-password',  [UserController::class, 'resetPassword'])->middleware('can:users.manage')->name('reset-password');
});

    Route::get('/companies', [CompanyController::class, 'index'])->middleware('can:companies.view')->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->middleware('can:companies.manage')->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->middleware('can:companies.manage')->name('companies.store');
    Route::get('/companies/{company}', [CompanyController::class, 'show'])->middleware('can:companies.view')->name('companies.show');
    Route::get('/companies/{company}/edit', [CompanyController::class, 'edit'])->middleware('can:companies.manage')->name('companies.edit');
    Route::put('/companies/{company}', [CompanyController::class, 'update'])->middleware('can:companies.manage')->name('companies.update');
    Route::delete('/companies/{company}', [CompanyController::class, 'destroy'])->middleware('can:companies.manage')->name('companies.destroy');
    Route::get('/hrms/dashboard', [App\Http\Controllers\HRMS\DashboardController::class, 'index'])->name('hrms.dashboard');
    Route::get('/hrms/calendar', [\App\Http\Controllers\HRMS\HrmsCalendarController::class, 'index'])->name('hrms.calendar.index');
    Route::post('/hrms/tasks', [App\Http\Controllers\HRMS\DashboardController::class, 'storeTask'])->name('hrms.tasks.store');
    Route::patch('/hrms/tasks/{task}/complete', [App\Http\Controllers\HRMS\DashboardController::class, 'completeTask'])->name('hrms.tasks.complete');
    Route::delete('/hrms/tasks/{task}', [App\Http\Controllers\HRMS\DashboardController::class, 'destroyTask'])->name('hrms.tasks.destroy');

    // Expense Requests
    Route::prefix('hrms/expense-requests')->name('hrms.expense-requests.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ExpenseRequestController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\ExpenseRequestController::class, 'store'])->name('store');
        Route::get('/{expenseRequest}/email-approve', [\App\Http\Controllers\ExpenseRequestController::class, 'emailApprove'])->name('email-approve');
        Route::get('/{expenseRequest}/email-reject', [\App\Http\Controllers\ExpenseRequestController::class, 'emailRejectPage'])->name('email-reject');
        Route::post('/{expenseRequest}/approve', [\App\Http\Controllers\ExpenseRequestController::class, 'approve'])->name('approve');
        Route::post('/{expenseRequest}/reject', [\App\Http\Controllers\ExpenseRequestController::class, 'reject'])->name('reject');
    });
    
    // Petty Cash Report & Transactions
    Route::get('/hrms/petty-cash', [\App\Http\Controllers\HRMS\PettyCashController::class, 'report'])->name('hrms.petty-cash.index');
    Route::post('/hrms/petty-cash', [\App\Http\Controllers\HRMS\PettyCashController::class, 'store'])->name('hrms.petty-cash.store');
    Route::put('/hrms/petty-cash/{entry}', [\App\Http\Controllers\HRMS\PettyCashController::class, 'update'])->name('hrms.petty-cash.update');
    Route::get('/hrms/petty-cash/export-excel', [\App\Http\Controllers\HRMS\PettyCashController::class, 'exportExcel'])->name('hrms.petty-cash.export-excel');
    Route::get('/hrms/petty-cash/export-pdf', [\App\Http\Controllers\HRMS\PettyCashController::class, 'exportPdf'])->name('hrms.petty-cash.export-pdf');

    // Rani Petty Cash Entries
    Route::post('/hrms/petty-cash/rani', [\App\Http\Controllers\HRMS\PettyCashController::class, 'storeRani'])->name('hrms.petty-cash.rani.store');
    Route::put('/hrms/petty-cash/rani/{raniEntry}', [\App\Http\Controllers\HRMS\PettyCashController::class, 'updateRani'])->name('hrms.petty-cash.rani.update');
    Route::delete('/hrms/petty-cash/rani/{raniEntry}', [\App\Http\Controllers\HRMS\PettyCashController::class, 'destroyRani'])->name('hrms.petty-cash.rani.destroy');
    Route::get('/hrms-announcements', [HrmsAnnouncementController::class, 'index'])->name('hrms-announcements.index');
    Route::get('/hrms-announcements/create', [HrmsAnnouncementController::class, 'create'])->name('hrms-announcements.create');
    Route::post('/hrms-announcements', [HrmsAnnouncementController::class, 'store'])->name('hrms-announcements.store');
    Route::get('/hrms-announcements/{announcement}/edit', [HrmsAnnouncementController::class, 'edit'])->name('hrms-announcements.edit');
    Route::put('/hrms-announcements/{announcement}', [HrmsAnnouncementController::class, 'update'])->name('hrms-announcements.update');
    Route::delete('/hrms-announcements/{announcement}', [HrmsAnnouncementController::class, 'destroy'])->name('hrms-announcements.destroy');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/checkout', [AttendanceController::class, 'createCheckout'])->name('attendance.checkout.create');
    Route::post('/attendance/checkout', [AttendanceController::class, 'storeCheckout'])->name('attendance.checkout.store');
    Route::get('/attendance/lookup', [AttendanceController::class, 'lookupAttendance'])->name('attendance.lookup');
    Route::get('/attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');

    // Outside Office Attendance Requests
    Route::prefix('hrms/outside-office-requests')->name('hrms.outside-office-requests.')->group(function () {
        Route::get('/', [\App\Http\Controllers\HRMS\OutsideOfficeAttendanceRequestController::class, 'index'])->name('index');
        Route::post('/{outsideOfficeRequest}/approve', [\App\Http\Controllers\HRMS\OutsideOfficeAttendanceRequestController::class, 'approve'])->name('approve');
        Route::post('/{outsideOfficeRequest}/reject', [\App\Http\Controllers\HRMS\OutsideOfficeAttendanceRequestController::class, 'reject'])->name('reject');
    });

    // Timesheet LOP Management
    Route::get('/hrms/timesheet-lop', [\App\Http\Controllers\HRMS\TimesheetLopController::class, 'index'])
        ->middleware('can:timesheet_lop.menuview')
        ->name('hrms.timesheet-lop.index');
    Route::get('/hrms/timesheet-lop/details/{employee}', [\App\Http\Controllers\HRMS\TimesheetLopController::class, 'details'])
        ->middleware('can:timesheet_lop.menuview')
        ->name('hrms.timesheet-lop.details');
    Route::get('/hrms/timesheet-lop/export', [\App\Http\Controllers\HRMS\TimesheetLopController::class, 'export'])
        ->middleware('can:timesheet_lop.menuview')
        ->name('hrms.timesheet-lop.export');

    Route::get('/house-keeping-management', [HouseKeepingManagementController::class, 'index'])
        ->middleware('can:house_keeping.menuview')
        ->name('house-keeping.index');
    Route::post('/house-keeping-management/completions', [HouseKeepingManagementController::class, 'updateCompletion'])
        ->middleware('can:house_keeping.menuview')
        ->name('house-keeping.completions.update');
    Route::resource('house-keeping-employees', HouseKeepingEmployeeController::class)
        ->middleware('can:house_keeping.menuview')
        ->names('house-keeping.employees');
    Route::get('/house-keeping-attendances', [HouseKeepingAttendanceController::class, 'index'])
        ->middleware('can:house_keeping.menuview')
        ->name('house-keeping.attendances.index');
    Route::post('/house-keeping-attendances', [HouseKeepingAttendanceController::class, 'storeOrUpdate'])
        ->middleware('can:house_keeping.menuview')
        ->name('house-keeping.attendances.store');
    Route::delete('/house-keeping-attendances/{attendance}', [HouseKeepingAttendanceController::class, 'destroy'])
        ->middleware('can:house_keeping.menuview')
        ->name('house-keeping.attendances.destroy');
    Route::resource('leave-requests', LeaveRequestController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/leave-requests/{leaveRequest}/approvals/{approval}/email-approve', [LeaveRequestController::class, 'emailApprove'])
        ->name('leave-requests.email-approve');
    Route::get('/leave-requests/{leaveRequest}/approvals/{approval}/email-reject', [LeaveRequestController::class, 'emailRejectPage'])
        ->name('leave-requests.email-reject');
    Route::patch('/leave-requests/{leaveRequest}/approvals/{approval}/approve', [LeaveRequestController::class, 'approve'])
        ->name('leave-requests.approve');
    Route::patch('/leave-requests/{leaveRequest}/approvals/{approval}/reject', [LeaveRequestController::class, 'reject'])
        ->name('leave-requests.reject');
    Route::resource('permission-requests', PermissionRequestController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/permission-requests/{permissionRequest}/approvals/{approval}/email-approve', [PermissionRequestController::class, 'emailApprove'])
        ->name('permission-requests.email-approve');
    Route::get('/permission-requests/{permissionRequest}/approvals/{approval}/email-reject', [PermissionRequestController::class, 'emailRejectPage'])
        ->name('permission-requests.email-reject');
    Route::patch('/permission-requests/{permissionRequest}/approvals/{approval}/approve', [PermissionRequestController::class, 'approve'])
        ->name('permission-requests.approve');
    Route::patch('/permission-requests/{permissionRequest}/approvals/{approval}/reject', [PermissionRequestController::class, 'reject'])
        ->name('permission-requests.reject');
    Route::resource('od-requests', OdRequestController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('/od-requests/{odRequest}/approvals/{approval}/email-approve', [OdRequestController::class, 'emailApprove'])
        ->name('od-requests.email-approve');
    Route::get('/od-requests/{odRequest}/approvals/{approval}/email-reject', [OdRequestController::class, 'emailRejectPage'])
        ->name('od-requests.email-reject');
    Route::patch('/od-requests/{odRequest}/approvals/{approval}/approve', [OdRequestController::class, 'approve'])
        ->name('od-requests.approve');
    Route::patch('/od-requests/{odRequest}/approvals/{approval}/reject', [OdRequestController::class, 'reject'])
        ->name('od-requests.reject');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::get('/recruitment/call-updates', [RecruitmentController::class, 'callUpdates'])
        ->name('recruitment.calls.index');
    Route::get('/recruitment/reminders', [RecruitmentReminderController::class, 'index'])
        ->name('recruitment.reminders.index');
    Route::post('/recruitment/{recruitment}/reminders', [RecruitmentReminderController::class, 'store'])
        ->name('recruitment.reminders.store');
    Route::patch('/recruitment/reminders/{reminder}/complete', [RecruitmentReminderController::class, 'complete'])
        ->name('recruitment.reminders.complete');
    Route::patch('/recruitment/reminders/{reminder}/incomplete', [RecruitmentReminderController::class, 'incomplete'])
        ->name('recruitment.reminders.incomplete');
    Route::delete('/recruitment/reminders/{reminder}', [RecruitmentReminderController::class, 'destroy'])
        ->name('recruitment.reminders.destroy');
    Route::resource('recruitment', RecruitmentController::class);
    Route::post('/recruitment/{recruitment}/call-updates', [RecruitmentController::class, 'storeCallUpdate'])
        ->name('recruitment.call-updates.store');
    Route::post('/recruitment/{recruitment}/interviews', [RecruitmentController::class, 'storeInterview'])
        ->name('recruitment.interviews.store');
    Route::put('/recruitment/{recruitment}/interviews/{interview}', [RecruitmentController::class, 'updateInterview'])
        ->name('recruitment.interviews.update');
    Route::patch('/recruitment/{recruitment}/status', [RecruitmentController::class, 'updateStatus'])
        ->name('recruitment.status.update');
    Route::resource('assets', AssetEntryController::class);
    Route::get('employee-onboarding/generate-id', [EmployeeOnboardingController::class, 'getGeneratedId'])->name('employee-onboarding.generate-id');
    Route::post('employee-onboarding/{employee_onboarding}/update-photo', [EmployeeOnboardingController::class, 'updatePhoto'])->name('employee-onboarding.update-photo');
    Route::post('employee-onboarding/{employee_onboarding}/update-document', [EmployeeOnboardingController::class, 'updateDocument'])->name('employee-onboarding.update-document');
    Route::patch('employee-onboarding/{employee_onboarding}/update-status', [EmployeeOnboardingController::class, 'updateStatus'])->name('employee-onboarding.update-status');
    Route::resource('employee-onboarding', EmployeeOnboardingController::class);
    Route::post('/employee-exit-requests', [EmployeeExitController::class, 'store'])->name('employee-exit-requests.store');
    Route::post('/employee-exit-requests/{employeeExitRequest}/revoke', [EmployeeExitController::class, 'requestRevoke'])->name('employee-exit-requests.revoke');
    Route::patch('/employee-exit-requests/{employeeExitRequest}/approve', [EmployeeExitController::class, 'approveExit'])->name('employee-exit-requests.approve');
    Route::patch('/employee-exit-requests/{employeeExitRequest}/reject', [EmployeeExitController::class, 'rejectExit'])->name('employee-exit-requests.reject');
    Route::patch('/employee-exit-requests/{employeeExitRequest}/approve-revoke', [EmployeeExitController::class, 'approveRevoke'])->name('employee-exit-requests.approve-revoke');
    Route::patch('/employee-exit-requests/{employeeExitRequest}/reject-revoke', [EmployeeExitController::class, 'rejectRevoke'])->name('employee-exit-requests.reject-revoke');
    Route::get('/dynamic-forms/{dynamicForm}/responses', [DynamicFormController::class, 'responses'])->name('dynamic-forms.responses');
    Route::get('/dynamic-forms/{dynamicForm}/export', [DynamicFormController::class, 'export'])->name('dynamic-forms.export');
    Route::resource('dynamic-forms', DynamicFormController::class);
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
    Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
    Route::get('/payroll/{payroll}/payslip/{item}', [PayrollController::class, 'payslip'])->name('payroll.payslip');
    Route::get('/interns/{intern}/convert-to-employee', [InternJoiningFormController::class, 'showConvertToEmployeeForm'])->name('interns.convert-to-employee');
    Route::post('/interns/{intern}/convert-to-employee', [InternJoiningFormController::class, 'convertToEmployee'])->name('interns.convert-to-employee.store');
    Route::patch('/interns/{intern}/update-status', [InternJoiningFormController::class, 'updateStatus'])->name('interns.update-status');
    Route::post('/interns/{intern}/update-status', [InternJoiningFormController::class, 'updateStatus'])->name('interns.update-status.post');
    Route::resource('interns', InternJoiningFormController::class);
    Route::get('/visitor-management/qr-code', [VisitorManagementController::class, 'qrCode'])->name('visitor-management.qr-code');
    Route::get('/visitor-management', [VisitorManagementController::class, 'index'])->name('visitor-management.index');
    Route::get('/visitor-management/create', [VisitorManagementController::class, 'create'])->name('visitor-management.create');
    Route::post('/visitor-management', [VisitorManagementController::class, 'store'])->name('visitor-management.store');
    Route::get('/visitor-management/{visitorEntry}', [VisitorManagementController::class, 'show'])->name('visitor-management.show');
    Route::get('/visitor-management/{visitorEntry}/edit', [VisitorManagementController::class, 'edit'])->name('visitor-management.edit');
    Route::put('/visitor-management/{visitorEntry}', [VisitorManagementController::class, 'update'])->name('visitor-management.update');
    Route::delete('/visitor-management/{visitorEntry}', [VisitorManagementController::class, 'destroy'])->name('visitor-management.destroy');
    Route::get('/facility-management/qr-code', [FacilityManagementController::class, 'qrCode'])->name('facility-management.qr-code');
    Route::get('/lead-price-requests', [LeadProductPriceRequestController::class, 'index'])
        ->middleware('can:price_requests.view')
        ->name('lead-price-requests.index');
    Route::patch('/lead-price-requests/{priceRequest}/approve', [LeadProductPriceRequestController::class, 'approve'])
        ->middleware('can:price_requests.approve')
        ->name('lead-price-requests.approve');
    Route::patch('/lead-price-requests/{priceRequest}/reject', [LeadProductPriceRequestController::class, 'reject'])
        ->middleware('can:price_requests.reject')
        ->name('lead-price-requests.reject');

    // ── CRM Tasks & Reminders ────────────────────────────────────
    Route::get('/crm/tasks', [\App\Http\Controllers\CrmTaskController::class, 'index'])->middleware('can:leads.view')->name('tasks.index');
    Route::patch('/crm/tasks/{reminder}/complete', [\App\Http\Controllers\CrmTaskController::class, 'complete'])->middleware('can:leads.edit')->name('tasks.complete');
    Route::patch('/crm/tasks/{reminder}/incomplete', [\App\Http\Controllers\CrmTaskController::class, 'incomplete'])->middleware('can:leads.edit')->name('tasks.incomplete');

    // ── Pre Sales Management ─────────────────────────────────────
    Route::get('/pre-sales', [\App\Http\Controllers\PreSalesController::class, 'index'])->name('pre-sales.index');
    Route::post('/pre-sales/allocate', [\App\Http\Controllers\PreSalesController::class, 'allocate'])->name('pre-sales.allocate');

    // ── Main Lead CRUD ─────────────────────────────────────────
   Route::prefix('leads')->name('leads.')->group(function () {

        // ── Core CRUD ─────────────────────────────────────────
        Route::get('/',            [LeadController::class, 'index'])->middleware('can:leads.view')->name('index');
        Route::get('/untouched',   [LeadController::class, 'untouchedIndex'])->middleware('can:leads.view')->name('untouched');
        Route::get('/products',    [LeadController::class, 'productsIndex'])->middleware('can:leads.view')->name('products.index');
        Route::get('/create',      [LeadController::class, 'create'])->middleware('can:leads.create')->name('create');
        Route::get('/call-updates',[LeadCallUpdateController::class, 'index'])->middleware('can:call_updates.view')->name('calls.index');
        Route::post('/',           [LeadController::class, 'store'])->middleware('can:leads.create')->name('store');
        Route::get('/{lead}',      [LeadController::class, 'show'])->middleware('can:leads.view')->name('show');
        Route::get('/{lead}/edit', [LeadController::class, 'edit'])->middleware('can:leads.edit')->name('edit');
        Route::put('/{lead}',      [LeadController::class, 'update'])->middleware('can:leads.edit')->name('update');
        Route::delete('/{lead}',   [LeadController::class, 'destroy'])->middleware('can:leads.delete')->name('destroy');
        Route::patch('/{lead}/status', [LeadController::class, 'updateStatus'])->middleware('can:leads.update')->name('update-status');
        Route::post('/{lead}/reassign', [LeadController::class, 'reassign'])->name('reassign');

        // ── Call Updates ──────────────────────────────────────
        Route::post('/{lead}/calls',             [LeadShowController::class, 'storeCall'])->middleware('can:call_updates.create')->name('calls.store');
        Route::put('/{lead}/calls/{call}',      [LeadShowController::class, 'updateCall'])->middleware('can:call_updates.create')->name('calls.update');
        Route::delete('/{lead}/calls/{call}',    [LeadShowController::class, 'destroyCall'])->middleware('can:call_updates.delete')->name('calls.destroy');

        // ── CST Updates ───────────────────────────────────────
        Route::post('/{lead}/cst-updates',       [LeadController::class, 'storeCstUpdate'])->name('cst-updates.store');

        // ── Reminders ─────────────────────────────────────────
        Route::post('/{lead}/reminders',                  [LeadShowController::class, 'storeReminder'])->middleware('can:leads.edit')->name('reminders.store');
        Route::patch('/{lead}/reminders/{reminder}/done', [LeadShowController::class, 'completeReminder'])->middleware('can:leads.edit')->name('reminders.complete');
        Route::delete('/{lead}/reminders/{reminder}',     [LeadShowController::class, 'destroyReminder'])->middleware('can:leads.edit')->name('reminders.destroy');

        // ── Products ───────────────────────────────────────────
        Route::post('/{lead}/products',
            [LeadShowController::class, 'storeProduct'])->middleware('can:leads.edit')->name('products.store');

        Route::put('/{lead}/products/{product}',
            [LeadShowController::class, 'updateProduct'])->middleware('can:leads.edit')->name('products.update');

        Route::patch('/{lead}/products/{product}/status',
            [LeadShowController::class, 'updateProductStatus'])->middleware('can:leads.edit')->name('products.update-status');

        Route::delete('/{lead}/products/{product}',
            [LeadShowController::class, 'destroyProduct'])->middleware('can:leads.edit')->name('products.destroy');

        // ── Product Payments (per-product payment history) ─────
        Route::post('/{lead}/products/{product}/payments',
            [LeadShowController::class, 'storeProductPayment'])->middleware('can:leads.edit')->name('products.payments.store');

        Route::delete('/{lead}/products/{product}/payments/{payment}',
            [LeadShowController::class, 'destroyProductPayment'])->middleware('can:leads.edit')->name('products.payments.destroy');

        // ── Quotations ─────────────────────────────────────────
        Route::post('/{lead}/quotations',
            [LeadShowController::class, 'storeQuotation'])->middleware('can:quotations.create')->name('quotations.store');
        Route::patch('/{lead}/quotations/{quotation}/status',
            [LeadShowController::class, 'updateQuotationStatus'])->middleware('can:quotations.approve')->name('quotations.update-status');
        Route::delete('/{lead}/quotations/{quotation}',
            [LeadShowController::class, 'destroyQuotation'])->middleware('can:quotations.delete')->name('quotations.destroy');
    });

    Route::prefix('products')->name('products.')->group(function () {

    // AJAX helpers (must be before {product} wildcard)
    Route::get('attributes-by-category/{category}', [ProductController::class, 'attributesByCategory'])
         ->middleware('can:products.view')
         ->name('attributes-by-category');

    Route::post('preview-price', [ProductController::class, 'previewPrice'])
         ->middleware('can:products.view')
         ->name('preview-price');

    // Standard resource routes
    Route::get('/',               [ProductController::class, 'index'])->middleware('can:products.view')->name('index');
    Route::get('/create',         [ProductController::class, 'create'])->middleware('can:products.create')->name('create');
    Route::post('/',              [ProductController::class, 'store'])->middleware('can:products.create')->name('store');
    Route::get('/{product}',      [ProductController::class, 'show'])->middleware('can:products.view')->name('show');
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->middleware('can:products.edit')->name('edit');
    Route::get('/{product}/ovp-form', [ProductOvpFormController::class, 'builder'])->middleware('can:products.edit')->name('ovp-form.builder');
    Route::put('/{product}',      [ProductController::class, 'update'])->middleware('can:products.edit')->name('update');
    Route::delete('/{product}',   [ProductController::class, 'destroy'])->middleware('can:products.delete')->name('destroy');


});



Route::resource('facility-management', FacilityManagementController::class)
         ->except(['show']);

Route::prefix('settings')->name('settings.')->group(function () {
    Route::post('holiday-calendars/import', [HolidayCalendarController::class, 'import'])
         ->name('holiday-calendars.import');
    Route::resource('holiday-calendars', HolidayCalendarController::class)
         ->except(['show']);
});

Route::prefix('settings')->name('settings.')->middleware('can:settings.view')->group(function () {

    // Main settings dashboard
    Route::get('/', fn() => view('pages.settings.index'))->name('index');

    // Activity Logs (User-wise & Lead-wise Audit)
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->name('activity-logs.show');

    // Backward compatibility redirects to Masters & HRMS Masters
    Route::get('lead-statuses', fn() => redirect()->route('masters.lead-statuses.index'))->name('lead-statuses.index');
    Route::get('product-category', fn() => redirect()->route('masters.product-category.index'))->name('product-category.index');

    Route::get('departments', fn() => redirect()->route('hrms.masters.departments.index'))->name('departments.index');
    Route::get('departments/create', fn() => redirect()->route('hrms.masters.departments.create'))->name('departments.create');
    Route::post('departments', [DepartmentController::class, 'store'])->middleware('can:departments.create')->name('departments.store');
    Route::get('departments/{department}/edit', fn(\App\Models\Department $department) => redirect()->route('hrms.masters.departments.edit', $department))->name('departments.edit');
    Route::put('departments/{department}', [DepartmentController::class, 'update'])->middleware('can:departments.edit')->name('departments.update');
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->middleware('can:departments.delete')->name('departments.destroy');

    Route::resource('branches', BranchController::class)
         ->middleware('can:branches.manage')
         ->except(['show']);

    Route::get('leave-types', fn() => redirect()->route('hrms.masters.leave-types.index'))->name('leave-types.index');
    Route::get('leave-types/create', fn() => redirect()->route('hrms.masters.leave-types.create'))->name('leave-types.create');
    Route::post('leave-types', [LeaveTypeController::class, 'store'])->middleware('can:settings.manage')->name('leave-types.store');
    Route::get('leave-types/{leaveType}/edit', fn(\App\Models\LeaveType $leaveType) => redirect()->route('hrms.masters.leave-types.edit', $leaveType))->name('leave-types.edit');
    Route::put('leave-types/{leaveType}', [LeaveTypeController::class, 'update'])->middleware('can:settings.manage')->name('leave-types.update');
    Route::delete('leave-types/{leaveType}', [LeaveTypeController::class, 'destroy'])->middleware('can:settings.manage')->name('leave-types.destroy');

    Route::get('asset-categories', fn() => redirect()->route('hrms.masters.asset-categories.index'))->name('asset-categories.index');
    Route::get('asset-categories/create', fn() => redirect()->route('hrms.masters.asset-categories.create'))->name('asset-categories.create');
    Route::post('asset-categories', [AssetCategoryController::class, 'store'])->middleware('can:settings.manage')->name('asset-categories.store');
    Route::get('asset-categories/{assetCategory}/edit', fn(\App\Models\AssetCategory $assetCategory) => redirect()->route('hrms.masters.asset-categories.edit', $assetCategory))->name('asset-categories.edit');
    Route::put('asset-categories/{assetCategory}', [AssetCategoryController::class, 'update'])->middleware('can:settings.manage')->name('asset-categories.update');
    Route::delete('asset-categories/{assetCategory}', [AssetCategoryController::class, 'destroy'])->middleware('can:settings.manage')->name('asset-categories.destroy');

    Route::resource('house-keeping-categories', HouseKeepingCategoryController::class)
         ->middleware('can:settings.manage')
         ->except(['show']);

    Route::resource('house-keeping-works', HouseKeepingWorkController::class)
         ->middleware('can:settings.manage')
         ->except(['show']);

    Route::get('facility-titles', fn() => redirect()->route('hrms.masters.facility-titles.index'))->name('facility-titles.index');
    Route::get('facility-titles/create', fn() => redirect()->route('hrms.masters.facility-titles.create'))->name('facility-titles.create');
    Route::post('facility-titles', [FacilityTitleController::class, 'store'])->middleware('can:settings.manage')->name('facility-titles.store');
    Route::get('facility-titles/{facilityTitle}/edit', fn(\App\Models\FacilityTitle $facilityTitle) => redirect()->route('hrms.masters.facility-titles.edit', $facilityTitle))->name('facility-titles.edit');
    Route::put('facility-titles/{facilityTitle}', [FacilityTitleController::class, 'update'])->middleware('can:settings.manage')->name('facility-titles.update');
    Route::delete('facility-titles/{facilityTitle}', [FacilityTitleController::class, 'destroy'])->middleware('can:settings.manage')->name('facility-titles.destroy');

    Route::get('product-attribute', fn() => redirect()->route('masters.product-attribute.index'))->name('product-attribute.index');

    Route::get('/facebook-integration',       [FacebookIntegrationController::class, 'index'])->middleware('can:facebook_integration.menuview')->name('facebook-integration');

    Route::get('/auth/redirect', function () {
    return Socialite::driver('facebook')->redirect();
});

Route::get('/api_integrations',[FacebookIntegrationController::class,'index']);
Route::get('/facebook_integration',[FacebookIntegrationController::class,'facebookIndex'])->name('facebook_integration'); // FACEBOOK INTEGRATION INDEX PAGE
Route::post('/connectfb',[FacebookIntegrationController::class,'connectfb'])->middleware('can:facebook_integration.manage');
Route::post('/mapfields',[FacebookIntegrationController::class,'mapfields'])->middleware('can:facebook_integration.manage');
Route::post('/fbassignleads',[FacebookIntegrationController::class,'fbassignleads'])->middleware('can:facebook_integration.manage');

    Route::get('/authenticate/redirect/{social}',[FacebookIntegrationController::class,'socialiteRedirect'])->name('socialite-redirect');
Route::get('/authenticate/callback/{social}',[FacebookIntegrationController::class,'socialiteCallback'])->name('socialite-callback');

Route::get('/auth/facebook',[FacebookIntegrationController::class,'socialiteRedirect']);
Route::get('/auth/facebook/callback',[FacebookIntegrationController::class,'socialiteCallback'])->name('facebook_callback');

Route::post('/multiple_campaigns',[FacebookIntegrationController::class,'multipleCampaigns'])->middleware('can:facebook_integration.manage')->name('multiple_campaigns');
Route::post('/choose_camps',[FacebookIntegrationController::class,'chooseCampaigns'])->middleware('can:facebook_integration.manage');

Route::get('/viewassigned', [FacebookIntegrationController::class, 'viewAssigned']);
Route::post('/assignUsers', [FacebookIntegrationController::class, 'assignUsers'])->middleware('can:facebook_integration.manage');

Route::get('/fb_multiple_campaigns/{adid}', [FacebookIntegrationController::class, 'fbMultipleCampaigns'])->name('fb_multiple_campaigns');
Route::get('/fb_multiple_accounts/{adid}', [FacebookIntegrationController::class, 'fbMultipleAdAccs'])->name('fb_multiple_accounts');
Route::get('/fb_ac_error', [FacebookIntegrationController::class, 'fberrorLogin'])->name('fb_ac_error');

Route::post('/choose_ad_accouts',[FacebookIntegrationController::class,'chooseadaccs'])->middleware('can:facebook_integration.manage');
Route::post('/fbassignleads',[FacebookIntegrationController::class,'fbassignleads'])->middleware('can:facebook_integration.manage')->name('fbassignleads');

Route::post('/deleteintegration',[FacebookIntegrationController::class,'deleteintegration'])->middleware('can:facebook_integration.manage')->name('deleteintegration');
Route::post('/editfieldmaps',[FacebookIntegrationController::class,'editfieldmaps'])->middleware('can:facebook_integration.manage')->name('editfieldmaps');
Route::post('/facebook-integration/{campaignMaster}/sync', [FacebookIntegrationController::class, 'syncCampaign'])
    ->middleware('can:facebook_integration.manage')
    ->name('facebook-integration.sync');
Route::post('/facebook-integration/sync-all', [FacebookIntegrationController::class, 'syncAllCampaigns'])
    ->middleware('can:facebook_integration.manage')
    ->name('facebook-integration.sync-all');


    Route::get('/quotation-setting',       [QuotationSettingsController::class, 'index'])->middleware('can:quotation_settings.menuview')->name('quotation');
    Route::get('/payroll', fn() => redirect()->route('hrms.masters.payroll.index'))->name('payroll.index');
    Route::post('/payroll', [PayrollSettingController::class, 'update'])->middleware('can:payroll_settings.manage')->name('payroll.update');

    Route::get('/design-settings', [DesignSettingController::class, 'index'])->middleware('can:design_settings.menuview')->name('design-settings.index');
    Route::post('/design-settings', [DesignSettingController::class, 'store'])->middleware('can:design_settings.manage')->name('design-settings.store');

    Route::get('/sales-targets', [SalesTargetSettingController::class, 'index'])->middleware('can:settings.manage')->name('sales-targets.index');
    Route::post('/sales-targets', [SalesTargetSettingController::class, 'store'])->middleware('can:settings.manage')->name('sales-targets.store');

    Route::post('/quotation', [QuotationSettingsController::class, 'update'])->middleware('can:quotation_settings.manage')->name('quotation.update');
    Route::delete('/quotation/file/{type}', [QuotationSettingsController::class, 'deleteFile'])->middleware('can:quotation_settings.manage')->name('quotation.file.delete');

    // Lead Reallocation
    Route::prefix('lead-reallocation')->name('lead-reallocation.')->middleware('can:settings.manage')->group(function () {
        Route::get('/', [\App\Http\Controllers\LeadReallocationController::class, 'index'])->name('index');
        Route::post('/reallocate', [\App\Http\Controllers\LeadReallocationController::class, 'reallocate'])->name('reallocate');
    });

    // Lead Import Settings
    Route::prefix('lead-import')->name('lead-import.')->middleware('can:settings.manage')->group(function () {
        Route::get('/', [\App\Http\Controllers\LeadImportController::class, 'index'])->name('index');
        Route::post('/parse', [\App\Http\Controllers\LeadImportController::class, 'parseFile'])->name('parse');
        Route::post('/process', [\App\Http\Controllers\LeadImportController::class, 'processImport'])->name('process');
        Route::get('/sample', [\App\Http\Controllers\LeadImportController::class, 'downloadSample'])->name('sample');
    });

    // Expense Pipeline Settings
    Route::prefix('expense-pipeline')->name('expense-pipeline.')->middleware('can:expense_pipeline.menuview')->group(function () {
        Route::get('/', [\App\Http\Controllers\ExpensePipelineController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\ExpensePipelineController::class, 'store'])->name('store');
        Route::put('/{expensePipeline}', [\App\Http\Controllers\ExpensePipelineController::class, 'update'])->name('update');
        Route::delete('/{expensePipeline}', [\App\Http\Controllers\ExpensePipelineController::class, 'destroy'])->name('destroy');
        Route::patch('/{expensePipeline}/toggle', [\App\Http\Controllers\ExpensePipelineController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Leave Hierarchy Settings
    Route::prefix('leave-hierarchy')->name('leave-hierarchy.')->middleware('can:leave_hierarchy.menuview')->group(function () {
        Route::get('/', [\App\Http\Controllers\LeaveHierarchyController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\LeaveHierarchyController::class, 'store'])->name('store');
        Route::put('/{leaveHierarchy}', [\App\Http\Controllers\LeaveHierarchyController::class, 'update'])->name('update');
        Route::delete('/{leaveHierarchy}', [\App\Http\Controllers\LeaveHierarchyController::class, 'destroy'])->name('destroy');
        Route::patch('/{leaveHierarchy}/toggle', [\App\Http\Controllers\LeaveHierarchyController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Expense Category Master
    Route::get('expense-categories', fn() => redirect()->route('hrms.masters.expense-categories.index'))->name('expense-categories.index');
    Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->name('expense-categories.update');
    Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');
    Route::patch('expense-categories/{expenseCategory}/toggle', [\App\Http\Controllers\ExpenseCategoryController::class, 'toggleStatus'])->name('expense-categories.toggle-status');

    Route::get('lead-sources', fn() => redirect()->route('masters.lead-sources.index'))->name('lead-sources.index');
    Route::get('outcome-categories', fn() => redirect()->route('masters.outcome-categories.index'))->name('outcome-categories.index');
    Route::get('outcome-sub-categories', fn() => redirect()->route('masters.outcome-sub-categories.index'))->name('outcome-sub-categories.index');

    Route::get('form-customization', function () {
        $companies = \App\Models\Company::orderBy('company_name')->get(['id', 'company_name']);
        return view('pages.field_customization.index', compact('companies'));
    })->middleware('can:form_customization.menuview')->name('form-customization.index');
});
    Route::get('/get-subcategories/{id}', [OutcomeCategoryController::class, 'getSubCategories'])->middleware('can:leads.view');

     Route::get('/quotations', [QuotationController::class, 'index'])->middleware('can:quotations.view')->name('quotations.index');
    Route::get('/quotations/create/{leadId?}', [QuotationController::class, 'create'])->middleware('can:quotations.create')->name('quotations.create');
    Route::post('/quotations', [QuotationController::class, 'store'])->middleware('can:quotations.create')->name('quotations.store');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->middleware('can:quotations.view')->name('quotations.show');
    Route::patch('/quotations/{quotation}/approve', [QuotationController::class, 'approve'])->middleware('can:quotations.approve')->name('quotations.approve');
    Route::post('/quotations/{quotation}/send-email', [QuotationController::class, 'sendEmail'])->middleware('can:quotations.view')->name('quotations.send-email');
    Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->middleware('can:quotations.delete')->name('quotations.destroy');


    // Helper: product search for Select2 AJAX (optional)
    Route::get('/api/products-search', [QuotationController::class, 'productsApi'])->middleware('can:quotations.create')->name('api.products.search');

    Route::post('/ai/summarize', [AiController::class, 'summarize'])->name('ai.summarize');

    // Support Ticket Routes
    Route::get('/support', [\App\Http\Controllers\SupportController::class, 'index'])->middleware('can:support.menuview')->name('support.index');
    Route::post('/support', [\App\Http\Controllers\SupportController::class, 'store'])->middleware('can:support.create')->name('support.store');
    Route::post('/support/{ticket}/update-status', [\App\Http\Controllers\SupportController::class, 'updateStatus'])->middleware('can:support.update')->name('support.update-status');


//     Route::prefix('products')->name('products.')->middleware(['auth'])->group(function () {
//     Route::get('/',                  [ProductController::class, 'index'])->name('index');
//     Route::get('/create',            [ProductController::class, 'create'])->name('create');
//     Route::post('/',                 [ProductController::class, 'store'])->name('store');
//     Route::get('/{product}',         [ProductController::class, 'show'])->name('show');
//     Route::get('/{product}/edit',    [ProductController::class, 'edit'])->name('edit');
//     Route::put('/{product}',         [ProductController::class, 'update'])->name('update');
//     Route::delete('/{product}',      [ProductController::class, 'destroy'])->name('destroy');
//     Route::patch('/{product}/toggle',[ProductController::class, 'toggleStatus'])->name('toggle');
// });

});

Route::get('/forms/{token}', [DynamicFormController::class, 'publicShow'])->name('dynamic-forms.public.show');
Route::post('/forms/{token}', [DynamicFormController::class, 'publicSubmit'])->name('dynamic-forms.public.submit');