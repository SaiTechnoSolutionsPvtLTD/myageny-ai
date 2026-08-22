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
            ['key' => 'lead_products',  'label' => 'Lead Products',  'section' => 'CRM', 'order' => 40, 'permission' => 'leads.view'],
            // Sits under "Leads" in the web sidebar (All Leads / Lead Products /
            // Add Lead / Pre Sales / Call Updates); mobile's CRM section is
            // already flat (lead_products/call_updates are siblings here too,
            // not nested), so this follows the same existing convention.
            ['key' => 'pre_sales',      'label' => 'Pre Sales',      'section' => 'CRM', 'order' => 45, 'permission' => 'pre_sales.menuview'],
            ['key' => 'call_updates',   'label' => 'Call Updates',   'section' => 'CRM', 'order' => 50, 'permission' => 'call_updates.menuview'],
            // Mobile mirror of the web sidebar's "Reminders & Tasks" item
            // (App\Http\Controllers\CrmTaskController::index, gated
            // `can:leads.view` there) — same permission here for parity.
            ['key' => 'reminders_tasks', 'label' => 'Reminders & Tasks', 'section' => 'CRM', 'order' => 55, 'permission' => 'leads.view'],
            ['key' => 'price_requests', 'label' => 'Price Requests', 'section' => 'CRM', 'order' => 60, 'permission' => 'price_requests.menuview', 'require_method' => 'allowsPriceRequests'],
            ['key' => 'reports',        'label' => 'Reports',        'section' => 'CRM', 'order' => 70],
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
            ['key' => 'cst_allocation', 'label' => 'CST Allocation', 'section' => 'CST', 'order' => 20, 'permission' => null],
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
            ['key' => 'projects.timesheets', 'label' => 'Timesheets',         'section' => 'PROJECTS', 'order' => 30],
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

    // ── HRMS (NEW) ───────────────────────────────────────────────────────
    'hrms' => [
        'label' => 'HRMS',
        'order' => 30,
        'gate' => null,
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
                'permission' => 'holiday_calendar.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
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
                ],
            ],
            [
                'key' => 'hrms.facility',
                'label' => 'Facility',
                'section' => 'HRMS',
                'order' => 100,
                'permission' => 'facility_management.menuview',
                'forbid_method' => 'isHrmsAttendanceOnlyUser'
            ],

            ['key' => 'hrms.profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 110],
        ],
    ],

];
