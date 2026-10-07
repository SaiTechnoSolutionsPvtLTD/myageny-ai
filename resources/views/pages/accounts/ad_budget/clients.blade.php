@extends('layouts.app')

@section('title', 'Ad Budget — For Clients')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
.adb-page { display:flex; flex-direction:column; gap:20px; padding:24px 28px; background:#f4f5f7; font-family:'Inter',sans-serif; min-height:100%; }
.adb-topbar { display:flex; align-items:center; justify-content:space-between; background:#fff; padding:18px 24px; border-radius:14px; border:1px solid #e2e8f0; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-title { font-size:20px; font-weight:800; color:#0f172a; margin-bottom:2px; }
.adb-sub { font-size:13px; color:#64748b; }

.adb-stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:14px; }
.adb-stat-card { background:#fff; border-radius:14px; padding:16px 20px; border:1px solid #e2e8f0; display:flex; align-items:center; gap:16px; box-shadow:0 2px 6px rgba(0,0,0,.02); }
.adb-stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.adb-stat-label { font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.adb-stat-val { font-size:22px; font-weight:800; color:#0f172a; margin-top:3px; line-height:1; }

.adb-card { background:#fff; border-radius:16px; border:1px solid #e2e8f0; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.02); }
.adb-nav-tabs { display:flex; align-items:center; gap:4px; padding:12px 20px 0 20px; background:#f8fafc; border-bottom:1px solid #e2e8f0; overflow-x:auto; }
.adb-tab-item { display:inline-flex; align-items:center; gap:8px; padding:10px 16px; border-radius:10px 10px 0 0; font-size:13px; font-weight:700; color:#64748b; text-decoration:none; border:1px solid transparent; border-bottom:none; background:transparent; transition:all .15s ease; white-space:nowrap; }
.adb-tab-item:hover { color:#fe5f04; background:#ffffff; }
.adb-tab-item.active { color:#fe5f04; background:#ffffff; border-color:#e2e8f0; border-bottom:2px solid #fe5f04; margin-bottom:-1px; }
.adb-tab-badge { padding:2px 8px; border-radius:999px; font-size:11px; font-weight:800; background:#e2e8f0; color:#475569; }
.adb-tab-item.active .adb-tab-badge { background:#fff3eb; color:#fe5f04; }
.adb-filter-bar { display:flex; align-items:center; gap:10px; padding:14px 20px; border-bottom:1px solid #e2e8f0; background:#fff; flex-wrap:nowrap; overflow-x:auto; }
.adb-search-wrap { position:relative; flex:1; min-width:200px; }
.adb-search-input { width:100%; padding:8px 12px 8px 36px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; outline:none; font-family:inherit; }
.adb-search-ico { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#94a3b8; }
.adb-select { padding:8px 14px; border:1px solid #cbd5e1; border-radius:8px; font-size:13px; font-family:inherit; outline:none; background:#fff; }

.adb-btn { display:inline-flex; align-items:center; gap:8px; padding:9px 18px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .2s; border:none; cursor:pointer; }
.adb-btn-primary { background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; box-shadow:0 4px 14px rgba(254,95,4,.25); }
.adb-btn-primary:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(254,95,4,.35); color:#fff; }
.adb-btn-secondary { background:#f1f5f9; color:#334155; }
.adb-btn-secondary:hover { background:#e2e8f0; }

.adb-tbl { width:100%; border-collapse:collapse; }
.adb-tbl th { font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:#64748b; padding:12px 16px; border-bottom:1px solid #e2e8f0; background:#f8fafc; text-align:left; }
.adb-tbl td { padding:14px 16px; font-size:13px; color:#1e293b; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.adb-badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:700; }
.ab-tl_pending { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
.ab-accounts_pending { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
.ab-approved { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.ab-rejected { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }

/* Modal Styling */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.5); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; }
.modal-overlay.active { display:flex; }
.modal-box { background:#fff; border-radius:18px; width:92%; max-width:560px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 60px rgba(0,0,0,.25); animation:popIn .25s ease; }
@keyframes popIn { from{ opacity:0; transform:scale(.95); } to{ opacity:1; transform:scale(1); } }
.modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #e2e8f0; background:#f8fafc; sticky:top; }
.modal-title { font-size:17px; font-weight:800; color:#0f172a; }
.modal-close { background:none; border:none; font-size:22px; color:#94a3b8; cursor:pointer; line-height:1; }
.modal-body { padding:24px; display:flex; flex-direction:column; gap:16px; }
.form-group { display:flex; flex-direction:column; gap:6px; }
.form-label { font-size:12px; font-weight:700; color:#334155; }
.form-input { padding:9px 14px; border:1px solid #cbd5e1; border-radius:9px; font-size:13px; font-family:inherit; outline:none; }
.form-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }

/* Multi-date Tags */
.date-tags-container { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; min-height:36px; padding:8px; border:1px dashed #cbd5e1; border-radius:9px; background:#fafafa; }
.date-tag { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:6px; font-size:12px; font-weight:700; }
.date-tag-remove { cursor:pointer; color:#ef4444; font-weight:900; line-height:1; }

.summary-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:16px; display:flex; flex-direction:column; gap:10px; }
.summary-row { display:flex; justify-content:space-between; font-size:13px; }
.summary-label { color:#64748b; font-weight:600; }
.summary-val { color:#0f172a; font-weight:700; }

/* File Tag */
.file-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:8px; background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; font-size:12px; font-weight:600; text-decoration:none; margin:3px; }
.file-chip:hover { background:#e2e8f0; color:#0f172a; }

/* Submission Progress & Overlay */
.submit-loading-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,.7); backdrop-filter:blur(5px); z-index:99999; align-items:center; justify-content:center; }
.submit-loading-overlay.active { display:flex; }
.submit-loading-card { background:#fff; border-radius:18px; padding:32px 40px; box-shadow:0 24px 60px rgba(0,0,0,.35); text-align:center; max-width:440px; width:90%; color:#0f172a; animation:popIn .25s ease; }
.submit-spinner { width:50px; height:50px; border:4px solid #fed7aa; border-top:4px solid #fe5f04; border-radius:50%; animation:spin .8s linear infinite; margin:0 auto 16px; }
@keyframes spin { 0%{ transform:rotate(0deg); } 100%{ transform:rotate(360deg); } }
.progress-bar-wrap { width:100%; height:8px; background:#e2e8f0; border-radius:999px; overflow:hidden; margin-top:16px; position:relative; }
.progress-bar-fill { height:100%; width:0%; background:linear-gradient(90deg,#fe5f04,#ff7c30); border-radius:999px; animation:progressAnim 3s ease-in-out forwards; }
@keyframes progressAnim { 0%{ width:5%; } 30%{ width:40%; } 70%{ width:80%; } 95%{ width:95%; } }
</style>
@endpush

@section('content')
<div class="adb-page">

    {{-- Topbar --}}
    <div class="adb-topbar">
        <div>
            <div class="adb-title">Ad Budget — For Clients</div>
            <div class="adb-sub">Raise & track Digital Marketing campaign ad budget requests with 2-stage approval</div>
        </div>
        <button type="button" class="adb-btn adb-btn-primary" onclick="openRaiseModal()">
            <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Raise Request
        </button>
    </div>

    {{-- Flash messages --}}
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
                <div class="adb-stat-label">Total Requested</div>
                <div class="adb-stat-val">₹{{ number_format($totalRequested, 2) }}</div>
            </div>
        </div>
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#f0fdf4; color:#16a34a;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="adb-stat-label">Total Approved</div>
                <div class="adb-stat-val">₹{{ number_format($totalApproved, 2) }}</div>
            </div>
        </div>
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#fef3c7; color:#d97706;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
                <div class="adb-stat-label">Pending DM TL</div>
                <div class="adb-stat-val">{{ $pendingTlCount }}</div>
            </div>
        </div>
        <div class="adb-stat-card">
            <div class="adb-stat-icon" style="background:#e0f2fe; color:#0284c7;">
                <i class="bi bi-person-check-fill"></i>
            </div>
            <div>
                <div class="adb-stat-label">Pending Accounts</div>
                <div class="adb-stat-val">{{ $pendingAccCount }}</div>
            </div>
        </div>
    </div>

    {{-- Requests Table Card --}}
    <div class="adb-card">
        @php
            $user = auth()->user();
            $isAccountsUser = ($user?->email === 'hr@saitechnosolutions.net') || $user?->hasAdminLikeRole();
            $isDmTlUser     = $user?->hasTlLikeRole() || $user?->hasAdminLikeRole();
            $activeStatus   = request('status');
        @endphp

        {{-- Nav Tabs --}}
        <div class="adb-nav-tabs">
            @if($isAccountsUser)
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => ''])) }}" class="adb-tab-item {{ empty($activeStatus) ? 'active' : '' }}">
                    <span>All Requests</span>
                    <span class="adb-tab-badge">{{ $statusCounts['all'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'accounts_pending'])) }}" class="adb-tab-item {{ $activeStatus === 'accounts_pending' ? 'active' : '' }}">
                    <span>New Requests (Pending HR)</span>
                    <span class="adb-tab-badge" style="background:#e0f2fe; color:#0369a1;">{{ $statusCounts['accounts_pending'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'approved'])) }}" class="adb-tab-item {{ $activeStatus === 'approved' ? 'active' : '' }}">
                    <span>HR Approved</span>
                    <span class="adb-tab-badge" style="background:#dcfce7; color:#15803d;">{{ $statusCounts['approved'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'rejected'])) }}" class="adb-tab-item {{ $activeStatus === 'rejected' ? 'active' : '' }}">
                    <span>Rejected</span>
                    <span class="adb-tab-badge" style="background:#fee2e2; color:#b91c1c;">{{ $statusCounts['rejected'] ?? 0 }}</span>
                </a>
            @elseif($isDmTlUser)
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => ''])) }}" class="adb-tab-item {{ empty($activeStatus) ? 'active' : '' }}">
                    <span>All Requests</span>
                    <span class="adb-tab-badge">{{ $statusCounts['all'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'tl_pending'])) }}" class="adb-tab-item {{ $activeStatus === 'tl_pending' ? 'active' : '' }}">
                    <span>New Requests (Pending TL)</span>
                    <span class="adb-tab-badge" style="background:#fef3c7; color:#b45309;">{{ $statusCounts['tl_pending'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'accounts_pending'])) }}" class="adb-tab-item {{ $activeStatus === 'accounts_pending' ? 'active' : '' }}">
                    <span>Pending Accounts</span>
                    <span class="adb-tab-badge" style="background:#e0f2fe; color:#0369a1;">{{ $statusCounts['accounts_pending'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'approved'])) }}" class="adb-tab-item {{ $activeStatus === 'approved' ? 'active' : '' }}">
                    <span>Approved</span>
                    <span class="adb-tab-badge" style="background:#dcfce7; color:#15803d;">{{ $statusCounts['approved'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'rejected'])) }}" class="adb-tab-item {{ $activeStatus === 'rejected' ? 'active' : '' }}">
                    <span>Rejected</span>
                    <span class="adb-tab-badge" style="background:#fee2e2; color:#b91c1c;">{{ $statusCounts['rejected'] ?? 0 }}</span>
                </a>
            @else
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => ''])) }}" class="adb-tab-item {{ empty($activeStatus) ? 'active' : '' }}">
                    <span>All Requests</span>
                    <span class="adb-tab-badge">{{ $statusCounts['all'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'tl_pending'])) }}" class="adb-tab-item {{ $activeStatus === 'tl_pending' ? 'active' : '' }}">
                    <span>Pending TL</span>
                    <span class="adb-tab-badge" style="background:#fef3c7; color:#b45309;">{{ $statusCounts['tl_pending'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'accounts_pending'])) }}" class="adb-tab-item {{ $activeStatus === 'accounts_pending' ? 'active' : '' }}">
                    <span>Pending HR Approval</span>
                    <span class="adb-tab-badge" style="background:#e0f2fe; color:#0369a1;">{{ $statusCounts['accounts_pending'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'approved'])) }}" class="adb-tab-item {{ $activeStatus === 'approved' ? 'active' : '' }}">
                    <span>Approved</span>
                    <span class="adb-tab-badge" style="background:#dcfce7; color:#15803d;">{{ $statusCounts['approved'] ?? 0 }}</span>
                </a>
                <a href="{{ route('accounts.ad-budget.clients', array_merge(request()->except('status'), ['status' => 'rejected'])) }}" class="adb-tab-item {{ $activeStatus === 'rejected' ? 'active' : '' }}">
                    <span>Rejected</span>
                    <span class="adb-tab-badge" style="background:#fee2e2; color:#b91c1c;">{{ $statusCounts['rejected'] ?? 0 }}</span>
                </a>
            @endif
        </div>

        {{-- Single-Row Filter Bar --}}
        <form method="GET" action="{{ route('accounts.ad-budget.clients') }}">
            <div class="adb-filter-bar">
                <div class="adb-search-wrap">
                    <i class="bi bi-search adb-search-ico"></i>
                    <input type="text" name="search" class="adb-search-input" placeholder="Search account name, requester, remarks…" value="{{ request('search') }}">
                </div>
                <select name="ad_account_id" class="adb-select" style="min-width:180px; flex-shrink:0;" onchange="this.form.submit()">
                    <option value="">All Ad Accounts</option>
                    @foreach($adAccounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('ad_account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->account_name }} ({{ $acc->platform }})
                        </option>
                    @endforeach
                </select>
                <select name="status" class="adb-select" style="min-width:160px; flex-shrink:0;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="tl_pending" {{ request('status') === 'tl_pending' ? 'selected' : '' }}>Pending DM TL</option>
                    <option value="accounts_pending" {{ request('status') === 'accounts_pending' ? 'selected' : '' }}>Pending Accounts</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <button type="submit" class="adb-btn adb-btn-secondary" style="padding:8px 14px; font-size:12px; flex-shrink:0;">Filter</button>
                @if(request()->hasAny(['search','ad_account_id','status']))
                    <a href="{{ route('accounts.ad-budget.clients') }}" class="adb-btn adb-btn-secondary" style="padding:8px 14px; font-size:12px; background:#fff; border:1px solid #cbd5e1; flex-shrink:0;">Reset</a>
                @endif
            </div>
        </form>

        @if($query->isEmpty())
            <div style="text-align:center; padding:40px; color:#94a3b8;">
                <i class="bi bi-inbox" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                No Client Ad Budget requests found. Click "Raise Request" to create one.
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="adb-tbl">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Ad Account</th>
                            <th>Requested Amount</th>
                            <th>Approved Amount</th>
                            <th>Selected Dates</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $user = auth()->user();
                            $isAccountsUser = ($user?->email === 'hr@saitechnosolutions.net') || $user?->hasAdminLikeRole();
                            $isDmTlUser     = $user?->hasTlLikeRole() || $user?->hasAdminLikeRole();
                        @endphp
                        @foreach($query as $item)
                        <tr>
                            <td style="color:#94a3b8; font-weight:700;">#{{ $item->id }}</td>
                            <td>
                                <strong>{{ $item->adAccount?->account_name ?? '—' }}</strong>
                                <div style="font-size:11px; color:#64748b;">
                                    {{ $item->adAccount?->platform }} {{ $item->adAccount?->account_id ? '('.$item->adAccount->account_id.')' : '' }}
                                </div>
                            </td>
                            <td style="font-weight:700; color:#475569;">₹{{ number_format($item->amount, 2) }}</td>
                            <td style="font-weight:800; color:{{ $item->approved_amount > 0 ? '#15803d' : '#94a3b8' }};">
                                {{ $item->approved_amount > 0 ? '₹'.number_format($item->approved_amount, 2) : '—' }}
                            </td>
                            <td>
                                @if(!empty($item->selected_dates))
                                    @foreach((array)$item->selected_dates as $dt)
                                        <span style="display:inline-block; padding:2px 7px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:5px; font-size:11px; font-weight:700; margin:1px;">
                                            📅 {{ $dt }}
                                        </span>
                                    @endforeach
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td>
                                <div><strong>{{ $item->requester?->name ?? '—' }}</strong></div>
                                <div style="font-size:11px; color:#94a3b8;">{{ $item->created_at->format('d M, h:i A') }}</div>
                            </td>
                            <td>
                                <span class="adb-badge ab-{{ $item->status }}">
                                    @if($item->status === 'tl_pending')
                                        ⏳ Pending DM TL
                                    @elseif($item->status === 'accounts_pending')
                                        🕒 Pending Accounts
                                    @elseif($item->status === 'approved')
                                        ✓ Approved
                                    @elseif($item->status === 'rejected')
                                        ✕ Rejected
                                    @else
                                        {{ ucfirst($item->status) }}
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    {{-- Step 1: DM TL Approval Buttons --}}
                                    @if($item->status === 'tl_pending' && $isDmTlUser)
                                        <form method="POST" action="{{ route('accounts.ad-budget.tl-approve', $item) }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="adb-btn" style="padding:4px 10px; font-size:11px; background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;">
                                                TL Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('accounts.ad-budget.tl-approve', $item) }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="adb-btn" style="padding:4px 10px; font-size:11px; background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;">
                                                Reject
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Step 2: Accounts Team Approval Button (hr@saitechnosolutions.net) --}}
                                    @if($item->status === 'accounts_pending' && $isAccountsUser)
                                        <button type="button" class="adb-btn" style="padding:4px 10px; font-size:11px; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;"
                                                onclick='openAccountsApproveModal(@json($item))'>
                                            Approve & Fill Details
                                        </button>
                                    @endif

                                    {{-- View Details Button for Executive & TL & Accounts --}}
                                    <button type="button" class="adb-btn adb-btn-secondary" style="padding:4px 10px; font-size:11px;"
                                            onclick='openViewDetailsModal(@json($item))'>
                                        👁️ View
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($query->hasPages())
                <div style="padding:16px 20px; border-top:1px solid #e2e8f0;">
                    {{ $query->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- Raise Request Form Modal (Step 1) --}}
