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

/* Stat Cards Grid */
.tjd-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
.tjd-stat-card { position: relative; overflow: hidden; background: #ffffff; border-radius: 20px; padding: 22px 24px; border: 2px solid #e2e8f0; box-shadow: 0 4px 18px rgba(15,23,42,0.03); cursor: pointer; transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; display: flex; flex-direction: column; justify-content: space-between; min-height: 140px; }
.tjd-stat-card:hover { transform: translateY(-4px); box-shadow: 0 16px 32px rgba(15,23,42,0.08); border-color: #cbd5e1; }
.tjd-stat-card.is-active { border-color: #0284c7; box-shadow: 0 0 0 4px rgba(2,132,199,0.18), 0 12px 28px rgba(2,132,199,0.12); }
.tjd-stat-header { display: flex; align-items: center; justify-content: space-between; }
.tjd-stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; transition: transform 0.25s ease; }
.tjd-stat-card:hover .tjd-stat-icon { transform: scale(1.08); }
.tjd-stat-value { font-size: 32px; font-weight: 900; color: #0f172a; line-height: 1; letter-spacing: -0.03em; margin-top: 14px; }
.tjd-stat-label { font-size: 13.5px; font-weight: 700; color: #475569; margin-top: 6px; }

/* Filter Tabs & Search Bar */
.tjd-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: #ffffff; padding: 14px 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02); }
.tjd-pills { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.tjd-pill { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 12px; font-size: 13px; font-weight: 700; text-decoration: none; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; transition: all 0.2s ease; }
.tjd-pill:hover { background: #f1f5f9; color: #0f172a; }
.tjd-pill.is-active { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; border-color: #0284c7; box-shadow: 0 4px 14px rgba(2,132,199,0.25); }
.tjd-pill-count { padding: 2px 8px; border-radius: 20px; font-size: 11.5px; font-weight: 800; background: rgba(0,0,0,0.06); }
.tjd-pill.is-active .tjd-pill-count { background: rgba(255,255,255,0.22); color: #ffffff; }

.tjd-search-box { display: flex; align-items: center; gap: 8px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 8px 14px; min-width: 280px; }
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
        {{-- Summary Stat Cards --}}
        <div class="tjd-stats">
            {{-- Open Projects Card --}}
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing', 'status' => 'open']) }}" class="tjd-stat-card {{ $activeStatus === 'open' ? 'is-active' : '' }}" style="background: linear-gradient(135deg, #ffffff 0%, #fff7ed 100%);">
                <div class="tjd-stat-header">
                    <div class="tjd-stat-icon" style="background: #ffedd5; color: #ea580c;">
                        <i class="bi bi-folder-symlink-fill"></i>
                    </div>
                    <span style="font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; background: #ffedd5; color: #c2410c;">New Handover</span>
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
                    <span style="font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; background: #e0f2fe; color: #0369a1;">In Progress</span>
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
                    <span style="font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; background: #ede9fe; color: #6d28d9;">Fix Verified</span>
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
                    <span style="font-size: 11.5px; font-weight: 800; padding: 4px 10px; border-radius: 20px; background: #d1fae5; color: #047857;">QA Passed</span>
                </div>
                <div>
                    <div class="tjd-stat-value" style="color: #047857;">{{ $completedCount }}</div>
                    <div class="tjd-stat-label">Completed Projects</div>
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
            </div>

            <div class="tjd-search-box">
                <i class="bi bi-search" style="color: #94a3b8; font-size: 14px;"></i>
                <input type="text" id="tjdSearchInput" onkeyup="filterTjdTable()" placeholder="Filter project, company, developer...">
            </div>
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
                        @default
                            Open Testing Projects (New Handovers)
                    @endswitch
                    <span style="font-size: 12px; font-weight: 800; color: #0284c7; background: #e0f2fe; padding: 3px 12px; border-radius: 20px; border: 1px solid #bae6fd;">
                        {{ $handovers->count() }} Projects
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
                                <td colspan="5" style="text-align: center; padding: 54px 20px; color: #64748b;">
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
        </div>
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
</script>
@endsection
