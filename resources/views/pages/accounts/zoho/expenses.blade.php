@extends('layouts.app')

@section('title', 'Zoho Books Expenses - Accounts')

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
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe5f04" stroke-width="2.2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                Zoho Books Expenses
            </div>
            <div class="zh-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.zoho.index') }}">Zoho Books</a>
                <span>/</span>
                <span>Expenses</span>
            </div>
        </div>
        <div class="zh-actions">
            <a href="{{ route('accounts.zoho.index') }}" class="acm-btn acm-btn-secondary">Hub Overview</a>
            @if($integration->is_connected)
            <form action="{{ route('accounts.zoho.expenses.sync') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="acm-btn acm-btn-primary" onclick="this.innerHTML='Syncing...'; this.disabled=true; this.form.submit();">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Sync Expenses from Zoho
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

        <div style="display:flex; gap:14px; flex-wrap:wrap;">
            <div class="zh-card" style="flex:1; min-width:200px;">
                <div style="font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase;">Total Zoho Expenses</div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:4px;">{{ number_format($totalCount) }}</div>
            </div>
            <div class="zh-card" style="flex:1; min-width:200px;">
                <div style="font-size:12px; color:#64748b; font-weight:700; text-transform:uppercase;">Total Expense Value</div>
                <div style="font-size:22px; font-weight:800; color:#0f172a; margin-top:4px;">₹{{ number_format($totalAmount, 2) }}</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('accounts.zoho.expenses') }}" style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 18px;">
            <div style="flex:1; min-width:200px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Account, Vendor, Description..." style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
            </div>
            <div>
                <select name="migration_status" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
                    <option value="">All Migration Status</option>
                    <option value="migrated" {{ request('migration_status') === 'migrated' ? 'selected' : '' }}>Migrated to CRM</option>
                    <option value="pending" {{ request('migration_status') === 'pending' ? 'selected' : '' }}>Pending Migration</option>
                </select>
            </div>
            <div>
                <input type="date" name="from_date" value="{{ request('from_date') }}" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
            </div>
            <div>
                <input type="date" name="to_date" value="{{ request('to_date') }}" style="padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px;">
            </div>
            <button type="submit" class="acm-btn acm-btn-primary">Filter</button>
            <a href="{{ route('accounts.zoho.expenses') }}" class="acm-btn acm-btn-outline">Reset</a>
        </form>

        <form action="{{ route('accounts.zoho.expenses.bulk-migrate') }}" method="POST">
            @csrf
            <div class="zh-card" style="padding:0; overflow:hidden;">
                <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div style="font-size:13.5px; font-weight:700; color:#0f172a;">
                        Showing {{ $expenses->firstItem() ?? 0 }} to {{ $expenses->lastItem() ?? 0 }} of {{ $expenses->total() }} Expenses
                    </div>
                    <div>
                        <button type="submit" class="acm-btn acm-btn-primary" onclick="return confirm('Migrate selected unmigrated expenses into CRM Expenses?')" style="font-size:12px;">
                            Bulk Migrate to CRM Expenses
                        </button>
                    </div>
                </div>

                @if($expenses->isEmpty())
                    <div style="text-align:center; padding:48px 20px;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:10px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <div style="font-size:14px; font-weight:700; color:#334155;">No Expenses Synced Yet</div>
                        <div style="font-size:12.5px; color:#64748b; margin-top:4px;">Click "Sync Expenses from Zoho" to pull expenses.</div>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="zh-table">
                            <thead>
                                <tr>
                                    <th style="width:36px; text-align:center;">
                                        <input type="checkbox" onchange="document.querySelectorAll('.exp-checkbox').forEach(cb => cb.checked = this.checked)">
                                    </th>
                                    <th>Date</th>
                                    <th>Account Name</th>
                                    <th>Paid Through</th>
                                    <th>Vendor / Payee</th>
                                    <th>Description / Ref</th>
                                    <th style="text-align:right;">Amount</th>
                                    <th style="text-align:center;">CRM Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($expenses as $exp)
                                <tr>
                                    <td style="text-align:center;">
                                        @if(!$exp->is_migrated_to_native)
                                            <input type="checkbox" name="selected_ids[]" value="{{ $exp->id }}" class="exp-checkbox">
                                        @endif
                                    </td>
                                    <td>{{ $exp->expense_date ? $exp->expense_date->format('d M Y') : '—' }}</td>
                                    <td><strong>{{ $exp->account_name ?: 'General Expense' }}</strong></td>
                                    <td>{{ $exp->paid_through_account_name ?: '—' }}</td>
                                    <td>{{ $exp->vendor_name ?: '—' }}</td>
                                    <td>
                                        <div>{{ $exp->description ?: '—' }}</div>
                                        @if($exp->reference_number)
                                            <div style="font-size:11px; color:#94a3b8;">Ref: {{ $exp->reference_number }}</div>
                                        @endif
                                    </td>
                                    <td style="text-align:right; font-weight:700;">₹{{ number_format((float)$exp->total, 2) }}</td>
                                    <td style="text-align:center;">
                                        @if($exp->is_migrated_to_native)
                                            <a href="{{ route('accounts.expenses.show', $exp->migrated_native_expense_id) }}" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#15803d; background:#dcfce7; padding:3px 8px; border-radius:6px; text-decoration:none;">
                                                ✓ In CRM (#{{ $exp->migratedExpense?->expense_number ?? 'EXP' }})
                                            </a>
                                        @else
                                            <span style="font-size:11px; color:#94a3b8; font-weight:600;">Pending</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        @if(!$exp->is_migrated_to_native)
                                            <button type="submit" formaction="{{ route('accounts.zoho.expenses.migrate', $exp->id) }}" class="acm-btn acm-btn-primary" style="padding:4px 8px; font-size:11.5px;">Migrate</button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="padding:14px 20px; border-top:1px solid #e2e8f0;">
                        {{ $expenses->links() }}
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection
