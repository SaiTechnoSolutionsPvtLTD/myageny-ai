@extends('layouts.app')

@section('title', ($lead->company_name ?: ($lead->contact_name ?: 'Account')) . ' - Technical SEO Projects')

@push('styles')
<style>
.seo-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.seo-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.seo-title { font-size:22px; font-weight:900; color:#111827; }
.seo-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.seo-body { padding:22px 28px 34px; display:grid; gap:20px; }

/* Customer Banner Card */
.seo-hero { background:#fff; border:1px solid #eee7df; border-radius:14px; padding:22px 26px; box-shadow:0 10px 30px rgba(15,23,42,.04); display:flex; justify-content:space-between; align-items:center; gap:20px; flex-wrap:wrap; }
.seo-hero-main { display:flex; gap:16px; align-items:center; }
.seo-hero-avatar { width:52px; height:52px; border-radius:12px; background:linear-gradient(135deg, #0284c7, #38bdf8); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:22px; }
.seo-hero-title { font-size:18px; font-weight:900; color:#0f172a; margin-bottom:4px; }
.seo-hero-meta { display:flex; flex-wrap:wrap; gap:14px; font-size:12px; color:#64748b; }

/* Stats Mini Grid */
.seo-stats-mini { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:14px; }
.seo-stat-mini { background:#fff; border:1px solid #eee7df; border-radius:12px; padding:16px 20px; box-shadow:0 4px 12px rgba(15,23,42,.03); }
.seo-stat-mini-label { font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:.05em; }
.seo-stat-mini-val { font-size:22px; font-weight:900; color:#0f172a; margin-top:4px; }

/* Table & Actions */
.seo-card { background:#fff; border:1px solid #eee7df; border-radius:14px; box-shadow:0 10px 30px rgba(15,23,42,.04); }
.seo-card-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:18px 24px; border-bottom:1px solid #f2ede8; background:#fffdfb; }
.seo-card-title { font-size:16px; font-weight:900; color:#111827; }
.seo-card-sub { font-size:12px; color:#64748b; margin-top:2px; }

.seo-btn { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:32px; padding:5px 14px; border-radius:8px; border:1px solid #d7dce2; background:#fff; color:#111827; text-decoration:none; font-size:12px; font-weight:700; cursor:pointer; transition:all .15s ease; white-space:nowrap; }
.seo-btn:hover { background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
.seo-btn-view { background:#f0f9ff; border-color:#bae6fd; color:#0369a1; }
.seo-btn-view:hover { background:#e0f2fe; color:#0284c7; }

.seo-table-wrap { overflow-x:auto; min-height:350px; }
.seo-table { width:100%; border-collapse:collapse; min-width:980px; }
.seo-table th { padding:16px 18px; text-align:left; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#64748b; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.seo-table td { padding:16px 18px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:middle; }
.seo-table tbody tr { cursor:pointer; transition:background .15s ease; }
.seo-table tbody tr:hover td { background:#f0f9ff; }

/* Status Badges */
.seo-status-pill { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:999px; font-size:11px; font-weight:800; text-transform:uppercase; }
.seo-status--delivered { background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; }
.seo-status--ontrack { background:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff; }
.seo-status--in_progress { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.seo-status--hold { background:#fffbeb; color:#b45309; border:1px solid #fde68a; }
.seo-status--cancelled { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; }

@media (max-width: 900px) {
    .seo-stats-mini { grid-template-columns:repeat(2,1fr); }
}
</style>
@endpush

@section('content')
<div class="seo-page">
    <div class="seo-topbar">
        <div>
            <div class="seo-title">{{ $lead->company_name ?: ($lead->contact_name ?: 'Customer Account') }}</div>
            <div class="seo-breadcrumb">
                <a href="{{ route('projects.dashboard', ['dashboard_type' => 'dm']) }}" style="color:#7c7c7c; text-decoration:none;">DM Dashboard</a>
                &gt; Technical SEO Projects
            </div>
        </div>
        <div>
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'dm']) }}" class="seo-btn">
                &larr; Back to DM Dashboard
            </a>
        </div>
    </div>

    <div class="seo-body">
        {{-- Account Overview Hero Banner --}}
        <section class="seo-hero">
            <div class="seo-hero-main">
                <div class="seo-hero-avatar">
                    {{ strtoupper(substr($lead->company_name ?: ($lead->contact_name ?: 'S'), 0, 1)) }}
                </div>
                <div>
                    <div class="seo-hero-title">{{ $lead->company_name ?: 'No Company Name' }}</div>
                    <div class="seo-hero-meta">
                        @if($lead->contact_name)
                            <span><strong style="color:#334155;">Contact:</strong> {{ $lead->contact_name }}</span>
                        @endif
                        @if($lead->mobile_number)
                            <span>&bull; <strong style="color:#334155;">Mobile:</strong> {{ $lead->mobile_number }}</span>
                        @endif
                        @if($lead->email)
                            <span>&bull; <strong style="color:#334155;">Email:</strong> {{ $lead->email }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <span style="display:inline-flex; align-items:center; gap:6px; background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; font-weight:800; padding:8px 16px; border-radius:999px; font-size:13px;">
                    🔄 {{ $stats['total_renewals'] }} {{ Str::plural('Time', $stats['total_renewals']) }} Renewed
                </span>
            </div>
        </section>

        {{-- Summary Stats Grid --}}
        <div class="seo-stats-mini">
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Total Technical SEO Renewals</div>
                <div class="seo-stat-mini-val" style="color:#0284c7;">{{ $stats['total_renewals'] }}</div>
            </div>
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Total Value</div>
                <div class="seo-stat-mini-val">₹{{ number_format($stats['total_value'], 2) }}</div>
            </div>
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Received</div>
                <div class="seo-stat-mini-val" style="color:#059669;">₹{{ number_format($stats['total_received'], 2) }}</div>
            </div>
            <div class="seo-stat-mini">
                <div class="seo-stat-mini-label">Balance</div>
                <div class="seo-stat-mini-val" style="color:#dc2626;">₹{{ number_format($stats['total_balance'], 2) }}</div>
            </div>
        </div>

        {{-- Renewals Table Card --}}
        <section class="seo-card">
            <div class="seo-card-head">
                <div>
                    <div class="seo-card-title">Technical SEO Renewals Breakdown</div>
                    <div class="seo-card-sub">Technical SEO renewal project records for {{ $lead->company_name ?: $lead->contact_name }}</div>
                </div>
            </div>
            <div class="seo-card-body" style="padding:0;">
                @if($projects->isNotEmpty())
                    <div class="seo-table-wrap">
                        <table class="seo-table">
                            <thead>
                                <tr>
                                    <th>Renewal #</th>
                                    <th>Project Name</th>
                                    <th>Allocated Team</th>
                                    <th>Execution Status</th>
                                    <th>Expiry / Delivery Date</th>
                                    <th>Total Value</th>
                                    <th>Received</th>
                                    <th>Balance</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projects as $index => $proj)
                                    @php
                                        $statusVal = strtolower((string) ($proj->project_execution_status ?: 'ontrack'));
                                        $statusLabels = [
                                            'ontrack'     => 'Onboard',
                                            'onboard'     => 'Onboard',
                                            'hold'        => 'Hold',
                                            'in_progress' => 'In Progress',
                                            'in progress' => 'In Progress',
                                            'delivered'   => 'Delivered',
                                            'completed'   => 'Delivered',
                                            'cancelled'   => 'Cancelled',
                                        ];
                                        $statusLabel = $statusLabels[$statusVal] ?? ucfirst($statusVal);
                                        $statusClass = match($statusVal) {
                                            'delivered', 'completed' => 'seo-status--delivered',
                                            'in_progress', 'in progress' => 'seo-status--in_progress',
                                            'hold' => 'seo-status--hold',
                                            'cancelled' => 'seo-status--cancelled',
                                            default => 'seo-status--ontrack',
                                        };
                                        $detailUrl = route('projects.show', $proj->id);
                                    @endphp
                                    <tr onclick="window.location.href='{{ $detailUrl }}'">
                                        <td>
                                            <span style="font-weight:800; color:#0284c7; background:#e0f2fe; padding:3px 9px; border-radius:6px; font-size:12px;">
                                                Renewal #{{ $index + 1 }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-weight:800; color:#0f172a;">{{ $proj->product_name ?: 'Technical SEO' }}</div>
                                            @if($proj->leadProduct?->product_name && $proj->leadProduct->product_name !== $proj->product_name)
                                                <div style="font-size:11px; color:#64748b;">{{ $proj->leadProduct->product_name }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!empty($proj->allocated_tl_names) && $proj->allocated_tl_names->isNotEmpty())
                                                <div style="font-size:12px; font-weight:700; color:#4338ca; display:flex; align-items:center; gap:4px;">
                                                    <span style="background:#e0e7ff; color:#3730a3; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:800; text-transform:uppercase;">TL</span>
                                                    {{ $proj->allocated_tl_names->implode(', ') }}
                                                </div>
                                            @endif
                                            @if(!empty($proj->allocated_employee_names) && $proj->allocated_employee_names->isNotEmpty())
                                                <div style="font-size:12px; font-weight:600; color:#334155; display:flex; align-items:center; gap:4px; margin-top:3px;">
                                                    <span style="background:#f1f5f9; color:#475569; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:800; text-transform:uppercase;">Team</span>
                                                    {{ $proj->allocated_employee_names->implode(', ') }}
                                                </div>
                                            @endif
                                            @if((empty($proj->allocated_tl_names) || $proj->allocated_tl_names->isEmpty()) && (empty($proj->allocated_employee_names) || $proj->allocated_employee_names->isEmpty()))
                                                <div style="font-size:12px; font-weight:600; color:#334155;">
                                                    {{ $proj->allocated_person_label ?: 'Not Allocated' }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="seo-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; color:#475569;">
                                                {{ $proj->project_delivery_date ? $proj->project_delivery_date->format('d M Y') : '—' }}
                                            </span>
                                        </td>
                                        <td><span style="font-weight:700; color:#0f172a;">₹{{ number_format($proj->project_value, 2) }}</span></td>
                                        <td><span style="font-weight:700; color:#059669;">₹{{ number_format($proj->received_amount, 2) }}</span></td>
                                        <td><span style="font-weight:700; color:#dc2626;">₹{{ number_format($proj->balance_amount, 2) }}</span></td>
                                        <td style="text-align:right;" onclick="event.stopPropagation();">
                                            <a href="{{ $detailUrl }}" class="seo-btn seo-btn-view" title="View details for project #{{ $proj->id }}">
                                                View Details &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="padding:40px; text-align:center; color:#64748b; font-size:14px;">
                        No Technical SEO renewal projects found for this account.
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection
