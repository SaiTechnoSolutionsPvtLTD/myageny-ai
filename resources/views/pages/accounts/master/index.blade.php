@extends('layouts.app')

@section('title', 'Accounts Master Hub - myAgenci.ai')

@push('styles')
<style>
.master-hub-page {
    min-height: 100%;
    padding: 28px 28px 60px 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 30%),
        linear-gradient(180deg, #f7f3ee 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}

.master-hub-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 28px;
    padding: 28px;
    border: 1px solid #e6e8ee;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff7f1 0%, #ffffff 58%, #f7fbff 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
    flex-wrap: wrap;
}

.master-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .6px;
    text-transform: uppercase;
    margin-bottom: 12px;
}

.master-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.2;
    color: #0f172a;
}

.master-subtitle {
    margin: 8px 0 0;
    max-width: 620px;
    font-size: 14px;
    line-height: 1.6;
    color: #64748b;
}

.master-glance {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.master-glance-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 14px;
    border: 1px solid #ece7e3;
    background: rgba(255, 255, 255, 0.95);
    color: #334155;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 2px 8px rgba(0,0,0,.02);
}

.master-glance-item i {
    font-size: 15px;
    color: #fe5f04;
}

/* Master Cards Grid - 4 Cards Per Row */
.master-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    width: 100%;
}

@media (max-width: 1400px) {
    .master-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 1024px) {
    .master-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
    .master-grid { grid-template-columns: 1fr; }
}

.master-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 240px;
    padding: 22px;
    border-radius: 18px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
    text-decoration: none;
    color: inherit;
    transition: all .25s ease;
}

.master-card:hover {
    transform: translateY(-5px);
    border-color: #fdba74;
    box-shadow: 0 18px 40px rgba(254, 95, 4, 0.12);
}

.master-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 20px;
}

.master-card-icon {
    width: 58px;
    height: 58px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(0,0,0,.06);
    transition: transform .2s ease;
}

.master-card:hover .master-card-icon {
    transform: scale(1.08);
}

.icon-orange {
    background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
    color: #ea580c;
    border: 1px solid #fed7aa;
}

.icon-blue {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

.icon-purple {
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
    color: #9333ea;
    border: 1px solid #e9d5ff;
}

.icon-emerald {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    color: #059669;
    border: 1px solid #a7f3d0;
}

.icon-teal {
    background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
    color: #0d9488;
    border: 1px solid #99f6e4;
}

.icon-indigo {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    color: #4f46e5;
    border: 1px solid #c7d2fe;
}

.icon-sky {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    color: #0284c7;
    border: 1px solid #bae6fd;
}

.icon-amber {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    color: #d97706;
    border: 1px solid #fde68a;
}

.icon-rose {
    background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
    color: #e11d48;
    border: 1px solid #fecdd3;
}

.master-card-badge {
    font-size: 11px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.master-card-title {
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}

.master-card-desc {
    font-size: 13px;
    line-height: 1.6;
    color: #64748b;
    margin-bottom: 24px;
}

.master-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 16px;
    border-top: 1px solid #f1f5f9;
    font-size: 13px;
    font-weight: 700;
}

.master-card-stats {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #475569;
    font-size: 12px;
}

.master-card-stats strong {
    color: #0f172a;
    font-size: 14px;
}

.master-card-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #fe5f04;
    font-weight: 800;
    font-size: 13px;
    transition: transform .15s ease;
}

.master-card:hover .master-card-action {
    transform: translateX(4px);
}
</style>
@endpush

