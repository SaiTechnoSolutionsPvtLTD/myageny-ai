{{-- Sidebar - matches exact design from static HTML --}}
@php
    $isProjectsModule = request()->routeIs('projects.dashboard')
        || request()->routeIs('projects.index')
        || request()->routeIs('projects.show')
        || request()->routeIs('projects.allocate')
        || request()->routeIs('projects.employee-allocate')
        || request()->routeIs('projects.timesheets')
        || request()->routeIs('projects.my-accounts')
        || request()->routeIs('projects.my-accounts.show');
    $isHrmsModule = request()->routeIs('hrms.dashboard')
        || request()->routeIs('hrms.petty-cash.*')
        || request()->routeIs('hrms.masters.*')
        || request()->routeIs('employee-onboarding.*')
        || request()->routeIs('recruitment.*')
        || request()->routeIs('assets.*')
        || request()->routeIs('interns.*')
        || request()->routeIs('attendance.*')
        || request()->routeIs('house-keeping.*')
        || request()->routeIs('payroll.*')
        || request()->routeIs('hrms-announcements.*')
        || request()->routeIs('leave-requests.*')
        || request()->routeIs('permission-requests.*')
        || request()->routeIs('visitor-management.*')
        || request()->routeIs('dynamic-forms.*')
        || request()->routeIs('facility-management.*')
        || request()->routeIs('settings.departments.*')
        || request()->routeIs('settings.leave-types.*')
        || request()->routeIs('settings.asset-categories.*')
        || request()->routeIs('settings.house-keeping-categories.*')
        || request()->routeIs('settings.house-keeping-works.*')
        || request()->routeIs('settings.payroll.*')
        || request()->routeIs('settings.facility-titles.*')
        || request()->routeIs('settings.holiday-calendars.*')
        || request()->routeIs('support.*');
    $hrmsSelfService = auth()->user()?->isHrmsAttendanceOnlyUser();
    $canAccessProjectsModule = auth()->user()?->canAccessProjectsModule();
    $isDesigningDashboardActive = auth()->user()?->belongsToDesigningDepartment()
        || ($isDesigningDashboard ?? false)
        || (auth()->user()?->canViewProjectsDashboardSwitcher() && session('selected_dashboard_type') === 'design');

    // OVP Module & Production Approvals Sidebar Counts
    $ovpNewCount = 0;
    $prodApprovalPendingCount = 0;

    if (auth()->check()) {
        $currentUser = auth()->user();

        if ($currentUser->can('ovp_module.menuview')) {
            $ovpTlRoleKeys = ['customer_support_team_tl'];
            $ovpExecutiveRoleKeys = ['customer_support_team_executive'];

            $normalizeRole = function(string $value): string {
                $value = \Illuminate\Support\Str::contains($value, '__') ? \Illuminate\Support\Str::afterLast($value, '__') : $value;
                return \Illuminate\Support\Str::of($value)
                    ->lower()
                    ->replace('&', 'and')
                    ->replace(['-', ' '], '_')
                    ->replaceMatches('/[^a-z0-9_]+/', '')
                    ->replaceMatches('/_+/', '_')
                    ->trim('_')
                    ->value();
            };

            $hasRoleKey = function($u, array $keys) use ($normalizeRole): bool {
                $normalizedKeys = collect($keys)->map(fn (string $key) => $normalizeRole($key))->filter()->unique();
                return $u->resolvedRoles(withDepartment: true)->contains(function ($role) use ($normalizedKeys, $normalizeRole) {
                    return $normalizedKeys->contains($normalizeRole((string) $role->name))
                        || $normalizedKeys->contains($normalizeRole((string) ($role->display_name ?? '')));
                });
            };

            $isTlScoped = ! $currentUser->hasAdminLikeRole() && ($hasRoleKey($currentUser, $ovpTlRoleKeys) || $currentUser->hasTlLikeRole());
            $isExecutiveScoped = ! $currentUser->hasAdminLikeRole() && ! $isTlScoped && ($hasRoleKey($currentUser, $ovpExecutiveRoleKeys) || $currentUser->hasExecutiveLikeRole());

            $ovpQuery = \App\Models\ProductionInitiation::query()
                ->whereIn('status', ['ovp_pending', 'initiated']);

            if ($isExecutiveScoped) {
                $ovpQuery->where('ovp_allocated_to', $currentUser->id);
            }

            $threeDaysAgo = \Illuminate\Support\Carbon::now()->subDays(3);
            $ovpNewCount = $ovpQuery->where('created_at', '>=', $threeDaysAgo)->count();
        }

        if ($currentUser->can('production_approval_module.menuview')) {
            $prodApprovalPendingCount = \App\Models\ProductionInitiation::query()
                ->whereIn('status', ['approval', 'approved'])
                ->where('production_approval_status', 'pending')
                ->count();
        }
    }
