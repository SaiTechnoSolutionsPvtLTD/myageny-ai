@extends('layouts.app')

@section('title', 'Quotations')

@push('styles')
<style>
.page-wrapper { padding: 32px; }
.page-header {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 24px;
}
.page-title { font-size: 22px; font-weight: 700; color: #121212; }
.btn-primary {
    display: inline-flex; align-items: center; gap: 6px;
    background-color: #fe5f04; color: #fff;
    padding: 8px 18px; border-radius: 20px;
    font-size: 14px; font-weight: 600; border: none; cursor: pointer;
    text-decoration: none;
}
.btn-primary:hover { background-color: #e05400; }
.btn-sm {
    padding: 4px 12px; font-size: 12px; border-radius: 12px;
}
.card {
    background: #fff; border: 1px solid #e1dee3;
    border-radius: 14px; overflow: hidden;
}
.filter-card {
    background: #fff; border: 1px solid #e1dee3; border-radius: 14px;
    padding: 18px; margin-bottom: 20px;
}
.filter-form {
    display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)) auto auto;
    gap: 12px; align-items: end;
}
.filter-group { display: flex; flex-direction: column; gap: 6px; }
.filter-label { font-size: 12px; font-weight: 600; color: #666; }
.filter-input, .filter-select {
    width: 100%; padding: 10px 12px; border: 1px solid #e1dee3; border-radius: 10px;
    font-size: 13px; color: #121212; background: #fff; outline: none;
}
.filter-input:focus, .filter-select:focus {
    border-color: #fe5f04; box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.10);
}
.btn-filter-reset {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 600;
    border: 1px solid #e1dee3; color: #444; text-decoration: none; background: #fff;
}
.btn-filter-reset:hover { border-color: #cfc9d2; background: #fafafa; }
.table-responsive { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead tr { background: #f8f8f8; }
th {
    padding: 12px 16px; font-size: 12px; font-weight: 600;
    color: #7c7c7c; text-align: left; border-bottom: 1px solid #e1dee3;
}
td {
    padding: 13px 16px; font-size: 13px; color: #121212;
    border-bottom: 1px solid #f1f1f1; vertical-align: middle;
}
tbody tr:hover { background: #fdf8f5; }
tbody tr:last-child td { border-bottom: none; }
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.badge-approved  { background: #edfaf3; color: #1a7a52; border: 1px solid #b6ead0; }
.badge-pending   { background: #fff8ec; color: #9a6200; border: 1px solid #ffd98a; }
.badge-agree     { background: #edfaf3; color: #1a7a52; border: 1px solid #b6ead0; }
.badge-disagree  { background: #fef2f2; color: #b42318; border: 1px solid #fecaca; }

/* ── Dropdown Styles for Quotations Table ── */
.crm-table-dropdown {
    position: relative;
    display: inline-block;
}
.crm-table-dropdown[open] {
    z-index: 1050 !important;
}
.crm-table-dropdown summary {
    list-style: none;
    outline: none;
    cursor: pointer;
}
.crm-table-dropdown summary::-webkit-details-marker {
    display: none;
}
.crm-table-dropdown-trigger {
    min-width: 82px;
    height: 32px;
    padding: 0 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    border-radius: 10px;
    border: 1px solid #e1dee3;
    background: #fff;
    color: #121212;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: all .15s ease;
    user-select: none;
}
.crm-table-dropdown[open] .crm-table-dropdown-trigger,
.crm-table-dropdown-trigger:hover {
    background: #fff7ed;
    color: #fe5f04;
    border-color: #fdba74;
}
.crm-table-dropdown-menu {
    position: absolute;
    right: 0;
    top: calc(100% + 6px);
    min-width: 170px;
    padding: 6px;
    border-radius: 12px;
    border: 1px solid #ece7ec;
    background: #ffffff;
    box-shadow: 0 16px 40px rgba(18, 18, 18, 0.18), 0 4px 12px rgba(0, 0, 0, 0.08);
    z-index: 99999 !important;
    display: flex;
    flex-direction: column;
    gap: 3px;
    text-align: left;
}
.crm-table-dropdown.dropup .crm-table-dropdown-menu {
    top: auto !important;
    bottom: calc(100% + 6px) !important;
    box-shadow: 0 -16px 40px rgba(18, 18, 18, 0.18), 0 -4px 12px rgba(0, 0, 0, 0.08) !important;
}
.crm-table-dropdown-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 8px 12px;
    border: 1px solid transparent;
    border-radius: 8px;
    background: #fff;
    color: #121212;
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-align: left;
    transition: all .15s ease;
    box-sizing: border-box;
}
.crm-table-dropdown-item i {
    font-size: 14px;
    line-height: 1;
}
.crm-table-dropdown-item:hover {
    background: #fff7ed;
    color: #fe5f04;
    border-color: #fed7aa;
}
.crm-table-dropdown-item.danger {
    color: #dc2626;
}
.crm-table-dropdown-item.danger:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fecaca;
}
.crm-table-dropdown-item:disabled,
.crm-table-dropdown-item.disabled {
    opacity: .5;
    cursor: not-allowed;
    background: transparent !important;
    color: #9e9e9e !important;
    border-color: transparent !important;
    pointer-events: none;
}
.crm-table-dropdown-menu form {
    margin: 0;
    width: 100%;
}
.action-btns { display: flex; gap: 6px; }
.btn-outline-sm {
    padding: 4px 12px; border-radius: 10px; font-size: 12px;
    font-weight: 500; border: 1px solid #e1dee3;
    background: #fff; cursor: pointer; text-decoration: none; color: #444;
}
.btn-outline-sm:hover { border-color: #fe5f04; color: #fe5f04; }
.btn-danger-sm {
    padding: 4px 12px; border-radius: 10px; font-size: 12px;
    font-weight: 500; border: 1px solid #ffc4c4;
    background: #fff5f5; cursor: pointer; color: #c00;
    text-decoration: none;
}
.btn-send-sm {
    padding: 4px 12px; border-radius: 10px; font-size: 12px;
    font-weight: 500; border: 1px solid #b6ead0;
    background: #edfaf3; cursor: pointer; color: #1a7a52;
    text-decoration: none;
}
.btn-send-sm:hover { border-color: #1a7a52; color: #11583a; }
.btn-send-sm:disabled {
    opacity: .55; cursor: not-allowed; border-color: #e1dee3;
    background: #f8f8f8; color: #9e9e9e;
}
.alert-success {
    background: #edfaf3; border: 1px solid #b6ead0; color: #1a7a52;
    padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;
}
.alert-error {
    background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;
    padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px;
}
.empty-state { text-align: center; padding: 60px 20px; color: #9e9e9e; }
.empty-state i { font-size: 42px; margin-bottom: 12px; display: block; }
.qt-modal-backdrop {
    position: fixed; inset: 0; z-index: 1400; display: none;
    align-items: center; justify-content: center; padding: 20px;
    background: rgba(18, 18, 18, .42);
}
.qt-modal-backdrop.is-open { display: flex; }
.qt-modal {
    width: min(420px, 100%); background: #fff; border-radius: 14px;
    border: 1px solid #e1dee3; box-shadow: 0 24px 70px rgba(18, 18, 18, .22);
    padding: 22px;
}
.qt-modal-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px; margin-bottom: 14px;
}
.qt-modal-title { font-size: 18px; font-weight: 700; color: #121212; }
.qt-modal-close {
    width: 34px; height: 34px; border-radius: 10px; border: 1px solid #e1dee3;
    color: #444; background: #fff; display: inline-flex; align-items: center; justify-content: center;
}
.qt-modal-close:hover { border-color: #fe5f04; color: #fe5f04; }
.qt-modal-copy { font-size: 14px; color: #555; line-height: 1.6; margin-bottom: 16px; }
.qt-modal-note {
    padding: 10px 12px; border-radius: 10px; background: #fff8ec;
    border: 1px solid #ffd98a; color: #7a4c00; font-size: 13px; margin-bottom: 18px;
}
.qt-modal-actions { display: flex; justify-content: flex-end; gap: 10px; }
.qt-modal-actions .btn-primary,
.qt-modal-actions .btn-filter-reset { border-radius: 10px; }
.qt-modal-actions .btn-primary:disabled { opacity: .7; cursor: wait; }
.qt-nav-tabs {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e1dee3;
    padding-bottom: 0;
}
.qt-nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 10px 10px 0 0;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
}
.qt-nav-tab:hover {
    color: #fe5f04;
    background: #fff8f5;
}
.qt-nav-tab.active {
    color: #fe5f04;
    background: #fff;
    border-bottom-color: #fe5f04;
}
.qt-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: #64748b;
}
.qt-nav-tab.active .qt-tab-badge {
    background: #fed7aa;
    color: #9a3412;
}
.qt-tab-badge-amber {
    background: #fef3c7 !important;
    color: #b45309 !important;
    border: 1px solid #fde68a;
}
@media (max-width: 1200px) {
    .filter-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: flex-start; gap: 12px; }
    .filter-form { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="page-wrapper">

    {{-- Header --}}
    <div class="page-header">
        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
            <div class="page-title">Quotations</div>
            @if(($currentTab ?? 'all') === 'pending')
                <span class="badge" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a; font-size:12px; padding:4px 12px;">
                    <i class="bi bi-clock-fill"></i> Pending Approval Queue ({{ $quotations->total() }})
                </span>
            @endif
        </div>
        <a href="{{ route('quotations.create') }}" class="btn-primary">
            <i class="bi bi-plus-lg"></i> Create Quotation
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert-error">{{ session('error') }}</div>
    @endif

    {{-- Tabs (Visible only if user can approve quotations: Admin & HR) --}}
    @if(!empty($canApprove))
        <div class="qt-nav-tabs">
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'all', 'page' => 1]) }}"
               class="qt-nav-tab {{ ($currentTab ?? 'all') !== 'pending' ? 'active' : '' }}">
                <i class="bi bi-collection"></i>
                <span>All Quotations</span>
                <span class="qt-tab-badge">{{ $allCount ?? 0 }}</span>
            </a>
            <a href="{{ request()->fullUrlWithQuery(['tab' => 'pending', 'page' => 1]) }}"
               class="qt-nav-tab {{ ($currentTab ?? 'all') === 'pending' ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i>
                <span>Pending Approval</span>
                @if(($pendingCount ?? 0) > 0)
                    <span class="qt-tab-badge qt-tab-badge-amber">{{ $pendingCount }}</span>
                @else
                    <span class="qt-tab-badge">0</span>
                @endif
            </a>
        </div>
    @endif

    <div class="filter-card">
        <form method="GET" action="{{ url()->current() }}" class="filter-form">
            <input type="hidden" name="tab" value="{{ $currentTab ?? 'all' }}">
            @if(request('lead_id'))
                <input type="hidden" name="lead_id" value="{{ request('lead_id') }}">
            @endif

            <div class="filter-group">
                <label class="filter-label" for="quotation_no">Quotation No</label>
                <input
                    type="text"
                    id="quotation_no"
                    name="quotation_no"
                    class="filter-input"
                    value="{{ request('quotation_no') }}"
                    placeholder="Search quotation no">
            </div>

            <div class="filter-group">
                <label class="filter-label" for="start_date">Start Date</label>
                <input
                    type="date"
                    id="start_date"
                    name="start_date"
                    class="filter-input"
                    value="{{ request('start_date') }}">
            </div>

            <div class="filter-group">
                <label class="filter-label" for="end_date">End Date</label>
                <input
                    type="date"
                    id="end_date"
                    name="end_date"
                    class="filter-input"
                    value="{{ request('end_date') }}">
            </div>

            @if(($currentTab ?? 'all') !== 'pending')
            <div class="filter-group">
                <label class="filter-label" for="status">Approval Status</label>
                <select id="status" name="status" class="filter-select">
                    <option value="">All Approval Status</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending Approval</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
            </div>
            @endif

            <div class="filter-group">
                <label class="filter-label" for="customer_response">Customer Status</label>
                <select id="customer_response" name="customer_response" class="filter-select">
                    <option value="">All Customer Status</option>
                    <option value="agree" @selected(request('customer_response') === 'agree')>Approved</option>
                    <option value="disagree" @selected(request('customer_response') === 'disagree')>Rejected</option>
                    <option value="pending" @selected(request('customer_response') === 'pending')>Pending</option>
                </select>
            </div>

            <div class="filter-group">
                <label class="filter-label" for="approved_by">Approved By</label>
                <select id="approved_by" name="approved_by" class="filter-select">
                    <option value="">All Users</option>
                    @foreach($approvers as $approver)
                        <option value="{{ $approver->id }}" @selected((string) request('approved_by') === (string) $approver->id)>
                            {{ $approver->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary">Apply Filter</button>
            <a href="{{ url()->current() . (($currentTab ?? 'all') === 'pending' ? '?tab=pending' : '') . (request()->filled('lead_id') ? ((($currentTab ?? 'all') === 'pending' ? '&' : '?') . 'lead_id=' . request('lead_id')) : '') }}" class="btn-filter-reset">Reset</a>
        </form>
    </div>

    {{-- Table --}}
    <div class="card crm-table-card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Quotation No</th>
                        <th>Lead</th>
                        <th>Date</th>
                        <th>Valid Until</th>
                        <th>Total Amount</th>
                        <th>Approval Status</th>
                        <th>Customer Status</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $q)
                    @php
                        $leadEmail = $q->lead?->email;
                        $response = $q->customer_response ?: 'pending';
                        $responseBadge = match ($response) {
                            'agree', 'approved', 'approve' => 'badge-agree',
                            'disagree', 'rejected', 'reject' => 'badge-disagree',
                            default => 'badge-pending',
                        };
                        $responseIcon = match ($response) {
                            'agree', 'approved', 'approve' => 'bi-check-circle-fill',
                            'disagree', 'rejected', 'reject' => 'bi-x-circle-fill',
                            default => 'bi-clock-fill',
                        };
                        $canApprove = (bool) auth()->user()?->canApproveQuotation();
                        $isApproved = (bool) $q->is_approved;
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration + ($quotations->firstItem() - 1) }}</td>
                        <td><strong>{{ $q->quotation_no }}</strong></td>
                        <td>{{ $q->lead?->contact_name ?? '—' }}<br>
                            <small style="color:#9e9e9e">{{ $q->lead->company_name ?? '' }}</small>
                        </td>
                        <td>{{ $q->quotation_date ? $q->quotation_date->format('d M Y') : '—' }}</td>
                        <td>{{ $q->valid_until ? $q->valid_until->format('d M Y') : ($q->quotation_date ? $q->quotation_date->copy()->addDays(7)->format('d M Y') : '—') }}</td>
                        <td><strong>₹{{ number_format($q->total_amount, 2) }}</strong></td>
                        <td>
                            @if($isApproved)
                                <span class="badge" style="background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">
                                    <i class="bi bi-check-circle-fill"></i> Approved
                                </span>
                                @if($q->approver)
                                    <br><small style="color:#64748b; font-size:11px;">by {{ $q->approver->name }}</small>
                                @endif
                            @elseif($q->rejected_at)
                                <span class="badge" style="background:#fef2f2; color:#b91c1c; border:1px solid #fecaca;" title="Remarks: {{ $q->rejection_remarks }}">
                                    <i class="bi bi-x-circle-fill"></i> Rejected
                                </span>
                                @if($q->rejecter)
                                    <br><small style="color:#64748b; font-size:11px;">by {{ $q->rejecter->name }}</small>
                                @endif
                                @if($q->rejection_remarks)
                                    <br><small style="color:#b91c1c; font-size:10px; font-weight:600; max-width:130px; display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $q->rejection_remarks }}">
                                        {{ $q->rejection_remarks }}
                                    </small>
                                @endif
                            @else
                                <span class="badge" style="background:#fffbeb; color:#b45309; border:1px solid #fde68a;">
                                    <i class="bi bi-clock-fill"></i> Pending Approval
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $responseBadge }}">
                                <i class="bi {{ $responseIcon }}"></i> {{ $q->customer_response_label }}
                            </span>
                            @if($q->customer_responded_at)
                                <br>
                                <small style="color:#9e9e9e">{{ $q->customer_responded_at->format('d M Y, h:i A') }}</small>
                            @endif
                        </td>
                        <td style="text-align:right">
                            <details class="crm-table-dropdown table-dropdown">
                                <summary class="crm-table-dropdown-trigger" title="Actions">
                                    <span>Actions</span>
                                    <i class="bi bi-chevron-down" style="font-size:10px;"></i>
                                </summary>
                                <div class="crm-table-dropdown-menu">
                                    <a href="{{ request()->routeIs('accounts.*') ? route('accounts.quotations.show', $q) : route('quotations.show', $q) }}" class="crm-table-dropdown-item" title="View Quotation">
                                        <i class="bi bi-eye"></i>
                                        <span>View</span>
                                    </a>

                                    @if(!$isApproved && $canApprove)
                                        <form method="POST" action="{{ route('quotations.approve', $q) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="crm-table-dropdown-item" style="color:#15803d;" title="Approve Quotation" onclick="return confirm('Approve Quotation #{{ $q->quotation_no }}?')">
                                                <i class="bi bi-check-circle-fill"></i>
                                                <span>Approve</span>
                                            </button>
                                        </form>
                                        @if(!$q->rejected_at)
                                            <button type="button" class="crm-table-dropdown-item danger js-reject-quotation"
                                                    title="Reject Quotation with remarks"
                                                    data-action="{{ route('quotations.reject', $q) }}"
                                                    data-quotation="{{ $q->quotation_no }}">
                                                <i class="bi bi-x-circle-fill"></i>
                                                <span>Reject</span>
                                            </button>
                                        @endif
                                    @endif

                                    <a href="{{ route('quotations.edit', $q) }}" class="crm-table-dropdown-item" title="Edit Quotation">
                                        <i class="bi bi-pencil"></i>
                                        <span>Edit</span>
                                    </a>

                                    @if(!$isApproved)
                                        <span class="crm-table-dropdown-item disabled" title="Quotation requires Admin or HR approval before sending">
                                            <i class="bi bi-send"></i>
                                            <span>Send Quotation</span>
                                        </span>
                                    @elseif(empty($leadEmail))
                                        <span class="crm-table-dropdown-item disabled" title="Lead email not available">
                                            <i class="bi bi-send"></i>
                                            <span>Send Quotation</span>
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            class="crm-table-dropdown-item js-send-quotation"
                                            style="color:#1a7a52;"
                                            data-action="{{ route('quotations.send-email', $q) }}"
                                            data-quotation="{{ $q->quotation_no }}"
                                            data-email="{{ $leadEmail }}"
                                            data-lead="{{ $q->lead?->contact_name ?? $q->lead?->company_name ?? 'Lead' }}"
                                            title="Send Quotation via Email">
                                            <i class="bi bi-send"></i>
                                            <span>Send Quotation</span>
                                        </button>
                                    @endif

                                    <form method="POST" action="{{ route('quotations.destroy', $q) }}"
                                          onsubmit="return confirm('Delete this quotation?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="crm-table-dropdown-item danger" title="Delete Quotation">
                                            <i class="bi bi-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </details>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                @if(($currentTab ?? 'all') === 'pending')
                                    <i class="bi bi-check-circle-fill" style="color:#10b981; font-size:42px;"></i>
                                    <p style="font-size:15px; font-weight:700; color:#1e293b; margin-top:8px;">No Quotations Pending Approval</p>
                                    <p style="font-size:13px; color:#64748b;">All quotations have been approved or none are awaiting review.</p>
                                @else
                                    <i class="bi bi-file-earmark-text"></i>
                                    <p style="margin-top:8px;">No quotations found.</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{-- Pagination --}}
        @if($quotations->hasPages())
        <div style="padding: 0;">
            @include('partials.table-pagination', ['paginator' => $quotations])
        </div>
        @endif
    </div>

    <div class="qt-modal-backdrop" id="sendQuotationModal" aria-hidden="true">
        <div class="qt-modal" role="dialog" aria-modal="true" aria-labelledby="sendQuotationTitle">
            <div class="qt-modal-header">
                <div>
                    <div class="qt-modal-title" id="sendQuotationTitle">Send Quotation</div>
                </div>
                <button type="button" class="qt-modal-close" id="sendQuotationClose" aria-label="Close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="qt-modal-copy">
                Quotation <strong id="sendQuotationNo"></strong> will be sent to
                <strong id="sendQuotationEmail"></strong>.
            </div>
            <div class="qt-modal-note">
                The quotation PDF will be attached in the email.
            </div>

            <form method="POST" id="sendQuotationForm">
                @csrf
                <div class="qt-modal-actions">
                    <button type="button" class="btn-filter-reset" id="sendQuotationCancel">Cancel</button>
                    <button type="submit" class="btn-primary" id="sendQuotationSubmit">
                        <i class="bi bi-send"></i> Send Mail
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reject Quotation Modal --}}
    <div class="qt-modal-backdrop" id="rejectQuotationModal" aria-hidden="true">
        <div class="qt-modal" role="dialog" aria-modal="true" style="width: min(480px, 100%);">
            <form method="POST" id="rejectQuotationForm" action="">
                @csrf @method('PATCH')
                <div class="qt-modal-header">
                    <div class="qt-modal-title" style="display:flex; align-items:center; gap:8px; color:#dc2626;">
                        <i class="bi bi-x-circle-fill"></i>
                        <span>Reject Quotation</span>
                    </div>
                    <button type="button" class="qt-modal-close" id="rejectQuotationClose" aria-label="Close">&times;</button>
                </div>
                <div class="qt-modal-copy">
                    Please enter the reason / remarks for rejecting quotation <strong id="rejectQuotationNoDisplay"></strong>. These remarks will be emailed directly to the sales person.
                </div>
                <div style="margin-bottom: 18px;">
                    <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin-bottom:6px;">
                        Rejection Remarks <span style="color:#dc2626;">*</span>
                    </label>
                    <textarea
                        name="rejection_remarks"
                        id="rejectionRemarksInput"
                        rows="3"
                        required
                        minlength="3"
                        maxlength="1000"
                        placeholder="Enter reason for rejection (e.g. incorrect pricing, missing discount, scope change)..."
                        style="width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:13px; font-family:inherit; resize:vertical; box-sizing:border-box; outline:none;"
                    ></textarea>
                </div>
                <div class="qt-modal-actions">
                    <button type="button" class="btn-filter-reset" id="rejectQuotationCancel">Cancel</button>
                    <button type="submit" class="btn-danger-sm" id="rejectQuotationSubmit" style="padding:10px 18px; font-size:13px; font-weight:700; border-radius:10px; background:#dc2626; color:#ffffff; border:none; cursor:pointer;">
                        <i class="bi bi-x-circle"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('sendQuotationModal');
    const form = document.getElementById('sendQuotationForm');
    const closeButton = document.getElementById('sendQuotationClose');
    const cancelButton = document.getElementById('sendQuotationCancel');
    const submitButton = document.getElementById('sendQuotationSubmit');
    const quotationNo = document.getElementById('sendQuotationNo');
    const quotationEmail = document.getElementById('sendQuotationEmail');
    const submitText = submitButton ? submitButton.innerHTML : '';

    if (!modal || !form || !submitButton || !quotationNo || !quotationEmail) {
        return;
    }

    function openModal(button) {
        if (button.disabled) {
            return;
        }

        form.action = button.dataset.action || '';
        quotationNo.textContent = button.dataset.quotation || '';
        quotationEmail.textContent = button.dataset.email || '';
        submitButton.disabled = false;
        submitButton.innerHTML = submitText;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        submitButton.focus();
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        form.removeAttribute('action');
        submitButton.disabled = false;
        submitButton.innerHTML = submitText;
    }

    document.querySelectorAll('.js-send-quotation').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button);
        });
    });

    closeButton?.addEventListener('click', closeModal);
    cancelButton?.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    form.addEventListener('submit', function () {
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Sending...';
    });
})();

