{{-- Sidebar - matches exact design from static HTML --}}
@php
    $isHrmsModule = request()->routeIs('hrms.dashboard')
        || request()->routeIs('hrms.masters.*')
        || request()->routeIs('employee-onboarding.*')
        || request()->routeIs('recruitment.*')
        || request()->routeIs('assets.*')
        || request()->routeIs('interns.*')
        || request()->routeIs('attendance.*')
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
        || request()->routeIs('settings.payroll.*')
        || request()->routeIs('settings.facility-titles.*')
        || request()->routeIs('settings.holiday-calendars.*');
    $hrmsSelfService = auth()->user()?->isHrmsAttendanceOnlyUser();
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
        @if($isHrmsModule)
        <div class="nav-section">
            <div class="nav-title">HRMS</div>
            <div class="nav-items">
                @can('dashboard.view')
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
                @endcan

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

                @if(! $hrmsSelfService && (auth()->user()?->belongsToHrDepartment() || auth()->user()?->hasHrLikeRole()))
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
                @can('leaverequest.menuview')
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
                @can('permissionrequest.menuview')
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
                <a href="{{ url('/dashboard') }}" class="nav-item {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}">
                    @if(request()->is('dashboard') || request()->is('/'))
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

                @can('price_requests.menuview')
                @if(auth()->user()->hasAnyRole(['super_admin', 'Super Admin', 'admin']))
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
                @endif
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

    <div class="user-profile">
        <div class="user-avatar-v">V</div>
        <div class="user-info">
            <span class="user-name">{{ Auth::user()->name }}</span>
            <span class="user-role">{{ Auth::user()->role_name }}</span>
        </div>
        <img src="{{ asset('images/42_3166.svg') }}" alt="Selector">

    </div>
 @include('layouts.logout_btn')
</aside>
