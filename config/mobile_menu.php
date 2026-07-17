<?php

return [

    // ── CRM ──────────────────────────────────────────────────────────────
    'crm' => [
        // Mirrors sidebar.blade.php's implicit CRM state: anyone who isn't
        // restricted to HRMS attendance-only self-service. (There's no
        // explicit "canAccessCrmModule()" on User — this is the closest
        // faithful equivalent using the one self-service flag the web
        // sidebar itself keys off; flag if a stricter rule exists.)
        'gate' => ['forbid_method' => 'isHrmsAttendanceOnlyUser'],
        'items' => [
            ['key' => 'dashboard',      'label' => 'Dashboard',      'section' => 'CRM', 'order' => 10],
            ['key' => 'leads',          'label' => 'Leads',          'section' => 'CRM', 'order' => 20, 'permission' => 'leads.menuview'],
            ['key' => 'quotations',     'label' => 'Quotations',     'section' => 'CRM', 'order' => 30, 'permission' => 'quotations.menuview'],
            ['key' => 'lead_products',  'label' => 'Lead Products',  'section' => 'CRM', 'order' => 40, 'permission' => 'leads.view'],
            ['key' => 'call_updates',   'label' => 'Call Updates',   'section' => 'CRM', 'order' => 50, 'permission' => 'call_updates.menuview'],
            ['key' => 'price_requests', 'label' => 'Price Requests', 'section' => 'CRM', 'order' => 60, 'permission' => 'price_requests.menuview'],
            ['key' => 'reports',        'label' => 'Reports',        'section' => 'CRM', 'order' => 70],
            ['key' => 'ovp_module',            'label' => 'OVP Module',            'section' => 'OVP & PRODUCTION', 'order' => 80, 'permission' => 'ovp_module.menuview'],
            ['key' => 'production_approvals',  'label' => 'Production Approvals',  'section' => 'OVP & PRODUCTION', 'order' => 90, 'permission' => 'production_approval_module.menuview'],
            ['key' => 'profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 100],
        ],
    ],

    // ── Projects (promoted from a nested CRM group to its own module) ──────
    'projects' => [
        // Same canAccessProjectsModule() gate as before — exact reuse.
        'gate' => ['require_method' => 'canAccessProjectsModule'],
        'items' => [
            ['key' => 'projects.dashboard', 'label' => 'Dashboard',          'section' => 'PROJECTS', 'order' => 10],
            ['key' => 'projects.list',      'label' => 'Projects Details',   'section' => 'PROJECTS', 'order' => 20],
            ['key' => 'projects.timesheets','label' => 'Timesheets',         'section' => 'PROJECTS', 'order' => 30],
            ['key' => 'projects.my_accounts', 'label' => 'My Accounts', 'section' => 'PROJECTS', 'order' => 40,
                'require_any_method' => ['belongsToDesigningDepartment', 'belongsToDigitalMarketingDepartment']],
            ['key' => 'projects.designing_dashboard', 'label' => 'Designing Dashboard', 'section' => 'PROJECTS', 'order' => 50,
                'require_any_method' => ['belongsToDesigningDepartment', 'belongsToDigitalMarketingDepartment']],
            ['key' => 'projects.profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 60],
        ],
    ],

    // ── HRMS (NEW) ───────────────────────────────────────────────────────
    'hrms' => [
        'gate' => ['require_any_method' => [
            'belongsToHrDepartment', 'hasHrLikeRole', 'isHrmsAttendanceOnlyUser',
            'hasAdminLikeRole', 'isSuperAdmin', 'isCompanyAdmin',
        ]],
        'items' => [
            // Dashboard + Attendance: no forbid_method — matches sidebar.blade.php,
            // these two are the ONLY items visible to $hrmsSelfService users.
            ['key' => 'hrms.dashboard',  'label' => 'Dashboard',  'section' => 'HRMS', 'order' => 10, 'permission' => 'dashboard.view'],
            ['key' => 'hrms.attendance', 'label' => 'Attendance', 'section' => 'HRMS', 'order' => 20, 'permission' => 'attendance.menuview'],

            // Everything else: gated by !$hrmsSelfService in sidebar.blade.php.
            ['key' => 'hrms.employees', 'label' => 'Employees', 'section' => 'HRMS', 'order' => 30,
                'permission' => 'employees.menuview', 'forbid_method' => 'isHrmsAttendanceOnlyUser'],
            ['key' => 'hrms.interns', 'label' => 'Interns', 'section' => 'HRMS', 'order' => 40,
                'permission' => 'interns.menuview', 'forbid_method' => 'isHrmsAttendanceOnlyUser'],
            ['key' => 'hrms.assets', 'label' => 'Assets', 'section' => 'HRMS', 'order' => 50,
                'permission' => 'assets.menuview', 'forbid_method' => 'isHrmsAttendanceOnlyUser'],
            ['key' => 'hrms.holidays', 'label' => 'Holidays', 'section' => 'HRMS', 'order' => 60,
                'permission' => 'holiday_calendar.menuview', 'forbid_method' => 'isHrmsAttendanceOnlyUser'],
            ['key' => 'hrms.leave', 'label' => 'Leave Request', 'section' => 'HRMS', 'order' => 70,
                'permission' => 'leave_requests.menuview', 'forbid_method' => 'isHrmsAttendanceOnlyUser'],
            ['key' => 'hrms.permission', 'label' => 'Permission Request', 'section' => 'HRMS', 'order' => 80,
                'permission' => 'permission_requests.menuview', 'forbid_method' => 'isHrmsAttendanceOnlyUser'],
            ['key' => 'hrms.visitor', 'label' => 'Visitor Management', 'section' => 'HRMS', 'order' => 90,
                'permission' => 'visitor_management.menuview', 'forbid_method' => 'isHrmsAttendanceOnlyUser'],
            ['key' => 'hrms.facility', 'label' => 'Facility', 'section' => 'HRMS', 'order' => 100,
                'permission' => 'facility_management.menuview', 'forbid_method' => 'isHrmsAttendanceOnlyUser'],

            ['key' => 'hrms.profile', 'label' => 'Profile', 'section' => 'ACCOUNT', 'order' => 110],
        ],
    ],

];