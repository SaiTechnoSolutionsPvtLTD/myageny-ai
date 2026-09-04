@extends('layouts.app')

@section('title', 'Expense Requests - myAgenci.ai HRMS')

@push('styles')
<style>
.exp-req-page {
    min-height: 100%;
    padding: 28px;
    background:
        radial-gradient(circle at top left, rgba(254, 95, 4, 0.08), transparent 35%),
        linear-gradient(180deg, #f8f6f2 0%, #f3f5f8 100%);
    font-family: 'Inter', sans-serif;
}
.exp-req-hero {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 20px;
    margin-bottom: 24px;
    padding: 28px;
    border: 1px solid #e6e8ee;
    border-radius: 22px;
    background: linear-gradient(135deg, #fff9f3 0%, #ffffff 55%, #f7f9fc 100%);
    box-shadow: 0 14px 40px rgba(15, 23, 42, 0.05);
}
.exp-req-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 12px;
    border-radius: 999px;
    background: #fff1e8;
    color: #c2410c;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .7px;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.exp-req-title {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.1;
    color: #111827;
}
.exp-req-subtitle {
    margin: 10px 0 0;
    max-width: 640px;
    font-size: 14px;
    line-height: 1.6;
    color: #6b7280;
}
.exp-req-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
    border: none;
    transition: all .2s ease;
    text-decoration: none;
    font-family: inherit;
}
.exp-req-btn-primary {
    background: linear-gradient(135deg, #fe5f04, #ff7c30);
    color: #fff;
    box-shadow: 0 6px 20px rgba(254, 95, 4, 0.28);
}
.exp-req-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(254, 95, 4, 0.38);
    color: #fff;
}
.exp-req-btn-outline {
    background: #fff;
    color: #374151;
    border: 1px solid #e5e7eb;
}
.exp-req-btn-outline:hover {
    border-color: #fe5f04;
    color: #fe5f04;
}

/* Stats */
.exp-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}
.exp-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 18px 20px;
    border: 2px solid #e5e7eb;
    box-shadow: 0 4px 14px rgba(0,0,0,0.02);
    display: flex;
    align-items: center;
    gap: 14px;
    text-decoration: none !important;
    cursor: pointer;
    transition: all 0.2s ease;
}
.exp-stat-card:hover {
    transform: translateY(-2px);
    border-color: #fe5f04;
    box-shadow: 0 8px 20px rgba(254, 95, 4, 0.12);
}
.exp-stat-card.is-active {
    border-color: #fe5f04;
    background: #fffdfb;
    box-shadow: 0 8px 20px rgba(254, 95, 4, 0.15);
}
.exp-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.exp-stat-label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; }
.exp-stat-val { font-size: 22px; font-weight: 800; color: #111827; margin-top: 2px; }

/* Table Card */
.exp-req-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 18px;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.03);
    overflow: hidden;
}
.exp-req-filter-bar {
    padding: 18px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    background: #fafafa;
}
.exp-search-input {
    padding: 8px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 13px;
    outline: none;
    background: #fff;
}
.exp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.exp-table th {
    background: #f9fafb;
    padding: 14px 20px;
    text-align: left;
    font-weight: 800;
    color: #4b5563;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: .6px;
    border-bottom: 1px solid #e5e7eb;
}
.exp-table td {
    padding: 14px 20px;
    border-bottom: 1px solid #f3f4f6;
    color: #1f2937;
    vertical-align: middle;
}
.exp-table tr:hover { background: #fffdfb; }

/* Badges */
.exp-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .4px;
}
.eb-pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.eb-approved { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.eb-rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Modal */
.exp-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    z-index: 999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}
.exp-modal {
    background: #ffffff;
    border-radius: 20px;
    width: 90%;
    max-width: 540px;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
    animation: popIn .2s ease;
    overflow: hidden;
}
@keyframes popIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.exp-modal-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #f3f4f6;
    background: #fafafa;
}
.exp-modal-title { font-size: 18px; font-weight: 800; color: #111827; }
.exp-modal-close {
    background: none; border: none; font-size: 22px; color: #9ca3af; cursor: pointer;
}
.exp-modal-body {
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.exp-form-group { display: flex; flex-direction: column; gap: 6px; }
.exp-form-label { font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; color: #4b5563; }
.exp-input, .exp-select, .exp-textarea {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    outline: none;
    font-family: inherit;
    background: #fff;
}
.exp-input:focus, .exp-select:focus, .exp-textarea:focus {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.exp-modal-foot {
    padding: 16px 24px;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Select2 Custom Styling for Modal */
.exp-modal-body .select2-container--default .select2-selection--single {
    height: 42px;
    border: 1px solid #d1d5db;
    border-radius: 10px;
    background: #fff;
    display: flex;
    align-items: center;
}
.exp-modal-body .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px;
    padding-left: 14px;
    padding-right: 34px;
    font-size: 14px;
    color: #111827;
}
.exp-modal-body .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
    right: 10px;
}
.exp-modal-body .select2-container--default.select2-container--focus .select2-selection--single,
.exp-modal-body .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #fe5f04;
    box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.12);
}
.select2-dropdown {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
    z-index: 99999 !important;
}
.select2-search--dropdown {
    padding: 8px 10px;
}
.select2-search--dropdown .select2-search__field {
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 13px;
    outline: none;
}
.select2-search--dropdown .select2-search__field:focus {
    border-color: #fe5f04;
}
.select2-results__option {
    font-size: 13px;
    padding: 8px 12px;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background: #fe5f04;
    color: #fff;
}
.select2-container--open {
    z-index: 99999 !important;
}

/* Detail Modal & Button Styling */
.exp-detail-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    color: #ea580c;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
    margin-top: 4px;
}
.exp-detail-btn:hover {
    background: #ffedd5;
    border-color: #fdba74;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(234, 88, 12, 0.15);
}
.exp-att-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ea580c;
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    border-radius: 10px;
    padding: 1px 5px;
    min-width: 15px;
    height: 15px;
}

