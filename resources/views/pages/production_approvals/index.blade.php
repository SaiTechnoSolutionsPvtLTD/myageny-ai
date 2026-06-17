@extends('layouts.app')

@section('title', 'Production Approvals')

@push('styles')
<style>
.pa-page { min-height:100%; background:linear-gradient(180deg,#f7f8fb 0%,#f1f5f9 100%); font-family:'Inter',sans-serif; }
.pa-topbar { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:0 28px; height:64px; background:#fff; border-bottom:1px solid #e5e7eb; }
.pa-title { font-size:20px; font-weight:900; color:#111827; }
.pa-breadcrumb { font-size:12px; color:#6b7280; margin-top:3px; }
.pa-chip { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#eef6ff; border:1px solid #cfe1ff; color:#1d4ed8; font-size:12px; font-weight:800; }
.pa-body { padding:22px 28px 34px; display:grid; gap:18px; }
.pa-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
.pa-card { display:block; text-decoration:none; border-radius:22px; border:1px solid #e5e7eb; padding:18px; box-shadow:0 12px 34px rgba(15,23,42,.06); transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
.pa-card:hover { transform:translateY(-2px); box-shadow:0 16px 38px rgba(15,23,42,.1); }
.pa-card.pending { background:linear-gradient(180deg,#fffaf3 0%,#ffffff 100%); border-color:#f6d7a7; }
.pa-card.approval { background:linear-gradient(180deg,#f3fcf5 0%,#ffffff 100%); border-color:#bce6c7; }
.pa-card.rejected { background:linear-gradient(180deg,#fff5f5 0%,#ffffff 100%); border-color:#f5c2c7; }
.pa-card.is-active.pending { box-shadow:0 0 0 3px rgba(245,158,11,.14), 0 16px 38px rgba(15,23,42,.1); }
.pa-card.is-active.approval { box-shadow:0 0 0 3px rgba(34,197,94,.14), 0 16px 38px rgba(15,23,42,.1); }
.pa-card.is-active.rejected { box-shadow:0 0 0 3px rgba(239,68,68,.12), 0 16px 38px rgba(15,23,42,.1); }
.pa-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; }
.pa-card-title { font-size:16px; font-weight:900; color:#111827; }
.pa-card-sub { margin-top:4px; font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:.08em; }
.pa-badge { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; border:1px solid transparent; }
.pa-badge.pending { color:#b45309; background:#fff1d6; border-color:#fcd9a2; }
.pa-badge.approval { color:#166534; background:#e9f9ee; border-color:#bce6c7; }
.pa-badge.rejected { color:#b91c1c; background:#fee2e2; border-color:#fecaca; }
.pa-card-count { margin-top:18px; font-size:44px; line-height:1; font-weight:900; letter-spacing:-.05em; color:#111827; }
.pa-table-card { background:#fff; border:1px solid #e5e7eb; border-radius:22px; overflow:hidden; box-shadow:0 12px 34px rgba(15,23,42,.06); }
.pa-table-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:16px 18px; border-bottom:1px solid #eef2f7; background:#fcfcfd; }
.pa-table-title { font-size:16px; font-weight:900; color:#111827; }
.pa-table-sub { font-size:12px; color:#6b7280; margin-top:3px; }
.pa-table-wrap { overflow-x:auto; }
.pa-table { width:100%; border-collapse:collapse; }
.pa-table th { padding:12px 14px; text-align:left; border-bottom:1px solid #eef2f7; background:#fafbfc; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; white-space:nowrap; }
.pa-table td { padding:14px; border-bottom:1px solid #f3f4f6; font-size:13px; color:#111827; vertical-align:middle; }
.pa-table tbody tr:hover td { background:#fafafa; }
.pa-product { font-weight:800; color:#111827; }
.pa-meta { font-size:11px; color:#6b7280; margin-top:3px; }
.pa-status-pill { display:inline-flex; align-items:center; padding:5px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:capitalize; border:1px solid transparent; }
.pa-status-pill.pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.pa-status-pill.approval { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.pa-status-pill.rejected { color:#b91c1c; background:#fef2f2; border-color:#fecaca; }
.pa-action-btn { display:inline-flex; align-items:center; justify-content:center; padding:9px 12px; border-radius:10px; background:#eff6ff; border:1px solid #bfdbfe; color:#1d4ed8; font-size:12px; font-weight:800; text-decoration:none; cursor:pointer; }
.pa-action-btn:hover { background:#dbeafe; }
.pa-action-muted { font-size:12px; color:#9ca3af; }
.pa-empty { padding:40px 20px; text-align:center; color:#6b7280; font-size:13px; }
.pa-flash { padding:12px 14px; border-radius:14px; font-size:13px; font-weight:700; }
.pa-flash.success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
.pa-modal { position:fixed; inset:0; display:none; align-items:center; justify-content:center; padding:16px; background:rgba(15,23,42,.58); z-index:1200; }
.pa-modal.is-open { display:flex; }
.pa-modal-card { width:min(100%, 1480px); height:min(94vh, 980px); display:flex; flex-direction:column; background:#fff; border-radius:24px; border:1px solid #e5e7eb; box-shadow:0 24px 80px rgba(15,23,42,.22); overflow:hidden; }
.pa-modal-head { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:18px 20px; border-bottom:1px solid #eef2f7; }
.pa-modal-title { font-size:18px; font-weight:900; color:#111827; }
.pa-modal-sub { margin-top:4px; font-size:12px; color:#6b7280; }
.pa-modal-close { border:none; background:#f8fafc; width:36px; height:36px; border-radius:10px; cursor:pointer; font-size:18px; color:#475569; }
.pa-modal-card form { display:flex; flex-direction:column; flex:1; min-height:0; }
.pa-modal-body { padding:20px; display:grid; gap:20px; overflow-y:auto; flex:1; min-height:0; }
.pa-review-layout { display:grid; gap:20px; align-items:start; }
.pa-remarks-panel { display:grid; gap:14px; padding:18px; border:1px solid #e5e7eb; border-radius:18px; background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%); }
.pa-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; }
.pa-input { width:100%; padding:11px 12px; border:1px solid #dbe1e8; border-radius:12px; background:#f8fafc; font-size:13px; color:#111827; }
.pa-textarea { min-height:160px; resize:vertical; }
.pa-link { display:inline-flex; align-items:center; gap:8px; color:#1d4ed8; font-size:13px; font-weight:700; text-decoration:none; }
.pa-link:hover { text-decoration:underline; }
.pa-custom-panel { display:grid; gap:14px; padding:18px; border:1px solid #e5e7eb; border-radius:18px; background:linear-gradient(180deg,#f8fbff 0%,#ffffff 100%); grid-column:1/-1; }
.pa-custom-list { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
.pa-custom-item { padding:12px 14px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
.pa-custom-item-label { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; }
.pa-custom-item-value { margin-top:6px; font-size:13px; color:#111827; line-height:1.6; word-break:break-word; }
.pa-custom-empty { padding:16px; border:1px dashed #cbd5e1; border-radius:14px; background:#fff; color:#94a3b8; font-size:13px; text-align:center; }
.pa-modal-actions { display:flex; align-items:center; justify-content:flex-end; gap:10px; padding:16px 20px 20px; border-top:1px solid #eef2f7; background:#fff; position:sticky; bottom:0; z-index:2; }
.pa-btn { display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; border-radius:12px; border:1px solid #d1d5db; background:#fff; color:#111827; font-size:13px; font-weight:800; cursor:pointer; }
.pa-btn.approve { background:#166534; border-color:#166534; color:#fff; }
.pa-btn.reject { background:#b91c1c; border-color:#b91c1c; color:#fff; }
@media (max-width: 1280px) {
    .pa-custom-list { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width: 1100px) {
    .pa-grid { grid-template-columns:1fr; }
}
@media (max-width: 768px) {
    .pa-topbar { height:auto; padding:16px 18px; align-items:flex-start; flex-direction:column; }
    .pa-body { padding:18px 16px 24px; }
    .pa-custom-list { grid-template-columns:1fr; }
    .pa-modal { padding:8px; }
    .pa-modal-card { width:100%; height:96vh; border-radius:18px; }
    .pa-modal-body { padding:16px; }
    .pa-modal-actions { padding:14px 16px 16px; }
}
</style>
@endpush

@section('content')
<div class="pa-page">
    <div class="pa-topbar">
        <div>
            <div class="pa-title">Production Approvals</div>
            <div class="pa-breadcrumb">CRM Dashboard > Production Approvals</div>
        </div>
        <div class="pa-chip">Approval Stage Tracker</div>
    </div>

    <div class="pa-body">
        @if(session('success'))
            <div class="pa-flash success">{{ session('success') }}</div>
        @endif

        <section class="pa-grid">
            @foreach($cards as $key => $card)
                <a
                    href="{{ route('production-approvals.index', ['bucket' => $key]) }}"
                    class="pa-card {{ $key }} {{ $selectedBucket === $key ? 'is-active' : '' }}"
                >
                    <div class="pa-card-head">
                        <div>
                            <div class="pa-card-title">{{ $card['title'] }}</div>
                            <div class="pa-card-sub">{{ $card['status_label'] }}</div>
                        </div>
                        <span class="pa-badge {{ $key }}">{{ $card['status_label'] }}</span>
                    </div>
                    <div class="pa-card-count">{{ number_format($card['count']) }}</div>
                </a>
            @endforeach
        </section>

        <section class="pa-table-card">
            <div class="pa-table-head">
                <div>
                    <div class="pa-table-title">{{ $selectedCard['title'] }} List</div>
                    <div class="pa-table-sub">Showing items currently in {{ strtolower($selectedCard['status_label']) }}.</div>
                </div>
                <span class="pa-badge {{ $selectedBucket }}">{{ number_format($selectedCard['count']) }} Items</span>
            </div>

            @if($selectedCard['items']->isNotEmpty())
                <div class="pa-table-wrap">
                    <table class="pa-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Lead / Company</th>
                                <th>Department</th>
                                <th>OVP Reviewed</th>
                                <th>Status</th>
                                <th>Actioned By</th>
                                <th>Actioned On</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedCard['items'] as $item)
                                @php
                                    $statusBucket = match (strtolower((string) $item->production_approval_status)) {
                                        'approval', 'approved' => 'approval',
                                        'rejected', 'reject' => 'rejected',
                                        default => 'pending',
                                    };
                                    $displayCompany = $item->company_name ?: ($item->lead?->company_name ?: 'No company');
                                    $displayClient = $item->client_name ?: ($item->lead?->contact_name ?: 'No client');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="pa-product">{{ $item->product_name }}</div>
                                        <div class="pa-meta">Working days: {{ $item->total_working_days }}</div>
                                    </td>
                                    <td>
                                        {{ $displayCompany }}
                                        <div class="pa-meta">{{ $displayClient }}</div>
                                    </td>
                                    <td>{{ $item->department?->name ?: 'No department' }}</td>
                                    <td>
                                        {{ optional($item->reviewed_at)->format('d M Y h:i A') ?: 'Not reviewed' }}
                                        <div class="pa-meta">{{ $item->reviewedBy?->name ?: 'OVP pending' }}</div>
                                    </td>
                                    <td><span class="pa-status-pill {{ $statusBucket }}">{{ str_replace('_', ' ', (string) $item->production_approval_status) }}</span></td>
                                    <td>{{ $item->productionApprovalReviewedBy?->name ?: 'Pending' }}</td>
                                    <td>{{ optional($item->production_approval_reviewed_at)->format('d M Y h:i A') ?: 'Pending' }}</td>
                                    <td>
                                        @if($statusBucket === 'pending')
                                            <button
                                                type="button"
                                                class="pa-action-btn"
                                                data-pa-open
                                                data-action="{{ route('production-approvals.review', $item) }}"
                                                data-approval-remarks="{{ $item->production_approval_remarks }}"
                                                data-custom-form='@json($item->custom_form_data ?? [])'
                                            >
                                                Review
                                            </button>
                                        @else
                                            <span class="pa-action-muted">Completed</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="pa-empty">No production approval items are available in this status right now.</div>
            @endif
        </section>
    </div>

    <div class="pa-modal" id="pa-review-modal">
        <div class="pa-modal-card">
            <div class="pa-modal-head">
                <div>
                    <div class="pa-modal-title">Production Approval Review</div>
                    <div class="pa-modal-sub">Approve or reject this request. The action user, date, and time will be saved automatically.</div>
                </div>
                <button type="button" class="pa-modal-close" data-pa-close>&times;</button>
            </div>

            <form method="POST" id="pa-review-form">
                @csrf
                <input type="hidden" name="decision" id="pa-decision-input">

                <div class="pa-modal-body">
                    <div class="pa-review-layout">
                        <div class="pa-custom-panel">
                            <div id="pa-custom-form-wrap" class="pa-custom-list"></div>
                        </div>

                        <div class="pa-remarks-panel">
                            <label class="pa-label" for="pa-approval-remarks">Production Approval Remarks</label>
                            <textarea id="pa-approval-remarks" name="production_approval_remarks" class="pa-input pa-textarea" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="pa-modal-actions">
                    <button type="button" class="pa-btn" data-pa-close>Cancel</button>
                    <button type="submit" class="pa-btn reject" data-decision="rejected">Reject</button>
                    <button type="submit" class="pa-btn approve" data-decision="approval">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('pa-review-modal');
    const form = document.getElementById('pa-review-form');

    if (!modal || !form) {
        return;
    }

    const decisionInput = document.getElementById('pa-decision-input');
    const approvalRemarksInput = document.getElementById('pa-approval-remarks');
    const customFormWrap = document.getElementById('pa-custom-form-wrap');

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderCustomFormData(entries) {
        if (!customFormWrap) {
            return;
        }

        if (!Array.isArray(entries) || entries.length === 0) {
            customFormWrap.innerHTML = '<div class="pa-custom-empty">No customized form data available for this request.</div>';
            return;
        }

        customFormWrap.innerHTML = entries.map(function (entry) {
            let valueHtml = '-';

            if (entry && entry.type === 'file' && entry.value && typeof entry.value === 'object') {
                valueHtml = '<a href="' + escapeHtml(entry.value.url || '#') + '" target="_blank" rel="noopener" class="pa-link">' + escapeHtml(entry.value.name || 'View file') + '</a>';
            } else if (Array.isArray(entry && entry.value)) {
                valueHtml = escapeHtml(entry.value.join(', '));
            } else if (entry && entry.value !== null && entry.value !== undefined && entry.value !== '') {
                valueHtml = escapeHtml(String(entry.value));
            }

            return '<div class="pa-custom-item">' +
                '<div class="pa-custom-item-label">' + escapeHtml(entry.label || entry.field_name || 'Field') + '</div>' +
                '<div class="pa-custom-item-value">' + valueHtml + '</div>' +
            '</div>';
        }).join('');
    }

    function openModal(button) {
        form.action = button.dataset.action || '';
        approvalRemarksInput.value = button.dataset.approvalRemarks || '';
        renderCustomFormData(JSON.parse(button.dataset.customForm || '[]'));
        decisionInput.value = '';
        modal.classList.add('is-open');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        form.reset();
        decisionInput.value = '';
        renderCustomFormData([]);
    }

    document.querySelectorAll('[data-pa-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button);
        });
    });

    document.querySelectorAll('[data-pa-close]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    form.querySelectorAll('[data-decision]').forEach(function (button) {
        button.addEventListener('click', function () {
            decisionInput.value = button.dataset.decision || '';
        });
    });
});
</script>
@endpush
