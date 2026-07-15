@extends('layouts.app')

@section('title', 'Sales Comparison Report - myAgenci.ai')

@push('styles')
<style>
.sc-page {
    min-height: 100%;
    padding: 28px;
    background: linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.sc-shell {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.sc-card {
    background: #ffffff;
    border: 1px solid #e1dee3;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
}
.sc-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
}
.sc-title {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: #111827;
}
.sc-sub {
    margin: 6px 0 0;
    font-size: 13px;
    line-height: 1.7;
    color: #6b7280;
    max-width: 740px;
}
.sc-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}
.sc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.15s ease;
}
.sc-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    border-color: #fe5f04;
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(254, 95, 4, 0.25);
}
.sc-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(254, 95, 4, 0.35);
}
.sc-btn-ghost {
    background: #ffffff;
    border-color: #e1dee3;
    color: #374151;
}
.sc-btn-ghost:hover {
    border-color: #94a3b8;
    color: #111827;
}
.sc-filter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-end;
}
.sc-filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
    min-width: 180px;
}
.sc-filter-label {
    font-size: 11px;
    font-weight: 800;
    color: #9e9e9e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.sc-input {
    height: 38px;
    border: 1px solid #e1dee3;
    border-radius: 10px;
    padding: 0 12px;
    font-size: 13.5px;
    font-weight: 700;
    color: #111827;
    background: #fafafa;
    font-family: inherit;
    outline: none;
    transition: all 0.15s ease;
    width: 100%;
}
.sc-input:focus {
    border-color: #fe5f04;
    background: #ffffff;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.1);
}
.sc-section-title {
    font-size: 16px;
    font-weight: 800;
    color: #111827;
    margin-bottom: 20px;
    border-bottom: 2px solid #f3f4f6;
    padding-bottom: 10px;
}
.sc-chart-container {
    padding: 20px 0;
    border: 1px solid #f0eef2;
    border-radius: 14px;
    background: #fafafa;
    margin-bottom: 24px;
}
.chart-row {
    display: grid;
    grid-template-columns: 220px 1fr;
    align-items: center;
    margin-bottom: 16px;
    gap: 12px;
}
.chart-label {
    font-size: 13px;
    font-weight: 700;
    color: #374151;
    text-align: right;
    padding-right: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.chart-bar-area {
    display: grid;
    grid-template-columns: 1fr 2px 1fr;
    align-items: center;
    height: 36px;
    position: relative;
}
.chart-side {
    display: flex;
    align-items: center;
    height: 100%;
}
.negative-side {
    justify-content: flex-end;
    padding-right: 4px;
}
.positive-side {
    justify-content: flex-start;
    padding-left: 4px;
}
.chart-center-line {
    background: #9ca3af;
    height: 100%;
    width: 2px;
}
.chart-bar {
    height: 22px;
    border-radius: 4px;
    transition: width 0.3s ease;
}
.negative-bar {
    background: linear-gradient(270deg, #dc2626, #f87171);
    border-radius: 4px 0 0 4px;
}
.positive-bar {
    background: linear-gradient(90deg, #16a34a, #4ade80);
    border-radius: 0 4px 4px 0;
}
.chart-value {
    font-size: 11.5px;
    font-weight: 800;
    margin: 0 8px;
}
.negative-val {
    color: #dc2626;
}
.positive-val {
    color: #16a34a;
}
.sc-table-wrap {
    overflow-x: auto;
}
.sc-table {
    width: 100%;
    border-collapse: collapse;
}
.sc-table th {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.7px;
    color: #9e9e9e;
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #f0eef2;
    background: #fafafa;
}
.sc-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f7f6f9;
    font-size: 13.5px;
    color: #374151;
    vertical-align: middle;
}
.sc-table tbody tr:last-child td {
    border-bottom: none;
}
.sc-table tbody tr:hover td {
    background: #fffaf7;
}
.badge-variance {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
}
.badge-variance-positive {
    background: #dcfce7;
    color: #16a34a;
}
.badge-variance-negative {
    background: #fee2e2;
    color: #dc2626;
}
.badge-variance-neutral {
    background: #f3f4f6;
    color: #4b5563;
}
</style>
@endpush

@section('content')
<div class="sc-page">
    <div class="sc-shell">

        {{-- Header Card --}}
        <div class="sc-card">
            <div class="sc-head">
                <div>
                    <h2 class="sc-title">Sales Comparison Report</h2>
                    <p class="sc-sub">
                        Compare actual sales collections against allocated revenue targets branch-wise or user-wise. 
                        View performance variance visually and export the comparison data.
                    </p>
                </div>
                <div class="sc-actions">
                    <a href="{{ route('reports.crm.index') }}" class="sc-btn sc-btn-ghost">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                        Back to Reports
                    </a>
                    <a href="{{ route('reports.crm.sales-comparison.export', request()->query()) }}" class="sc-btn sc-btn-primary">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Export Excel
                    </a>
                </div>
            </div>
        </div>

        {{-- Filters Card --}}
        <div class="sc-card">
            <form method="GET" action="{{ route('reports.crm.sales-comparison') }}" id="scFilterForm" class="sc-filter-row">
                
                {{-- Period Wise Selection --}}
                <div class="sc-filter-group">
                    <label class="sc-filter-label">Period Type</label>
                    <select name="period_type" id="periodTypeSelect" class="sc-input">
                        <option value="month" @selected($periodType === 'month')>Month</option>
                        <option value="quarter" @selected($periodType === 'quarter')>Quarter</option>
                        <option value="year" @selected($periodType === 'year')>Year</option>
                    </select>
                </div>

                {{-- Month Picker --}}
                <div class="sc-filter-group picker-group" id="monthPickerGroup">
                    <label class="sc-filter-label">Select Month</label>
                    <input type="month" name="month" value="{{ $selectedMonth }}" class="sc-input" onchange="document.getElementById('scFilterForm').submit()">
                </div>

                {{-- Quarter Picker --}}
                <div class="sc-filter-group picker-group" id="quarterPickerGroup" style="display: none;">
                    <label class="sc-filter-label">Select Quarter</label>
                    <select name="quarter" class="sc-input" onchange="document.getElementById('scFilterForm').submit()">
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            @for($q = 1; $q <= 4; $q++)
                                <option value="{{ $y }}-Q{{ $q }}" @selected($selectedQuarter === "{$y}-Q{$q}")>Q{{ $q }} {{ $y }}</option>
                            @endfor
                        @endfor
                    </select>
                </div>

                {{-- Year Picker --}}
                <div class="sc-filter-group picker-group" id="yearPickerGroup" style="display: none;">
                    <label class="sc-filter-label">Select Year</label>
                    <select name="year" class="sc-input" onchange="document.getElementById('scFilterForm').submit()">
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" @selected($selectedYear == $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Scope Selection --}}
                <div class="sc-filter-group">
                    <label class="sc-filter-label">Compare By</label>
                    <select name="scope" id="scopeSelect" class="sc-input" onchange="document.getElementById('scFilterForm').submit()">
                        <option value="all_branches" @selected($scope === 'all_branches')>All Branches</option>
                        <option value="particular_branch" @selected($scope === 'particular_branch')>Particular Branch (Users)</option>
                        <option value="all_users" @selected($scope === 'all_users')>All Users</option>
                    </select>
                </div>

                {{-- Particular Branch Picker --}}
                <div class="sc-filter-group" id="branchGroup" style="display: none;">
                    <label class="sc-filter-label">Select Branch</label>
                    <select name="branch_id" class="sc-input" onchange="document.getElementById('scFilterForm').submit()">
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" @selected($selectedBranchId == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <button type="submit" class="sc-btn sc-btn-ghost" style="height: 38px;">
                        <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Filter
                    </button>
                </div>

            </form>
        </div>

        {{-- Comparison Chart Card --}}
        <div class="sc-card">
            <div class="sc-section-title">Variance Chart (Actual - Target)</div>

            @if(empty($comparisonData))
                <div style="text-align: center; padding: 40px 0; color: #9e9e9e;">
                    <div style="font-size: 36px; margin-bottom: 10px;">📊</div>
                    <strong>No comparison data available for the selected filters.</strong>
                </div>
            @else
                @php
                    $maxDiff = max(collect($comparisonData)->map(fn($x) => abs($x['difference']))->push(1)->all());
                @endphp

                <div class="sc-chart-container">
                    @foreach($comparisonData as $item)
                        @php
                            $diff = $item['difference'];
                            $percent = min(100, ($maxDiff > 0 ? (abs($diff) / $maxDiff) * 100 : 0));
                        @endphp
                        <div class="chart-row">
                            <div class="chart-label" title="{{ $item['label'] }}">{{ $item['label'] }}</div>
                            <div class="chart-bar-area">
                                {{-- Negative side (extends left) --}}
                                <div class="chart-side negative-side">
                                    @if($diff < 0)
                                        <span class="chart-value negative-val">-₹{{ number_format(abs($diff), 2) }}</span>
                                        <div class="chart-bar negative-bar" style="width: {{ $percent }}%;"></div>
                                    @endif
                                </div>
                                {{-- Center Line --}}
                                <div class="chart-center-line"></div>
                                {{-- Positive side (extends right) --}}
                                <div class="chart-side positive-side">
                                    @if($diff >= 0)
                                        <div class="chart-bar positive-bar" style="width: {{ $percent }}%;"></div>
                                        <span class="chart-value positive-val">+₹{{ number_format($diff, 2) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Details Table --}}
                <div class="sc-section-title" style="margin-top: 20px;">Data Details</div>
                <div class="sc-table-wrap">
                    <table class="sc-table">
                        <thead>
                            <tr>
                                <th>Name / Branch</th>
                                <th style="text-align: right;">Target Revenue</th>
                                <th style="text-align: right;">Actual Collection</th>
                                <th style="text-align: right; width: 220px;">Variance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comparisonData as $item)
                                @php
                                    $diff = $item['difference'];
                                    $badgeClass = $diff > 0 ? 'badge-variance-positive' : ($diff < 0 ? 'badge-variance-negative' : 'badge-variance-neutral');
                                    $prefix = $diff > 0 ? '+' : '';
                                @endphp
                                <tr>
                                    <td style="font-weight: 700;">{{ $item['label'] }}</td>
                                    <td style="text-align: right; font-family: monospace;">₹{{ number_format($item['target'], 2) }}</td>
                                    <td style="text-align: right; font-family: monospace;">₹{{ number_format($item['actual'], 2) }}</td>
                                    <td style="text-align: right;">
                                        <span class="badge-variance {{ $badgeClass }}">
                                            @if($diff > 0)
                                                ▲
                                            @elseif($diff < 0)
                                                ▼
                                            @endif
                                            {{ $prefix }}₹{{ number_format($diff, 2) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodType = document.getElementById('periodTypeSelect');
    const monthGroup = document.getElementById('monthPickerGroup');
    const quarterGroup = document.getElementById('quarterPickerGroup');
    const yearGroup = document.getElementById('yearPickerGroup');

    function togglePickers() {
        monthGroup.style.display = 'none';
        quarterGroup.style.display = 'none';
        yearGroup.style.display = 'none';

        if (periodType.value === 'month') {
            monthGroup.style.display = 'block';
        } else if (periodType.value === 'quarter') {
            quarterGroup.style.display = 'block';
        } else if (periodType.value === 'year') {
            yearGroup.style.display = 'block';
        }
    }

    periodType.addEventListener('change', togglePickers);
    togglePickers(); // init

    const scopeSelect = document.getElementById('scopeSelect');
    const branchGroup = document.getElementById('branchGroup');

    function toggleBranchGroup() {
        if (scopeSelect.value === 'particular_branch') {
            branchGroup.style.display = 'block';
        } else {
            branchGroup.style.display = 'none';
        }
    }

    scopeSelect.addEventListener('change', toggleBranchGroup);
    toggleBranchGroup(); // init
});
</script>
@endsection