@section('content')
<div class="master-hub-page">

    {{-- Hero Section --}}
    <div class="master-hub-hero">
        <div>
            <div class="master-kicker">
                <i class="bi bi-grid-1x2-fill"></i>
                ACCOUNTS MODULE • MASTER CONFIGURATION
            </div>
            <h1 class="master-title">Accounts Master Hub</h1>
            <p class="master-subtitle">
                Centralized master control for expense categorization, subcategory breakdown, ad accounts, and registered vendors. Select a card below to manage records and add new entries.
            </p>
        </div>

        <div class="master-glance">
            <div class="master-glance-item">
                <i class="bi bi-folder2-open"></i>
                <span><strong>{{ $categoryStats['total'] }}</strong> Expense Categories</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-diagram-3"></i>
                <span><strong>{{ $subcategoryStats['total'] }}</strong> Expense Subcats</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-headset" style="color:#0d9488;"></i>
                <span><strong>{{ $supportCategoryStats['total'] }}</strong> Support Categories</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-diagram-3-fill" style="color:#4f46e5;"></i>
                <span><strong>{{ $supportSubcategoryStats['total'] }}</strong> Support Subcats</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-wallet2" style="color:#9333ea;"></i>
                <span><strong>{{ $adAccountStats['total'] }}</strong> Ad Accounts</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-shop" style="color:#059669;"></i>
                <span><strong>{{ $vendorStats['total'] }}</strong> Vendors</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-box-seam" style="color:#0284c7;"></i>
                <span><strong>{{ $itemStats['total'] }}</strong> Items</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-people" style="color:#d97706;"></i>
                <span><strong>{{ $customerStats['total'] }}</strong> Customers</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-receipt-cutoff" style="color:#e11d48;"></i>
                <span>Invoice: <strong>{{ $invoiceSetting ? ($invoiceSetting->invoice_prefix ?: 'INV-') : 'Setup' }}</strong></span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-check-circle-fill" style="color:#16a34a;"></i>
                <span><strong>{{ $categoryStats['active'] + $subcategoryStats['active'] + $supportCategoryStats['active'] + $supportSubcategoryStats['active'] + $adAccountStats['active'] + $vendorStats['active'] + $itemStats['active'] + $customerStats['active'] }}</strong> Active Entries</span>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:14px 20px; border-radius:12px; font-size:13px; font-weight:600; margin-bottom:24px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- Cards Grid --}}
    <div class="master-grid">

        {{-- Card 1: Expense Category Master --}}
        <a href="{{ route('accounts.master.expense-categories.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-orange">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <span class="master-card-badge">Primary Master</span>
                </div>
                <div class="master-card-title">Expense Category</div>
                <div class="master-card-desc">
                    Define high-level expense groups (e.g., Office Expenses, Marketing & Ads, Travel & Conveyance, Software & Subscriptions).
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $categoryStats['total'] }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $categoryStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

        {{-- Card 2: Expense Subcategory Master --}}
        <a href="{{ route('accounts.master.expense-subcategories.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-blue">
                        <i class="bi bi-diagram-3"></i>
                    </div>
                    <span class="master-card-badge">Sub Master</span>
                </div>
                <div class="master-card-title">Expense Subcategory</div>
                <div class="master-card-desc">
                    Define itemized breakdown lines mapped under parent expense categories with custom identifiers, codes, and remarks.
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $subcategoryStats['total'] }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $subcategoryStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

        {{-- Card 3: Support Category Master --}}
        <a href="{{ route('accounts.master.support-categories.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-teal">
                        <i class="bi bi-headset"></i>
                    </div>
                    <span class="master-card-badge">Support Master</span>
                </div>
                <div class="master-card-title">Support Category</div>
                <div class="master-card-desc">
                    Define high-level support classifications, ticket groups, and service request categories with active/inactive availability.
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $supportCategoryStats['total'] }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $supportCategoryStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

        {{-- Card 4: Support Subcategory Master --}}
        <a href="{{ route('accounts.master.support-subcategories.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-indigo">
                        <i class="bi bi-diagram-3-fill"></i>
                    </div>
                    <span class="master-card-badge">Hierarchy Master</span>
                </div>
                <div class="master-card-title">Support Subcategory</div>
                <div class="master-card-desc">
                    Configure granular support subcategories with multi-tier, user-wise hierarchy escalation chains and approval levels.
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $supportSubcategoryStats['total'] }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $supportSubcategoryStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

        {{-- Card 5: Ad Account Master --}}
        <a href="{{ route('accounts.ad-accounts-master.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-purple">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <span class="master-card-badge">Ads Master</span>
                </div>
                <div class="master-card-title">Ad Account Master</div>
                <div class="master-card-desc">
                    Manage digital marketing ad accounts for Meta, Google Ads, LinkedIn & more with branch allocation, platform IDs, and status tracking.
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $adAccountStats['total'] }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $adAccountStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

        {{-- Card 6: Vendor Master --}}
        <a href="{{ route('accounts.master.vendors.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-emerald">
                        <i class="bi bi-shop"></i>
                    </div>
                    <span class="master-card-badge">Vendors Master</span>
                </div>
                <div class="master-card-title">Vendor Master</div>
                <div class="master-card-desc">
                    Register and manage supplier & vendor profiles, GST numbers, contact details, and bank settlement credentials.
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $vendorStats['total'] }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $vendorStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

        {{-- Card 7: Items Master --}}
        <a href="{{ route('accounts.master.items.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-sky">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <span class="master-card-badge">Catalog Master</span>
                </div>
                <div class="master-card-title">Items Master</div>
                <div class="master-card-desc">
                    Define and manage goods & services catalog items, HSN/SAC codes, standard rates, GST slabs, and remarks.
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $itemStats['total'] }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $itemStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

        {{-- Card 8: Customer Master --}}
        <a href="{{ route('accounts.master.customers.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-amber">
                        <i class="bi bi-people"></i>
                    </div>
                    <span class="master-card-badge">Client Master</span>
                </div>
                <div class="master-card-title">Customer Master</div>
                <div class="master-card-desc">
                    Manage client profiles, converted leads integration, billing & shipping addresses, GST/PAN numbers, and payment terms.
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $customerStats['total'] }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $customerStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

        {{-- Card 9: Invoice Settings --}}
        <a href="{{ route('accounts.master.invoice-settings.index') }}" class="master-card">
            <div>
                <div class="master-card-top">
                    <div class="master-card-icon icon-rose">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <span class="master-card-badge">Billing Setup</span>
                </div>
                <div class="master-card-title">Invoice Settings</div>
                <div class="master-card-desc">
                    Configure invoice number format, upload company logo & signature, enter company address, banking details & UPI, and set terms & conditions.
                </div>
            </div>

            <div class="master-card-footer">
                <div class="master-card-stats">
                    <strong>{{ $invoiceSetting ? 'Configured' : 'Setup' }}</strong>
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:{{ $invoiceSetting ? '#16a34a' : '#ea580c' }}; font-weight:700;">
                        {{ $invoiceSetting ? $invoiceSetting->formatInvoiceNumber() : 'INV-0001' }}
                    </span>
                </div>
                <div class="master-card-action">
                    Configure <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

    </div>

</div>
@endsection