<div class="modal-overlay" id="raiseModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Raise Ad Budget Request (Client)</div>
            <button type="button" class="modal-close" onclick="closeRaiseModal()">×</button>
        </div>
        <form id="raiseForm" onsubmit="event.preventDefault(); proceedToConfirm();">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Ad Account <span style="color:#dc2626;">*</span></label>
                    <select id="ad_account_id" class="form-input" required>
                        <option value="">-- Select Ad Account from Master --</option>
                        @foreach($adAccounts as $acc)
                            <option value="{{ $acc->id }}" data-name="{{ $acc->account_name }}" data-platform="{{ $acc->platform }}">
                                {{ $acc->account_name }} ({{ $acc->platform }}) {{ $acc->account_id ? '- '.$acc->account_id : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Budget Amount (₹) <span style="color:#dc2626;">*</span></label>
                    <input type="number" id="amount" class="form-input" step="0.01" min="1" placeholder="e.g. 5000.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Select Dates (Multiple Dates Selection) <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="flatpickr_dates_input" class="form-input" placeholder="Click to select multiple dates…" required readonly>
                    <div class="date-tags-container" id="dateTagsList">
                        <span style="font-size:12px; color:#94a3b8;">No dates selected yet. Pick dates from the picker above.</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Remarks / Description</label>
                    <textarea id="remarks" class="form-input" rows="2" placeholder="Campaign goals, target platforms, special instructions…"></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                    <button type="button" class="adb-btn adb-btn-secondary" onclick="closeRaiseModal()">Cancel</button>
                    <button type="submit" class="adb-btn adb-btn-primary">Proceed to Confirm →</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Confirmation Modal (Step 2) --}}
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Confirm Ad Budget Request</div>
            <button type="button" class="modal-close" onclick="closeConfirmModal()">×</button>
        </div>
        <form method="POST" action="{{ route('accounts.ad-budget.clients.raise-request') }}" id="finalSubmitForm">
            @csrf
            <input type="hidden" name="ad_account_id" id="hidden_ad_account_id">
            <input type="hidden" name="amount" id="hidden_amount">
            <input type="hidden" name="selected_dates" id="hidden_selected_dates">
            <input type="hidden" name="remarks" id="hidden_remarks">

            <div class="modal-body">
                <div style="font-size:13px; color:#475569;">
                    Please confirm the details of your Ad Budget Request before submitting:
                </div>

                <div class="summary-card">
                    <div class="summary-row">
                        <span class="summary-label">Ad Account:</span>
                        <span class="summary-val" id="summary_ad_account"></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Budget Amount:</span>
                        <span class="summary-val" style="color:#fe5f04; font-size:16px;" id="summary_amount"></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Selected Dates:</span>
                        <span class="summary-val" id="summary_dates"></span>
                    </div>
                    <div class="summary-row" id="summary_remarks_row">
                        <span class="summary-label">Remarks:</span>
                        <span class="summary-val" id="summary_remarks"></span>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                    <button type="button" class="adb-btn adb-btn-secondary" onclick="backToEdit()">← Back / Edit</button>
                    <button type="submit" class="adb-btn adb-btn-primary">Confirm & Submit Request</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Accounts Team Approval Modal (hr@saitechnosolutions.net) --}}
