@extends('layouts.app')

@section('title', 'Zoho Books Invoices - Accounts')

@push('styles')
<style>
.zh-page { display:flex; flex-direction:column; min-height:100%; background:#f4f5f7; font-family:'Inter',sans-serif; }
.zh-topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:0 28px; min-height:64px; background:#fff; border-bottom:1px solid #e1dee3; position:sticky; top:0; z-index:30; }
.zh-title { font-size:18px; font-weight:800; color:#121212; display:flex; align-items:center; gap:8px; line-height:1.2; }
.zh-breadcrumbs { font-size:12px; color:#9e9e9e; margin-top:2px; display:flex; align-items:center; gap:6px; }
.zh-breadcrumbs a { color:#9e9e9e; text-decoration:none; }
.zh-breadcrumbs a:hover { color:#fe5f04; }
.zh-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.zh-body { padding:22px 28px 34px; display:flex; flex-direction:column; gap:20px; flex:1; }

.acm-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.acm-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.acm-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.acm-btn-secondary { background:#f1f5f9; color:#334155; }
.acm-btn-secondary:hover { background:#e2e8f0; }
.acm-btn-outline { background:#fff; border:1px solid #cbd5e1; color:#334155; }
.acm-btn-outline:hover { border-color:#fe5f04; color:#fe5f04; }

.zh-card { background:#fff; border-radius:12px; border:1px solid #e2e8f0; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.03); }
.zh-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:14px; }
.zh-stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; position:relative; overflow:hidden; }
.zh-stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:#fe5f04; }
.zh-stat-label { font-size:11.5px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; margin-bottom:4px; }
.zh-stat-value { font-size:20px; font-weight:800; color:#0f172a; }

.zh-filter-bar { display:flex; gap:12px; flex-wrap:wrap; align-items:center; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 18px; }
.zh-filter-input { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; color:#0f172a; outline:none; font-family:inherit; }
.zh-filter-input:focus { border-color:#fe5f04; }

.zh-table { width:100%; border-collapse:collapse; font-size:13px; text-align:left; }
.zh-table th { background:#f8fafc; padding:12px 14px; font-size:11.5px; text-transform:uppercase; color:#64748b; font-weight:700; border-bottom:1px solid #e2e8f0; }
.zh-table td { padding:14px; border-bottom:1px solid #f1f5f9; color:#334155; vertical-align:middle; }
.zh-table tr:hover td { background:#fafaf9; }
</style>
@endpush

@section('content')
<div class="zh-page">
    <div class="zh-topbar">
        <div>
            <div class="zh-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe5f04" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Zoho Books Invoices
            </div>
            <div class="zh-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.zoho.index') }}">Zoho Books</a>
                <span>/</span>
                <span>Invoices</span>
            </div>
        </div>
        <div class="zh-actions">
            <a href="{{ route('accounts.zoho.index') }}" class="acm-btn acm-btn-secondary">
                Hub Overview
            </a>
            @if($integration->is_connected)
            <form action="{{ route('accounts.zoho.invoices.sync') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="acm-btn acm-btn-primary" onclick="this.innerHTML='Syncing...'; this.disabled=true; this.form.submit();">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Sync Latest from Zoho
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="zh-body">
        @if(session('success'))
            <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:10px; font-size:13.5px; font-weight:600;">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:14px 18px; border-radius:10px; font-size:13.5px; font-weight:600;">
                {{ session('error') }}
            </div>
        @endif

        <!-- Stats Overview -->
        <div class="zh-stats-grid">
            <div class="zh-stat-card">
                <div class="zh-stat-label">Filtered Invoices</div>
                <div class="zh-stat-value">{{ number_format($totalCount) }}</div>
            </div>
            <div class="zh-stat-card">
                <div class="zh-stat-label">Total Amount</div>
                <div class="zh-stat-value">₹{{ number_format($totalAmount, 2) }}</div>
            </div>
            <div class="zh-stat-card">
                <div class="zh-stat-label">Total Balance Due</div>
                <div class="zh-stat-value" style="color:#dc2626;">₹{{ number_format($totalBalance, 2) }}</div>
            </div>
            <div class="zh-stat-card">
                <div class="zh-stat-label">Total Received</div>
                <div class="zh-stat-value" style="color:#16a34a;">₹{{ number_format($totalPaid, 2) }}</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('accounts.zoho.invoices') }}" class="zh-filter-bar">
            <div style="flex:1; min-width:200px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Invoice #, Customer, Company..." class="zh-filter-input" style="width:100%;">
            </div>

            <div>
                <select name="status" class="zh-filter-input">
                    <option value="all">All Zoho Statuses</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                </select>
            </div>

            <div>
                <select name="migration_status" class="zh-filter-input">
                    <option value="">All Migration Status</option>
                    <option value="migrated" {{ request('migration_status') === 'migrated' ? 'selected' : '' }}>Migrated to CRM</option>
                    <option value="pending" {{ request('migration_status') === 'pending' ? 'selected' : '' }}>Pending Migration</option>
                </select>
            </div>

            <div>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="zh-filter-input" title="From Date">
            </div>
            <div>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="zh-filter-input" title="To Date">
            </div>

            <button type="submit" class="acm-btn acm-btn-primary" style="padding:8px 14px;">Filter</button>
            <a href="{{ route('accounts.zoho.invoices') }}" class="acm-btn acm-btn-outline" style="padding:8px 14px;">Reset</a>
        </form>

        <!-- Invoices Table Card -->
        <form id="bulkMigrateForm" action="{{ route('accounts.zoho.invoices.bulk-migrate') }}" method="POST">
            @csrf
            <div class="zh-card" style="padding:0; overflow:hidden;">
                <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div style="font-size:13.5px; font-weight:700; color:#0f172a;">
                        Showing {{ $invoices->firstItem() ?? 0 }} to {{ $invoices->lastItem() ?? 0 }} of {{ $invoices->total() }} Invoices
                    </div>
                    <div>
                        <button type="submit" class="acm-btn acm-btn-primary" onclick="return confirm('Migrate selected unmigrated invoices into CRM Invoices?')" style="font-size:12px;">
                            Bulk Migrate to CRM
                        </button>
                    </div>
                </div>

                @if($invoices->isEmpty())
                    <div style="text-align:center; padding:48px 20px;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:10px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                        <div style="font-size:14px; font-weight:700; color:#334155;">No Invoices Found</div>
                        <div style="font-size:12.5px; color:#64748b; margin-top:4px;">Try clearing filters or sync invoices from Zoho Books.</div>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="zh-table">
                            <thead>
                                <tr>
                                    <th style="width:36px; text-align:center;">
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                                    </th>
                                    <th>Invoice #</th>
                                    <th>Customer Details</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th style="text-align:right;">Subtotal</th>
                                    <th style="text-align:right;">Total Amount</th>
                                    <th style="text-align:right;">Balance Due</th>
                                    <th style="text-align:center;">Zoho Status</th>
                                    <th style="text-align:center;">CRM Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($invoices as $inv)
                                <tr>
                                    <td style="text-align:center;">
                                        @if(!$inv->is_migrated_to_native)
                                            <input type="checkbox" name="selected_ids[]" value="{{ $inv->id }}" class="inv-checkbox">
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('accounts.zoho.show-invoice', $inv->id) }}" style="font-weight:700; color:#fe5f04; text-decoration:none;">
                                            {{ $inv->invoice_number }}
                                        </a>
                                        @if($inv->reference_number)
                                            <div style="font-size:11px; color:#94a3b8;">Ref: {{ $inv->reference_number }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:#0f172a;">{{ $inv->customer_name }}</div>
                                        @if($inv->company_name && $inv->company_name !== $inv->customer_name)
                                            <div style="font-size:11.5px; color:#64748b;">{{ $inv->company_name }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : '—' }}</td>
                                    <td>{{ $inv->due_date ? $inv->due_date->format('d M Y') : '—' }}</td>
                                    <td style="text-align:right;">₹{{ number_format((float)$inv->sub_total, 2) }}</td>
                                    <td style="text-align:right; font-weight:700;">₹{{ number_format((float)$inv->total, 2) }}</td>
                                    <td style="text-align:right; font-weight:700; color:{{ $inv->balance > 0 ? '#dc2626' : '#16a34a' }};">
                                        ₹{{ number_format((float)$inv->balance, 2) }}
                                    </td>
                                    <td style="text-align:center;">
                                        <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; text-transform:uppercase; background:{{ $inv->status === 'paid' ? '#dcfce7' : ($inv->status === 'sent' ? '#e0f2fe' : ($inv->status === 'overdue' ? '#fee2e2' : '#f1f5f9')) }}; color:{{ $inv->status === 'paid' ? '#15803d' : ($inv->status === 'sent' ? '#0369a1' : ($inv->status === 'overdue' ? '#b91c1c' : '#475569')) }};">
                                            {{ $inv->status }}
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        @if($inv->is_migrated_to_native)
                                            <a href="{{ route('accounts.sales.invoices.show', $inv->migrated_native_invoice_id) }}" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#15803d; background:#dcfce7; padding:3px 8px; border-radius:6px; text-decoration:none;">
                                                ✓ Migrated
                                            </a>
                                        @else
                                            <span style="font-size:11px; color:#94a3b8; font-weight:600;">Pending</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex; align-items:center; gap:6px;">
                                            <a href="{{ route('accounts.zoho.show-invoice', $inv->id) }}" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11.5px;">View</a>
                                            @if(!$inv->is_migrated_to_native)
                                                <button type="submit" formaction="{{ route('accounts.zoho.invoices.migrate', $inv->id) }}" class="acm-btn acm-btn-primary" style="padding:4px 8px; font-size:11.5px;">Migrate</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="padding:14px 20px; border-top:1px solid #e2e8f0;">
                        {{ $invoices->links() }}
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

<script>
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.inv-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
}
</script>
@endsection
