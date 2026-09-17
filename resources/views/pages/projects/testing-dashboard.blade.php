@extends('layouts.app')

@section('title', 'Testing Projects Dashboard')

@push('styles')
<style>
.tjd-page { min-height: 100vh; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); font-family: 'Inter', system-ui, -apple-system, sans-serif; }
.tjd-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px 32px; background: #ffffff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); }
.tjd-title { font-size: 24px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 12px; letter-spacing: -0.02em; }
.tjd-title-icon { width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 8px 16px rgba(2,132,199,0.25); }
.tjd-breadcrumb { margin-top: 4px; font-size: 13px; color: #64748b; font-weight: 500; }
.tjd-chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 999px; background: #f0f9ff; border: 1px solid #bae6fd; color: #0284c7; font-size: 12.5px; font-weight: 700; box-shadow: 0 2px 8px rgba(2,132,199,0.08); }
.tjd-pulse-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 0 0 rgba(16,185,129,0.7); animation: tjdPulse 1.8s infinite; }
@keyframes tjdPulse { 0% { box-shadow: 0 0 0 0 rgba(16,185,129,0.7); } 70% { box-shadow: 0 0 0 8px rgba(16,185,129,0); } 100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); } }

.tjd-body { padding: 28px 32px 48px; display: grid; gap: 26px; }

