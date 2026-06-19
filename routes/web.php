<?php

use App\Http\Controllers\AiController;
use App\Http\Controllers\AccessMappingController;
use App\Http\Controllers\AssetEntryController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DynamicFormController;
use App\Http\Controllers\EmployeeOnboardingController;
use App\Http\Controllers\EmployeeExitController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CrmReportController;
use App\Http\Controllers\FacebookIntegrationController;
use App\Http\Controllers\HolidayCalendarController;
use App\Http\Controllers\HouseKeepingCategoryController;
use App\Http\Controllers\HouseKeepingManagementController;
use App\Http\Controllers\HouseKeepingWorkController;
use App\Http\Controllers\HrmsAnnouncementController;
use App\Http\Controllers\InternJoiningFormController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadCallUpdateController;
use App\Http\Controllers\LeadProductPriceRequestController;
use App\Http\Controllers\LeadShowController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\LeadSourceController;
use App\Http\Controllers\LeadStatusController;
use App\Http\Controllers\MastersController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OvpModuleController;
use App\Http\Controllers\OutcomeCategoryController;
use App\Http\Controllers\OutcomeSubCategoryController;
use App\Http\Controllers\ProductionApprovalController;
use App\Http\Controllers\PermissionRequestController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollSettingController;
use App\Http\Controllers\ProductAttributeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductOvpFormController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\QuotationSettingsController;
use App\Http\Controllers\RecruitmentController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorManagementController;
use App\Http\Controllers\FacilityManagementController;
use App\Http\Controllers\FacilityTitleController;
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


Route::get('/lead/form-customization', fn() => view('pages.field_customization.index'))
    ->middleware(['auth', 'can:form_customization.menuview']);