@endphp

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            {{--  <div class="logo-icon">
                <img src="{{ asset('images/my_agenci_logo.png') }}" alt="Logo" class="logo-img">
            </div>
            <span class="logo-text">myAgenci.ai</span>  --}}
            <img src="{{ asset('images/my_agenci_logo.png') }}" alt="Logo" class="logo-img">
            {{--  <img src="{{ asset('images/42_3060.svg') }}" alt="Collapse" class="collapse-icon">  --}}
        </div>

        <div class="sidebar-accent"></div>
    </div>

    <nav class="sidebar-nav">
        @if($isProjectsModule)
        <div class="nav-section">
            <div class="nav-title">PROJECTS</div>
            <div class="nav-items">
                @if($canAccessProjectsModule)
                @php
                    $dashboardUrl = route('projects.dashboard');
                    if (auth()->user()?->canViewProjectsDashboardSwitcher()) {
                        $dashboardUrl .= $isDesigningDashboardActive ? '?dashboard_type=design' : '?dashboard_type=production';
                    }
                @endphp
                <a href="{{ $dashboardUrl }}" class="nav-item {{ request()->routeIs('projects.dashboard') ? 'active' : '' }}">
                    @if(request()->routeIs('projects.dashboard'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="8" height="8" rx="2"></rect>
                            <rect x="13" y="3" width="8" height="5" rx="2"></rect>
                            <rect x="13" y="10" width="8" height="11" rx="2"></rect>
                            <rect x="3" y="13" width="8" height="8" rx="2"></rect>
                        </svg>
                        <span>Dashboard</span>
                    </div>
                </a>

                @if($isDesigningDashboardActive || auth()->user()?->belongsToDigitalMarketingDepartment())
                <a href="{{ route('projects.my-accounts') }}" class="nav-item {{ request()->routeIs('projects.my-accounts') || request()->routeIs('projects.my-accounts.show') ? 'active' : '' }}">
                    @if(request()->routeIs('projects.my-accounts') || request()->routeIs('projects.my-accounts.show'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>My Accounts</span>
                    </div>
                </a>
                @endif

                @if(!$isDesigningDashboardActive && !auth()->user()?->belongsToDigitalMarketingDepartment())
                <a href="{{ route('projects.index') }}" class="nav-item {{ request()->routeIs('projects.index') || request()->routeIs('projects.show') || request()->routeIs('projects.allocate') || request()->routeIs('projects.employee-allocate') ? 'active' : '' }}">
                    @if(request()->routeIs('projects.index') || request()->routeIs('projects.show') || request()->routeIs('projects.allocate') || request()->routeIs('projects.employee-allocate'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h18"></path>
                            <path d="M6 3h12l1 4H5l1-4z"></path>
                            <path d="M5 11h14v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8z"></path>
                            <path d="M10 15h4"></path>
                        </svg>
                        <span>All Projects</span>
                    </div>
                </a>
                @endif

                <a href="{{ route('projects.timesheets') }}" class="nav-item {{ request()->routeIs('projects.timesheets') ? 'active' : '' }}">
                    @if(request()->routeIs('projects.timesheets'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <path d="M3 10h18"></path>
                            <path d="M8 14h.01"></path>
                            <path d="M12 14h.01"></path>
                            <path d="M16 14h.01"></path>
                        </svg>
                        <span>Timesheets</span>
                    </div>
                </a>
                @endif
            </div>
        </div>
        @elseif($isHrmsModule)
        <div class="nav-section">
            <div class="nav-title">HRMS</div>
            <div class="nav-items">
                @if(auth()->user()?->can('dashboard.view') || auth()->user()?->can('dashboard.menuview') || auth()->user()?->can('modules_menu.hrms') || auth()->user()?->isCompanyAdmin() || auth()->user()?->isSystemAdmin())
                <a href="{{ route('hrms.dashboard') }}" class="nav-item {{ request()->routeIs('hrms.dashboard') ? 'active' : '' }}">
                    @if(request()->routeIs('hrms.dashboard'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="8" height="8" rx="2"></rect>
                            <rect x="13" y="3" width="8" height="5" rx="2"></rect>
                            <rect x="13" y="10" width="8" height="11" rx="2"></rect>
                            <rect x="3" y="13" width="8" height="8" rx="2"></rect>
                        </svg>
                        <span>Dashboard</span>
                    </div>
                </a>
                @endif

                @if(! $hrmsSelfService)
                @can('masters.menuview')
                 <a href="{{ route('hrms.masters.index') }}" class="nav-item {{ request()->routeIs('hrms.masters.*') || request()->routeIs('settings.departments.*') || request()->routeIs('settings.leave-types.*') || request()->routeIs('settings.asset-categories.*') || request()->routeIs('settings.payroll.*') || request()->routeIs('settings.facility-titles.*') ? 'active' : '' }}">
                    @if(request()->routeIs('hrms.masters.*') || request()->routeIs('settings.departments.*') || request()->routeIs('settings.leave-types.*') || request()->routeIs('settings.asset-categories.*') || request()->routeIs('settings.payroll.*') || request()->routeIs('settings.facility-titles.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"></path>
                            <path d="M12 12l8-4.5"></path>
                            <path d="M12 12v9"></path>
                            <path d="M12 12L4 7.5"></path>
                        </svg>
                        <span>Masters</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('employees.menuview')
                <a href="{{ route('employee-onboarding.index') }}" class="nav-item {{ request()->routeIs('employee-onboarding.*') ? 'active' : '' }}">
                    @if(request()->routeIs('employee-onboarding.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="10" cy="7" r="4"></circle>
                            <path d="M20 8v6"></path>
                            <path d="M23 11h-6"></path>
                        </svg>
                        <span>Employees</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('recruitment.menuview')
                <a href="{{ route('recruitment.index') }}" class="nav-item {{ request()->routeIs('recruitment.*') ? 'active' : '' }}">
                    @if(request()->routeIs('recruitment.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9.5" cy="7" r="3.5"></circle>
                            <path d="M17 8h4"></path>
                            <path d="M19 6v4"></path>
                            <path d="M17 16h4"></path>
                        </svg>
                        <span>Recruitment</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('interns.menuview')
                <a href="{{ route('interns.index') }}" class="nav-item {{ request()->routeIs('interns.*') ? 'active' : '' }}">
                    @if(request()->routeIs('interns.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"></path>
                            <path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"></path>
                        </svg>
                        <span>Interns</span>
                    </div>
                </a>
                @endcan
                @endif

                @can('attendance.menuview')
                <a href="{{ route('attendance.index') }}" class="nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                    @if(request()->routeIs('attendance.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <path d="M3 10h18"></path>
                            <path d="M8 14h.01"></path>
                            <path d="M12 14h.01"></path>
                            <path d="M16 14h.01"></path>
                            <path d="M8 18h.01"></path>
                            <path d="M12 18h.01"></path>
                        </svg>
                        <span>Attendance</span>
                    </div>
                </a>
                @endcan

                @if(! $hrmsSelfService)
                @can('house_keeping.menuview')
                <a href="javascript:void(0)"
                   class="nav-item has-dropdown {{ (request()->routeIs('house-keeping.*') || request()->routeIs('settings.house-keeping-categories.*') || request()->routeIs('settings.house-keeping-works.*')) ? 'active open' : '' }}"
                   onclick="toggleDropdown(this)">

                    @if(request()->routeIs('house-keeping.*') || request()->routeIs('settings.house-keeping-categories.*') || request()->routeIs('settings.house-keeping-works.*'))
                        <div class="active-indicator"></div>
                    @endif

                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18"></path>
                            <path d="M7 6v14"></path>
                            <path d="M17 6v14"></path>
                            <path d="M3 12h18"></path>
                            <path d="M10 16h4"></path>
                        </svg>
                        <span>House Keeping</span>
                        <img src="{{ asset('images/42_3081.svg') }}" alt="Expand" class="chevron">
                    </div>
                </a>

                <div class="submenu {{ (request()->routeIs('house-keeping.*') || request()->routeIs('settings.house-keeping-categories.*') || request()->routeIs('settings.house-keeping-works.*')) ? 'show' : '' }}">
                    <a href="{{ route('house-keeping.index') }}" class="submenu-item {{ request()->routeIs('house-keeping.index') ? 'active' : '' }}">
                        Cleaning Sheet
                    </a>
                    <a href="{{ route('house-keeping.employees.index') }}" class="submenu-item {{ request()->routeIs('house-keeping.employees.*') ? 'active' : '' }}">
                        Employee Entry
                    </a>
                    <a href="{{ route('house-keeping.attendances.index') }}" class="submenu-item {{ request()->routeIs('house-keeping.attendances.*') ? 'active' : '' }}">
                        Attendance
                    </a>
                </div>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('payroll.menuview')
                <a href="{{ route('payroll.index') }}" class="nav-item {{ request()->routeIs('payroll.*') ? 'active' : '' }}">
                    @if(request()->routeIs('payroll.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                            <path d="M7 10h10"></path>
                            <path d="M7 14h6"></path>
                        </svg>
                        <span>Payroll</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('petty_cash.menuview')
                <a href="{{ route('hrms.petty-cash.index') }}" class="nav-item {{ request()->routeIs('hrms.petty-cash.*') ? 'active' : '' }}">
                    @if(request()->routeIs('hrms.petty-cash.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <line x1="2" y1="10" x2="22" y2="10"></line>
                            <circle cx="12" cy="15" r="2"></circle>
                        </svg>
                        <span>Petty Cash</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('announcements.menuview')
                <a href="{{ route('hrms-announcements.index') }}" class="nav-item {{ request()->routeIs('hrms-announcements.*') ? 'active' : '' }}">
                    @if(request()->routeIs('hrms-announcements.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        <span>Announcements</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('leave_requests.menuview')
                <a href="{{ route('leave-requests.index') }}" class="nav-item {{ request()->routeIs('leave-requests.*') ? 'active' : '' }}">
                    @if(request()->routeIs('leave-requests.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <path d="M3 10h18"></path>
                            <path d="m9 16 2 2 4-5"></path>
                        </svg>
                        <span>Leave Requests</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('permission_requests.menuview')
                <a href="{{ route('permission-requests.index') }}" class="nav-item {{ request()->routeIs('permission-requests.*') ? 'active' : '' }}">
                    @if(request()->routeIs('permission-requests.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>
                        </svg>
                        <span>Permission Requests</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('visitor_management.menuview')
                <a href="{{ route('visitor-management.index') }}" class="nav-item {{ request()->routeIs('visitor-management.*') ? 'active' : '' }}">
                    @if(request()->routeIs('visitor-management.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9.5" cy="7" r="3"></circle>
                            <path d="M17 11h4"></path>
                            <path d="M19 9v4"></path>
                        </svg>
                        <span>Visitor Management</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('dynamic_forms.menuview')
                <a href="{{ route('dynamic-forms.index') }}" class="nav-item {{ request()->routeIs('dynamic-forms.*') ? 'active' : '' }}">
                    @if(request()->routeIs('dynamic-forms.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M8 6h13"></path>
                            <path d="M8 12h13"></path>
                            <path d="M8 18h13"></path>
                            <path d="M3 6h.01"></path>
                            <path d="M3 12h.01"></path>
                            <path d="M3 18h.01"></path>
                        </svg>
                        <span>Form Builder</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('facility_management.menuview')
                <a href="{{ route('facility-management.index') }}" class="nav-item {{ request()->routeIs('facility-management.*') ? 'active' : '' }}">
                    @if(request()->routeIs('facility-management.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9.5" cy="7" r="3"></circle>
                            <path d="M17 11h4"></path>
                            <path d="M19 9v4"></path>
                        </svg>
                        <span>Facility Management</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('assets.menuview')
                <a href="{{ route('assets.index') }}" class="nav-item {{ request()->routeIs('assets.*') ? 'active' : '' }}">
                    @if(request()->routeIs('assets.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <path d="m3.3 7 8.7 5 8.7-5"></path>
                            <path d="M12 22V12"></path>
                        </svg>
                        <span>Assets</span>
                    </div>
                </a>
                @endcan
                @endif

                @if(! $hrmsSelfService)
                @can('holiday_calendar.menuview')
                <a href="{{ route('settings.holiday-calendars.index') }}" class="nav-item {{ request()->routeIs('settings.holiday-calendars.*') ? 'active' : '' }}">
                    @if(request()->routeIs('settings.holiday-calendars.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>Holiday Calendar</span>
                    </div>
                </a>
                @endcan
                @endif

            </div>
        </div>
        @else
        {{-- CRM --}}
        <div class="nav-section">
            <div class="nav-title">CRM</div>
            <div class="nav-items">
                <a href="{{ route('dashboard.admin') }}" class="nav-item {{ request()->routeIs('dashboard.admin') || request()->is('dashboard') || request()->is('/') ? 'active' : '' }}">
                    @if(request()->routeIs('dashboard.admin') || request()->is('dashboard') || request()->is('/'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="8" height="8" rx="2"></rect>
                            <rect x="13" y="3" width="8" height="5" rx="2"></rect>
                            <rect x="13" y="10" width="8" height="11" rx="2"></rect>
                            <rect x="3" y="13" width="8" height="8" rx="2"></rect>
                        </svg>
                        <span>Dashboard</span>
                    </div>
                </a>

                @if($hrmsSelfService)
                <a href="{{ route('attendance.index') }}" class="nav-item {{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                    @if(request()->routeIs('attendance.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="8" height="8" rx="2"></rect>
                            <rect x="13" y="3" width="8" height="5" rx="2"></rect>
                            <rect x="13" y="10" width="8" height="11" rx="2"></rect>
                            <rect x="3" y="13" width="8" height="8" rx="2"></rect>
                        </svg>
                        <span>Attendance</span>
                    </div>
                </a>
                @endif

                 <a href="{{ route('masters.index') }}" class="nav-item {{ request()->is('masters') || request()->is('masters/*') ? 'active' : '' }}">
                    @if(request()->is('masters') || request()->is('masters/*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"></path>
                            <path d="M12 12l8-4.5"></path>
                            <path d="M12 12v9"></path>
                            <path d="M12 12L4 7.5"></path>
                        </svg>
                        <span>Masters</span>
                    </div>
                </a>

                @can('leads.menuview')
    <a href="javascript:void(0)"
   class="nav-item has-dropdown {{ request()->is('lead*') ? 'active open' : '' }}"
   onclick="toggleDropdown(this)">

    @if(request()->is('lead*'))
        <div class="active-indicator"></div>
    @endif

    <div class="nav-content">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="10" cy="7" r="4"></circle>
            <path d="M20 8v6"></path>
            <path d="M23 11h-6"></path>
        </svg>
        <span>Leads</span>
        <img src="{{ asset('images/42_3081.svg') }}" alt="Expand" class="chevron">
    </div>
</a>
@endcan

<!-- Dropdown Menu -->
<div class="submenu {{ request()->is('lead*') ? 'show' : 'show' }}">
     @can('leads.menuview')
    <a href="{{ url('/leads') }}" class="submenu-item {{ request()->is('leads') ? 'active' : '' }}">
        All Leads
    </a>
    @endcan

    @can('leads.view')
    <a href="{{ route('leads.products.index') }}" class="submenu-item {{ request()->is('leads/products') ? 'active' : '' }}">
        Lead Products
    </a>
    @endcan

     @can('leads.create')
    <a href="{{ url('/leads/create') }}" class="submenu-item {{ request()->is('leads/create') ? 'active' : '' }}">
        Add Lead
    </a>
    @endcan

    @can('call_updates.menuview')
    <a href="{{ route('leads.calls.index') }}" class="submenu-item {{ request()->is('leads/call-updates') ? 'active' : '' }}">
        Call Updates
    </a>
    @endcan
</div>
                @can('quotations.menuview')
                <a href="{{ url('/quotations') }}" class="nav-item {{ request()->is('quotations') || request()->is('/') ? 'active' : '' }}">
                    @if(request()->is('quotations') || request()->is('/'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                            <path d="M14 3v6h6"></path>
                            <line x1="8" y1="13" x2="16" y2="13"></line>
                            <line x1="8" y1="17" x2="14" y2="17"></line>
                        </svg>
                        <span>Quotations</span>
                    </div>
                </a>
                @endcan

                @can('reports.menuview')
                <a href="{{ route('reports.crm.index') }}" class="nav-item {{ request()->routeIs('reports.crm.*') ? 'active' : '' }}">
                    @if(request()->routeIs('reports.crm.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19h16"></path>
                            <path d="M7 16V10"></path>
                            <path d="M12 16V5"></path>
                            <path d="M17 16v-8"></path>
                        </svg>
                        <span>Reports</span>
                    </div>
                </a>
                @endcan

                @can('price_requests.menuview')

                <a href="{{ route('lead-price-requests.index') }}" class="nav-item {{ request()->routeIs('lead-price-requests.*') ? 'active' : '' }}">
                    @if(request()->routeIs('lead-price-requests.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3v12"></path>
                            <path d="m8 11 4 4 4-4"></path>
                            <path d="M5 21h14"></path>
                        </svg>
                        <span>Price Requests</span>
                    </div>
                </a>

                @endcan

                @can('cst_allocation.menuview')
                <a href="{{ route('cst-allocation.index') }}" class="nav-item {{ request()->routeIs('cst-allocation.*') ? 'active' : '' }}">
                    @if(request()->routeIs('cst-allocation.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>CST Allocation</span>
                    </div>
                </a>
                @endcan

                {{--  @if($canAccessProjectsModule)
                <a href="{{ route('projects.dashboard') }}" class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                    @if(request()->routeIs('projects.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h18"></path>
                            <path d="M6 3h12l1 4H5l1-4z"></path>
                            <path d="M5 11h14v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8z"></path>
                            <path d="M10 15h4"></path>
                        </svg>
                        <span>Projects Dashboard</span>
                    </div>
                </a>
                @endif  --}}

                @can('ovp_module.menuview')
                <a href="{{ route('ovp-module.index') }}" class="nav-item {{ request()->routeIs('ovp-module.*') ? 'active' : '' }}">
                    @if(request()->routeIs('ovp-module.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 7h16"></path>
                            <path d="M4 12h10"></path>
                            <path d="M4 17h7"></path>
                            <path d="M17 10l3 3-3 3"></path>
                            <path d="M14 13h6"></path>
                        </svg>
                        <span>OVP Module</span>
                        @if(isset($ovpNewCount) && $ovpNewCount > 0)
                            <span class="nav-badge" style="margin-left: auto; background-color: #fe5f04; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 10px; line-height: 1;">{{ $ovpNewCount }}</span>
                        @endif
                    </div>
                </a>
                @endcan

                @can('production_approval_module.menuview')
                <a href="{{ route('production-approvals.index') }}" class="nav-item {{ request()->routeIs('production-approvals.*') ? 'active' : '' }}">
                    @if(request()->routeIs('production-approvals.*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 7h16"></path>
                            <path d="M4 12h10"></path>
                            <path d="M4 17h7"></path>
                            <path d="M17 10l3 3-3 3"></path>
                            <path d="M14 13h6"></path>
                        </svg>
                        <span>Production Approvals</span>
                        @if(isset($prodApprovalPendingCount) && $prodApprovalPendingCount > 0)
                            <span class="nav-badge" style="margin-left: auto; background-color: #fe5f04; color: #fff; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 10px; line-height: 1;">{{ $prodApprovalPendingCount }}</span>
                        @endif
                    </div>
                </a>
                @endcan

                @can('settings.menuview')
                <a href="{{ url('/settings') }}" class="nav-item {{ request()->is('settings') || request()->is('settings/*') ? 'active' : '' }}">
                    @if(request()->is('settings') || request()->is('settings/*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        <span>Settings</span>
                    </div>
                </a>
                @endcan

                @can('authentication.menuview')
                <a href="{{ route('auth.index') }}" class="nav-item {{ request()->is('authentications') || request()->is('authentications/*') ? 'active' : '' }}">
                    @if(request()->is('authentications') || request()->is('authentications/*'))
                        <div class="active-indicator"></div>
                    @endif
                    <div class="nav-content">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l7 4v6c0 5-3.5 9.74-7 10-3.5-.26-7-5-7-10V6l7-4z"></path>
                            <path d="M9 12l2 2 4-4"></path>
                        </svg>
                        <span>Authentications</span>
                    </div>
                </a>
                @endcan

            </div>
        </div>
        @endif

    </nav>

    <div class="sidebar-footer">
        {{-- Support Option (Visible to everyone) --}}
        <a href="{{ route('support.index') }}" class="support-card {{ request()->routeIs('support.*') ? 'active' : '' }}">
            <div class="support-icon-wrapper">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
            <span>Support Portal</span>
        </a>

        {{-- User Profile Card --}}
        <div class="user-profile-card">
            <div class="user-profile-details">
                <div class="user-avatar-v">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    <span class="user-status-dot"></span>
                </div>
                <div class="user-info">
                    <span class="user-name" title="{{ Auth::user()->name }}">{{ Auth::user()->name }}</span>
                    <span class="user-role-badge" title="{{ ucwords(str_replace('_', ' ', Auth::user()->role_name)) }}">{{ ucwords(str_replace('_', ' ', Auth::user()->role_name)) }}</span>
                </div>
            </div>
            <div class="user-profile-actions">
                <!-- Sign Out Action -->
                <button type="button" class="profile-action-btn logout-btn" onclick="confirmLogout()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Sign Out</span>
                </button>
            </div>
        </div>
    </div>

    @include('layouts.logout_btn')
</aside>
