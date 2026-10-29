@extends('layouts.app')

@section('title', 'Zoho Books Quotations - Accounts')

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

.lead-chip { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#f0f9ff; color:#0369a1; border:1px solid #bae6fd; text-decoration:none; }
.lead-chip:hover { background:#e0f2fe; color:#0284c7; }
</style>
@endpush

@section('content')
<div class="zh-page">
    <div class="zh-topbar">
        <div>
            <div class="zh-title">
                <i class="bi bi-file-earmark-text-fill" style="color:#fe5f04;"></i>
                Zoho Books Quotations (Estimates)
            </div>
            <div class="zh-breadcrumbs">
                <a href="{{ route('accounts.dashboard') }}">Accounts</a>
                <span>/</span>
                <a href="{{ route('accounts.zoho.index') }}">Zoho Books</a>
                <span>/</span>
                <span>Quotations</span>
            </div>
        </div>
        <div class="zh-actions">
            <a href="{{ route('accounts.zoho.index') }}" class="acm-btn acm-btn-secondary">
                Hub Overview
            </a>
            <form action="{{ route('accounts.zoho.auto-map-leads') }}" method="POST" style="margin:0;" onsubmit="return confirm('Scan and auto-map all unmapped invoices & quotations to matching CRM Leads?');">
                @csrf
                <button type="submit" class="acm-btn acm-btn-outline" title="Auto-match quotations & invoices to leads by phone, email, company, or contact name">
                    <i class="bi bi-person-check-fill" style="color:#fe5f04;"></i>
                    Auto-Map Leads
                </button>
            </form>
            @if($integration->is_connected)
            <form action="{{ route('accounts.zoho.quotations.sync') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="acm-btn acm-btn-primary" onclick="this.innerHTML='Syncing...'; this.disabled=true; this.form.submit();">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Sync Estimates from Zoho
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
                <div class="zh-stat-label">Filtered Quotations</div>
                <div class="zh-stat-value">{{ number_format($totalCount) }}</div>
            </div>
            <div class="zh-stat-card">
                <div class="zh-stat-label">Total Value</div>
                <div class="zh-stat-value">₹{{ number_format($totalAmount, 2) }}</div>
            </div>
            <div class="zh-stat-card" style="border-left-color:#16a34a;">
                <div class="zh-stat-label">Migrated to CRM</div>
                <div class="zh-stat-value" style="color:#16a34a;">{{ number_format($migratedCount) }}</div>
            </div>
            <div class="zh-stat-card" style="border-left-color:#f59e0b;">
                <div class="zh-stat-label">Pending Migration</div>
                <div class="zh-stat-value" style="color:#d97706;">{{ number_format($pendingCount) }}</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="{{ route('accounts.zoho.quotations') }}" class="zh-filter-bar">
            <div style="flex:1; min-width:200px;">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Estimate #, Customer, Company..." class="zh-filter-input" style="width:100%;">
            </div>

            <div>
                <select name="status" class="zh-filter-input">
                    <option value="all">All Zoho Statuses</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="invoiced" {{ request('status') === 'invoiced' ? 'selected' : '' }}>Invoiced</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="declined" {{ request('status') === 'declined' ? 'selected' : '' }}>Declined</option>
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
            <a href="{{ route('accounts.zoho.quotations') }}" class="acm-btn acm-btn-outline" style="padding:8px 14px;">Reset</a>
        </form>

        <!-- Quotations Table Card -->
        <form id="bulkMigrateForm" action="{{ route('accounts.zoho.quotations.bulk-migrate') }}" method="POST">
            @csrf
            <div class="zh-card" style="padding:0; overflow:hidden;">
                <div style="padding:16px 20px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div style="font-size:13.5px; font-weight:700; color:#0f172a;">
                        Showing {{ $quotations->firstItem() ?? 0 }} to {{ $quotations->lastItem() ?? 0 }} of {{ $quotations->total() }} Quotations
                    </div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <button type="button" class="acm-btn" style="background:#0284c7; color:#fff; font-size:12px;" onclick="openBatchModal()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                            Batch Migrate All (Auto Runner)
                        </button>
                        <button type="submit" class="acm-btn acm-btn-primary" onclick="return confirm('Migrate selected unmigrated quotations into CRM Quotations and map to Leads?')" style="font-size:12px;">
                            Bulk Migrate Selected
                        </button>
                    </div>
                </div>

                @if($quotations->isEmpty())
                    <div style="text-align:center; padding:48px 20px;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom:10px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                        <div style="font-size:14px; font-weight:700; color:#334155;">No Quotations Found</div>
                        <div style="font-size:12.5px; color:#64748b; margin-top:4px;">Try clearing filters or sync estimates from Zoho Books.</div>
                    </div>
                @else
                    <div style="overflow-x:auto;">
                        <table class="zh-table">
                            <thead>
                                <tr>
                                    <th style="width:36px; text-align:center;">
                                        <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
                                    </th>
                                    <th>Estimate #</th>
                                    <th>Customer Details</th>
                                    <th>Mapped Lead</th>
                                    <th>Date</th>
                                    <th>Valid Until</th>
                                    <th style="text-align:right;">Subtotal</th>
                                    <th style="text-align:right;">Total Amount</th>
                                    <th style="text-align:center;">Zoho Status</th>
                                    <th style="text-align:center;">CRM Status</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quotations as $quotation)
                                <tr>
                                    <td style="text-align:center;">
                                        @if(!$quotation->is_migrated_to_native)
                                            <input type="checkbox" name="selected_ids[]" value="{{ $quotation->id }}" class="quote-checkbox">
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('accounts.zoho.show-quotation', $quotation->id) }}" style="font-weight:700; color:#fe5f04; text-decoration:none;">
                                            {{ $quotation->estimate_number }}
                                        </a>
                                        @if($quotation->reference_number)
                                            <div style="font-size:11px; color:#94a3b8;">Ref: {{ $quotation->reference_number }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:#0f172a;">{{ $quotation->customer_name }}</div>
                                        @if($quotation->company_name && $quotation->company_name !== $quotation->customer_name)
                                            <div style="font-size:11.5px; color:#64748b;">{{ $quotation->company_name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($quotation->migratedQuotation && $quotation->migratedQuotation->lead)
                                            <a href="{{ route('leads.show', $quotation->migratedQuotation->lead->id) }}" class="lead-chip" target="_blank" title="View Lead Profile">
                                                <i class="bi bi-person-check-fill"></i> {{ $quotation->migratedQuotation->lead->name }}
                                            </a>
                                        @else
                                            <span style="font-size:11px; color:#94a3b8;">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $quotation->estimate_date ? $quotation->estimate_date->format('d M Y') : '—' }}</td>
                                    <td>{{ $quotation->expiry_date ? $quotation->expiry_date->format('d M Y') : '—' }}</td>
                                    <td style="text-align:right;">₹{{ number_format((float)$quotation->sub_total, 2) }}</td>
                                    <td style="text-align:right; font-weight:700;">₹{{ number_format((float)$quotation->total, 2) }}</td>
                                    <td style="text-align:center;">
                                        <span style="display:inline-block; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; text-transform:uppercase; background:{{ in_array($quotation->status, ['accepted', 'invoiced']) ? '#dcfce7' : ($quotation->status === 'sent' ? '#e0f2fe' : ($quotation->status === 'declined' ? '#fee2e2' : '#f1f5f9')) }}; color:{{ in_array($quotation->status, ['accepted', 'invoiced']) ? '#15803d' : ($quotation->status === 'sent' ? '#0369a1' : ($quotation->status === 'declined' ? '#b91c1c' : '#475569')) }};">
                                            {{ $quotation->status }}
                                        </span>
                                    </td>
                                    <td style="text-align:center;">
                                        @if($quotation->is_migrated_to_native && $quotation->migratedQuotation)
                                            <a href="{{ route('quotations.show', $quotation->migrated_native_quotation_id) }}" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:#15803d; background:#dcfce7; padding:3px 8px; border-radius:6px; text-decoration:none;">
                                                ✓ Migrated
                                            </a>
                                        @else
                                            <span style="font-size:11px; color:#94a3b8; font-weight:600;">Pending</span>
                                        @endif
                                    </td>
                                    <td style="text-align:right;">
                                        <div style="display:inline-flex; align-items:center; gap:6px;">
                                            <a href="{{ route('accounts.zoho.show-quotation', $quotation->id) }}" class="acm-btn acm-btn-outline" style="padding:4px 8px; font-size:11.5px;">View</a>
                                            @if(!$quotation->is_migrated_to_native)
                                                <button type="submit" formaction="{{ route('accounts.zoho.quotations.migrate', $quotation->id) }}" class="acm-btn acm-btn-primary" style="padding:4px 8px; font-size:11.5px;">Migrate</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="padding:14px 20px; border-top:1px solid #e2e8f0;">
                        {{ $quotations->links() }}
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Batch Migration Progress Modal -->
<div id="batchModalOverlay" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(3px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; width:90%; max-width:520px; padding:28px; box-shadow:0 20px 40px rgba(0,0,0,0.25);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
            <div style="width:40px; height:40px; border-radius:10px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:20px;">
                <i class="bi bi-file-earmark-arrow-up"></i>
            </div>
            <div>
                <div style="font-size:16px; font-weight:800; color:#0f172a;">Migrating Zoho Quotations</div>
                <div style="font-size:12px; color:#64748b;">Matching customer phone to Leads and creating native quotations</div>
            </div>
        </div>

        <div style="background:#f1f5f9; border-radius:10px; height:12px; overflow:hidden; margin:18px 0 10px;">
            <div id="batchProgressBar" style="width:0%; height:100%; background:linear-gradient(90deg,#fe5f04,#f97316); transition:width .3s ease;"></div>
        </div>

        <div style="display:flex; justify-content:space-between; font-size:12px; color:#64748b; font-weight:600; margin-bottom:16px;">
            <span id="batchStatusText">Preparing runner...</span>
            <span id="batchProgressPct">0%</span>
        </div>

        <div id="batchLogBox" style="background:#0f172a; color:#cbd5e1; border-radius:8px; padding:12px; font-size:11px; font-family:monospace; height:120px; overflow-y:auto; margin-bottom:18px; line-height:1.5;">
            <div>> Initializing batch quotation migration...</div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" id="batchCancelBtn" class="acm-btn acm-btn-secondary" onclick="stopBatchMigration()">Cancel</button>
            <button type="button" id="batchCloseBtn" class="acm-btn acm-btn-primary" style="display:none;" onclick="location.reload()">Done / Reload</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleSelectAll(master) {
    document.querySelectorAll('.quote-checkbox').forEach(cb => {
        cb.checked = master.checked;
    });
}

let isBatchRunning = false;
let shouldStopBatch = false;

function openBatchModal() {
    document.getElementById('batchModalOverlay').style.display = 'flex';
    document.getElementById('batchProgressBar').style.width = '0%';
    document.getElementById('batchProgressPct').innerText = '0%';
    document.getElementById('batchStatusText').innerText = 'Starting migration...';
    document.getElementById('batchLogBox').innerHTML = '<div>> Initializing batch quotation migration...</div>';
    document.getElementById('batchCancelBtn').style.display = 'inline-flex';
    document.getElementById('batchCloseBtn').style.display = 'none';

    isBatchRunning = true;
    shouldStopBatch = false;
    runBatchStep();
}

function stopBatchMigration() {
    shouldStopBatch = true;
    document.getElementById('batchModalOverlay').style.display = 'none';
}

function appendBatchLog(msg) {
    const box = document.getElementById('batchLogBox');
    if (!box) return;
    const line = document.createElement('div');
    line.innerText = msg;
    box.appendChild(line);
    box.scrollTop = box.scrollHeight;
}

function runBatchStep() {
    if (shouldStopBatch) {
        appendBatchLog('> Batch migration cancelled by user.');
        return;
    }

    fetch('{{ route('accounts.zoho.quotations.migrate-batch') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ limit: 10 })
    })
    .then(async r => {
        if (!r.ok) {
            const errText = await r.text();
            throw new Error(`Server returned HTTP ${r.status} (${r.statusText || 'Error'})`);
        }
        return r.json();
    })
    .then(data => {
        if (!data.success) {
            appendBatchLog('> ERROR: ' + (data.message || 'Batch migration failed'));
            document.getElementById('batchStatusText').innerText = 'Migration encountered an error.';
            document.getElementById('batchCancelBtn').style.display = 'none';
            document.getElementById('batchCloseBtn').style.display = 'inline-flex';
            return;
        }

        const pct = data.progress_pct || 0;
        document.getElementById('batchProgressBar').style.width = pct + '%';
        document.getElementById('batchProgressPct').innerText = pct + '%';
        document.getElementById('batchStatusText').innerText = `Migrated ${data.total_migrated} of ${data.total_all} (${data.remaining} remaining)`;

        appendBatchLog(`> Migrated batch: ${data.batch_migrated} quotations. Remaining: ${data.remaining}`);

        if (data.errors && data.errors.length) {
            data.errors.forEach(err => appendBatchLog(`! Error on ${err}`));
        }

        if (data.completed || data.remaining === 0) {
            appendBatchLog('> All quotations have been migrated successfully & mapped to Leads!');
            document.getElementById('batchStatusText').innerText = 'Migration Complete!';
            document.getElementById('batchProgressBar').style.width = '100%';
            document.getElementById('batchProgressPct').innerText = '100%';
            document.getElementById('batchCancelBtn').style.display = 'none';
            document.getElementById('batchCloseBtn').style.display = 'inline-flex';
            isBatchRunning = false;
        } else {
            setTimeout(runBatchStep, 600);
        }
    })
    .catch(err => {
        appendBatchLog('> Network error: ' + err.message);
        document.getElementById('batchCancelBtn').style.display = 'none';
        document.getElementById('batchCloseBtn').style.display = 'inline-flex';
    });
}
</script>
@endpush
@endsection