<div class="modal-overlay" id="accApproveModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Accounts Approval (hr@saitechnosolutions.net)</div>
            <button type="button" class="modal-close" onclick="closeAccApproveModal()">×</button>
        </div>
        <form method="POST" id="accApproveForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div style="font-size:13px; color:#475569; margin-bottom:4px;">
                    Fill approval details & upload proof documents for Request <strong id="acc_modal_req_id"></strong>:
                </div>

                <div class="form-group">
                    <label class="form-label">Payment / Approval Date <span style="color:#dc2626;">*</span></label>
                    <input type="date" name="payment_date" id="acc_payment_date" class="form-input" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm / Select Ad Account <span style="color:#dc2626;">*</span></label>
                    <select name="ad_account_id" id="acc_ad_account_id" class="form-input" required>
                        @foreach($adAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->account_name }} ({{ $acc->platform }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Approved Amount (₹) <span style="color:#dc2626;">*</span></label>
                    <input type="number" name="approved_amount" id="acc_approved_amount" class="form-input" step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Accounts Remarks</label>
                    <textarea name="accounts_remarks" id="acc_remarks" class="form-input" rows="2" placeholder="Payment reference, bank details, transaction notes…"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Attachments (Multiple - Non Mandatory)</label>
                    <input type="file" name="attachments[]" class="form-input" multiple accept="image/*,.pdf,.doc,.docx,.xlsx">
                    <span style="font-size:11px; color:#64748b;">Attach payment screenshot, bank receipt, invoice copy, etc.</span>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                    <button type="button" class="adb-btn adb-btn-secondary" onclick="closeAccApproveModal()">Cancel</button>
                    <button type="submit" class="adb-btn adb-btn-primary" style="background:linear-gradient(135deg,#059669,#10b981);">
                        ✓ Approve & Finalize Payment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- View Details Modal for DM TL & DM Executive --}}
