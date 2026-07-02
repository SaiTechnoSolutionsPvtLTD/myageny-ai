@extends('layouts.app')

@section('title', 'My Accounts')

@push('styles')
<style>
.pjd-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pjd-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.pjd-title { font-size:24px; font-weight:900; color:#111827; }
.pjd-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.pjd-chip { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:12px; font-weight:800; }
.pjd-body { padding:22px 28px 34px; display:grid; gap:18px; }
.pjd-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:20px; margin-bottom:15px; }
.pjd-stat { position:relative; overflow:hidden; background:var(--stat-gradient); border:none; border-radius:16px; padding:24px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1),0 8px 10px -6px rgba(0,0,0,0.05); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; }
.pjd-stat:hover { transform:translateY(-5px); box-shadow:0 20px 25px -5px rgba(0,0,0,0.15),0 10px 10px -5px rgba(0,0,0,0.08); }
.pjd-stat-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
.pjd-stat-icon { display:flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:12px; background:rgba(255,255,255,0.2); color:#fff; font-size:20px; backdrop-filter:blur(4px); }
.pjd-stat-label { font-size:12px; font-weight:800; color:rgba(255,255,255,0.9); text-transform:uppercase; letter-spacing:.06em; text-shadow:0 1px 2px rgba(0,0,0,0.1); }
.pjd-stat-body { display:flex; flex-direction:column; gap:4px; }
.pjd-stat-value { font-size:32px; font-weight:900; color:#fff; line-height:1.2; text-shadow:0 2px 4px rgba(0,0,0,0.1); }
.pjd-stat-footer { margin-top:14px; padding-top:14px; border-top:1px dashed rgba(255,255,255,0.25); font-size:13px; color:rgba(255,255,255,0.9); font-weight:600; text-shadow:0 1px 2px rgba(0,0,0,0.1); }
.pjd-card { background:#fff; border:1px solid #eee7df; border-radius:10px; overflow:hidden; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.pjd-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:18px 20px; border-bottom:1px solid #f2ede8; background:#fffdfb; }
.pjd-card-title { font-size:16px; font-weight:900; color:#111827; }
.pjd-card-sub { margin-top:4px; font-size:12px; color:#7c7c7c; }
.pjd-card-body { padding:20px; }
.pjd-table-wrap { overflow-x:auto; }
.pjd-table { width:100%; border-collapse:collapse; min-width:880px; }
.pjd-table th { padding:12px 14px; text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#7c7c7c; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.pjd-table td { padding:14px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:top; }
.pjd-table tbody tr:hover td { background:#fffaf5; }
.pjd-product { font-weight:800; color:#111827; text-decoration:none; }
.pjd-meta { margin-top:4px; font-size:11px; color:#64748b; }
.pjd-btn { display:inline-flex; align-items:center; justify-content:center; min-height:36px; padding:6px 14px; border-radius:8px; border:1px solid #d7dce2; background:#fff; color:#111827; text-decoration:none; font-size:13px; font-weight:800; cursor:pointer; }
.pjd-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }
.pjd-btn-primary:hover { background:#d97706; border-color:#d97706; }
</style>
@endpush

@section('content')
<div class="pjd-page">
    <div class="pjd-topbar">
        <div>
            <div class="pjd-title">My Accounts</div>
            <div class="pjd-breadcrumb">Modules > Projects > My Accounts</div>
        </div>
        <div class="pjd-chip">Allocated Accounts Overview</div>
    </div>

    <div class="pjd-body">
        {{-- KPI Cards --}}
        <section class="pjd-stats">
            {{-- Total Accounts --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #ea580c 0%, #ff7e3b 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Total Accounts</span>
                    <span class="pjd-stat-icon">👥</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $leads->count() }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Active allocated customer leads
                </div>
            </div>

            {{-- Total Renewals --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Total Renewals</span>
                    <span class="pjd-stat-icon">🔄</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $leads->sum('renewals_count') }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Count-wise renewal products
                </div>
            </div>

            {{-- Posters Summary --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #059669 0%, #34d399 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Posters Summary</span>
                    <span class="pjd-stat-icon">🎨</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $leads->sum('total_posters') }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Comp: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800;">{{ $leads->sum('completed_posters') }}</span> | 
                    Pend: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800;">{{ $leads->sum('pending_posters') }}</span>
                </div>
            </div>

            {{-- Videos Summary --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #7c3aed 0%, #c084fc 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Videos Summary</span>
                    <span class="pjd-stat-icon">🎥</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $leads->sum('total_videos') }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Comp: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800;">{{ $leads->sum('completed_videos') }}</span> | 
                    Pend: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800;">{{ $leads->sum('pending_videos') }}</span>
                </div>
            </div>
        </section>

        <section class="pjd-card">
            <div class="pjd-card-head">
                <div>
                    <div class="pjd-card-title">All Allocated Accounts</div>
                    <div class="pjd-card-sub">List of leads/accounts having projects allocated to you.</div>
                </div>
            </div>
            <div class="pjd-card-body" style="padding:0;">
                <div class="pjd-table-wrap">
                    <table class="pjd-table">
                        <thead>
                            <tr>
                                <th>Company / Account Name</th>
                                <th>Contact Person</th>
                                <th>Mobile & Email</th>
                                <th>Renewals Count</th>
                                <th>Posters (Total / Comp / Pend)</th>
                                <th>Videos (Total / Comp / Pend)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leads as $lead)
                                <tr>
                                    <td>
                                        <div class="pjd-product">{{ $lead->company_name ?: 'No Company Name' }}</div>
                                    </td>
                                    <td>
                                        <div style="font-weight:600; color:#475569;">{{ $lead->contact_name ?: '—' }}</div>
                                    </td>
                                    <td>
                                        <div style="font-weight:500; color:#475569;">{{ $lead->mobile_number ?: '—' }}</div>
                                        <div class="pjd-meta">{{ $lead->email ?: '—' }}</div>
                                    </td>
                                    <td>
                                        <span style="font-weight:700; color:#1e293b;">{{ $lead->renewals_count }}</span>
                                    </td>
                                    <td>
                                        <div style="font-weight:700; color:#1e293b;">
                                            {{ $lead->total_posters }} 
                                            <span style="font-weight:500; color:#64748b;">/</span> 
                                            <span style="color:#166534;">{{ $lead->completed_posters }}</span> 
                                            <span style="font-weight:500; color:#64748b;">/</span> 
                                            <span style="color:#c2410c;">{{ $lead->pending_posters }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight:700; color:#1e293b;">
                                            {{ $lead->total_videos }} 
                                            <span style="font-weight:500; color:#64748b;">/</span> 
                                            <span style="color:#166534;">{{ $lead->completed_videos }}</span> 
                                            <span style="font-weight:500; color:#64748b;">/</span> 
                                            <span style="color:#c2410c;">{{ $lead->pending_videos }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('projects.my-accounts.show', ['lead' => $lead->id]) }}" class="pjd-btn pjd-btn-primary">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align:center; padding:40px; color:#64748b;">
                                        No allocated accounts found for your user.
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
