@extends('layouts.app')

@section('title', ($lead->company_name ?: $lead->contact_name) . ' - Campaigns')

@push('styles')
<style>
.cmp-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.cmp-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.cmp-title { font-size:22px; font-weight:900; color:#111827; }
.cmp-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.cmp-body { padding:22px 28px 34px; display:grid; gap:20px; }

/* Customer Banner Card */
.cmp-hero { background:#fff; border:1px solid #eee7df; border-radius:14px; padding:22px 26px; box-shadow:0 10px 30px rgba(15,23,42,.04); display:flex; justify-content:space-between; align-items:flex-start; gap:20px; flex-wrap:wrap; }
.cmp-hero-main { display:flex; gap:16px; align-items:center; }
.cmp-hero-avatar { width:52px; height:52px; border-radius:12px; background:linear-gradient(135deg, #ea580c, #fb923c); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:22px; }
.cmp-hero-title { font-size:18px; font-weight:900; color:#0f172a; margin-bottom:4px; }
.cmp-hero-meta { display:flex; flex-wrap:wrap; gap:14px; font-size:12px; color:#64748b; }
.cmp-hero-pills { display:flex; gap:12px; align-items:center; }

.cmp-info-pill { padding:8px 16px; border-radius:10px; background:#f8fafc; border:1px solid #e2e8f0; display:flex; flex-direction:column; gap:2px; }
.cmp-info-pill-label { font-size:10px; font-weight:800; text-transform:uppercase; color:#94a3b8; letter-spacing:.05em; }
.cmp-info-pill-val { font-size:14px; font-weight:900; color:#0f172a; }

/* Stats Mini Grid */
.cmp-stats-mini { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:14px; }
.cmp-stat-mini { background:#fff; border:1px solid #eee7df; border-radius:12px; padding:16px 20px; box-shadow:0 4px 12px rgba(15,23,42,.03); }
.cmp-stat-mini-label { font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.cmp-stat-mini-val { font-size:24px; font-weight:900; color:#0f172a; margin-top:4px; }

/* Table & Actions */
.cmp-card { background:#fff; border:1px solid #eee7df; border-radius:14px; box-shadow:0 10px 30px rgba(15,23,42,.04); }
.cmp-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 24px; border-bottom:1px solid #f2ede8; background:#fffdfb; }
.cmp-card-title { font-size:16px; font-weight:900; color:#111827; }

.cmp-btn { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:32px; padding:5px 12px; border-radius:8px; border:1px solid #d7dce2; background:#fff; color:#111827; text-decoration:none; font-size:12px; font-weight:700; cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.cmp-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.cmp-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }
.cmp-btn-sm { min-height:28px; padding:3px 9px; font-size:11.5px; }

.cmp-btn-view { background:#f0f9ff; border-color:#bae6fd; color:#0369a1; }
.cmp-btn-view:hover { background:#e0f2fe; color:#0284c7; }
.cmp-btn-pause { background:#fffbeb; border-color:#fde68a; color:#b45309; }
.cmp-btn-pause:hover { background:#fef3c7; color:#92400e; }
.cmp-btn-resume { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
.cmp-btn-resume:hover { background:#d1fae5; color:#047857; }
.cmp-btn-extend { background:#f5f3ff; border-color:#ddd6fe; color:#6d28d9; }
.cmp-btn-extend:hover { background:#ede9fe; color:#5b21b6; }
.cmp-btn-stop { background:#fff1f2; border-color:#fecdd3; color:#be123c; }
.cmp-btn-stop:hover { background:#ffe4e6; color:#9f1239; }

.cmp-table-wrap { overflow-x:auto; min-height:220px; }
.cmp-table { width:100%; border-collapse:collapse; min-width:980px; }
.cmp-table th { padding:14px 16px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.cmp-table td { padding:14px 16px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:middle; }
.cmp-table tbody tr:hover td { background:#fffaf5; }

/* Status Badges */
.cmp-status-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; }
.cmp-status--active { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.cmp-status--paused { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.cmp-status--completed { background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; }
.cmp-status--expired { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }
.cmp-status--stopped { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
.cmp-status--inactive { background:#f1f5f9; color:#64748b; border:1px solid #cbd5e1; }

.cmp-pause-tag { display:inline-flex; flex-direction:column; gap:2px; margin-top:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.cmp-pause-history-tag { display:inline-flex; flex-direction:column; gap:1px; margin-top:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#f8fafc; color:#334155; border:1px solid #e2e8f0; }
.cmp-extended-badge { display:inline-flex; align-items:center; gap:4px; margin-top:3px; padding:2px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#f5f3ff; color:#6d28d9; border:1px solid #ddd6fe; }
.cmp-stopped-badge { display:inline-flex; flex-direction:column; gap:2px; margin-top:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }

.cmp-platform-tag { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; background:#f1f5f9; color:#334155; }

.cmp-btn-danger { background:#ef4444; border-color:#ef4444; color:#fff; }
.cmp-btn-danger:hover { background:#dc2626; border-color:#dc2626; color:#fff; }
.cmp-btn-icon-danger { color:#ef4444; border-color:#fee2e2; background:#fff; }
.cmp-btn-icon-danger:hover { background:#fef2f2; border-color:#fca5a5; color:#dc2626; }

/* Modal Styles */
.cmp-modal-overlay { position:fixed; inset:0; background:rgba(15,23,42,0.6); backdrop-filter:blur(3px); z-index:1050; display:none; align-items:center; justify-content:center; padding:20px; }
.cmp-modal-overlay.is-active { display:flex; }
.cmp-modal-box { background:#fff; border-radius:16px; width:100%; max-width:600px; max-height:90vh; overflow-y:auto; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); animation:cmpModalIn 0.2s cubic-bezier(0.16,1,0.3,1); }
.cmp-modal-box--sm { max-width:460px; }
.cmp-modal-box--md { max-width:560px; }
.cmp-modal-box--lg { max-width:720px; }
@keyframes cmpModalIn { from { opacity:0; transform:scale(0.96) translateY(10px); } to { opacity:1; transform:scale(1) translateY(0); } }
.cmp-modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 24px; border-bottom:1px solid #f1f5f9; }
.cmp-modal-title { font-size:17px; font-weight:900; color:#0f172a; }
.cmp-modal-close { background:transparent; border:none; color:#94a3b8; font-size:20px; cursor:pointer; }
.cmp-modal-close:hover { color:#0f172a; }
.cmp-modal-body { padding:20px 24px; display:grid; gap:16px; }
.cmp-modal-footer { padding:16px 24px; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:10px; background:#fafaf9; border-radius:0 0 16px 16px; }

/* History View Modal Tabs */
.cmp-tabs-nav { display:flex; gap:6px; border-bottom:1px solid #e2e8f0; padding:0 24px; background:#fafaf9; overflow-x:auto; }
.cmp-tab-link { padding:12px 14px; font-size:12px; font-weight:800; color:#64748b; border:none; background:transparent; border-bottom:2px solid transparent; cursor:pointer; white-space:nowrap; transition:all .15s; }
.cmp-tab-link:hover { color:#0f172a; }
.cmp-tab-link.is-active { color:#ea580c; border-bottom-color:#ea580c; background:#fff; }
.cmp-tab-pane { display:none; }
.cmp-tab-pane.is-active { display:grid; gap:16px; }

/* Timeline UI in Modal */
.cmp-timeline-item { position:relative; padding-left:22px; padding-bottom:14px; border-left:2px solid #e2e8f0; }
.cmp-timeline-item:last-child { border-left-color:transparent; padding-bottom:0; }
.cmp-timeline-dot { position:absolute; left:-7px; top:2px; width:12px; height:12px; border-radius:50%; background:#ea580c; border:2px solid #fff; box-shadow:0 0 0 2px #fdba74; }
.cmp-timeline-title { font-size:13px; font-weight:800; color:#0f172a; }
.cmp-timeline-meta { font-size:11.5px; color:#64748b; margin-top:2px; }

/* Delete Confirmation Modal Specific Styles */
.cmp-delete-body { padding:28px 24px 20px; text-align:center; display:flex; flex-direction:column; align-items:center; }
.cmp-delete-icon-wrap { margin-bottom:16px; }
.cmp-delete-icon { width:58px; height:58px; border-radius:50%; background:#fef2f2; border:1px solid #fee2e2; display:flex; align-items:center; justify-content:center; box-shadow:0 0 0 8px rgba(254,226,226,0.45); }
.cmp-delete-title { font-size:19px; font-weight:900; color:#0f172a; margin-bottom:8px; }
.cmp-delete-desc { font-size:13px; color:#64748b; line-height:1.6; margin-bottom:16px; }
.cmp-delete-target-pill { display:inline-block; font-weight:800; color:#0f172a; background:#f1f5f9; padding:2px 8px; border-radius:6px; margin:0 2px; }
.cmp-delete-warning-box { background:#fffbeb; border:1px solid #fef3c7; border-radius:10px; padding:10px 14px; font-size:12px; color:#92400e; display:flex; align-items:center; gap:8px; text-align:left; line-height:1.4; width:100%; box-sizing:border-box; }
.cmp-delete-footer { padding:16px 24px; background:#fafaf9; border-top:1px solid #f1f5f9; display:flex; gap:12px; border-radius:0 0 16px 16px; }

.cmp-form-group { display:flex; flex-direction:column; gap:5px; }
.cmp-form-label { font-size:12px; font-weight:700; color:#334155; }
.cmp-form-label span { color:#ef4444; }
.cmp-form-input, .cmp-form-select, .cmp-form-textarea { width:100%; padding:8px 12px; border-radius:8px; border:1px solid #cbd5e1; font-size:13px; color:#0f172a; outline:none; transition:border-color .15s; }
.cmp-form-input:focus, .cmp-form-select:focus, .cmp-form-textarea:focus { border-color:#ea580c; }
.cmp-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

.cmp-notice-box { padding:12px 14px; border-radius:10px; font-size:12px; line-height:1.5; }
.cmp-notice-box--warning { background:#fffbeb; border:1px solid #fef3c7; color:#92400e; }
.cmp-notice-box--info { background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1; }
.cmp-notice-box--danger { background:#fff1f2; border:1px solid #fecdd3; color:#9f1239; }
.cmp-notice-box--success { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }

@media (max-width: 900px) {
    .cmp-stats-mini { grid-template-columns:repeat(2,1fr); }
    .cmp-form-grid { grid-template-columns:1fr; }
}

/* Action Dropdown Menu */
.cmp-dropdown { position:relative; display:inline-block; text-align:left; }
.cmp-dropdown-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:30px; padding:5px 12px; border-radius:8px; border:1px solid #cbd5e1; background:#ffffff; color:#1e293b; font-size:12px; font-weight:700; cursor:pointer; transition:all .15s ease; box-shadow:0 1px 2px rgba(15,23,42,.04); }
.cmp-dropdown-btn:hover { background:#f8fafc; border-color:#94a3b8; color:#0f172a; }
.cmp-dropdown.is-open .cmp-dropdown-btn { background:#fff7ed; border-color:#ea580c; color:#ea580c; box-shadow:0 0 0 2px rgba(234,88,12,.15); }
.cmp-dropdown-menu { position:absolute; right:0; top:calc(100% + 4px); z-index:100; min-width:190px; background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:6px; box-shadow:0 10px 25px -5px rgba(15,23,42,.15), 0 8px 10px -6px rgba(15,23,42,.1); display:none; flex-direction:column; gap:2px; animation:cmpDropIn 0.15s cubic-bezier(0.16,1,0.3,1); }
.cmp-dropdown.is-open .cmp-dropdown-menu { display:flex; }
@keyframes cmpDropIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
.cmp-dropdown-item { display:flex; align-items:center; gap:9px; width:100%; padding:8px 10px; border-radius:6px; border:none; background:transparent; color:#334155; font-size:12px; font-weight:600; text-align:left; cursor:pointer; transition:background .12s ease, color .12s ease; white-space:nowrap; }
.cmp-dropdown-item:hover { background:#f1f5f9; color:#0f172a; }
.cmp-dropdown-icon { font-size:13px; width:18px; text-align:center; display:inline-flex; align-items:center; justify-content:center; }
.cmp-dropdown-item--view:hover { background:#f0f9ff; color:#0284c7; }
.cmp-dropdown-item--pause:hover { background:#fffbeb; color:#b45309; }
.cmp-dropdown-item--resume:hover { background:#ecfdf5; color:#059669; }
.cmp-dropdown-item--stop:hover { background:#fff1f2; color:#be123c; }
.cmp-dropdown-item--extend:hover { background:#f5f3ff; color:#7c3aed; }
.cmp-dropdown-item--danger { color:#dc2626; }
.cmp-dropdown-item--danger:hover { background:#fef2f2; color:#b91c1c; }
.cmp-dropdown-divider { height:1px; background:#f1f5f9; margin:4px 0; }
</style>
@endpush

@section('content')
<div class="cmp-page">
    <div class="cmp-topbar">
        <div>
            <div class="cmp-title">{{ $lead->company_name ?: ($lead->contact_name ?: 'Customer Account') }}</div>
            <div class="cmp-breadcrumb">
                <a href="{{ route('projects.campaigns.index') }}" style="color:#7c7c7c; text-decoration:none;">Campaigns</a>
                &gt; {{ $lead->company_name ?: $lead->contact_name }}
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('projects.campaigns.index') }}" class="cmp-btn">
                &larr; Back to Leads
            </a>
            <button type="button" class="cmp-btn cmp-btn-primary" onclick="openCreateModal()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Create Campaign
            </button>
        </div>
    </div>

    <div class="cmp-body">
        @if(session('success'))
            <div style="padding:12px 18px; border-radius:10px; background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; font-size:13px; font-weight:700;">
                ✓ {{ session('success') }}
            </div>
        @endif

        {{-- Customer Overview Banner --}}
        <section class="cmp-hero">
            <div class="cmp-hero-main">
                <div class="cmp-hero-avatar">
                    {{ strtoupper(substr($lead->company_name ?: ($lead->contact_name ?: 'C'), 0, 1)) }}
                </div>
                <div>
                    <div class="cmp-hero-title">{{ $lead->company_name ?: 'No Company Name' }}</div>
                    <div class="cmp-hero-meta">
                        <span>👤 <strong>Contact:</strong> {{ $lead->contact_name ?: '—' }}</span>
                        @if($lead->mobile_number)
                            <span>📞 <strong>Mobile:</strong> {{ $lead->mobile_number }}</span>
                        @endif
                        @if($lead->email)
                            <span>✉️ <strong>Email:</strong> {{ $lead->email }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="cmp-hero-pills">
                <div class="cmp-info-pill">
                    <span class="cmp-info-pill-label">Allocated Team</span>
                    <span class="cmp-info-pill-val" style="font-size:13px;">
                        @if($allocatedUsers && $allocatedUsers->isNotEmpty())
                            {{ $allocatedUsers->pluck('name')->implode(', ') }}
                        @else
                            <span style="color:#94a3b8; font-weight:500;">Pending</span>
                        @endif
                    </span>
                </div>

                @php
                    $latestBudget = $dmInitiations->pluck('lead_budget_amount')->filter(fn($b) => (float)$b > 0)->first();
                    $latestBudgetType = $dmInitiations->pluck('budget_amount_type')->filter()->first() ?? 'Standard';
                @endphp
                @if($latestBudget)
                    <div class="cmp-info-pill" style="background:#fff7ed; border-color:#fed7aa;">
                        <span class="cmp-info-pill-label" style="color:#ea580c;">Approved Ad Budget</span>
                        <span class="cmp-info-pill-val" style="color:#c2410c;">
                            ₹{{ number_format((float) $latestBudget, 2) }}
                            <span style="font-size:11px; font-weight:600; color:#9a3412;">({{ $latestBudgetType }})</span>
                        </span>
                    </div>
                @endif
            </div>
        </section>

        {{-- Mini Stats --}}
        <section class="cmp-stats-mini">
            <div class="cmp-stat-mini">
                <div class="cmp-stat-mini-label">Total Campaigns</div>
                <div class="cmp-stat-mini-val">{{ $stats['total'] }}</div>
            </div>
            <div class="cmp-stat-mini">
                <div class="cmp-stat-mini-label" style="color:#059669;">Active Campaigns</div>
                <div class="cmp-stat-mini-val" style="color:#059669;">{{ $stats['active'] }}</div>
            </div>
            <div class="cmp-stat-mini">
                <div class="cmp-stat-mini-label" style="color:#b45309;">Paused Campaigns</div>
                <div class="cmp-stat-mini-val" style="color:#b45309;">{{ $stats['paused'] }}</div>
            </div>
            <div class="cmp-stat-mini">
                <div class="cmp-stat-mini-label" style="color:#dc2626;">Campaign Expired</div>
                <div class="cmp-stat-mini-val" style="color:#dc2626;">{{ $stats['expired'] }}</div>
            </div>
            <div class="cmp-stat-mini">
                <div class="cmp-stat-mini-label" style="color:#be123c;">Stopped Campaigns</div>
                <div class="cmp-stat-mini-val" style="color:#be123c;">{{ $stats['stopped'] ?? 0 }}</div>
            </div>
        </section>

        {{-- Campaigns List Table --}}
        <section class="cmp-card">
            <div class="cmp-card-head">
                <div class="cmp-card-title">Customer Campaigns List</div>
                <button type="button" class="cmp-btn cmp-btn-primary cmp-btn-sm" onclick="openCreateModal()">
                    + Add Campaign
                </button>
            </div>

            <div class="cmp-table-wrap">
                <table class="cmp-table">
                    <thead>
                        <tr>
                            <th style="width: 26%;">Campaign Name & Details</th>
                            <th style="width: 12%;">Platform</th>
                            <th style="width: 16%;">Status & Duration</th>
                            <th style="width: 13%;">Budget</th>
                            <th style="width: 15%;">Schedule / Dates</th>
                            <th style="width: 18%; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $camp)
                            <tr>
                                <td>
                                    <div style="font-weight:800; color:#0f172a; font-size:13.5px;">{{ $camp->campaign_name }}</div>
                                    
                                    @if($camp->extendedFrom)
                                        <div class="cmp-extended-badge" title="Latest extended version in this campaign chain. Click 'Actions -> View Full History' to see all previous versions.">
                                            🔄 Latest Extension (from: <strong>{{ $camp->extendedFrom->campaign_name }}</strong>)
                                        </div>
                                    @endif

                                    @if($camp->ad_account_name)
                                        <div style="font-size:11px; color:#475569; margin-top:3px;">
                                            💼 <strong>Ad Account:</strong> {{ $camp->ad_account_name }}
                                        </div>
                                    @endif

                                    @if($camp->remarks)
                                        <div style="font-size:11px; color:#94a3b8; margin-top:2px;">💬 {{ Str::limit($camp->remarks, 50) }}</div>
                                    @endif

                                    @if($camp->status === 'stopped' && $camp->stop_reason)
                                        <div style="font-size:11px; color:#be123c; margin-top:2px;">🛑 <strong>Stop Reason:</strong> {{ Str::limit($camp->stop_reason, 45) }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($camp->platform)
                                        <span class="cmp-platform-tag">
                                            @if(str_contains(strtolower($camp->platform), 'facebook') || str_contains(strtolower($camp->platform), 'meta'))
                                                📘 {{ $camp->platform }}
                                            @elseif(str_contains(strtolower($camp->platform), 'google'))
                                                🌐 {{ $camp->platform }}
                                            @elseif(str_contains(strtolower($camp->platform), 'instagram'))
                                                📷 {{ $camp->platform }}
                                            @elseif(str_contains(strtolower($camp->platform), 'linkedin'))
                                                💼 {{ $camp->platform }}
                                            @else
                                                📢 {{ $camp->platform }}
                                            @endif
                                        </span>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($camp->status === 'stopped')
                                        <span class="cmp-status-pill cmp-status--stopped">
                                            🛑 Stopped
                                        </span>
                                        <div class="cmp-stopped-badge">
                                            <span>⏹️ Stopped: {{ $camp->stop_date ? $camp->stop_date->format('d M Y') : ($camp->stopped_at ? $camp->stopped_at->format('d M Y') : '—') }}</span>
                                            <span style="font-size:10px; font-weight:600; color:#9f1239;">(Ran for {{ $camp->calculateRunDays() }} {{ Str::plural('day', $camp->calculateRunDays()) }})</span>
                                            @if($camp->refund_amount && (float)$camp->refund_amount > 0)
                                                <span style="font-size:10px; font-weight:800; color:#15803d;">💸 Refund: ₹{{ number_format((float)$camp->refund_amount, 2) }}</span>
                                            @endif
                                        </div>
                                    @elseif($camp->isExpired() || $camp->status === 'expired')
                                        <span class="cmp-status-pill cmp-status--expired">
                                            ⏱️ Expired
                                        </span>
                                    @elseif($camp->status === 'active')
                                        <span class="cmp-status-pill cmp-status--active">
                                            ● Active
                                        </span>
                                    @elseif($camp->status === 'paused')
                                        <span class="cmp-status-pill cmp-status--paused">
                                            ❚❚ Paused
                                        </span>
                                    @else
                                        <span class="cmp-status-pill cmp-status--inactive">
                                            {{ ucfirst($camp->status) }}
                                        </span>
                                    @endif

                                    {{-- Pause Info --}}
                                    @if($camp->status === 'paused' && $camp->paused_at)
                                        <div class="cmp-pause-tag" title="Paused on {{ $camp->paused_at->format('d M Y') }}">
                                            <span>⏸️ Paused: {{ $camp->paused_at->format('d M Y') }}</span>
                                            <span style="font-weight:600; color:#92400e; font-size:10px;">({{ $camp->currentPausedDays() }} {{ Str::plural('day', $camp->currentPausedDays()) }} ago)</span>
                                        </div>
                                    @elseif($camp->total_paused_days > 0 || !empty($camp->pause_history))
                                        @php
                                            $lastPause = collect($camp->pause_history)->last();
                                        @endphp
                                        <div class="cmp-pause-history-tag" title="Total Paused Duration: {{ $camp->total_paused_days }} Days">
                                            <span>⏱️ Total Paused: {{ $camp->total_paused_days }} {{ Str::plural('day', $camp->total_paused_days) }}</span>
                                            @if($lastPause && !empty($lastPause['paused_date']))
                                                <span style="font-size:10px; color:#64748b; font-weight:500;">
                                                    ({{ $lastPause['paused_date'] }} - {{ $lastPause['resumed_date'] }})
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($camp->budget_amount)
                                        <div style="font-weight:800; color:#0f172a;">
                                            ₹{{ number_format((float) $camp->budget_amount, 2) }}
                                        </div>
                                        <div style="font-size:11px; color:#64748b;">
                                            {{ $camp->budget_type ?: 'Monthly' }} (₹{{ number_format($camp->calculateDailyBudget(), 2) }}/d)
                                        </div>
                                    @else
                                        <span style="color:#94a3b8;">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($camp->start_date || $camp->end_date)
                                        <div style="font-size:12px; color:#334155; font-weight:600;">
                                            {{ $camp->start_date ? $camp->start_date->format('d M Y') : 'Start' }}
                                            →
                                            {{ $camp->end_date ? $camp->end_date->format('d M Y') : 'Ongoing' }}
                                        </div>
                                    @else
                                        <span style="color:#94a3b8; font-size:12px;">Not scheduled</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    <div class="cmp-dropdown" data-dropdown-id="{{ $camp->id }}">
                                        <button type="button" class="cmp-dropdown-btn" onclick="toggleActionDropdown(event, {{ $camp->id }})" title="Campaign Actions">
                                            <span>Actions</span>
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M6 9l6 6 6-6"></path>
                                            </svg>
                                        </button>
                                        <div class="cmp-dropdown-menu" id="actionMenu-{{ $camp->id }}">
                                            {{-- 1. View Full Details & History --}}
                                            <button
                                                type="button"
                                                class="cmp-dropdown-item cmp-dropdown-item--view"
                                                onclick='closeAllActionDropdowns(); openViewHistoryModal(@json($camp), {{ $camp->calculateRunDays() }}, {{ $camp->calculateDailyBudget() }})'
                                            >
                                                <span class="cmp-dropdown-icon">👁️</span>
                                                <span>View Full History</span>
                                            </button>

                                            {{-- 2. Pause / Resume Action --}}
                                            @if($camp->status === 'active')
                                                <button
                                                    type="button"
                                                    class="cmp-dropdown-item cmp-dropdown-item--pause"
                                                    onclick='closeAllActionDropdowns(); openPauseModal(@json($camp))'
                                                >
                                                    <span class="cmp-dropdown-icon">❚❚</span>
                                                    <span>Pause Campaign</span>
                                                </button>
                                            @elseif($camp->status === 'paused')
                                                <button
                                                    type="button"
                                                    class="cmp-dropdown-item cmp-dropdown-item--resume"
                                                    onclick='closeAllActionDropdowns(); openResumeModal(@json($camp))'
                                                >
                                                    <span class="cmp-dropdown-icon">▶</span>
                                                    <span>Resume Campaign</span>
                                                </button>
                                            @endif

                                            {{-- 3. Stop Campaign Action --}}
                                            @if($camp->status !== 'stopped')
                                                <button
                                                    type="button"
                                                    class="cmp-dropdown-item cmp-dropdown-item--stop"
                                                    onclick='closeAllActionDropdowns(); openStopModal(@json($camp), {{ $camp->calculateRunDays() }}, {{ $camp->calculateDailyBudget() }})'
                                                >
                                                    <span class="cmp-dropdown-icon">🛑</span>
                                                    <span>Stop & Refund Notice</span>
                                                </button>
                                            @endif

                                            {{-- 4. Extend / Renew Campaign Action --}}
                                            <button
                                                type="button"
                                                class="cmp-dropdown-item cmp-dropdown-item--extend"
                                                onclick='closeAllActionDropdowns(); openExtendModal(@json($camp))'
                                            >
                                                <span class="cmp-dropdown-icon">🔄</span>
                                                <span>Extend / Renew</span>
                                            </button>

                                            <div class="cmp-dropdown-divider"></div>

                                            {{-- 5. Edit Button --}}
                                            <button
                                                type="button"
                                                class="cmp-dropdown-item"
                                                onclick='closeAllActionDropdowns(); openEditModal(@json($camp))'
                                            >
                                                <span class="cmp-dropdown-icon">✏️</span>
                                                <span>Edit Campaign</span>
                                            </button>

                                            {{-- 6. Delete Button --}}
                                            <button
                                                type="button"
                                                class="cmp-dropdown-item cmp-dropdown-item--danger"
                                                onclick="closeAllActionDropdowns(); openDeleteModal({{ $camp->id }}, @js($camp->campaign_name))"
                                            >
                                                <span class="cmp-dropdown-icon">🗑️</span>
                                                <span>Delete Campaign</span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px 20px; color: #64748b;">
                                    <div style="font-size: 28px; margin-bottom: 6px;">📢</div>
                                    <div style="font-weight: 700; color: #1e293b;">No Campaigns Created Yet</div>
                                    <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                                        Click "+ Add Campaign" to create the first campaign for this customer.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

{{-- 1. View Campaign History & Full Details Modal --}}
<div class="cmp-modal-overlay" id="viewCampaignHistoryModal">
    <div class="cmp-modal-box cmp-modal-box--lg">
        <div class="cmp-modal-header">
            <div>
                <div class="cmp-modal-title" id="viewModalCampaignTitle">Campaign Details & History</div>
                <div style="font-size:12px; color:#64748b; margin-top:2px;" id="viewModalCampaignSubtitle">Comprehensive logs of extensions, pauses, and stops</div>
            </div>
            <button type="button" class="cmp-modal-close" onclick="closeViewHistoryModal()">&times;</button>
        </div>

        {{-- Nav Tabs --}}
        <div class="cmp-tabs-nav">
            <button type="button" class="cmp-tab-link is-active" onclick="switchViewTab('tabOverview')">📋 Overview</button>
            <button type="button" class="cmp-tab-link" onclick="switchViewTab('tabExtensions')">🔄 Extension / Renewal History (<span id="viewExtBadge">0</span>)</button>
            <button type="button" class="cmp-tab-link" onclick="switchViewTab('tabPauses')">⏸️ Pause & Resume Logs (<span id="viewPauseBadge">0</span>)</button>
            <button type="button" class="cmp-tab-link" onclick="switchViewTab('tabStop')">🛑 Stop & Refund</button>
        </div>

        <div class="cmp-modal-body">
            {{-- Tab 1: Overview --}}
            <div class="cmp-tab-pane is-active" id="tabOverview">
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:12px;">
                    <div class="cmp-info-pill">
                        <span class="cmp-info-pill-label">Ad Account Name</span>
                        <span class="cmp-info-pill-val" id="viewAdAccountVal" style="font-size:13px; color:#2563eb;">—</span>
                    </div>
                    <div class="cmp-info-pill">
                        <span class="cmp-info-pill-label">Platform</span>
                        <span class="cmp-info-pill-val" id="viewPlatformVal" style="font-size:13px;">—</span>
                    </div>
                    <div class="cmp-info-pill">
                        <span class="cmp-info-pill-label">Current Status</span>
                        <span class="cmp-info-pill-val" id="viewStatusVal" style="font-size:13px;">—</span>
                    </div>
                    <div class="cmp-info-pill">
                        <span class="cmp-info-pill-label">Campaign Budget</span>
                        <span class="cmp-info-pill-val" id="viewBudgetVal" style="font-size:13px; color:#0f172a;">—</span>
                    </div>
                    <div class="cmp-info-pill">
                        <span class="cmp-info-pill-label">Daily Budget</span>
                        <span class="cmp-info-pill-val" id="viewDailyBudgetVal" style="font-size:13px; color:#ea580c;">—</span>
                    </div>
                    <div class="cmp-info-pill">
                        <span class="cmp-info-pill-label">Active Days Run</span>
                        <span class="cmp-info-pill-val" id="viewRunDaysVal" style="font-size:13px; color:#166534;">—</span>
                    </div>
                </div>

                <div style="padding:14px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
                    <div style="font-weight:800; color:#334155; margin-bottom:4px;">📅 Schedule & Duration:</div>
                    <div id="viewScheduleVal" style="color:#0f172a; font-weight:600;">—</div>
                </div>

                <div style="padding:14px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; font-size:12.5px;">
                    <div style="font-weight:800; color:#334155; margin-bottom:4px;">💬 Remarks / Instructions:</div>
                    <div id="viewRemarksVal" style="color:#475569; white-space:pre-line;">None</div>
                </div>
            </div>

            {{-- Tab 2: Extension History --}}
            <div class="cmp-tab-pane" id="tabExtensions">
                <div id="viewExtensionParentBox"></div>
                <div style="font-size:13px; font-weight:800; color:#0f172a; margin-top:6px;">Subsequent Renewals & Extensions:</div>
                <div id="viewExtensionChildrenList" style="display:grid; gap:10px;"></div>
            </div>

            {{-- Tab 3: Pause & Resume Logs --}}
            <div class="cmp-tab-pane" id="tabPauses">
                <div id="viewCurrentPauseAlert"></div>
                <div style="font-size:13px; font-weight:800; color:#0f172a; margin-top:4px;">Pause & Resume History Logs:</div>
                <div id="viewPauseTimelineList" style="display:grid; gap:10px;"></div>
            </div>

            {{-- Tab 4: Stop & Refund --}}
            <div class="cmp-tab-pane" id="tabStop">
                <div id="viewStopDetailsBox"></div>
            </div>
        </div>

        <div class="cmp-modal-footer">
            <button type="button" class="cmp-btn cmp-btn-primary" onclick="closeViewHistoryModal()">Close</button>
        </div>
    </div>
</div>

{{-- 2. Create Campaign Modal --}}
<div class="cmp-modal-overlay" id="createCampaignModal">
    <div class="cmp-modal-box">
        <div class="cmp-modal-header">
            <div class="cmp-modal-title">Create Customer Campaign</div>
            <button type="button" class="cmp-modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" action="{{ route('projects.campaigns.store', $lead->id) }}">
            @csrf
            <div class="cmp-modal-body">
                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Campaign Name <span>*</span></label>
                        <input type="text" name="campaign_name" required placeholder="e.g. Lead Gen FB Ads - Q3" class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Ad Account Name</label>
                        <input type="text" name="ad_account_name" placeholder="e.g. Meta Ads - Client Account" class="cmp-form-input">
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Platform</label>
                        <select name="platform" class="cmp-form-select">
                            <option value="Facebook / Meta">Facebook / Meta</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Google Ads">Google Ads</option>
                            <option value="YouTube Ads">YouTube Ads</option>
                            <option value="LinkedIn Ads">LinkedIn Ads</option>
                            <option value="SEO / SEM">SEO / SEM</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Status <span>*</span></label>
                        <select name="status" required class="cmp-form-select">
                            <option value="active" selected>Active</option>
                            <option value="paused">Paused</option>
                            <option value="expired">Expired</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Budget Amount (₹)</label>
                        <input type="number" step="0.01" min="0" name="budget_amount" placeholder="e.g. 5000" class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Budget Type</label>
                        <select name="budget_type" class="cmp-form-select">
                            <option value="Daily">Daily</option>
                            <option value="Monthly" selected>Monthly</option>
                            <option value="Total">Total</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Start Date</label>
                        <input type="date" name="start_date" class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">End Date</label>
                        <input type="date" name="end_date" class="cmp-form-input">
                    </div>
                </div>

                <div class="cmp-form-group">
                    <label class="cmp-form-label">Remarks / Notes</label>
                    <textarea name="remarks" rows="2" placeholder="Any special instructions or ad notes..." class="cmp-form-textarea"></textarea>
                </div>
            </div>
            <div class="cmp-modal-footer">
                <button type="button" class="cmp-btn" onclick="closeCreateModal()">Cancel</button>
                <button type="submit" class="cmp-btn cmp-btn-primary">Create Campaign</button>
            </div>
        </form>
    </div>
</div>

{{-- 3. Edit Campaign Modal --}}
<div class="cmp-modal-overlay" id="editCampaignModal">
    <div class="cmp-modal-box">
        <div class="cmp-modal-header">
            <div class="cmp-modal-title">Edit Customer Campaign</div>
            <button type="button" class="cmp-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" id="editCampaignForm" action="">
            @csrf
            @method('PUT')
            <div class="cmp-modal-body">
                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Campaign Name <span>*</span></label>
                        <input type="text" id="edit_campaign_name" name="campaign_name" required class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Ad Account Name</label>
                        <input type="text" id="edit_ad_account_name" name="ad_account_name" placeholder="e.g. Meta Ads - Client Account" class="cmp-form-input">
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Platform</label>
                        <select id="edit_platform" name="platform" class="cmp-form-select">
                            <option value="Facebook / Meta">Facebook / Meta</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Google Ads">Google Ads</option>
                            <option value="YouTube Ads">YouTube Ads</option>
                            <option value="LinkedIn Ads">LinkedIn Ads</option>
                            <option value="SEO / SEM">SEO / SEM</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Status <span>*</span></label>
                        <select id="edit_status" name="status" required class="cmp-form-select">
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="expired">Expired</option>
                            <option value="stopped">Stopped</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Budget Amount (₹)</label>
                        <input type="number" step="0.01" min="0" id="edit_budget_amount" name="budget_amount" class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Budget Type</label>
                        <select id="edit_budget_type" name="budget_type" class="cmp-form-select">
                            <option value="Daily">Daily</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Total">Total</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Start Date</label>
                        <input type="date" id="edit_start_date" name="start_date" class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">End Date</label>
                        <input type="date" id="edit_end_date" name="end_date" class="cmp-form-input">
                    </div>
                </div>

                <div class="cmp-form-group">
                    <label class="cmp-form-label">Remarks / Notes</label>
                    <textarea id="edit_remarks" name="remarks" rows="2" class="cmp-form-textarea"></textarea>
                </div>
            </div>
            <div class="cmp-modal-footer">
                <button type="button" class="cmp-btn" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="cmp-btn cmp-btn-primary">Update Campaign</button>
            </div>
        </form>
    </div>
</div>

{{-- 4. Pause Campaign Modal --}}
<div class="cmp-modal-overlay" id="pauseCampaignModal">
    <div class="cmp-modal-box cmp-modal-box--sm">
        <div class="cmp-modal-header">
            <div class="cmp-modal-title">⏸️ Pause Campaign</div>
            <button type="button" class="cmp-modal-close" onclick="closePauseModal()">&times;</button>
        </div>
        <form method="POST" id="pauseCampaignForm" action="">
            @csrf
            <div class="cmp-modal-body">
                <div class="cmp-notice-box cmp-notice-box--warning">
                    Are you sure you want to pause <strong id="pauseCampaignName">this campaign</strong>?
                </div>

                <div class="cmp-form-group">
                    <label class="cmp-form-label">Pause Date <span>*</span></label>
                    <input type="date" id="pause_date" name="pause_date" required class="cmp-form-input" value="{{ date('Y-m-d') }}">
                    <span style="font-size:11px; color:#64748b;">Specify the date when this campaign was or will be paused.</span>
                </div>

                <div class="cmp-form-group">
                    <label class="cmp-form-label">Reason / Remarks (Optional)</label>
                    <textarea name="remarks" rows="2" placeholder="e.g. Ad budget limit reached / Client requested temporary pause..." class="cmp-form-textarea"></textarea>
                </div>
            </div>
            <div class="cmp-modal-footer">
                <button type="button" class="cmp-btn" onclick="closePauseModal()">Cancel</button>
                <button type="submit" class="cmp-btn cmp-btn-pause" style="font-weight:800;">
                    Confirm & Pause Campaign
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 5. Resume Campaign Modal --}}
<div class="cmp-modal-overlay" id="resumeCampaignModal">
    <div class="cmp-modal-box cmp-modal-box--sm">
        <div class="cmp-modal-header">
            <div class="cmp-modal-title">▶️ Resume Campaign</div>
            <button type="button" class="cmp-modal-close" onclick="closeResumeModal()">&times;</button>
        </div>
        <form method="POST" id="resumeCampaignForm" action="">
            @csrf
            <div class="cmp-modal-body">
                <div class="cmp-notice-box cmp-notice-box--info">
                    Resuming campaign <strong id="resumeCampaignName">this campaign</strong>.
                    <div style="margin-top:4px; font-size:11px;" id="resumePausedDateNote">Paused Date: —</div>
                </div>

                <div class="cmp-form-group">
                    <label class="cmp-form-label">Resume / Activation Date <span>*</span></label>
                    <input type="date" id="resume_date" name="resume_date" required class="cmp-form-input" value="{{ date('Y-m-d') }}" onchange="calculateResumeDays()">
                    <span style="font-size:11px; color:#64748b;">Select the exact date of campaign resumption.</span>
                </div>

                <div id="resumeCalculatedPreview" style="padding:10px 12px; border-radius:8px; background:#f0fdf4; border:1px solid #bbf7d0; font-size:12px; color:#166534; font-weight:700;">
                    ⏱️ Calculated Pause Duration: <span id="resumeDiffDays">1 day</span>
                </div>

                <div class="cmp-form-group">
                    <label class="cmp-form-label">Remarks (Optional)</label>
                    <textarea name="remarks" rows="2" placeholder="e.g. Budget reloaded, ads resumed..." class="cmp-form-textarea"></textarea>
                </div>
            </div>
            <div class="cmp-modal-footer">
                <button type="button" class="cmp-btn" onclick="closeResumeModal()">Cancel</button>
                <button type="submit" class="cmp-btn cmp-btn-resume" style="font-weight:800;">
                    Confirm & Activate Campaign
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 6. Extend / Renew Campaign Modal --}}
<div class="cmp-modal-overlay" id="extendCampaignModal">
    <div class="cmp-modal-box">
        <div class="cmp-modal-header">
            <div class="cmp-modal-title">🔄 Extend / Renew Campaign</div>
            <button type="button" class="cmp-modal-close" onclick="closeExtendModal()">&times;</button>
        </div>
        <form method="POST" id="extendCampaignForm" action="">
            @csrf
            <div class="cmp-modal-body">
                <div class="cmp-notice-box cmp-notice-box--info">
                    Extending from original campaign: <strong id="extendParentName">Parent Campaign</strong>
                    <div style="font-size:11px; margin-top:2px;">This will add a new renewal row linked directly to this campaign and ad account.</div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">New Campaign Name <span>*</span></label>
                        <input type="text" id="extend_campaign_name" name="campaign_name" required class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Ad Account Name</label>
                        <input type="text" id="extend_ad_account_name" name="ad_account_name" class="cmp-form-input">
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Platform</label>
                        <select id="extend_platform" name="platform" class="cmp-form-select">
                            <option value="Facebook / Meta">Facebook / Meta</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Google Ads">Google Ads</option>
                            <option value="YouTube Ads">YouTube Ads</option>
                            <option value="LinkedIn Ads">LinkedIn Ads</option>
                            <option value="SEO / SEM">SEO / SEM</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Status <span>*</span></label>
                        <select id="extend_status" name="status" required class="cmp-form-select">
                            <option value="active" selected>Active</option>
                            <option value="paused">Paused</option>
                            <option value="expired">Expired</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Budget Amount (₹)</label>
                        <input type="number" step="0.01" min="0" id="extend_budget_amount" name="budget_amount" class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Budget Type</label>
                        <select id="extend_budget_type" name="budget_type" class="cmp-form-select">
                            <option value="Daily">Daily</option>
                            <option value="Monthly" selected>Monthly</option>
                            <option value="Total">Total</option>
                            <option value="Custom">Custom</option>
                        </select>
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Renewal Start Date</label>
                        <input type="date" id="extend_start_date" name="start_date" class="cmp-form-input">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Renewal End Date</label>
                        <input type="date" id="extend_end_date" name="end_date" class="cmp-form-input">
                    </div>
                </div>

                <div class="cmp-form-group">
                    <label class="cmp-form-label">Renewal Remarks / Notes</label>
                    <textarea id="extend_remarks" name="remarks" rows="2" placeholder="e.g. Month 2 Renewal after approval..." class="cmp-form-textarea"></textarea>
                </div>
            </div>
            <div class="cmp-modal-footer">
                <button type="button" class="cmp-btn" onclick="closeExtendModal()">Cancel</button>
                <button type="submit" class="cmp-btn cmp-btn-primary">Confirm & Create Extension</button>
            </div>
        </form>
    </div>
</div>

{{-- 7. Stop Campaign & Refund Modal --}}
<div class="cmp-modal-overlay" id="stopCampaignModal">
    <div class="cmp-modal-box cmp-modal-box--md">
        <div class="cmp-modal-header">
            <div class="cmp-modal-title">🛑 Stop Campaign & Process Refund</div>
            <button type="button" class="cmp-modal-close" onclick="closeStopModal()">&times;</button>
        </div>
        <form method="POST" id="stopCampaignForm" action="">
            @csrf
            <div class="cmp-modal-body">
                <div class="cmp-notice-box cmp-notice-box--danger">
                    You are stopping campaign: <strong id="stopCampaignName">Campaign</strong>
                </div>

                {{-- Metrics Preview Grid --}}
                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px;">
                    <div>
                        <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#64748b;">Daily Budget</div>
                        <div style="font-size:15px; font-weight:900; color:#ea580c;" id="stopDailyBudget">₹0.00</div>
                    </div>
                    <div>
                        <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#64748b;">Days Run (Active)</div>
                        <div style="font-size:15px; font-weight:900; color:#0f172a;" id="stopDaysRun">0 days</div>
                    </div>
                    <div>
                        <div style="font-size:10px; font-weight:800; text-transform:uppercase; color:#64748b;">Estimated Spend</div>
                        <div style="font-size:15px; font-weight:900; color:#dc2626;" id="stopEstimatedSpend">₹0.00</div>
                    </div>
                </div>

                <div class="cmp-form-grid">
                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Stop Date <span>*</span></label>
                        <input type="date" id="stop_date" name="stop_date" required class="cmp-form-input" value="{{ date('Y-m-d') }}" onchange="recalculateStopMetrics()">
                    </div>

                    <div class="cmp-form-group">
                        <label class="cmp-form-label">Refund Amount (₹)</label>
                        <input type="number" step="0.01" min="0" id="stop_refund_amount" name="refund_amount" placeholder="e.g. 2500" class="cmp-form-input">
                    </div>
                </div>

                <div class="cmp-form-group">
                    <label class="cmp-form-label">Reason for Stopping & Refund Remarks <span>*</span></label>
                    <textarea id="stop_reason" name="stop_reason" required rows="2" placeholder="e.g. Client requested campaign termination, unused balance calculated for refund..." class="cmp-form-textarea"></textarea>
                </div>

                {{-- CBO Alert --}}
                <div class="cmp-notice-box cmp-notice-box--warning" style="display:flex; align-items:flex-start; gap:8px;">
                    <span style="font-size:16px;">📧</span>
                    <div>
                        <strong>Chief Business Officer Notification:</strong><br>
                        An automated email will be sent to the <strong>Chief Business Officer (CBO)</strong> containing the client name, ad account, total run days (<span id="stopNoticeDays">0</span> days), daily budget, and refund details.
                    </div>
                </div>
            </div>
            <div class="cmp-modal-footer">
                <button type="button" class="cmp-btn" onclick="closeStopModal()">Cancel</button>
                <button type="submit" class="cmp-btn cmp-btn-danger" style="font-weight:800;">
                    Confirm Stop & Notify CBO
                </button>
            </div>
        </form>
    </div>
</div>

{{-- 8. Delete Campaign Confirmation Modal --}}
<div class="cmp-modal-overlay" id="deleteCampaignModal">
    <div class="cmp-modal-box cmp-modal-box--sm">
        <form method="POST" id="deleteCampaignForm" action="">
            @csrf
            @method('DELETE')
            <div class="cmp-delete-body">
                <div class="cmp-delete-icon-wrap">
                    <div class="cmp-delete-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                    </div>
                </div>
                
                <div class="cmp-delete-title">Delete Campaign?</div>
                
                <div class="cmp-delete-desc">
                    Are you sure you want to delete <span class="cmp-delete-target-pill" id="deleteCampaignTargetName">Campaign</span>?
                </div>

                <div class="cmp-delete-warning-box">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" style="flex-shrink:0;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <span>This campaign will be archived from this customer's active campaign list.</span>
                </div>
            </div>

            <div class="cmp-delete-footer">
                <button type="button" class="cmp-btn" onclick="closeDeleteModal()" style="flex:1;">Cancel</button>
                <button type="submit" class="cmp-btn cmp-btn-danger" style="flex:1;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 6h18m-2 0v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6m3 0V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                    </svg>
                    Yes, Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentActiveStopCampaign = null;
let currentActiveResumeCampaign = null;
let allCampaignsList = @json($allCampaigns ?? $campaigns);

// Helper: trace all ancestor / past versions of this campaign
function getAncestryChain(campaign, allList) {
    let ancestors = [];
    let currentId = campaign.extended_from_id;
    let visited = new Set();
    
    while (currentId && !visited.has(currentId)) {
        visited.add(currentId);
        let parent = allList.find(c => c.id === currentId);
        if (parent) {
            ancestors.unshift(parent); // oldest first
            currentId = parent.extended_from_id;
        } else {
            break;
        }
    }
    return ancestors;
}

// Helper: trace any descendant renewals branched from this campaign
function getDescendantsChain(campaign, allList) {
    let descendants = [];
    let currentId = campaign.id;
    let queue = [currentId];
    let visited = new Set([currentId]);

    while (queue.length > 0) {
        let parentId = queue.shift();
        let children = allList.filter(c => c.extended_from_id === parentId);
        children.forEach(child => {
            if (!visited.has(child.id)) {
                visited.add(child.id);
                descendants.push(child);
                queue.push(child.id);
            }
        });
    }
    return descendants;
}

// 1. View History Modal
function openViewHistoryModal(campaign, runDays, dailyBudget) {
    document.getElementById('viewModalCampaignTitle').textContent = campaign.campaign_name || 'Campaign Details';
    document.getElementById('viewModalCampaignSubtitle').textContent = 'ID #' + campaign.id + ' • ' + (campaign.platform || 'General');

    // Tab 1: Overview
    document.getElementById('viewAdAccountVal').textContent = campaign.ad_account_name || '—';
    document.getElementById('viewPlatformVal').textContent = campaign.platform || '—';
    document.getElementById('viewStatusVal').textContent = (campaign.status || 'active').toUpperCase();
    document.getElementById('viewBudgetVal').textContent = campaign.budget_amount ? ('₹' + parseFloat(campaign.budget_amount).toFixed(2) + ' (' + (campaign.budget_type || 'Monthly') + ')') : '—';
    document.getElementById('viewDailyBudgetVal').textContent = '₹' + parseFloat(dailyBudget).toFixed(2) + ' / day';
    document.getElementById('viewRunDaysVal').textContent = runDays + (runDays === 1 ? ' day' : ' days');
    
    let sched = (campaign.start_date ? campaign.start_date.substring(0, 10) : 'Start') + ' → ' + (campaign.end_date ? campaign.end_date.substring(0, 10) : 'Ongoing');
    document.getElementById('viewScheduleVal').textContent = sched;
    document.getElementById('viewRemarksVal').textContent = campaign.remarks || 'No specific remarks.';

    // Tab 2: Extension & Full Lineage History (Past & Descendants)
    let ancestors = getAncestryChain(campaign, allCampaignsList);
    let children = getDescendantsChain(campaign, allCampaignsList);
    let totalExtensionsCount = ancestors.length + children.length;
    document.getElementById('viewExtBadge').textContent = totalExtensionsCount;

    let historyBox = document.getElementById('viewExtensionParentBox');
    let childrenBox = document.getElementById('viewExtensionChildrenList');

    if (totalExtensionsCount === 0) {
        historyBox.innerHTML = `
            <div style="padding:16px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; color:#64748b; font-size:12.5px; text-align:center;">
                🌱 <strong>Original Single Campaign:</strong> This is a standalone campaign and has no previous or subsequent extensions.
            </div>
        `;
        childrenBox.innerHTML = '';
    } else {
        let ancestryHtml = '';
        
        // Render Past / Ancestor Campaign Versions
        ancestors.forEach((anc, idx) => {
            let tagLabel = (idx === 0) ? '🌱 Original Base Campaign' : `🔄 Past Renewal #${idx}`;
            ancestryHtml += `
                <div style="position:relative; padding:14px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; margin-bottom:10px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;">
                        <div>
                            <span style="display:inline-block; font-size:10.5px; font-weight:800; text-transform:uppercase; color:#6d28d9; background:#f5f3ff; border:1px solid #ddd6fe; padding:2px 8px; border-radius:6px; margin-bottom:4px;">
                                ${tagLabel}
                            </span>
                            <div style="font-weight:800; color:#0f172a; font-size:13.5px;">${anc.campaign_name}</div>
                        </div>
                        <span class="cmp-status-pill cmp-status--${anc.status || 'expired'}">${anc.status || 'past renewal'}</span>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-top:10px; font-size:11.5px; color:#475569;">
                        <div>📅 <strong>Dates:</strong> ${anc.start_date ? anc.start_date.substring(0, 10) : '—'} → ${anc.end_date ? anc.end_date.substring(0, 10) : '—'}</div>
                        <div>💰 <strong>Budget:</strong> ₹${parseFloat(anc.budget_amount || 0).toFixed(2)} (${anc.budget_type || 'Monthly'})</div>
                        <div>💼 <strong>Account:</strong> ${anc.ad_account_name || '—'}</div>
                    </div>
                    ${anc.remarks ? `<div style="margin-top:6px; font-size:11px; color:#64748b;">💬 ${anc.remarks}</div>` : ''}
                </div>
            `;
        });

        // Current Active / Selected Version Card
        let currentTag = ancestors.length > 0 ? `⭐ Latest Active Extended Version (Renewal #${ancestors.length})` : '⭐ Current Active Version';
        ancestryHtml += `
            <div style="position:relative; padding:16px; border-radius:12px; background:#fff7ed; border:2px solid #fed7aa; margin-bottom:12px; box-shadow:0 4px 12px rgba(254,95,4,.06);">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; flex-wrap:wrap;">
                    <div>
                        <span style="display:inline-block; font-size:11px; font-weight:900; text-transform:uppercase; color:#c2410c; background:#ffedd5; border:1px solid #fdba74; padding:3px 10px; border-radius:6px; margin-bottom:4px;">
                            ${currentTag}
                        </span>
                        <div style="font-weight:900; color:#0f172a; font-size:14.5px;">${campaign.campaign_name}</div>
                    </div>
                    <span class="cmp-status-pill cmp-status--${campaign.status || 'active'}">${campaign.status || 'active'}</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-top:10px; font-size:12px; color:#334155;">
                    <div>📅 <strong>Dates:</strong> ${campaign.start_date ? campaign.start_date.substring(0, 10) : 'Start'} → ${campaign.end_date ? campaign.end_date.substring(0, 10) : 'Ongoing'}</div>
                    <div>💰 <strong>Budget:</strong> ₹${parseFloat(campaign.budget_amount || 0).toFixed(2)} (${campaign.budget_type || 'Monthly'})</div>
                    <div>💼 <strong>Account:</strong> ${campaign.ad_account_name || '—'}</div>
                </div>
            </div>
        `;

        historyBox.innerHTML = `
            <div style="margin-bottom:8px; font-size:12px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em;">
                📜 Complete Campaign Extension Lineage (${ancestors.length + 1} Versions in Chain):
            </div>
            ${ancestryHtml}
        `;

        if (children.length > 0) {
            let childHtml = '';
            children.forEach((ch, idx) => {
                childHtml += `
                    <div style="padding:12px 14px; border-radius:12px; background:#fff; border:1px solid #e2e8f0; margin-bottom:8px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div style="font-weight:800; color:#0f172a; font-size:13px;">${ch.campaign_name}</div>
                            <span class="cmp-status-pill cmp-status--${ch.status || 'active'}">${ch.status || 'active'}</span>
                        </div>
                        <div style="font-size:11.5px; color:#64748b; margin-top:4px;">
                            💰 Budget: <strong>₹${parseFloat(ch.budget_amount || 0).toFixed(2)}</strong> | 📅 Schedule: ${ch.start_date ? ch.start_date.substring(0, 10) : '—'} to ${ch.end_date ? ch.end_date.substring(0, 10) : 'Ongoing'}
                        </div>
                    </div>
                `;
            });
            childrenBox.innerHTML = childHtml;
        } else {
            childrenBox.innerHTML = '';
        }
    }

    // Tab 3: Pause History
    let pauseHistory = campaign.pause_history || [];
    let pauseCount = pauseHistory.length + (campaign.status === 'paused' ? 1 : 0);
    document.getElementById('viewPauseBadge').textContent = pauseCount;

    let pauseAlertBox = document.getElementById('viewCurrentPauseAlert');
    if (campaign.status === 'paused') {
        let pDate = campaign.paused_at ? new Date(campaign.paused_at).toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'}) : 'Recently';
        pauseAlertBox.innerHTML = `
            <div class="cmp-notice-box cmp-notice-box--warning" style="margin-bottom:12px;">
                ⏸️ <strong>Currently Paused:</strong> This campaign is currently in paused state since <strong>${pDate}</strong>.
            </div>
        `;
    } else {
        pauseAlertBox.innerHTML = '';
    }

    let timelineList = document.getElementById('viewPauseTimelineList');
    if (pauseHistory.length > 0) {
        let html = '';
        pauseHistory.forEach((log, index) => {
            html += `
                <div class="cmp-timeline-item">
                    <div class="cmp-timeline-dot"></div>
                    <div class="cmp-timeline-title">Paused for ${log.days || 1} ${log.days == 1 ? 'day' : 'days'}</div>
                    <div class="cmp-timeline-meta">
                        📅 <strong>Period:</strong> ${log.paused_date || '—'} → ${log.resumed_date || '—'}
                    </div>
                </div>
            `;
        });
        timelineList.innerHTML = `
            <div style="padding:14px; background:#fafaf9; border-radius:12px; border:1px solid #f1f5f9;">
                <div style="font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; margin-bottom:10px;">
                    Total Paused Days: ${campaign.total_paused_days || 0} days
                </div>
                ${html}
            </div>
        `;
    } else if (campaign.status !== 'paused') {
        timelineList.innerHTML = `
            <div style="padding:14px; text-align:center; color:#94a3b8; font-size:12px; border:1px dashed #cbd5e1; border-radius:10px;">
                ✓ This campaign has run continuously without any recorded pauses.
            </div>
        `;
    } else {
        timelineList.innerHTML = '';
    }

    // Tab 4: Stop History
    let stopBox = document.getElementById('viewStopDetailsBox');
    if (campaign.status === 'stopped' || campaign.stopped_at || campaign.stop_date) {
        let sDate = campaign.stop_date ? campaign.stop_date.substring(0, 10) : (campaign.stopped_at ? campaign.stopped_at.substring(0, 10) : '—');
        let refundTxt = campaign.refund_amount ? ('₹' + parseFloat(campaign.refund_amount).toFixed(2)) : 'None / Not recorded';
        stopBox.innerHTML = `
            <div class="cmp-notice-box cmp-notice-box--danger" style="margin-bottom:12px;">
                🛑 <strong>Campaign Stopped on ${sDate}</strong>
            </div>
            <div style="display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; margin-bottom:12px;">
                <div class="cmp-info-pill">
                    <span class="cmp-info-pill-label">Total Days Run</span>
                    <span class="cmp-info-pill-val" style="color:#dc2626;">${runDays} days</span>
                </div>
                <div class="cmp-info-pill">
                    <span class="cmp-info-pill-label">Refund Processed</span>
                    <span class="cmp-info-pill-val" style="color:#15803d;">${refundTxt}</span>
                </div>
            </div>
            <div style="padding:14px; border-radius:12px; background:#fff5f5; border:1px solid #fee2e2; font-size:12.5px; margin-bottom:12px;">
                <div style="font-weight:800; color:#991b1b; margin-bottom:4px;">🛑 Reason for Stopping:</div>
                <div style="color:#7f1d1d; white-space:pre-line;">${campaign.stop_reason || 'No specific stop reason noted.'}</div>
            </div>
            <div class="cmp-notice-box cmp-notice-box--success">
                ✉️ <strong>CBO Notification:</strong> Email notification with refund & run days summary was dispatched to the Chief Business Officer upon stopping.
            </div>
        `;
    } else {
        stopBox.innerHTML = `
            <div style="padding:24px; text-align:center; color:#64748b; font-size:13px; border:1px dashed #cbd5e1; border-radius:12px;">
                <div style="font-size:24px; margin-bottom:6px;">🟢</div>
                <div style="font-weight:800; color:#0f172a;">Campaign is Not Stopped</div>
                <div style="font-size:12px; color:#94a3b8; margin-top:2px;">Currently in <strong>${(campaign.status || 'active').toUpperCase()}</strong> status.</div>
            </div>
        `;
    }

    switchViewTab('tabOverview');
    document.getElementById('viewCampaignHistoryModal').classList.add('is-active');
}
function closeViewHistoryModal() {
    document.getElementById('viewCampaignHistoryModal').classList.remove('is-active');
}
function switchViewTab(tabId) {
    document.querySelectorAll('.cmp-tab-pane').forEach(el => el.classList.remove('is-active'));
    document.querySelectorAll('.cmp-tab-link').forEach(el => el.classList.remove('is-active'));

    document.getElementById(tabId).classList.add('is-active');
    
    // Highlight correct tab button
    const btnMap = {
        'tabOverview': 0,
        'tabExtensions': 1,
        'tabPauses': 2,
        'tabStop': 3
    };
    const links = document.querySelectorAll('.cmp-tab-link');
    if (links[btnMap[tabId]]) {
        links[btnMap[tabId]].classList.add('is-active');
    }
}

// 2. Create Modal
// Action Dropdown Management (Single-open: clicking one hides all other open dropdowns)
function toggleActionDropdown(event, id) {
    if (event) {
        event.stopPropagation();
    }
    const targetDropdown = document.querySelector(`.cmp-dropdown[data-dropdown-id="${id}"]`);
    if (!targetDropdown) return;

    const wasOpen = targetDropdown.classList.contains('is-open');

    // Close any other open dropdowns across all rows
    closeAllActionDropdowns();

    // Toggle current dropdown
    if (!wasOpen) {
        targetDropdown.classList.add('is-open');
    }
}

function closeAllActionDropdowns() {
    document.querySelectorAll('.cmp-dropdown.is-open').forEach(el => {
        el.classList.remove('is-open');
    });
}

// Global click listener to close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.cmp-dropdown')) {
        closeAllActionDropdowns();
    }
});

// Close open dropdowns on scroll or Escape key
window.addEventListener('scroll', function() {
    closeAllActionDropdowns();
}, true);

function openCreateModal() {
    document.getElementById('createCampaignModal').classList.add('is-active');
}
function closeCreateModal() {
    document.getElementById('createCampaignModal').classList.remove('is-active');
}

// 3. Edit Modal
function openEditModal(campaign) {
    const form = document.getElementById('editCampaignForm');
    form.action = '/projects/campaigns/' + campaign.id;

    document.getElementById('edit_campaign_name').value = campaign.campaign_name || '';
    document.getElementById('edit_ad_account_name').value = campaign.ad_account_name || '';
    document.getElementById('edit_platform').value = campaign.platform || 'Facebook / Meta';
    document.getElementById('edit_status').value = campaign.status || 'active';
    document.getElementById('edit_budget_amount').value = campaign.budget_amount || '';
    document.getElementById('edit_budget_type').value = campaign.budget_type || 'Monthly';
    document.getElementById('edit_start_date').value = campaign.start_date ? campaign.start_date.substring(0, 10) : '';
    document.getElementById('edit_end_date').value = campaign.end_date ? campaign.end_date.substring(0, 10) : '';
    document.getElementById('edit_remarks').value = campaign.remarks || '';

    document.getElementById('editCampaignModal').classList.add('is-active');
}
function closeEditModal() {
    document.getElementById('editCampaignModal').classList.remove('is-active');
}

// 4. Pause Modal
function openPauseModal(campaign) {
    const form = document.getElementById('pauseCampaignForm');
    form.action = '/projects/campaigns/' + campaign.id + '/pause';
    document.getElementById('pauseCampaignName').textContent = campaign.campaign_name || 'this campaign';
    document.getElementById('pauseCampaignModal').classList.add('is-active');
}
function closePauseModal() {
    document.getElementById('pauseCampaignModal').classList.remove('is-active');
}

// 5. Resume Modal
function openResumeModal(campaign) {
    currentActiveResumeCampaign = campaign;
    const form = document.getElementById('resumeCampaignForm');
    form.action = '/projects/campaigns/' + campaign.id + '/resume';
    document.getElementById('resumeCampaignName').textContent = campaign.campaign_name || 'this campaign';
    
    let pausedDateText = campaign.paused_at ? new Date(campaign.paused_at).toLocaleDateString('en-GB', {day:'numeric', month:'short', year:'numeric'}) : 'Earlier';
    document.getElementById('resumePausedDateNote').textContent = 'Paused Date: ' + pausedDateText;

    calculateResumeDays();
    document.getElementById('resumeCampaignModal').classList.add('is-active');
}
function closeResumeModal() {
    document.getElementById('resumeCampaignModal').classList.remove('is-active');
}
function calculateResumeDays() {
    if (!currentActiveResumeCampaign) return;
    const resumeInput = document.getElementById('resume_date').value;
    if (!resumeInput) return;

    let pausedDate = currentActiveResumeCampaign.paused_at ? new Date(currentActiveResumeCampaign.paused_at) : new Date(currentActiveResumeCampaign.updated_at);
    let resumeDate = new Date(resumeInput);
    
    let diffTime = resumeDate - pausedDate;
    let diffDays = Math.max(1, Math.round(diffTime / (1000 * 60 * 60 * 24)));
    if (isNaN(diffDays)) diffDays = 1;

    document.getElementById('resumeDiffDays').textContent = diffDays + (diffDays === 1 ? ' day' : ' days');
}

// 6. Extend / Renew Modal (Clean naming avoiding repetitive Renewal strings)
function openExtendModal(campaign) {
    const form = document.getElementById('extendCampaignForm');
    form.action = '/projects/campaigns/' + campaign.id + '/extend';
    
    document.getElementById('extendParentName').textContent = campaign.campaign_name || 'Campaign #' + campaign.id;
    
    // Clean repetitive "(Renewal ...)" or "(Extension ...)"
    let rawName = campaign.campaign_name || 'Campaign';
    let cleanBase = rawName.replace(/\s*\((Renewal|Extension)(\s*#?\d*)?\)/gi, '').trim();
    
    // Count child renewals to generate clean index
    let existingExtensions = campaign.extensions || allCampaignsList.filter(c => c.extended_from_id === campaign.id);
    let nextIndex = (existingExtensions ? existingExtensions.length : 0) + 1;
    let suggestedName = nextIndex > 1 ? `${cleanBase} (Renewal #${nextIndex})` : `${cleanBase} (Renewal)`;

    document.getElementById('extend_campaign_name').value = suggestedName;
    document.getElementById('extend_ad_account_name').value = campaign.ad_account_name || '';
    document.getElementById('extend_platform').value = campaign.platform || 'Facebook / Meta';
    document.getElementById('extend_status').value = 'active';
    document.getElementById('extend_budget_amount').value = campaign.budget_amount || '';
    document.getElementById('extend_budget_type').value = campaign.budget_type || 'Monthly';
    
    // Default start date to previous end date + 1 day or today
    let nextStartDate = '';
    if (campaign.end_date) {
        let d = new Date(campaign.end_date);
        d.setDate(d.getDate() + 1);
        nextStartDate = d.toISOString().substring(0, 10);
    } else {
        nextStartDate = new Date().toISOString().substring(0, 10);
    }
    document.getElementById('extend_start_date').value = nextStartDate;
    document.getElementById('extend_end_date').value = '';
    document.getElementById('extend_remarks').value = 'Renewal from ' + cleanBase;

    document.getElementById('extendCampaignModal').classList.add('is-active');
}
function closeExtendModal() {
    document.getElementById('extendCampaignModal').classList.remove('is-active');
}

// 7. Stop Modal
function openStopModal(campaign, runDays, dailyBudget) {
    currentActiveStopCampaign = {
        ...campaign,
        daily_budget: dailyBudget,
        calculated_run_days: runDays
    };

    const form = document.getElementById('stopCampaignForm');
    form.action = '/projects/campaigns/' + campaign.id + '/stop';
    document.getElementById('stopCampaignName').textContent = campaign.campaign_name || 'this campaign';
    document.getElementById('stopDailyBudget').textContent = '₹' + parseFloat(dailyBudget).toFixed(2);
    
    document.getElementById('stop_date').value = new Date().toISOString().substring(0, 10);
    document.getElementById('stop_refund_amount').value = '';
    document.getElementById('stop_reason').value = '';

    recalculateStopMetrics();
    document.getElementById('stopCampaignModal').classList.add('is-active');
}
function closeStopModal() {
    document.getElementById('stopCampaignModal').classList.remove('is-active');
}
function recalculateStopMetrics() {
    if (!currentActiveStopCampaign) return;
    const stopDateVal = document.getElementById('stop_date').value;
    if (!stopDateVal) return;

    let startDate = currentActiveStopCampaign.start_date ? new Date(currentActiveStopCampaign.start_date) : new Date();
    let stopDate = new Date(stopDateVal);
    
    let diffDays = Math.max(0, Math.round((stopDate - startDate) / (1000 * 60 * 60 * 24)) + 1);
    let pausedDays = parseInt(currentActiveStopCampaign.total_paused_days || 0);
    let activeDays = Math.max(0, diffDays - pausedDays);

    let dailyBudget = parseFloat(currentActiveStopCampaign.daily_budget || 0);
    let estimatedSpend = activeDays * dailyBudget;
    let totalBudget = parseFloat(currentActiveStopCampaign.budget_amount || 0);
    let suggestedRefund = Math.max(0, totalBudget - estimatedSpend);

    document.getElementById('stopDaysRun').textContent = activeDays + (activeDays === 1 ? ' day' : ' days');
    document.getElementById('stopEstimatedSpend').textContent = '₹' + estimatedSpend.toFixed(2);
    document.getElementById('stopNoticeDays').textContent = activeDays;

    if (suggestedRefund > 0 && !document.getElementById('stop_refund_amount').value) {
        document.getElementById('stop_refund_amount').placeholder = 'Suggested balance: ₹' + suggestedRefund.toFixed(2);
    }
}

// 8. Delete Modal
function openDeleteModal(id, name) {
    const form = document.getElementById('deleteCampaignForm');
    form.action = '/projects/campaigns/' + id;
    document.getElementById('deleteCampaignTargetName').textContent = name || 'this campaign';
    document.getElementById('deleteCampaignModal').classList.add('is-active');
}
function closeDeleteModal() {
    document.getElementById('deleteCampaignModal').classList.remove('is-active');
}

// Close modals on clicking overlay background
document.querySelectorAll('.cmp-modal-overlay').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('is-active');
        }
    });
});

// Close modals on Escape key press
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateModal();
        closeEditModal();
        closeViewHistoryModal();
        closePauseModal();
        closeResumeModal();
        closeExtendModal();
        closeStopModal();
        closeDeleteModal();
    }
});
</script>
@endsection