// Rejection Modal JS
(function () {
    const rejectModal = document.getElementById('rejectQuotationModal');
    const rejectForm = document.getElementById('rejectQuotationForm');
    const rejectClose = document.getElementById('rejectQuotationClose');
    const rejectCancel = document.getElementById('rejectQuotationCancel');
    const rejectQuotationNoDisplay = document.getElementById('rejectQuotationNoDisplay');
    const rejectionRemarksInput = document.getElementById('rejectionRemarksInput');

    if (!rejectModal || !rejectForm) return;

    function openRejectModal(actionUrl, qNo) {
        rejectForm.action = actionUrl;
        if (rejectQuotationNoDisplay) rejectQuotationNoDisplay.textContent = qNo ? ('#' + qNo) : '';
        if (rejectionRemarksInput) rejectionRemarksInput.value = '';
        rejectModal.classList.add('is-open');
        setTimeout(() => rejectionRemarksInput && rejectionRemarksInput.focus(), 60);
    }

    function closeRejectModal() {
        rejectModal.classList.remove('is-open');
    }

    document.querySelectorAll('.js-reject-quotation').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openRejectModal(btn.dataset.action, btn.dataset.quotation);
        });
    });

    rejectClose?.addEventListener('click', closeRejectModal);
    rejectCancel?.addEventListener('click', closeRejectModal);

    rejectModal.addEventListener('click', function (e) {
        if (e.target === rejectModal) closeRejectModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && rejectModal.classList.contains('is-open')) closeRejectModal();
    });
})();
</script>
@endpush
