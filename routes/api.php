<?php

use App\Http\Controllers\AdminDashboardProductController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\App\AuthController as MobileAuthController;
use App\Http\Controllers\App\DailyAttendanceController as MobileDailyAttendanceController;
use App\Http\Controllers\App\DashboardController as MobileDashboardController;
use App\Http\Controllers\App\LeadController as MobileLeadController;
use App\Http\Controllers\App\LeadShowController as MobileLeadShowController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadFormFieldController;
use App\Http\Controllers\LeadProductController;
use App\Http\Controllers\LeadProductPriceRequestController;
use App\Http\Controllers\ProductOvpFormController;
use App\Http\Controllers\OutcomeCategoryController;
use App\Http\Controllers\App\AppApiController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Controllers\App\HRMS\DashboardApiController;
use App\Http\Controllers\App\HRMS\EmployeeApiController;
use App\Http\Controllers\App\HRMS\InternApiController;
use App\Http\Controllers\App\HRMS\AttendanceApiController;
use App\Http\Controllers\App\HRMS\AssetApiController;
use App\Http\Controllers\App\HRMS\HolidayApiController;
use App\Http\Controllers\App\HRMS\LeaveTypeApiController;
use App\Http\Controllers\App\HRMS\LeaveRequestApiController;
use App\Http\Controllers\App\HRMS\PermissionRequestApiController;
use App\Http\Controllers\App\HRMS\FacilityManagementApiController;
use App\Http\Controllers\App\HRMS\VisitorManagementApiController;
use App\Http\Controllers\App\HRMS\AttendanceLocationApiController;
use App\Http\Controllers\App\OvpModuleApiController;
use App\Http\Controllers\App\ProductionApprovalApiController;
use App\Http\Controllers\App\ProductionInitiationApiController;
use App\Http\Controllers\App\ProjectApiController;
use App\Http\Controllers\App\ReportApiController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\App\AppMenuController;
use App\Http\Controllers\App\NotificationApiController as MobileNotificationApiController;
use App\Http\Controllers\App\CstAllocationApiController;
use App\Http\Controllers\App\CustomerSuccessDashboardApiController;