<div class="modal-overlay" id="viewDetailsModal">
    <div class="modal-box">
        <div class="modal-header">
            <div class="modal-title">Ad Budget Request Details</div>
            <button type="button" class="modal-close" onclick="closeViewDetailsModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="summary-card">
                <div class="summary-row">
                    <span class="summary-label">Request ID:</span>
                    <span class="summary-val" id="view_req_id"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Requested By:</span>
                    <span class="summary-val" id="view_requester"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Requested Amount:</span>
                    <span class="summary-val" id="view_req_amount"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Selected Dates:</span>
                    <span class="summary-val" id="view_dates"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Status:</span>
                    <span class="summary-val" id="view_status"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Executive Remarks:</span>
                    <span class="summary-val" id="view_exec_remarks"></span>
                </div>
            </div>

            <div style="font-size:14px; font-weight:800; color:#0f172a; margin-top:8px;">DM TL Approval:</div>
            <div class="summary-card">
                <div class="summary-row">
                    <span class="summary-label">DM TL Status:</span>
                    <span class="summary-val" id="view_tl_status"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">TL Approved By:</span>
                    <span class="summary-val" id="view_tl_by"></span>
                </div>
            </div>

            <div style="font-size:14px; font-weight:800; color:#0f172a; margin-top:8px;">Accounts Team Approval (hr@saitechnosolutions.net):</div>
            <div class="summary-card">
                <div class="summary-row">
                    <span class="summary-label">Accounts Approved By:</span>
                    <span class="summary-val" id="view_acc_by"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Payment / Approval Date:</span>
                    <span class="summary-val" id="view_payment_date"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Confirmed Ad Account:</span>
                    <span class="summary-val" id="view_ad_account"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Final Approved Amount:</span>
                    <span class="summary-val" style="color:#059669; font-weight:800; font-size:15px;" id="summary_approved_amount"></span>
                </div>
                <div class="summary-row">
                    <span class="summary-label">Accounts Remarks:</span>
                    <span class="summary-val" id="view_acc_remarks"></span>
                </div>
                <div class="summary-row" style="flex-direction:column; gap:6px;">
                    <span class="summary-label">Attachments & Payment Proofs:</span>
                    <div id="view_attachments_list" style="margin-top:4px;"></div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                <button type="button" class="adb-btn adb-btn-secondary" onclick="closeViewDetailsModal()">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Global Progress Overlay for Mail Dispatch & Form Submission --}}