/* Stat Cards Grid - 5 Cards in a Single Row */
.tjd-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 16px; }
.tjd-stat-card { position: relative; overflow: hidden; background: #ffffff; border-radius: 18px; padding: 16px 18px; border: 2px solid #e2e8f0; box-shadow: 0 4px 18px rgba(15,23,42,0.03); cursor: pointer; transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; min-height: 125px; }
.tjd-stat-card:hover { transform: translateY(-4px); box-shadow: 0 16px 32px rgba(15,23,42,0.08); border-color: #cbd5e1; }
.tjd-stat-card.is-active { border-color: #0284c7; box-shadow: 0 0 0 4px rgba(2,132,199,0.18), 0 12px 28px rgba(2,132,199,0.12); }
.tjd-stat-header { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.tjd-stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 19px; font-weight: 700; transition: transform 0.25s ease; flex-shrink: 0; }
.tjd-stat-card:hover .tjd-stat-icon { transform: scale(1.08); }
.tjd-stat-badge { font-size: 11px; font-weight: 800; padding: 3px 9px; border-radius: 20px; white-space: nowrap; }
.tjd-stat-value { font-size: 28px; font-weight: 900; color: #0f172a; line-height: 1; letter-spacing: -0.03em; margin-top: 10px; }
.tjd-stat-label { font-size: 13px; font-weight: 700; color: #475569; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

@media (max-width: 1280px) {
    .tjd-stats { gap: 12px; }
    .tjd-stat-card { padding: 14px 14px; min-height: 115px; border-radius: 14px; }
    .tjd-stat-icon { width: 36px; height: 36px; font-size: 17px; }
    .tjd-stat-badge { font-size: 10px; padding: 2px 7px; }
    .tjd-stat-value { font-size: 24px; }
    .tjd-stat-label { font-size: 12px; }
}
@media (max-width: 991px) {
    .tjd-stats { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 640px) {
    .tjd-stats { grid-template-columns: 1fr; }
}

/* Filter Tabs & Search Bar */
.tjd-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: #ffffff; padding: 14px 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02); }
.tjd-pills { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.tjd-pill { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 12px; font-size: 13px; font-weight: 700; text-decoration: none; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; transition: all 0.2s ease; }
.tjd-pill:hover { background: #f1f5f9; color: #0f172a; }
.tjd-pill.is-active { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; border-color: #0284c7; box-shadow: 0 4px 14px rgba(2,132,199,0.25); }
.tjd-pill-count { padding: 2px 8px; border-radius: 20px; font-size: 11.5px; font-weight: 800; background: rgba(0,0,0,0.06); }
.tjd-pill.is-active .tjd-pill-count { background: rgba(255,255,255,0.22); color: #ffffff; }

.tjd-search-box { display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 8px 14px; min-width: 280px; transition: border-color 0.2s ease; }
.tjd-search-box:focus-within { border-color: #0284c7; background: #ffffff; }
.tjd-search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; font-weight: 600; color: #0f172a; }
.tjd-search-box input::placeholder { color: #94a3b8; }

/* Table Container Card */
.tjd-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 24px rgba(15,23,42,0.04); }
.tjd-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 20px 26px; border-bottom: 1px solid #e2e8f0; background: #ffffff; }
.tjd-card-title { font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }

/* Table Styling */
.tjd-table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; }
.tjd-table th { padding: 16px 24px; font-size: 11.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.tjd-table td { padding: 18px 24px; font-size: 13.5px; color: #1e293b; border-bottom: 1px solid #f1f5f9; vertical-align: middle; transition: background 0.15s ease; }
.tjd-table tr:last-child td { border-bottom: none; }
.tjd-table tbody tr:hover td { background: #f0f9ff; }

.tjd-dev-chip { display: inline-flex; align-items: center; gap: 9px; padding: 5px 12px 5px 6px; border-radius: 30px; background: #f8fafc; border: 1px solid #e2e8f0; }
.tjd-dev-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(2,132,199,0.2); }

.tjd-btn-view { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 12px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; font-size: 13px; font-weight: 700; text-decoration: none; transition: all 0.22s ease; border: none; box-shadow: 0 4px 12px rgba(2,132,199,0.22); }
.tjd-btn-view:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(2,132,199,0.32); color: #ffffff; }

/* Pagination Footer */
.tjd-pagination-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #ffffff; }
.tjd-pagination-info { font-size: 13px; font-weight: 600; color: #64748b; }
.tjd-pagination-info strong { color: #0f172a; font-weight: 800; }
.tjd-pagination-right { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.tjd-per-page { display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 700; color: #64748b; }
.tjd-per-page select { padding: 6px 12px; border-radius: 8px; border: 1.5px solid #cbd5e1; font-size: 12.5px; font-weight: 700; color: #0f172a; background: #f8fafc; outline: none; cursor: pointer; transition: all 0.2s ease; }
.tjd-per-page select:focus { border-color: #0284c7; background: #ffffff; }
.tjd-pagination-nav { display: inline-flex; align-items: center; gap: 4px; }
.tjd-page-btn { min-width: 34px; height: 34px; padding: 0 10px; border-radius: 10px; font-size: 12.5px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; transition: all 0.2s ease; }
.tjd-page-btn:hover:not(.is-disabled) { background: #f1f5f9; color: #0284c7; border-color: #cbd5e1; }
.tjd-page-btn.is-active { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; border-color: #0284c7; box-shadow: 0 3px 10px rgba(2,132,199,0.25); }
.tjd-page-btn.is-disabled { opacity: 0.45; cursor: not-allowed; background: #f8fafc; color: #94a3b8; }
.tjd-page-ellipsis { padding: 0 6px; color: #94a3b8; font-weight: 700; }
</style>
@endpush

@section('content')
<div class="tjd-page">
    {{-- Header Topbar --}}
    <div class="tjd-topbar">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div class="tjd-title-icon">🧪</div>
            <div>
                <div class="tjd-title">Testing Department Projects Dashboard</div>
                <div class="tjd-breadcrumb">QA &amp; Software Testing Handover Projects Management Workspace</div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="tjd-chip">
                <span class="tjd-pulse-dot"></span> Active QA Workspace
            </div>
        </div>
    </div>

    <div class="tjd-body">
        {{-- Summary Stat Cards (5 Cards Single Row) --}}
        <div class="tjd-stats">
            {{-- Open Projects Card --}}
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'open']) }}" class="tjd-stat-card {{ $activeStatus === 'open' ? 'is-active' : '' }}" style="background: linear-gradient(135deg, #ffffff 0%, #fff7ed 100%);">
                <div class="tjd-stat-header">
                    <div class="tjd-stat-icon" style="background: #ffedd5; color: #ea580c;">
                        <i class="bi bi-folder-symlink-fill"></i>
                    </div>
                    <span class="tjd-stat-badge" style="background: #ffedd5; color: #c2410c;">New Handover</span>
                </div>
                <div>
                    <div class="tjd-stat-value" style="color: #c2410c;">{{ $openCount }}</div>
                    <div class="tjd-stat-label">Open Projects</div>
                </div>
            </a>

            {{-- Ongoing Projects Card --}}
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'ongoing']) }}" class="tjd-stat-card {{ $activeStatus === 'ongoing' ? 'is-active' : '' }}" style="background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);">
                <div class="tjd-stat-header">
                    <div class="tjd-stat-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="bi bi-play-circle-fill"></i>
                    </div>
                    <span class="tjd-stat-badge" style="background: #e0f2fe; color: #0369a1;">In Progress</span>
                </div>
                <div>
                    <div class="tjd-stat-value" style="color: #0369a1;">{{ $ongoingCount }}</div>
                    <div class="tjd-stat-label">Ongoing Projects</div>
                </div>
            </a>

            {{-- Retesting Projects Card --}}
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'retesting']) }}" class="tjd-stat-card {{ $activeStatus === 'retesting' ? 'is-active' : '' }}" style="background: linear-gradient(135deg, #ffffff 0%, #f5f3ff 100%);">
                <div class="tjd-stat-header">
                    <div class="tjd-stat-icon" style="background: #ede9fe; color: #7c3aed;">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <span class="tjd-stat-badge" style="background: #ede9fe; color: #6d28d9;">Fix Verified</span>
                </div>
                <div>
                    <div class="tjd-stat-value" style="color: #6d28d9;">{{ $retestingCount }}</div>
                    <div class="tjd-stat-label">Retesting Projects</div>
                </div>
            </a>

            {{-- Completed Projects Card --}}
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'completed']) }}" class="tjd-stat-card {{ $activeStatus === 'completed' ? 'is-active' : '' }}" style="background: linear-gradient(135deg, #ffffff 0%, #ecfdf5 100%);">
                <div class="tjd-stat-header">
                    <div class="tjd-stat-icon" style="background: #d1fae5; color: #059669;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <span class="tjd-stat-badge" style="background: #d1fae5; color: #047857;">QA Passed</span>
                </div>
                <div>
                    <div class="tjd-stat-value" style="color: #047857;">{{ $completedCount }}</div>
                    <div class="tjd-stat-label">Completed Projects</div>
                </div>
            </a>

            {{-- Ready Launch Projects Card --}}
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'ready_launch']) }}" class="tjd-stat-card {{ in_array($activeStatus, ['ready_launch', 'ready_to_launch']) ? 'is-active' : '' }}" style="background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);">
                <div class="tjd-stat-header">
                    <div class="tjd-stat-icon" style="background: #bbf7d0; color: #16a34a;">
                        <i class="bi bi-rocket-takeoff-fill"></i>
                    </div>
                    <span class="tjd-stat-badge" style="background: #bbf7d0; color: #15803d;">🚀 Launch</span>
                </div>
                <div>
                    <div class="tjd-stat-value" style="color: #15803d;">{{ $readyLaunchCount ?? 0 }}</div>
                    <div class="tjd-stat-label">Ready Launch</div>
                </div>
            </a>
        </div>

        {{-- Toolbar: Tabs & Search Bar --}}
        <div class="tjd-toolbar">
            <div class="tjd-pills">
                <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'open']) }}" class="tjd-pill {{ $activeStatus === 'open' ? 'is-active' : '' }}">
                    <span>🟧 Open Projects</span>
                    <span class="tjd-pill-count">{{ $openCount }}</span>
                </a>
                <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'ongoing']) }}" class="tjd-pill {{ $activeStatus === 'ongoing' ? 'is-active' : '' }}">
                    <span>🟦 Ongoing Projects</span>
                    <span class="tjd-pill-count">{{ $ongoingCount }}</span>
                </a>
                <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'retesting']) }}" class="tjd-pill {{ $activeStatus === 'retesting' ? 'is-active' : '' }}">
                    <span>🟪 Retesting Projects</span>
                    <span class="tjd-pill-count">{{ $retestingCount }}</span>
                </a>
                <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'completed']) }}" class="tjd-pill {{ $activeStatus === 'completed' ? 'is-active' : '' }}">
                    <span>🟩 Completed Projects</span>
                    <span class="tjd-pill-count">{{ $completedCount }}</span>
                </a>
                <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'ready_launch']) }}" class="tjd-pill {{ in_array($activeStatus, ['ready_launch', 'ready_to_launch']) ? 'is-active' : '' }}">
                    <span>🚀 Ready Launch</span>
                    <span class="tjd-pill-count">{{ $readyLaunchCount ?? 0 }}</span>
                </a>
            </div>

            <form method="GET" action="{{ route('projects.dashboard') }}" style="margin: 0;">
                <input type="hidden" name="dashboard_type" value="testing">
                <input type="hidden" name="status" value="{{ $activeStatus }}">
                @if(request('per_page'))
                    <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                @endif
                <div class="tjd-search-box">
                    <i class="bi bi-search" style="color: #94a3b8; font-size: 14px;"></i>
                    <input type="text" name="search" id="tjdSearchInput" value="{{ $search ?? '' }}" onkeyup="filterTjdTable()" placeholder="Filter or press enter to search...">
                    @if(!empty($search))
                        <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => $activeStatus]) }}" style="color: #94a3b8; text-decoration: none; font-size: 16px; font-weight: 800; line-height: 1; padding: 0 4px;" title="Clear search">&times;</a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Projects Table Card Container --}}
        <div class="tjd-card">
            <div class="tjd-card-head">
                <div class="tjd-card-title">
                    <i class="bi bi-shield-check" style="color: #0284c7; font-size: 20px;"></i>
                    @switch($activeStatus)
                        @case('ongoing')
                            Ongoing Testing Projects
                            @break
                        @case('retesting')
                            Retesting Projects
                            @break
                        @case('completed')
                            Completed Testing Projects
                            @break
                        @case('ready_launch')
                        @case('ready_to_launch')
                            Ready to Launch Projects
                            @break
                        @default
                            Open Testing Projects (New Handovers)
                    @endswitch
                    <span style="font-size: 12px; font-weight: 800; color: #0284c7; background: #e0f2fe; padding: 3px 12px; border-radius: 20px; border: 1px solid #bae6fd;">
                        {{ $handovers->total() }} Projects
                    </span>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="tjd-table" id="tjdProjectsTable">
                    <thead>
                        <tr>
                            <th>Project &amp; Client Info</th>
                            <th>Testing Handover Date</th>
                            <th>Developer Name</th>
                            <th>Target Delivery Date</th>
                            <th style="text-align: center;">Total Bugs</th>
                            <th style="text-align: center;">Resolved Bugs</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($handovers as $handover)
                            @php
                                $proj = $handover->productionInitiation;
                                $projectName = $proj?->leadProduct?->name 
                                    ?? $proj?->product?->name 
                                    ?? ($proj?->lead?->company_name ? $proj->lead->company_name . ' Project' : 'Project #' . $handover->production_initiation_id);
                                $movedDate = $handover->created_at ? $handover->created_at->format('d M Y, h:i A') : 'N/A';
                                $devName = $handover->movedBy?->name ?? 'Developer Team';
                                $deliveryDate = $proj?->project_delivery_date ? \Carbon\Carbon::parse($proj->project_delivery_date)->format('d M Y') : 'N/A';
                                
                                $allBugs = $proj?->bugs ?? collect();
                                $totalBugsCount = $allBugs->count();
                                $resolvedBugsCount = $allBugs->whereIn('status', ['fixed', 'closed', 'resolved'])->count();
                            @endphp
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 38px; height: 38px; border-radius: 12px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; color: #0284c7; font-size: 18px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                            💻
                                        </div>
                                        <div>
                                            <div style="font-weight: 800; font-size: 14px; color: #0f172a; letter-spacing: -0.01em;">{{ $projectName }}</div>
                                            @if($proj?->lead?->company_name)
                                                <div style="font-size: 12px; color: #64748b; margin-top: 2px; font-weight: 600;">🏢 {{ $proj->lead->company_name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; color: #334155; background: #f8fafc; padding: 6px 12px; border-radius: 10px; border: 1px solid #e2e8f0; font-size: 12.5px;">
                                        <i class="bi bi-calendar-check" style="color: #0284c7;"></i> {{ $movedDate }}
                                    </div>
                                </td>
                                <td>
                                    <div class="tjd-dev-chip">
                                        <div class="tjd-dev-avatar">
                                            {{ strtoupper(substr($devName, 0, 1)) }}
                                        </div>
                                        <span style="font-weight: 700; color: #1e293b; font-size: 13px;">{{ $devName }}</span>
                                    </div>
                                </td>
                                <td>
                                    @if($deliveryDate !== 'N/A')
                                        <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 800; color: #dc2626; background: #fef2f2; padding: 6px 12px; border-radius: 10px; border: 1px solid #fecaca; font-size: 12.5px;">
                                            <i class="bi bi-clock-fill"></i> {{ $deliveryDate }}
                                        </span>
                                    @else
                                        <span style="color: #94a3b8; font-weight: 600;">N/A</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($totalBugsCount > 0)
                                        <span style="display: inline-flex; align-items: center; gap: 5px; font-weight: 800; color: #dc2626; background: #fef2f2; padding: 5px 12px; border-radius: 20px; border: 1px solid #fecaca; font-size: 12.5px;">
                                            <i class="bi bi-bug-fill"></i> {{ $totalBugsCount }} Bugs
                                        </span>
                                    @else
                                        <span style="display: inline-flex; align-items: center; gap: 5px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 5px 12px; border-radius: 20px; border: 1px solid #a7f3d0; font-size: 12.5px;">
                                            <i class="bi bi-check-circle-fill"></i> 0 Bugs
                                        </span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($resolvedBugsCount > 0)
                                        <span style="display: inline-flex; align-items: center; gap: 5px; font-weight: 800; color: #059669; background: #ecfdf5; padding: 5px 12px; border-radius: 20px; border: 1px solid #a7f3d0; font-size: 12.5px;">
                                            <i class="bi bi-check-circle-fill"></i> {{ $resolvedBugsCount }} Fixed
                                        </span>
                                    @else
                                        <span style="color: #94a3b8; font-weight: 600; font-size: 12.5px;">0 Fixed</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <a href="{{ route('projects.testing-details', ['productionInitiation' => $handover->production_initiation_id]) }}" class="tjd-btn-view">
                                        <i class="bi bi-eye-fill"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 54px 20px; color: #64748b;">
                                    <div style="width: 64px; height: 64px; border-radius: 20px; background: #f0f9ff; color: #0284c7; font-size: 32px; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; box-shadow: 0 4px 14px rgba(2,132,199,0.15);">
                                        🧪
                                    </div>
                                    <div style="font-weight: 800; font-size: 16px; color: #1e293b;">No Projects Found Under This Status</div>
                                    <div style="font-size: 13px; color: #64748b; margin-top: 4px;">When development team transfers projects to testing, they will appear here.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Table Pagination Footer --}}
            @if($handovers->total() > 0)
            <div class="tjd-pagination-footer">
                <div class="tjd-pagination-info">
                    Showing <strong>{{ $handovers->firstItem() ?? 0 }}</strong> to <strong>{{ $handovers->lastItem() ?? 0 }}</strong> of <strong>{{ $handovers->total() }}</strong> projects
                </div>

                <div class="tjd-pagination-right">
                    <div class="tjd-per-page">
                        <span>Show:</span>
                        <select onchange="changeTjdPerPage(this.value)">
                            <option value="10" {{ $handovers->perPage() == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ $handovers->perPage() == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ $handovers->perPage() == 50 ? 'selected' : '' }}>50</option>
                        </select>
                    </div>

                    @if($handovers->hasPages())
                        <nav class="tjd-pagination-nav">
                            {{-- Previous Page Link --}}
                            @if($handovers->onFirstPage())
                                <span class="tjd-page-btn is-disabled" title="Previous Page">
                                    <i class="bi bi-chevron-left"></i>
                                </span>
                            @else
                                <a href="{{ $handovers->previousPageUrl() }}" class="tjd-page-btn" title="Previous Page">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            @endif

                            {{-- Page Numbers --}}
                            @php
                                $cur = $handovers->currentPage();
                                $last = $handovers->lastPage();
                                $start = max(1, $cur - 2);
                                $end = min($last, $cur + 2);
                            @endphp

                            @if($start > 1)
                                <a href="{{ $handovers->url(1) }}" class="tjd-page-btn">1</a>
                                @if($start > 2)
                                    <span class="tjd-page-ellipsis">&hellip;</span>
                                @endif
                            @endif

                            @for($p = $start; $p <= $end; $p++)
                                @if($p == $cur)
                                    <span class="tjd-page-btn is-active">{{ $p }}</span>
                                @else
                                    <a href="{{ $handovers->url($p) }}" class="tjd-page-btn">{{ $p }}</a>
                                @endif
                            @endfor

                            @if($end < $last)
                                @if($end < $last - 1)
                                    <span class="tjd-page-ellipsis">&hellip;</span>
                                @endif
                                <a href="{{ $handovers->url($last) }}" class="tjd-page-btn">{{ $last }}</a>
                            @endif

                            {{-- Next Page Link --}}
                            @if($handovers->hasMorePages())
                                <a href="{{ $handovers->nextPageUrl() }}" class="tjd-page-btn" title="Next Page">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            @else
                                <span class="tjd-page-btn is-disabled" title="Next Page">
                                    <i class="bi bi-chevron-right"></i>
                                </span>
                            @endif
                        </nav>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- Employee-wise Timesheet & Tasks for Testing --}}
        @include('pages.projects.partials.employee_timesheet_tasks')
    </div>
</div>

<script>
function filterTjdTable() {
    const input = document.getElementById('tjdSearchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('tjdProjectsTable');
    const trs = table.getElementsByTagName('tr');

    for (let i = 1; i < trs.length; i++) {
        const tr = trs[i];
        if (tr.getElementsByTagName('td').length === 0) continue;
        const textContent = tr.textContent.toLowerCase();
        if (textContent.indexOf(filter) > -1) {
            tr.style.display = '';
        } else {
            tr.style.display = 'none';
        }
    }
}

function changeTjdPerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', perPage);
    url.searchParams.set('projects_page', 1);
    window.location.href = url.toString();
}
</script>
@endsection