/* Confirmation Modals */
.exp-confirm-modal {
    position: fixed;
    inset: 0;
    background: rgba(18, 24, 38, 0.5);
    backdrop-filter: blur(4px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 20000;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.exp-confirm-modal.show {
    display: flex;
    opacity: 1;
}
.exp-confirm-card {
    background: #ffffff;
    border-radius: 20px;
    width: 450px;
    max-width: calc(100vw - 32px);
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    transform: translateY(15px);
    transition: transform 0.25s ease;
}
.exp-confirm-modal.show .exp-confirm-card {
    transform: translateY(0);
}
.exp-confirm-head {
    padding: 18px 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fafafa;
}
.exp-confirm-title {
    font-size: 16px;
    font-weight: 800;
    color: #111827;
    display: flex;
    align-items: center;
    gap: 8px;
}
.exp-confirm-body {
    padding: 24px;
    text-align: center;
}
.exp-confirm-foot {
    padding: 16px 24px;
    border-top: 1px solid #f3f4f6;
    background: #fafafa;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}

/* Process / Progress Bar Overlay (matching Support Portal) */
.exp-process-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.75);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 999999;
    animation: expFadeInOverlay 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes expFadeInOverlay {
    from { opacity: 0; }
    to { opacity: 1; }
}
.exp-process-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 40px 36px;
    width: 460px;
    max-width: calc(100vw - 32px);
    box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
    text-align: center;
    transform: scale(0.95);
    animation: expScaleInCard 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes expScaleInCard {
    to { transform: scale(1); }
}
.exp-process-icon-wrap {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.exp-process-spinner {
    position: absolute;
    inset: 0;
    border: 3.5px solid #ffe6d5;
    border-top-color: #fe5f04;
    border-radius: 50%;
    animation: expSpinOverlay 0.9s linear infinite;
}
@keyframes expSpinOverlay {
    to { transform: rotate(360deg); }
}
.exp-process-icon {
    font-size: 32px;
    color: #fe5f04;
    animation: expPulseIcon 1.5s ease-in-out infinite alternate;
}
@keyframes expPulseIcon {
    from { transform: scale(0.88); opacity: 0.85; }
    to { transform: scale(1.12); opacity: 1; }
}
.exp-process-title {
    font-size: 19px;
    font-weight: 800;
    color: #111827;
    margin: 0 0 6px;
}
.exp-process-subtitle {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 24px;
    line-height: 1.5;
}
.exp-progress-wrapper { width: 100%; }
.exp-progress-bar {
    width: 100%;
    height: 10px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    position: relative;
}
.exp-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #fe5f04 0%, #ff8c3a 100%);
    border-radius: 999px;
    transition: width 0.25s ease;
}
.exp-progress-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    font-size: 12px;
    font-weight: 700;
    color: #4b5563;
}
</style>
@endpush

@section('content')
<div class="exp-req-page">

    {{-- Hero --}}
    <div class="exp-req-hero">
        <div>
            <div class="exp-req-kicker">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Expense Reimbursements
            </div>
            <h2 class="exp-req-title">Expense Requests</h2>
            <p class="exp-req-subtitle">Submit expense requests to trigger multi-stage hierarchy email approvals (e.g. Sales Executive ➔ Sales TL ➔ Sales Manager ➔ COO).</p>
        </div>
        <div>
            <button class="exp-req-btn exp-req-btn-primary" onclick="openSendModal()">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Send Expense Request
            </button>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
    <div style="margin-bottom:20px; padding:14px 18px; border-radius:12px; background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="margin-bottom:20px; padding:14px 18px; border-radius:12px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; font-weight:700; font-size:14px; display:flex; align-items:center; gap:10px;">
        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Stats Cards --}}
    @php
        $currentStatus = request('status');
    @endphp
    <div class="exp-stats-row">
        <a href="{{ route('hrms.expense-requests.index', array_filter(request()->except('status', 'page'))) }}"
           class="exp-stat-card {{ empty($currentStatus) ? 'is-active' : '' }}">
            <div class="exp-stat-icon" style="background:#fff1e8; color:#fe5f04;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div>
                <div class="exp-stat-label">Total Requests</div>
                <div class="exp-stat-val">{{ $totalCount }}</div>
            </div>
        </a>

        <a href="{{ route('hrms.expense-requests.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
           class="exp-stat-card {{ $currentStatus === 'pending' ? 'is-active' : '' }}">
            <div class="exp-stat-icon" style="background:#fffbeb; color:#b45309;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <div class="exp-stat-label">Pending Approval</div>
                <div class="exp-stat-val" style="color:#b45309;">{{ $pendingCount }}</div>
            </div>
        </a>

        <a href="{{ route('hrms.expense-requests.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}"
           class="exp-stat-card {{ $currentStatus === 'approved' ? 'is-active' : '' }}">
            <div class="exp-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
                <div class="exp-stat-label">Approved</div>
                <div class="exp-stat-val" style="color:#16a34a;">{{ $approvedCount }}</div>
            </div>
        </a>

        <a href="{{ route('hrms.expense-requests.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}"
           class="exp-stat-card {{ $currentStatus === 'rejected' ? 'is-active' : '' }}">
            <div class="exp-stat-icon" style="background:#fef2f2; color:#dc2626;">
                <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <div>
                <div class="exp-stat-label">Rejected</div>
                <div class="exp-stat-val" style="color:#dc2626;">{{ $rejectedCount }}</div>
            </div>
        </a>
    </div>

    {{-- Main Table Card --}}
    <div class="exp-req-card">
        <form method="GET" action="{{ route('hrms.expense-requests.index') }}">
            <div class="exp-req-filter-bar">
                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                    <input type="text" name="search" class="exp-search-input" style="width:220px;" placeholder="Search description, applicant…" value="{{ request('search') }}">
                    
                    <select name="expense_category_id" class="exp-search-input" style="width:170px;" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('expense_category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                        @endforeach
                    </select>

                    <select name="status" class="exp-search-input" style="width:130px;" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>

                    <button type="submit" class="exp-req-btn exp-req-btn-primary" style="padding:7px 16px; font-size:12px; height:34px; border-radius:9px;">Filter</button>

                    @if(request()->hasAny(['search', 'status', 'expense_category_id']))
                    <a href="{{ route('hrms.expense-requests.index') }}" style="font-size:12px; color:#6b7280; text-decoration:none; font-weight:600;">Reset</a>
                    @endif
                </div>

                <div style="font-size:12px; font-weight:700; color:#6b7280;">
                    Showing {{ $requests->total() }} Requests
                </div>
            </div>
        </form>

        <table class="exp-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Applicant</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Details & Receipts</th>
                    <th>Current Approver Stage</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $index => $req)
                @php
                    $applicantRole = $req->user?->roles->first()?->display_name ?? ucfirst(str_replace('_',' ', preg_replace('/^company_\d+__/', '', $req->user?->roles->first()?->name ?? 'User')));
                    $canAction = $req->canUserAction();
                    $stages = $req->approval_stages;
                @endphp
                <tr>
                    <td style="color:#9ca3af; font-weight:600;">
                        {{ $requests->firstItem() + $index }}
                    </td>
                    <td>
                        <strong style="color:#111827; font-size:14px; display:block;">{{ $req->user?->name ?? 'User #'.$req->user_id }}</strong>
                        <span style="font-size:11px; color:#6b7280;">{{ $applicantRole }} • {{ $req->user?->branch?->name ?? 'Main Branch' }}</span>
                    </td>
                    <td>
                        <span style="display:inline-block; padding:3px 10px; border-radius:6px; background:#fff7ed; color:#ea580c; font-weight:700; font-size:12px;">
                            {{ $req->category?->name ?? 'General' }}
                        </span>
                    </td>
                    <td>
                        <strong style="color:#111827; font-size:15px;">₹{{ number_format($req->amount, 2) }}</strong>
                    </td>
                    <td>
                        <button type="button"
                                class="exp-detail-btn"
                                data-details="{{ json_encode([
                                    'id' => $req->id,
                                    'applicant' => $req->user?->name ?? ('User #'.$req->user_id),
                                    'role' => $applicantRole,
                                    'branch' => $req->user?->branch?->name ?? 'Main Branch',
                                    'category' => $req->category?->name ?? 'General',
                                    'amount' => number_format($req->amount, 2),
                                    'description' => $req->description,
                                    'status' => $req->status,
                                    'attachments' => $req->attachment_urls,
                                    'created_at' => $req->created_at ? $req->created_at->format('d M Y, h:i A') : '-'
                                ]) }}"
                                onclick="openExpenseDetailModal(this)">
                            <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span>View Details & Receipts</span>
                            @if(!empty($req->attachment_urls))
                                <span class="exp-att-count">{{ count($req->attachment_urls) }}</span>
                            @endif
                        </button>
                    </td>
                    <td>
                        @if(!empty($stages))
                            <div style="display:flex; flex-direction:column; gap:5px; min-width:180px;">
                                @foreach($stages as $stg)
                                    <div style="display:flex; align-items:center; gap:6px; font-size:11px;">
                                        @if($stg['status'] === 'completed')
                                            <span style="display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%; background:#dcfce7; color:#15803d; font-weight:800; font-size:10px; flex-shrink:0;">✓</span>
                                            <div style="line-height:1.2;">
                                                <span style="color:#15803d; font-weight:700;">Stage {{ $stg['step'] }}: {{ $stg['role_name'] }}</span>
                                                @if($stg['actioned_by'])
                                                    <span style="color:#64748b; font-size:10px; display:block;">By {{ $stg['actioned_by'] }}</span>
                                                @endif
                                            </div>
                                        @elseif($stg['status'] === 'current')
                                            <span style="display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%; background:#fef3c7; color:#b45309; font-weight:800; font-size:10px; flex-shrink:0;">⏳</span>
                                            <div style="line-height:1.2;">
                                                <span style="color:#b45309; font-weight:800; background:#fef3c7; padding:2px 6px; border-radius:4px; display:inline-block;">
                                                    Stage {{ $stg['step'] }}: {{ $stg['role_name'] }}
                                                </span>
                                            </div>
                                        @elseif($stg['status'] === 'rejected')
                                            <span style="display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%; background:#fee2e2; color:#b91c1c; font-weight:800; font-size:10px; flex-shrink:0;">✕</span>
                                            <div style="line-height:1.2;">
                                                <span style="color:#b91c1c; font-weight:700;">Stage {{ $stg['step'] }}: {{ $stg['role_name'] }}</span>
                                                @if($stg['actioned_by'])
                                                    <span style="color:#b91c1c; font-size:10px; display:block;">Rejected by {{ $stg['actioned_by'] }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span style="display:inline-flex; align-items:center; justify-content:center; width:18px; height:18px; border-radius:50%; background:#f3f4f6; color:#9ca3af; font-weight:700; font-size:10px; flex-shrink:0;">{{ $stg['step'] }}</span>
                                            <span style="color:#9ca3af; font-weight:600;">Stage {{ $stg['step'] }}: {{ $stg['role_name'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            @if($req->status === 'pending')
                                <span style="font-size:12px; font-weight:700; color:#374151;">
                                    Stage {{ $req->current_step }}: {{ $req->currentApproverRole?->display_name ?? ucfirst(str_replace('_',' ', preg_replace('/^company_\d+__/', '', $req->currentApproverRole?->name ?? 'Approver'))) }}
                                </span>
                            @elseif($req->status === 'approved')
                                <span style="font-size:11px; color:#166534; font-weight:700;">
                                    Approved by {{ $req->approver?->name ?? 'Approver' }}
                                </span>
                            @else
                                <span style="font-size:11px; color:#991b1b; font-weight:700;">
                                    Rejected by {{ $req->approver?->name ?? 'Approver' }}
                                </span>
                            @endif
                        @endif

                        @if($req->status === 'rejected' && $req->rejection_reason)
                            <div style="margin-top:6px; padding:6px 10px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; color:#991b1b; font-size:11px; font-weight:600; line-height:1.4; max-width:220px;">
                                💬 <strong>Remarks:</strong> {{ $req->rejection_reason }}
                            </div>
                        @endif
                    </td>
                    <td>
                        @if($req->status === 'pending')
                            <span class="exp-badge eb-pending">⏳ Pending</span>
                        @elseif($req->status === 'approved')
                            <span class="exp-badge eb-approved">✓ Approved</span>
                        @else
                            <span class="exp-badge eb-rejected">✕ Rejected</span>
                        @endif
                    </td>
                    <td style="color:#9ca3af; font-size:12px;">
                        {{ $req->created_at ? $req->created_at->format('d M Y, h:i A') : '-' }}
                    </td>
                    <td style="text-align:right;">
                        @if($canAction)
                            <div style="display:inline-flex; gap:6px;">
                                <form id="approveForm_{{ $req->id }}" method="POST" action="{{ route('hrms.expense-requests.approve', $req) }}">
                                    @csrf
                                    <button type="button" class="exp-req-btn exp-req-btn-primary" style="padding:6px 12px; font-size:11px; background:#16a34a; box-shadow:none;" onclick="showConfirmApproveModal('approveForm_{{ $req->id }}')">
                                        Approve (Stage {{ $req->current_step }})
                                    </button>
                                </form>

                                <button type="button" class="exp-req-btn exp-req-btn-outline" style="padding:6px 10px; font-size:11px; color:#dc2626; border-color:#fecaca;"
                                    onclick="openRejectModal({{ $req->id }})">
                                    Reject
                                </button>
                            </div>
                        @elseif($req->status === 'pending')
                            <span style="display:inline-block; font-size:11px; color:#6b7280; background:#f3f4f6; padding:4px 8px; border-radius:6px; font-weight:600;">
                                Waiting for Stage {{ $req->current_step }}
                            </span>
                        @else
                            <span style="font-size:12px; color:#9ca3af;">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center; padding:50px 20px; color:#6b7280;">
                        <div style="font-size:36px; margin-bottom:8px;">💸</div>
                        <div style="font-weight:700; color:#374151; font-size:15px;">No Expense Requests Found</div>
                        <div style="font-size:13px; margin-top:4px;">Click "Send Expense Request" to submit an expense reimbursement.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($requests->hasPages())
        <div style="padding:16px 24px; border-top:1px solid #f3f4f6;">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

</div>

{{-- Send Expense Request Modal --}}
<div class="exp-modal-overlay" id="sendModal">
    <div class="exp-modal">
        <div class="exp-modal-head">
            <div class="exp-modal-title">Send Expense Request</div>
            <button class="exp-modal-close" onclick="closeSendModal()">✕</button>
        </div>

        <form id="createExpenseForm" method="POST" action="{{ route('hrms.expense-requests.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="exp-modal-body">
                
                <div class="exp-form-group">
                    <label class="exp-form-label">Expense Category <span style="color:#dc2626;">*</span></label>
                    <select name="expense_category_id" id="send_expense_category_id" class="exp-select select2" required style="width:100%;">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="exp-form-group">
                    <label class="exp-form-label">Amount (₹) <span style="color:#dc2626;">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="exp-input" required placeholder="0.00">
                </div>

                <div class="exp-form-group">
                    <label class="exp-form-label">Description & Reason <span style="color:#dc2626;">*</span></label>
                    <textarea name="description" rows="4" class="exp-textarea" required placeholder="Detail the expense purpose, location, date, and items purchased..."></textarea>
                </div>

                <div class="exp-form-group">
                    <label class="exp-form-label">Attach Receipts / Invoices (Optional)</label>
                    <input type="file" name="attachments[]" class="exp-input" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" multiple>
                    <span style="font-size:11px; color:#6b7280; margin-top:2px;">PDF, PNG, JPG up to 5MB each (Select multiple files if needed)</span>
                </div>

            </div>

            <div class="exp-modal-foot">
                <button type="button" class="exp-req-btn exp-req-btn-outline" onclick="closeSendModal()">Cancel</button>
                <button type="button" class="exp-req-btn exp-req-btn-primary" onclick="showConfirmSendModal()">Send Request</button>
            </div>
        </form>
    </div>
</div>

{{-- Reject Reason Modal --}}
<div class="exp-modal-overlay" id="rejectModal">
    <div class="exp-modal">
        <div class="exp-modal-head">
            <div class="exp-modal-title">Reject Expense Request</div>
            <button class="exp-modal-close" onclick="closeRejectModal()">✕</button>
        </div>

        <form id="rejectForm" method="POST" action="">
            @csrf
            <div class="exp-modal-body">
                <div class="exp-form-group">
                    <label class="exp-form-label">Rejection Reason</label>
                    <textarea name="rejection_reason" rows="3" class="exp-textarea" placeholder="Provide reason for rejecting this expense request..."></textarea>
                </div>
            </div>

            <div class="exp-modal-foot">
                <button type="button" class="exp-req-btn exp-req-btn-outline" onclick="closeRejectModal()">Cancel</button>
                <button type="button" class="exp-req-btn exp-req-btn-primary" style="background:#dc2626;" onclick="showConfirmRejectModal()">Reject Request</button>
            </div>
        </form>
    </div>
</div>

{{-- Expense Details & Attachments Modal --}}
<div class="exp-modal-overlay" id="expenseDetailModal" style="display:none;">
    <div class="exp-modal" style="max-width: 620px; max-height: 90vh;">
        <div class="exp-modal-head">
            <div style="display:flex; align-items:center; gap:10px;">
                <div class="exp-modal-title">Expense Request Details</div>
                <span id="detailStatusBadge" class="exp-badge"></span>
            </div>
            <button class="exp-modal-close" onclick="closeExpenseDetailModal()">✕</button>
        </div>

        <div class="exp-modal-body" style="overflow-y: auto; max-height: calc(90vh - 140px); gap: 16px;">
            
            {{-- Quick Overview Cards --}}
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 2px;">Applicant</span>
                    <div id="detailApplicant" style="font-size: 14px; font-weight: 800; color: #0f172a;"></div>
                    <span id="detailRoleBranch" style="font-size: 11px; color: #64748b;"></span>
                </div>
                <div style="padding: 12px 14px; background: #fff7ed; border: 1px solid #ffedd5; border-radius: 12px;">
                    <span style="font-size: 11px; font-weight: 700; color: #c2410c; text-transform: uppercase; display: block; margin-bottom: 2px;">Category & Amount</span>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span id="detailCategory" style="font-size: 13px; font-weight: 700; color: #ea580c;"></span>
                        <span id="detailAmount" style="font-size: 16px; font-weight: 800; color: #9a3412;"></span>
                    </div>
                    <span id="detailDate" style="font-size: 11px; color: #9a3412; opacity: 0.8; margin-top: 2px; display: block;"></span>
                </div>
            </div>

            {{-- Full Description Box --}}
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label class="exp-form-label" style="color: #374151;">Description & Reason</label>
                <div id="detailDescription" style="padding: 14px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; font-size: 13px; line-height: 1.6; color: #374151; white-space: pre-wrap; word-break: break-word;"></div>
            </div>

            {{-- Attachments Gallery / List --}}
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <label class="exp-form-label" style="color: #374151; margin-bottom: 0;">Attachments & Receipts</label>
                    <span id="detailAttCount" style="font-size: 11px; font-weight: 700; color: #6b7280;"></span>
                </div>
                
                <div id="detailAttachmentsContainer" style="display: flex; flex-direction: column; gap: 8px;">
                    {{-- Dynamically populated via JS --}}
                </div>
            </div>

        </div>

        <div class="exp-modal-foot">
            <button type="button" class="exp-req-btn exp-req-btn-outline" onclick="closeExpenseDetailModal()">Close</button>
        </div>
    </div>
</div>

{{-- Confirm Send Modal --}}
<div id="confirmSendModal" class="exp-confirm-modal">
    <div class="exp-confirm-card">
        <div class="exp-confirm-head" style="background:#fff7ed; border-bottom-color:#ffedd5;">
            <div class="exp-confirm-title" style="color:#c2410c;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Confirm Submission
            </div>
            <button class="exp-modal-close" onclick="closeConfirmSendModal()">✕</button>
        </div>
        <div class="exp-confirm-body">
            <p style="font-size:15px; font-weight:800; color:#111827; margin-bottom:8px;">Are you sure you want to submit this expense request?</p>
            <p style="font-size:13px; color:#6b7280; line-height:1.5;">An automated email notification will be sent to the Stage 1 approver according to the HRMS approval pipeline.</p>
        </div>
        <div class="exp-confirm-foot">
            <button type="button" class="exp-req-btn exp-req-btn-outline" onclick="closeConfirmSendModal()">Cancel</button>
            <button type="button" class="exp-req-btn exp-req-btn-primary" id="btnConfirmSendSubmit">Yes, Send Request</button>
        </div>
    </div>
</div>

{{-- Confirm Approve Modal --}}
<div id="confirmApproveModal" class="exp-confirm-modal">
    <div class="exp-confirm-card">
        <div class="exp-confirm-head" style="background:#f0fdf4; border-bottom-color:#bbf7d0;">
            <div class="exp-confirm-title" style="color:#15803d;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Confirm Approval
            </div>
            <button class="exp-modal-close" onclick="closeConfirmApproveModal()">✕</button>
        </div>
        <div class="exp-confirm-body">
            <p style="font-size:15px; font-weight:800; color:#111827; margin-bottom:8px;">Are you sure you want to approve this expense request?</p>
            <p style="font-size:13px; color:#6b7280; line-height:1.5;">An email notification will be sent to the next stage approver (or applicant if final stage).</p>
        </div>
        <div class="exp-confirm-foot">
            <button type="button" class="exp-req-btn exp-req-btn-outline" onclick="closeConfirmApproveModal()">Cancel</button>
            <button type="button" class="exp-req-btn exp-req-btn-primary" id="btnConfirmApproveSubmit" style="background:#16a34a;">Yes, Approve</button>
        </div>
    </div>
</div>

{{-- Confirm Reject Modal --}}
<div id="confirmRejectModal" class="exp-confirm-modal">
    <div class="exp-confirm-card">
        <div class="exp-confirm-head" style="background:#fef2f2; border-bottom-color:#fecaca;">
            <div class="exp-confirm-title" style="color:#b91c1c;">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Confirm Rejection
            </div>
            <button class="exp-confirm-body" style="padding:0; border:none; background:none;">
            </button>
            <button class="exp-modal-close" onclick="closeConfirmRejectModal()">✕</button>
        </div>
        <div class="exp-confirm-body">
            <p style="font-size:15px; font-weight:800; color:#111827; margin-bottom:8px;">Are you sure you want to reject this expense request?</p>
            <p style="font-size:13px; color:#6b7280; line-height:1.5;">An email notification with the rejection reason will be sent to the applicant.</p>
        </div>
        <div class="exp-confirm-foot">
            <button type="button" class="exp-req-btn exp-req-btn-outline" onclick="closeConfirmRejectModal()">Cancel</button>
            <button type="button" class="exp-req-btn exp-req-btn-primary" id="btnConfirmRejectSubmit" style="background:#dc2626;">Yes, Reject Request</button>
        </div>
    </div>
</div>

{{-- Process Overlay / Progress Bar Modal --}}
<div id="expProcessOverlay" class="exp-process-overlay">
    <div class="exp-process-card">
        <div class="exp-process-icon-wrap">
            <div class="exp-process-spinner"></div>
            <svg class="exp-process-icon" width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <h4 id="expProcessTitle" class="exp-process-title">Sending Email & Processing...</h4>
        <p id="expProcessSubtitle" class="exp-process-subtitle">Please wait while the email notification is being sent...</p>

        <div class="exp-progress-wrapper">
            <div class="exp-progress-bar">
                <div id="expProgressFill" class="exp-progress-fill"></div>
            </div>
            <div class="exp-progress-status">
                <span id="expProgressText">Preparing email notification...</span>
                <span id="expProgressPercent">0%</span>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let pendingApproveFormId = null;

$(document).ready(function() {
    if (window.jQuery && window.jQuery.fn.select2) {
        $('#send_expense_category_id').select2({
            placeholder: '-- Select Category --',
            allowClear: true,
            dropdownParent: $('#sendModal'),
            width: '100%'
        });
    }
});

function openSendModal() {
    document.getElementById('sendModal').style.display = 'flex';
    if (window.jQuery && window.jQuery.fn.select2) {
        $('#send_expense_category_id').val('').trigger('change.select2');
    }
}
function closeSendModal() {
    document.getElementById('sendModal').style.display = 'none';
}

function openRejectModal(requestId) {
    document.getElementById('rejectForm').action = `/hrms/expense-requests/${requestId}/reject`;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

function openExpenseDetailModal(btn) {
    try {
        const raw = btn.getAttribute('data-details');
        const data = JSON.parse(raw);

        document.getElementById('detailApplicant').textContent = data.applicant || 'Unknown';
        document.getElementById('detailRoleBranch').textContent = (data.role || '') + (data.branch ? (' • ' + data.branch) : '');
        document.getElementById('detailCategory').textContent = data.category || 'General';
        document.getElementById('detailAmount').textContent = '₹' + data.amount;
        document.getElementById('detailDate').textContent = 'Submitted on: ' + (data.created_at || '-');
        document.getElementById('detailDescription').textContent = data.description || '-';

        // Status Badge
        const statusBadge = document.getElementById('detailStatusBadge');
        statusBadge.className = 'exp-badge ' + (data.status === 'approved' ? 'eb-approved' : (data.status === 'rejected' ? 'eb-rejected' : 'eb-pending'));
        statusBadge.textContent = data.status === 'approved' ? '✓ Approved' : (data.status === 'rejected' ? '✕ Rejected' : '⏳ Pending');

        // Attachments
        const container = document.getElementById('detailAttachmentsContainer');
        container.innerHTML = '';
        const attachments = data.attachments || [];
        document.getElementById('detailAttCount').textContent = attachments.length + ' file(s)';

        if (attachments.length === 0) {
            container.innerHTML = `
                <div style="padding: 16px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; text-align: center; color: #94a3b8; font-size: 13px;">
                    📎 No receipts or attachments uploaded with this request.
                </div>
            `;
        } else {
            let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px;">';
            attachments.forEach((url, idx) => {
                const ext = url.split('.').pop().toLowerCase().split('?')[0];
                const isImage = ['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext);
                const isPdf = ext === 'pdf';
                const fileLabel = `Receipt #${idx + 1}`;

                html += `
                    <div style="padding: 10px 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; display: flex; align-items: center; justify-content: space-between; gap: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1;">
                            ${isImage 
                                ? `<a href="${url}" target="_blank" style="display:block; width:44px; height:44px; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; flex-shrink:0;">
                                    <img src="${url}" style="width:100%; height:100%; object-fit:cover;" alt="Receipt">
                                   </a>`
                                : `<div style="width:44px; height:44px; border-radius:8px; background:${isPdf ? '#fee2e2' : '#e0e7ff'}; color:${isPdf ? '#dc2626' : '#4f46e5'}; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:11px; flex-shrink:0;">
                                    ${isPdf ? 'PDF' : 'DOC'}
                                   </div>`
                            }
                            <div style="min-width: 0; flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${fileLabel}</div>
                                <span style="font-size: 11px; color: #64748b; text-transform: uppercase;">${ext.toUpperCase()} File</span>
                            </div>
                        </div>
                        <a href="${url}" target="_blank" style="display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; background: #fe5f04; color: #fff; border-radius: 8px; font-size: 11px; font-weight: 700; text-decoration: none; flex-shrink: 0; gap: 4px;" title="Open in new tab">
                            <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                            View
                        </a>
                    </div>
                `;
            });
            html += '</div>';
            container.innerHTML = html;
        }

        document.getElementById('expenseDetailModal').style.display = 'flex';
    } catch (e) {
        console.error('Error opening expense details modal:', e);
    }
}

function closeExpenseDetailModal() {
    document.getElementById('expenseDetailModal').style.display = 'none';
}

// Confirmation Modals Logic
function showConfirmSendModal() {
    const form = document.getElementById('createExpenseForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    document.getElementById('confirmSendModal').classList.add('show');
}
function closeConfirmSendModal() {
    document.getElementById('confirmSendModal').classList.remove('show');
}

function showConfirmApproveModal(formId) {
    pendingApproveFormId = formId;
    document.getElementById('confirmApproveModal').classList.add('show');
}
function closeConfirmApproveModal() {
    pendingApproveFormId = null;
    document.getElementById('confirmApproveModal').classList.remove('show');
}

function showConfirmRejectModal() {
    const form = document.getElementById('rejectForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    document.getElementById('confirmRejectModal').classList.add('show');
}
function closeConfirmRejectModal() {
    document.getElementById('confirmRejectModal').classList.remove('show');
}

// Progress Overlay Logic (Matching Support Portal)
let expProcessProgressInterval = null;

function showProcessOverlay(title, subtitle) {
    if (title) document.getElementById('expProcessTitle').innerText = title;
    if (subtitle) document.getElementById('expProcessSubtitle').innerText = subtitle;

    const overlay = document.getElementById('expProcessOverlay');
    const fill = document.getElementById('expProgressFill');
    const percentText = document.getElementById('expProgressPercent');
    const statusText = document.getElementById('expProgressText');

    overlay.style.display = 'flex';

    let currentProgress = 5;
    fill.style.width = currentProgress + '%';
    percentText.innerText = currentProgress + '%';
    statusText.innerText = 'Connecting to server...';

    if (expProcessProgressInterval) clearInterval(expProcessProgressInterval);

    expProcessProgressInterval = setInterval(function() {
        if (currentProgress < 30) {
            currentProgress += Math.floor(Math.random() * 8) + 4;
            statusText.innerText = 'Building email notification...';
        } else if (currentProgress < 70) {
            currentProgress += Math.floor(Math.random() * 6) + 3;
            statusText.innerText = 'Sending email via SMTP...';
        } else if (currentProgress < 92) {
            currentProgress += Math.floor(Math.random() * 3) + 1;
            statusText.innerText = 'Finalizing expense workflow...';
        }

        if (currentProgress > 94) {
            currentProgress = 94;
        }

        fill.style.width = currentProgress + '%';
        percentText.innerText = currentProgress + '%';
    }, 250);
}

document.addEventListener('DOMContentLoaded', function() {
    // Confirm Send Submit Listener
    const btnSend = document.getElementById('btnConfirmSendSubmit');
    if (btnSend) {
        btnSend.addEventListener('click', function() {
            const form = document.getElementById('createExpenseForm');
            closeConfirmSendModal();
            closeSendModal();
            showProcessOverlay(
                "Sending Email & Creating Expense Request...",
                "Please wait while your expense request is submitted and email notification is sent..."
            );
            form.submit();
        });
    }

    // Confirm Approve Submit Listener
    const btnApprove = document.getElementById('btnConfirmApproveSubmit');
    if (btnApprove) {
        btnApprove.addEventListener('click', function() {
            if (pendingApproveFormId) {
                const form = document.getElementById(pendingApproveFormId);
                closeConfirmApproveModal();
                showProcessOverlay(
                    "Sending Email & Processing Approval...",
                    "Please wait while the expense request is approved and email notification is sent..."
                );
                form.submit();
            }
        });
    }

    // Confirm Reject Submit Listener
    const btnReject = document.getElementById('btnConfirmRejectSubmit');
    if (btnReject) {
        btnReject.addEventListener('click', function() {
            const form = document.getElementById('rejectForm');
            closeConfirmRejectModal();
            closeRejectModal();
            showProcessOverlay(
                "Sending Email & Processing Rejection...",
                "Please wait while the expense request is rejected and email notification is sent..."
            );
            form.submit();
        });
    }
});

document.getElementById('sendModal').addEventListener('click', function(e) {
    if (e.target === this) closeSendModal();
});
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endpush