<div class="submit-loading-overlay" id="globalLoadingOverlay">
    <div class="submit-loading-card">
        <div class="submit-spinner"></div>
        <div style="font-size:17px; font-weight:800; color:#0f172a;" id="loadingTitle">Sending Mail Notification…</div>
        <div style="font-size:13px; color:#64748b; margin-top:6px; line-height:1.4;" id="loadingSubtitle">
            Processing budget request & dispatching email to team members. Please wait a moment.
        </div>
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill"></div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
let selectedDatesArray = [];
let flatpickrInstance = null;

document.addEventListener("DOMContentLoaded", function() {
    flatpickrInstance = flatpickr("#flatpickr_dates_input", {
        mode: "multiple",
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function(selectedDates, dateStr, instance) {
            selectedDatesArray = selectedDates.map(d => instance.formatDate(d, "Y-m-d"));
            renderDateTags();
        }
    });

    // Auto-handle POST form submissions to show progress bar and disable double submit
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (this.id === 'raiseForm') {
                return; // Ignore Step 1 raise form; progress bar shows only on Confirm submit
            }

            const method = (this.getAttribute('method') || '').toUpperCase();
            if (method !== 'POST') {
                return;
            }

            const submitBtns = this.querySelectorAll('button[type="submit"]');
            submitBtns.forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.style.cursor = 'not-allowed';
            });

            document.getElementById('globalLoadingOverlay').classList.add('active');
        });
    });
});