/*
|--------------------------------------------------------------------------
| Home Page Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::post('/ai/summarize', [AiController::class, 'summarize'])->name('ai.summarize');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard-data', [SuperAdminDashboardController::class, 'dashboardData']);
    Route::get('/product-dashboard-data', [AdminDashboardProductController::class, 'index']);
    Route::get('/product-dashboard-data/filters', [AdminDashboardProductController::class, 'filterOptions']);
});

Route::get('/getLeadQuotations/{leadid}', [QuotationController::class, 'getLeadQuotations']);
Route::get('/getAllQuotations', [QuotationController::class, 'getAllQuotations']);

// Route::get('/products', [ProductController::class, 'getProducts']);



// ── Product catalogue ──────────────────────────────────────────
Route::get('products',        [LeadProductController::class, 'productList']);
Route::get('products/{id}',   [LeadProductController::class, 'productDetail']);
Route::get('products/{product}/ovp-form-fields', [ProductOvpFormController::class, 'index']);
Route::post('products/{product}/ovp-form-fields', [ProductOvpFormController::class, 'store']);
Route::post('products/{product}/ovp-form-fields/reorder', [ProductOvpFormController::class, 'reorder']);
Route::get('products/{product}/ovp-form-schema', [ProductOvpFormController::class, 'schema']);
Route::put('products/{product}/ovp-form-fields/{field}', [ProductOvpFormController::class, 'update']);
Route::patch('products/{product}/ovp-form-fields/{field}/toggle', [ProductOvpFormController::class, 'toggle']);
Route::delete('products/{product}/ovp-form-fields/{field}', [ProductOvpFormController::class, 'destroy']);

// ── Lead Products (Deals) ──────────────────────────────────────
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('lead-products/{lead_id}', [LeadProductController::class, 'index']);
    Route::post('lead-products',           [LeadProductController::class, 'store']);
    Route::get('lead-products/{id}/production', [LeadProductController::class, 'productionDetail']);
    Route::post('lead-products/{id}/production-initiations', [LeadProductController::class, 'storeProductionInitiation']);
    Route::post('lead-product-price-requests', [LeadProductPriceRequestController::class, 'store']);
    Route::put('lead-products/status',    [LeadProductController::class, 'updateStatus']);
    Route::put('lead-products/{id}',       [LeadProductController::class, 'update']);
    Route::delete('lead-products/{id}',    [LeadProductController::class, 'destroy']);

    // ── Payments ───────────────────────────────────────────────────
    Route::get('payments/{lead_product_id}', [LeadProductController::class, 'paymentHistory']);
    Route::post('payments',                   [LeadProductController::class, 'storePayment']);
    Route::delete('payments/{id}',              [LeadProductController::class, 'destroyPayment']);
});

/*
|--------------------------------------------------------------------------
| Mobile Auth Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->prefix('lead-form-fields')->group(function () {

    // ── Meta / Utility ───────────────────────────────────────────────
    Route::get('field-types',  [LeadFormFieldController::class, 'fieldTypes']);   // GET  /api/lead-form-fields/field-types
    Route::get('schema',       [LeadFormFieldController::class, 'schema']);       // GET  /api/lead-form-fields/schema
    Route::post('reorder',     [LeadFormFieldController::class, 'reorder']);      // POST /api/lead-form-fields/reorder
    Route::post('calculate',   [LeadFormFieldController::class, 'calculate']);    // POST /api/lead-form-fields/calculate

    // ── CRUD ─────────────────────────────────────────────────────────
    Route::get('/',            [LeadFormFieldController::class, 'index']);        // GET  /api/lead-form-fields
    Route::post('/',           [LeadFormFieldController::class, 'store']);        // POST /api/lead-form-fields
    Route::get('/{leadFormField}',    [LeadFormFieldController::class, 'show']);  // GET  /api/lead-form-fields/{id}
    Route::put('/{leadFormField}',    [LeadFormFieldController::class, 'update']); // PUT  /api/lead-form-fields/{id}
    Route::patch('/{leadFormField}',  [LeadFormFieldController::class, 'update']); // PATCH /api/lead-form-fields/{id}
    Route::delete('/{leadFormField}', [LeadFormFieldController::class, 'destroy']); // DELETE /api/lead-form-fields/{id}
    Route::patch('/{leadFormField}/toggle', [LeadFormFieldController::class, 'toggle']); // PATCH /api/lead-form-fields/{id}/toggle
});

Route::prefix('mobile/auth')->group(function () {
    Route::post('login',    [MobileAuthController::class, 'login']);
    Route::post('register', [MobileAuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [MobileAuthController::class, 'logout']);
        Route::get('me',      [MobileAuthController::class, 'me']);
    });
});



Route::get('/get-outcome-category', [OutcomeCategoryController::class, 'getOutcomeCategory']);
Route::get('/get-subcategories/{id}', [OutcomeCategoryController::class, 'getSubCategories']);
Route::get('/get-lead-status', [LeadController::class, 'leadStatus']);
Route::get('/get-lead-source', [LeadController::class, 'leadSource']);
Route::get('/quotation/{quotation}', [QuotationController::class, 'apiShow']);
Route::post('/create-quotation', [QuotationController::class, 'apiStore']);
Route::put('/quotation/{quotation}', [QuotationController::class, 'apiUpdate']);
Route::patch('/quotation/{quotation}', [QuotationController::class, 'apiUpdate']);

/*
|--------------------------------------------------------------------------
| Mobile Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('mobile')->name('mobile.')->group(function () {
    Route::get('dashboard', [MobileDashboardController::class, 'index'])->name('dashboard');
    Route::get('menu', [AppMenuController::class, 'index'])->name('menu');
    Route::get('modules', [AppMenuController::class, 'modules'])->name('modules');
    Route::prefix('hrms')->name('hrms.')->group(function () {
        Route::get('dashboard',         [DashboardApiController::class,  'index'])->name('dashboard');
        Route::get('employees/meta',    [EmployeeApiController::class,    'meta'])->name('employees.meta');
        Route::get('employees',         [EmployeeApiController::class,    'index'])->name('employees.index');
        Route::get('employees/{id}',    [EmployeeApiController::class,    'show'])->name('employees.show');

        Route::get('interns',      [InternApiController::class, 'index'])->name('interns.index');
        Route::get('interns/{id}', [InternApiController::class, 'show'])->name('interns.show');

        Route::get('attendance',      [AttendanceApiController::class, 'index'])->name('attendance.index');
        Route::get('attendance/{id}', [AttendanceApiController::class, 'show'])->name('attendance.show');

        Route::get('assets/meta',   [AssetApiController::class, 'meta'])->name('assets.meta');
        Route::get('assets',        [AssetApiController::class, 'index'])->name('assets.index');
        Route::get('assets/{id}',   [AssetApiController::class, 'show'])->name('assets.show');

        Route::get('holidays/meta', [HolidayApiController::class, 'meta'])->name('holidays.meta');
        Route::get('holidays',      [HolidayApiController::class, 'index'])->name('holidays.index');

        Route::get('leave-types',      [LeaveTypeApiController::class, 'index'])->name('leave-types.index');
        Route::get('leave-types/{leaveType}', [LeaveTypeApiController::class, 'show'])->name('leave-types.show');

        // Leave Requests — static routes BEFORE wildcard
        Route::get('leave-requests/meta',               [LeaveRequestApiController::class, 'meta'])->name('leave-requests.meta');
        Route::get('leave-requests/pending-approvals',  [LeaveRequestApiController::class, 'pendingApprovals'])->name('leave-requests.pending-approvals');
        Route::get('leave-requests/handled-approvals',  [LeaveRequestApiController::class, 'handledApprovals'])->name('leave-requests.handled-approvals');

        Route::get('leave-requests',                    [LeaveRequestApiController::class, 'index'])->name('leave-requests.index');
        Route::post('leave-requests',                   [LeaveRequestApiController::class, 'store'])->name('leave-requests.store');
        Route::get('leave-requests/{leaveRequest}',     [LeaveRequestApiController::class, 'show'])->name('leave-requests.show');

        // Approve / Reject
        Route::post('leave-requests/{leaveRequest}/approvals/{approval}/approve', [LeaveRequestApiController::class, 'approve'])->name('leave-requests.approve');
        Route::post('leave-requests/{leaveRequest}/approvals/{approval}/reject',  [LeaveRequestApiController::class, 'reject'])->name('leave-requests.reject');

        // Permission Requests — static routes BEFORE wildcard
        Route::get('permission-requests/meta',              [PermissionRequestApiController::class, 'meta'])->name('permission-requests.meta');
        Route::get('permission-requests/pending-approvals', [PermissionRequestApiController::class, 'pendingApprovals'])->name('permission-requests.pending-approvals');
        Route::get('permission-requests/handled-approvals', [PermissionRequestApiController::class, 'handledApprovals'])->name('permission-requests.handled-approvals');

        Route::get('permission-requests',                   [PermissionRequestApiController::class, 'index'])->name('permission-requests.index');
        Route::post('permission-requests',                  [PermissionRequestApiController::class, 'store'])->name('permission-requests.store');
        Route::get('permission-requests/{permissionRequest}', [PermissionRequestApiController::class, 'show'])->name('permission-requests.show');

        // Approve / Reject
        Route::post('permission-requests/{permissionRequest}/approvals/{approval}/approve', [PermissionRequestApiController::class, 'approve'])->name('permission-requests.approve');
        Route::post('permission-requests/{permissionRequest}/approvals/{approval}/reject',  [PermissionRequestApiController::class, 'reject'])->name('permission-requests.reject');

        Route::get('facility-titles', [FacilityManagementApiController::class, 'titles'])
            ->name('facility-titles.index');

        // ── Facility Management CRUD ──────────────────────────────────────────────
        Route::get('facility-management',             [FacilityManagementApiController::class, 'index'])
            ->name('facility-management.index');

        Route::post('facility-management',            [FacilityManagementApiController::class, 'store'])
            ->name('facility-management.store');

        Route::get('facility-management/{facilityManagement}',    [FacilityManagementApiController::class, 'show'])
            ->name('facility-management.show');

        Route::put('facility-management/{facilityManagement}',    [FacilityManagementApiController::class, 'update'])
            ->name('facility-management.update');

        Route::delete('facility-management/{facilityManagement}', [FacilityManagementApiController::class, 'destroy'])
            ->name('facility-management.destroy');

        Route::get('visitor-management',              [VisitorManagementApiController::class, 'index'])->name('visitor-management.index');
        Route::post('visitor-management',             [VisitorManagementApiController::class, 'store'])->name('visitor-management.store');
        Route::get('visitor-management/{id}',         [VisitorManagementApiController::class, 'show'])->name('visitor-management.show');
        Route::put('visitor-management/{id}',         [VisitorManagementApiController::class, 'update'])->name('visitor-management.update');
        Route::delete('visitor-management/{id}',      [VisitorManagementApiController::class, 'destroy'])->name('visitor-management.destroy');
    });

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::post('check-in', [MobileDailyAttendanceController::class, 'attendanceCheckIn'])->name('check-in');
        Route::post('check-out', [MobileDailyAttendanceController::class, 'attendanceCheckOut'])->name('check-out');
        Route::get('daily-list', [MobileDailyAttendanceController::class, 'dailyAttendanceList'])->name('daily-list');
        Route::get('/branch-location', [AttendanceLocationApiController::class, 'myBranch']);
    });

    // ── OVP Module ───────────────────────────────────────────────────────────────
    Route::prefix('ovp')->name('ovp.')->group(function () {
        Route::get('/',                                      [OvpModuleApiController::class, 'index'])->name('index');
        Route::get('/executives',                            [OvpModuleApiController::class, 'executives'])->name('executives');
        Route::post('/{productionInitiation}/allocate',      [OvpModuleApiController::class, 'allocate'])->name('allocate');
        Route::post('/{productionInitiation}/review',        [OvpModuleApiController::class, 'review'])->name('review');
    });

    // ── Production Approvals ─────────────────────────────────────────────────────
    Route::prefix('production-approvals')->name('production-approvals.')->group(function () {
        Route::get('/',                                          [ProductionApprovalApiController::class, 'index'])->name('index');
        Route::post('/{productionInitiation}/review',            [ProductionApprovalApiController::class, 'review'])->name('review');
    });

    // ── Production Initiation ──────────────────────────────────────────────────────
    Route::prefix('production-initiation')->name('production-initiation.')->group(function () {
        Route::get('/{leadProduct}/schema', [ProductionInitiationApiController::class, 'schema'])->name('schema');
        Route::post('/{leadProduct}/store',  [ProductionInitiationApiController::class, 'store'])->name('store');
    });

    Route::prefix('projects')->name('projects.')->group(function () {

        // ── Static / literal segments FIRST ─────────────────────────────────
        Route::get('dashboard', [ProjectApiController::class, 'dashboard'])
            ->name('dashboard');

        Route::get('timesheets',  [ProjectApiController::class, 'timesheets'])
            ->name('timesheets.index');
        Route::post('timesheets', [ProjectApiController::class, 'storeTimesheet'])
            ->name('timesheets.store');

        Route::patch('timesheets/{timesheet}/status', [ProjectApiController::class, 'updateTimesheetStatus'])
            ->name('timesheets.update-status');

        Route::get('/', [ProjectApiController::class, 'index'])
            ->name('index');

        Route::post('updates/quick', [ProjectApiController::class, 'storeQuickUpdate'])
            ->name('updates.quick-store');

        Route::get('my-accounts', [ProjectApiController::class, 'myAccounts'])
            ->name('mobile.projects.my-accounts.index');

        Route::get('my-accounts/{lead}', [ProjectApiController::class, 'showMyAccount'])
            ->name('mobile.projects.my-accounts.show');

        Route::get('designing-dashboard', [ProjectApiController::class, 'designingDashboard'])
            ->name('mobile.projects.designing-dashboard');

        Route::post('designing-dashboard/update-task', [ProjectApiController::class, 'updatePlannedTask'])
            ->name('mobile.projects.designing-dashboard.update-task');

        Route::post('designing-dashboard/allocate', [ProjectApiController::class, 'allocateDailyTask'])
            ->name('mobile.projects.designing-dashboard.allocate');

        // ── {productionInitiation} wildcard LAST ────────────────────────────
        Route::get('/{productionInitiation}', [ProjectApiController::class, 'show'])
            ->name('show');
        Route::post('/{productionInitiation}/allocate', [ProjectApiController::class, 'allocate'])
            ->name('allocate');
        Route::post('/{productionInitiation}/employee-allocate', [ProjectApiController::class, 'allocateEmployees'])
            ->name('employee-allocate');
        Route::post('/{productionInitiation}/schedule', [ProjectApiController::class, 'updateSchedule'])
            ->name('schedule.update');
        Route::post('/{productionInitiation}/updates', [ProjectApiController::class, 'storeUpdate'])
            ->name('updates.store');

        Route::patch('{productionInitiation}/content-calendar-sheet', [ProjectApiController::class, 'updateContentCalendarSheet'])
            ->name('mobile.projects.content-calendar-sheet.update');
        Route::patch('{productionInitiation}/content-calendar/approve', [ProjectApiController::class, 'approveContentCalendar'])
            ->name('mobile.projects.content-calendar.approve');
        Route::get('{productionInitiation}/content-calendar-data', [ProjectApiController::class, 'fetchContentCalendarData'])
            ->name('mobile.projects.content-calendar-data');
    });

    Route::get('/cst-allocation', [CstAllocationApiController::class, 'index']);
    Route::get('/cst-allocation/filters', [CstAllocationApiController::class, 'filters']);
    Route::post('/cst-allocation/{lead}/allocate-tl', [CstAllocationApiController::class, 'allocateTl']);
    Route::post('/cst-allocation/{lead}/allocate-executive', [CstAllocationApiController::class, 'allocateExecutive']);

    Route::get('/customer-success/data', [CustomerSuccessDashboardApiController::class, 'data']);
    Route::get('/customer-success/filters', [CustomerSuccessDashboardApiController::class, 'filters']);

    Route::get('notifications', [MobileNotificationApiController::class, 'index']);
    Route::get('notifications/unread-count', [MobileNotificationApiController::class, 'unreadCount']);
    Route::post('notifications/{notificationId}/read', [MobileNotificationApiController::class, 'markAsRead']);
    Route::post('notifications/read-all', [MobileNotificationApiController::class, 'markAllAsRead']);
});

/*
|--------------------------------------------------------------------------
| Mobile Leads Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('mobile/leads')->name('mobile.leads.')->group(function () {

    // ── Static routes MUST come before {lead} wildcard ──────────────────────
    Route::get('meta',             [MobileLeadController::class, 'meta'])->name('meta');
    Route::get('form-fields',      [MobileLeadController::class, 'customFields'])->name('form-fields'); // ← was 'lead-form-fields'

    Route::get('/lead-products', [MobileLeadController::class, 'leadProductFunction']);
    Route::get('/call-updates',  [MobileLeadController::class, 'callUpdateFunction']);

    Route::get('price-requests',              [MobileLeadController::class, 'priceRequestIndex']);
    Route::post('price-requests/{req}/approve', [MobileLeadController::class, 'priceRequestApprove']);
    Route::post('price-requests/{req}/reject', [MobileLeadController::class, 'priceRequestReject']);

    // ── CRUD ────────────────────────────────────────────────────────────────
    Route::get('/',          [MobileLeadController::class, 'index'])->name('index');
    Route::post('/',         [MobileLeadController::class, 'store'])->name('store');
    Route::get('/{lead}',    [MobileLeadController::class, 'show'])->name('show');
    Route::put('/{lead}',    [MobileLeadController::class, 'update'])->name('update');
    Route::delete('/{lead}', [MobileLeadController::class, 'destroy'])->name('destroy');

    Route::patch('/{lead}/status',         [MobileLeadController::class, 'updateStatus'])->name('update-status');
    Route::post('/{lead}/custom-fields',   [MobileLeadController::class, 'syncCustomFields'])->name('custom-fields.sync'); // ← removed extra /leads/

    // ── Call Updates ────────────────────────────────────────────────────────
    Route::post('/{lead}/calls',          [MobileLeadShowController::class, 'storeCall'])->name('calls.store');
    Route::delete('/{lead}/calls/{call}', [MobileLeadShowController::class, 'destroyCall'])->name('calls.destroy');

    // ── Reminders ───────────────────────────────────────────────────────────
    Route::post('/{lead}/reminders',                      [MobileLeadShowController::class, 'storeReminder'])->name('reminders.store');
    Route::patch('/{lead}/reminders/{reminder}/complete', [MobileLeadShowController::class, 'completeReminder'])->name('reminders.complete');
    Route::delete('/{lead}/reminders/{reminder}',         [MobileLeadShowController::class, 'destroyReminder'])->name('reminders.destroy');
    Route::post('/reminder-list',                         [AppApiController::class, 'reminderList']);

    // ── Lead Products ────────────────────────────────────────────────────────
    Route::post('/leadproducts-store',                   [LeadProductController::class, 'store'])->name('products.store');
    Route::put('/{lead}/products/{product}',          [MobileLeadShowController::class, 'updateProduct'])->name('products.update');
    Route::patch('/{lead}/products/{product}/status', [MobileLeadShowController::class, 'updateProductStatus'])->name('products.status');
    Route::delete('/{lead}/products/{product}',       [MobileLeadShowController::class, 'destroyProduct'])->name('products.destroy');

    Route::post('/price-requests', [MobileLeadShowController::class, 'priceRequest'])
        ->name('mobile.leads.price-requests.store');

    // ── Product Payments ─────────────────────────────────────────────────────
    Route::post('/{lead}/products/{product}/payments',             [MobileLeadShowController::class, 'storeProductPayment'])->name('products.payments.store');
    Route::get('/{lead}/products/{product}/payments',   [MobileLeadShowController::class, 'productPayments'])->name('products.payments.index');
    Route::delete('/{lead}/products/{product}/payments/{payment}', [MobileLeadShowController::class, 'destroyProductPayment'])->name('products.payments.destroy');

    // ── Quotations ───────────────────────────────────────────────────────────
    Route::post('/{lead}/quotations',                     [MobileLeadShowController::class, 'storeQuotation'])->name('quotations.store');
    Route::patch('/{lead}/quotations/{quotation}/status', [MobileLeadShowController::class, 'updateQuotationStatus'])->name('quotations.status');
    Route::delete('/{lead}/quotations/{quotation}',       [MobileLeadShowController::class, 'destroyQuotation'])->name('quotations.destroy');
    Route::put('/{lead}/quotations/{quotation}/approve', [MobileLeadShowController::class, 'approveQuotation'])
        ->name('quotations.approve');
    Route::post('/{lead}/quotations/{quotation}/send-email', [MobileLeadShowController::class, 'sendQuotationEmail'])
        ->name('quotations.send-email');
});


/*
|--------------------------------------------------------------------------
| Mobile Reports Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('mobile/reports/crm')->name('mobile.reports.crm.')->group(function () {

    Route::get('/leads-summary', [ReportApiController::class, 'leadsSummaryApi'])->name('index');
    Route::get('/payment-collection', [ReportApiController::class, 'paymentCollectionApi'])->name('payment-collection');
    Route::get('/product-wise', [ReportApiController::class, 'productWiseApi'])->name('product-wise');
    Route::get('/revenue-comparison', [ReportApiController::class, 'revenueComparisonApi'])->name('revenue-comparison');
    Route::get('/branch-comparison', [ReportApiController::class, 'branchComparisonApi'])->name('branch-comparison');
    Route::get('/smm', [ReportApiController::class, 'smmReportApi'])->name('smm');
});
