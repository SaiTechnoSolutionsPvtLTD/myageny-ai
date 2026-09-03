@extends('layouts.app')

@section('title', 'Settings - myAgenci.ai')

@push('styles')
<style>
.settings-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.10), transparent 30%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
}
.settings-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
    padding: 28px;
    border: 1px solid #e6e8ee;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
}
.settings-kicker {
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
.settings-kicker svg,
.settings-glance-item svg,
.settings-card-icon svg,
.settings-card-link svg {
    flex-shrink: 0;
}
.settings-title {
    margin: 0;
    font-size: 30px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.settings-subtitle {
    margin: 10px 0 0;
    max-width: 640px;
    font-size: 14px;
    line-height: 1.7;
    color: #6b7280;
}
.settings-glance {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}
.settings-glance-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid #ece7e3;
    background: rgba(255, 255, 255, 0.92);
    color: #4b5563;
    font-size: 12px;
    font-weight: 700;
}
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 18px;
}
.settings-card {
    position: relative;
    overflow: hidden;
    display: block;
    padding: 22px;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
    text-decoration: none;
    color: inherit;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.settings-card:hover {
    transform: translateY(-4px);
    border-color: #fed7aa;
    box-shadow: 0 18px 36px rgba(254, 95, 4, 0.12);
}
.settings-card::after {
    content: '';
    position: absolute;
    right: -28px;
    bottom: -28px;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    opacity: .15;
}
.settings-card.status::after { background: #f59e0b; }
.settings-card.source::after { background: #f97316; }
.settings-card.outcome::after { background: #ec4899; }
.settings-card.subcategory::after { background: #3b82f6; }
.settings-card.product-category::after { background: #8b5cf6; }
.settings-card.attribute::after { background: #14b8a6; }
.settings-card.quotation::after { background: #0ea5e9; }
.settings-card.facebook::after { background: #2563eb; }
.settings-card.branch::after { background: #0f766e; }
.settings-card.department::after { background: #059669; }
.settings-card.holiday::after { background: #ef4444; }
.settings-card.payroll::after { background: #16a34a; }
.settings-card.design::after  { background: #7c3aed; }
.settings-card-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.settings-kicker svg,
.settings-glance-item svg,
.settings-card-link svg {
    width: 16px;
    height: 16px;
}
.settings-card-icon svg {
    width: 24px;
    height: 24px;
}
.settings-card-icon.status {
    background: linear-gradient(135deg, #fff7ed, #fef3c7);
    color: #b45309;
}
.settings-card-icon.source {
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    color: #ea580c;
}
.settings-card-icon.outcome {
    background: linear-gradient(135deg, #fdf2f8, #fce7f3);
    color: #db2777;
}
.settings-card-icon.subcategory {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: #2563eb;
}
.settings-card-icon.product-category {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #7c3aed;
}
.settings-card-icon.attribute {
    background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
    color: #0f766e;
}
.settings-card-icon.quotation {
    background: linear-gradient(135deg, #eff6ff, #e0f2fe);
    color: #0369a1;
}
.settings-card-icon.facebook {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: #1d4ed8;
}
.settings-card-icon.branch {
    background: linear-gradient(135deg, #f0fdfa, #ccfbf1);
    color: #0f766e;
}
.settings-card-icon.department {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    color: #047857;
}
.settings-card-icon.holiday {
    background: linear-gradient(135deg, #fff1f2, #ffe4e6);
    color: #be123c;
}
.settings-card-icon.payroll {
    background: linear-gradient(135deg, #ecfdf5, #dcfce7);
    color: #15803d;
}
.settings-card-icon.design {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #7c3aed;
}
.settings-card-title {
    margin: 0 0 8px;
    font-size: 17px;
    font-weight: 800;
    color: #111827;
}
.settings-card-text {
    margin: 0;
    max-width: 280px;
    font-size: 13px;
    line-height: 1.7;
    color: #6b7280;
}
.settings-card-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    font-size: 12px;
    font-weight: 800;
    color: #111827;
}
@media (max-width: 768px) {
    .settings-page {
        padding: 18px;
    }
    .settings-hero {
        padding: 22px;
        flex-direction: column;
        align-items: flex-start;
    }
    .settings-title {
        font-size: 24px;
    }
}
</style>
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-hero">
        <div>
            <div class="settings-kicker">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" y1="6" x2="20" y2="6"/>
                    <line x1="4" y1="18" x2="20" y2="18"/>
                    <line x1="10" y1="6" x2="10" y2="18"/>
                    <circle cx="10" cy="6" r="2.5" fill="currentColor" stroke="none"/>
                    <circle cx="14" cy="18" r="2.5" fill="currentColor" stroke="none"/>
                </svg>
                Workspace Setup
            </div>
            <h2 class="settings-title">Settings</h2>
            <p class="settings-subtitle">Manage integrations, quotation templates, and other CRM settings. Configure master data in the Masters section.</p>
        </div>

        <div class="settings-glance">
            <div class="settings-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 3v5h5"/>
                </svg>
                Quotations
            </div>
            <div class="settings-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 8h3V3h-3a5 5 0 0 0-5 5v3H6v5h3v5h5v-5h3l1-5h-4v-2a1 1 0 0 1 1-1z"/>
                </svg>
                Integrations
            </div>
        </div>
    </div>

    <div class="settings-grid">

        @can('quotation_settings.menuview')
        <a href="/settings/quotation-setting" class="settings-card quotation">
            <div class="settings-card-icon quotation">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 3v5h5"/>
                    <line x1="9" y1="13" x2="15" y2="13"/>
                    <line x1="9" y1="17" x2="13" y2="17"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Quotation Settings</h4>
            <p class="settings-card-text">Control branding, numbering, company information, terms, and signatures used in generated quotations.</p>
            <span class="settings-card-link">
                Open Quotation Settings
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('expense_pipeline.menuview')
        <a href="{{ route('settings.expense-pipeline.index') }}" class="settings-card payroll">
            <div class="settings-card-icon payroll">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Expense Pipeline</h4>
            <p class="settings-card-text">Configure dynamic multi-stage role hierarchy pipelines for expense approvals across roles.</p>
            <span class="settings-card-link">
                Configure Expense Pipeline
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan


        @can('leave_hierarchy.menuview')
        <a href="{{ route('settings.leave-hierarchy.index') }}" class="settings-card attendance">
            <div class="settings-card-icon attendance">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Leave Hierarchy</h4>
            <p class="settings-card-text">Configure dynamic multi-stage role approval hierarchies for leave requests across organization roles.</p>
            <span class="settings-card-link">
                Configure Leave Hierarchy
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('field_customization.menuview')
        <a href="{{ url('/lead/form-customization') }}" class="settings-card facebook">
            <div class="settings-card-icon facebook">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 3v5h5"/>
                    <path d="M9 13h6"/>
                    <path d="M9 17h3"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Field Customization</h4>
            <p class="settings-card-text">Customize lead form fields to capture the specific information your business needs for lead intake.</p>
            <span class="settings-card-link">
                Open Field Customization
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('facebook_integration.menuview')
        <a href="/settings/facebook-integration" class="settings-card facebook">
            <div class="settings-card-icon facebook">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 8h3V3h-3a5 5 0 0 0-5 5v3H6v5h3v5h5v-5h3l1-5h-4v-2a1 1 0 0 1 1-1z"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Facebook Integration</h4>
            <p class="settings-card-text">Connect campaign flows and manage mapped lead intake from Facebook forms inside your CRM.</p>
            <span class="settings-card-link">
                Open Facebook Integration
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('branches.menuview')
        <a href="{{ route('settings.branches.index') }}" class="settings-card branch">
            <div class="settings-card-icon branch">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l7-4 7 4v14"></path>
                    <path d="M9 10h6"></path>
                    <path d="M9 14h6"></path>
                </svg>
            </div>
            <h4 class="settings-card-title">Branches</h4>
            <p class="settings-card-text">Create company-wise branches and keep one default branch ready even when no branch was added manually.</p>
            <span class="settings-card-link">
                Open Branches
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('payroll_settings.menuview')
        <a href="{{ route('settings.payroll.index') }}" class="settings-card payroll">
            <div class="settings-card-icon payroll">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="6" width="18" height="12" rx="2"/>
                    <path d="M7 10h10"/>
                    <path d="M7 14h6"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Payroll Settings</h4>
            <p class="settings-card-text">Set default PF and ESI percentages, salary limit rules, and monthly payroll calculation defaults.</p>
            <span class="settings-card-link">
                Open Payroll Settings
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('design_settings.menuview')
        <a href="{{ route('settings.design-settings.index') }}" class="settings-card design">
            <div class="settings-card-icon design">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Design Settings</h4>
            <p class="settings-card-text">Set per-user daily production targets for Designing department members. Configure targets for Posters, Videos, Logos, Flyers, and more.</p>
            <span class="settings-card-link">
                Open Design Settings
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('sales_targets.menuview')
        <a href="{{ route('settings.sales-targets.index') }}" class="settings-card outcome">
            <div class="settings-card-icon outcome">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Target Allocation</h4>
            <p class="settings-card-text">Configure target revenue amounts for Sales department members to compare target vs actual collection.</p>
            <span class="settings-card-link">
                Open Target Allocation
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('lead_reallocation.menuview')
        <a href="{{ route('settings.lead-reallocation.index') }}" class="settings-card branch">
            <div class="settings-card-icon branch">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M13 5H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h7"></path>
                    <path d="M13 12H3"></path>
                    <path d="M13 9l3-3"></path>
                    <path d="M16 6l3 3"></path>
                    <path d="M19 3v6"></path>
                </svg>
            </div>
            <h4 class="settings-card-title">Lead Reallocation</h4>
            <p class="settings-card-text">Reassign leads and their products from one user to another. Useful for managing team changes and workload distribution.</p>
            <span class="settings-card-link">
                Open Lead Reallocation
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('lead_import.menuview')
        <a href="{{ route('settings.lead-import.index') }}" class="settings-card source">
            <div class="settings-card-icon source">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="12" y1="18" x2="12" y2="12"></line>
                    <polyline points="9 15 12 12 15 15"></polyline>
                </svg>
            </div>
            <h4 class="settings-card-title">Lead Import</h4>
            <p class="settings-card-text">Import leads in bulk from Excel or CSV files with custom field mapping, product assignment, and sales/presales team allocation.</p>
            <span class="settings-card-link">
                Open Lead Import
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        <a href="{{ route('settings.activity-logs.index') }}" class="settings-card quotation">
            <div class="settings-card-icon quotation">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h4 class="settings-card-title">Activity Logs</h4>
            <p class="settings-card-text">Track full user actions from login to logout, lead-wise timeline, system interactions, and audit trail.</p>
            <span class="settings-card-link">
                Open Activity Logs
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
    </div>
</div>
@endsection
