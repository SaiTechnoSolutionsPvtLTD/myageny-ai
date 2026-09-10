@extends('layouts.app')

@section('title', 'Digital Marketing Campaigns')

@push('styles')
<style>
.cmp-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.cmp-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.cmp-title { font-size:24px; font-weight:900; color:#111827; }
.cmp-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.cmp-chip { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:999px; background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:12px; font-weight:800; }
.cmp-body { padding:22px 28px 34px; display:grid; gap:20px; }

/* Stats Grid */
.cmp-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; }
.cmp-stat { position:relative; overflow:hidden; background:var(--stat-gradient); border:none; border-radius:16px; padding:22px 24px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.08),0 8px 10px -6px rgba(0,0,0,0.04); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; }
.cmp-stat:hover { transform:translateY(-4px); box-shadow:0 18px 25px -5px rgba(0,0,0,0.12),0 10px 10px -5px rgba(0,0,0,0.06); }
.cmp-stat-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; }
.cmp-stat-icon { display:flex; align-items:center; justify-content:center; width:42px; height:42px; border-radius:12px; background:rgba(255,255,255,0.22); color:#fff; font-size:18px; backdrop-filter:blur(4px); }
.cmp-stat-label { font-size:11px; font-weight:800; color:rgba(255,255,255,0.92); text-transform:uppercase; letter-spacing:.06em; text-shadow:0 1px 2px rgba(0,0,0,0.1); }
.cmp-stat-value { font-size:30px; font-weight:900; color:#fff; line-height:1.2; text-shadow:0 2px 4px rgba(0,0,0,0.1); }
.cmp-stat-footer { margin-top:12px; padding-top:12px; border-top:1px dashed rgba(255,255,255,0.25); font-size:12px; color:rgba(255,255,255,0.92); font-weight:600; text-shadow:0 1px 2px rgba(0,0,0,0.1); }

/* Card & Table */
.cmp-card { background:#fff; border:1px solid #eee7df; border-radius:14px; overflow:hidden; box-shadow:0 10px 30px rgba(15,23,42,.04); }
.cmp-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 24px; border-bottom:1px solid #f2ede8; background:#fffdfb; flex-wrap:wrap; }
.cmp-card-title { font-size:16px; font-weight:900; color:#111827; display:flex; align-items:center; gap:8px; }
.cmp-card-sub { margin-top:3px; font-size:12px; color:#7c7c7c; }

/* Filter / Search */
.cmp-search-box { display:flex; align-items:center; gap:10px; }
.cmp-input { min-height:38px; padding:6px 14px; border-radius:8px; border:1px solid #e2e8f0; font-size:13px; color:#1e293b; background:#fff; outline:none; transition:border-color .15s; }
.cmp-input:focus { border-color:#ea580c; }
.cmp-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; min-height:38px; padding:6px 16px; border-radius:8px; border:1px solid #d7dce2; background:#fff; color:#111827; text-decoration:none; font-size:13px; font-weight:700; cursor:pointer; transition:all .15s ease; }
.cmp-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.cmp-btn-primary:hover { background:#c2410c; border-color:#c2410c; color:#fff; }

.cmp-table-wrap { overflow-x:auto; }
.cmp-table { width:100%; border-collapse:collapse; min-width:980px; }
.cmp-table th { padding:14px 16px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.cmp-table td { padding:16px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:middle; }
.cmp-table tbody tr:hover td { background:#fffaf5; }

/* Company & Customer */
.cmp-company-name { font-weight:800; font-size:14px; color:#0f172a; display:flex; align-items:center; gap:8px; }
.cmp-avatar { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg, #ea580c, #fb923c); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px; flex-shrink:0; }
.cmp-customer-name { font-weight:700; color:#1e293b; font-size:13px; }
.cmp-contact-meta { font-size:11px; color:#64748b; margin-top:2px; display:flex; flex-direction:column; gap:2px; }

/* Team Allocation Badges */
.cmp-team-wrap { display:flex; flex-wrap:wrap; gap:6px; max-width:260px; }
.cmp-team-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 8px; border-radius:6px; font-size:11px; font-weight:700; }
.cmp-team-badge--tl { background:#eff6ff; color:#1d4ed8; border:1px solid #dbeafe; }
.cmp-team-badge--emp { background:#f8fafc; color:#475569; border:1px solid #e2e8f0; }

/* Pill Counts */
.cmp-count-badge { display:inline-flex; align-items:center; justify-content:center; padding:4px 12px; border-radius:999px; font-size:13px; font-weight:800; }
.cmp-count-total { background:#f1f5f9; color:#1e293b; border:1px solid #e2e8f0; }
.cmp-count-active { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.cmp-pulse-dot { width:7px; height:7px; border-radius:50%; background:#10b981; display:inline-block; margin-right:5px; box-shadow:0 0 0 0 rgba(16,185,129,0.7); animation:cmpPulse 2s infinite; }

@keyframes cmpPulse {
    0% { transform:scale(0.95); box-shadow:0 0 0 0 rgba(16,185,129,0.7); }
    70% { transform:scale(1); box-shadow:0 0 0 6px rgba(16,185,129,0); }
    100% { transform:scale(0.95); box-shadow:0 0 0 0 rgba(16,185,129,0); }
}

@media (max-width: 1024px) {
    .cmp-stats { grid-template-columns:repeat(2,minmax(0,1fr)); }
}
@media (max-width: 640px) {
    .cmp-stats { grid-template-columns:1fr; }
    .cmp-topbar, .cmp-body { padding:16px; }
}
</style>
@endpush

@section('content')
<div class="cmp-page">
    <div class="cmp-topbar">
        <div>
            <div class="cmp-title">Digital Marketing Campaigns</div>
            <div class="cmp-breadcrumb">Modules &gt; Production &gt; Campaigns</div>
        </div>
        <div class="cmp-chip">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                <line x1="4" y1="22" x2="4" y2="15"></line>
            </svg>
            Digital Marketing Budget Approved Leads
        </div>
    </div>

    <div class="cmp-body">
        {{-- KPI Cards --}}
        <section class="cmp-stats">
            {{-- Total Leads --}}
            <div class="cmp-stat" style="--stat-gradient: linear-gradient(135deg, #ea580c 0%, #fb923c 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Total DM Leads</span>
                    <span class="cmp-stat-icon">🏢</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_leads'] }}</div>
                <div class="cmp-stat-footer">
                    Leads with Budget Approval = Yes
                </div>
            </div>

            {{-- Total Campaigns --}}
            <div class="cmp-stat" style="--stat-gradient: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Total Campaigns</span>
                    <span class="cmp-stat-icon">📢</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_campaigns'] }}</div>
                <div class="cmp-stat-footer">
                    Across all customer accounts
                </div>
            </div>

            {{-- Active Campaigns --}}
            <div class="cmp-stat" style="--stat-gradient: linear-gradient(135deg, #059669 0%, #34d399 100%);">
                <div class="cmp-stat-header">
                    <span class="cmp-stat-label">Active Campaigns</span>
                    <span class="cmp-stat-icon">⚡</span>
                </div>
                <div class="cmp-stat-value">{{ $stats['total_active_campaigns'] }}</div>
                <div class="cmp-stat-footer">
                    Currently running campaigns
                </div>
            </div>
        </section>

        {{-- Main Table Card --}}
        <section class="cmp-card">
            <div class="cmp-card-head">
                <div>
                    <div class="cmp-card-title">
                        <span>Campaigns by Customer / Lead</span>
                    </div>
                    <div class="cmp-card-sub">
                        Showing all leads whose Digital Marketing products are moved to production with budget approval required.
                    </div>
                </div>

                <form method="GET" action="{{ route('projects.campaigns.index') }}" class="cmp-search-box">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search company, customer, mobile..."
                        class="cmp-input"
                        style="width: 260px;"
                    >
                    <button type="submit" class="cmp-btn cmp-btn-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Search
                    </button>
                    @if($search)
                        <a href="{{ route('projects.campaigns.index') }}" class="cmp-btn">Clear</a>
                    @endif
                </form>
            </div>

            <div class="cmp-card-body" style="padding:0;">
                <div class="cmp-table-wrap">
                    <table class="cmp-table">
                        <thead>
                            <tr>
                                <th style="width: 24%;">Company Name</th>
                                <th style="width: 18%;">Customer Name</th>
                                <th style="width: 22%;">Team Allocation</th>
                                <th style="width: 12%; text-align: center;">No Of Campaigns</th>
                                <th style="width: 14%; text-align: center;">No of Active Campaigns</th>
                                <th style="width: 10%; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leads as $lead)
                                <tr>
                                    {{-- Company Name --}}
                                    <td>
                                        <div class="cmp-company-name">
                                            <div class="cmp-avatar">
                                                {{ strtoupper(substr($lead->company_name ?: ($lead->contact_name ?: 'C'), 0, 1)) }}
                                            </div>
                                            <div>
                                                <div>{{ $lead->company_name ?: '—' }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Customer Name --}}
                                    <td>
                                        <div class="cmp-customer-name">{{ $lead->contact_name ?: '—' }}</div>
                                        <div class="cmp-contact-meta">
                                            @if($lead->mobile_number)
                                                <span>📞 {{ $lead->mobile_number }}</span>
                                            @endif
                                            @if($lead->email)
                                                <span>✉️ {{ $lead->email }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- Team Allocation --}}
                                    <td>
                                        <div class="cmp-team-wrap">
                                            @if($lead->allocated_tls && $lead->allocated_tls->isNotEmpty())
                                                @foreach($lead->allocated_tls as $tl)
                                                    <span class="cmp-team-badge cmp-team-badge--tl" title="Team Leader">
                                                        👑 TL: {{ $tl->name }}
                                                    </span>
                                                @endforeach
                                            @endif

                                            @if($lead->allocated_employees && $lead->allocated_employees->isNotEmpty())
                                                @foreach($lead->allocated_employees as $emp)
                                                    <span class="cmp-team-badge cmp-team-badge--emp" title="Team Member">
                                                        👤 {{ $emp->name }}
                                                    </span>
                                                @endforeach
                                            @endif

                                            @if((!$lead->allocated_tls || $lead->allocated_tls->isEmpty()) && (!$lead->allocated_employees || $lead->allocated_employees->isEmpty()))
                                                <span style="color:#94a3b8; font-size:12px; font-style:italic;">Not allocated yet</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- No Of Campaigns --}}
                                    <td style="text-align: center;">
                                        <span class="cmp-count-badge cmp-count-total">
                                            {{ $lead->no_of_campaigns }}
                                        </span>
                                    </td>

                                    {{-- No of Active Campaigns --}}
                                    <td style="text-align: center;">
                                        <span class="cmp-count-badge cmp-count-active">
                                            @if($lead->no_of_active_campaigns > 0)
                                                <span class="cmp-pulse-dot"></span>
                                            @endif
                                            {{ $lead->no_of_active_campaigns }} Active
                                        </span>
                                    </td>

                                    {{-- View Button --}}
                                    <td style="text-align: right;">
                                        <a href="{{ route('projects.campaigns.show', $lead->id) }}" class="cmp-btn cmp-btn-primary">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 48px 20px; color: #64748b;">
                                        <div style="font-size: 36px; margin-bottom: 8px;">📢</div>
                                        <div style="font-size: 16px; font-weight: 700; color: #1e293b;">No Digital Marketing Products Found</div>
                                        <div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">
                                            There are currently no products moved to production in the Digital Marketing department with Budget Approval needed.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
