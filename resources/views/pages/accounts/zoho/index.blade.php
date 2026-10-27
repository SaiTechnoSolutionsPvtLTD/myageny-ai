@extends('layouts.app')

@section('title', 'Zoho Books Migration Hub - Accounts')

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
.zh-stat-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:18px 20px; position:relative; overflow:hidden; }
.zh-stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:#fe5f04; }
.zh-stat-label { font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; margin-bottom:6px; }
.zh-stat-value { font-size:22px; font-weight:800; color:#0f172a; }
.zh-stat-sub { font-size:12px; color:#94a3b8; margin-top:4px; }

.zh-status-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.zh-status-badge.connected { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.zh-status-badge.disconnected { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

.zh-step-card { display:flex; align-items:flex-start; gap:16px; padding:16px; border:1px solid #f1f5f9; border-radius:10px; background:#f8fafc; }
.zh-step-num { width:32px; height:32px; border-radius:50%; background:#fe5f04; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; flex-shrink:0; }

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
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fe5f04" stroke-width="2.2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Zoho Books Migration Hub
            </div>
            <div class="zh-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <span>Zoho Books Sync & Migration</span>
            </div>
        </div>
        <div class="zh-actions">
            <a href="{{ route('accounts.zoho.settings') }}" class="acm-btn acm-btn-outline">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                API Settings
            </a>
            <a href="{{ route('accounts.zoho.invoices') }}" class="acm-btn acm-btn-secondary">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                View Zoho Invoices ({{ $totalInvoicesCount }})
            </a>
            @if($integration->is_connected)
            <form action="{{ route('accounts.zoho.invoices.sync') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="acm-btn acm-btn-primary" onclick="this.innerHTML='Syncing...'; this.disabled=true; this.form.submit();">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Sync Invoices Now
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="zh-body">
        @if(session('success'))
            <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:10px; font-size:13.5px; font-weight:600; display:flex; align-items:center; gap:10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error') || $integration->last_error)
            <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:14px 18px; border-radius:10px; font-size:13.5px; font-weight:600; display:flex; align-items:center; gap:10px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                {{ session('error') ?: $integration->last_error }}
            </div>
        @endif

        <!-- Connection Banner -->
        <div class="zh-card" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
            <div style="display:flex; align-items:center; gap:16px;">
                <div style="width:48px; height:48px; border-radius:12px; background:#fff7ed; display:flex; align-items:center; justify-content:center; color:#fe5f04; border:1px solid #ffedd5;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <h2 style="font-size:16px; font-weight:800; color:#0f172a; margin:0;">Zoho Books API v3 Connection</h2>
                        @if($integration->is_connected)
                            <span class="zh-status-badge connected">● Connected</span>
                        @else
                            <span class="zh-status-badge disconnected">● Disconnected</span>
                        @endif
                    </div>
                    <div style="font-size:12.5px; color:#64748b; margin-top:4px;">
                        @if($integration->is_connected)
                            Active Organization: <strong style="color:#0f172a;">{{ $integration->organization_name ?: 'Default' }}</strong> (ID: {{ $integration->organization_id }}) &bull; Region: <strong>{{ strtoupper($integration->region) }}</strong> &bull; Last Synced: {{ $integration->last_synced_at ? $integration->last_synced_at->format('d M Y, h:i A') : 'Never' }}
                        @else
                            No active OAuth session. Please connect your Zoho account in settings to fetch invoices.
                        @endif
                    </div>
                </div>
            </div>
            <div>
                @if($integration->is_connected)
                    <button onclick="testConnection()" class="acm-btn acm-btn-outline" style="font-size:12px;">Test Connection</button>
                @else
                    <a href="{{ route('accounts.zoho.settings') }}" class="acm-btn acm-btn-primary" style="font-size:12px;">Configure & Connect</a>
                @endif
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="zh-stats-grid">
            <div class="zh-stat-card">
                <div class="zh-stat-label">Total Zoho Invoices</div>
                <div class="zh-stat-value">{{ number_format($totalInvoicesCount) }}</div>
                <div class="zh-stat-sub">Fetched from Zoho Books</div>
            </div>
            <div class="zh-stat-card">
                <div class="zh-stat-label">Total Invoiced Value</div>
                <div class="zh-stat-value">₹{{ number_format($totalInvoiceAmount, 2) }}</div>
                <div class="zh-stat-sub">Gross Zoho Billing</div>
            </div>
            <div class="zh-stat-card">
                <div class="zh-stat-label">Total Balance Due</div>
                <div class="zh-stat-value" style="color:#dc2626;">₹{{ number_format($totalBalanceDue, 2) }}</div>
                <div class="zh-stat-sub">Uncollected in Zoho</div>
            </div>
            <div class="zh-stat-card">
                <div class="zh-stat-label">Migrated to CRM</div>
                <div class="zh-stat-value" style="color:#16a34a;">{{ number_format($migratedInvoicesCount) }} / {{ number_format($totalInvoicesCount) }}</div>
                <div class="zh-stat-sub">{{ number_format($pendingInvoicesCount) }} pending migration</div>
            </div>
        </div>

        <!-- 4-Step Migration Workflow Card -->
        <div class="zh-card">
            <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin-top:0; margin-bottom:14px;">Zoho Books Migration Workflow</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px;">
                <div class="zh-step-card">
                    <div class="zh-step-num">1</div>
                    <div>
                        <div style="font-weight:700; font-size:13.5px; color:#0f172a;">Connect API / OAuth</div>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Enter Client ID, Secret, or Refresh Token from Zoho Developer Console.</div>
                    </div>
                </div>
                <div class="zh-step-card">
                    <div class="zh-step-num">2</div>
                    <div>
                        <div style="font-weight:700; font-size:13.5px; color:#0f172a;">Sync Invoices & Line Items</div>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Fetches all invoices, customers, line items, and taxes via Zoho API v3.</div>
                    </div>
                </div>
                <div class="zh-step-card">
                    <div class="zh-step-num">3</div>
                    <div>
                        <div style="font-weight:700; font-size:13.5px; color:#0f172a;">View & Verify in Software</div>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">Review all Zoho invoices with detailed line items and status right here.</div>
                    </div>
                </div>
                <div class="zh-step-card">
                    <div class="zh-step-num">4</div>
                    <div>
                        <div style="font-weight:700; font-size:13.5px; color:#0f172a;">Migrate into CRM Ledger</div>
                        <div style="font-size:12px; color:#64748b; margin-top:4px;">1-Click convert Zoho invoices into active CRM Sales Invoices & Customer records.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Invoices Table -->
        <div class="zh-card" style="padding:0; overflow:hidden;">
            <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 style="font-size:15px; font-weight:800; color:#0f172a; margin:0;">Recent Synced Invoices</h3>
                    <div style="font-size:12px; color:#64748b; margin-top:2px;">Showing latest invoices fetched from Zoho Books</div>
                </div>
                <a href="{{ route('accounts.zoho.invoices') }}" class="acm-btn acm-btn-outline" style="font-size:12px;">View All ({{ $totalInvoicesCount }})</a>
            </div>

            @if($recentInvoices->isEmpty())
                <div style="text-align:center; padding:40px 20px;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:10px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                    <div style="font-size:14px; font-weight:700; color:#334155;">No Zoho Invoices Synced Yet</div>
                    <div style="font-size:12.5px; color:#64748b; margin-top:4px; max-width:400px; margin-left:auto; margin-right:auto;">
                        Connect your Zoho Books account and click "Sync Invoices Now" to pull all invoices into this software.
                    </div>
                    @if($integration->is_connected)
                        <form action="{{ route('accounts.zoho.invoices.sync') }}" method="POST" style="margin-top:14px;">
                            @csrf
                            <button type="submit" class="acm-btn acm-btn-primary">Sync Now</button>
                        </form>
                    @else
                        <a href="{{ route('accounts.zoho.settings') }}" class="acm-btn acm-btn-primary" style="margin-top:14px;">Go to Settings & Connect</a>
                    @endif
                </div>
            @else
                <div style="overflow-x:auto;">
                    <table class="zh-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer Name</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th style="text-align:right;">Total Amount</th>
                                <th style="text-align:right;">Balance</th>
                                <th style="text-align:center;">Zoho Status</th>
                                <th style="text-align:center;">CRM Migration</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentInvoices as $inv)
                            <tr>
                                <td>
                                    <a href="{{ route('accounts.zoho.show-invoice', $inv->id) }}" style="font-weight:700; color:#fe5f04; text-decoration:none;">
                                        {{ $inv->invoice_number }}
                                    </a>
                                </td>
                                <td>
                                    <div style="font-weight:600; color:#0f172a;">{{ $inv->customer_name }}</div>
                                    @if($inv->company_name && $inv->company_name !== $inv->customer_name)
                                        <div style="font-size:11.5px; color:#64748b;">{{ $inv->company_name }}</div>
                                    @endif
                                </td>
                                <td>{{ $inv->invoice_date ? $inv->invoice_date->format('d M Y') : '—' }}</td>
                                <td>{{ $inv->due_date ? $inv->due_date->format('d M Y') : '—' }}</td>
                                <td style="text-align:right; font-weight:700;">₹{{ number_format((float)$inv->total, 2) }}</td>
                                <td style="text-align:right; font-weight:700; color:{{ $inv->balance > 0 ? '#dc2626' : '#16a34a' }};">
                                    ₹{{ number_format((float)$inv->balance, 2) }}
                                </td>
                                <td style="text-align:center;">
                                    <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; text-transform:uppercase; background:{{ $inv->status === 'paid' ? '#dcfce7' : ($inv->status === 'sent' ? '#e0f2fe' : '#f1f5f9') }}; color:{{ $inv->status === 'paid' ? '#15803d' : ($inv->status === 'sent' ? '#0369a1' : '#475569') }};">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    @if($inv->is_migrated_to_native)
                                        <a href="{{ route('accounts.sales.invoices.show', $inv->migrated_native_invoice_id) }}" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#15803d; background:#dcfce7; padding:3px 8px; border-radius:6px; text-decoration:none;">
                                            ✓ In CRM (#{{ $inv->migratedInvoice?->invoice_number ?? $inv->invoice_number }})
                                        </a>
                                    @else
                                        <span style="font-size:11px; color:#94a3b8; font-weight:600;">Not Migrated</span>
                                    @endif
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex; align-items:center; gap:6px;">
                                        <a href="{{ route('accounts.zoho.show-invoice', $inv->id) }}" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11.5px;">View</a>
                                        @if(!$inv->is_migrated_to_native)
                                            <form action="{{ route('accounts.zoho.invoices.migrate', $inv->id) }}" method="POST" style="margin:0;">
                                                @csrf
                                                <button type="submit" class="acm-btn acm-btn-primary" style="padding:4px 8px; font-size:11.5px;">Migrate</button>
                                            </form>
                                        @endif
                                    </div>
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
function testConnection() {
    fetch('{{ route("accounts.zoho.test-connection") }}')
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if(data.success) {
                location.reload();
            }
        })
        .catch(e => alert('Connection test error: ' + e));
}
</script>
@endsection