function renderDateTags() {
    const container = document.getElementById('dateTagsList');
    if (selectedDatesArray.length === 0) {
        container.innerHTML = '<span style="font-size:12px; color:#94a3b8;">No dates selected yet. Pick dates from the picker above.</span>';
        return;
    }

    container.innerHTML = selectedDatesArray.map(date => `
        <span class="date-tag">
            📅 ${date}
            <span class="date-tag-remove" onclick="removeDate('${date}')">×</span>
        </span>
    `).join('');
}

function removeDate(dateStr) {
    selectedDatesArray = selectedDatesArray.filter(d => d !== dateStr);
    if (flatpickrInstance) {
        flatpickrInstance.setDate(selectedDatesArray, true);
    }
    renderDateTags();
}

function openRaiseModal() {
    document.getElementById('raiseModal').classList.add('active');
}
function closeRaiseModal() {
    document.getElementById('raiseModal').classList.remove('active');
}

function proceedToConfirm() {
    const accSelect = document.getElementById('ad_account_id');
    const accId = accSelect.value;
    const amount = document.getElementById('amount').value;
    const remarks = document.getElementById('remarks').value.trim();

    if (!accId) {
        alert("Please select an Ad Account.");
        return;
    }
    if (!amount || parseFloat(amount) <= 0) {
        alert("Please enter a valid amount.");
        return;
    }
    if (selectedDatesArray.length === 0) {
        alert("Please select at least one date.");
        return;
    }

    const selectedOption = accSelect.options[accSelect.selectedIndex];
    const accName = selectedOption.getAttribute('data-name') + ' (' + selectedOption.getAttribute('data-platform') + ')';

    document.getElementById('hidden_ad_account_id').value = accId;
    document.getElementById('hidden_amount').value = amount;
    document.getElementById('hidden_selected_dates').value = selectedDatesArray.join(',');
    document.getElementById('hidden_remarks').value = remarks;

    document.getElementById('summary_ad_account').innerText = accName;
    document.getElementById('summary_amount').innerText = '₹' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('summary_dates').innerHTML = selectedDatesArray.map(d => `<span class="date-tag" style="margin:2px;">📅 ${d}</span>`).join(' ');
    document.getElementById('summary_remarks').innerText = remarks || 'None';

    closeRaiseModal();
    document.getElementById('confirmModal').classList.add('active');
}

