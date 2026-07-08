@extends('layouts.app')

@section('title', 'CRM Branch Wise Comparison Report - myAgenci.ai')

@push('styles')
<style>
.crm-branch-page { min-height: 100%; padding: 24px; background: linear-gradient(180deg, #f7f5f1 0%, #f3f6fa 100%); }
.crm-branch-shell { max-width: 1440px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
.crm-branch-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px; border: 1px solid #e7e5e4; border-radius: 24px; background: linear-gradient(135deg, #f5f3ff 0%, #ffffff 52%, #eff6ff 100%); box-shadow: 0 14px 38px rgba(15, 23, 42, 0.05); }
.crm-branch-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: #e0e7ff; color: #4f46e5; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-branch-title { margin: 14px 0 6px; font-size: 30px; font-weight: 800; color: #111827; }
.crm-branch-subtitle { margin: 0; font-size: 14px; line-height: 1.7; color: #6b7280; max-width: 760px; }
.crm-branch-actions { display: flex; align-items: center; gap: 10px; }
.crm-branch-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; color: #111827; font-size: 13px; font-weight: 800; text-decoration: none; }
.crm-branch-btn:hover { border-color: #c7d2fe; background: #f5f3ff; color: #4f46e5; }
.crm-branch-btn-primary { border-color: transparent; background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; box-shadow: 0 6px 18px rgba(79, 70, 229, 0.22); }
.crm-branch-btn-primary:hover { color: #fff; border-color: transparent; background: linear-gradient(135deg, #3730a3, #4f46e5); }
.crm-branch-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-branch-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #f1f5f9; }
.crm-branch-card-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-branch-card-subtitle { margin-top: 3px; font-size: 12px; color: #6b7280; }
.crm-branch-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe; font-size: 11px; font-weight: 800; }
.crm-branch-body { padding: 18px; }
.crm-branch-form { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; }
.crm-branch-field { display: flex; flex-direction: column; gap: 7px; }
.crm-branch-label { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
.crm-branch-input, .crm-branch-select { width: 100%; padding: 11px 13px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 13px; outline: none; }
.crm-branch-input:focus, .crm-branch-select:focus { border-color: #6366f1; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12); background: #fff; }
.crm-branch-actions-row { display: flex; align-items: flex-end; gap: 10px; }
.crm-branch-table-wrap { overflow-x: auto; }
.crm-branch-table { width: 100%; border-collapse: collapse; min-width: 1000px; }
.crm-branch-table th { padding: 12px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; white-space: nowrap; }
.crm-branch-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #111827; vertical-align: middle; }
.crm-branch-table tbody tr:hover td { background: #faf5ff; }
.crm-branch-name { font-weight: 800; color: #111827; }
.crm-branch-money { font-weight: 800; white-space: nowrap; }
.crm-branch-tabs { display: inline-flex; align-items: center; gap: 10px; padding: 10px; border-radius: 20px; background: linear-gradient(135deg, #f5f3ff 0%, #ffffff 52%, #eff6ff 100%); border: 1px solid #c7d2fe; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255,255,255,.9); width: fit-content; }
.crm-branch-tab { padding: 12px 22px; border-radius: 14px; border: 1px solid transparent; background: rgba(255,255,255,.72); color: #475569; font-size: 13px; font-weight: 900; transition: all .18s ease; }
.crm-branch-tab:hover { border-color: #c7d2fe; background: #ffffff; color: #4f46e5; transform: translateY(-1px); }
.crm-branch-tab.is-active { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; border-color: #3730a3; box-shadow: 0 10px 22px rgba(79, 70, 229, 0.24); }
.crm-branch-panel { display: none; flex-direction: column; gap: 18px; }
.crm-branch-panel.is-active { display: flex; }
.crm-branch-analytics-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.crm-branch-analytics-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 18px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-branch-analytics-card.full { grid-column: 1 / -1; }
.crm-branch-chart { position: relative; min-height: 320px; }
.crm-branch-chart.tall { min-height: 400px; }
.crm-branch-matrix-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.crm-branch-matrix-table th { padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; text-align: center; }
.crm-branch-matrix-table td { padding: 12px; border: 1px solid #e2e8f0; font-size: 13px; text-align: center; }
.crm-branch-matrix-table td.branch-label { font-weight: 800; text-align: left; background: #f8fafc; }
.crm-branch-matrix-table td.total-cell { font-weight: 800; background: #f1f5f9; }
@media (max-width: 1280px) {
    .crm-branch-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .crm-branch-analytics-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .crm-branch-page { padding: 18px; }
    .crm-branch-topbar, .crm-branch-head { flex-direction: column; align-items: flex-start; }
    .crm-branch-form { grid-template-columns: 1fr; }
    .crm-branch-actions-row { flex-wrap: wrap; }
    .crm-branch-tabs { width: 100%; flex-wrap: wrap; }
}
</style>
@endpush

@section('content')
<div class="crm-branch-page">
    <div class="crm-branch-shell">
        <div class="crm-branch-topbar">
            <div>
                <span class="crm-branch-kicker">CRM Reports</span>
                <h1 class="crm-branch-title">Branch Wise Comparison Report</h1>
                <p class="crm-branch-subtitle">Compare performance, lead generation, revenue, source distributions, and stages across your company branches.</p>
            </div>
            <div class="crm-branch-actions">
                <a href="{{ route('reports.crm.branch-comparison.export', request()->query()) }}" class="crm-branch-btn crm-branch-btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('reports.crm.index') }}" class="crm-branch-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <div class="crm-branch-card">
            <div class="crm-branch-head">
                <div>
                    <div class="crm-branch-card-title">Filters</div>
                    <div class="crm-branch-card-subtitle">Choose period type to compare branch statistics.</div>
                </div>
                <div class="crm-branch-chip">Selected Period: {{ $periodLabel }}</div>
            </div>
            <div class="crm-branch-body">
                <form method="GET" action="{{ route('reports.crm.branch-comparison') }}" class="crm-branch-form">
                    <div class="crm-branch-field">
                        <label class="crm-branch-label" for="period_type">Period Type</label>
                        <select id="period_type" name="period_type" class="crm-branch-select">
                            @foreach($filterOptions['period_types'] as $key => $label)
                                <option value="{{ $key }}" @selected($selectedFilters['period_type'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-branch-field" id="yearField">
                        <label class="crm-branch-label" for="year">Year</label>
                        <select id="year" name="year" class="crm-branch-select">
                            @foreach($filterOptions['years'] as $year)
                                <option value="{{ $year }}" @selected($selectedFilters['year'] === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-branch-field" id="monthField">
                        <label class="crm-branch-label" for="month">Month</label>
                        <select id="month" name="month" class="crm-branch-select">
                            @foreach($filterOptions['months'] as $monthVal => $monthLbl)
                                <option value="{{ $monthVal }}" @selected($selectedFilters['month'] === $monthVal)>{{ $monthLbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-branch-field" id="quarterField">
                        <label class="crm-branch-label" for="quarter">Quarter</label>
                        <select id="quarter" name="quarter" class="crm-branch-select">
                            @foreach($filterOptions['quarters'] as $qVal => $qLbl)
                                <option value="{{ $qVal }}" @selected($selectedFilters['quarter'] === $qVal)>{{ $qLbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-branch-field" id="dateFromField">
                        <label class="crm-branch-label" for="date_from">Date From</label>
                        <input id="date_from" type="date" name="date_from" class="crm-branch-input" value="{{ $selectedFilters['date_from'] }}">
                    </div>

                    <div class="crm-branch-field" id="dateToField">
                        <label class="crm-branch-label" for="date_to">Date To</label>
                        <input id="date_to" type="date" name="date_to" class="crm-branch-input" value="{{ $selectedFilters['date_to'] }}">
                    </div>

                    <div class="crm-branch-actions-row">
                        <button type="submit" class="crm-branch-btn crm-branch-btn-primary">Apply Filters</button>
                        <a href="{{ route('reports.crm.branch-comparison') }}" class="crm-branch-btn">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="crm-branch-tabs" id="crmBranchTabs">
            <button type="button" class="crm-branch-tab is-active" data-tab-target="comparison-table-panel">Comparison Table</button>
            <button type="button" class="crm-branch-tab" data-tab-target="leads-panel">Leads Analytics</button>
            <button type="button" class="crm-branch-tab" data-tab-target="revenue-panel">Revenue Analytics</button>
            <button type="button" class="crm-branch-tab" data-tab-target="source-panel">Source Analytics</button>
            <button type="button" class="crm-branch-tab" data-tab-target="status-panel">Status Analytics</button>
        </div>

        {{-- Comparison Table Panel --}}
        <div class="crm-branch-panel is-active" id="comparison-table-panel">
            <div class="crm-branch-card">
                <div class="crm-branch-head">
                    <div>
                        <div class="crm-branch-card-title">Branch Performance Grid</div>
                        <div class="crm-branch-card-subtitle">Overview of lead volumes and revenues across branches.</div>
                    </div>
                </div>
                <div class="crm-branch-table-wrap">
                    <table class="crm-branch-table">
                        <thead>
                            <tr>
                                <th>Branch Name</th>
                                <th>Total Leads</th>
                                <th>Converted Leads</th>
                                <th>Conversion Rate</th>
                                <th>Sales contract Value</th>
                                <th>Received Amount</th>
                                <th>Outstanding Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comparisonData['rows'] as $row)
                                @php
                                    $rate = $row['total_leads'] > 0 ? round(($row['converted_leads'] / $row['total_leads']) * 100, 2) : 0;
                                    $outstanding = max(0, $row['revenue'] - $row['received']);
                                @endphp
                                <tr>
                                    <td><span class="crm-branch-name">{{ $row['branch_name'] }}</span></td>
                                    <td>{{ number_format($row['total_leads']) }}</td>
                                    <td>{{ number_format($row['converted_leads']) }}</td>
                                    <td><strong>{{ $rate }}%</strong></td>
                                    <td class="crm-branch-money">Rs {{ number_format($row['revenue'], 2) }}</td>
                                    <td class="crm-branch-money" style="color: #16a34a;">Rs {{ number_format($row['received'], 2) }}</td>
                                    <td class="crm-branch-money" style="color: #dc2626;">Rs {{ number_format($outstanding, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">No data found for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Leads Analytics Panel --}}
        <div class="crm-branch-panel" id="leads-panel">
            <div class="crm-branch-analytics-grid">
                <div class="crm-branch-analytics-card full">
                    <div class="crm-branch-head">
                        <div>
                            <div class="crm-branch-card-title">Leads vs Converted Volume</div>
                            <div class="crm-branch-card-subtitle">Comparison of total lead generation against converted accounts.</div>
                        </div>
                    </div>
                    <div class="crm-branch-chart tall">
                        <canvas id="branchLeadsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Revenue Analytics Panel --}}
        <div class="crm-branch-panel" id="revenue-panel">
            <div class="crm-branch-analytics-grid">
                <div class="crm-branch-analytics-card full">
                    <div class="crm-branch-head">
                        <div>
                            <div class="crm-branch-card-title">Sales contract Value vs Collected Revenue</div>
                            <div class="crm-branch-card-subtitle">Comparison of sales contract totals against actual payment collections.</div>
                        </div>
                    </div>
                    <div class="crm-branch-chart tall">
                        <canvas id="branchRevenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Source Analytics Panel --}}
        <div class="crm-branch-panel" id="source-panel">
            <div class="crm-branch-card">
                <div class="crm-branch-head">
                    <div>
                        <div class="crm-branch-card-title">Lead Source Matrix</div>
                        <div class="crm-branch-card-subtitle">Spread of lead channels across each branch.</div>
                    </div>
                </div>
                <div class="crm-branch-body" style="overflow-x: auto;">
                    <table class="crm-branch-matrix-table">
                        <thead>
                            <tr>
                                <th style="text-align: left;">Branch Name</th>
                                @foreach($comparisonData['all_sources'] as $source)
                                    <th>{{ $source }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comparisonData['rows'] as $row)
                                @php $branchTotal = 0; @endphp
                                <tr>
                                    <td class="branch-label">{{ $row['branch_name'] }}</td>
                                    @foreach($comparisonData['all_sources'] as $source)
                                        @php
                                            $count = $row['sources'][$source] ?? 0;
                                            $branchTotal += $count;
                                        @endphp
                                        <td>{{ number_format($count) }}</td>
                                    @endforeach
                                    <td class="total-cell">{{ number_format($branchTotal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($comparisonData['all_sources']) + 2 }}" style="text-align: center; color: #6b7280;">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="crm-branch-analytics-card" style="margin-top: 18px;">
                <div class="crm-branch-head">
                    <div>
                        <div class="crm-branch-card-title">Lead Source Chart</div>
                        <div class="crm-branch-card-subtitle">Branch-wise channel breakdown view.</div>
                    </div>
                </div>
                <div class="crm-branch-chart">
                    <canvas id="branchSourceChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Status Analytics Panel --}}
        <div class="crm-branch-panel" id="status-panel">
            <div class="crm-branch-card">
                <div class="crm-branch-head">
                    <div>
                        <div class="crm-branch-card-title">Lead Status Matrix</div>
                        <div class="crm-branch-card-subtitle">Spread of lead stages across each branch.</div>
                    </div>
                </div>
                <div class="crm-branch-body" style="overflow-x: auto;">
                    <table class="crm-branch-matrix-table">
                        <thead>
                            <tr>
                                <th style="text-align: left;">Branch Name</th>
                                @foreach($comparisonData['all_statuses'] as $status)
                                    <th>{{ $status }}</th>
                                @endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($comparisonData['rows'] as $row)
                                @php $branchTotal = 0; @endphp
                                <tr>
                                    <td class="branch-label">{{ $row['branch_name'] }}</td>
                                    @foreach($comparisonData['all_statuses'] as $status)
                                        @php
                                            $count = $row['statuses'][$status] ?? 0;
                                            $branchTotal += $count;
                                        @endphp
                                        <td>{{ number_format($count) }}</td>
                                    @endforeach
                                    <td class="total-cell">{{ number_format($branchTotal) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($comparisonData['all_statuses']) + 2 }}" style="text-align: center; color: #6b7280;">No data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="crm-branch-analytics-card" style="margin-top: 18px;">
                <div class="crm-branch-head">
                    <div>
                        <div class="crm-branch-card-title">Lead Status Chart</div>
                        <div class="crm-branch-card-subtitle">Branch-wise status distribution view.</div>
                    </div>
                </div>
                <div class="crm-branch-chart">
                    <canvas id="branchStatusChart"></canvas>
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
    const tabs = document.querySelectorAll('#crmBranchTabs [data-tab-target]');
    const panels = document.querySelectorAll('.crm-branch-panel');
    const periodSelect = document.getElementById('period_type');
    
    const fields = {
        custom: [document.getElementById('dateFromField'), document.getElementById('dateToField')],
        month: [document.getElementById('yearField'), document.getElementById('monthField')],
        quarter: [document.getElementById('yearField'), document.getElementById('quarterField')],
        year: [document.getElementById('yearField')]
    };

    const syncFields = () => {
        const type = periodSelect.value;
        // Hide all fields first
        Object.values(fields).flat().forEach(el => {
            if (el) el.style.display = 'none';
        });
        // Show selected fields
        if (fields[type]) {
            fields[type].forEach(el => {
                if (el) el.style.display = 'flex';
            });
        }
    };

    periodSelect?.addEventListener('change', syncFields);
    syncFields();

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((btn) => btn.classList.remove('is-active'));
            panels.forEach((pnl) => pnl.classList.remove('is-active'));
            tab.classList.add('is-active');
            document.getElementById(tab.dataset.tabTarget)?.classList.add('is-active');
        });
    });

    const data = @json($comparisonData);
    const registry = {};

    const createChart = (id, config) => {
        const canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') return;
        if (registry[id]) registry[id].destroy();
        registry[id] = new Chart(canvas.getContext('2d'), config);
    };

    // 1. Leads Chart
    createChart('branchLeadsChart', {
        type: 'bar',
        data: {
            labels: data.rows.map(r => r.branch_name),
            datasets: [
                {
                    label: 'Total Leads',
                    data: data.rows.map(r => r.total_leads),
                    backgroundColor: '#4f46e5',
                    borderRadius: 8,
                },
                {
                    label: 'Converted Leads',
                    data: data.rows.map(r => r.converted_leads),
                    backgroundColor: '#10b981',
                    borderRadius: 8,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // 2. Revenue Chart
    const currency = (val) => 'Rs ' + Number(val).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    createChart('branchRevenueChart', {
        type: 'bar',
        data: {
            labels: data.rows.map(r => r.branch_name),
            datasets: [
                {
                    label: 'Sales Contract Value',
                    data: data.rows.map(r => r.revenue),
                    backgroundColor: '#6366f1',
                    borderRadius: 8,
                },
                {
                    label: 'Received Revenue',
                    data: data.rows.map(r => r.received),
                    backgroundColor: '#34d399',
                    borderRadius: 8,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${currency(ctx.parsed.y)}` } } },
            scales: { y: { beginAtZero: true, ticks: { callback: (val) => currency(val) } } }
        }
    });

    // 3. Lead Source Chart
    const sourceDatasets = data.all_sources.map((src, i) => {
        const colors = ['#4f46e5', '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#6b7280'];
        return {
            label: src,
            data: data.rows.map(r => r.sources[src] || 0),
            backgroundColor: colors[i % colors.length],
            borderRadius: 4,
        };
    });
    createChart('branchSourceChart', {
        type: 'bar',
        data: {
            labels: data.rows.map(r => r.branch_name),
            datasets: sourceDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true }
            }
        }
    });

    // 4. Lead Status Chart
    const statusDatasets = data.all_statuses.map((stat, i) => {
        const colors = ['#6366f1', '#60a5fa', '#34d399', '#fbbf24', '#f87171', '#a78bfa', '#f472b6', '#9ca3af'];
        return {
            label: stat,
            data: data.rows.map(r => r.statuses[stat] || 0),
            backgroundColor: colors[i % colors.length],
            borderRadius: 4,
        };
    });
    createChart('branchStatusChart', {
        type: 'bar',
        data: {
            labels: data.rows.map(r => r.branch_name),
            datasets: statusDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true }
            }
        }
    });
})();
</script>
@endpush
