@extends('layouts.app')

@section('title', 'Masters - myAgenci.ai')

@push('styles')
<style>
.masters-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.10), transparent 30%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
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
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
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
    padding: 10px 12px;
    border-radius: 14px;
    border: 1px solid #ece7e3;
    background: rgba(255, 255, 255, 0.92);
    color: #4b5563;
    font-size: 12px;
    font-weight: 700;
}
.masters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 18px;
}
.masters-card {
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
    opacity: .15;
}
.masters-card.status::after { background: #f59e0b; }
.masters-card.source::after { background: #f97316; }
.masters-card.outcome::after { background: #ec4899; }
.masters-card.subcategory::after { background: #3b82f6; }
.masters-card.product-category::after { background: #8b5cf6; }
.masters-card.product-subcategory::after { background: #06b6d4; }
.masters-card.product::after { background: #10b981; }
.masters-card.department::after { background: #059669; }
.masters-card.leave-type::after { background: #0f766e; }
.masters-card.facility-title::after { background: #f97316; }
.masters-card-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
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
.masters-card-icon.status {
    background: linear-gradient(135deg, #fff7ed, #fef3c7);
    color: #b45309;
}
.masters-card-icon.source {
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    color: #ea580c;
}
.masters-card-icon.outcome {
    background: linear-gradient(135deg, #fdf2f8, #fce7f3);
    color: #db2777;
}
.masters-card-icon.subcategory {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: #2563eb;
}
.masters-card-icon.product-category {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #7c3aed;
}
.masters-card-icon.product-subcategory {
    background: linear-gradient(135deg, #ecfdf5, #cffafe);
    color: #0891b2;
}
.masters-card-icon.product {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    color: #059669;
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
.masters-card-title {
    margin: 0 0 8px;
    font-size: 17px;
    font-weight: 800;
    color: #111827;
}
.masters-card-text {
    margin: 0;
    max-width: 280px;
    font-size: 13px;
    line-height: 1.7;
    color: #6b7280;
}
.masters-card-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    font-size: 12px;
    font-weight: 800;
    color: #111827;
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
}
</style>
@endpush

@section('content')
<div class="masters-page">
    <div class="masters-hero">
        <div>
            <div class="masters-kicker">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" y1="6" x2="20" y2="6"/>
                    <line x1="4" y1="18" x2="20" y2="18"/>
                    <line x1="10" y1="6" x2="10" y2="18"/>
                    <circle cx="10" cy="6" r="2.5" fill="currentColor" stroke="none"/>
                    <circle cx="14" cy="18" r="2.5" fill="currentColor" stroke="none"/>
                </svg>
                Data Configuration
            </div>
            <h2 class="masters-title">Masters</h2>
            <p class="masters-subtitle">Manage and organize all master data configurations that power your CRM workflows, from lead pipeline to product catalog.</p>
        </div>

        <div class="masters-glance">
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="6" cy="6" r="2.5"/>
                    <circle cx="18" cy="6" r="2.5"/>
                    <circle cx="12" cy="18" r="2.5"/>
                    <path d="M8.1 7.3l2.9 7.4"/>
                    <path d="M15.9 7.3l-2.9 7.4"/>
                </svg>
                Pipeline Setup
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3l8 4.5-8 4.5-8-4.5L12 3z"/>
                    <path d="M4 7.5V16.5L12 21l8-4.5V7.5"/>
                    <path d="M12 12v9"/>
                </svg>
                Products
            </div>
            <div class="masters-glance-item">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="10" cy="7" r="3"></circle>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                </svg>
                Organization
            </div>
        </div>
    </div>

    <div class="masters-grid">

        @can('lead_status.menuview')
        <a href="{{ route('settings.lead-statuses.index') }}" class="masters-card status">
            <div class="masters-card-icon status">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3v18"/>
                    <path d="M12 5h7l-2.5 3L19 11h-7"/>
                    <path d="M12 13H5l2.5 3L5 19h7"/>
                </svg>
            </div>
            <h4 class="masters-card-title">Lead Status</h4>
            <p class="masters-card-text">Organize pipeline stages so teams can track lead progress clearly from new enquiry to closure.</p>
            <span class="masters-card-link">
                Manage Lead Status
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('lead_source.menuview')
        <a href="{{ route('settings.lead-sources.index') }}" class="masters-card source">
            <div class="masters-card-icon source">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="2.5"/>
                    <path d="M5 12a7 7 0 0 1 7-7"/>
                    <path d="M19 12a7 7 0 0 0-7-7"/>
                    <path d="M2 12a10 10 0 0 1 10-10"/>
                    <path d="M22 12A10 10 0 0 0 12 2"/>
                </svg>
            </div>
            <h4 class="masters-card-title">Lead Source</h4>
            <p class="masters-card-text">Track which campaigns, channels, and referrals are generating incoming leads for the business.</p>
            <span class="masters-card-link">
                Manage Lead Source
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('outcome_category.menuview')
        <a href="{{ route('settings.outcome-categories.index') }}" class="masters-card outcome">
            <div class="masters-card-icon outcome">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="5" width="12" height="12" rx="2"/>
                    <path d="M8 9h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2V9"/>
                </svg>
            </div>
            <h4 class="masters-card-title">Outcome Category</h4>
            <p class="masters-card-text">Group lead or call outcomes into clean top-level buckets for easier reporting and follow-up rules.</p>
            <span class="masters-card-link">
                Manage Outcome Categories
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('outcome_sub_category.menuview')
        <a href="{{ route('settings.outcome-sub-categories.index') }}" class="masters-card subcategory">
            <div class="masters-card-icon subcategory">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="6" cy="8" r="2.5"/>
                    <circle cx="18" cy="8" r="2.5"/>
                    <circle cx="12" cy="18" r="2.5"/>
                    <path d="M8.3 9.5l2.3 5.5"/>
                    <path d="M15.7 9.5l-2.3 5.5"/>
                </svg>
            </div>
            <h4 class="masters-card-title">Outcome Sub Category</h4>
            <p class="masters-card-text">Add more detailed outcome labels under each category to refine your team's tracking and analysis.</p>
            <span class="masters-card-link">
                Manage Sub Categories
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('product_category.menuview')
        <a href="{{ route('settings.product-category.index') }}" class="masters-card product-category">
            <div class="masters-card-icon product-category">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="4" y="4" width="7" height="16" rx="1.5"/>
                    <rect x="13" y="4" width="7" height="7" rx="1.5"/>
                    <rect x="13" y="13" width="7" height="7" rx="1.5"/>
                </svg>
            </div>
            <h4 class="masters-card-title">Product Category</h4>
            <p class="masters-card-text">Structure your catalog into well-defined product groups so configuration and reporting stay manageable.</p>
            <span class="masters-card-link">
                Manage Product Categories
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('product_attributes.menuview')
        <a href="{{ route('settings.product-attribute.index') }}" class="masters-card product-subcategory">
            <div class="masters-card-icon product-subcategory">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="4" y1="7" x2="20" y2="7"/>
                    <line x1="4" y1="17" x2="20" y2="17"/>
                    <circle cx="8" cy="7" r="2.5"/>
                    <circle cx="16" cy="17" r="2.5"/>
                </svg>
            </div>
            <h4 class="masters-card-title">Product Sub Category</h4>
            <p class="masters-card-text">Define attribute sets and configurable product details your sales and quotation workflows depend on.</p>
            <span class="masters-card-link">
                Manage Product Sub Categories
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </a>
        @endcan

        @can('products.menuview')
        <a href="{{ route('products.index') }}" class="masters-card product">
            <div class="masters-card-icon product">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3l8 4.5-8 4.5-8-4.5L12 3z"/>
                    <path d="M4 7.5V16.5L12 21l8-4.5V7.5"/>
                    <path d="M12 12v9"/>
                </svg>
            </div>
            <h4 class="masters-card-title">Products</h4>
            <p class="masters-card-text">Configure and manage all products your organization sells, including pricing, variants, and availability.</p>
            <span class="masters-card-link">
                Manage Products
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