Route::middleware(['auth'])->group(function () {

  // Dashboard
    Route::get('/', fn() => redirect()->route('dashboard'));
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

     // Super Admin Dashboard (API-integrated blade)
    Route::get('/dashboard/admin', [DashboardController::class, 'index'])
        ->name('dashboard.admin');

    Route::get('/product-dashboard/admin', [SuperAdminDashboardController::class, 'adminProductindex'])
        ->middleware('can:dashboard.view');

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

    Route::get('/authentications', [UserController::class, 'authIndex'])
        ->middleware('can:authentication.menuview')
        ->name('auth.index');

    Route::get('/ovp-module', [OvpModuleController::class, 'index'])
        ->middleware('can:ovp_module.menuview')
        ->name('ovp-module.index');
    Route::get('/reports/crm', [CrmReportController::class, 'index'])
        ->name('reports.crm.index');
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
        Route::get('/projects/timesheets', [ProjectController::class, 'timesheets'])
            ->name('projects.timesheets');
        Route::post('/projects/timesheets', [ProjectController::class, 'storeTimesheet'])
            ->name('projects.timesheets.store');
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
        Route::post('/projects-details/updates/quick', [ProjectController::class, 'storeQuickUpdate'])
            ->name('projects.updates.quick-store');
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
    Route::get('/hrms-announcements', [HrmsAnnouncementController::class, 'index'])->name('hrms-announcements.index');
    Route::get('/hrms-announcements/create', [HrmsAnnouncementController::class, 'create'])->name('hrms-announcements.create');
    Route::post('/hrms-announcements', [HrmsAnnouncementController::class, 'store'])->name('hrms-announcements.store');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/create', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/checkout', [AttendanceController::class, 'createCheckout'])->name('attendance.checkout.create');
    Route::post('/attendance/checkout', [AttendanceController::class, 'storeCheckout'])->name('attendance.checkout.store');
    Route::get('/attendance/lookup', [AttendanceController::class, 'lookupAttendance'])->name('attendance.lookup');
    Route::get('/attendance/export', [AttendanceController::class, 'export'])->name('attendance.export');
    Route::get('/house-keeping-management', [HouseKeepingManagementController::class, 'index'])->name('house-keeping.index');
    Route::post('/house-keeping-management/completions', [HouseKeepingManagementController::class, 'updateCompletion'])->name('house-keeping.completions.update');
    Route::resource('leave-requests', LeaveRequestController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/leave-requests/{leaveRequest}/approvals/{approval}/approve', [LeaveRequestController::class, 'approve'])
        ->name('leave-requests.approve');
    Route::patch('/leave-requests/{leaveRequest}/approvals/{approval}/reject', [LeaveRequestController::class, 'reject'])
        ->name('leave-requests.reject');
    Route::resource('permission-requests', PermissionRequestController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/permission-requests/{permissionRequest}/approvals/{approval}/approve', [PermissionRequestController::class, 'approve'])
        ->name('permission-requests.approve');
    Route::patch('/permission-requests/{permissionRequest}/approvals/{approval}/reject', [PermissionRequestController::class, 'reject'])
        ->name('permission-requests.reject');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');
    Route::resource('recruitment', RecruitmentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/recruitment/{recruitment}/call-updates', [RecruitmentController::class, 'storeCallUpdate'])
        ->name('recruitment.call-updates.store');
    Route::post('/recruitment/{recruitment}/interviews', [RecruitmentController::class, 'storeInterview'])
        ->name('recruitment.interviews.store');
    Route::patch('/recruitment/{recruitment}/status', [RecruitmentController::class, 'updateStatus'])
        ->name('recruitment.status.update');
    Route::resource('assets', AssetEntryController::class);
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

    // ── Main Lead CRUD ─────────────────────────────────────────
   Route::prefix('leads')->name('leads.')->group(function () {

        // ── Core CRUD ─────────────────────────────────────────
        Route::get('/',            [LeadController::class, 'index'])->middleware('can:leads.view')->name('index');
        Route::get('/products',    [LeadController::class, 'productsIndex'])->middleware('can:leads.view')->name('products.index');
        Route::get('/create',      [LeadController::class, 'create'])->middleware('can:leads.create')->name('create');
        Route::get('/call-updates',[LeadCallUpdateController::class, 'index'])->middleware('can:call_updates.view')->name('calls.index');
        Route::post('/',           [LeadController::class, 'store'])->middleware('can:leads.create')->name('store');
        Route::get('/{lead}',      [LeadController::class, 'show'])->middleware('can:leads.view')->name('show');
        Route::get('/{lead}/edit', [LeadController::class, 'edit'])->middleware('can:leads.edit')->name('edit');
        Route::put('/{lead}',      [LeadController::class, 'update'])->middleware('can:leads.edit')->name('update');
        Route::delete('/{lead}',   [LeadController::class, 'destroy'])->middleware('can:leads.delete')->name('destroy');
        Route::patch('/{lead}/status', [LeadController::class, 'updateStatus'])->middleware('can:leads.update')->name('update-status');

        // ── Call Updates ──────────────────────────────────────
        Route::post('/{lead}/calls',             [LeadShowController::class, 'storeCall'])->middleware('can:call_updates.create')->name('calls.store');
        Route::delete('/{lead}/calls/{call}',    [LeadShowController::class, 'destroyCall'])->middleware('can:call_updates.delete')->name('calls.destroy');

        // ── Reminders ─────────────────────────────────────────
        Route::post('/{lead}/reminders',                  [LeadShowController::class, 'storeReminder'])->middleware('can:leads.edit')->name('reminders.store');
        Route::patch('/{lead}/reminders/{reminder}/done', [LeadShowController::class, 'completeReminder'])->middleware('can:leads.edit')->name('reminders.complete');
        Route::delete('/{lead}/reminders/{reminder}',     [LeadShowController::class, 'destroyReminder'])->middleware('can:leads.edit')->name('reminders.destroy');

        // ── Products ───────────────────────────────────────────
        Route::post('/{lead}/products',
            [LeadShowController::class, 'storeProduct'])->middleware('can:leads.edit')->name('products.store');

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

Route::get('/forms/{token}', [DynamicFormController::class, 'publicShow'])->name('dynamic-forms.public.show');
Route::post('/forms/{token}', [DynamicFormController::class, 'publicSubmit'])->name('dynamic-forms.public.submit');

Route::resource('facility-management', FacilityManagementController::class)
         ->except(['show']);

Route::prefix('settings')->name('settings.')->middleware('can:settings.view')->group(function () {

    // Main settings dashboard
    Route::get('/', fn() => view('pages.settings.index'))->name('index');

    // Lead Status  (no create/show pages – handled via modal on index)
    Route::resource('lead-statuses', LeadStatusController::class)

         ->except(['create', 'show']);

    Route::resource('product-category', ProductCategoryController::class)

         ->except(['create', 'show']);

    Route::resource('departments', DepartmentController::class)
         ->middleware('can:settings.manage')
         ->except(['show']);

    Route::resource('branches', BranchController::class)
         ->middleware('can:settings.manage')
         ->except(['show']);



    Route::resource('leave-types', LeaveTypeController::class)
         ->middleware('can:settings.manage')
         ->except(['show']);

    Route::resource('asset-categories', AssetCategoryController::class)
         ->middleware('can:settings.manage')
         ->except(['show']);

    Route::resource('house-keeping-categories', HouseKeepingCategoryController::class)
         ->except(['show']);

    Route::resource('house-keeping-works', HouseKeepingWorkController::class)
         ->except(['show']);

    Route::resource('facility-titles', FacilityTitleController::class)
         ->middleware('can:settings.manage')
         ->except(['show']);

    Route::post('holiday-calendars/import', [HolidayCalendarController::class, 'import'])
         ->middleware('can:settings.manage')
         ->name('holiday-calendars.import');

    Route::resource('holiday-calendars', HolidayCalendarController::class)
         ->middleware('can:settings.manage')
         ->except(['show']);

    Route::resource('product-attribute', ProductAttributeController::class)
         ->middleware('can:settings.manage')
         ->except(['create', 'show']);

    Route::get('/facebook-integration',       [FacebookIntegrationController::class, 'index'])->name('facebook-integration');

    Route::get('/auth/redirect', function () {
    return Socialite::driver('facebook')->redirect();
});

Route::get('/api_integrations',[FacebookIntegrationController::class,'index']);
Route::get('/facebook_integration',[FacebookIntegrationController::class,'facebookIndex'])->name('facebook_integration'); // FACEBOOK INTEGRATION INDEX PAGE
Route::post('/connectfb',[FacebookIntegrationController::class,'connectfb'])->middleware('can:settings.manage');
Route::post('/mapfields',[FacebookIntegrationController::class,'mapfields'])->middleware('can:settings.manage');
Route::post('/fbassignleads',[FacebookIntegrationController::class,'fbassignleads'])->middleware('can:settings.manage');

    Route::get('/authenticate/redirect/{social}',[FacebookIntegrationController::class,'socialiteRedirect'])->name('socialite-redirect');
Route::get('/authenticate/callback/{social}',[FacebookIntegrationController::class,'socialiteCallback'])->name('socialite-callback');

Route::get('/auth/facebook',[FacebookIntegrationController::class,'socialiteRedirect']);
Route::get('/auth/facebook/callback',[FacebookIntegrationController::class,'socialiteCallback'])->name('facebook_callback');

Route::post('/multiple_campaigns',[FacebookIntegrationController::class,'multipleCampaigns'])->middleware('can:settings.manage')->name('multiple_campaigns');
Route::post('/choose_camps',[FacebookIntegrationController::class,'chooseCampaigns'])->middleware('can:settings.manage');

Route::get('/viewassigned', [FacebookIntegrationController::class, 'viewAssigned']);
Route::post('/assignUsers', [FacebookIntegrationController::class, 'assignUsers'])->middleware('can:settings.manage');

Route::get('/fb_multiple_campaigns/{adid}', [FacebookIntegrationController::class, 'fbMultipleCampaigns'])->name('fb_multiple_campaigns');
Route::get('/fb_multiple_accounts/{adid}', [FacebookIntegrationController::class, 'fbMultipleAdAccs'])->name('fb_multiple_accounts');
Route::get('/fb_ac_error', [FacebookIntegrationController::class, 'fberrorLogin'])->name('fb_ac_error');

Route::post('/choose_ad_accouts',[FacebookIntegrationController::class,'chooseadaccs'])->middleware('can:settings.manage');
Route::post('/fbassignleads',[FacebookIntegrationController::class,'fbassignleads'])->middleware('can:settings.manage')->name('fbassignleads');

Route::post('/deleteintegration',[FacebookIntegrationController::class,'deleteintegration'])->middleware('can:settings.manage')->name('deleteintegration');
Route::post('/editfieldmaps',[FacebookIntegrationController::class,'editfieldmaps'])->middleware('can:settings.manage')->name('editfieldmaps');
Route::post('/facebook-integration/{campaignMaster}/sync', [FacebookIntegrationController::class, 'syncCampaign'])
    ->middleware('can:settings.manage')
    ->name('facebook-integration.sync');


    Route::get('/quotation-setting',       [QuotationSettingsController::class, 'index'])->name('quotation');
    Route::get('/payroll', [PayrollSettingController::class, 'index'])->middleware('can:settings.manage')->name('payroll.index');
    Route::post('/payroll', [PayrollSettingController::class, 'update'])->middleware('can:settings.manage')->name('payroll.update');

    Route::post('/quotation', [QuotationSettingsController::class, 'update'])->middleware('can:settings.manage')->name('quotation.update');
    Route::delete('/quotation/file/{type}', [QuotationSettingsController::class, 'deleteFile'])->middleware('can:settings.manage')->name('quotation.file.delete');

    // Lead Source
    Route::resource('lead-sources', LeadSourceController::class)
         ->middleware('can:lead_source.view')
         ->except(['create', 'show']);

    // Outcome Category
    Route::resource('outcome-categories', OutcomeCategoryController::class)
         ->middleware('can:outcome_category.view')
         ->except(['create', 'show']);

    // Outcome Sub Category
    Route::resource('outcome-sub-categories', OutcomeSubCategoryController::class)
         ->middleware('can:outcome_sub_category.view')
         ->except(['create', 'show']);
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
