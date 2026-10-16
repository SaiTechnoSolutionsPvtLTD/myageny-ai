@extends('layouts.app')

@section('title', 'Customer Balance Sheet - Accounts Reports')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.inv-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.inv-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:60px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.inv-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.inv-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.inv-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.inv-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }

@media (max-width: 900px) {
    .inv-topbar { padding:14px 20px; flex-direction:column; align-items:flex-start; gap:12px; min-height:auto; }
    .inv-body { padding:16px 20px 24px; }
}

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }
.acm-btn-pdf { background:#ef4444; color:#fff; box-shadow:0 3px 10px rgba(239,68,68,0.25); }
.acm-btn-pdf:hover { background:#dc2626; color:#fff; }
.acm-btn-print { background:#0284c7; color:#fff; box-shadow:0 3px 10px rgba(2,132,199,0.25); }
.acm-btn-print:hover { background:#0369a1; color:#fff; }

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:20px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.1; }
.acm-stat-sub { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }

/* Filter Section */
.filter-card { background:#fff; border-radius:14px; border:1px solid #e2e8f0; padding:16px 20px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.filter-grid { display:grid; grid-template-columns:minmax(260px, 2fr) minmax(160px, 1fr) minmax(160px, 1fr) auto; gap:14px; align-items:flex-end; }
@media (max-width: 992px) {
    .filter-grid { grid-template-columns:1fr; }
}
.f-label { display:block; font-size:11px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.04em; margin-bottom:5px; }
.f-input { width:100%; height:40px; border-radius:8px; border:1px solid #cbd5e1; padding:0 12px; font-size:13px; color:#1e293b; background:#fff; outline:none; transition:border-color .15s; }
.f-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

/* Select2 Customization */
.select2-container .select2-selection--single { height:40px !important; border:1px solid #cbd5e1 !important; border-radius:8px !important; display:flex !important; align-items:center !important; }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height:38px !important; color:#1e293b !important; font-size:13px !important; font-weight:600 !important; padding-left:12px !important; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height:38px !important; right:8px !important; }

/* Quick Presets Pills */
.presets-wrap { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:12px; padding-top:12px; border-top:1px dashed #e2e8f0; }
.preset-pill { font-size:11.5px; font-weight:600; padding:4px 10px; border-radius:6px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; cursor:pointer; text-decoration:none; transition:all .15s; }
.preset-pill:hover { background:#fee2e2; border-color:#fca5a5; color:#b91c1c; }
.preset-pill.active { background:#fff7ed; border-color:#fed7aa; color:#c2410c; font-weight:700; }

/* Customer Info Banner */
.customer-banner { background:linear-gradient(135deg, #ffffff, #fafafa); border:1px solid #e2e8f0; border-radius:14px; padding:18px 22px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; }
.cb-left { display:flex; align-items:center; gap:14px; min-width:0; }
.cb-avatar { width:52px; height:52px; border-radius:14px; background:linear-gradient(135deg, #fe5f04, #ff7c30); color:#fff; display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:800; flex-shrink:0; }
.cb-name { font-size:17px; font-weight:800; color:#0f172a; line-height:1.2; }
.cb-company { font-size:13px; font-weight:600; color:#64748b; margin-top:2px; }
.cb-meta { display:flex; align-items:center; gap:12px; font-size:12px; color:#475569; margin-top:6px; flex-wrap:wrap; }
.cb-meta span { display:inline-flex; align-items:center; gap:4px; }
.cb-right { display:flex; flex-direction:column; align-items:flex-end; gap:4px; text-align:right; }
.cb-bal-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.04em; }
.cb-bal-val { font-size:24px; font-weight:900; color:#ea580c; line-height:1.1; }

/* Ledger Table */
.ledger-tbl-wrap { width:100%; overflow-x:auto; }
.ledger-tbl { width:100%; border-collapse:collapse; font-size:13px; text-align:left; }
.ledger-tbl thead th { background:#f8fafc; color:#475569; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.05em; padding:12px 16px; border-bottom:1px solid #e2e8f0; white-space:nowrap; }
.ledger-tbl tbody td { padding:13px 16px; border-bottom:1px solid #f1f5f9; color:#1e293b; vertical-align:middle; }
.ledger-tbl tbody tr:hover td { background:#fafbfc; }
.ledger-tbl tbody tr.opening-row td { background:#f8fafc; font-weight:700; color:#334155; border-bottom:2px solid #e2e8f0; }
.ledger-tbl tfoot td { background:#f8fafc; font-weight:800; color:#0f172a; padding:14px 16px; border-top:2px solid #cbd5e1; border-bottom:2px solid #cbd5e1; }

.text-right { text-align:right !important; }
.text-center { text-align:center !important; }
.amount-debit { color:#1e293b; font-weight:700; font-variant-numeric:tabular-nums; }
.amount-credit { color:#059669; font-weight:700; font-variant-numeric:tabular-nums; }
.amount-balance { font-weight:800; font-variant-numeric:tabular-nums; }
.badge-custom { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.02em; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-file-earmark-spreadsheet-fill" style="color:#fe5f04;"></i>
                <span>Customer Balance Sheet</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <span>Reports</span>
                <span>/</span>
                <span style="color:#fe5f04; font-weight:600;">Customer Balance Sheet</span>
            </div>
        </div>

        <div class="inv-actions">
            @if(!empty($selectedCustomer))
                <a href="{{ route('accounts.reports.customer-balance-sheet.print', request()->query()) }}" target="_blank" class="acm-btn acm-btn-print">
                    <i class="bi bi-printer-fill"></i>
                    <span>Print Statement</span>
                </a>
                <a href="{{ route('accounts.reports.customer-balance-sheet.export-pdf', request()->query()) }}" class="acm-btn acm-btn-pdf">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                    <span>Export PDF</span>
                </a>
            @endif
        </div>
    </div>

    <div class="inv-body">

        {{-- Filter Card --}}
        <div class="filter-card">
            <form method="GET" action="{{ route('accounts.reports.customer-balance-sheet') }}" id="filterForm">
                <div class="filter-grid">
                    <div>
                        <label class="f-label" for="customerSelect">
                            <i class="bi bi-person-fill"></i> Select Customer
                        </label>
                        <select name="customer_id" id="customerSelect" class="f-input">
                            <option value="all" {{ empty($selectedCustomer) ? 'selected' : '' }}>
                                — All Customers (Consolidated Summary) —
                            </option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ (isset($selectedCustomer) && $selectedCustomer->id == $c->id) ? 'selected' : '' }}>
                                    {{ $c->customer_name }} {{ $c->company_name ? '('.$c->company_name.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="f-label" for="dateFrom">
                            <i class="bi bi-calendar3"></i> From Date
                        </label>
                        <input type="date" name="date_from" id="dateFrom" value="{{ $dateFrom }}" class="f-input">
                    </div>

                    <div>
                        <label class="f-label" for="dateTo">
                            <i class="bi bi-calendar3"></i> To Date
                        </label>
                        <input type="date" name="date_to" id="dateTo" value="{{ $dateTo }}" class="f-input">
                    </div>

                    <div style="display:flex; gap:8px;">
                        <button type="submit" class="acm-btn acm-btn-primary" style="height:40px; white-space:nowrap;">
                            <i class="bi bi-funnel-fill"></i> Generate
                        </button>
                        <a href="{{ route('accounts.reports.customer-balance-sheet') }}" class="acm-btn acm-btn-secondary" style="height:40px;" title="Reset filters">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    </div>
                </div>

                {{-- Quick Presets --}}
                <div class="presets-wrap">
                    <span style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase;">Quick Ranges:</span>
                    <button type="button" class="preset-pill" onclick="setPreset('fy')">This Financial Year</button>
                    <button type="button" class="preset-pill" onclick="setPreset('month')">This Month</button>
                    <button type="button" class="preset-pill" onclick="setPreset('last_month')">Last Month</button>
                    <button type="button" class="preset-pill" onclick="setPreset('all_time')">All Time</button>
                </div>
            </form>
        </div>

        @if(!empty($selectedCustomer))
            {{-- MODE 1: SINGLE CUSTOMER DETAILED STATEMENT --}}

            {{-- Customer Information Banner --}}
            <div class="customer-banner">
                <div class="cb-left">
                    <div class="cb-avatar">
                        {{ strtoupper(substr($selectedCustomer->customer_name ?: 'C', 0, 1)) }}
                    </div>
                    <div style="min-width:0;">
                        <div class="cb-name">{{ $selectedCustomer->customer_name }}</div>
                        <div class="cb-company">{{ $selectedCustomer->company_name ?: 'Independent Client' }}</div>
                        <div class="cb-meta">
                            @if($selectedCustomer->mobile_number)
                                <span><i class="bi bi-telephone-fill" style="color:#0284c7;"></i> {{ $selectedCustomer->mobile_number }}</span>
                            @endif
                            @if($selectedCustomer->email)
                                <span><i class="bi bi-envelope-fill" style="color:#0284c7;"></i> {{ $selectedCustomer->email }}</span>
                            @endif
                            @if($selectedCustomer->gst_number)
                                <span><strong>GSTIN:</strong> {{ $selectedCustomer->gst_number }}</span>
                            @endif
                            @if($selectedCustomer->pan_number)
                                <span><strong>PAN:</strong> {{ $selectedCustomer->pan_number }}</span>
                            @endif
                            @if($selectedCustomer->billing_city || $selectedCustomer->billing_state)
                                <span><i class="bi bi-geo-alt-fill" style="color:#64748b;"></i> {{ implode(', ', array_filter([$selectedCustomer->billing_city, $selectedCustomer->billing_state])) }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="cb-right">
                    <span class="cb-bal-label">Period Closing Balance</span>
                    <span class="cb-bal-val">₹{{ number_format((float) $closingBalance, 2) }}</span>
                    <span style="font-size:11px; color:#64748b; font-weight:600;">
                        Statement Period: {{ \Carbon\Carbon::parse($dateFrom)->format('d M, Y') }} — {{ \Carbon\Carbon::parse($dateTo)->format('d M, Y') }}
                    </span>
                </div>
            </div>

            {{-- Stat Cards Grid --}}
            <div class="acm-stats-grid">
                {{-- Opening Balance --}}
                <div class="acm-stat-card">
                    <div class="acm-stat-icon" style="background:#f1f5f9; color:#475569;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label">Opening Balance</div>
                        <div class="acm-stat-val">₹{{ number_format((float) $openingBalance, 2) }}</div>
                        <div class="acm-stat-sub">As of {{ \Carbon\Carbon::parse($dateFrom)->format('d M, Y') }}</div>
                    </div>
                </div>

                {{-- Invoiced in Period --}}
                <div class="acm-stat-card">
                    <div class="acm-stat-icon" style="background:#eff6ff; color:#1d4ed8;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label">Billed (Debit)</div>
                        <div class="acm-stat-val" style="color:#1d4ed8;">₹{{ number_format((float) $totalPeriodDebit, 2) }}</div>
                        <div class="acm-stat-sub">Invoices in period</div>
                    </div>
                </div>

                {{-- Paid in Period --}}
                <div class="acm-stat-card">
                    <div class="acm-stat-icon" style="background:#ecfdf5; color:#047857;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label">Paid (Credit)</div>
                        <div class="acm-stat-val" style="color:#047857;">₹{{ number_format((float) $totalPeriodPaid, 2) }}</div>
                        <div class="acm-stat-sub">Receipts & Settled</div>
                    </div>
                </div>

                {{-- Credit Notes in Period --}}
                <div class="acm-stat-card">
                    <div class="acm-stat-icon" style="background:#fffbeb; color:#b45309;">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label">Credit Notes</div>
                        <div class="acm-stat-val" style="color:#b45309;">₹{{ number_format((float) $totalPeriodCredited, 2) }}</div>
                        <div class="acm-stat-sub">Adjustments in period</div>
                    </div>
                </div>

                {{-- Net Closing Balance --}}
                <div class="acm-stat-card" style="border-color:#fed7aa; background:#fff7ed;">
                    <div class="acm-stat-icon" style="background:#fe5f04; color:#fff;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label" style="color:#9a3412;">Closing Balance Due</div>
                        <div class="acm-stat-val" style="color:#c2410c;">₹{{ number_format((float) $closingBalance, 2) }}</div>
                        <div class="acm-stat-sub" style="color:#ea580c;">As of {{ \Carbon\Carbon::parse($dateTo)->format('d M, Y') }}</div>
                    </div>
                </div>
            </div>

            {{-- Statement Ledger Card --}}
            <div class="acm-card">
                <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <div style="font-size:15px; font-weight:800; color:#0f172a;">Statement of Accounts (Ledger)</div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;">
                            Chronological transaction statement with progressive running balance.
                        </div>
                    </div>
                    <div style="font-size:12px; color:#64748b; font-weight:600;">
                        Showing {{ count($rows) + 1 }} ledger entries
                    </div>
                </div>

                <div class="ledger-tbl-wrap">
                    <table class="ledger-tbl">
                        <thead>
                            <tr>
                                <th style="width:110px;">Date</th>
                                <th style="width:130px;">Type</th>
                                <th style="width:160px;">Reference #</th>
                                <th>Particulars / Details</th>
                                <th class="text-right" style="width:140px;">Debit (+)</th>
                                <th class="text-right" style="width:140px;">Credit (-)</th>
                                <th class="text-right" style="width:150px;">Balance (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Opening Balance Row --}}
                            <tr class="opening-row">
                                <td>{{ \Carbon\Carbon::parse($dateFrom)->format('d M, Y') }}</td>
                                <td>
                                    <span class="badge-custom" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1;">
                                        <i class="bi bi-clock"></i> Opening
                                    </span>
                                </td>
                                <td>—</td>
                                <td>
                                    <strong>Opening Balance</strong>
                                    <span style="font-size:11.5px; color:#64748b; margin-left:6px;">(Balance carried forward before {{ \Carbon\Carbon::parse($dateFrom)->format('d M, Y') }})</span>
                                </td>
                                <td class="text-right">—</td>
                                <td class="text-right">—</td>
                                <td class="text-right amount-balance" style="color:{{ $openingBalance > 0 ? '#ea580c' : '#000' }};">
                                    ₹{{ number_format((float) $openingBalance, 2) }}
                                </td>
                            </tr>

                            {{-- In-Period Transactions --}}
                            @forelse($rows as $r)
                                <tr>
                                    <td style="white-space:nowrap; font-weight:600; color:#334155;">
                                        {{ $r['date']->format('d M, Y') }}
                                    </td>
                                    <td>
                                        <span class="badge-custom" style="{{ $r['badge_style'] }}">
                                            @if($r['type'] === 'invoice')
                                                <i class="bi bi-receipt"></i>
                                            @elseif($r['type'] === 'payment')
                                                <i class="bi bi-arrow-down-left"></i>
                                            @else
                                                <i class="bi bi-award"></i>
                                            @endif
                                            {{ $r['type_label'] }}
                                        </span>
                                    </td>
                                    <td style="font-weight:700;">
                                        @if(!empty($r['url']))
                                            <a href="{{ $r['url'] }}" target="_blank" style="color:#0284c7; text-decoration:none;" title="View document">
                                                {{ $r['ref_no'] }} <i class="bi bi-box-arrow-up-right" style="font-size:10px;"></i>
                                            </a>
                                        @else
                                            <span style="color:#334155;">{{ $r['ref_no'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:#0f172a;">{{ $r['particulars'] }}</div>
                                        @if(!empty($r['details']))
                                            <div style="font-size:11.5px; color:#64748b; margin-top:2px;">
                                                {{ $r['details'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-right amount-debit">
                                        @if($r['debit'] > 0)
                                            ₹{{ number_format((float) $r['debit'], 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-right amount-credit">
                                        @if($r['credit'] > 0)
                                            ₹{{ number_format((float) $r['credit'], 2) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-right amount-balance" style="color:{{ $r['running_balance'] > 0 ? '#ea580c' : '#000' }};">
                                        ₹{{ number_format((float) $r['running_balance'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center" style="padding:30px; color:#64748b;">
                                        <i class="bi bi-inbox" style="font-size:26px; display:block; color:#cbd5e1; margin-bottom:6px;"></i>
                                        No transactions recorded for this customer between {{ \Carbon\Carbon::parse($dateFrom)->format('d M, Y') }} and {{ \Carbon\Carbon::parse($dateTo)->format('d M, Y') }}.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-right" style="font-size:12px; text-transform:uppercase; letter-spacing:0.04em;">
                                    Period Totals / Closing Balance:
                                </td>
                                <td class="text-right amount-debit" style="color:#1d4ed8; font-size:14px;">
                                    ₹{{ number_format((float) $totalPeriodDebit, 2) }}
                                </td>
                                <td class="text-right amount-credit" style="color:#047857; font-size:14px;">
                                    ₹{{ number_format((float) $totalPeriodCredit, 2) }}
                                </td>
                                <td class="text-right amount-balance" style="color:#ea580c; font-size:15px; font-weight:900;">
                                    ₹{{ number_format((float) $closingBalance, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        @else
            {{-- MODE 2: ALL CUSTOMERS CONSOLIDATED SUMMARY --}}

            {{-- Summary Stats Grid --}}
            <div class="acm-stats-grid">
                <div class="acm-stat-card" style="border-color:#fed7aa; background:#fff7ed;">
                    <div class="acm-stat-icon" style="background:#fe5f04; color:#fff;">
                        <i class="bi bi-wallet-fill"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label" style="color:#9a3412;">Total Receivables</div>
                        <div class="acm-stat-val" style="color:#c2410c;">₹{{ number_format((float) $totalReceivables, 2) }}</div>
                        <div class="acm-stat-sub" style="color:#ea580c;">Across all active clients</div>
                    </div>
                </div>

                <div class="acm-stat-card">
                    <div class="acm-stat-icon" style="background:#eff6ff; color:#1d4ed8;">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label">Billed in Period</div>
                        <div class="acm-stat-val" style="color:#1d4ed8;">₹{{ number_format((float) $totalPeriodInvoiced, 2) }}</div>
                        <div class="acm-stat-sub">From {{ \Carbon\Carbon::parse($dateFrom)->format('d M') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M, Y') }}</div>
                    </div>
                </div>

                <div class="acm-stat-card">
                    <div class="acm-stat-icon" style="background:#ecfdf5; color:#047857;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label">Collected in Period</div>
                        <div class="acm-stat-val" style="color:#047857;">₹{{ number_format((float) $totalPeriodCollected, 2) }}</div>
                        <div class="acm-stat-sub">Customer receipts</div>
                    </div>
                </div>

                <div class="acm-stat-card">
                    <div class="acm-stat-icon" style="background:#fef2f2; color:#dc2626;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="acm-stat-label">Pending Balances</div>
                        <div class="acm-stat-val" style="color:#dc2626;">{{ $customersWithBalance }} Clients</div>
                        <div class="acm-stat-sub">Out of {{ count($customers) }} active customers</div>
                    </div>
                </div>
            </div>

            {{-- Summary Table Card --}}
            <div class="acm-card">
                <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <div style="font-size:15px; font-weight:800; color:#0f172a;">Customer Receivables Summary</div>
                        <div style="font-size:12px; color:#64748b; margin-top:2px;">
                            Overview of total billed, payments received, and current outstanding balance per client.
                        </div>
                    </div>
                    <div style="font-size:12px; color:#64748b; font-weight:600;">
                        Showing {{ count($customerRows) }} clients
                    </div>
                </div>

                <div class="ledger-tbl-wrap">
                    <table class="ledger-tbl">
                        <thead>
                            <tr>
                                <th>Customer / Company</th>
                                <th>Contact</th>
                                <th class="text-right">Billed in Period (₹)</th>
                                <th class="text-right">Collected in Period (₹)</th>
                                <th class="text-right">Credits in Period (₹)</th>
                                <th class="text-right">Current Balance Due (₹)</th>
                                <th class="text-center" style="width:130px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customerRows as $row)
                                @php $cust = $row['customer']; @endphp
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:#0f172a;">{{ $cust->customer_name }}</div>
                                        @if($cust->company_name)
                                            <div style="font-size:12px; color:#64748b; margin-top:1px;">{{ $cust->company_name }}</div>
                                        @endif
                                        @if($cust->gst_number)
                                            <div style="font-size:11px; color:#94a3b8; margin-top:1px;">GST: {{ $cust->gst_number }}</div>
                                        @endif
                                    </td>
                                    <td style="font-size:12px; color:#475569;">
                                        @if($cust->mobile_number)
                                            <div><i class="bi bi-telephone" style="font-size:11px;"></i> {{ $cust->mobile_number }}</div>
                                        @endif
                                        @if($cust->email)
                                            <div style="color:#64748b;"><i class="bi bi-envelope" style="font-size:11px;"></i> {{ $cust->email }}</div>
                                        @endif
                                    </td>
                                    <td class="text-right amount-debit">
                                        ₹{{ number_format((float) $row['invoiced_period'], 2) }}
                                    </td>
                                    <td class="text-right amount-credit">
                                        ₹{{ number_format((float) $row['collected_period'], 2) }}
                                    </td>
                                    <td class="text-right" style="color:#b45309; font-weight:700;">
                                        ₹{{ number_format((float) $row['credited_period'], 2) }}
                                    </td>
                                    <td class="text-right amount-balance" style="color:{{ $row['current_balance'] > 0 ? '#ea580c' : '#047857' }}; font-size:14px;">
                                        ₹{{ number_format((float) $row['current_balance'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('accounts.reports.customer-balance-sheet', ['customer_id' => $cust->id, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                                           class="acm-btn acm-btn-secondary" style="padding:6px 12px; font-size:12px;" title="View detailed ledger">
                                            <i class="bi bi-journal-text"></i>
                                            <span>Statement</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center" style="padding:30px; color:#64748b;">
                                        No active customers found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right" style="font-size:12px; text-transform:uppercase;">
                                    Total Across All Clients:
                                </td>
                                <td class="text-right amount-debit" style="color:#1d4ed8; font-size:14px;">
                                    ₹{{ number_format((float) $totalPeriodInvoiced, 2) }}
                                </td>
                                <td class="text-right amount-credit" style="color:#047857; font-size:14px;">
                                    ₹{{ number_format((float) $totalPeriodCollected, 2) }}
                                </td>
                                <td class="text-right" style="color:#b45309; font-size:14px; font-weight:800;">
                                    ₹{{ number_format((float) $totalPeriodCredited, 2) }}
                                </td>
                                <td class="text-right amount-balance" style="color:#ea580c; font-size:15px; font-weight:900;">
                                    ₹{{ number_format((float) $totalReceivables, 2) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        @endif

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#customerSelect').select2({
        width: '100%',
        placeholder: 'Select a customer'
    });

    $('#customerSelect').on('change', function() {
        $('#filterForm').submit();
    });
});

function setPreset(type) {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');

    let from = '';
    let to = `${yyyy}-${mm}-${dd}`;

    if (type === 'fy') {
        const fyStart = (today.getMonth() + 1) >= 4 ? yyyy : yyyy - 1;
        from = `${fyStart}-04-01`;
    } else if (type === 'month') {
        from = `${yyyy}-${mm}-01`;
    } else if (type === 'last_month') {
        const lastMonthDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);
        from = `${lastMonthDate.getFullYear()}-${String(lastMonthDate.getMonth() + 1).padStart(2, '0')}-01`;
        to = `${lastMonthEnd.getFullYear()}-${String(lastMonthEnd.getMonth() + 1).padStart(2, '0')}-${String(lastMonthEnd.getDate()).padStart(2, '0')}`;
    } else if (type === 'all_time') {
        from = '2020-01-01';
    }

    document.getElementById('dateFrom').value = from;
    document.getElementById('dateTo').value = to;
    document.getElementById('filterForm').submit();
}
</script>
@endpush
