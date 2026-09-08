@extends('layouts.app')

@section('title', 'CRM Revenue Comparison Report - myAgenci.ai')

@push('styles')
<style>
.crm-rev-page { min-height: 100%; padding: 24px; background: linear-gradient(180deg, #f7f5f1 0%, #f3f6fa 100%); }
.crm-rev-shell { max-width: 1440px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
.crm-rev-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px; border: 1px solid #e7e5e4; border-radius: 24px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #ffedd5 100%); box-shadow: 0 14px 38px rgba(15, 23, 42, 0.05); }
.crm-rev-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: #ffedd5; color: #c2410c; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-rev-title { margin: 14px 0 6px; font-size: 30px; font-weight: 800; color: #111827; }
.crm-rev-subtitle { margin: 0; font-size: 14px; line-height: 1.7; color: #6b7280; max-width: 760px; }
.crm-rev-actions { display: flex; align-items: center; gap: 10px; }
.crm-rev-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; color: #111827; font-size: 13px; font-weight: 800; text-decoration: none; }
.crm-rev-btn:hover { border-color: #fdba74; background: #fff7ed; color: #c2410c; }
.crm-rev-btn-primary { border-color: transparent; background: linear-gradient(135deg, #f97316, #fb923c); color: #fff; box-shadow: 0 6px 18px rgba(249, 115, 22, 0.22); }
.crm-rev-btn-primary:hover { color: #fff; border-color: transparent; background: linear-gradient(135deg, #ea580c, #f97316); }
.crm-rev-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.crm-rev-stat { position: relative; overflow: hidden; padding: 20px; border-radius: 22px; border: 1px solid #e7e5e4; background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(248,250,252,.98) 100%); box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05); }
.crm-rev-stat::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 5px; border-radius: 999px; background: var(--stat-accent, #f97316); }
.crm-rev-stat-label { margin-top: 8px; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
.crm-rev-stat-value { margin-top: 10px; font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: -.03em; }
.crm-rev-stat-note { margin-top: 10px; font-size: 12px; color: #64748b; }
.crm-rev-tabs { display: inline-flex; align-items: center; gap: 10px; padding: 10px; border-radius: 20px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #ffedd5 100%); border: 1px solid #fed7aa; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255,255,255,.9); width: fit-content; }
.crm-rev-tab { padding: 12px 22px; border-radius: 14px; border: 1px solid transparent; background: rgba(255,255,255,.72); color: #475569; font-size: 13px; font-weight: 900; transition: all .18s ease; }
.crm-rev-tab:hover { border-color: #fdba74; background: #ffffff; color: #c2410c; transform: translateY(-1px); }
.crm-rev-tab.is-active { background: linear-gradient(135deg, #f97316, #fb923c); color: #fff; border-color: #ea580c; box-shadow: 0 10px 22px rgba(249, 115, 22, 0.24); }
.crm-rev-panel { display: none; flex-direction: column; gap: 18px; }
.crm-rev-panel.is-active { display: flex; }
.crm-rev-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-rev-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #f1f5f9; }
.crm-rev-card-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-rev-card-subtitle { margin-top: 3px; font-size: 12px; color: #6b7280; }
.crm-rev-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 11px; font-weight: 800; }
.crm-rev-body { padding: 18px; }
.crm-rev-form { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; }
.crm-rev-field { display: flex; flex-direction: column; gap: 7px; }
.crm-rev-label { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
.crm-rev-input, .crm-rev-select { width: 100%; padding: 11px 13px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 13px; outline: none; }
.crm-rev-input:focus, .crm-rev-select:focus { border-color: #fb923c; box-shadow: 0 0 0 4px rgba(251, 146, 60, 0.12); background: #fff; }
.crm-rev-actions-row { display: flex; align-items: flex-end; gap: 10px; }
.crm-rev-table-wrap { overflow-x: auto; }
.crm-rev-table { width: 100%; border-collapse: collapse; min-width: 1100px; }
.crm-rev-table th { padding: 12px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; white-space: nowrap; }
.crm-rev-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #111827; vertical-align: middle; }
.crm-rev-table tbody tr:hover td { background: #fffaf5; }
.crm-rev-money { font-weight: 800; white-space: nowrap; }
.crm-rev-analytics-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.crm-rev-analytics-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 18px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-rev-analytics-card.full { grid-column: 1 / -1; }
.crm-rev-chart { position: relative; min-height: 300px; }
.crm-rev-chart.tall { min-height: 350px; }
@media (max-width: 1280px) {
    .crm-rev-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .crm-rev-form, .crm-rev-analytics-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .crm-rev-page { padding: 18px; }
    .crm-rev-topbar, .crm-rev-head { flex-direction: column; align-items: flex-start; }
    .crm-rev-stats, .crm-rev-form, .crm-rev-analytics-grid { grid-template-columns: 1fr; }
    .crm-rev-actions-row { flex-wrap: wrap; }
    .crm-rev-tabs { width: 100%; flex-wrap: wrap; }
}
</style>
@endpush

@section('content')
<div class="crm-rev-page">
    <div class="crm-rev-shell">
        <div class="crm-rev-topbar">
            <div>
                <span class="crm-rev-kicker">CRM Reports</span>
                <h1 class="crm-rev-title">Revenue Comparison Report</h1>
                <p class="crm-rev-subtitle">Compare current period value and count against the previous matching period, then track difference and growth in one reporting space.</p>
            </div>
            <div class="crm-rev-actions">
                <a href="{{ route('reports.crm.revenue-comparison.export', request()->query()) }}" class="crm-rev-btn crm-rev-btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('reports.crm.index') }}" class="crm-rev-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <div class="crm-rev-stats">
            <div class="crm-rev-stat" style="--stat-accent:#f97316;">
                <div class="crm-rev-stat-label">Current Period Value</div>
                <div class="crm-rev-stat-value">Rs {{ number_format($comparison['cards']['current_value'], 2) }}</div>
                <div class="crm-rev-stat-note">{{ $comparison['table']['current_period_label'] }}</div>
            </div>
            <div class="crm-rev-stat" style="--stat-accent:#fb923c;">
                <div class="crm-rev-stat-label">Current Period Count</div>
                <div class="crm-rev-stat-value">{{ number_format($comparison['cards']['current_count']) }}</div>
                <div class="crm-rev-stat-note">Visible lead-product entries in selected period.</div>
            </div>
            <div class="crm-rev-stat" style="--stat-accent:#ea580c;">
                <div class="crm-rev-stat-label">Difference Amount</div>
                <div class="crm-rev-stat-value">Rs {{ number_format($comparison['cards']['difference_amount'], 2) }}</div>
                <div class="crm-rev-stat-note">Current period minus previous period value.</div>
            </div>
            <div class="crm-rev-stat" style="--stat-accent:#c2410c;">
                <div class="crm-rev-stat-label">Growth %</div>
                <div class="crm-rev-stat-value">{{ number_format($comparison['cards']['growth_percent'], 2) }}%</div>
                <div class="crm-rev-stat-note">Count growth versus previous matching period.</div>
            </div>
        </div>

        <div class="crm-rev-tabs" id="crmRevTabs">
            <button type="button" class="crm-rev-tab is-active" data-tab-target="crm-rev-data">Data</button>
            <button type="button" class="crm-rev-tab" data-tab-target="crm-rev-analytics">Analytics</button>
        </div>

        <div class="crm-rev-card">
            <div class="crm-rev-head">
                <div>
                    <div class="crm-rev-card-title">Filters</div>
                    <div class="crm-rev-card-subtitle">Choose period type and product if you want a tighter comparison.</div>
                </div>
                <div class="crm-rev-chip">{{ strtoupper($selectedFilters['period_type']) }} comparison</div>
            </div>
            <div class="crm-rev-body">
                <form method="GET" action="{{ route('reports.crm.revenue-comparison') }}" class="crm-rev-form">
                    <input type="hidden" name="tab" id="crmActiveTabInput" value="{{ request('tab', 'crm-rev-data') }}">
                    <div class="crm-rev-field">
                        <label class="crm-rev-label" for="period_type">Period Type</label>
                        <select id="period_type" name="period_type" class="crm-rev-select">
                            @foreach($filterOptions['period_types'] as $key => $label)
                                <option value="{{ $key }}" @selected($selectedFilters['period_type'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-rev-field">
                        <label class="crm-rev-label" for="year">Year</label>
                        <select id="year" name="year" class="crm-rev-select">
                            @foreach($filterOptions['years'] as $year)
                                <option value="{{ $year }}" @selected($selectedFilters['year'] === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-rev-field" id="monthField">
                        <label class="crm-rev-label" for="month">Month</label>
                        <select id="month" name="month" class="crm-rev-select">
                            @foreach($filterOptions['months'] as $monthValue => $monthLabel)
                                <option value="{{ $monthValue }}" @selected($selectedFilters['month'] === $monthValue)>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-rev-field" id="quarterField">
                        <label class="crm-rev-label" for="quarter">Quarter</label>
                        <select id="quarter" name="quarter" class="crm-rev-select">
                            @foreach($filterOptions['quarters'] as $quarterValue => $quarterLabel)
                                <option value="{{ $quarterValue }}" @selected($selectedFilters['quarter'] === $quarterValue)>{{ $quarterLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-rev-field">
                        <label class="crm-rev-label" for="product_id">Product</label>
                        <select id="product_id" name="product_id" class="crm-rev-select">
                            <option value="">All Products</option>
                            @foreach($filterOptions['products'] as $product)
                                <option value="{{ $product->id }}" @selected($selectedFilters['product_id'] === $product->id)>
                                    {{ $product->package_name ?: $product->product_name }}{{ $product->sku ? ' - ' . $product->sku : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="crm-rev-field">
                        <label class="crm-rev-label" for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id" class="crm-rev-select">
                            <option value="">All Branches</option>
                            @foreach($filterOptions['branches'] as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedFilters['branch_id'] === $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-rev-actions-row">
                        <button type="submit" class="crm-rev-btn crm-rev-btn-primary">Apply Filters</button>
                        <a href="{{ route('reports.crm.revenue-comparison') }}" class="crm-rev-btn">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="crm-rev-panel is-active" id="crm-rev-data">
            <div class="crm-rev-card">
                <div class="crm-rev-head">
                    <div>
                        <div class="crm-rev-card-title">Revenue Comparison Sheet</div>
                        <div class="crm-rev-card-subtitle">{{ $comparison['table']['current_period_label'] }} vs {{ $comparison['table']['previous_period_label'] }}</div>
                    </div>
                </div>
                <div class="crm-rev-table-wrap">
                    <table class="crm-rev-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th>Current Period Value</th>
                                <th>Current Period Count</th>
                                <th>Previous Period Value</th>
                                <th>Previous Period Count</th>
                                <th>Difference Amount</th>
                                <th>Difference %</th>
                                <th>Growth %</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $comparison['table']['period_label'] }}</td>
                                <td class="crm-rev-money">Rs {{ number_format($comparison['table']['current_period_value'], 2) }}</td>
                                <td>{{ number_format($comparison['table']['current_period_count']) }}</td>
                                <td class="crm-rev-money">Rs {{ number_format($comparison['table']['previous_period_value'], 2) }}</td>
                                <td>{{ number_format($comparison['table']['previous_period_count']) }}</td>
                                <td class="crm-rev-money">{{ number_format($comparison['table']['difference_amount'], 2) }}</td>
                                <td>{{ number_format($comparison['table']['difference_percent'], 2) }}%</td>
                                <td>{{ number_format($comparison['table']['growth_percent'], 2) }}%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="crm-rev-panel" id="crm-rev-analytics">
            <div class="crm-rev-analytics-grid">
                <div class="crm-rev-analytics-card">
                    <div class="crm-rev-head">
                        <div>
                            <div class="crm-rev-card-title">Current vs Previous Value</div>
                            <div class="crm-rev-card-subtitle">Direct revenue comparison for the selected period pair.</div>
                        </div>
                    </div>
                    <div class="crm-rev-chart">
                        <canvas id="revCompareValueChart"></canvas>
                    </div>
                </div>

                <div class="crm-rev-analytics-card">
                    <div class="crm-rev-head">
                        <div>
                            <div class="crm-rev-card-title">Current vs Previous Count</div>
                            <div class="crm-rev-card-subtitle">Volume comparison for the same period pair.</div>
                        </div>
                    </div>
                    <div class="crm-rev-chart">
                        <canvas id="revCompareCountChart"></canvas>
                    </div>
                </div>

                <div class="crm-rev-analytics-card full">
                    <div class="crm-rev-head">
                        <div>
                            <div class="crm-rev-card-title">Revenue Trend</div>
                            <div class="crm-rev-card-subtitle">Last six comparable periods based on your selected period type.</div>
                        </div>
                    </div>
                    <div class="crm-rev-chart tall">
                        <canvas id="revTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(() => {
    const tabs = document.querySelectorAll('#crmRevTabs [data-tab-target]');
    const panels = document.querySelectorAll('.crm-rev-panel');
    const periodTypeSelect = document.getElementById('period_type');
    const monthField = document.getElementById('monthField');
    const quarterField = document.getElementById('quarterField');

    const syncPeriodFields = () => {
        const type = periodTypeSelect?.value;
        if (monthField) {
            monthField.style.display = type === 'month' ? 'flex' : 'none';
        }
        if (quarterField) {
            quarterField.style.display = type === 'quarter' ? 'flex' : 'none';
        }
    };

    const tabInput = document.getElementById('crmActiveTabInput');

    function activateTab(targetId) {
        const activeTabBtn = document.querySelector(`#crmRevTabs [data-tab-target="${targetId}"]`);
        const activePanel = document.getElementById(targetId);
        if (activeTabBtn && activePanel) {
            tabs.forEach((button) => button.classList.remove('is-active'));
            panels.forEach((panel) => panel.classList.remove('is-active'));
            activeTabBtn.classList.add('is-active');
            activePanel.classList.add('is-active');
            if (tabInput) tabInput.value = targetId;
        }
    }

    const initialTab = new URLSearchParams(window.location.search).get('tab') || (tabInput ? tabInput.value : 'crm-rev-data');
    if (initialTab) {
        activateTab(initialTab);
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.tabTarget;
            activateTab(targetId);
            const url = new URL(window.location);
            url.searchParams.set('tab', targetId);
            window.history.replaceState({}, '', url);
        });
    });

    periodTypeSelect?.addEventListener('change', syncPeriodFields);
    syncPeriodFields();

    const analytics = @json($comparison['analytics']);
    const currency = (value) => 'Rs ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    const registry = {};

    const createChart = (id, config) => {
        const canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        if (registry[id]) {
            registry[id].destroy();
        }

        registry[id] = new Chart(canvas.getContext('2d'), config);
    };

    createChart('revCompareValueChart', {
        type: 'bar',
        data: {
            labels: analytics.comparison_chart.map(item => item.label),
            datasets: [{
                label: 'Revenue Value',
                data: analytics.comparison_chart.map(item => item.value),
                backgroundColor: ['#fdba74', '#f97316'],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${currency(ctx.parsed.y)}` } }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: (value) => currency(value) } }
            }
        }
    });

    createChart('revCompareCountChart', {
        type: 'doughnut',
        data: {
            labels: analytics.comparison_chart.map(item => item.label),
            datasets: [{
                data: analytics.comparison_chart.map(item => item.count),
                backgroundColor: ['#fed7aa', '#ea580c'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed} entries` } }
            }
        }
    });

    createChart('revTrendChart', {
        type: 'line',
        data: {
            labels: analytics.trend_chart.map(item => item.label),
            datasets: [
                {
                    label: 'Revenue Value',
                    data: analytics.trend_chart.map(item => item.value),
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.12)',
                    fill: true,
                    tension: 0.35,
                    yAxisID: 'y',
                },
                {
                    label: 'Count',
                    data: analytics.trend_chart.map(item => item.count),
                    borderColor: '#7c2d12',
                    backgroundColor: 'rgba(124, 45, 18, 0.08)',
                    fill: false,
                    tension: 0.35,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ctx.dataset.label === 'Count'
                            ? `${ctx.dataset.label}: ${ctx.parsed.y}`
                            : `${ctx.dataset.label}: ${currency(ctx.parsed.y)}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: (value) => currency(value) }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
})();
</script>
@endpush
