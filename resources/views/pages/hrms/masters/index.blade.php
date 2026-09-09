@extends('layouts.app')

@section('title', 'HRMS Masters - myAgenci.ai')

@push('styles')
<style>
.masters-page {
    min-height: 100%;
    padding: 28px 28px 60px 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 30%),
        linear-gradient(180deg, #f7f3ee 0%, #f3f5f8 100%);
}
.masters-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
    padding: 28px;
    border: 1px solid #e6e8ee;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff7f1 0%, #ffffff 58%, #f7fbff 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
}
.masters-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.masters-kicker svg,
.masters-glance-item svg,
.masters-card-icon svg,
.masters-card-link svg {
    flex-shrink: 0;
}
.masters-title {
    margin: 0;
    font-size: 30px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.masters-subtitle {
    margin: 10px 0 0;
    max-width: 640px;
    font-size: 14px;
    line-height: 1.7;
    color: #6b7280;
}
.masters-glance {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.masters-glance-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 14px;
    border: 1px solid #ece7e3;
    background: rgba(255, 255, 255, 0.92);
    color: #4b5563;
    font-size: 12px;
    font-weight: 700;
}
.masters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}
.masters-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 220px;
    padding: 24px;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
    text-decoration: none;
    color: inherit;
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
}
.masters-card:hover {
    transform: translateY(-4px);
    border-color: #fed7aa;
    box-shadow: 0 18px 36px rgba(254, 95, 4, 0.12);
}
.masters-card::after {
    content: '';
    position: absolute;
    right: -28px;
    bottom: -28px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    opacity: .12;
    pointer-events: none;
}
.masters-card.department::after { background: #059669; }
.masters-card.leave-type::after { background: #0f766e; }
.masters-card.facility-title::after { background: #f97316; }
.masters-card.asset-category::after { background: #2563eb; }
.masters-card.house-keeping::after { background: #0ea5a4; }
.masters-card.payroll::after { background: #0f766e; }
.masters-card.attendance-rule::after { background: #7c3aed; }

.masters-card-body {
    display: flex;
    flex-direction: column;
}
.masters-card-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.masters-kicker svg,
.masters-glance-item svg,
.masters-card-link svg {
    width: 16px;
    height: 16px;
}
.masters-card-icon svg {
    width: 24px;
    height: 24px;
}
.masters-card-icon.department {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    color: #047857;
}
.masters-card-icon.leave-type {
    background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
    color: #0f766e;
}
.masters-card-icon.facility-title {
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    color: #c2410c;
}
.masters-card-icon.asset-category {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: #1d4ed8;
}
.masters-card-icon.house-keeping {
    background: linear-gradient(135deg, #ecfeff, #cffafe);
    color: #0f766e;
}
.masters-card-icon.payroll {
    background: linear-gradient(135deg, #ecfeff, #cffafe);
    color: #0f766e;
}
.masters-card-icon.attendance-rule {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #6d28d9;
}
.masters-card-title {
    margin: 0 0 8px;
    font-size: 17px;
    font-weight: 800;
    color: #111827;
}
.masters-card-text {
    margin: 0;
    font-size: 13px;
    line-height: 1.6;
    color: #6b7280;
}
.masters-card-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 20px;
    font-size: 13px;
    font-weight: 700;
    color: #ea580c;
    transition: gap 0.15s ease;
}
.masters-card:hover .masters-card-link {
    gap: 12px;
}
@media (max-width: 768px) {
    .masters-page {
        padding: 18px;
    }
    .masters-hero {
        padding: 22px;
        flex-direction: column;
        align-items: flex-start;
    }
    .masters-title {
        font-size: 24px;
    }
    .masters-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endpush

@section('content')
<div class="masters-page">
    <div class="masters-hero">
        <div>
            <div class="masters-kicker">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="4" width="16" height="16" rx="3"/>
                    <path d="M8 12h8"/>
                    <path d="M12 8v8"/>
                </svg>
                HRMS Configuration
            </div>
            <h2 class="masters-title">HRMS Masters</h2>
            <p class="masters-subtitle">Manage the HR structure and employee-focused master data used across onboarding, attendance, leave, permission, payroll, and facility workflows.</p>
        </div>

        <div class="masters-glance">
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="10" cy="7" r="3"></circle>
                </svg>
                Department Setup
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <path d="M3 10h18"></path>
                </svg>
                Leave Rules
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 7h16"></path>
                    <path d="M7 7v12"></path>
                    <path d="M17 7v12"></path>
                </svg>
                Facility Labels
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="16" rx="3"/>
                    <path d="M8 9h8"/>
                    <path d="M8 13h5"/>
                </svg>
                Asset Categories
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 7h16"/>
                    <path d="M7 7v12"/>
                    <path d="M17 7v12"/>
                    <path d="M4 12h16"/>
                </svg>
                Cleaning Sheet
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                    <path d="M7 10h10"></path>
                    <path d="M7 14h6"></path>
                </svg>
                Payroll Defaults
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"></circle>
                    <path d="M12 7v5l3 2"></path>
                </svg>
                Attendance Rules
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                Expense Categories
            </div>
        </div>
    </div>

    <div class="masters-grid">
        @can('departments.menuview')
        <a href="{{ route('hrms.masters.departments.index') }}" class="masters-card department">
            <div class="masters-card-body">
                <div class="masters-card-icon department">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="3"/>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 4.13a3 3 0 0 1 0 5.74"/>
                    </svg>
                </div>
                <h4 class="masters-card-title">Departments</h4>
                <p class="masters-card-text">Maintain department master data for HR structure, onboarding alignment, and employee organization.</p>
            </div>
            <span class="masters-card-link">
                Manage Departments
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('settings.manage')
        <a href="{{ route('hrms.masters.leave-types.index') }}" class="masters-card leave-type">
            <div class="masters-card-body">
                <div class="masters-card-icon leave-type">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 2v4"/>
                        <path d="M16 2v4"/>
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M3 10h18"/>
                        <path d="M8 15h4"/>
                        <path d="M8 18h8"/>
                    </svg>
                </div>
                <h4 class="masters-card-title">Leave Types</h4>
                <p class="masters-card-text">Maintain leave categories such as casual, sick, earned, or unpaid leave for HR workflows.</p>
            </div>
            <span class="masters-card-link">
                Manage Leave Types
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('settings.manage')
        <a href="{{ route('hrms.masters.facility-titles.index') }}" class="masters-card facility-title">
            <div class="masters-card-body">
                <div class="masters-card-icon facility-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 7h16"/>
                        <path d="M7 7v12"/>
                        <path d="M17 7v12"/>
                        <path d="M5 19h14"/>
                        <path d="M9 4h6l1 3H8l1-3z"/>
                    </svg>
                </div>
                <h4 class="masters-card-title">Facility Titles</h4>
                <p class="masters-card-text">Maintain the selectable titles used while creating facility management entries.</p>
            </div>
            <span class="masters-card-link">
                Manage Facility Titles
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('settings.manage')
        <a href="{{ route('hrms.masters.asset-categories.index') }}" class="masters-card asset-category">
            <div class="masters-card-body">
                <div class="masters-card-icon asset-category">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="3"/>
                        <path d="M7 9h10"/>
                        <path d="M7 13h6"/>
                        <path d="M16.5 16.5h.01"/>
                    </svg>
                </div>
                <h4 class="masters-card-title">Asset Categories</h4>
                <p class="masters-card-text">Maintain the selectable category master used while creating and filtering asset entries.</p>
            </div>
            <span class="masters-card-link">
                Manage Asset Categories
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('settings.manage')
        <a href="{{ route('hrms.masters.expense-categories.index') }}" class="masters-card facility-title">
            <div class="masters-card-body">
                <div class="masters-card-icon facility-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <h4 class="masters-card-title">Expense Categories</h4>
                <p class="masters-card-text">Maintain expense categories used in petty cash entries and expense claim workflows.</p>
            </div>
            <span class="masters-card-link">
                Manage Expense Categories
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        <a href="{{ route('house-keeping.index') }}" class="masters-card house-keeping">
            <div class="masters-card-body">
                <div class="masters-card-icon house-keeping">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 7h16"/>
                        <path d="M7 7v12"/>
                        <path d="M17 7v12"/>
                        <path d="M4 12h16"/>
                        <path d="M10 16h4"/>
                    </svg>
                </div>
                <h4 class="masters-card-title">House Keeping</h4>
                <p class="masters-card-text">Maintain cleaning categories, work lists, and review the monthly house keeping cleaning sheet.</p>
            </div>
            <span class="masters-card-link">
                Open House Keeping
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>

        @can('settings.manage')
        <a href="{{ route('hrms.masters.payroll.index') }}#leave-settings" class="masters-card attendance-rule">
            <div class="masters-card-body">
                <div class="masters-card-icon attendance-rule">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M8 2v4"/>
                        <path d="M16 2v4"/>
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M3 10h18"/>
                        <path d="M8 15h8"/>
                    </svg>
                </div>
                <h4 class="masters-card-title">Leave Settings</h4>
                <p class="masters-card-text">Set monthly paid leave allowance so payroll can separate payable leave days from LOP automatically.</p>
            </div>
            <span class="masters-card-link">
                Open Leave Settings
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('settings.manage')
        <a href="{{ route('hrms.masters.payroll.index') }}#permission-settings" class="masters-card attendance-rule">
            <div class="masters-card-body">
                <div class="masters-card-icon attendance-rule">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="M12 7v5l3 2"></path>
                    </svg>
                </div>
                <h4 class="masters-card-title">Permission Settings</h4>
                <p class="masters-card-text">Set monthly permission count and daily permission hours so payroll can convert excess permission usage into LOP.</p>
            </div>
            <span class="masters-card-link">
                Open Permission Settings
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('settings.manage')
        <a href="{{ route('hrms.masters.payroll.index') }}#grace-time-settings" class="masters-card attendance-rule">
            <div class="masters-card-body">
                <div class="masters-card-icon attendance-rule">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 6v6l4 2"></path>
                        <circle cx="12" cy="12" r="9"></circle>
                    </svg>
                </div>
                <h4 class="masters-card-title">Grace Time Settings</h4>
                <p class="masters-card-text">Set the login grace cutoff so late attendance can consume permission allowance automatically during payroll.</p>
            </div>
            <span class="masters-card-link">
                Open Grace Time Settings
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('settings.manage')
        <a href="{{ route('hrms.masters.payroll.index') }}" class="masters-card payroll">
            <div class="masters-card-body">
                <div class="masters-card-icon payroll">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                        <path d="M7 10h10"></path>
                        <path d="M7 14h6"></path>
                    </svg>
                </div>
                <h4 class="masters-card-title">Payroll Settings</h4>
                <p class="masters-card-text">Manage default PF, ESI, payroll deduction, leave, permission, and grace-time settings used across HRMS payroll workflow.</p>
            </div>
            <span class="masters-card-link">
                Open Payroll Settings
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan
    </div>
</div>
@endsection
