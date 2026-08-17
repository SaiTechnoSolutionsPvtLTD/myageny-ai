@extends('layouts.app')

@section('title', 'Account Details')

@push('styles')
<style>
.pjd-page { min-height:100%; background:linear-gradient(180deg,#fffaf5 0%,#f8fafc 100%); font-family:'Inter',sans-serif; }
.pjd-topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:22px 28px; background:#fff; border-bottom:1px solid #eee7df; }
.pjd-title { font-size:24px; font-weight:900; color:#111827; }
.pjd-breadcrumb { margin-top:4px; font-size:12px; color:#7c7c7c; }
.pjd-chip { display:inline-flex; align-items:center; gap:8px; padding:10px 14px; border-radius:999px; background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; font-size:12px; font-weight:800; }
.pjd-body { padding:22px 28px 34px; display:grid; gap:20px; }

.pjd-lead-info { display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:16px; background:#fff; border:1px solid #eee7df; border-radius:12px; padding:20px; box-shadow:0 10px 25px rgba(15,23,42,.03); }
.pjd-info-item { display:grid; gap:4px; }
.pjd-info-label { font-size:10px; font-weight:800; text-transform:uppercase; color:#64748b; letter-spacing:.08em; }
.pjd-info-value { font-size:14px; font-weight:700; color:#1e293b; }

.pjd-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:20px; }
.pjd-stat { position:relative; overflow:hidden; background:var(--stat-gradient); border:none; border-radius:16px; padding:24px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.1),0 8px 10px -6px rgba(0,0,0,0.05); display:flex; flex-direction:column; justify-content:space-between; transition:all 0.3s cubic-bezier(0.4,0,0.2,1); color:#fff; }
.pjd-stat:hover { transform:translateY(-5px); box-shadow:0 20px 25px -5px rgba(0,0,0,0.15),0 10px 10px -5px rgba(0,0,0,0.08); }
.pjd-stat-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
.pjd-stat-icon { display:flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:12px; background:rgba(255,255,255,0.2); color:#fff; font-size:20px; backdrop-filter:blur(4px); }
.pjd-stat-label { font-size:12px; font-weight:800; color:rgba(255,255,255,0.9); text-transform:uppercase; letter-spacing:.06em; text-shadow:0 1px 2px rgba(0,0,0,0.1); }
.pjd-stat-body { display:flex; flex-direction:column; gap:4px; }
.pjd-stat-value { font-size:32px; font-weight:900; color:#fff; line-height:1.2; text-shadow:0 2px 4px rgba(0,0,0,0.1); }
.pjd-stat-footer { margin-top:14px; padding-top:14px; border-top:1px dashed rgba(255,255,255,0.25); font-size:13px; color:rgba(255,255,255,0.9); font-weight:600; text-shadow:0 1px 2px rgba(0,0,0,0.1); }

.pjd-card { background:#fff; border:1px solid #eee7df; border-radius:10px; overflow:hidden; box-shadow:0 14px 34px rgba(15,23,42,.05); }
.pjd-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:16px 20px; border-bottom:1px solid #f2ede8; background:#fffdfb; }
.pjd-card-title { font-size:16px; font-weight:900; color:#111827; }
.pjd-card-sub { margin-top:4px; font-size:12px; color:#7c7c7c; }
.pjd-card-body { padding:20px; }

.pjd-table-wrap { overflow-x:auto; }
.pjd-table { width:100%; border-collapse:collapse; min-width:880px; }
.pjd-table th { padding:12px 14px; text-align:left; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; color:#7c7c7c; background:#fafaf9; border-bottom:1px solid #f2ede8; }
.pjd-table td { padding:14px; border-bottom:1px solid #f6f2ee; font-size:13px; color:#111827; vertical-align:middle; }
.pjd-table tbody tr:hover td { background:#fffaf5; }

.pjd-product { font-weight:800; color:#111827; text-decoration:none; }
.pjd-meta { margin-top:4px; font-size:11px; color:#64748b; }
.pjd-pill { display:inline-flex; align-items:center; padding:6px 10px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid transparent; text-transform:uppercase; letter-spacing:.06em; }
.pjd-pill.pending { color:#b45309; background:#fff7ed; border-color:#fed7aa; }
.pjd-pill.allocated { color:#166534; background:#f0fdf4; border-color:#bbf7d0; }
.pjd-btn { display:inline-flex; align-items:center; justify-content:center; min-height:36px; padding:6px 14px; border-radius:8px; border:1px solid #d7dce2; background:#fff; color:#111827; text-decoration:none; font-size:13px; font-weight:800; cursor:pointer; }
.pjd-btn-primary { background:#ea580c; border-color:#ea580c; color:#fff; }

.badge-overdue { display:inline-flex; align-items:center; gap:4px; background:#fef2f2; border:1px solid #fecaca; color:#dc2626; font-size:11px; font-weight:800; padding:4px 8px; border-radius:999px; text-transform:uppercase; }
.badge-renewal { background:#f0fdfa; border:1px solid #99f6e4; color:#0d9488; }
</style>
@endpush

@section('content')
<div class="pjd-page">
    <div class="pjd-topbar">
        <div>
            <div class="pjd-title">{{ $lead->company_name ?: 'Account Details' }}</div>
            <div class="pjd-breadcrumb">Modules > Projects > My Accounts > Details</div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('projects.my-accounts') }}" class="pjd-btn">Back to Accounts</a>
            <div class="pjd-chip">Account details & renewals overview</div>
        </div>
    </div>

    <div class="pjd-body">
        {{-- Lead Information --}}
        <section class="pjd-lead-info">
            <div class="pjd-info-item">
                <div class="pjd-info-label">Company Name</div>
                <div class="pjd-info-value" style="color:#ea580c; font-size:16px;">{{ $lead->company_name ?: '—' }}</div>
            </div>
            <div class="pjd-info-item">
                <div class="pjd-info-label">Contact Person</div>
                <div class="pjd-info-value">{{ $lead->contact_name ?: '—' }}</div>
            </div>
            <div class="pjd-info-item">
                <div class="pjd-info-label">Mobile Number</div>
                <div class="pjd-info-value">{{ $lead->mobile_number ?: '—' }}</div>
            </div>
            <div class="pjd-info-item">
                <div class="pjd-info-label">Email Address</div>
                <div class="pjd-info-value">{{ $lead->email ?: '—' }}</div>
            </div>
            <div class="pjd-info-item" style="margin-top:12px;">
                <div class="pjd-info-label">Lead Source</div>
                <div class="pjd-info-value">{{ $lead->lead_source ?: '—' }}</div>
            </div>
            <div class="pjd-info-item" style="margin-top:12px;">
                <div class="pjd-info-label">Lead Status</div>
                <div class="pjd-info-value">
                    <span class="pjd-pill" style="background: {{ $lead->status_color['bg'] ?? '#f1f5f9' }}; color: {{ $lead->status_color['text'] ?? '#475569' }}; border-color: {{ $lead->status_color['border'] ?? '#cbd5e1' }}">
                        {{ $lead->lead_status ?: '—' }}
                    </span>
                </div>
            </div>
            <div class="pjd-info-item" style="margin-top:12px;">
                <div class="pjd-info-label">Priority</div>
                <div class="pjd-info-value">
                    <span class="pjd-pill" style="background: {{ $lead->priority_color['bg'] ?? '#f1f5f9' }}; color: {{ $lead->priority_color['text'] ?? '#475569' }}; border-color: {{ $lead->priority_color['border'] ?? '#cbd5e1' }}">
                        {{ $lead->priority_label }}
                    </span>
                </div>
            </div>
            <div class="pjd-info-item" style="margin-top:12px;">
                <div class="pjd-info-label">Deal Value</div>
                <div class="pjd-info-value" style="color:#0f172a;">{{ $lead->formatted_deal_value }}</div>
            </div>
        </section>

        {{-- KPI Summaries --}}
        <section class="pjd-stats" style="grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px;">
            {{-- Total Renewals --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #0284c7 0%, #38bdf8 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Total Renewals</span>
                    <span class="pjd-stat-icon">🔄</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $stats['total_renewals'] }}</span>
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
                    <span class="pjd-stat-value">{{ $stats['total_posters'] }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Comp: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800;">{{ $stats['completed_posters'] }}</span> | 
                    Pend: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800;">{{ $stats['pending_posters'] }}</span> |
                    Overdue: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800; color: #fee2e2;">{{ $stats['overdue_posters'] }}</span>
                </div>
            </div>

            {{-- Videos Summary --}}
            <div class="pjd-stat" style="--stat-gradient: linear-gradient(135deg, #7c3aed 0%, #c084fc 100%);">
                <div class="pjd-stat-header">
                    <span class="pjd-stat-label">Videos Summary</span>
                    <span class="pjd-stat-icon">🎥</span>
                </div>
                <div class="pjd-stat-body">
                    <span class="pjd-stat-value">{{ $stats['total_videos'] }}</span>
                </div>
                <div class="pjd-stat-footer">
                    Comp: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800;">{{ $stats['completed_videos'] }}</span> | 
                    Pend: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800;">{{ $stats['pending_videos'] }}</span> |
                    Overdue: <span style="background: rgba(255, 255, 255, 0.22); padding: 2px 6px; border-radius: 4px; font-weight: 800; color: #fee2e2;">{{ $stats['overdue_videos'] }}</span>
                </div>
            </div>
        </section>

        {{-- Associated Projects Section --}}
        <h2 style="font-size:18px; font-weight:900; color:#111827; margin: 15px 0 5px 0;">Renewals & Associated Projects</h2>

        <div class="pjd-card">
            <div class="pjd-card-body" style="padding:0;">
                @if($tableRows->isNotEmpty())
                    <div class="pjd-table-wrap">
                        <table class="pjd-table">
                            <thead>
                                <tr>

                                    <th>Project / Product Name</th>
                                    <th>Department</th>
                                    <th>Start Date</th>
                                    <th>Delivery Date</th>
                                    <th>Allocated Employee</th>
                                    <th>Onboarded Counts</th>
                                    <th>Delivered Counts</th>
                                    <th>Status</th>
                                    <th>Overdue Alert</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tableRows as $row)
                                    <tr>

                                        @if($row['has_project'])
                                            @php $project = $row['project']; @endphp
                                            <td>
                                                <div class="pjd-product">{{ $project->product_name }}</div>
                                                <div class="pjd-meta">ID: #{{ $project->id }}</div>
                                            </td>
                                            <td>
                                                <div style="font-weight:600; color:#475569;">{{ $project->department?->name ?: '—' }}</div>
                                            </td>
                                            <td>
                                                <div style="color:#475569; white-space: nowrap;">{{ $project->start_date ? $project->start_date->format('d M Y') : '—' }}</div>
                                            </td>
                                            <td>
                                                <div style="color:#475569; white-space: nowrap;">{{ $project->project_delivery_date ? $project->project_delivery_date->format('d M Y') : '—' }}</div>
                                            </td>
                                            <td>
                                                <div style="font-weight:600; color:#475569; font-size:12px;">{{ $project->allocated_names }}</div>
                                            </td>
                                            <td>
                                                <span style="font-weight:700; color:#475569; white-space: nowrap;">P: {{ $project->onboarded_posters }} / V: {{ $project->onboarded_videos }}</span>
                                            </td>
                                            <td>
                                                <span style="font-weight:700; color:#166534; white-space: nowrap;">P: {{ $project->delivered_posters }} / V: {{ $project->delivered_videos }}</span>
                                            </td>
                                            <td>
                                                <span class="pjd-pill {{ $project->project_execution_status === 'delivered' ? 'allocated' : 'pending' }}">
                                                    {{ $project->project_execution_status ?: 'Pending' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($project->is_overdue)
                                                    <span class="badge-overdue">
                                                        ⚠️ Overdue
                                                    </span>
                                                @else
                                                    <span style="color:#64748b; font-size:12px; font-weight:600;">No</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('projects.show', ['productionInitiation' => $project->id]) }}" class="pjd-btn">
                                                    View Project
                                                </a>
                                            </td>
                                        @else
                                            <td>
                                                <div class="pjd-product">{{ $row['renewal_name'] ?: 'Purchased Product' }}</div>
                                                <div class="pjd-meta" style="color:#d97706; font-weight:700;">Initiation Pending</div>
                                            </td>
                                            <td>
                                                <div style="font-weight:600; color:#94a3b8; font-style:italic;">Not Initiated</div>
                                            </td>
                                            <td style="vertical-align: middle;">—</td>
                                            <td style="vertical-align: middle;">—</td>
                                            <td style="vertical-align: middle;">—</td>
                                            <td style="vertical-align: middle;"><span style="font-weight:700; color:#94a3b8; white-space: nowrap;">P: 0 / V: 0</span></td>
                                            <td style="vertical-align: middle;"><span style="font-weight:700; color:#94a3b8; white-space: nowrap;">P: 0 / V: 0</span></td>
                                            <td style="vertical-align: middle;">
                                                <span class="pjd-pill pending">
                                                    Not Initiated
                                                </span>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span style="color:#64748b; font-size:12px; font-weight:600;">No</span>
                                            </td>
                                            <td style="vertical-align: middle;">
                                                <span style="color:#94a3b8; font-size: 12px; font-style: italic;">—</span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align:center; padding:40px; color:#64748b;">No renewals or associated projects found for this account.</div>
                @endif
            </div>
        </div>

        {{-- Section 2: Non-Count Renewal Projects (count_wise_report = false & is_this_renewal_product = true) --}}
        <h2 style="font-size:18px; font-weight:900; color:#111827; margin: 28px 0 8px 0; display:flex; align-items:center; gap:8px;">
            <span>📢</span> Non-Count Renewal Projects (Lead Generation & Campaign Management)
        </h2>

        <div class="pjd-card">
            <div class="pjd-card-body" style="padding:0;">
                @if(isset($nonCountWiseProjects) && $nonCountWiseProjects->isNotEmpty())
                    <div class="pjd-table-wrap">
                        <table class="pjd-table">
                            <thead>
                                <tr>
                                    <th>Project / Product Name</th>
                                    <th>Department</th>
                                    <th>Start Date</th>
                                    <th>Delivery Date</th>
                                    <th>Allocated Employee</th>
                                    <th>Status</th>
                                    <th>Overdue Alert</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($nonCountWiseProjects as $project)
                                    <tr>
                                        <td>
                                            <div class="pjd-product">{{ $project->product_name ?: ($project->leadProduct?->product_name ?? '—') }}</div>
                                            <div class="pjd-meta">ID: #{{ $project->id }}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600; color:#475569;">{{ $project->department?->name ?: 'Digital Marketing' }}</div>
                                        </td>
                                        <td>
                                            <div style="color:#475569; white-space: nowrap;">{{ $project->start_date ? $project->start_date->format('d M Y') : '—' }}</div>
                                        </td>
                                        <td>
                                            <div style="color:#475569; white-space: nowrap;">{{ $project->project_delivery_date ? $project->project_delivery_date->format('d M Y') : '—' }}</div>
                                        </td>
                                        <td>
                                            <div style="font-weight:600; color:#475569; font-size:12px;">{{ $project->allocated_names }}</div>
                                        </td>
                                        <td>
                                            <span class="pjd-pill {{ $project->project_execution_status === 'delivered' ? 'allocated' : 'pending' }}">
                                                {{ $project->project_execution_status ?: 'Pending' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($project->is_overdue)
                                                <span class="badge-overdue">
                                                    ⚠️ Overdue
                                                </span>
                                            @else
                                                <span style="color:#64748b; font-size:12px; font-weight:600;">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('projects.show', ['productionInitiation' => $project->id]) }}" class="pjd-btn">
                                                View Project
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align:center; padding:35px; color:#64748b; font-size:13px;">
                        No non-count renewal projects (count_wise_report = false & is_this_renewal_product = true) found for this account.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
