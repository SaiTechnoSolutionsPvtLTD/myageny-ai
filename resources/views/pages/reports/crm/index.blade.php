@extends('layouts.app')

@section('title', 'CRM Reports - myAgenci.ai')

@push('styles')
<style>
.crm-reports-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(249, 115, 22, 0.12), transparent 28%),
        linear-gradient(180deg, #f7f5f1 0%, #f3f6fa 100%);
}
.crm-reports-shell {
    max-width: 1280px;
    margin: 0 auto;
}
.crm-reports-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(280px, .9fr);
    align-items: stretch;
    gap: 24px;
    margin-bottom: 24px;
    padding: 28px;
    border: 1px solid #e7e5e4;
    border-radius: 24px;
    background: linear-gradient(135deg, #fff7ed 0%, #ffffff 52%, #f5f9ff 100%);
    box-shadow: 0 16px 42px rgba(15, 23, 42, 0.05);
}
.crm-reports-hero-copy {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
}
.crm-reports-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #ffedd5;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
}
.crm-reports-kicker svg,
.crm-report-card-icon svg,
.crm-report-card-link svg {
    flex-shrink: 0;
}
.crm-reports-kicker svg {
    width: 16px;
    height: 16px;
}
.crm-reports-title {
    margin: 0;
    font-size: 30px;
    font-weight: 800;
    line-height: 1.08;
    color: #111827;
}
.crm-reports-subtitle {
    margin: 10px 0 0;
    max-width: 700px;
    font-size: 14px;
    line-height: 1.7;
    color: #6b7280;
}
.crm-reports-glance {
    display: grid;
    grid-template-columns: 1fr;
    align-content: center;
    gap: 12px;
}
.crm-reports-glance-item {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    align-items: center;
    gap: 12px;
    min-height: 78px;
    padding: 16px 18px;
    border-radius: 18px;
    border: 1px solid #ece7e3;
    background: rgba(255, 255, 255, 0.94);
    color: #4b5563;
    font-size: 12px;
    font-weight: 700;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.65);
}
.crm-reports-glance-icon {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #eff6ff, #ffffff);
    color: #475569;
}
.crm-reports-glance-icon svg {
    width: 20px;
    height: 20px;
}
.crm-reports-glance-copy {
    min-width: 0;
}
.crm-reports-glance-label {
    display: block;
    margin-bottom: 4px;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: #94a3b8;
}
.crm-reports-glance-value {
    display: block;
    font-size: 20px;
    font-weight: 800;
    line-height: 1.2;
    color: #0f172a;
}
.crm-reports-glance-note {
    display: block;
    margin-top: 2px;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
}
.crm-reports-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
    align-items: stretch;
}
.crm-report-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 280px;
    padding: 22px;
    border-radius: 18px;
    border: 1px solid #e5e7eb;
    background: #ffffff;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.04);
}
.crm-report-card.is-link {
    text-decoration: none;
    color: inherit;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.crm-report-card.is-link:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
    border-color: #d6d3d1;
}
.crm-report-card::after {
    content: '';
    position: absolute;
    right: -34px;
    bottom: -34px;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    opacity: .14;
}
.crm-report-card.lead::after { background: #f97316; }
.crm-report-card.product::after { background: #0ea5e9; }
.crm-report-card.pipeline::after { background: #8b5cf6; }
.crm-report-card.campaign::after { background: #2563eb; }
.crm-report-card.team::after { background: #059669; }
.crm-report-card.quotation::after { background: #ec4899; }
.crm-report-card.branch::after { background: #4f46e5; }
.crm-report-card.smm::after { background: #0891b2; }
.crm-report-card.sales-comparison::after { background: #10b981; }
.crm-report-card.outstanding::after { background: #f59e0b; }
.crm-report-card-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.crm-report-card-icon svg {
    width: 24px;
    height: 24px;
}
.crm-report-card-icon.lead {
    background: linear-gradient(135deg, #fff7ed, #ffedd5);
    color: #ea580c;
}
.crm-report-card-icon.product {
    background: linear-gradient(135deg, #eff6ff, #e0f2fe);
    color: #0284c7;
}
.crm-report-card-icon.pipeline {
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
    color: #7c3aed;
}
.crm-report-card-icon.campaign {
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
    color: #1d4ed8;
}
.crm-report-card-icon.team {
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
    color: #047857;
}
.crm-report-card-icon.quotation {
    background: linear-gradient(135deg, #fdf2f8, #fce7f3);
    color: #db2777;
}
.crm-report-card-icon.branch {
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #4f46e5;
}
.crm-report-card-icon.smm {
    background: linear-gradient(135deg, #ecfeff, #cffafe);
    color: #0891b2;
}
.crm-report-card-icon.sales-comparison {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    color: #16a34a;
}
.crm-report-card-icon.outstanding {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    color: #d97706;
}
.crm-report-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    width: fit-content;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .3px;
    text-transform: uppercase;
}
.crm-report-status.ready {
    background: #ecfdf5;
    color: #047857;
}
.crm-report-status.soon {
    background: #fff7ed;
    color: #c2410c;
}
.crm-report-card-title {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 800;
    color: #111827;
}
.crm-report-card-text {
    margin: 0;
    font-size: 13px;
    line-height: 1.7;
    color: #6b7280;
}
.crm-report-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 18px;
}
.crm-report-card-content {
    display: flex;
    flex-direction: column;
    gap: 8px;
    min-height: 0;
}
.crm-report-card-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: auto;
    padding-top: 18px;
    font-size: 12px;
    font-weight: 800;
    color: #111827;
}
.crm-report-card-link svg {
    width: 18px;
    height: 18px;
    flex: 0 0 18px;
}
.crm-report-card-link.disabled {
    color: #9ca3af;
}
@media (max-width: 1399px) {
    .crm-reports-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 1040px) {
    .crm-reports-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .crm-reports-page {
        padding: 18px;
    }
    .crm-reports-hero {
        padding: 22px;
        grid-template-columns: 1fr;
    }
    .crm-reports-title {
        font-size: 24px;
    }
    .crm-reports-grid {
        grid-template-columns: 1fr;
    }
    .crm-report-card-head {
        align-items: flex-start;
    }
    .crm-report-status {
        max-width: 130px;
        justify-content: center;
    }
}
</style>
@endpush

@section('content')
<div class="crm-reports-page">
    <div class="crm-reports-shell">
    <div class="crm-reports-hero">
        <div class="crm-reports-hero-copy">
            <div class="crm-reports-kicker">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M4 19h16"/>
                    <path d="M7 16V8"/>
                    <path d="M12 16V5"/>
                    <path d="M17 16v-4"/>
                </svg>
                CRM Analytics
            </div>
            <h2 class="crm-reports-title">Reports</h2>
            <p class="crm-reports-subtitle">Keep all CRM reporting entry points in one place. This page is ready for multiple reports, so we can keep adding lead, product, quotation, and performance views without changing the sidebar again.</p>
        </div>

        <div class="crm-reports-glance">
            <div class="crm-reports-glance-item">
                <span class="crm-reports-glance-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 19h16"/>
                        <path d="M7 16V8"/>
                        <path d="M12 16V5"/>
                        <path d="M17 16v-4"/>
                    </svg>
                </span>
                <span class="crm-reports-glance-copy">
                    <span class="crm-reports-glance-label">Live Modules</span>
                    <span class="crm-reports-glance-value">{{ count($reports) }} Reports</span>
                    <span class="crm-reports-glance-note">Multiple CRM report entries in one place</span>
                </span>
            </div>
            <div class="crm-reports-glance-item">
                <span class="crm-reports-glance-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 20V10"/>
                        <path d="m18 20-6-6-6 6"/>
                        <path d="M5 4h14"/>
                    </svg>
                </span>
                <span class="crm-reports-glance-copy">
                    <span class="crm-reports-glance-label">Scalable Layout</span>
                    <span class="crm-reports-glance-value">Future Ready</span>
                    <span class="crm-reports-glance-note">Easy to add more reports without changing navigation</span>
                </span>
            </div>
        </div>
    </div>

    <div class="crm-reports-grid">
        @foreach($reports as $report)
        @php
            $isReady = $report['status'] === 'Ready for setup';
            $reportUrl = $isReady ? ($report['route'] ?? null) : null;
        @endphp
        <{{ $reportUrl ? 'a' : 'div' }}
            @if($reportUrl)
                href="{{ $reportUrl }}"
            @endif
            class="crm-report-card {{ $report['theme'] }} {{ $reportUrl ? 'is-link' : '' }}">
            <div class="crm-report-card-head">
                <div class="crm-report-card-icon {{ $report['theme'] }}">
                    @if($report['theme'] === 'lead')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="10" cy="7" r="4"/>
                        <path d="M20 8v6"/>
                        <path d="M23 11h-6"/>
                    </svg>
                    @elseif($report['theme'] === 'product')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7h18"/>
                        <path d="M6 3h12l1 4H5l1-4z"/>
                        <path d="M5 11h14v8a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-8z"/>
                    </svg>
                    @elseif($report['theme'] === 'pipeline')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h7v12H3z"/>
                        <path d="M14 10h7v8h-7z"/>
                        <path d="M10 12h4"/>
                    </svg>
                    @elseif($report['theme'] === 'campaign')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 4h16v12H4z"/>
                        <path d="M8 20h8"/>
                        <path d="M12 16v4"/>
                        <path d="m8 10 2.5 2.5L16 7"/>
                    </svg>
                    @elseif($report['theme'] === 'team')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    @elseif($report['theme'] === 'branch')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <line x1="9" y1="3" x2="9" y2="21"/>
                        <line x1="15" y1="3" x2="15" y2="21"/>
                        <line x1="3" y1="9" x2="21" y2="9"/>
                        <line x1="3" y1="15" x2="21" y2="15"/>
                    </svg>
                    @elseif($report['theme'] === 'smm')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        <path d="M8 10h8"/>
                        <path d="M8 14h5"/>
                    </svg>
                    @elseif($report['theme'] === 'sales-comparison')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    @elseif($report['theme'] === 'outstanding')
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    @else
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/>
                        <path d="M14 3v6h6"/>
                        <path d="M8 13h8"/>
                        <path d="M8 17h5"/>
                    </svg>
                    @endif
                </div>

                <span class="crm-report-status {{ $isReady ? 'ready' : 'soon' }}">{{ $report['status'] }}</span>
            </div>

            <div class="crm-report-card-content">
                <h4 class="crm-report-card-title">{{ $report['title'] }}</h4>
                <p class="crm-report-card-text">{{ $report['description'] }}</p>
            </div>

            <span class="crm-report-card-link {{ $isReady ? '' : 'disabled' }}">
                {{ $isReady ? 'Configure This Report' : 'Available Soon' }}
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </{{ $reportUrl ? 'a' : 'div' }}>
        @endforeach
    </div>
    </div>
</div>
@endsection
