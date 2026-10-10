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

/* Master Cards Grid */
.master-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
    max-width: 900px;
}

.master-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 250px;
    padding: 28px;
    border-radius: 20px;
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
                Centralized master control for expense categorization and subcategory breakdown. Select a card below to manage records and add new entries.
            </p>
        </div>

        <div class="master-glance">
            <div class="master-glance-item">
                <i class="bi bi-folder2-open"></i>
                <span><strong>{{ $categoryStats['total'] }}</strong> Categories</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-diagram-3"></i>
                <span><strong>{{ $subcategoryStats['total'] }}</strong> Subcategories</span>
            </div>
            <div class="master-glance-item">
                <i class="bi bi-check-circle-fill" style="color:#16a34a;"></i>
                <span><strong>{{ $categoryStats['active'] + $subcategoryStats['active'] }}</strong> Active Entries</span>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:14px 20px; border-radius:12px; font-size:13px; font-weight:600; margin-bottom:24px; max-width:900px;">
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
                    <strong>{{ $categoryStats['total'] }}</strong> Categories
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $categoryStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage Categories <i class="bi bi-arrow-right"></i>
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
                    <strong>{{ $subcategoryStats['total'] }}</strong> Subcategories
                    <span style="color:#94a3b8;">•</span>
                    <span style="color:#16a34a; font-weight:700;">{{ $subcategoryStats['active'] }} Active</span>
                </div>
                <div class="master-card-action">
                    Manage Subcategories <i class="bi bi-arrow-right"></i>
                </div>
            </div>
        </a>

    </div>

</div>
@endsection