function backToEdit() {
    document.getElementById('confirmModal').classList.remove('active');
    document.getElementById('raiseModal').classList.add('active');
}
function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
}

function openAccountsApproveModal(item) {
    document.getElementById('accApproveForm').action = '/accounts/ad-budget/' + item.id + '/accounts-approve';
    document.getElementById('acc_modal_req_id').innerText = '#' + item.id;
    document.getElementById('acc_ad_account_id').value = item.ad_account_id || '';
    document.getElementById('acc_approved_amount').value = item.amount || '';
    document.getElementById('accApproveModal').classList.add('active');
}
function closeAccApproveModal() {
    document.getElementById('accApproveModal').classList.remove('active');
}

function openViewDetailsModal(item) {
    document.getElementById('view_req_id').innerText = '#' + item.id;
    document.getElementById('view_requester').innerText = item.requester ? item.requester.name : '—';
    document.getElementById('view_req_amount').innerText = '₹' + parseFloat(item.amount).toLocaleString('en-IN', {minimumFractionDigits: 2});
    document.getElementById('view_dates').innerHTML = (item.selected_dates || []).map(d => `<span class="date-tag" style="margin:2px;">📅 ${d}</span>`).join(' ') || '—';
    document.getElementById('view_status').innerText = item.status.toUpperCase();
    document.getElementById('view_exec_remarks').innerText = item.remarks || 'None';

    document.getElementById('view_tl_status').innerText = (item.status === 'tl_pending') ? 'Pending DM TL Approval' : 'Approved by DM TL';
    document.getElementById('view_tl_by').innerText = item.tl_approver ? (item.tl_approver.name + ' (' + (item.tl_approved_at || '') + ')') : '—';

    document.getElementById('view_acc_by').innerText = item.approver ? (item.approver.name + ' (' + (item.approver.email || '') + ')') : '—';
    document.getElementById('view_payment_date').innerText = item.payment_date || 'Not set yet';
    document.getElementById('view_ad_account').innerText = item.ad_account ? (item.ad_account.account_name + ' (' + item.ad_account.platform + ')') : '—';
    document.getElementById('summary_approved_amount').innerText = item.approved_amount ? ('₹' + parseFloat(item.approved_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})) : 'Pending Accounts Approval';
    document.getElementById('view_acc_remarks').innerText = item.accounts_remarks || 'None';

    const attachmentsContainer = document.getElementById('view_attachments_list');
    if (item.attachments && item.attachments.length > 0) {
        attachmentsContainer.innerHTML = item.attachments.map(att => `
            <a href="${att.url}" target="_blank" class="file-chip">
                📎 ${att.name || 'Attachment'}
            </a>
        `).join('');
    } else {
        attachmentsContainer.innerHTML = '<span style="color:#94a3b8; font-size:12px;">No attachments uploaded yet.</span>';
    }

    document.getElementById('viewDetailsModal').classList.add('active');
}
function closeViewDetailsModal() {
    document.getElementById('viewDetailsModal').classList.remove('active');
}
</script>
@endpush
@endsection
