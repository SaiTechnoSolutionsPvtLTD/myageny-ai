@extends('layouts.app')

@section('title', 'Invoices - Accounts Sales')

@push('styles')
<style>
.inv-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.inv-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); flex-wrap:wrap; gap:12px; }
.inv-breadcrumbs { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:700; color:#64748b; margin-bottom:4px; }
.inv-breadcrumbs a { color:#64748b; text-decoration:none; }
.inv-breadcrumbs a:hover { color:#fe5f04; }
.inv-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; display:flex; align-items:center; gap:10px; }
.inv-sub { font-size:13px; color:#64748b; }

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
.acm-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:wrap; }
.acm-search-wrap { position:relative; flex:1; min-width:240px; }
.acm-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.acm-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.acm-select { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.acm-tbl { width:100%; border-collapse:collapse; }
.acm-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.acm-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }

.acm-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; white-space:nowrap; }
.status-paid { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.status-partially-paid { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.status-sent { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
.status-draft { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.status-overdue { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.status-cancelled { background:#f1f5f9; color:#94a3b8; border:1px solid #cbd5e1; text-decoration:line-through; }

.inv-code { font-family:monospace; font-weight:800; color:#0f172a; text-decoration:none; font-size:13px; }
.inv-code:hover { color:#fe5f04; text-decoration:underline; }
</style>
@endpush

@section('content')
<div class="inv-page">

    {{-- Topbar --}}
    <div class="inv-topbar">
        <div>
            <div class="inv-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <span>Sales</span>
                <span>/</span>
                <span style="color:#0f172a;">Invoices</span>
            </div>
            <div class="inv-title">
                <i class="bi bi-receipt" style="color:#fe5f04;"></i>
                <span>Sales Invoices</span>
            </div>
            <div class="inv-sub">Generate customer invoices, track collections, GST breakup, payment receipts, and balance dues.</div>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <a href="{{ route('accounts.master.invoice-settings.index') }}" class="acm-btn acm-btn-outline" title="Configure Invoice Format & Company Details">
                <i class="bi bi-gear"></i> Invoice Settings
            </a>
            <a href="{{ route('accounts.sales.invoices.create') }}" class="acm-btn acm-btn-primary">
                <i class="bi bi-plus-lg"></i> New Invoice
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 18px; border-radius:10px; font-size:13px; font-weight:600;">
            ✕ {{ session('error') }}
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="acm-stats-grid">
        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fff7ed; color:#ea580c;">
                <i class="bi bi-receipt-cutoff"></i>
            </div>
            <div>
                <div class="acm-stat-label">Total Invoiced</div>
                <div class="acm-stat-val">₹{{ number_format($stats['total_amount'], 2) }}</div>
                <div class="acm-stat-sub">{{ $stats['total_count'] }} Invoices Total</div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check2-circle"></i>
            </div>
            <div>
                <div class="acm-stat-label">Paid / Received</div>
                <div class="acm-stat-val" style="color:#16a34a;">₹{{ number_format($stats['paid_amount'], 2) }}</div>
                <div class="acm-stat-sub">{{ $stats['paid_count'] }} Fully Paid</div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#fef2f2; color:#dc2626;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="acm-stat-label">Balance Due</div>
                <div class="acm-stat-val" style="color:#dc2626;">₹{{ number_format($stats['balance_due'], 2) }}</div>
                <div class="acm-stat-sub">{{ $stats['partial_count'] }} Partially Paid</div>
            </div>
        </div>

        <div class="acm-stat-card">
            <div class="acm-stat-icon" style="background:#eff6ff; color:#2563eb;">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div>
                <div class="acm-stat-label">Draft / Open</div>
                <div class="acm-stat-val">{{ $stats['draft_count'] }}</div>
                <div class="acm-stat-sub">{{ $stats['overdue_count'] }} Overdue</div>
            </div>
        </div>
    </div>

    {{-- Invoices Table Card --}}
    <div class="acm-card">
        {{-- Filters --}}
        <form method="GET" action="{{ route('accounts.sales.invoices.index') }}" class="acm-filter-bar">
            <div class="acm-search-wrap">
                <i class="bi bi-search acm-search-ico"></i>
                <input type="text" name="search" class="acm-search-input"
                       placeholder="Search by Invoice #, Customer Name, Company, Phone, or Email…"
                       value="{{ request('search') }}">
            </div>

            <select name="status" class="acm-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="partially_paid" {{ request('status') === 'partially_paid' ? 'selected' : '' }}>Partially Paid</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <input type="date" name="from_date" class="acm-select" value="{{ request('from_date') }}" title="From Date" onchange="this.form.submit()">
            <input type="date" name="to_date" class="acm-select" value="{{ request('to_date') }}" title="To Date" onchange="this.form.submit()">

            <button type="submit" class="acm-btn acm-btn-secondary" style="padding:8px 14px;">
                <i class="bi bi-funnel"></i> Filter
            </button>

            @if(request()->anyFilled(['search', 'status', 'from_date', 'to_date']))
                <a href="{{ route('accounts.sales.invoices.index') }}" class="acm-btn acm-btn-outline" style="padding:8px 12px; color:#dc2626;" title="Reset filters">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            @endif
        </form>

        {{-- Table --}}
        @if($invoices->isEmpty())
            <div style="text-align:center; padding:60px 20px; color:#94a3b8;">
                <i class="bi bi-receipt" style="font-size:48px; display:block; margin-bottom:12px; color:#cbd5e1;"></i>
                <div style="font-size:16px; font-weight:700; color:#475569; margin-bottom:4px;">No Invoices Found</div>
                <div style="font-size:13px; color:#64748b; margin-bottom:20px;">Get started by issuing your first invoice to a client.</div>
                <a href="{{ route('accounts.sales.invoices.create') }}" class="acm-btn acm-btn-primary">
                    <i class="bi bi-plus-lg"></i> Create New Invoice
                </a>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="acm-tbl">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer & Company</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th style="text-align:right;">Amount (₹)</th>
                            <th style="text-align:right;">Paid (₹)</th>
                            <th style="text-align:right;">Balance Due (₹)</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $inv)
                        <tr>
                            <td>
                                <a href="{{ route('accounts.sales.invoices.show', $inv->id) }}" class="inv-code">
                                    {{ $inv->invoice_number }}
                                </a>
                                @if($inv->salesPerson)
                                    <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                        <i class="bi bi-person"></i> {{ $inv->salesPerson->name }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div style="font-weight:700; color:#0f172a;">{{ $inv->customer_name }}</div>
                                @if($inv->company_name)
                                    <div style="font-size:11px; color:#64748b; margin-top:2px;">
                                        <i class="bi bi-building"></i> {{ $inv->company_name }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div style="font-size:13px; color:#334155;">{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</div>
                            </td>
                            <td>
                                @if($inv->due_date)
                                    <div style="font-size:13px; color:{{ \Carbon\Carbon::parse($inv->due_date)->isPast() && $inv->balance_due > 0 ? '#dc2626' : '#334155' }};">
                                        {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}
                                        @if(\Carbon\Carbon::parse($inv->due_date)->isPast() && $inv->balance_due > 0)
                                            <span style="font-size:10px; font-weight:800; color:#dc2626; display:block;">OVERDUE</span>
                                        @endif
                                    </div>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:800; color:#0f172a; white-space:nowrap;">
                                ₹{{ number_format((float) $inv->total_amount, 2) }}
                            </td>
                            <td style="text-align:right; font-weight:700; color:#16a34a; white-space:nowrap;">
                                ₹{{ number_format((float) $inv->paid_amount, 2) }}
                            </td>
                            <td style="text-align:right; font-weight:800; color:{{ $inv->balance_due > 0 ? '#dc2626' : '#16a34a' }}; white-space:nowrap;">
                                ₹{{ number_format((float) $inv->balance_due, 2) }}
                            </td>
                            <td>
                                {!! $inv->status_badge !!}
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <div style="display:inline-flex; align-items:center; gap:6px;">
                                    <a href="{{ route('accounts.sales.invoices.show', $inv->id) }}" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;" title="View Invoice">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="{{ route('accounts.sales.invoices.edit', $inv->id) }}" class="acm-btn acm-btn-secondary" style="padding:4px 8px; font-size:11px;" title="Edit Invoice">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a href="{{ route('accounts.sales.invoices.print', $inv->id) }}" target="_blank" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11px;" title="Print Invoice">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <form method="POST" action="{{ route('accounts.sales.invoices.destroy', $inv->id) }}" onsubmit="return confirm('Are you sure you want to delete invoice #{{ $inv->invoice_number }}?')" style="margin:0; display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="acm-btn acm-btn-danger" style="padding:4px 8px; font-size:11px;" title="Delete Invoice">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #f1f5f9;">
                    {{ $invoices->links() }}
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
