@extends('layouts.app')

@section('title', 'Credit Notes (CN) - Accounts Sales')

@push('styles')
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
.acm-btn-danger { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.acm-btn-danger:hover { background:#fca5a5; }

.acm-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
.acm-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.acm-stat-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
.acm-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.acm-stat-val { font-size:20px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1.1; }
.acm-stat-sub { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; }

.acm-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }

/* Filter Accordion */
.acc-filter-accordion { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); transition:box-shadow .2s; }
.acc-filter-accordion:hover { box-shadow:0 4px 12px rgba(0,0,0,.04); }
.acc-filter-toggle { list-style:none; display:flex; align-items:center; justify-content:space-between; gap:16px; padding:14px 20px; cursor:pointer; user-select:none; background:#fff; transition:background .15s ease; }
.acc-filter-toggle::-webkit-details-marker, .acc-filter-toggle::marker { display:none; content:""; }
.acc-filter-toggle:hover { background:#f8fafc; }
.acc-filter-accordion[open] .acc-filter-toggle { border-bottom:1px solid #e2e8f0; background:#fafbfc; }
.acc-filter-toggle-left { display:flex; align-items:center; gap:12px; min-width:0; }
.acc-filter-icon { width:36px; height:36px; border-radius:10px; display:inline-flex; align-items:center; justify-content:center; background:#fff7ed; color:#fe5f04; font-size:15px; flex-shrink:0; border:1px solid #ffedd5; }
.acc-filter-title { font-size:14px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.acc-filter-sub { font-size:12px; color:#64748b; margin-top:2px; }
.acc-filter-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:999px; background:#fe5f04; color:#fff; font-size:11px; font-weight:800; letter-spacing:0.02em; }
.acc-filter-toggle-right { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.acc-filter-toggle-hint { font-size:12px; font-weight:700; color:#64748b; }
.acc-filter-toggle-hint::after { content:"Show Filters"; }
.acc-filter-accordion[open] .acc-filter-toggle-hint::after { content:"Hide Filters"; }
.acc-filter-chevron { width:32px; height:32px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #e2e8f0; color:#64748b; background:#fff; transition:all .2s ease; font-size:13px; }
.acc-filter-accordion[open] .acc-filter-chevron { transform:rotate(180deg); color:#fe5f04; border-color:#fed7aa; background:#fff7ed; }
.acc-filter-body { padding:16px 20px; background:#fff; }

.inv-filter-single-row { display:flex; align-items:flex-end; gap:12px; width:100%; flex-wrap:wrap; }
.filter-col { display:flex; flex-direction:column; gap:6px; }
.filter-col-search { flex:2; min-width:200px; }
.filter-col-reason { flex:1.4; min-width:160px; }
.filter-col-status { flex:1; min-width:120px; }
.filter-col-date { flex:1; min-width:125px; }
.filter-col-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; margin-bottom:0; }
.filter-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; line-height:1; }
.filter-input-wrap { position:relative; width:100%; }
.filter-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:13px; pointer-events:none; }
.filter-control { width:100%; height:38px; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; color:#0f172a; box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
.filter-control:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.12); }
.filter-input-wrap .filter-control { padding-left:36px; }
.filter-select { cursor:pointer; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

.cn-num-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #fef2f2;
    border: 1px solid #fecaca;
    padding: 3px 8px;
    border-radius: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    font-weight: 800;
    color: #b91c1c;
    text-decoration: none;
    transition: all .15s;
}
.cn-num-pill:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: #991b1b;
}

.inv-ref-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    padding: 3px 8px;
    border-radius: 6px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12.5px;
    font-weight: 800;
    color: #15803d;
    text-decoration: none;
    transition: all .15s;
}
.inv-ref-pill:hover {
    background: #dcfce7;
    color: #166534;
}

.reason-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    white-space: nowrap;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11.5px;
    font-weight: 800;
}
.status-badge.issued {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}
.status-badge.void {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-title">
                <i class="bi bi-file-earmark-minus" style="color:#dc2626;"></i>
                <span>Credit Notes (CN)</span>
            </div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.sales.invoices.index') }}">Sales</a>
                <span>/</span>
                <span style="color:#121212; font-weight:700;">Credit Notes</span>
            </div>
        </div>
        <div class="inv-actions">
            <a href="{{ route('accounts.sales.invoices.index') }}" class="acm-btn acm-btn-outline" title="View Invoices List">
                <i class="bi bi-receipt"></i> Invoices
            </a>
            <a href="{{ route('accounts.sales.credit-notes.create') }}" class="acm-btn acm-btn-primary">
                <i class="bi bi-plus-lg"></i> New Credit Note
            </a>
        </div>
    </div>

    <div class="inv-body">

        {{-- Flash Alerts --}}
        @if(session('success'))
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Summary Stats Grid --}}
        <div class="acm-stats-grid">
            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca;">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Total Credited</div>
                    <div class="acm-stat-val" style="color:#dc2626;">₹{{ number_format($totalIssuedAmount, 2) }}</div>
                    <div class="acm-stat-sub">Across active credit notes</div>
                </div>
            </div>

            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe;">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Total CNs Issued</div>
                    <div class="acm-stat-val">{{ number_format($totalCnCount) }}</div>
                    <div class="acm-stat-sub">All-time credit records</div>
                </div>
            </div>

            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0;">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Active / Applied</div>
                    <div class="acm-stat-val" style="color:#16a34a;">{{ number_format($activeCnCount) }}</div>
                    <div class="acm-stat-sub">Adjusted against invoice balances</div>
                </div>
            </div>

            <div class="acm-stat-card">
                <div class="acm-stat-icon" style="background:#f8fafc; color:#64748b; border:1px solid #e2e8f0;">
                    <i class="bi bi-slash-circle"></i>
                </div>
                <div>
                    <div class="acm-stat-label">Voided CNs</div>
                    <div class="acm-stat-val" style="color:#64748b;">{{ number_format($voidCnCount) }}</div>
                    <div class="acm-stat-sub">Cancelled / nullified notes</div>
                </div>
            </div>
        </div>

        {{-- Filter Accordion --}}
        @php
            $hasActiveFilters = request()->filled('search') || request()->filled('reason') || request()->filled('status') || request()->filled('date_from') || request()->filled('date_to');
            $activeFilterCount = (request()->filled('search') ? 1 : 0)
                               + (request()->filled('reason') ? 1 : 0)
                               + (request()->filled('status') ? 1 : 0)
                               + (request()->filled('date_from') ? 1 : 0)
                               + (request()->filled('date_to') ? 1 : 0);
        @endphp

        <details class="acc-filter-accordion" {{ $hasActiveFilters ? 'open' : '' }}>
            <summary class="acc-filter-toggle">
                <div class="acc-filter-toggle-left">
                    <div class="acc-filter-icon">
                        <i class="bi bi-funnel-fill"></i>
                    </div>
                    <div>
                        <div class="acc-filter-title">
                            <span>Filter Credit Notes</span>
                            @if($activeFilterCount > 0)
                                <span class="acc-filter-badge">{{ $activeFilterCount }} Active</span>
                            @endif
                        </div>
                        <div class="acc-filter-sub">Search by CN #, customer name, invoice #, reason, or date range</div>
                    </div>
                </div>
                <div class="acc-filter-toggle-right">
                    <span class="acc-filter-toggle-hint"></span>
                    <span class="acc-filter-chevron"><i class="bi bi-chevron-down"></i></span>
                </div>
            </summary>

            <div class="acc-filter-body">
                <form method="GET" action="{{ route('accounts.sales.credit-notes.index') }}" class="inv-filter-single-row">
                    <div class="filter-col filter-col-search">
                        <label class="filter-label">Search</label>
                        <div class="filter-input-wrap">
                            <i class="bi bi-search filter-icon"></i>
                            <input type="text" name="search" class="filter-control" placeholder="CN #, Invoice #, Customer..." value="{{ request('search') }}">
                        </div>
                    </div>

                    <div class="filter-col filter-col-reason">
                        <label class="filter-label">Reason</label>
                        <select name="reason" class="filter-control filter-select">
                            <option value="">All Reasons</option>
                            @foreach($reasons as $rKey => $rLabel)
                                <option value="{{ $rKey }}" {{ request('reason') === $rKey ? 'selected' : '' }}>{{ $rLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-col filter-col-status">
                        <label class="filter-label">Status</label>
                        <select name="status" class="filter-control filter-select">
                            <option value="">All Statuses</option>
                            <option value="issued" {{ request('status') === 'issued' ? 'selected' : '' }}>Active / Issued</option>
                            <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void (Cancelled)</option>
                        </select>
                    </div>

                    <div class="filter-col filter-col-date">
                        <label class="filter-label">From Date</label>
                        <input type="date" name="date_from" class="filter-control" value="{{ request('date_from') }}">
                    </div>

                    <div class="filter-col filter-col-date">
                        <label class="filter-label">To Date</label>
                        <input type="date" name="date_to" class="filter-control" value="{{ request('date_to') }}">
                    </div>

                    <div class="filter-col filter-col-actions">
                        <button type="submit" class="acm-btn acm-btn-primary" style="height:38px; padding:0 16px; white-space:nowrap;">
                            <i class="bi bi-funnel"></i> Apply
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ route('accounts.sales.credit-notes.index') }}" class="acm-btn acm-btn-outline" style="height:38px; padding:0 12px; color:#dc2626; border-color:#fca5a5; white-space:nowrap;" title="Reset filters">
                                <i class="bi bi-x-circle"></i> Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </details>

        {{-- Credit Notes Table --}}
        <div class="acm-card">
            <div style="padding:14px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:#fff; flex-wrap:wrap; gap:10px;">
                <div style="font-size:14px; font-weight:800; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <i class="bi bi-journal-text" style="color:#dc2626;"></i>
                    <span>All Credit Notes</span>
                    <span style="font-size:12px; font-weight:700; background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:999px;">{{ $creditNotes->total() }}</span>
                </div>
            </div>

            @if($creditNotes->count() > 0)
                <div style="overflow-x:auto;">
                    <table class="acm-tbl">
                        <thead>
                            <tr>
                                <th>Date & CN #</th>
                                <th>Invoice Reference</th>
                                <th>Customer</th>
                                <th>Reason</th>
                                <th style="text-align:right;">Credited Amount (₹)</th>
                                <th style="text-align:center;">Status</th>
                                <th>Issued By</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($creditNotes as $cn)
                                <tr>
                                    <td>
                                        <div style="font-weight:700; color:#0f172a;">{{ $cn->credit_note_date ? $cn->credit_note_date->format('d M Y') : '—' }}</div>
                                        <div style="margin-top:2px;">
                                            <a href="{{ route('accounts.sales.credit-notes.show', $cn->id) }}" class="cn-num-pill" title="View Credit Note">
                                                <i class="bi bi-file-earmark-minus"></i>
                                                <span>{{ $cn->credit_note_number }}</span>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        @if($cn->invoice)
                                            <a href="{{ route('accounts.sales.invoices.show', $cn->invoice_id) }}" class="inv-ref-pill" title="View Invoice">
                                                <i class="bi bi-receipt"></i>
                                                <span>{{ $cn->invoice->invoice_number }}</span>
                                            </a>
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                                Total: ₹{{ number_format((float) $cn->invoice->total_amount, 2) }}
                                            </div>
                                        @else
                                            <span style="color:#94a3b8;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-weight:800; color:#0f172a;">{{ $cn->customer?->customer_name ?: ($cn->invoice?->customer_name ?: 'Valued Customer') }}</div>
                                        @if($cn->customer?->company_name)
                                            <div style="font-size:11.5px; color:#64748b;">{{ $cn->customer->company_name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="reason-badge">
                                            <i class="bi bi-tag-fill" style="color:#f59e0b; font-size:10px;"></i>
                                            <span>{{ \App\Models\CreditNote::REASONS[$cn->reason] ?? $cn->reason }}</span>
                                        </span>
                                        @if($cn->notes)
                                            <div style="font-size:11px; color:#64748b; margin-top:3px; max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $cn->notes }}">
                                                {{ $cn->notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="font-size:14.5px; font-weight:800; color:#dc2626;">
                                            ₹{{ number_format((float) $cn->total_amount, 2) }}
                                        </div>
                                        @if($cn->total_tax > 0)
                                            <div style="font-size:11px; color:#64748b;">
                                                (Tax: ₹{{ number_format((float) $cn->total_tax, 2) }})
                                            </div>
                                        @endif
                                    </td>
                                    <td style="text-align:center;">
                                        <span class="status-badge {{ $cn->status }}">
                                            @if($cn->status === 'issued')
                                                <i class="bi bi-check-circle-fill"></i> Active
                                            @else
                                                <i class="bi bi-slash-circle-fill"></i> Void
                                            @endif
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:#334155;">{{ $cn->creator?->name ?? 'System' }}</div>
                                        <div style="font-size:10.5px; color:#94a3b8;">{{ $cn->created_at?->diffForHumans() }}</div>
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex; align-items:center; gap:6px;">
                                            <a href="{{ route('accounts.sales.credit-notes.show', $cn->id) }}" class="acm-btn acm-btn-outline" style="padding:5px 9px; font-size:12px;" title="View Credit Note">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('accounts.sales.credit-notes.print', $cn->id) }}" target="_blank" class="acm-btn acm-btn-outline" style="padding:5px 9px; font-size:12px;" title="Print / PDF">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            <form method="POST" action="{{ route('accounts.sales.credit-notes.void', $cn->id) }}" onsubmit="return confirm('Are you sure you want to {{ $cn->status === 'void' ? 'restore' : 'void' }} Credit Note #{{ $cn->credit_note_number }}? Invoice balance will update.')" style="margin:0; display:inline-block;">
                                                @csrf
                                                <button type="submit" class="acm-btn acm-btn-outline" style="padding:5px 9px; font-size:12px; color:{{ $cn->status === 'void' ? '#16a34a' : '#ea580c' }};" title="{{ $cn->status === 'void' ? 'Restore CN' : 'Void CN' }}">
                                                    <i class="bi {{ $cn->status === 'void' ? 'bi-arrow-counterclockwise' : 'bi-slash-circle' }}"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('accounts.sales.credit-notes.destroy', $cn->id) }}" onsubmit="return confirm('Permanently delete Credit Note #{{ $cn->credit_note_number }} (₹{{ number_format((float)$cn->total_amount, 2) }})? The invoice balance will be adjusted back.')" style="margin:0; display:inline-block;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="acm-btn acm-btn-outline" style="padding:5px 9px; font-size:12px; color:#dc2626;" title="Delete Credit Note">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($creditNotes->hasPages())
                    <div style="padding:16px 20px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                        <div style="font-size:12px; color:#64748b;">
                            Showing {{ $creditNotes->firstItem() }} to {{ $creditNotes->lastItem() }} of {{ $creditNotes->total() }} credit notes
                        </div>
                        <div>
                            {{ $creditNotes->links() }}
                        </div>
                    </div>
                @endif
            @else
                <div style="padding:50px 20px; text-align:center;">
                    <div style="width:64px; height:64px; border-radius:50%; background:#fef2f2; color:#dc2626; display:inline-flex; align-items:center; justify-content:center; font-size:28px; margin-bottom:12px;">
                        <i class="bi bi-file-earmark-minus"></i>
                    </div>
                    <div style="font-size:16px; font-weight:800; color:#0f172a; margin-bottom:4px;">No Credit Notes Found</div>
                    <div style="font-size:13px; color:#64748b; max-width:420px; margin:0 auto 18px;">
                        @if($hasActiveFilters)
                            No credit notes match your filter criteria. Try adjusting or clearing your filters.
                        @else
                            No credit notes have been issued yet. You can create a credit note directly against any sales invoice to adjust balances, provide post-invoice discounts, or record cancellations.
                        @endif
                    </div>
                    @if($hasActiveFilters)
                        <a href="{{ route('accounts.sales.credit-notes.index') }}" class="acm-btn acm-btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    @else
                        <a href="{{ route('accounts.sales.credit-notes.create') }}" class="acm-btn acm-btn-primary">
                            <i class="bi bi-plus-lg"></i> Create Credit Note
                        </a>
                    @endif
                </div>
            @endif
        </div>

    </div>{{-- /inv-body --}}
</div>{{-- /inv-page --}}
@endsection
