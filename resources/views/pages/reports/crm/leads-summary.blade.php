@extends('layouts.app')

@section('title', 'CRM Leads Summary - myAgenci.ai')

@push('styles')
<style>
.crm-summary-page { min-height: 100%; padding: 24px; background: linear-gradient(180deg, #f7f5f1 0%, #f3f6fa 100%); }
.crm-summary-shell { max-width: 1440px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
.crm-summary-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px; border: 1px solid #e7e5e4; border-radius: 24px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #eff6ff 100%); box-shadow: 0 14px 38px rgba(15, 23, 42, 0.05); }
.crm-summary-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; border-radius: 999px; background: #ffedd5; color: #c2410c; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-summary-title { margin: 14px 0 6px; font-size: 30px; font-weight: 800; color: #111827; }
.crm-summary-subtitle { margin: 0; font-size: 14px; line-height: 1.7; color: #6b7280; max-width: 720px; }
.crm-summary-actions { display: flex; align-items: center; gap: 10px; }
.crm-summary-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 12px; border: 1px solid #e5e7eb; background: #fff; color: #111827; font-size: 13px; font-weight: 800; text-decoration: none; }
.crm-summary-btn:hover { border-color: #fdba74; background: #fff7ed; color: #c2410c; }
.crm-summary-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.crm-summary-stat { position: relative; overflow: hidden; padding: 20px; border-radius: 22px; border: 1px solid #e7e5e4; background: linear-gradient(180deg, rgba(255,255,255,.98) 0%, rgba(248,250,252,.98) 100%); box-shadow: 0 14px 32px rgba(15, 23, 42, 0.05); }
.crm-summary-stat::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 5px; border-radius: 999px; background: var(--stat-accent, #f97316); }
.crm-summary-stat::after { content: ''; position: absolute; right: -28px; top: -28px; width: 110px; height: 110px; border-radius: 50%; background: color-mix(in srgb, var(--stat-soft, #ffedd5) 75%, white 25%); opacity: .85; }
.crm-summary-stat-top { position: relative; z-index: 1; display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; }
.crm-summary-stat-icon { width: 46px; height: 46px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; background: var(--stat-soft, #ffedd5); color: var(--stat-accent, #f97316); box-shadow: inset 0 1px 0 rgba(255,255,255,.75); }
.crm-summary-stat-icon svg { width: 20px; height: 20px; }
.crm-summary-stat-chip { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; border: 1px solid color-mix(in srgb, var(--stat-accent, #f97316) 22%, white 78%); color: var(--stat-accent, #f97316); background: rgba(255,255,255,.88); font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.crm-summary-stat-label { position: relative; z-index: 1; margin-top: 16px; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
.crm-summary-stat-value { position: relative; z-index: 1; margin-top: 10px; font-size: 28px; font-weight: 900; color: #0f172a; letter-spacing: -.03em; }
.crm-summary-stat-note { position: relative; z-index: 1; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(226, 232, 240, .9); font-size: 12px; color: #64748b; }
.crm-summary-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-summary-filter-head, .crm-summary-table-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 16px 18px; border-bottom: 1px solid #f1f5f9; }
.crm-summary-card-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-summary-card-subtitle { margin-top: 3px; font-size: 12px; color: #6b7280; }
.crm-summary-chip { display: inline-flex; align-items: center; gap: 6px; padding: 7px 10px; border-radius: 999px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 11px; font-weight: 800; }
.crm-summary-filter-body { padding: 18px; }
.crm-summary-form { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 14px; }
.crm-summary-field { display: flex; flex-direction: column; gap: 7px; }
.crm-summary-label { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; }
.crm-summary-input, .crm-summary-select { width: 100%; padding: 11px 13px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #0f172a; font-size: 13px; outline: none; }
.crm-summary-input:focus, .crm-summary-select:focus { border-color: #fb923c; box-shadow: 0 0 0 4px rgba(251, 146, 60, 0.12); background: #fff; }
.crm-summary-form-actions { display: flex; align-items: flex-end; gap: 10px; }
.crm-summary-btn-primary { border-color: transparent; background: linear-gradient(135deg, #f97316, #fb923c); color: #fff; box-shadow: 0 6px 18px rgba(249, 115, 22, 0.22); }
.crm-summary-btn-primary:hover { color: #fff; border-color: transparent; background: linear-gradient(135deg, #ea580c, #f97316); }
.crm-summary-tabs { display: inline-flex; align-items: center; gap: 10px; padding: 10px; border-radius: 20px; background: linear-gradient(135deg, #fff7ed 0%, #ffffff 55%, #eff6ff 100%); border: 1px solid #fed7aa; box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255,255,255,.9); width: fit-content; }
.crm-summary-tab { padding: 12px 22px; border-radius: 14px; border: 1px solid transparent; background: rgba(255,255,255,.72); color: #475569; font-size: 13px; font-weight: 900; letter-spacing: .02em; box-shadow: inset 0 1px 0 rgba(255,255,255,.9); transition: all .18s ease; }
.crm-summary-tab:hover { border-color: #fdba74; background: #ffffff; color: #c2410c; transform: translateY(-1px); }
.crm-summary-tab.is-active { background: linear-gradient(135deg, #f97316, #fb923c); color: #fff; border-color: #ea580c; box-shadow: 0 10px 22px rgba(249, 115, 22, 0.24); }
.crm-summary-panel { display: none; flex-direction: column; gap: 18px; }
.crm-summary-panel.is-active { display: flex; }
.crm-analytics-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
.crm-analytics-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 20px; padding: 18px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
.crm-analytics-card.full { grid-column: 1 / -1; }
.crm-analytics-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.crm-analytics-title { font-size: 15px; font-weight: 800; color: #111827; }
.crm-analytics-subtitle { margin-top: 4px; font-size: 12px; color: #6b7280; }
.crm-analytics-pill { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; font-size: 11px; font-weight: 800; }
.crm-analytics-chart { position: relative; min-height: 280px; }
.crm-analytics-chart.tall { min-height: 340px; }
.crm-insight-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.crm-insight-card { padding: 16px; border-radius: 18px; border: 1px solid #e2e8f0; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
.crm-insight-label { font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
.crm-insight-value { margin-top: 8px; font-size: 22px; font-weight: 900; color: #0f172a; }
.crm-insight-note { margin-top: 6px; font-size: 12px; color: #64748b; line-height: 1.5; }
.crm-summary-table-wrap { overflow-x: auto; }
.crm-summary-table { width: 100%; border-collapse: collapse; min-width: 1540px; }
.crm-summary-table th { padding: 12px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #64748b; white-space: nowrap; }
.crm-summary-table td { padding: 14px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #111827; vertical-align: middle; }
.crm-summary-table tbody tr:hover td { background: #fffaf5; }
.crm-summary-id { font-family: Consolas, monospace; font-size: 12px; color: #9ca3af; }
.crm-summary-name { font-weight: 800; color: #111827; }
.crm-summary-muted { color: #6b7280; font-size: 12px; }
.crm-summary-status { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 999px; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11px; font-weight: 800; }
.crm-summary-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
.crm-summary-money { font-weight: 800; white-space: nowrap; }
.crm-summary-empty { padding: 56px 20px; text-align: center; color: #6b7280; }
.crm-summary-empty strong { display: block; margin-bottom: 8px; font-size: 18px; color: #111827; }
@media (max-width: 1280px) {
    .crm-summary-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .crm-summary-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .crm-analytics-grid, .crm-insight-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .crm-summary-page { padding: 18px; }
    .crm-summary-topbar { flex-direction: column; align-items: flex-start; }
    .crm-summary-stats, .crm-summary-form { grid-template-columns: 1fr; }
    .crm-summary-form-actions { flex-wrap: wrap; }
    .crm-summary-filter-head, .crm-summary-table-head { flex-direction: column; align-items: flex-start; }
    .crm-summary-tabs { width: 100%; flex-wrap: wrap; }
}
</style>
@endpush

@section('content')
@php
    $hasCustomFilters =
        request()->filled('lead_source')
        || request()->filled('lead_status')
        || request()->filled('assigned_to')
        || request()->filled('product_id')
        || request()->filled('branch_id')
        || request('date_from') !== $defaultFromDate
        || request('date_to') !== $defaultToDate;
@endphp
<div class="crm-summary-page">
    <div class="crm-summary-shell">
        <div class="crm-summary-topbar">
            <div>
                <span class="crm-summary-kicker">CRM Reports</span>
                <h1 class="crm-summary-title">Leads Summary Report</h1>
                <p class="crm-summary-subtitle">Lead source, lead status, owner, date, and product-wise summary with payment visibility in one report sheet.</p>
            </div>
            <div class="crm-summary-actions">
                <a href="{{ route('reports.crm.leads-summary.export', request()->query()) }}" class="crm-summary-btn crm-summary-btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/>
                        <path d="m7 10 5 5 5-5"/>
                        <path d="M5 21h14"/>
                    </svg>
                    Export Excel
                </a>
                <a href="{{ route('reports.crm.index') }}" class="crm-summary-btn">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Back to Reports
                </a>
            </div>
        </div>

        <div class="crm-summary-stats">
            <div class="crm-summary-stat" style="--stat-accent:#f97316;--stat-soft:#ffedd5;">
                <div class="crm-summary-stat-top">
                    <span class="crm-summary-stat-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path d="M4 19h16"/>
                            <path d="M7 16V8"/>
                            <path d="M12 16V5"/>
                            <path d="M17 16v-4"/>
                        </svg>
                    </span>
                    <span class="crm-summary-stat-chip">Volume</span>
                </div>
                <div class="crm-summary-stat-label">Report Rows</div>
                <div class="crm-summary-stat-value">{{ number_format($summary['rows']) }}</div>
                <div class="crm-summary-stat-note">Current filter set-ku visible lead summary rows.</div>
            </div>
            <div class="crm-summary-stat" style="--stat-accent:#2563eb;--stat-soft:#dbeafe;">
                <div class="crm-summary-stat-top">
                    <span class="crm-summary-stat-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <line x1="12" y1="2" x2="12" y2="22"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H7"/>
                        </svg>
                    </span>
                    <span class="crm-summary-stat-chip">Value</span>
                </div>
                <div class="crm-summary-stat-label">Total Cost</div>
                <div class="crm-summary-stat-value">Rs {{ number_format($summary['total_cost'], 2) }}</div>
                <div class="crm-summary-stat-note">All matched lead-product value combined together.</div>
            </div>
            <div class="crm-summary-stat" style="--stat-accent:#16a34a;--stat-soft:#dcfce7;">
                <div class="crm-summary-stat-top">
                    <span class="crm-summary-stat-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <path d="M12 2v20"/>
                            <path d="M17 6H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"/>
                            <path d="m7 12 3 3 7-7"/>
                        </svg>
                    </span>
                    <span class="crm-summary-stat-chip">Collected</span>
                </div>
                <div class="crm-summary-stat-label">Received Cost</div>
                <div class="crm-summary-stat-value">Rs {{ number_format($summary['received_cost'], 2) }}</div>
                <div class="crm-summary-stat-note">Snapshot of amount received from clients.</div>
            </div>
            <div class="crm-summary-stat" style="--stat-accent:#dc2626;--stat-soft:#fee2e2;">
                <div class="crm-summary-stat-top">
                    <span class="crm-summary-stat-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 8v4"/>
                            <path d="M12 16h.01"/>
                        </svg>
                    </span>
                    <span class="crm-summary-stat-chip">Balance</span>
                </div>
                <div class="crm-summary-stat-label">Pending Cost</div>
                <div class="crm-summary-stat-value">Rs {{ number_format($summary['pending_cost'], 2) }}</div>
                <div class="crm-summary-stat-note">Follow-up required outstanding amount total.</div>
            </div>
        </div>

        <div class="crm-summary-tabs" id="crmSummaryTabs">
            <button type="button" class="crm-summary-tab is-active" data-tab-target="data-panel">Data</button>
            <button type="button" class="crm-summary-tab" data-tab-target="analytics-panel">Analytics</button>
        </div>

        <div class="crm-summary-card">
            <div class="crm-summary-filter-head">
                <div>
                    <div class="crm-summary-card-title">Filters</div>
                    <div class="crm-summary-card-subtitle">Lead Source, Lead Status, User-wise, Date-wise, Product-wise</div>
                </div>
                <div class="crm-summary-chip">{{ $reportRows->total() }} results</div>
            </div>
            <div class="crm-summary-filter-body">
                <form method="GET" action="{{ route('reports.crm.leads-summary') }}" class="crm-summary-form">
                    <div class="crm-summary-field">
                        <label class="crm-summary-label" for="lead_source">Lead Source</label>
                        <select id="lead_source" name="lead_source" class="crm-summary-select">
                            <option value="">All Sources</option>
                            @foreach($sourceOptions as $source)
                                <option value="{{ $source }}" @selected(request('lead_source') === $source)>{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-summary-field">
                        <label class="crm-summary-label" for="lead_status">Lead Status</label>
                        <select id="lead_status" name="lead_status" class="crm-summary-select">
                            <option value="">All Statuses</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" @selected(request('lead_status') === $status)>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-summary-field">
                        <label class="crm-summary-label" for="assigned_to">User-wise</label>
                        <select id="assigned_to" name="assigned_to" class="crm-summary-select">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-summary-field">
                        <label class="crm-summary-label" for="branch_id">Branch</label>
                        <select id="branch_id" name="branch_id" class="crm-summary-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-summary-field">
                        <label class="crm-summary-label" for="date_from">Date From</label>
                        <input id="date_from" type="date" name="date_from" class="crm-summary-input" value="{{ request('date_from', $defaultFromDate) }}">
                    </div>

                    <div class="crm-summary-field">
                        <label class="crm-summary-label" for="date_to">Date To</label>
                        <input id="date_to" type="date" name="date_to" class="crm-summary-input" value="{{ request('date_to', $defaultToDate) }}">
                    </div>

                    <div class="crm-summary-field">
                        <label class="crm-summary-label" for="product_id">Product-wise</label>
                        <select id="product_id" name="product_id" class="crm-summary-select">
                            <option value="">All Products</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}" @selected((string) request('product_id') === (string) $product->id)>
                                    {{ $product->package_name ?: $product->product_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="crm-summary-form-actions">
                        <button type="submit" class="crm-summary-btn crm-summary-btn-primary">Apply Filters</button>
                        @if($hasCustomFilters)
                            <a href="{{ route('reports.crm.leads-summary') }}" class="crm-summary-btn">Reset</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="crm-summary-panel is-active" id="data-panel">
            <div class="crm-summary-card">
                <div class="crm-summary-table-head">
                    <div>
                        <div class="crm-summary-card-title">Lead Summary Sheet</div>
                        <div class="crm-summary-card-subtitle">Showing {{ $reportRows->firstItem() ?? 0 }}-{{ $reportRows->lastItem() ?? 0 }} of {{ $reportRows->total() }} rows</div>
                    </div>
                </div>

                @if($reportRows->isEmpty())
                    <div class="crm-summary-empty">
                        <strong>No lead summary rows found</strong>
                        Try changing the filters to load a wider date range or more sources.
                    </div>
                @else
                    <div class="crm-summary-table-wrap">
                        <table class="crm-summary-table">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile Number</th>
                                    <th>Lead Source</th>
                                    <th>Lead Status</th>
                                    <th>Product Name</th>
                                    <th>Entry Date</th>
                                    <th>Converted Date</th>
                                    <th>Total Cost</th>
                                    <th>Received Cost</th>
                                    <th>Pending Cost</th>
                                    <th>Allocated To</th>
                                    <th>Lead Age</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reportRows as $row)
                                    @php
                                        $entryDate = $row->lead_date ?? optional($row->lead_created_at)?->toDateString();
                                        $entryCarbon = $entryDate ? \Illuminate\Support\Carbon::parse($entryDate) : null;
                                        $convertedCarbon = $row->converted_at ? \Illuminate\Support\Carbon::parse($row->converted_at) : null;
                                        $leadStatus = $row->product_lead_status ?: $row->base_lead_status;
                                        $pendingCost = max(0, (float) ($row->total_price ?? 0) - (float) ($row->amount_paid ?? 0));
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ route('leads.show', $row->lead_id) }}" class="crm-summary-id">LD-{{ str_pad($row->lead_id, 4, '0', STR_PAD_LEFT) }}</a>
                                        </td>
                                        <td>
                                            <div class="crm-summary-name">{{ $row->contact_name ?: '-' }}</div>
                                        </td>
                                        <td class="crm-summary-muted">{{ $row->email ?: '-' }}</td>
                                        <td>{{ $row->mobile_number ?: '-' }}</td>
                                        <td>{{ $row->lead_source ?: '-' }}</td>
                                        <td>
                                            <span class="crm-summary-status">
                                                <span class="crm-summary-dot"></span>
                                                {{ $leadStatus ?: '-' }}
                                            </span>
                                        </td>
                                        <td>{{ $row->product_name ?: '-' }}</td>
                                        <td>{{ $entryCarbon?->format('d M Y') ?: '-' }}</td>
                                        <td>{{ $convertedCarbon?->format('d M Y') ?: '-' }}</td>
                                        <td class="crm-summary-money">Rs {{ number_format((float) ($row->total_price ?? 0), 2) }}</td>
                                        <td class="crm-summary-money" style="color:#15803d;">Rs {{ number_format((float) ($row->amount_paid ?? 0), 2) }}</td>
                                        <td class="crm-summary-money" style="color:#dc2626;">Rs {{ number_format($pendingCost, 2) }}</td>
                                        <td>{{ $row->allocated_to_name ?: '-' }}</td>
                                        <td>{{ $entryCarbon ? $entryCarbon->diffForHumans(now(), true) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($reportRows->hasPages())
                        @include('partials.table-pagination', ['paginator' => $reportRows])
                    @endif
                @endif
            </div>
        </div>

        <div class="crm-summary-panel" id="analytics-panel">
            <div class="crm-insight-grid">
                @php
                    $topSource = collect($analytics['sources'])->first();
                    $topStatus = collect($analytics['statuses'])->first();
                    $topOwner = collect($analytics['owners'])->first();
                @endphp
                <div class="crm-insight-card">
                    <div class="crm-insight-label">Top Lead Source</div>
                    <div class="crm-insight-value">{{ $topSource['label'] ?? 'N/A' }}</div>
                    <div class="crm-insight-note">{{ number_format($topSource['count'] ?? 0) }} leads currently dominate this filter view.</div>
                </div>
                <div class="crm-insight-card">
                    <div class="crm-insight-label">Top Lead Status</div>
                    <div class="crm-insight-value">{{ $topStatus['label'] ?? 'N/A' }}</div>
                    <div class="crm-insight-note">{{ number_format($topStatus['count'] ?? 0) }} rows are sitting in this stage right now.</div>
                </div>
                <div class="crm-insight-card">
                    <div class="crm-insight-label">Top Owner</div>
                    <div class="crm-insight-value">{{ $topOwner['label'] ?? 'N/A' }}</div>
                    <div class="crm-insight-note">This owner currently holds the highest visible lead volume.</div>
                </div>
            </div>

            <div class="crm-analytics-grid">
                <div class="crm-analytics-card full">
                    <div class="crm-analytics-head">
                        <div>
                            <div class="crm-analytics-title">Lead Trend Overview</div>
                            <div class="crm-analytics-subtitle">Month-wise lead volume and received value across the selected filters.</div>
                        </div>
                        <span class="crm-analytics-pill">{{ count($analytics['monthly_trend']) }} periods</span>
                    </div>
                    <div class="crm-analytics-chart tall">
                        <canvas id="leadTrendChart"></canvas>
                    </div>
                </div>

                <div class="crm-analytics-card">
                    <div class="crm-analytics-head">
                        <div>
                            <div class="crm-analytics-title">Lead Source Analysis</div>
                            <div class="crm-analytics-subtitle">Which channels are bringing the most leads.</div>
                        </div>
                        <span class="crm-analytics-pill">Top {{ count($analytics['sources']) }}</span>
                    </div>
                    <div class="crm-analytics-chart">
                        <canvas id="leadSourceChart"></canvas>
                    </div>
                </div>

                <div class="crm-analytics-card">
                    <div class="crm-analytics-head">
                        <div>
                            <div class="crm-analytics-title">Lead Status Analysis</div>
                            <div class="crm-analytics-subtitle">Stage-wise spread of current visible leads.</div>
                        </div>
                        <span class="crm-analytics-pill">Top {{ count($analytics['statuses']) }}</span>
                    </div>
                    <div class="crm-analytics-chart">
                        <canvas id="leadStatusChart"></canvas>
                    </div>
                </div>

                <div class="crm-analytics-card">
                    <div class="crm-analytics-head">
                        <div>
                            <div class="crm-analytics-title">User-wise Lead Analysis</div>
                            <div class="crm-analytics-subtitle">Allocated users with the highest lead count.</div>
                        </div>
                        <span class="crm-analytics-pill">Top {{ count($analytics['owners']) }}</span>
                    </div>
                    <div class="crm-analytics-chart">
                        <canvas id="leadOwnerChart"></canvas>
                    </div>
                </div>

                <div class="crm-analytics-card">
                    <div class="crm-analytics-head">
                        <div>
                            <div class="crm-analytics-title">Product-wise Lead Analysis</div>
                            <div class="crm-analytics-subtitle">Product demand and associated visible row volume.</div>
                        </div>
                        <span class="crm-analytics-pill">Top {{ count($analytics['products']) }}</span>
                    </div>
                    <div class="crm-analytics-chart">
                        <canvas id="leadProductChart"></canvas>
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
    const tabs = document.querySelectorAll('[data-tab-target]');
    const panels = document.querySelectorAll('.crm-summary-panel');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((button) => button.classList.remove('is-active'));
            panels.forEach((panel) => panel.classList.remove('is-active'));

            tab.classList.add('is-active');
            document.getElementById(tab.dataset.tabTarget)?.classList.add('is-active');
        });
    });

    const analytics = @json($analytics);
    const chartRegistry = {};

    const createChart = (id, config) => {
        const canvas = document.getElementById(id);
        if (!canvas || typeof Chart === 'undefined') {
            return;
        }

        if (chartRegistry[id]) {
            chartRegistry[id].destroy();
        }

        chartRegistry[id] = new Chart(canvas.getContext('2d'), config);
    };

    const currency = (value) => 'Rs ' + Number(value || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 });
    const volumeColor = ['#f97316', '#fb923c', '#fdba74', '#fed7aa', '#ffedd5', '#ea580c'];
    const coolPalette = ['#2563eb', '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', '#1d4ed8'];
    const greenPalette = ['#16a34a', '#22c55e', '#4ade80', '#86efac', '#bbf7d0', '#15803d'];

    createChart('leadTrendChart', {
        type: 'line',
        data: {
            labels: analytics.monthly_trend.map(item => item.label),
            datasets: [
                {
                    label: 'Lead Count',
                    data: analytics.monthly_trend.map(item => item.count),
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.12)',
                    fill: true,
                    tension: 0.35,
                    yAxisID: 'y',
                },
                {
                    label: 'Received Cost',
                    data: analytics.monthly_trend.map(item => item.received_cost),
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.08)',
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
                        label: (ctx) => ctx.dataset.label === 'Lead Count'
                            ? `${ctx.dataset.label}: ${ctx.parsed.y}`
                            : `${ctx.dataset.label}: ${currency(ctx.parsed.y)}`
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Lead Count' } },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { callback: (value) => currency(value) },
                    title: { display: true, text: 'Received Cost' }
                }
            }
        }
    });

    createChart('leadSourceChart', {
        type: 'doughnut',
        data: {
            labels: analytics.sources.map(item => item.label),
            datasets: [{
                data: analytics.sources.map(item => item.count),
                backgroundColor: volumeColor,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed} leads` } }
            }
        }
    });

    createChart('leadStatusChart', {
        type: 'bar',
        data: {
            labels: analytics.statuses.map(item => item.label),
            datasets: [{
                label: 'Lead Count',
                data: analytics.statuses.map(item => item.count),
                backgroundColor: coolPalette,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    createChart('leadOwnerChart', {
        type: 'bar',
        data: {
            labels: analytics.owners.map(item => item.label),
            datasets: [{
                label: 'Lead Count',
                data: analytics.owners.map(item => item.count),
                backgroundColor: greenPalette,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });

    createChart('leadProductChart', {
        type: 'bar',
        data: {
            labels: analytics.products.map(item => item.label),
            datasets: [{
                label: 'Lead Count',
                data: analytics.products.map(item => item.count),
                backgroundColor: ['#7c3aed', '#8b5cf6', '#a78bfa', '#c4b5fd', '#ddd6fe', '#6d28d9'],
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
})();
</script>
@endpush
