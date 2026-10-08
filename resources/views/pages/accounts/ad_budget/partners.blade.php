@extends('layouts.app')

@section('title', 'Ad Budget — For Partners')

@push('styles')
<style>
.adb-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.adb-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; }
.adb-sub { font-size:13px; color:#64748b; }

.adb-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; }
.adb-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.adb-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.adb-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.adb-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.adb-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:nowrap; overflow-x:auto; }
.adb-search-wrap { position:relative; flex:1; min-width:180px; }
.adb-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.adb-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.adb-select { padding:8px 12px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.adb-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.adb-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.adb-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.adb-btn-secondary { background:#f1f5f9; color:#334155; }
.adb-btn-secondary:hover { background:#e2e8f0; }

.adb-tbl { width:100%; border-collapse:collapse; }
.adb-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; white-space:nowrap; }
.adb-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.adb-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:92%; max-width:560px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; sticky:top; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:16px; }

.summary-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; display:flex; flex-direction:column; gap:10px; }
.summary-row { display:flex; justify-content:space-between; font-size:13px; }
.summary-label { color:#64748b; font-weight:600; }
.summary-val { color:#0f172a; font-weight:700; }
</style>
@endpush

@section('content')
<div class="adb-page">

    {{-- Topbar --}}
    <div class="adb-topbar">
        <div>
            <div class="adb-title">Ad Budget — For Partners</div>
            <div class="adb-sub">Partner Ad Budget payment entries recorded via Lead Payments</div>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div style="background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div style="background:#fee2e2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:10px; font-size:13px; font-weight:600;">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    {{-- Stats Cards --}}
    <div class="adb-stats-grid">
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#fff7ed; color:#ea580c;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Amount Received</div>
                <div class="adb-stat-val">₹{{ number_format($totalAmount, 2) }}</div>
            </div>
        </div>
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Payment Entries</div>
                <div class="adb-stat-val">{{ $totalCount }}</div>
            </div>
        </div>
    </div>

    {{-- Payments Table Card --}}
    <div class="adb-card">

        {{-- Single-Row Filter Bar --}}
        <form method="GET" action="{{ route('accounts.ad-budget.partners') }}">
            <div class="adb-filter-bar">
                {{-- Search --}}
                <div class="adb-search-wrap">
                    <i class="bi bi-search adb-search-ico"></i>
                    <input type="text" name="search" class="adb-search-input" placeholder="Search Lead, Client, Ref #, Notes..." value="{{ request('search') }}">
                </div>

                {{-- Branch Filter --}}
                <select name="branch_id" class="adb-select" style="min-width:170px; flex-shrink:0;">
                    <option value="">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ (string)request('branch_id') === (string)$b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>

                {{-- Date Range Filters --}}
                <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                    <span style="font-size:12px; font-weight:700; color:#64748b;">From:</span>
                    <input type="date" name="from_date" class="adb-select" style="padding:6px 10px;" value="{{ request('from_date') }}">
                </div>

                <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                    <span style="font-size:12px; font-weight:700; color:#64748b;">To:</span>
                    <input type="date" name="to_date" class="adb-select" style="padding:6px 10px;" value="{{ request('to_date') }}">
                </div>

                {{-- Action Buttons --}}
                <button type="submit" class="adb-btn adb-btn-primary" style="padding:8px 16px; font-size:12px; flex-shrink:0;">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                @if(request()->hasAny(['search','branch_id','from_date','to_date']))
                    <a href="{{ route('accounts.ad-budget.partners') }}" class="adb-btn adb-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1; flex-shrink:0;">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        @if($payments->isEmpty())
            <div style="text-align:center; padding:40px; color:#94a3b8;">
                <i class="bi bi-inbox" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                No Partner Ad Budget payment records found.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="adb-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Payment Date</th>
                            <th>Lead / Client</th>
                            <th>Product</th>
                            <th>Branch</th>
                            <th>Payment Mode</th>
                            <th>Ref # / UTR</th>
                            <th>Amount</th>
                            <th>Recorded By</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payments as $item)
                        @php
                            $leadName = $item->lead?->company_name ?: ($item->lead?->contact_name ?: ('Lead #' . $item->lead_id));
                            $modeIcons = [
                                'cash'          => '💵 Cash',
                                'bank_transfer' => '🏦 Bank Transfer',
                                'cheque'        => '📝 Cheque',
                                'upi'           => '📱 UPI',
                                'card'          => '💳 Card',
                            ];
                            $modeLabel = $modeIcons[$item->payment_mode] ?? ucfirst($item->payment_mode ?? 'Other');
                        @endphp
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">#{{ $item->id }}</td>
                            <td style="font-weight:700; white-space:nowrap;">
                                {{ $item->payment_date ? $item->payment_date->format('d M, Y') : '—' }}
                            </td>
                            <td>
                                <div>
                                    @if($canRedirectLead)
                                        <a href="{{ url('/leads/' . $item->lead_id) }}" target="_blank" style="color:#fe5f04; font-weight:700; text-decoration:none;">
                                            {{ $leadName }}
                                        </a>
                                    @else
                                        <span style="color:#0f172a; font-weight:700;">
                                            {{ $leadName }}
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size:11px; color:#64748b;">
                                    Lead #{{ $item->lead_id }} {{ $item->lead?->mobile_number ? '• '.$item->lead->mobile_number : '' }}
                                </div>
                            </td>
                            <td>
                                <strong>{{ $item->product?->product_name ?? ($item->product?->deal_name ?? '—') }}</strong>
                            </td>
                            <td>
                                <span style="font-weight:600; color:#334155;">{{ $item->branch?->name ?? '—' }}</span>
                            </td>
                            <td>
                                <span class="adb-badge" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1;">
                                    {{ $modeLabel }}
                                </span>
                            </td>
                            <td>
                                <span style="font-family:monospace; font-weight:600; color:#475569;">
                                    {{ $item->reference_number ?: '—' }}
                                </span>
                            </td>
                            <td style="font-weight:800; color:#15803d; white-space:nowrap;">
                                ₹{{ number_format($item->amount, 2) }}
                            </td>
                            <td>
                                <div><strong>{{ $item->recordedBy?->name ?? '—' }}</strong></div>
                                <div style="font-size:11px; color:#94a3b8;">{{ $item->created_at ? $item->created_at->format('d M, h:i A') : '' }}</div>
                            </td>
                            <td>
                                <button type="button" class="adb-btn adb-btn-secondary" style="padding:4px 10px; font-size:11px;"
                                        data-item="{{ json_encode([
                                            'id' => $item->id,
                                            'lead_id' => $item->lead_id,
                                            'lead_name' => $leadName,
                                            'product' => $item->product?->product_name ?? '—',
                                            'branch' => $item->branch?->name ?? '—',
                                            'payment_date' => $item->payment_date ? $item->payment_date->format('d M, Y') : '—',
                                            'amount' => number_format($item->amount, 2),
                                            'mode' => $modeLabel,
                                            'ref' => $item->reference_number ?: '—',
                                            'recorded_by' => $item->recordedBy?->name ?? '—',
                                            'created_at' => $item->created_at ? $item->created_at->format('d M, Y h:i A') : '—',
                                            'notes' => $item->notes ?: 'None',
                                            'attachment_url' => $item->attachment_url,
                                            'attachment_name' => $item->attachment_name,
                                        ]) }}"
                                        onclick='openPaymentDetailsModal(JSON.parse(this.getAttribute("data-item")))'>
                                    👁️ View
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($payments->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #e2e8f0;">
                    {{ $payments->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- Payment Details View Modal --}}
<div class="modal-overlay" id="detailsModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Partner Ad Budget Payment Details</div>
            <button type="button" class="modal-close" onclick="closeDetailsModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="summary-card">
                <div class="summary-row">
                    <span class="summary-label">Payment Entry ID</span>
                    <span class="summary-val" id="view-id"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Lead / Client</span>
                    <span class="summary-val" id="view-lead"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Product / Deal</span>
                    <span class="summary-val" id="view-product"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Branch</span>
                    <span class="summary-val" id="view-branch"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Payment Date</span>
                    <span class="summary-val" id="view-date"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Payment Mode</span>
                    <span class="summary-val" id="view-mode"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Ref # / UTR</span>
                    <span class="summary-val" id="view-ref"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Amount Received</span>
                    <span class="summary-val" style="color:#15803d; font-size:15px;" id="view-amount"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Recorded By</span>
                    <span class="summary-val" id="view-user"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Recorded At</span>
                    <span class="summary-val" id="view-created"></span>
                </div>
            </div>

            <div>
                <div style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px;">Notes / Remarks:</div>
                <div style="padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; color:#334155;" id="view-notes"></div>
            </div>

            <div id="view-attachment-wrap" style="display:none;">
                <div style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px;">Attachment / Proof:</div>
                <a id="view-attachment-link" href="#" target="_blank" class="adb-btn adb-btn-secondary" style="font-size:12px;">
                    📎 Download Attachment
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CAN_REDIRECT_LEAD = @json($canRedirectLead);

function openPaymentDetailsModal(item) {
    document.getElementById('view-id').innerText = '#' + item.id;
    if (CAN_REDIRECT_LEAD) {
        document.getElementById('view-lead').innerHTML = '<a href="/leads/' + item.lead_id + '" target="_blank" style="color:#fe5f04; text-decoration:none;">' + item.lead_name + ' (Lead #' + item.lead_id + ')</a>';
    } else {
        document.getElementById('view-lead').innerText = item.lead_name + ' (Lead #' + item.lead_id + ')';
    }
    document.getElementById('view-product').innerText = item.product;
    document.getElementById('view-branch').innerText = item.branch;
    document.getElementById('view-date').innerText = item.payment_date;
    document.getElementById('view-mode').innerText = item.mode;
    document.getElementById('view-ref').innerText = item.ref;
    document.getElementById('view-amount').innerText = '₹' + item.amount;
    document.getElementById('view-user').innerText = item.recorded_by;
    document.getElementById('view-created').innerText = item.created_at;
    document.getElementById('view-notes').innerText = item.notes;

    if (item.attachment_url) {
        document.getElementById('view-attachment-wrap').style.display = 'block';
        document.getElementById('view-attachment-link').href = item.attachment_url;
    } else {
        document.getElementById('view-attachment-wrap').style.display = 'none';
    }

    document.getElementById('detailsModal').classList.add('active');
}

function closeDetailsModal() {
    document.getElementById('detailsModal').classList.remove('active');
}

window.addEventListener('click', function(e) {
    const modal = document.getElementById('detailsModal');
    if (e.target === modal) {
        closeDetailsModal();
    }
});
</script>
@endpush
@endsection
