<?php

return [

    // ── CRM ──────────────────────────────────────────────────────────────
    'crm' => [
        'label' => 'CRM',
        'order' => 10,
        'gate' => ['require_method' => 'canAccessMobileCrmModule'],
        'items' => [
            ['key' => 'dashboard',      'label' => 'Dashboard',      'section' => 'CRM', 'order' => 10],
            ['key' => 'leads',          'label' => 'Leads',          'section' => 'CRM', 'order' => 20, 'permission' => 'leads.menuview'],
            ['key' => 'quotations',     'label' => 'Quotations',     'section' => 'CRM', 'order' => 30, 'permission' => 'quotations.menuview'],
            ['key' => 'lead_products',  'label' => 'Lead Products',  'section' => 'CRM', 'order' => 40, 'permission' => 'lead_products.menuview', 'permission_aliases' => ['lead_products.view', 'lead_products', 'leads.view', 'leads.menuview']],
            // Sits under "Leads" in the web sidebar (All Leads / Lead Products /
            // Add Lead / Pre Sales / Call Updates); mobile's CRM section is
            // already flat (lead_products/call_updates are siblings here too,
            // not nested), so this follows the same existing convention.
            ['key' => 'pre_sales',      'label' => 'Pre Sales',      'section' => 'CRM', 'order' => 45, 'permission' => 'pre_sales.menuview'],
            ['key' => 'call_updates',   'label' => 'Call Updates',   'section' => 'CRM', 'order' => 50, 'permission' => 'call_updates.menuview'],
            // Mobile mirror of the web sidebar's "Reminders & Tasks" item
            // (App\Http\Controllers\CrmTaskController::index, gated
            // `can:leads.view` there) — same permission here for parity.
            ['key' => 'reminders_tasks', 'label' => 'Reminder & Tasks', 'section' => 'CRM', 'order' => 55, 'permission' => 'reminders_tasks.menuview', 'permission_aliases' => ['tasks.menuview', 'tasks.view', 'leads.view', 'leads.menuview']],
            ['key' => 'day_closing',    'label' => 'Day Closing',    'section' => 'CRM', 'order' => 58, 'permission' => 'day_closing.menuview', 'permission_aliases' => ['day_closing.view', 'day_closing', 'sales_day_closing.menuview', 'leads.view', 'leads.menuview', 'call_updates.menuview'], 'forbid_method' => 'isCustomerSuccessOnlyUser'],
            ['key' => 'price_requests', 'label' => 'Price Requests', 'section' => 'CRM', 'order' => 60, 'permission' => 'price_requests.menuview', 'require_method' => 'allowsPriceRequests'],
            ['key' => 'cst_allocation', 'label' => 'CST Allocation', 'section' => 'CRM', 'order' => 65, 'permission' => 'cst_allocation.menuview'],
            ['key' => 'cst_day_closing', 'label' => 'CST Day Closing', 'section' => 'CRM', 'order' => 66, 'permission' => 'cst_day_closing.menuview', 'permission_aliases' => ['cst_day_closing.view', 'cst_day_closing', 'cst.day_closing', 'cst_allocation.menuview', 'cst_allocation.view']],
            ['key' => 'reports',        'label' => 'Reports',        'section' => 'CRM', 'order' => 70, 'permission' => 'reports.menuview'],
            ['key' => 'ovp_module',            'label' => 'OVP Module',            'section' => 'OVP & PRODUCTION', 'order' => 80, 'permission' => 'ovp_module.menuview'],
            ['key' => 'production_approvals',  'label' => 'Production Approvals',  'section' => 'OVP & PRODUCTION', 'order' => 90, 'permission' => 'production_approval_module.menuview'],
            ['key' => 'notifications',   'label' => 'Notifications',  'section' => 'CRM', 'order' => 15],
            ['key' => 'profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 100],
        ],
    ],

    'cst' => [
        'label' => 'CST',
        'order' => 40,
        'gate' => ['require_method' => 'canAccessMobileCstModule'],
        'items' => [
            ['key' => 'cst.dashboard', 'label' => 'Dashboard', 'section' => 'CST', 'order' => 10],
            ['key' => 'cst_allocation', 'label' => 'CST Allocation', 'section' => 'CST', 'order' => 20, 'permission' => 'cst_allocation.menuview'],
            ['key' => 'cst_day_closing', 'label' => 'CST Day Closing', 'section' => 'CST', 'order' => 25, 'permission' => 'cst_day_closing.menuview', 'permission_aliases' => ['cst_day_closing.view', 'cst_day_closing', 'cst.day_closing', 'cst_allocation.menuview', 'cst_allocation.view']],
            ['key' => 'cst.profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 30],
        ],
    ],

    // ── Projects (promoted from a nested CRM group to its own module) ──────
    'projects' => [
        'label' => 'Production',
        'order' => 20,
        'gate' => ['require_method' => 'canAccessMobileProjectsModule'],
        'items' => [
            ['key' => 'projects.dashboard', 'label' => 'Dashboard',          'section' => 'PROJECTS', 'order' => 10],
            ['key' => 'projects.list',      'label' => 'Projects Details',   'section' => 'PROJECTS', 'order' => 20],
            [
                'key' => 'projects.smm_sheet',
                'label' => 'Organic Campaigns',
                'section' => 'PROJECTS',
                'order' => 21,
                'require_any_method' => ['belongsToDesigningDepartment', 'belongsToDigitalMarketingDepartment', 'hasAdminLikeRole', 'canViewProjectsDashboardSwitcher'],
            ],
            [
                'key' => 'projects.campaigns',
                'label' => 'Paid Campaigns',
                'section' => 'PROJECTS',
                'order' => 22,
                'require_any_method' => ['belongsToDigitalMarketingDepartment', 'hasAdminLikeRole'],
            ],
            // Mobile mirror of the web sidebar's "Tasks" link under Projects
            // (App\Http\Controllers\ProductionTaskController, gated
            // @can('tasks.menuview') there) — same permission here.
            ['key' => 'projects.tasks',      'label' => 'Tasks',              'section' => 'PROJECTS', 'order' => 25, 'permission' => 'tasks.menuview'],
            ['key' => 'projects.timesheets', 'label' => 'Timesheets',         'section' => 'PROJECTS', 'order' => 30, 'permission' => 'timesheets.menuview'],
            [
                'key' => 'projects.my_accounts',
                'label' => 'My Accounts',
                'section' => 'PROJECTS',
                'order' => 40,
                'require_any_method' => ['belongsToDesigningDepartment', 'belongsToDigitalMarketingDepartment']
            ],
            [
                'key' => 'projects.designing_dashboard',
                'label' => 'Designing Dashboard',
                'section' => 'PROJECTS',
                'order' => 50,
                'require_any_method' => ['belongsToDesigningDepartment', 'belongsToDigitalMarketingDepartment']
            ],
            // Mobile mirror of the web sidebar's Testing Department dashboard
            // (auto-selected by ProjectController::dashboard() for these same
            // users) — see ProjectApiController::testingDashboard().
            [
                'key' => 'projects.testing_dashboard',
                'label' => 'Testing Dashboard',
                'section' => 'PROJECTS',
                'order' => 55,
                'require_any_method' => ['belongsToTestingDepartment', 'hasTestingLikeRole']
            ],
            ['key' => 'projects.profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 60],
        ],
    ],

    // ── Face Attendance ─────────────────────────────────────────────────────
    // New self-service module (ticket: "New Module – Face Recognition
    // Attendance"). gate => null, same as 'hrms' below, so this tile is
    // universally visible to every employee regardless of Spatie
    // modules_menu.* permissions — see MenuService::accessibleModules()'s
    // hardcoded allow-list, which now includes this key for the same reason
    // it already includes 'hrms': self-service employees never get explicit
    // modules_menu.* grants but still need to be able to mark attendance.
    // No sub-items: the module is a single Mark Attendance flow reached
    // directly from the Modules-tab tile, not a per-item drawer menu.
    // 'face_attendance' => [
    //     'label' => 'Face Attendance',
    //     'order' => 35,
    //     'gate' => null,
    //     'items' => [
    //         ['key' => 'face_attendance.mark', 'label' => 'Mark Attendance', 'section' => 'FACE ATTENDANCE', 'order' => 10],
    //     ],
    // ],

    // ── HRMS (NEW) ───────────────────────────────────────────────────────
    'hrms' => [
        'label' => 'HRMS',
        'order' => 30,
        'gate' => ['require_method' => 'canAccessHrmsModule'],
        'items' => [
            // Dashboard + Attendance: no forbid_method — matches sidebar.blade.php,
            // these two are the ONLY items visible to $hrmsSelfService users.
            ['key' => 'hrms.dashboard',  'label' => 'Dashboard',  'section' => 'HRMS', 'order' => 10, 'permission' => 'dashboard.view'],
            ['key' => 'hrms.attendance', 'label' => 'Attendance', 'section' => 'HRMS', 'order' => 20, 'permission' => 'attendance.menuview'],

            // Everything else: gated by !$hrmsSelfService in sidebar.blade.php.
            [
                'key' => 'hrms.employees',
                'label' => 'Employees',
                'section' => 'HRMS',
                'order' => 30,
                'permission' => 'employees.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            [
                'key' => 'hrms.interns',
                'label' => 'Interns',
                'section' => 'HRMS',
                'order' => 40,
                'permission' => 'interns.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            [
                'key' => 'hrms.assets',
                'label' => 'Assets',
                'section' => 'HRMS',
                'order' => 50,
                'permission' => 'assets.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            [
                'key' => 'hrms.holidays',
                'label' => 'Holidays',
                'section' => 'HRMS',
                'order' => 60,
                'permission' => null,
            ],
            [
                'key' => 'hrms.leave',
                'label' => 'Leave Request',
                'section' => 'HRMS',
                'order' => 70,
                'permission' => 'leave_requests.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            [
                'key' => 'hrms.permission',
                'label' => 'Permission Request',
                'section' => 'HRMS',
                'order' => 80,
                'permission' => 'permission_requests.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            [
                'key' => 'hrms.visitor',
                'label' => 'Visitor Management',
                'section' => 'HRMS',
                'order' => 90,
                'permission' => 'visitor_management.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            // Announcements: universally accessible to all HRMS users, matching
            // sidebar.blade.php line 535.
            [
                'key' => 'hrms.announcements',
                'label' => 'Announcements',
                'section' => 'HRMS',
                'order' => 94,
                'permission' => null,
            ],
            // Matches sidebar.blade.php's @can('expense_request.menuview') gate
            // exactly — same permission key, so the mobile menu and web
            // sidebar always show/hide this item in lockstep.
            [
                'key' => 'hrms.expense_request',
                'label' => 'Expense Request',
                'section' => 'HRMS',
                'order' => 95,
                'permission' => 'expense_request.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            // Web's sidebar.blade.php shows the OD Requests nav link to
            // anyone who isn't hrms-self-service-only (`@if(! $hrmsSelfService)`)
            // — no granular @can() gate exists for it there. Mobile now has
            // its own od_request.menuview permission (mobile-only, see
            // laravel-mobile-od-request.md / the
            // add_od_request_menuview_permission migration), mirroring the
            // 'permission' pattern used by every sibling HRMS item below.
            // $user->can() is safe against an unrecognized/unassigned
            // permission name here — AppServiceProvider's Gate::before calls
            // User::hasCrmPermission(), a plain collection lookup that
            // returns false rather than throwing — so this can never break
            // the wider /mobile/menu response. forbid_method is kept
            // alongside it to match the same web condition the sibling items
            // already enforce.
            [
                'key' => 'hrms.od_request',
                'label' => 'OD Requests',
                'section' => 'HRMS',
                'order' => 96,
                'permission' => 'od_request.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            // Matches sidebar.blade.php's `@if(! $hrmsSelfService) @can('recruitment.menuview')`
            // gate exactly — recruitment.menuview already exists as a real
            // Spatie permission (see App\Models\Permission's permission map),
            // so this needed no new migration, unlike od_request.menuview.
            [
                'key' => 'hrms.payroll',
                'label' => 'Payroll',
                'section' => 'HRMS',
                'order' => 97,
                'permission' => 'payroll.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser',
            ],
            [
                'key' => 'hrms.recruitment',
                'label' => 'Recruitment',
                'section' => 'HRMS',
                'order' => 98,
                'permission' => 'recruitment.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            // No web-sidebar equivalent (this workflow is mobile-only), so
            // there's no existing Spatie permission to mirror — gated the
            // same way OutsideOfficeApprovalApiController::canManage() gates
            // the API itself, via require_any_method instead of 'permission'.
            [
                'key' => 'hrms.outside_office_approval',
                'label' => 'Outside Office Approval',
                'section' => 'HRMS',
                'order' => 97,
                'require_any_method' => [
                    'isSystemAdmin',
                    'belongsToHrDepartment',
                    'hasHrLikeRole',
                    'isCompanyAdmin',
                    'isBranchAdmin',
                    'isBranchManager',
                ],
            ],
            // HR/Admin-only, same gate pattern as outside_office_approval
            // above — employees must never see or reach this item (ticket
            // section 2: "Employees should not have access to register or
            // update their own face"). See FaceRegistrationApiController.
            // [
            //     'key' => 'hrms.face_registration',
            //     'label' => 'Face Registration',
            //     'section' => 'HRMS',
            //     'order' => 96,
            //     'require_any_method' => [
            //         'isSystemAdmin',
            //         'belongsToHrDepartment',
            //         'hasHrLikeRole',
            //         'isCompanyAdmin',
            //         'isBranchAdmin',
            //     ],
            // ],
            // Matches sidebar.blade.php's `@if(! $hrmsSelfService) @can('petty_cash.menuview')`
            // gate exactly — petty_cash.menuview already exists as a real
            // Spatie permission (it already gates the live web page), so
            // this needed no new migration, same precedent as
            // recruitment.menuview above.
            [
                'key' => 'hrms.petty_cash',
                'label' => 'Petty Cash',
                'section' => 'HRMS',
                'order' => 99,
                'permission' => 'petty_cash.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            [
                'key' => 'hrms.timesheet_lop',
                'label' => 'Timesheet LOP',
                'section' => 'HRMS',
                'order' => 99,
                'require_method' => 'canAccessTimesheetLop',
                'forbid_method' => 'isHrmsAttendanceOnlyUser',
            ],
            [
                'key' => 'hrms.facility',
                'label' => 'Facility',
                'section' => 'HRMS',
                'order' => 100,
                'permission' => 'facility_management.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],
            [
                'key' => 'hrms.house_keeping',
                'label' => 'House Keeping',
                'section' => 'HRMS',
                'order' => 105,
                'permission' => 'house_keeping.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser',
            ],

            ['key' => 'hrms.profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 110],
        ],
    ],

    'accounts' => [
        'label' => 'Accounts',
        'order' => 50,
        'gate' => ['require_method' => 'canAccessMobileAccountsModule'],
        'items' => [
            [
                'key' => 'accounts.dashboard',
                'label' => 'Dashboard',
                'section' => 'ACCOUNTS',
                'order' => 10,
                'permission' => 'accounts_dashboard.menuview',
                'permission_aliases' => ['accounts_dashboard.view', 'accounts_dashboard'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.ad_budget_clients',
                'label' => 'For Clients',
                'section' => 'AD BUDGET',
                'order' => 20,
                'permission' => 'ad_budget.clients',
                'permission_aliases' => ['accounts_ad_budget.clients', 'accounts_ad_budget.view', 'accounts_ad_budget.menuview', 'ad_budget.menuview'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.ad_budget_partners',
                'label' => 'For Partners',
                'section' => 'AD BUDGET',
                'order' => 30,
                'permission' => 'ad_budget.partners',
                'permission_aliases' => ['accounts_ad_budget.partners', 'accounts_ad_budget.view', 'accounts_ad_budget.menuview', 'ad_budget.menuview'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.ad_spend',
                'label' => 'Ad Spend',
                'section' => 'AD BUDGET',
                'order' => 40,
                'permission' => 'accounts_ad_budget.view',
                'permission_aliases' => ['accounts_ad_budget.menuview', 'ad_budget.menuview', 'accounts_ad_spend.menuview', 'accounts_ad_budget_spend.menuview'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.allocation_vs_spent',
                'label' => 'Allocation Vs Spend',
                'section' => 'AD BUDGET',
                'order' => 50,
                'permission' => 'accounts_ad_budget.view',
                'permission_aliases' => ['accounts_ad_budget.menuview', 'ad_budget.menuview', 'accounts_allocation_vs_spent.menuview', 'accounts_ad_budget_allocation.menuview'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.domains_hosting',
                'label' => 'Domains & Hosting',
                'section' => 'ACCOUNTS',
                'order' => 60,
                'permission' => 'accounts_domains_hosting.menuview',
                'permission_aliases' => ['accounts_domains_hosting.view', 'domains_hosting.menuview'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.quotations',
                'label' => 'Quotations',
                'section' => 'ACCOUNTS',
                'order' => 60.5,
                'permission' => 'accounts_quotations.menuview',
                'permission_aliases' => ['accounts_quotations.view', 'quotations.menuview', 'quotations.view'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.sales_invoices',
                'label' => 'Invoices',
                'section' => 'SALES',
                'order' => 61,
                'permission' => 'accounts_invoices.menuview',
                'permission_aliases' => ['accounts_invoices.view', 'accounts_sales.menuview', 'accounts_sales.view'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.sales_payments',
                'label' => 'Payments',
                'section' => 'SALES',
                'order' => 62,
                'permission' => 'accounts_payments.menuview',
                'permission_aliases' => ['accounts_payments.view', 'accounts_sales.menuview', 'accounts_sales.view'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.sales_credit_notes',
                'label' => 'Credit Notes',
                'section' => 'SALES',
                'order' => 63,
                'permission' => 'accounts_credit_notes.menuview',
                'permission_aliases' => ['accounts_credit_notes.view', 'accounts_sales.menuview', 'accounts_sales.view'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.create_invoice',
                'label' => 'New Invoice',
                'section' => 'SALES',
                'order' => 64,
                'permission' => 'accounts_invoices.create',
                'permission_aliases' => ['accounts_sales.create'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.purchase_orders',
                'label' => 'PO Orders',
                'section' => 'PO ORDERS',
                'order' => 64,
                'permission' => 'accounts_purchase_orders.menuview',
                'permission_aliases' => ['accounts_purchase_orders.view'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.create_purchase_order',
                'label' => 'New PO',
                'section' => 'PO ORDERS',
                'order' => 65,
                'permission' => 'accounts_purchase_orders.create',
                'permission_aliases' => ['accounts_purchase_orders.edit'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.expenses',
                'label' => 'Expense List',
                'section' => 'EXPENSE',
                'order' => 65,
                'permission' => 'accounts_expenses.menuview',
                'permission_aliases' => ['accounts_expenses.view'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.add_expense',
                'label' => 'Add Expense',
                'section' => 'EXPENSE',
                'order' => 66,
                'permission' => 'accounts_expenses.create',
                'permission_aliases' => ['accounts_expenses.edit'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.expense_pivot',
                'label' => 'Expense Pivot',
                'section' => 'EXPENSE',
                'order' => 67,
                'permission' => 'accounts_expenses.pivot',
                'permission_aliases' => ['accounts_expenses.menuview', 'accounts_expenses.view'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.reports',
                'label' => 'Reports',
                'section' => 'REPORTS',
                'order' => 68,
                'permission' => 'accounts_reports.menuview',
                'permission_aliases' => ['accounts_reports.view'],
                'keep_static_label' => true,
            ],
            [
                'key' => 'accounts.ad_accounts_master',
                'label' => 'Ad Accounts Master',
                'section' => 'ACCOUNTS',
                'order' => 70,
                'permission' => 'accounts_master.menuview',
                'permission_aliases' => ['accounts_master.view', 'ad_accounts_master.manage'],
                'keep_static_label' => true,
            ],
            ['key' => 'accounts.profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 80],
        ],
    ],

];