@extends('layouts.app')

@section('title', 'Project Testing Details')

@push('styles')
<style>
.ptd-page { min-height: 100vh; background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); font-family: 'Inter', system-ui, -apple-system, sans-serif; }
.ptd-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 24px 32px; background: #ffffff; border-bottom: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); flex-wrap: wrap; }
.ptd-back-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 12px; background: #f8fafc; color: #475569; font-size: 13px; font-weight: 700; text-decoration: none; border: 1px solid #cbd5e1; transition: all 0.22s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.02); }
.ptd-back-btn:hover { background: #f1f5f9; color: #0f172a; transform: translateX(-3px); border-color: #94a3b8; }

.ptd-title-icon { width: 44px; height: 44px; border-radius: 14px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 8px 16px rgba(2,132,199,0.25); flex-shrink: 0; }
.ptd-title { font-size: 22px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em; }
.ptd-body { padding: 28px 32px 48px; display: grid; gap: 24px; max-width: 1280px; margin: 0 auto; }

.ptd-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 24px rgba(15,23,42,0.04); }
.ptd-card-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 20px 26px; border-bottom: 1px solid #e2e8f0; background: #ffffff; flex-wrap: wrap; }
.ptd-card-title { font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px; }
.ptd-card-body { padding: 26px; }

/* Info Grid Cards */
.ptd-grid-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 18px; }
.ptd-info-box { background: #f8fafc; padding: 18px 20px; border-radius: 16px; border: 1px solid #e2e8f0; transition: all 0.2s ease; }
.ptd-info-box:hover { background: #ffffff; border-color: #cbd5e1; box-shadow: 0 6px 16px rgba(15,23,42,0.04); }
.ptd-info-label { font-size: 11.5px; font-weight: 800; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: .06em; }
.ptd-info-val { font-size: 14.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }

/* Action Buttons & Status Selector */
.ptd-status-selector { display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 6px 14px 6px 16px; border-radius: 14px; border: 1.5px solid #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
.ptd-status-select { border: none; background: transparent; outline: none; font-weight: 800; font-size: 13.5px; color: #0f172a; cursor: pointer; padding: 4px 0; }

.ptd-btn-bug { background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: #ffffff; border: none; font-weight: 800; padding: 11px 24px; border-radius: 14px; box-shadow: 0 4px 16px rgba(220,38,38,0.3); cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 13.5px; transition: all 0.22s ease; text-decoration: none; }
.ptd-btn-bug:hover { background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(220,38,38,0.4); color: #ffffff; }

/* Credentials & Notes Blocks */
.ptd-credentials-box { background: #0f172a; color: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #1e293b; box-shadow: 0 6px 18px rgba(15,23,42,0.12); }
.ptd-notes-box { background: linear-gradient(135deg, #fffdfb 0%, #fff8f3 100%); padding: 20px; border-radius: 16px; border: 1px solid #ffd8bf; box-shadow: 0 4px 14px rgba(194,65,12,0.04); }

/* Bugs Table Styles */
.ptd-bugs-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.ptd-bugs-table th { background: #f8fafc; color: #475569; font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; padding: 14px 18px; border-bottom: 1.5px solid #e2e8f0; }
.ptd-bugs-table td { padding: 16px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; background: #ffffff; }
.ptd-bugs-table tr:hover td { background: #f8fafc; }
.ptd-bugs-table tr:last-child td { border-bottom: none; }

/* Modal Floating Overlay & Backdrop CSS */
.ps-modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(8px); z-index: 1200; display: none; opacity: 0; transition: opacity 0.25s ease; }
.ps-modal-overlay.is-open { display: block !important; opacity: 1 !important; }

.ps-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.95); width: min(580px, calc(100vw - 32px)); max-height: calc(100vh - 48px); overflow: auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.28); z-index: 1210; display: none; opacity: 0; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); }
.ps-modal.is-open { display: flex !important; flex-direction: column !important; opacity: 1 !important; transform: translate(-50%, -50%) scale(1) !important; }

.ps-modal-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 22px 26px; border-bottom: 1px solid #e2e8f0; background: #ffffff; }
.ps-modal-close { width: 38px; height: 38px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
.ps-modal-close:hover { background: #f1f5f9; color: #0f172a; }
.ps-modal-body { padding: 24px 26px; }

.pjd-select, .cc-sheet-input { width: 100%; min-height: 44px; padding: 10px 14px; border: 1.5px solid #cbd5e1; border-radius: 12px; background: #ffffff; color: #0f172a; font-size: 14px; outline: none; transition: all 0.2s ease; }
.pjd-select:focus, .cc-sheet-input:focus { border-color: #dc2626; box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.12); }
.ps-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 10px 20px; border-radius: 12px; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.2s ease; border: none; }

/* Preloader & Enhanced Status Confirmation Styles */
.preloader-spinner-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 3.5px solid #e2e8f0;
    border-top-color: #0284c7;
    border-right-color: #10b981;
    animation: preloaderSpin 1s linear infinite;
}
@keyframes preloaderSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes preloaderPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}
.preloader-bar-anim {
    width: 35%;
    height: 100%;
    background: linear-gradient(90deg, #0284c7, #10b981, #0284c7);
    border-radius: 999px;
    position: absolute;
    animation: preloaderBar 1.4s ease-in-out infinite alternate;
}
@keyframes preloaderBar {
    0% { left: 0%; width: 25%; }
    100% { left: 75%; width: 25%; }
}

/* View Bug Description Button & Modal Elements */
.ptd-btn-view-desc {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11.5px;
    font-weight: 800;
    color: #0284c7;
    background: #f0f9ff;
    border: 1.5px solid #bae6fd;
    padding: 4px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 1px 3px rgba(2, 132, 199, 0.08);
}
.ptd-btn-view-desc:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.22);
}
.vbm-att-item:hover {
    background: #ffffff !important;
    border-color: #0284c7 !important;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.15) !important;
    transform: translateY(-2px);
}
.ptd-btn-summary-dropdown:hover {
    background: #f0f9ff !important;
    color: #0284c7 !important;
    border-color: #0284c7 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.15) !important;
}
.ptd-dropdown-menu {
    animation: ptdDropdownFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes ptdDropdownFadeIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}
.tsm-metric-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 14px;
    padding: 14px 12px;
    text-align: center;
    transition: all 0.2s ease;
}
.tsm-metric-card:hover {
    background: #ffffff;
    border-color: #0284c7;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.1);
}
.tsm-att-img-preview {
    width: 64px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    border: 1.5px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.tsm-att-img-preview:hover {
    transform: scale(1.08);
    border-color: #0284c7;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
}
.ptd-btn-dev-remarks {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 16px;
    border-radius: 12px;
    background: #fff7ed;
    color: #c2410c;
    border: 1.5px solid #fed7aa;
    font-size: 12.5px;
    font-weight: 800;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 6px rgba(194, 65, 12, 0.08);
}
.ptd-btn-dev-remarks:hover {
    background: #ea580c;
    color: #ffffff;
    border-color: #ea580c;
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(234, 88, 12, 0.25);
}
</style>
@endpush

@section('content')
@php
    $currentUser = auth()->user();
    $isSuperOrCompanyAdmin = $currentUser && ($currentUser->isSuperAdmin() || $currentUser->isCompanyAdmin());
    $isTestingMember = $currentUser && ($currentUser->belongsToTestingDepartment() || $currentUser->hasTestingLikeRole());
    $isDevMember = $currentUser && ! $isTestingMember && ($currentUser->belongsToDevelopmentDepartment() || $currentUser->hasDevelopmentLikeRole() || $currentUser->isDevelopmentProjectCoordinator() || $currentUser->isDevelopmentTeam());
    $canEditDeveloperStatus = ! $isTestingMember && ($isDevMember || $isSuperOrCompanyAdmin);
    $canEditTesterStatus = $isTestingMember || $isSuperOrCompanyAdmin;

    $projectName = $projectItem->leadProduct?->name
        ?? $projectItem->product?->name
        ?? ($projectItem->lead?->company_name ? $projectItem->lead->company_name . ' Project' : 'Project #' . $projectItem->id);
@endphp

<div class="ptd-page">
    {{-- Topbar --}}
    <div class="ptd-topbar">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <a href="{{ route('projects.dashboard', ['dashboard_type' => 'testing']) }}" class="ptd-back-btn">
                <i class="bi bi-arrow-left"></i> Back to Testing Dashboard
            </a>
            <div style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 800; color: #0284c7; background: #f0f9ff; padding: 6px 14px; border-radius: 10px; border: 1.5px solid #bae6fd;">
                <span>🧪</span> Project QA Testing Details
            </div>
        </div>

        {{-- Actions & Testing Status Selector --}}
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            {{-- Testing Summary Report Dropdown --}}
            <div class="ptd-summary-dropdown" style="position: relative; display: inline-block;">
                <button type="button" onclick="toggleSummaryDropdown(event)" class="ptd-btn-summary-dropdown" id="btnSummaryDropdownToggle"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 7px 16px; border-radius: 12px; background: #ffffff; color: #0284c7; border: 1.5px solid #bae6fd; font-size: 12.5px; font-weight: 800; cursor: pointer; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 2px 6px rgba(2, 132, 199, 0.08);">
                    <i class="bi bi-file-earmark-bar-graph-fill" style="color: #0284c7; font-size: 15px;"></i>
                    <span>Testing Summary Report</span>
                    <i class="bi bi-chevron-down" id="summaryDropdownChevron" style="font-size: 11px; transition: transform 0.2s ease; margin-left: 2px;"></i>
                </button>
                <div id="summaryReportDropdownMenu" class="ptd-dropdown-menu"
                     style="display: none; position: absolute; right: 0; top: calc(100% + 6px); min-width: 230px; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 16px; box-shadow: 0 16px 36px rgba(15, 23, 42, 0.14); z-index: 1050; padding: 8px;">
                    <a href="javascript:void(0)" onclick="openTestingSummaryModal(); closeSummaryDropdown();"
                       style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 12px; color: #0f172a; text-decoration: none; font-size: 13px; font-weight: 700; transition: all 0.15s ease;"
                       onmouseover="this.style.background='#f0f9ff'; this.style.color='#0284c7';"
                       onmouseout="this.style.background='transparent'; this.style.color='#0f172a';">
                        <span style="display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; background: #e0f2fe; color: #0284c7; font-size: 16px; flex-shrink: 0;">
                            <i class="bi bi-card-text"></i>
                        </span>
                        <div>
                            <div style="font-weight: 800; font-size: 13px; line-height: 1.2;">Summary</div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 500; margin-top: 2px;">View QA Overview &amp; Metrics</div>
                        </div>
                    </a>
                    <div style="height: 1px; background: #f1f5f9; margin: 4px 6px;"></div>
                    <a href="{{ route('projects.testing-summary-report.export-pdf', [$projectItem, 'view' => 'preview']) }}" target="_blank" onclick="closeSummaryDropdown();"
                       style="display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 12px; color: #0f172a; text-decoration: none; font-size: 13px; font-weight: 700; transition: all 0.15s ease;"
                       onmouseover="this.style.background='#f0fdf4'; this.style.color='#15803d';"
                       onmouseout="this.style.background='transparent'; this.style.color='#0f172a';">
                        <span style="display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; background: #dcfce7; color: #15803d; font-size: 16px; flex-shrink: 0;">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                        </span>
                        <div>
                            <div style="font-weight: 800; font-size: 13px; line-height: 1.2;">Report PDF</div>
                            <div style="font-size: 11px; color: #64748b; font-weight: 500; margin-top: 2px;">Stream PDF in new tab</div>
                        </div>
                    </a>
                </div>
            </div>

            @php
                $currStatus = $handover?->status ?? 'open';
                if (in_array($currStatus, ['open', 'moved_to_testing'])) {
                    $normalizedStatus = 'open';
                } elseif (in_array($currStatus, ['ready_launch', 'ready_to_launch'])) {
                    $normalizedStatus = 'ready_launch';
                } else {
                    $normalizedStatus = $currStatus;
                }

                $qaTheme = match($normalizedStatus) {
                    'ready_launch' => ['border' => '#10b981', 'bg' => '#ecfdf5', 'color' => '#047857', 'icon' => '🚀'],
                    'completed'    => ['border' => '#22c55e', 'bg' => '#f0fdf4', 'color' => '#15803d', 'icon' => '🟩'],
                    'retesting'    => ['border' => '#8b5cf6', 'bg' => '#f5f3ff', 'color' => '#6d28d9', 'icon' => '🟪'],
                    'ongoing'      => ['border' => '#0284c7', 'bg' => '#f0f9ff', 'color' => '#0369a1', 'icon' => '🟦'],
                    default        => ['border' => '#f97316', 'bg' => '#fff7ed', 'color' => '#c2410c', 'icon' => '🟧'],
                };
            @endphp
            <form id="qaStatusForm" method="POST" action="{{ route('projects.testing-details.update-status', $projectItem) }}"
                  class="ptd-status-selector"
                  style="border: 2px solid {{ $qaTheme['border'] }}; background: {{ $qaTheme['bg'] }}; box-shadow: 0 4px 14px rgba(0,0,0,0.03); transition: all 0.25s ease;">
                @csrf
                <span style="font-size: 16px;">{{ $qaTheme['icon'] }}</span>
                <span style="font-size: 11.5px; font-weight: 900; color: {{ $qaTheme['color'] }}; text-transform: uppercase; letter-spacing: .06em;">QA Status:</span>
                <select name="status" class="ptd-status-select" data-current="{{ $normalizedStatus }}" onchange="handleQaStatusChange(this)"
                        style="color: {{ $qaTheme['color'] }}; font-weight: 800; font-size: 13.5px; cursor: pointer; outline: none;">
                    <option value="open" {{ $normalizedStatus === 'open' ? 'selected' : '' }} style="color: #c2410c; background: #ffffff;">🟧 Open (New Handover)</option>
                    <option value="ongoing" {{ $normalizedStatus === 'ongoing' ? 'selected' : '' }} style="color: #0369a1; background: #ffffff;">🟦 Ongoing Testing</option>
                    <option value="retesting" {{ $normalizedStatus === 'retesting' ? 'selected' : '' }} style="color: #6d28d9; background: #ffffff;">🟪 Retesting Phase</option>
                    <option value="completed" {{ $normalizedStatus === 'completed' ? 'selected' : '' }} style="color: #15803d; background: #ffffff;">🟩 Completed (QA Passed)</option>
                    <option value="ready_launch" {{ $normalizedStatus === 'ready_launch' ? 'selected' : '' }} style="color: #047857; background: #ffffff; font-weight: 800;">🚀 Ready Launch</option>
                </select>
            </form>
        </div>
    </div>

    <div class="ptd-body">
        {{-- Overview Card --}}
        <div class="ptd-card">
            <div class="ptd-card-head">
                <div class="ptd-card-title">
                    <i class="bi bi-shield-check" style="color: #0284c7; font-size: 20px;"></i> Testing Handover Overview
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="ptd-btn-dev-remarks" onclick="openDevRemarksModal()">
                        <i class="bi bi-chat-left-text-fill"></i> Developer Remarks &amp; Instructions
                    </button>
                    <button type="button" class="ptd-btn-bug" onclick="openAddBugModal()">
                        <i class="bi bi-bug-fill"></i> + Add Bug
                    </button>
                </div>
            </div>
            <div class="ptd-card-body">
                {{-- Prominent Project Name & Client Information Banner --}}
                <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1.5px solid #bae6fd; border-radius: 18px; padding: 20px 24px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; gap: 18px; flex-wrap: wrap; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.08);">
                    <div style="display: flex; align-items: center; gap: 16px;">
                        <div style="width: 52px; height: 52px; border-radius: 16px; background: #0284c7; color: #ffffff; font-size: 26px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(2,132,199,0.3); flex-shrink: 0;">
                            📁
                        </div>
                        <div>
                            <div style="font-size: 11px; font-weight: 800; color: #0369a1; text-transform: uppercase; letter-spacing: .06em;">
                                Project Under QA Testing
                            </div>
                            <div style="font-size: 21px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em; margin-top: 2px;">
                                {{ $projectName }}
                            </div>
                            @if($projectItem->lead?->company_name)
                                <div style="font-size: 13.5px; color: #475569; margin-top: 4px; font-weight: 600;">
                                    🏢 Client / Company: <strong style="color: #0f172a;">{{ $projectItem->lead->company_name }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <span style="font-size: 12.5px; font-weight: 800; background: #ffffff; color: #0284c7; border: 1.5px solid #bae6fd; padding: 6px 14px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
                            Project #{{ $projectItem->id }}
                        </span>
                        @if($projectItem->department?->name)
                            <span style="font-size: 12.5px; font-weight: 800; background: #ffffff; color: #475569; border: 1.5px solid #cbd5e1; padding: 6px 14px; border-radius: 10px;">
                                Dept: {{ $projectItem->department->name }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="ptd-grid-info">
                    <div class="ptd-info-box">
                        <div class="ptd-info-label">Testing Handover Date</div>
                        <div class="ptd-info-val" style="color: #0284c7;">
                            <i class="bi bi-calendar-check-fill"></i>
                            {{ $handover?->created_at ? $handover->created_at->format('d M Y, h:i A') : 'N/A' }}
                        </div>
                    </div>

                    <div class="ptd-info-box">
                        <div class="ptd-info-label">Developer Name</div>
                        <div class="ptd-info-val">
                            <div style="width: 26px; height: 26px; border-radius: 50%; background: #0284c7; color: #fff; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center;">
                                {{ strtoupper(substr($handover?->movedBy?->name ?? 'D', 0, 1)) }}
                            </div>
                            {{ $handover?->movedBy?->name ?? 'Dev Team' }}
                        </div>
                    </div>

                    <div class="ptd-info-box">
                        <div class="ptd-info-label">Testing TL</div>
                        <div class="ptd-info-val">
                            <i class="bi bi-person-badge-fill" style="color: #0284c7;"></i>
                            {{ $handover?->testingTl?->name ?? 'Assigned QA TL' }}
                        </div>
                    </div>

                    <div class="ptd-info-box">
                        <div class="ptd-info-label">Target Delivery Date</div>
                        <div class="ptd-info-val" style="color: #dc2626;">
                            <i class="bi bi-alarm-fill"></i>
                            {{ $projectItem->project_delivery_date ? \Carbon\Carbon::parse($projectItem->project_delivery_date)->format('d M Y') : 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Developer Remarks & Instructions Bar --}}
                <div style="margin-top: 20px; padding: 16px 22px; background: #fff7ed; border: 1.5px solid #fed7aa; border-radius: 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: #ea580c; color: #ffffff; font-size: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(234, 88, 12, 0.25);">
                            📝
                        </div>
                        <div>
                            <div style="font-size: 14px; font-weight: 800; color: #9a3412;">
                                Developer Remarks &amp; Handover Instructions
                            </div>
                            <div style="font-size: 12.5px; color: #c2410c; margin-top: 2px;">
                                @if($handover?->notes)
                                    Remarks available: <em>"{{ \Illuminate\Support\Str::limit($handover->notes, 65) }}"</em>
                                @else
                                    Testing instructions, staging credentials &amp; developer notes.
                                @endif
                            </div>
                        </div>
                    </div>
                    <button type="button" class="ptd-btn-dev-remarks" onclick="openDevRemarksModal()">
                        <i class="bi bi-eye-fill"></i> View Remarks &amp; Instructions
                    </button>
                </div>
            </div>
        </div>

        {{-- Reported Bugs Table Card --}}
        <div class="ptd-card">
            <div class="ptd-card-head">
                <div class="ptd-card-title">
                    <i class="bi bi-bug-fill" style="color: #dc2626; font-size: 20px;"></i> Reported Bugs &amp; Issues
                    <span style="font-size: 12px; font-weight: 800; background: #fee2e2; color: #991b1b; padding: 3px 12px; border-radius: 20px; border: 1px solid #fecaca;">
                        {{ $bugs->count() }} Reported
                    </span>
                </div>
                <button type="button" class="ptd-btn-bug" onclick="openAddBugModal()">
                    <i class="bi bi-plus-circle-fill"></i> Add New Bug
                </button>
            </div>
            <div style="padding: 0;">
                <div style="overflow-x: auto;">
                    <table class="ptd-bugs-table">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">#</th>
                                <th style="width: 140px;">Priority</th>
                                <th>Bug Description</th>
                                <th style="width: 190px;">Reported By &amp; Date</th>
                                <th style="width: 150px; text-align: center;">Attachment</th>
                                <th style="width: 140px; text-align: center;">Dev Status</th>
                                <th style="width: 160px; text-align: center;">Tester Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $allBugDataMap = []; @endphp
                            @forelse($bugs as $index => $bug)
                                @php
                                    $priorityBadge = match($bug->priority) {
                                        'High' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'icon' => 'bi-exclamation-triangle-fill'],
                                        'Low' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd', 'icon' => 'bi-info-circle-fill'],
                                        default => ['bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa', 'icon' => 'bi-exclamation-circle-fill'],
                                    };

                                    $rawDevStatus = strtolower(trim((string) ($bug->developer_status ?: ($bug->status === 'ongoing' ? 'ongoing' : (in_array($bug->status, ['completed', 'fixed', 'closed']) ? 'completed' : 'pending')))));
                                    if (! in_array($rawDevStatus, ['ongoing', 'pending', 'completed'], true)) {
                                        $rawDevStatus = 'pending';
                                    }
                                    $devStatusInfo = match($rawDevStatus) {
                                        'ongoing' => ['color' => '#0284c7', 'border' => '#bae6fd', 'bg' => '#f0f9ff', 'label' => 'Ongoing', 'icon' => '🔄'],
                                        'completed' => ['color' => '#16a34a', 'border' => '#bbf7d0', 'bg' => '#f0fdf4', 'label' => 'Completed', 'icon' => '✅'],
                                        default => ['color' => '#ea580c', 'border' => '#fed7aa', 'bg' => '#fff7ed', 'label' => 'Pending', 'icon' => '⏳'],
                                    };

                                    $rawTesterStatus = strtolower(trim((string) ($bug->tester_status ?: ($bug->status === 'closed' ? 'closed' : ($bug->status === 'reopen' ? 'reopen' : 'pending')))));
                                    if (! in_array($rawTesterStatus, ['closed', 'reopen', 'pending'], true)) {
                                        $rawTesterStatus = 'pending';
                                    }
                                    $testerStatusInfo = match($rawTesterStatus) {
                                        'reopen' => ['color' => '#dc2626', 'border' => '#fecaca', 'bg' => '#fef2f2', 'label' => 'Reopen', 'icon' => '🔁'],
                                        'closed' => ['color' => '#475569', 'border' => '#cbd5e1', 'bg' => '#f8fafc', 'label' => 'Closed', 'icon' => '🔒'],
                                        default => ['color' => '#ea580c', 'border' => '#fed7aa', 'bg' => '#fff7ed', 'label' => 'Pending', 'icon' => '⏳'],
                                    };

                                    $bugHistory = is_array($bug->status_history) ? $bug->status_history : [];
                                    $bugModalData = [
                                        'id' => $bug->id,
                                        'index' => $index + 1,
                                        'priority' => $bug->priority,
                                        'priority_badge' => $priorityBadge,
                                        'description' => $bug->description,
                                        'created_by' => $bug->createdBy?->name ?? 'QA Tester',
                                        'created_at' => $bug->created_at?->format('d M Y, h:i A'),
                                        'dev_status' => $rawDevStatus,
                                        'tester_status' => $rawTesterStatus,
                                        'dev_status_info' => $devStatusInfo,
                                        'tester_status_info' => $testerStatusInfo,
                                        'can_edit_dev' => $canEditDeveloperStatus,
                                        'can_edit_tester' => $canEditTesterStatus,
                                        'update_url' => route('projects.bugs.update-status', $bug),
                                        'reopen_count' => (int) ($bug->reopen_count ?? 0),
                                        'developer_remarks' => $bug->developer_remarks,
                                        'tester_remarks' => $bug->tester_remarks,
                                        'latest_remarks' => $bug->latest_remarks,
                                        'status_history' => $bugHistory,
                                        'attachments' => $bug->attachment_list,
                                    ];
                                    $allBugDataMap[$bug->id] = $bugModalData;
                                @endphp

                                <tr>
                                    <td style="text-align: center; font-weight: 800; color: #64748b;">
                                        {{ $index + 1 }}
                                    </td>
                                    <td>
                                        <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; background: {{ $priorityBadge['bg'] }}; color: {{ $priorityBadge['color'] }}; border: 1px solid {{ $priorityBadge['border'] }}; font-size: 12px; font-weight: 800;">
                                            <i class="bi {{ $priorityBadge['icon'] }}"></i> {{ $bug->priority }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-size: 13.5px; color: #0f172a; line-height: 1.5; font-weight: 500; max-width: 480px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; word-break: break-word;">
                                            {!! nl2br(e($bug->description)) !!}
                                        </div>
                                        @if($bug->latest_remarks)
                                            <div style="margin-top: 5px; font-size: 11.5px; color: #0369a1; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px; max-width: 480px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                <i class="bi bi-chat-left-dots-fill" style="font-size: 10px;"></i>
                                                <span style="font-weight: 700;">Latest:</span>
                                                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $bug->latest_remarks }}</span>
                                            </div>
                                        @endif
                                        <div style="margin-top: 6px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                            <button type="button" class="ptd-btn-view-desc"
                                                onclick="openBugViewModalById({{ $bug->id }})">
                                                <i class="bi bi-eye-fill"></i> View Description
                                            </button>
                                            <button type="button" class="ptd-btn-view-history"
                                                onclick="openBugHistoryModalById({{ $bug->id }})"
                                                style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: #4338ca; background: #eef2ff; border: 1.5px solid #c7d2fe; padding: 4px 10px; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;">
                                                <i class="bi bi-clock-history"></i> History ({{ count($bugHistory) }})
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 4px;">
                                            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #1e293b;">
                                                <div style="width: 24px; height: 24px; border-radius: 50%; background: #e0f2fe; color: #0284c7; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                    {{ strtoupper(substr($bug->createdBy?->name ?? 'Q', 0, 1)) }}
                                                </div>
                                                <span>{{ $bug->createdBy?->name ?? 'QA Tester' }}</span>
                                            </div>
                                            <div style="font-size: 11.5px; color: #64748b; font-weight: 600;">
                                                <i class="bi bi-clock-history"></i> {{ $bug->created_at?->format('d M Y, h:i A') }}
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        @php
                                            $attList = $bug->attachment_list;
                                        @endphp
                                        @if(!empty($attList))
                                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                                @foreach($attList as $attIndex => $att)
                                                    <a href="{{ $att['url'] }}" target="_blank" style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: #0284c7; background: #f0f9ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #bae6fd; text-decoration: none; transition: all 0.2s ease; max-width: 180px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="{{ $att['name'] }}">
                                                        <i class="bi bi-paperclip" style="font-size: 13px;"></i>
                                                        <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $att['name'] }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span style="color: #94a3b8; font-size: 12px; font-weight: 600;">No Files</span>
                                        @endif
                                    </td>
                                    {{-- Dev Status Column --}}
                                    <td style="text-align: center;">
                                        <button type="button"
                                            onclick="openBugStatusModalById({{ $bug->id }}, 'developer')"
                                            style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 12px; border: 1.5px solid {{ $devStatusInfo['border'] }}; background: {{ $devStatusInfo['bg'] }}; color: {{ $devStatusInfo['color'] }}; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.06);"
                                            title="{{ $canEditDeveloperStatus ? 'Click to update Developer Status & Remarks' : 'Click to view Bug Status & Remarks (Read-only for Testing)' }}">
                                            <span>{{ $devStatusInfo['icon'] }} {{ $devStatusInfo['label'] }}</span>
                                            @if($canEditDeveloperStatus)
                                                <i class="bi bi-pencil-square" style="font-size: 11px; opacity: 0.7;"></i>
                                            @else
                                                <i class="bi bi-eye" style="font-size: 11px; opacity: 0.7;"></i>
                                            @endif
                                        </button>
                                    </td>
                                    {{-- Tester Status Column --}}
                                    <td style="text-align: center;">
                                        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
                                            <button type="button"
                                                onclick="openBugStatusModalById({{ $bug->id }}, 'tester')"
                                                style="display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 12px; border: 1.5px solid {{ $testerStatusInfo['border'] }}; background: {{ $testerStatusInfo['bg'] }}; color: {{ $testerStatusInfo['color'] }}; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.06);"
                                                title="{{ $canEditTesterStatus ? 'Click to update Tester Status & Remarks' : 'Click to view Bug Status & Remarks (Read-only for Development)' }}">
                                                <span>{{ $testerStatusInfo['icon'] }} {{ $testerStatusInfo['label'] }}</span>
                                                @if($canEditTesterStatus)
                                                    <i class="bi bi-pencil-square" style="font-size: 11px; opacity: 0.7;"></i>
                                                @else
                                                    <i class="bi bi-eye" style="font-size: 11px; opacity: 0.7;"></i>
                                                @endif
                                            </button>
                                            @if(($bug->reopen_count ?? 0) > 0)
                                                <span style="display: inline-flex; align-items: center; gap: 3px; font-size: 10.5px; font-weight: 800; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; padding: 2px 7px; border-radius: 12px;" title="Reopened {{ $bug->reopen_count }} times">
                                                    <i class="bi bi-arrow-repeat"></i> Reopened: {{ $bug->reopen_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 54px 20px; background: #ffffff;">
                                        <div style="width: 64px; height: 64px; border-radius: 20px; background: #f0f9ff; color: #0284c7; font-size: 32px; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px auto; box-shadow: 0 4px 14px rgba(2,132,199,0.15);">
                                            🎉
                                        </div>
                                        <div style="font-size: 17px; font-weight: 800; color: #0f172a;">No Bugs Reported Yet</div>
                                        <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Click <strong>+ Add Bug</strong> above to report any QA issues or bugs found during testing.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <script>
                        window._bugDataMap = window._bugDataMap || {};
                        Object.assign(window._bugDataMap, @json($allBugDataMap));
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Bug Modal Popup --}}
<div class="ps-modal-overlay" id="add-bug-modal-overlay" onclick="closeAddBugModal()"></div>
<div class="ps-modal" id="add-bug-modal" style="max-width: 580px; overflow: hidden;">
    <div class="ps-modal-head">
        <div>
            <div class="ps-card-title" style="display:flex; align-items:center; gap:8px; color:#dc2626;">
                🐞 Report Project Bug
            </div>
            <div class="ps-card-sub">Log bug description, set priority level, and upload screenshot or attachment.</div>
        </div>
        <button type="button" class="ps-modal-close" onclick="closeAddBugModal()" aria-label="Close modal">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <div class="ps-modal-body">
        <form method="POST" action="{{ route('projects.bugs.store', $projectItem) }}" enctype="multipart/form-data" onsubmit="handleAddBugSubmit(event, this)">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Priority Level <span style="color: #ef4444;">*</span>
                    </label>
                    <select name="priority" class="pjd-select" style="width: 100%; min-height: 42px; padding: 8px 12px; border-radius: 10px; border: 1px solid #cbd5e1;" required>
                        <option value="High">🔴 High Priority</option>
                        <option value="Medium" selected>🟠 Medium Priority</option>
                        <option value="Low">🔵 Low Priority</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        Bug Description <span style="color: #ef4444;">*</span>
                    </label>
                    <textarea name="description" rows="4" class="cc-sheet-input" style="width: 100%; min-height: 110px; padding: 10px; border-radius: 10px;" placeholder="Describe the issue, step-by-step reproduction, or expected vs actual behavior..." required></textarea>
                </div>

                <div>
                    <label style="display: flex; align-items: center; justify-content: space-between; font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 6px;">
                        <span>Attachments / Files Upload (Multiple Allowed)</span>
                        <span style="font-size: 11px; color: #64748b; font-weight: 500;">Screenshots, PDFs, Logs, Docs</span>
                    </label>
                    <input type="file" name="attachments[]" id="bugAttachmentsInput" class="cc-sheet-input" style="width: 100%; padding: 8px 10px;" accept="image/*,.pdf,.doc,.docx,.zip,.txt,.log" multiple onchange="handleBugFilesChange(this)">
                    <div id="bugFilesPreviewList" style="margin-top: 8px; display: flex; flex-direction: column; gap: 4px;"></div>
                    <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
                        <i class="bi bi-info-circle"></i> You can select <strong>multiple files</strong> at once (Images, PDF, Log, Word, Zip). Max 20MB per file.
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                    <button type="button" class="ps-btn" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;" onclick="closeAddBugModal()">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitBug" class="ps-btn" style="background: #dc2626; color: #ffffff; border: none; font-weight: 700; padding: 10px 22px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                        <span id="btnSubmitBugIcon">🐞</span>
                        <span id="btnSubmitBugText">Submit Bug Report</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Status Change Confirmation Modal --}}
<div id="status-confirm-modal-overlay" class="ps-modal-overlay" onclick="closeStatusConfirmModal()"></div>
<div id="status-confirm-modal" class="ps-modal" style="max-width: 480px; border-radius: 26px; padding: 0; border: none; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);">
    <div style="padding: 28px 28px 22px; text-align: center;">
        <div id="scmIconWrapper" style="width: 72px; height: 72px; margin: 0 auto 16px; border-radius: 22px; display: flex; align-items: center; justify-content: center; font-size: 34px; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            🚀
        </div>
        <div id="scmTitle" style="font-size: 20px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">
            Update Status?
        </div>
        <div id="scmSubtitle" style="font-size: 13.5px; color: #64748b; margin-top: 6px; line-height: 1.5;">
            Are you sure you want to proceed with this status change?
        </div>

        {{-- Transition preview card --}}
        <div style="margin: 18px 0; padding: 12px 18px; border-radius: 16px; background: #f8fafc; border: 1.5px solid #e2e8f0; display: flex; align-items: center; justify-content: center; gap: 12px; font-size: 13px; font-weight: 800;">
            <span id="scmFromBadge" style="padding: 5px 12px; border-radius: 10px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;"></span>
            <span style="color: #94a3b8; font-size: 16px;">➔</span>
            <span id="scmToBadge" style="padding: 5px 12px; border-radius: 10px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;"></span>
        </div>

        {{-- Email notification highlight for Ready Launch --}}
        <div id="scmEmailNotice" style="display: none; text-align: left; background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 16px; padding: 14px 18px; margin-bottom: 20px;">
            <div style="font-size: 11.5px; font-weight: 800; color: #15803d; text-transform: uppercase; letter-spacing: .06em; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-send-check-fill" style="color: #16a34a; font-size: 14px;"></i> Automated Email Notification
            </div>
            <div style="font-size: 12.5px; color: #166534; margin-top: 6px; line-height: 1.55;">
                An official <strong>Ready to Launch</strong> notification email will be dispatched to:
                <ul style="margin: 4px 0 0 18px; padding: 0; font-size: 12px; color: #15803d;">
                    <li>👨‍💻 Assigned Developer(s)</li>
                    <li>🧑‍💼 Developer Team Lead(s)</li>
                    <li>📋 Development Project Coordinator</li>
                </ul>
            </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: center; margin-top: 10px;">
            <button type="button" class="ps-btn" onclick="closeStatusConfirmModal()" style="flex: 1; min-height: 46px; border-radius: 14px; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-weight: 700; cursor: pointer; transition: all 0.2s ease;">
                Cancel
            </button>
            <button type="button" id="scmConfirmBtn" onclick="confirmStatusChangeSubmit()" class="ps-btn" style="flex: 1.4; min-height: 46px; border-radius: 14px; background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff; border: none; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(2,132,199,0.3); display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <span id="scmConfirmBtnText">Yes, Update Status</span>
            </button>
        </div>
    </div>
</div>

{{-- Fullscreen Preloader Overlay --}}
<div id="statusPreloaderOverlay" style="position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.82); backdrop-filter: blur(12px); display: none; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease;">
    <div style="background: #ffffff; border-radius: 28px; padding: 36px 44px; max-width: 440px; width: calc(100vw - 40px); text-align: center; box-shadow: 0 25px 60px rgba(0,0,0,0.35); border: 1px solid #e2e8f0; transform: scale(0.95); transition: transform 0.3s ease;" id="preloaderCard">
        <div style="position: relative; width: 84px; height: 84px; margin: 0 auto 20px;">
            <div class="preloader-spinner-ring"></div>
            <div id="preloaderIcon" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 38px; animation: preloaderPulse 1.6s infinite ease-in-out;">
                🚀
            </div>
        </div>
        <div id="preloaderTitle" style="font-size: 20px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">
            Updating Project Status...
        </div>
        <div id="preloaderSubtitle" style="font-size: 13.5px; color: #64748b; margin-top: 8px; line-height: 1.6;">
            Please wait while the status is updated.
        </div>
        {{-- Animated Progress Bar --}}
        <div style="margin-top: 22px; width: 100%; height: 6px; background: #f1f5f9; border-radius: 999px; overflow: hidden; position: relative;">
            <div class="preloader-bar-anim"></div>
        </div>
    </div>
</div>

{{-- View Bug Details Modal Popup --}}
<div class="ps-modal-overlay" id="view-bug-modal-overlay" onclick="closeBugViewModal()"></div>
<div class="ps-modal" id="view-bug-modal" style="max-width: 660px; border-radius: 24px; overflow: hidden; padding: 0; box-shadow: 0 25px 60px rgba(0,0,0,0.3); border: 1px solid #e2e8f0;">
    {{-- Header --}}
    <div style="padding: 20px 24px; background: #ffffff; border-bottom: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #fef2f2; color: #dc2626; font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #fee2e2;">
                🐞
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="font-size: 17px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">
                        Bug #<span id="vbmIndex">1</span> Details
                    </div>
                    <span id="vbmPriorityBadge" style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; border: 1px solid transparent;"></span>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 3px;">
                    Reported by <strong id="vbmReporter" style="color: #334155;">QA Tester</strong> &bull; <span id="vbmDate"></span>
                </div>
            </div>
        </div>
        <button type="button" class="ps-modal-close" onclick="closeBugViewModal()" aria-label="Close modal" style="position: static; width: 34px; height: 34px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease;">
            <i class="bi bi-x-lg" style="font-size: 13px;"></i>
        </button>
    </div>

    {{-- Body --}}
    <div style="padding: 22px 24px; max-height: calc(85vh - 140px); overflow-y: auto;">
        {{-- Status Badges Row --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
            <div style="padding: 12px 14px; border-radius: 14px; background: #f8fafc; border: 1.5px solid #e2e8f0;">
                <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Developer Status</div>
                <div style="margin-top: 6px;">
                    <span id="vbmDevStatus" style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 800; border: 1px solid transparent;"></span>
                </div>
            </div>
            <div style="padding: 12px 14px; border-radius: 14px; background: #f8fafc; border: 1.5px solid #e2e8f0;">
                <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Tester Status</div>
                <div style="margin-top: 6px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span id="vbmTesterStatus" style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 800; border: 1px solid transparent;"></span>
                    <span id="vbmReopenWrapper" style="display: none; align-items: center; gap: 4px; font-size: 11px; font-weight: 800; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 10px;">
                        <i class="bi bi-arrow-repeat"></i> Reopened: <span id="vbmReopenBadge">0</span>
                    </span>
                </div>
            </div>
        </div>

        {{-- Description Container with Copy Button --}}
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <label style="font-size: 12.5px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.04em;">
                    Bug Description
                </label>
                <button type="button" id="vbmCopyBtn" onclick="copyBugDescription()" style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: #475569; background: #f1f5f9; border: 1.5px solid #cbd5e1; padding: 4px 10px; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;">
                    <i class="bi bi-clipboard"></i> Copy Text
                </button>
            </div>
            <div id="vbmDescription" style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 16px 18px; font-size: 13.5px; line-height: 1.65; color: #1e293b; white-space: pre-wrap; word-break: break-word; font-weight: 500; min-height: 100px; max-height: 280px; overflow-y: auto;">
            </div>
        </div>

        {{-- Attachments Section --}}
        <div id="vbmAttachmentsSection" style="margin-top: 20px; display: none;">
            <label style="display: block; font-size: 12.5px; font-weight: 800; color: #1e293b; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;">
                Attachments &amp; Proofs
            </label>
            <div id="vbmAttachmentsList" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px;">
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div style="padding: 16px 24px; background: #f8fafc; border-top: 1.5px solid #f1f5f9; display: flex; justify-content: flex-end;">
        <button type="button" class="ps-btn" onclick="closeBugViewModal()" style="padding: 9px 24px; border-radius: 12px; background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; cursor: pointer; transition: all 0.2s ease;">
            Close
        </button>
    </div>
</div>

{{-- Bug Status Update & Mandatory Remarks Modal --}}
<div id="bug-status-modal-overlay" class="ps-modal-overlay" onclick="closeBugStatusModal()"></div>
<div id="bug-status-modal" class="ps-modal" style="max-width: 560px; width: calc(100vw - 32px); border-radius: 24px; padding: 0; border: 1px solid #e2e8f0; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35); overflow: hidden;">
    <form id="bugStatusForm" method="POST" action="" style="margin: 0; display: flex; flex-direction: column; width: 100%;">
        @csrf
        @method('PATCH')
        <input type="hidden" id="bsmStatusType" name="status_type" value="">
        <input type="hidden" id="bsmBugId" name="bug_id" value="">

        <div style="padding: 20px 24px; background: #ffffff; border-bottom: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div id="bsmRoleIcon" style="width: 44px; height: 44px; border-radius: 14px; background: #f0f9ff; color: #0284c7; font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bae6fd;">
                    🔄
                </div>
                <div>
                    <div id="bsmTitle" style="font-size: 17px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">
                        Update Bug Status
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        Select development/testing status &amp; provide mandatory remarks.
                    </div>
                </div>
            </div>
            <button type="button" class="ps-modal-close" onclick="closeBugStatusModal()" aria-label="Close modal" style="position: static; width: 34px; height: 34px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="bi bi-x-lg" style="font-size: 13px;"></i>
            </button>
        </div>

        <div style="padding: 20px 24px; max-height: calc(85vh - 140px); overflow-y: auto;">
            {{-- Bug Summary Card --}}
            <div style="margin-bottom: 16px; padding: 12px 16px; border-radius: 14px; background: #f8fafc; border: 1.5px solid #e2e8f0;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;">
                    <div id="bsmPriorityBadge"></div>
                    <div id="bsmIndexText" style="font-size: 11.5px; font-weight: 800; color: #64748b;"></div>
                </div>
                <div id="bsmDescSnippet" style="font-size: 13px; color: #1e293b; line-height: 1.5; font-weight: 500; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"></div>
            </div>

            {{-- Two-column Status Selectors: Developer Status & Testing Status --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 16px;">
                {{-- Development Status Box --}}
                <div id="bsmDevBox" style="background: #f0f9ff; border: 1.5px solid #bae6fd; border-radius: 14px; padding: 12px 14px; transition: all 0.2s ease;">
                    <label for="bsmDevStatus" style="display: flex; align-items: center; justify-content: space-between; font-size: 11.5px; font-weight: 800; color: #0369a1; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;">
                        <span>🛠️ Dev Status</span>
                        <span id="bsmDevPermBadge" style="font-size: 10px; padding: 1px 6px; border-radius: 6px; background: #e0f2fe; color: #0369a1; font-weight: 800;">Developer</span>
                    </label>
                    <select id="bsmDevStatus" name="developer_status" style="width: 100%; border-radius: 10px; border: 1.5px solid #93c5fd; padding: 7px 10px; font-size: 13px; font-weight: 800; color: #0f172a; background: #ffffff; cursor: pointer; outline: none;">
                        <option value="pending">⏳ Pending</option>
                        <option value="ongoing">🔄 Ongoing</option>
                        <option value="completed">✅ Completed</option>
                    </select>
                    <div id="bsmDevStatusDisabled" style="display: none; padding: 7px 10px; border-radius: 10px; background: #e2e8f0; color: #475569; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1;"></div>
                </div>

                {{-- Testing / QA Status Box --}}
                <div id="bsmTesterBox" style="background: #faf5ff; border: 1.5px solid #e9d5ff; border-radius: 14px; padding: 12px 14px; transition: all 0.2s ease;">
                    <label for="bsmTesterStatus" style="display: flex; align-items: center; justify-content: space-between; font-size: 11.5px; font-weight: 800; color: #6d28d9; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 8px;">
                        <span>🧪 QA / Tester Status</span>
                        <span id="bsmTesterPermBadge" style="font-size: 10px; padding: 1px 6px; border-radius: 6px; background: #f3e8ff; color: #7e22ce; font-weight: 800;">QA Tester</span>
                    </label>
                    <select id="bsmTesterStatus" name="tester_status" style="width: 100%; border-radius: 10px; border: 1.5px solid #d8b4fe; padding: 7px 10px; font-size: 13px; font-weight: 800; color: #0f172a; background: #ffffff; cursor: pointer; outline: none;">
                        <option value="pending">⏳ Pending</option>
                        <option value="reopen">🔁 Reopen</option>
                        <option value="closed">🔒 Closed</option>
                    </select>
                    <div id="bsmTesterStatusDisabled" style="display: none; padding: 7px 10px; border-radius: 10px; background: #e2e8f0; color: #475569; font-size: 12px; font-weight: 700; border: 1px solid #cbd5e1;"></div>
                </div>
            </div>

            {{-- Previous Remarks Preview (if available) --}}
            <div id="bsmPrevRemarksBox" style="display: none; margin-bottom: 16px; padding: 10px 14px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; font-size: 12px;">
                <div style="font-weight: 800; color: #64748b; font-size: 10.5px; text-transform: uppercase; margin-bottom: 5px;">Previous Remarks on Record:</div>
                <div id="bsmPrevDevRemarks" style="display: none; color: #0369a1; margin-bottom: 4px; line-height: 1.4;"><strong>🛠️ Dev:</strong> <span></span></div>
                <div id="bsmPrevTesterRemarks" style="display: none; color: #6d28d9; line-height: 1.4;"><strong>🧪 QA:</strong> <span></span></div>
            </div>

            {{-- Mandatory Remarks Textarea --}}
            <div>
                <label for="bsmRemarksInput" style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px;">
                    Mandatory Remarks / Update Notes <span style="color: #dc2626; font-size: 14px;">*</span>
                </label>
                <textarea id="bsmRemarksInput" name="remarks" rows="3" placeholder="Explain fixes, changes made, test verification results, or reason for status update (Mandatory)..." required style="width: 100%; border-radius: 12px; border: 1.5px solid #cbd5e1; padding: 10px 14px; font-size: 13.5px; color: #0f172a; outline: none; transition: border-color 0.2s ease; box-sizing: border-box; resize: vertical;" oninput="document.getElementById('bsmRemarksError').style.display = 'none';"></textarea>
                <div id="bsmRemarksError" style="display: none; color: #dc2626; font-size: 11.5px; font-weight: 700; margin-top: 5px;">
                    <i class="bi bi-exclamation-circle-fill"></i> Please provide remarks explaining this status change (minimum 2 characters).
                </div>
            </div>
        </div>

        <div style="padding: 14px 24px; background: #f8fafc; border-top: 1.5px solid #f1f5f9; display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="ps-btn" onclick="closeBugStatusModal()" style="padding: 9px 20px; border-radius: 12px; background: #f1f5f9; color: #475569; border: 1.5px solid #cbd5e1; font-weight: 700; cursor: pointer;">
                Cancel
            </button>
            <button type="button" id="bsmSubmitBtn" onclick="submitBugStatusWithRemarks()" class="ps-btn" style="padding: 9px 22px; border-radius: 12px; background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff; border: none; font-weight: 800; cursor: pointer; box-shadow: 0 4px 14px rgba(2,132,199,0.3); display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-check2-circle"></i> Confirm &amp; Save
            </button>
        </div>
    </form>
</div>

{{-- Bug History & Remarks Modal --}}
<div id="bug-history-modal-overlay" class="ps-modal-overlay" onclick="closeBugHistoryModal()"></div>
<div id="bug-history-modal" class="ps-modal" style="max-width: 640px; width: calc(100vw - 32px); max-height: 88vh; border-radius: 24px; padding: 0; border: 1px solid #e2e8f0; box-shadow: 0 25px 65px rgba(0,0,0,0.35); overflow: hidden;">
    <div style="padding: 18px 24px; background: #ffffff; border-bottom: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #eef2ff; color: #4338ca; font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #c7d2fe;">
                📜
            </div>
            <div>
                <div style="font-size: 17px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em; display: flex; align-items: center; gap: 8px;">
                    <span>Bug Status &amp; Remarks History</span>
                    <span id="bhmIndexBadge" style="font-size: 11px; padding: 2px 8px; border-radius: 8px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">Bug #1</span>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Full chronological audit log of Developer and Tester updates with remarks.
                </div>
            </div>
        </div>
        <button type="button" class="ps-modal-close" onclick="closeBugHistoryModal()" aria-label="Close modal" style="position: static; width: 34px; height: 34px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="bi bi-x-lg" style="font-size: 13px;"></i>
        </button>
    </div>

    <div style="padding: 20px 24px; overflow-y: auto; flex: 1; max-height: calc(85vh - 130px);">
        {{-- Bug Summary Snippet --}}
        <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 12px 16px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;">
                <div id="bhmPriorityBadge" style="font-size: 11px; font-weight: 800;"></div>
                <div id="bhmReporter" style="font-size: 11.5px; color: #64748b; font-weight: 600;"></div>
            </div>
            <div id="bhmDescSnippet" style="font-size: 13px; color: #1e293b; line-height: 1.5; font-weight: 500;"></div>
        </div>

        {{-- Latest Remarks Highlights --}}
        <div id="bhmLatestRemarksContainer" style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 18px;">
            {{-- Dynamically populated with Dev and QA latest remarks cards --}}
        </div>

        {{-- Timeline Container --}}
        <div>
            <div style="font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-clock-history"></i> Audit Trail Timeline
            </div>
            <div id="bhmTimelineList" style="position: relative;">
                {{-- Dynamically populated via JS --}}
            </div>
        </div>
    </div>

    <div style="padding: 14px 24px; background: #f8fafc; border-top: 1.5px solid #f1f5f9; display: flex; justify-content: flex-end;">
        <button type="button" class="ps-btn" onclick="closeBugHistoryModal()" style="padding: 8px 22px; border-radius: 12px; background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; cursor: pointer;">
            Close
        </button>
    </div>
</div>

{{-- Developer Remarks & Instructions Modal Popup --}}
<div class="ps-modal-overlay" id="dev-remarks-modal-overlay" onclick="closeDevRemarksModal()"></div>
<div class="ps-modal" id="dev-remarks-modal" style="max-width: 680px; width: calc(100vw - 32px); max-height: 90vh; border-radius: 24px; overflow: hidden; padding: 0; box-shadow: 0 25px 65px rgba(0,0,0,0.35); border: 1px solid #e2e8f0; display: none; flex-direction: column;">
    {{-- Modal Head --}}
    <div style="padding: 20px 24px; background: #ffffff; border-bottom: 1.5px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #fff7ed; color: #ea580c; font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #fed7aa;">
                📝
            </div>
            <div>
                <div style="font-size: 17px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">
                    Developer Remarks &amp; Instructions
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Handed over by <strong style="color: #334155;">{{ $handover?->movedBy?->name ?? 'Developer' }}</strong> &bull; {{ $handover?->created_at ? $handover->created_at->format('d M Y, h:i A') : 'N/A' }}
                </div>
            </div>
        </div>
        <button type="button" class="ps-modal-close" onclick="closeDevRemarksModal()" aria-label="Close modal" style="position: static; width: 34px; height: 34px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer;">
            <i class="bi bi-x-lg" style="font-size: 13px;"></i>
        </button>
    </div>

    {{-- Modal Body --}}
    <div style="padding: 22px 24px; max-height: calc(85vh - 140px); overflow-y: auto;">
        {{-- Staging Link if available --}}
        @if($handover?->testing_link)
            <div style="margin-bottom: 18px; padding: 14px 18px; background: #f0f9ff; border: 1.5px solid #bae6fd; border-radius: 14px;">
                <div style="font-size: 11px; font-weight: 800; color: #0369a1; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                    <i class="bi bi-link-45deg" style="font-size: 16px;"></i> Staging / Testing URL
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ $handover->testing_link }}" target="_blank" style="font-size: 13.5px; font-weight: 700; color: #0284c7; text-decoration: underline; word-break: break-all;">
                        {{ $handover->testing_link }}
                    </a>
                    <a href="{{ $handover->testing_link }}" target="_blank" class="ps-btn" style="padding: 5px 12px; font-size: 11.5px; border-radius: 8px; background: #0284c7; color: #ffffff; text-decoration: none; font-weight: 700;">
                        Open <i class="bi bi-box-arrow-up-right" style="font-size: 10px;"></i>
                    </a>
                </div>
            </div>
        @endif

        {{-- Credentials if available --}}
        @if($handover?->credentials)
            <div style="margin-bottom: 18px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <label style="font-size: 12px; font-weight: 800; color: #0369a1; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-key-fill"></i> Testing Credentials &amp; Access Details
                    </label>
                    <button type="button" id="btnCopyCreds" onclick="copyDevCredentials()" style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: #0284c7; background: #f0f9ff; border: 1px solid #bae6fd; padding: 4px 10px; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;">
                        <i class="bi bi-clipboard"></i> Copy Credentials
                    </button>
                </div>
                <div id="devModalCredentials" style="background: #0f172a; color: #38bdf8; font-family: monospace; font-size: 13px; line-height: 1.65; padding: 14px 16px; border-radius: 14px; border: 1.5px solid #1e293b; white-space: pre-wrap; word-break: break-word;">{!! e($handover->credentials) !!}</div>
            </div>
        @endif

        {{-- Remarks & Notes --}}
        <div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <label style="font-size: 12px; font-weight: 800; color: #9a3412; text-transform: uppercase; letter-spacing: 0.04em; display: flex; align-items: center; gap: 6px;">
                    <i class="bi bi-chat-left-text-fill"></i> Developer Remarks &amp; Special Instructions
                </label>
                @if($handover?->notes)
                    <button type="button" id="btnCopyRemarks" onclick="copyDevRemarks()" style="display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; padding: 4px 10px; border-radius: 8px; cursor: pointer; transition: all 0.2s ease;">
                        <i class="bi bi-clipboard"></i> Copy Remarks
                    </button>
                @endif
            </div>
            <div id="devModalRemarks" style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 16px 18px; font-size: 13.5px; line-height: 1.7; color: #1e293b; white-space: pre-wrap; word-break: break-word; font-weight: 500; min-height: 100px; max-height: 300px; overflow-y: auto;">
                @if($handover?->notes)
                    {!! e($handover->notes) !!}
                @else
                    <span style="color: #94a3b8; font-style: italic;">No specific remarks or instructions were provided by the developer during testing handover.</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Foot --}}
    <div style="padding: 16px 24px; background: #f8fafc; border-top: 1.5px solid #f1f5f9; display: flex; justify-content: flex-end;">
        <button type="button" class="ps-btn" onclick="closeDevRemarksModal()" style="padding: 9px 24px; border-radius: 12px; background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; cursor: pointer;">
            Close
        </button>
    </div>
</div>

{{-- Testing Summary Report Modal Popup --}}
<div class="ps-modal-overlay" id="testing-summary-modal-overlay" onclick="closeTestingSummaryModal()"></div>
<div class="ps-modal" id="testing-summary-modal" style="max-width: 980px; width: calc(100vw - 32px); max-height: 92vh; border-radius: 24px; overflow: hidden; padding: 0; box-shadow: 0 25px 65px rgba(0,0,0,0.35); border: 1px solid #e2e8f0; display: none; flex-direction: column;">
    {{-- Header with action buttons --}}
    <div style="padding: 18px 24px; background: #ffffff; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 14px; background: #f0f9ff; color: #0284c7; font-size: 22px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid #bae6fd;">
                🧪
            </div>
            <div>
                <div style="font-size: 18px; font-weight: 900; color: #0f172a; letter-spacing: -0.02em;">
                    Project QA &amp; Testing Summary Report
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    {{ $summaryData['projectName'] ?? $projectName }} &bull; Generated: {{ $summaryData['generatedAt'] ?? now()->format('d M Y') }}
                </div>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('projects.testing-summary-report', $projectItem) }}" target="_blank" class="ps-btn" style="padding: 7px 14px; border-radius: 10px; background: #f8fafc; color: #334155; border: 1.5px solid #cbd5e1; font-weight: 700; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-box-arrow-up-right"></i> Full Page
            </a>
            <button type="button" onclick="printTestingSummaryModal()" class="ps-btn" style="padding: 7px 14px; border-radius: 10px; background: #ffffff; color: #475569; border: 1.5px solid #cbd5e1; font-weight: 700; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-printer"></i> Print
            </button>
            <a href="{{ route('projects.testing-summary-report.export-pdf', $projectItem) }}" class="ps-btn" style="padding: 7px 16px; border-radius: 10px; background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff; border: none; font-weight: 800; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(2,132,199,0.25);">
                <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF
            </a>
            <button type="button" class="ps-modal-close" onclick="closeTestingSummaryModal()" aria-label="Close modal" style="position: static; width: 34px; height: 34px; border-radius: 10px; border: 1.5px solid #e2e8f0; background: #f8fafc; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                <i class="bi bi-x-lg" style="font-size: 13px;"></i>
            </button>
        </div>
    </div>

    {{-- Body (Scrollable printable report area) --}}
    <div id="testingSummaryPrintableArea" style="padding: 24px; overflow-y: auto; flex: 1;">
        {{-- Section 1: Project Details --}}
        <div style="margin-bottom: 22px;">
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-building"></i> 1. PROJECT &amp; CLIENT INFORMATION
            </div>
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 16px 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Project Name</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0284c7; margin-top: 2px;">{{ $summaryData['projectName'] ?? $projectName }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Client / Company</div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ $summaryData['clientName'] ?? 'N/A' }}</div>
                        @if(!empty($summaryData['clientPhone']) || !empty($summaryData['clientEmail']))
                            <div style="font-size: 11.5px; color: #64748b;">{{ $summaryData['clientPhone'] ?? '' }} {{ !empty($summaryData['clientEmail']) ? '• ' . $summaryData['clientEmail'] : '' }}</div>
                        @endif
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Project ID &amp; Dept</div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #334155; margin-top: 2px;">
                            #{{ $projectItem->id }} &bull; {{ $projectItem->department?->name ?? 'Development' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Target Delivery</div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #334155; margin-top: 2px;">
                            {{ $projectItem->project_delivery_date?->format('d M Y') ?? 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Assigned Developer(s)</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                            @if(!empty($summaryData['devUsers']) && $summaryData['devUsers']->isNotEmpty())
                                {{ $summaryData['devUsers']->pluck('name')->implode(', ') }}
                            @else
                                <span style="color: #94a3b8;">Not specified</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Team Lead(s)</div>
                        <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                            @if(!empty($summaryData['tlUsers']) && $summaryData['tlUsers']->isNotEmpty())
                                {{ $summaryData['tlUsers']->pluck('name')->implode(', ') }}
                            @else
                                <span style="color: #94a3b8;">Not specified</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Testing Phase & Handover --}}
        <div style="margin-bottom: 22px;">
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-check2-circle"></i> 2. QA TESTING OVERVIEW &amp; HANDOVER DETAILS
            </div>
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 16px 20px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Current QA Status</div>
                        <div style="margin-top: 4px;">
                            @php
                                $si = $summaryData['qaStatusInfo'] ?? ['label' => 'Open', 'icon' => '🟧', 'bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa'];
                            @endphp
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 12px; font-size: 12.5px; font-weight: 800; background: {{ $si['bg'] }}; color: {{ $si['color'] }}; border: 1.5px solid {{ $si['border'] }};">
                                {{ $si['icon'] }} {{ $si['label'] }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Handover Date &amp; Developer</div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                            {{ $handover?->created_at?->format('d M Y, h:i A') ?? 'N/A' }}
                        </div>
                        <div style="font-size: 11.5px; color: #64748b;">By: <strong>{{ $handover?->movedBy?->name ?? 'Dev Team' }}</strong></div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">QA Tester / Lead</div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                            {{ $handover?->testingTl?->name ?? 'QA Testing Team' }}
                        </div>
                    </div>
                    @if($handover?->testing_link)
                    <div style="grid-column: 1 / -1;">
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Staging / Testing URL</div>
                        <div style="margin-top: 2px;">
                            <a href="{{ $handover->testing_link }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 700; color: #0284c7; text-decoration: underline;">
                                <span>{{ $handover->testing_link }}</span> <i class="bi bi-box-arrow-up-right" style="font-size: 11px;"></i>
                            </a>
                        </div>
                    </div>
                    @endif
                    @if($handover?->credentials)
                    <div style="grid-column: 1 / -1;">
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Testing Credentials</div>
                        <div style="margin-top: 2px; font-family: monospace; font-size: 12px; color: #334155; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px;">
                            {{ $handover->credentials }}
                        </div>
                    </div>
                    @endif
                    @if($handover?->notes)
                    <div style="grid-column: 1 / -1;">
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Handover Notes</div>
                        <div style="margin-top: 2px; font-size: 12.5px; color: #334155; line-height: 1.5; white-space: pre-wrap;">
                            {{ $handover->notes }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Section 3: Executive Bug Metrics (KPI cards) --}}
        <div style="margin-bottom: 22px;">
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-bar-chart-line-fill"></i> 3. QA QUALITY METRICS &amp; BUG RESOLUTION STATUS
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                <div class="tsm-metric-card">
                    <div style="font-size: 22px; font-weight: 900; color: #0284c7;">{{ $summaryData['totalBugs'] ?? 0 }}</div>
                    <div style="font-size: 10.5px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-top: 2px;">Total Bugs</div>
                </div>
                <div class="tsm-metric-card">
                    <div style="font-size: 22px; font-weight: 900; color: #dc2626;">{{ $summaryData['highBugs'] ?? 0 }}</div>
                    <div style="font-size: 10.5px; font-weight: 800; color: #dc2626; text-transform: uppercase; margin-top: 2px;">High Priority</div>
                </div>
                <div class="tsm-metric-card">
                    <div style="font-size: 22px; font-weight: 900; color: #ea580c;">{{ $summaryData['medBugs'] ?? 0 }}</div>
                    <div style="font-size: 10.5px; font-weight: 800; color: #ea580c; text-transform: uppercase; margin-top: 2px;">Med Priority</div>
                </div>
                <div class="tsm-metric-card">
                    <div style="font-size: 22px; font-weight: 900; color: #16a34a;">{{ $summaryData['devCompleted'] ?? 0 }}</div>
                    <div style="font-size: 10.5px; font-weight: 800; color: #16a34a; text-transform: uppercase; margin-top: 2px;">Dev Done</div>
                </div>
                <div class="tsm-metric-card">
                    <div style="font-size: 22px; font-weight: 900; color: #047857;">{{ $summaryData['testerClosed'] ?? 0 }}</div>
                    <div style="font-size: 10.5px; font-weight: 800; color: #047857; text-transform: uppercase; margin-top: 2px;">QA Closed</div>
                </div>
                <div class="tsm-metric-card">
                    <div style="font-size: 22px; font-weight: 900; color: #dc2626;">{{ $summaryData['totalReopens'] ?? 0 }}</div>
                    <div style="font-size: 10.5px; font-weight: 800; color: #dc2626; text-transform: uppercase; margin-top: 2px;">Reopens</div>
                </div>
                <div class="tsm-metric-card" style="background: #f0fdf4; border-color: #bbf7d0;">
                    <div style="font-size: 22px; font-weight: 900; color: #047857;">{{ $summaryData['passRate'] ?? 100 }}%</div>
                    <div style="font-size: 10.5px; font-weight: 800; color: #047857; text-transform: uppercase; margin-top: 2px;">Resolution</div>
                </div>
            </div>
        </div>

        {{-- Section 4: Automated QA Testing Summary & Bulletin Points --}}
        @php
            $bulletins = $summaryData['testingBulletins'] ?? null;
        @endphp
        <div style="margin-bottom: 22px;">
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                <span style="display: flex; align-items: center; gap: 6px;">
                    <i class="bi bi-robot" style="color: #0284c7;"></i> 4. QA AUTOMATED TESTING SUMMARY &amp; BULLETIN POINTS
                </span>
                <span style="font-size: 11px; font-weight: 700; color: #0284c7; background: #f0f9ff; padding: 3px 10px; border-radius: 8px; border: 1px solid #bae6fd;">
                    ✨ Auto-Generated from Reported Bug Descriptions
                </span>
            </div>

            <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 20px; box-shadow: 0 4px 14px rgba(15,23,42,0.03);">
                {{-- Executive Bullet Points --}}
                @if(!empty($bulletins['bulletinPoints']))
                <div style="margin-bottom: 16px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px 20px;">
                    <div style="font-size: 11.5px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <span>📌</span> Key Testing Takeaways &amp; Executive Summary
                    </div>
                    <ul style="margin: 0; padding-left: 20px; color: #1e293b; font-size: 13px; line-height: 1.65;">
                        @foreach($bulletins['bulletinPoints'] as $pt)
                            <li style="margin-bottom: 8px;">{!! $pt !!}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Tested Modules / Functional Scope --}}
                @if(!empty($bulletins['modulesCovered']))
                <div style="margin-bottom: 18px;">
                    <div style="font-size: 11.5px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <span>🎯</span> Functional Areas &amp; Modules Tested by QA
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 10px;">
                        @foreach($bulletins['modulesCovered'] as $mod)
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; display: flex; align-items: flex-start; gap: 10px; transition: all 0.2s ease;">
                            <span style="font-size: 20px; line-height: 1;">{{ $mod['icon'] }}</span>
                            <div>
                                <div style="font-size: 12.5px; font-weight: 800; color: #0f172a;">{{ $mod['title'] }}</div>
                                <div style="font-size: 11.5px; color: #64748b; margin-top: 3px; line-height: 1.4;">{{ $mod['desc'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Key Defect Findings Bulletins --}}
                @if(!empty($bulletins['keyFindings']))
                <div>
                    <div style="font-size: 11.5px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <span>🔍</span> Verified Test Scenarios &amp; Defect Points ({{ count($bulletins['keyFindings']) }})
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach($bulletins['keyFindings'] as $kf)
                        @php
                            $pStyle = match($kf['priority']) {
                                'High' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca'],
                                'Low' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd'],
                                default => ['bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa'],
                            };
                            $tStyle = match($kf['raw_tester_status']) {
                                'closed' => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'border' => '#bbf7d0', 'label' => 'Closed'],
                                'reopen' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'label' => 'Reopen'],
                                default => ['bg' => '#fff7ed', 'color' => '#ea580c', 'border' => '#fed7aa', 'label' => 'Pending'],
                            };
                            $dStyle = match($kf['raw_dev_status']) {
                                'completed' => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'border' => '#bbf7d0', 'label' => 'Dev Completed'],
                                'ongoing' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd', 'label' => 'Dev Ongoing'],
                                default => ['bg' => '#fff7ed', 'color' => '#ea580c', 'border' => '#fed7aa', 'label' => 'Dev Pending'],
                            };
                        @endphp
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; margin-bottom: 2px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                                <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 260px;">
                                    <span style="font-weight: 800; color: #64748b; font-size: 12px; min-width: 22px;">#{{ $kf['index'] }}</span>
                                    <span style="font-size: 10.5px; font-weight: 800; padding: 2px 8px; border-radius: 6px; background: {{ $pStyle['bg'] }}; color: {{ $pStyle['color'] }}; border: 1px solid {{ $pStyle['border'] }};">
                                        {{ $kf['priority'] }}
                                    </span>
                                    <span style="font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.4;">
                                        {{ $kf['summary'] }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 6px; background: {{ $dStyle['bg'] }}; color: {{ $dStyle['color'] }}; border: 1px solid {{ $dStyle['border'] }};">
                                        {{ $dStyle['label'] }}
                                    </span>
                                    @if($kf['reopen_count'] > 0)
                                    <span style="font-size: 11px; font-weight: 800; padding: 2px 7px; border-radius: 6px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;" title="Reopened {{ $kf['reopen_count'] }} time(s)">
                                        🔁 {{ $kf['reopen_count'] }} reopen(s)
                                    </span>
                                    @endif
                                    <span style="font-size: 11px; font-weight: 800; padding: 3px 9px; border-radius: 6px; background: {{ $tStyle['bg'] }}; color: {{ $tStyle['color'] }}; border: 1px solid {{ $tStyle['border'] }};">
                                        QA: {{ $tStyle['label'] }}
                                    </span>
                                </div>
                            </div>
                            @if(!empty($kf['developer_remarks']) || !empty($kf['tester_remarks']))
                                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #cbd5e1; display: flex; flex-direction: column; gap: 6px;">
                                    @if(!empty($kf['developer_remarks']))
                                        <div style="font-size: 12px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 5px 10px; color: #0369a1; display: flex; align-items: baseline; gap: 6px;">
                                            <span style="font-weight: 800; white-space: nowrap;">🛠️ Dev Remarks:</span>
                                            <span style="font-weight: 500; color: #0c4a6e;">{{ $kf['developer_remarks'] }}</span>
                                        </div>
                                    @endif
                                    @if(!empty($kf['tester_remarks']))
                                        <div style="font-size: 12px; background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 5px 10px; color: #6d28d9; display: flex; align-items: baseline; gap: 6px;">
                                            <span style="font-weight: 800; white-space: nowrap;">🧪 QA Remarks:</span>
                                            <span style="font-weight: 500; color: #581c87;">{{ $kf['tester_remarks'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Section 5: Detailed Bug Register Table with Attachment Previews --}}
        <div style="margin-bottom: 22px;">
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-bug-fill"></i> 5. Detailed Bug Register &amp; Proofs ({{ $summaryData['totalBugs'] ?? 0 }} Issues)
            </div>
            @if(!empty($summaryData['bugs']) && count($summaryData['bugs']) > 0)
            <div style="overflow-x: auto; border-radius: 14px; border: 1.5px solid #e2e8f0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                    <thead>
                        <tr style="background: #0f172a; color: #ffffff;">
                            <th style="padding: 10px 12px; text-align: center; width: 36px;">#</th>
                            <th style="padding: 10px 12px; width: 85px;">Priority</th>
                            <th style="padding: 10px 12px;">Bug Description</th>
                            <th style="padding: 10px 12px; width: 140px;">Reported By</th>
                            <th style="padding: 10px 12px; text-align: center; width: 100px;">Dev Status</th>
                            <th style="padding: 10px 12px; text-align: center; width: 100px;">Tester Status</th>
                            <th style="padding: 10px 12px; text-align: center; width: 70px;">Reopen</th>
                            <th style="padding: 10px 12px; width: 170px;">Attachments &amp; Proofs</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaryData['bugs'] as $bItem)
                            @php
                                $pBadge = match($bItem['priority']) {
                                    'High' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca'],
                                    'Low' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd'],
                                    default => ['bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa'],
                                };
                                $dBadge = match($bItem['raw_dev_status']) {
                                    'completed' => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'border' => '#bbf7d0', 'label' => 'Completed'],
                                    'ongoing' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd', 'label' => 'Ongoing'],
                                    default => ['bg' => '#fff7ed', 'color' => '#ea580c', 'border' => '#fed7aa', 'label' => 'Pending'],
                                };
                                $tBadge = match($bItem['raw_tester_status']) {
                                    'closed' => ['bg' => '#f8fafc', 'color' => '#475569', 'border' => '#cbd5e1', 'label' => 'Closed'],
                                    'reopen' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'label' => 'Reopen'],
                                    default => ['bg' => '#fff7ed', 'color' => '#ea580c', 'border' => '#fed7aa', 'label' => 'Pending'],
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                                <td style="padding: 10px 12px; text-align: center; font-weight: 800; color: #64748b;">
                                    {{ $bItem['index'] }}
                                </td>
                                <td style="padding: 10px 12px;">
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 800; background: {{ $pBadge['bg'] }}; color: {{ $pBadge['color'] }}; border: 1px solid {{ $pBadge['border'] }};">
                                        {{ $bItem['priority'] }}
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; white-space: pre-wrap; word-break: break-word; font-weight: 500; color: #1e293b; max-width: 320px;">
                                    {{ $bItem['description'] }}
                                    @if(!empty($bItem['developer_remarks']))
                                        <div style="margin-top: 6px; font-size: 11px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 4px 8px; color: #0369a1;">
                                            <strong>🛠️ Dev:</strong> {{ $bItem['developer_remarks'] }}
                                        </div>
                                    @endif
                                    @if(!empty($bItem['tester_remarks']))
                                        <div style="margin-top: 4px; font-size: 11px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; padding: 4px 8px; color: #6d28d9;">
                                            <strong>🧪 QA:</strong> {{ $bItem['tester_remarks'] }}
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 10px 12px;">
                                    <div style="font-weight: 700; color: #0f172a;">{{ $bItem['created_by'] }}</div>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">{{ $bItem['created_at'] }}</div>
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 8px; font-size: 11.5px; font-weight: 800; background: {{ $dBadge['bg'] }}; color: {{ $dBadge['color'] }}; border: 1px solid {{ $dBadge['border'] }};">
                                        {{ $dBadge['label'] }}
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 8px; font-size: 11.5px; font-weight: 800; background: {{ $tBadge['bg'] }}; color: {{ $tBadge['color'] }}; border: 1px solid {{ $tBadge['border'] }};">
                                        {{ $tBadge['label'] }}
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    @if($bItem['reopen_count'] > 0)
                                        <span style="display: inline-block; padding: 2px 7px; border-radius: 8px; font-size: 11px; font-weight: 800; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                                            {{ $bItem['reopen_count'] }}
                                        </span>
                                    @else
                                        <span style="color: #94a3b8; font-weight: 600;">0</span>
                                    @endif
                                </td>
                                <td style="padding: 10px 12px;">
                                    @if(!empty($bItem['attachments']))
                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                            @foreach($bItem['attachments'] as $att)
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    @if($att['is_image'])
                                                        <img src="{{ $att['url'] }}" alt="{{ $att['name'] }}"
                                                             class="tsm-att-img-preview"
                                                             onclick="openImageLightbox('{{ addslashes($att['url']) }}', '{{ addslashes($att['name']) }}')"
                                                             title="Click to zoom image preview">
                                                    @endif
                                                    <div style="min-width: 0; flex: 1;">
                                                        <a href="{{ $att['url'] }}" target="_blank" style="font-size: 11px; font-weight: 700; color: #0284c7; text-decoration: none; word-break: break-all; display: block;" title="{{ $att['name'] }}">
                                                            <i class="bi bi-paperclip"></i> {{ \Illuminate\Support\Str::limit($att['name'], 16) }}
                                                        </a>
                                                        <a href="{{ $att['url'] }}" target="_blank" style="font-size: 10px; color: #64748b; text-decoration: underline;">Open Link</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #94a3b8; font-size: 11.5px;">No Files</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 28px 20px; background: #f8fafc; border-radius: 14px; border: 1.5px solid #e2e8f0; color: #15803d; font-weight: 800;">
                🎉 No bugs reported. Testing passed cleanly!
            </div>
            @endif
        </div>

        {{-- Section 6: QA Verification & Signoff --}}
        <div>
            <div style="font-size: 13px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                <i class="bi bi-award-fill"></i> 6. QA Verification &amp; Project Sign-off
            </div>
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 16px; padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 260px;">
                    <div style="font-size: 15px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span>Verification Sign-Off:</span>
                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 10px; font-size: 12px; font-weight: 800; background: {{ $si['bg'] }}; color: {{ $si['color'] }}; border: 1.5px solid {{ $si['border'] }};">
                            {{ $si['icon'] }} {{ $si['label'] }}
                        </span>
                    </div>
                    <div style="font-size: 12px; color: #475569; margin-top: 6px; line-height: 1.5;">
                        @if(($summaryData['qaRawStatus'] ?? '') === 'ready_launch')
                            This project has successfully completed quality inspection and bug fixing. It is officially certified as <strong>Ready for Launch</strong> by the QA department.
                        @elseif(($summaryData['qaRawStatus'] ?? '') === 'completed')
                            Testing completed. Verification items have been completed and approved.
                        @else
                            The project is currently under active quality inspection or developer fixes. Final sign-off will be issued upon resolving pending items.
                        @endif
                    </div>
                </div>
                <div style="text-align: center; padding: 10px 18px; border-radius: 12px; background: #ffffff; border: 1.5px solid #cbd5e1;">
                    <div style="font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase;">QA VERIFICATION SEAL</div>
                    <div style="font-size: 24px; margin: 2px 0;">{{ $si['icon'] }}</div>
                    <div style="font-size: 11.5px; font-weight: 900; color: {{ $si['color'] }};">{{ $si['label'] }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div style="padding: 14px 24px; background: #f8fafc; border-top: 1.5px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 12px; color: #64748b;">
            Confidential QA &amp; Testing Audit Report &bull; Sai Techno Solutions
        </div>
        <button type="button" class="ps-btn" onclick="closeTestingSummaryModal()" style="padding: 8px 22px; border-radius: 10px; background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; cursor: pointer;">
            Close
        </button>
    </div>
</div>

{{-- Attachment Image Lightbox Modal --}}
<div class="ps-modal-overlay" id="image-lightbox-modal-overlay" onclick="closeImageLightbox()" style="z-index: 10005;"></div>
<div id="image-lightbox-modal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10006; max-width: 90vw; max-height: 90vh; background: #ffffff; padding: 16px; border-radius: 20px; box-shadow: 0 25px 65px rgba(0,0,0,0.5); text-align: center;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 12px;">
        <span id="lightboxTitle" style="font-size: 13.5px; font-weight: 800; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 70vw;"></span>
        <div style="display: flex; align-items: center; gap: 8px;">
            <a id="lightboxDownloadBtn" href="#" target="_blank" download class="ps-btn" style="padding: 5px 12px; font-size: 12px; border-radius: 8px; background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; text-decoration: none; font-weight: 700;">
                <i class="bi bi-download"></i> Download
            </a>
            <button type="button" onclick="closeImageLightbox()" style="border: none; background: #f1f5f9; width: 30px; height: 30px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #64748b;">
                <i class="bi bi-x-lg" style="font-size: 13px;"></i>
            </button>
        </div>
    </div>
    <div style="max-width: 85vw; max-height: 76vh; overflow: auto; display: flex; align-items: center; justify-content: center;">
        <img id="lightboxImage" src="" alt="Screenshot" style="max-width: 100%; max-height: 74vh; border-radius: 12px; object-fit: contain; box-shadow: 0 4px 14px rgba(0,0,0,0.1);">
    </div>
</div>

<script>
let pendingStatusChange = null;

function handleBugFilesChange(input) {
    const list = document.getElementById('bugFilesPreviewList');
    if (!list) return;
    list.innerHTML = '';
    if (!input.files || input.files.length === 0) return;

    for (let i = 0; i < input.files.length; i++) {
        const file = input.files[i];
        const sizeKb = (file.size / 1024).toFixed(1);
        const item = document.createElement('div');
        item.style.cssText = 'display:flex; align-items:center; justify-content:space-between; padding:4px 10px; background:#f1f5f9; border-radius:6px; font-size:12px; color:#334155;';
        item.innerHTML = `
            <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:380px;">
                📎 <strong>${file.name}</strong>
            </span>
            <span style="color:#64748b; font-size:11px; margin-left:8px;">${sizeKb} KB</span>
        `;
        list.appendChild(item);
    }
}

function openAddBugModal() {
    const btn = document.getElementById('btnSubmitBug');
    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
        const icon = document.getElementById('btnSubmitBugIcon');
        const text = document.getElementById('btnSubmitBugText');
        if (icon) icon.innerHTML = '🐞';
        if (text) text.textContent = 'Submit Bug Report';
    }
    const preview = document.getElementById('bugFilesPreviewList');
    if (preview) preview.innerHTML = '';
    const fileInp = document.getElementById('bugAttachmentsInput');
    if (fileInp) fileInp.value = '';

    document.getElementById('add-bug-modal-overlay').classList.add('is-open');
    document.getElementById('add-bug-modal').classList.add('is-open');
}
function closeAddBugModal() {
    document.getElementById('add-bug-modal-overlay').classList.remove('is-open');
    document.getElementById('add-bug-modal').classList.remove('is-open');
}
function handleAddBugSubmit(event, form) {
    const btn = document.getElementById('btnSubmitBug');
    if (btn) {
        if (btn.disabled) {
            event.preventDefault();
            return false;
        }
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'wait';
        const icon = document.getElementById('btnSubmitBugIcon');
        const text = document.getElementById('btnSubmitBugText');
        if (icon) icon.innerHTML = '<i class="bi bi-arrow-repeat ps-spin-icon"></i>';
        if (text) text.textContent = ' Submitting Bug Report...';
    }
}

function handleQaStatusChange(selectElem) {
    const newStatus = selectElem.value;
    const oldStatus = selectElem.getAttribute('data-current');
    if (newStatus === oldStatus) return;

    const statusMap = {
        'open': { label: 'Open (New Handover)', icon: '🟧', color: '#c2410c', bg: '#fff7ed', border: '#fed7aa' },
        'ongoing': { label: 'Ongoing Testing', icon: '🟦', color: '#0369a1', bg: '#f0f9ff', border: '#bae6fd' },
        'retesting': { label: 'Retesting Phase', icon: '🟪', color: '#6d28d9', bg: '#f5f3ff', border: '#ddd6fe' },
        'completed': { label: 'Completed (QA Passed)', icon: '🟩', color: '#15803d', bg: '#f0fdf4', border: '#bbf7d0' },
        'ready_launch': { label: 'Ready to Launch', icon: '🚀', color: '#047857', bg: '#ecfdf5', border: '#a7f3d0' }
    };

    const oldInfo = statusMap[oldStatus] || { label: oldStatus, icon: '🏷️', color: '#475569', bg: '#f1f5f9', border: '#cbd5e1' };
    const newInfo = statusMap[newStatus] || { label: newStatus, icon: '🏷️', color: '#475569', bg: '#f1f5f9', border: '#cbd5e1' };

    pendingStatusChange = {
        form: selectElem.form,
        select: selectElem,
        oldStatus: oldStatus,
        newStatus: newStatus,
        type: 'qa',
        isReadyLaunch: (newStatus === 'ready_launch')
    };

    const iconWrapper = document.getElementById('scmIconWrapper');
    const title = document.getElementById('scmTitle');
    const subtitle = document.getElementById('scmSubtitle');
    const fromBadge = document.getElementById('scmFromBadge');
    const toBadge = document.getElementById('scmToBadge');
    const emailNotice = document.getElementById('scmEmailNotice');
    const confirmBtn = document.getElementById('scmConfirmBtn');
    const confirmBtnText = document.getElementById('scmConfirmBtnText');

    if (newStatus === 'ready_launch') {
        iconWrapper.innerHTML = '🚀';
        iconWrapper.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        iconWrapper.style.color = '#ffffff';
        iconWrapper.style.boxShadow = '0 10px 25px rgba(16,185,129,0.35)';
        title.innerHTML = 'Approve for Ready Launch?';
        subtitle.innerHTML = 'This project will be marked as QA Approved & Ready for Launch.';
        emailNotice.style.display = 'block';
        confirmBtn.style.background = 'linear-gradient(135deg, #059669, #10b981)';
        confirmBtn.style.boxShadow = '0 4px 16px rgba(16,185,129,0.4)';
        confirmBtnText.innerHTML = '<i class="bi bi-rocket-takeoff-fill"></i> Confirm & Send Mail';
    } else {
        iconWrapper.innerHTML = newInfo.icon;
        iconWrapper.style.background = newInfo.bg;
        iconWrapper.style.color = newInfo.color;
        iconWrapper.style.boxShadow = '0 8px 20px rgba(0,0,0,0.06)';
        title.innerHTML = 'Confirm QA Status Change';
        subtitle.innerHTML = 'Are you sure you want to update the testing status?';
        emailNotice.style.display = 'none';
        confirmBtn.style.background = 'linear-gradient(135deg, #0284c7, #0369a1)';
        confirmBtn.style.boxShadow = '0 4px 14px rgba(2,132,199,0.3)';
        confirmBtnText.innerHTML = '<i class="bi bi-check2-circle"></i> Yes, Update Status';
    }

    fromBadge.innerHTML = oldInfo.icon + ' ' + oldInfo.label;
    fromBadge.style.color = oldInfo.color;
    fromBadge.style.background = oldInfo.bg;
    fromBadge.style.border = '1px solid ' + oldInfo.border;

    toBadge.innerHTML = newInfo.icon + ' ' + newInfo.label;
    toBadge.style.color = newInfo.color;
    toBadge.style.background = newInfo.bg;
    toBadge.style.border = '1px solid ' + newInfo.border;

    document.getElementById('status-confirm-modal-overlay').classList.add('is-open');
    document.getElementById('status-confirm-modal').classList.add('is-open');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

let currentBugModalData = null;

function openBugStatusModalById(bugId, targetRole) {
    try {
        const bug = (window._bugDataMap && window._bugDataMap[bugId]) ? window._bugDataMap[bugId] : null;
        if (!bug) {
            console.error('Bug not found in _bugDataMap for ID:', bugId);
            return;
        }
        openBugStatusModalWithData(bug, targetRole);
    } catch (e) {
        console.error('Error in openBugStatusModalById:', e);
    }
}

function openBugStatusModalDirect(btn, targetRole) {
    try {
        if (typeof btn === 'number' || (typeof btn === 'string' && !isNaN(btn))) {
            openBugStatusModalById(Number(btn), targetRole);
            return;
        }
        let bug = null;
        const raw = btn ? btn.getAttribute('data-bug') : null;
        if (raw) {
            bug = JSON.parse(raw);
        } else if (btn && btn.getAttribute('data-bug-id')) {
            const bId = btn.getAttribute('data-bug-id');
            bug = window._bugDataMap ? window._bugDataMap[bId] : null;
        }
        if (!bug) return;
        openBugStatusModalWithData(bug, targetRole, btn ? btn.getAttribute('data-update-url') : null);
    } catch (e) {
        console.error('Error opening status modal:', e);
    }
}

function openBugStatusModalWithData(bug, targetRole, customUpdateUrl = null) {
    try {
        currentBugModalData = bug;

        const updateUrl = customUpdateUrl || bug.update_url;
        const form = document.getElementById('bugStatusForm');
        if (form && updateUrl) {
            form.action = updateUrl;
        }

        const idInput = document.getElementById('bsmBugId');
        if (idInput) idInput.value = bug.id;

        const typeInput = document.getElementById('bsmStatusType');
        if (typeInput) typeInput.value = targetRole || '';

        const title = document.getElementById('bsmTitle');
        if (title) {
            title.textContent = 'Update Bug Status (Bug #' + bug.index + ')';
        }

        const indexText = document.getElementById('bsmIndexText');
        if (indexText) {
            indexText.textContent = 'Bug #' + bug.index;
        }

        const descElem = document.getElementById('bsmDescSnippet');
        if (descElem) {
            descElem.textContent = bug.description || 'No description provided.';
        }

        const pBadge = document.getElementById('bsmPriorityBadge');
        if (pBadge && bug.priority_badge) {
            pBadge.innerHTML = `<span style="display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; background:${bug.priority_badge.bg}; color:${bug.priority_badge.color}; border:1px solid ${bug.priority_badge.border}; font-size:11px; font-weight:800;"><i class="bi ${bug.priority_badge.icon}"></i> ${escapeHtml(bug.priority)} Priority</span>`;
        }

        // Developer Status setup
        const devSelect = document.getElementById('bsmDevStatus');
        const devDisabled = document.getElementById('bsmDevStatusDisabled');
        const devBox = document.getElementById('bsmDevBox');
        const canEditDev = (bug.can_edit_dev === true || bug.can_edit_dev === 1);

        if (devSelect && devDisabled) {
            if (canEditDev) {
                devSelect.style.display = 'block';
                devSelect.disabled = false;
                devDisabled.style.display = 'none';
                devSelect.value = bug.dev_status || 'pending';
            } else {
                devSelect.style.display = 'none';
                devSelect.disabled = true;
                devDisabled.style.display = 'block';
                const dLabel = bug.dev_status_info ? bug.dev_status_info.label : (bug.dev_status || 'Pending');
                const dIcon = bug.dev_status_info ? bug.dev_status_info.icon : '⏳';
                devDisabled.innerHTML = `${dIcon} ${dLabel} <span style="font-size:10px; opacity:0.75; font-weight:500;">(Non-editable in Testing login)</span>`;
            }
        }

        // Tester Status setup
        const testerSelect = document.getElementById('bsmTesterStatus');
        const testerDisabled = document.getElementById('bsmTesterStatusDisabled');
        const testerBox = document.getElementById('bsmTesterBox');
        const canEditTester = (bug.can_edit_tester === true || bug.can_edit_tester === 1);

        if (testerSelect && testerDisabled) {
            if (canEditTester) {
                testerSelect.style.display = 'block';
                testerSelect.disabled = false;
                testerDisabled.style.display = 'none';
                testerSelect.value = bug.tester_status || 'pending';
            } else {
                testerSelect.style.display = 'none';
                testerSelect.disabled = true;
                testerDisabled.style.display = 'block';
                const tLabel = bug.tester_status_info ? bug.tester_status_info.label : (bug.tester_status || 'Pending');
                const tIcon = bug.tester_status_info ? bug.tester_status_info.icon : '⏳';
                testerDisabled.innerHTML = `${tIcon} ${tLabel} <span style="font-size:10px; opacity:0.75; font-weight:500;">(Non-editable in Developer login)</span>`;
            }
        }

        // Highlight based on target role
        if (devBox) devBox.style.borderColor = (targetRole === 'developer') ? '#0284c7' : '#bae6fd';
        if (testerBox) testerBox.style.borderColor = (targetRole === 'tester') ? '#7c3aed' : '#e9d5ff';

        // Previous Remarks display
        const prevBox = document.getElementById('bsmPrevRemarksBox');
        const prevDev = document.getElementById('bsmPrevDevRemarks');
        const prevTester = document.getElementById('bsmPrevTesterRemarks');
        let hasPrev = false;

        if (prevDev) {
            if (bug.developer_remarks) {
                prevDev.style.display = 'block';
                prevDev.querySelector('span').textContent = bug.developer_remarks;
                hasPrev = true;
            } else {
                prevDev.style.display = 'none';
            }
        }
        if (prevTester) {
            if (bug.tester_remarks) {
                prevTester.style.display = 'block';
                prevTester.querySelector('span').textContent = bug.tester_remarks;
                hasPrev = true;
            } else {
                prevTester.style.display = 'none';
            }
        }
        if (prevBox) {
            prevBox.style.display = hasPrev ? 'block' : 'none';
        }

        // Reset Remarks Input
        const remarksInput = document.getElementById('bsmRemarksInput');
        const remarksError = document.getElementById('bsmRemarksError');
        if (remarksInput) {
            remarksInput.value = '';
            remarksInput.style.borderColor = '#cbd5e1';
            if (targetRole === 'developer') {
                remarksInput.placeholder = 'Explain fixes applied, code modifications, or development progress (Mandatory)...';
            } else if (targetRole === 'tester') {
                remarksInput.placeholder = 'Explain QA verification findings, edge cases tested, or reopen/close reason (Mandatory)...';
            } else {
                remarksInput.placeholder = 'Enter reason or update notes (Mandatory)...';
            }
        }
        if (remarksError) {
            remarksError.style.display = 'none';
        }

        const overlay = document.getElementById('bug-status-modal-overlay');
        const modal = document.getElementById('bug-status-modal');
        if (overlay) {
            overlay.style.display = '';
            overlay.classList.add('is-open');
        }
        if (modal) {
            modal.style.display = '';
            modal.classList.add('is-open');
        }

        setTimeout(() => {
            if (targetRole === 'developer' && canEditDev && devSelect) {
                devSelect.focus();
            } else if (targetRole === 'tester' && canEditTester && testerSelect) {
                testerSelect.focus();
            } else if (remarksInput) {
                remarksInput.focus();
            }
        }, 80);
    } catch (e) {
        console.error('Error opening status modal with data:', e);
    }
}

function handleBugStatusChange(selectElem) {
    const raw = selectElem.form ? selectElem.form.getAttribute('data-bug') : null;
    const isDev = (selectElem.getAttribute('data-title') || '').toLowerCase().includes('dev');
    openBugStatusModalDirect(selectElem, isDev ? 'developer' : 'tester');
}

function closeBugStatusModal() {
    currentBugModalData = null;
    const overlay = document.getElementById('bug-status-modal-overlay');
    const modal = document.getElementById('bug-status-modal');
    if (overlay) {
        overlay.classList.remove('is-open');
        overlay.style.display = '';
    }
    if (modal) {
        modal.classList.remove('is-open');
        modal.style.display = '';
    }
}

function submitBugStatusWithRemarks() {
    const remarksInput = document.getElementById('bsmRemarksInput');
    const remarksError = document.getElementById('bsmRemarksError');
    const val = remarksInput ? remarksInput.value.trim() : '';

    if (!val || val.length < 2) {
        if (remarksError) remarksError.style.display = 'block';
        if (remarksInput) {
            remarksInput.focus();
            remarksInput.style.borderColor = '#dc2626';
        }
        return;
    }

    const form = document.getElementById('bugStatusForm');
    if (!form) return;

    closeBugStatusModal();
    if (typeof showPreloader === 'function') {
        showPreloader(false);
    }
    form.submit();
}

function closeStatusConfirmModal() {
    if (pendingStatusChange && pendingStatusChange.select) {
        pendingStatusChange.select.value = pendingStatusChange.oldStatus;
    }
    pendingStatusChange = null;
    document.getElementById('status-confirm-modal-overlay').classList.remove('is-open');
    document.getElementById('status-confirm-modal').classList.remove('is-open');
}

function confirmStatusChangeSubmit() {
    if (!pendingStatusChange) return;

    const change = pendingStatusChange;
    document.getElementById('status-confirm-modal-overlay').classList.remove('is-open');
    document.getElementById('status-confirm-modal').classList.remove('is-open');

    showPreloader(change.isReadyLaunch);
    change.form.submit();
}

function showPreloader(isReadyLaunch = false) {
    const overlay = document.getElementById('statusPreloaderOverlay');
    const card = document.getElementById('preloaderCard');
    const icon = document.getElementById('preloaderIcon');
    const title = document.getElementById('preloaderTitle');
    const subtitle = document.getElementById('preloaderSubtitle');

    if (isReadyLaunch) {
        icon.innerHTML = '🚀';
        title.innerHTML = 'Updating &amp; Sending Notification...';
        subtitle.innerHTML = 'Dispatching QA Signoff notification email to Developer, Team Lead &amp; Project Coordinator...';
    } else {
        icon.innerHTML = '⏳';
        title.innerHTML = 'Updating Project Status...';
        subtitle.innerHTML = 'Please wait while the status is being updated.';
    }

    overlay.style.display = 'flex';
    setTimeout(() => {
        overlay.style.opacity = '1';
        if (card) card.style.transform = 'scale(1)';
    }, 10);
}

function openBugHistoryModalById(bugId) {
    try {
        const bug = (window._bugDataMap && window._bugDataMap[bugId]) ? window._bugDataMap[bugId] : null;
        if (!bug) {
            console.error('Bug not found for ID:', bugId);
            return;
        }
        openBugHistoryModalWithData(bug);
    } catch (e) {
        console.error('Error in openBugHistoryModalById:', e);
    }
}

function openBugHistoryModal(btn) {
    try {
        if (typeof btn === 'number' || (typeof btn === 'string' && !isNaN(btn))) {
            openBugHistoryModalById(Number(btn));
            return;
        }
        let bug = null;
        const raw = btn ? btn.getAttribute('data-bug') : null;
        if (raw) {
            bug = JSON.parse(raw);
        } else if (btn && btn.getAttribute('data-bug-id')) {
            const bId = btn.getAttribute('data-bug-id');
            bug = window._bugDataMap ? window._bugDataMap[bId] : null;
        }
        if (!bug) return;
        openBugHistoryModalWithData(bug);
    } catch (e) {
        console.error('Error opening bug history modal:', e);
    }
}

function openBugHistoryModalWithData(bug) {
    try {
        const indexBadge = document.getElementById('bhmIndexBadge');
        if (indexBadge) indexBadge.textContent = 'Bug #' + bug.index;

        const pBadge = document.getElementById('bhmPriorityBadge');
        if (pBadge && bug.priority_badge) {
            pBadge.innerHTML = `<span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:12px; background:${bug.priority_badge.bg}; color:${bug.priority_badge.color}; border:1px solid ${bug.priority_badge.border}; font-size:11px; font-weight:800;"><i class="bi ${bug.priority_badge.icon}"></i> ${escapeHtml(bug.priority)} Priority</span>`;
        }

        const repElem = document.getElementById('bhmReporter');
        if (repElem) repElem.textContent = 'Reported by ' + (bug.created_by || 'QA Tester') + ' • ' + (bug.created_at || 'Recently');

        const descElem = document.getElementById('bhmDescSnippet');
        if (descElem) descElem.textContent = bug.description || 'No description';

        renderBugTimeline(bug.status_history, bug);

        const overlay = document.getElementById('bug-history-modal-overlay');
        const modal = document.getElementById('bug-history-modal');
        if (overlay) {
            overlay.style.display = '';
            overlay.classList.add('is-open');
        }
        if (modal) {
            modal.style.display = '';
            modal.classList.add('is-open');
        }
    } catch (e) {
        console.error('Error opening bug history modal with data:', e);
    }
}

function closeBugHistoryModal() {
    const overlay = document.getElementById('bug-history-modal-overlay');
    const modal = document.getElementById('bug-history-modal');
    if (overlay) {
        overlay.classList.remove('is-open');
        overlay.style.display = '';
    }
    if (modal) {
        modal.classList.remove('is-open');
        modal.style.display = '';
    }
}

function renderBugTimeline(history, bug) {
    const remarksContainer = document.getElementById('bhmLatestRemarksContainer');
    if (remarksContainer) {
        let rHtml = '';
        if (bug.developer_remarks) {
            rHtml += `
                <div style="background: #f0f9ff; border: 1.5px solid #bae6fd; border-radius: 12px; padding: 10px 14px;">
                    <div style="font-size: 11px; font-weight: 800; color: #0369a1; text-transform: uppercase; margin-bottom: 3px; display: flex; align-items: center; gap: 6px;">
                        <span>🛠️</span> Latest Developer Remarks
                    </div>
                    <div style="font-size: 13px; color: #0c4a6e; font-weight: 600; line-height: 1.5;">${escapeHtml(bug.developer_remarks)}</div>
                </div>
            `;
        }
        if (bug.tester_remarks) {
            rHtml += `
                <div style="background: #faf5ff; border: 1.5px solid #e9d5ff; border-radius: 12px; padding: 10px 14px;">
                    <div style="font-size: 11px; font-weight: 800; color: #7e22ce; text-transform: uppercase; margin-bottom: 3px; display: flex; align-items: center; gap: 6px;">
                        <span>🧪</span> Latest Tester Remarks
                    </div>
                    <div style="font-size: 13px; color: #581c87; font-weight: 600; line-height: 1.5;">${escapeHtml(bug.tester_remarks)}</div>
                </div>
            `;
        }
        remarksContainer.innerHTML = rHtml;
        remarksContainer.style.display = rHtml ? 'flex' : 'none';
    }

    const list = document.getElementById('bhmTimelineList');
    if (!list) return;
    list.innerHTML = '';

    const entries = Array.isArray(history) ? history : [];
    if (entries.length === 0) {
        if (bug.developer_remarks || bug.tester_remarks) {
            let html = '';
            if (bug.developer_remarks) {
                html += createTimelineItemHtml({
                    role_type: 'developer',
                    user_name: 'Developer',
                    formatted_date: 'Recorded',
                    from_status: 'pending',
                    to_status: bug.dev_status?.label || 'ongoing',
                    remarks: bug.developer_remarks
                });
            }
            if (bug.tester_remarks) {
                html += createTimelineItemHtml({
                    role_type: 'tester',
                    user_name: bug.created_by || 'QA Tester',
                    formatted_date: 'Recorded',
                    from_status: 'pending',
                    to_status: bug.tester_status?.label || 'pending',
                    remarks: bug.tester_remarks
                });
            }
            list.innerHTML = html;
            return;
        }

        list.innerHTML = `
            <div style="text-align: center; padding: 28px 16px; background: #f8fafc; border-radius: 14px; border: 1.5px dashed #cbd5e1; color: #64748b;">
                <div style="font-size: 26px; margin-bottom: 6px;">📝</div>
                <div style="font-size: 13.5px; font-weight: 700; color: #334155;">No Status Updates Recorded Yet</div>
                <div style="font-size: 12px; margin-top: 4px;">When a Developer or Tester updates the status with mandatory remarks, each step will be logged here in chronological order.</div>
            </div>
        `;
        return;
    }

    let html = '<div style="position: absolute; top: 12px; bottom: 12px; left: 19px; width: 2px; background: #e2e8f0; z-index: 0;"></div>';
    entries.forEach((item, idx) => {
        html += createTimelineItemHtml(item);
    });
    list.innerHTML = html;
}

function createTimelineItemHtml(item) {
    const isDev = (item.role_type === 'developer');
    const roleIcon = isDev ? '👨‍💻' : '🧪';
    const roleBadgeBg = isDev ? '#f0f9ff' : '#f5f3ff';
    const roleBadgeColor = isDev ? '#0284c7' : '#6d28d9';
    const roleBadgeBorder = isDev ? '#bae6fd' : '#ddd6fe';
    const roleName = isDev ? 'Developer' : 'QA Tester';

    const fromLabel = escapeHtml(item.from_status || 'pending');
    const toLabel = escapeHtml(item.to_status || 'updated');
    const remarks = escapeHtml(item.remarks || '');
    const dateStr = escapeHtml(item.formatted_date || item.created_at || 'Recently');
    const userName = escapeHtml(item.user_name || (isDev ? 'Developer' : 'Tester'));

    return `
        <div style="position: relative; z-index: 1; display: flex; gap: 14px; margin-bottom: 18px;">
            <div style="width: 40px; height: 40px; border-radius: 12px; background: ${roleBadgeBg}; border: 1.5px solid ${roleBadgeBorder}; color: ${roleBadgeColor}; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
                ${roleIcon}
            </div>
            <div style="flex: 1; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; margin-bottom: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px; background: ${roleBadgeBg}; color: ${roleBadgeColor}; border: 1px solid ${roleBadgeBorder}; text-transform: uppercase;">
                            ${roleName}
                        </span>
                        <strong style="font-size: 13px; color: #0f172a;">${userName}</strong>
                    </div>
                    <span style="font-size: 11.5px; color: #64748b; font-weight: 500;">
                        <i class="bi bi-clock"></i> ${dateStr}
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px;">
                    <span style="padding: 2px 7px; border-radius: 6px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 11px; text-transform: capitalize;">${fromLabel}</span>
                    <span style="color: #94a3b8;">➔</span>
                    <span style="padding: 2px 7px; border-radius: 6px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 11px; text-transform: capitalize;">${toLabel}</span>
                </div>
                <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-left: 3px solid ${roleBadgeColor}; border-radius: 6px; padding: 8px 12px; font-size: 12.5px; color: #1e293b; line-height: 1.5; white-space: pre-wrap; word-break: break-word;">
                    ${remarks}
                </div>
            </div>
        </div>
    `;
}

function openBugViewModalById(bugId) {
    try {
        const bug = (window._bugDataMap && window._bugDataMap[bugId]) ? window._bugDataMap[bugId] : null;
        if (!bug) {
            console.error('Bug not found for ID:', bugId);
            return;
        }
        openBugViewModalWithData(bug);
    } catch (e) {
        console.error('Error in openBugViewModalById:', e);
    }
}

function openBugViewModal(btn) {
    try {
        if (typeof btn === 'number' || (typeof btn === 'string' && !isNaN(btn))) {
            openBugViewModalById(Number(btn));
            return;
        }
        let bug = null;
        const raw = btn ? btn.getAttribute('data-bug') : null;
        if (raw) {
            bug = JSON.parse(raw);
        } else if (btn && btn.getAttribute('data-bug-id')) {
            const bId = btn.getAttribute('data-bug-id');
            bug = window._bugDataMap ? window._bugDataMap[bId] : null;
        }
        if (!bug) return;
        openBugViewModalWithData(bug);
    } catch (e) {
        console.error('Error opening bug view modal:', e);
    }
}

function openBugViewModalWithData(bug) {
    try {
        const indexElem = document.getElementById('vbmIndex');
        if (indexElem) indexElem.textContent = bug.index;

        // Priority
        const pBadge = document.getElementById('vbmPriorityBadge');
        if (pBadge && bug.priority_badge) {
            pBadge.style.background = bug.priority_badge.bg;
            pBadge.style.color = bug.priority_badge.color;
            pBadge.style.borderColor = bug.priority_badge.border;
            pBadge.innerHTML = `<i class="bi ${bug.priority_badge.icon}"></i> ${escapeHtml(bug.priority)} Priority`;
        }

        // Reporter & Date
        const reporterElem = document.getElementById('vbmReporter');
        if (reporterElem) reporterElem.textContent = bug.created_by || 'QA Tester';

        const dateElem = document.getElementById('vbmDate');
        if (dateElem) dateElem.textContent = bug.created_at || 'Recently';

        // Dev Status
        const devElem = document.getElementById('vbmDevStatus');
        if (devElem && bug.dev_status_info) {
            devElem.style.background = bug.dev_status_info.bg;
            devElem.style.color = bug.dev_status_info.color;
            devElem.style.borderColor = bug.dev_status_info.border;
            devElem.innerHTML = `${bug.dev_status_info.icon} ${escapeHtml(bug.dev_status_info.label)}`;
        }

        // Tester Status
        const testerElem = document.getElementById('vbmTesterStatus');
        if (testerElem && bug.tester_status_info) {
            testerElem.style.background = bug.tester_status_info.bg;
            testerElem.style.color = bug.tester_status_info.color;
            testerElem.style.borderColor = bug.tester_status_info.border;
            testerElem.innerHTML = `${bug.tester_status_info.icon} ${escapeHtml(bug.tester_status_info.label)}`;
        }

        // Reopen Count
        const reopenWrapper = document.getElementById('vbmReopenWrapper');
        const reopenBadge = document.getElementById('vbmReopenBadge');
        if (reopenWrapper && reopenBadge) {
            if (bug.reopen_count > 0) {
                reopenBadge.textContent = bug.reopen_count;
                reopenWrapper.style.display = 'inline-flex';
            } else {
                reopenWrapper.style.display = 'none';
            }
        }

        // Description
        const descElem = document.getElementById('vbmDescription');
        if (descElem) {
            descElem.textContent = bug.description || 'No description provided.';
        }

        // Attachments
        const attSection = document.getElementById('vbmAttachmentsSection');
        const attListElem = document.getElementById('vbmAttachmentsList');
        if (attSection && attListElem) {
            attListElem.innerHTML = '';
            if (bug.attachments && bug.attachments.length > 0) {
                attSection.style.display = 'block';
                bug.attachments.forEach(att => {
                    const isImg = /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(att.name || att.url);
                    const item = document.createElement('a');
                    item.href = att.url;
                    item.target = '_blank';
                    item.rel = 'noopener noreferrer';
                    item.className = 'vbm-att-item';
                    item.style.cssText = 'display:flex; align-items:center; gap:12px; padding:10px 14px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:14px; text-decoration:none; color:#1e293b; transition:all 0.2s cubic-bezier(0.4, 0, 0.2, 1);';

                    let iconOrThumb = '';
                    if (isImg) {
                        iconOrThumb = `<img src="${encodeURI(att.url)}" alt="${escapeHtml(att.name)}" style="width:42px; height:42px; object-fit:cover; border-radius:8px; border:1px solid #cbd5e1; flex-shrink:0;">`;
                    } else {
                        iconOrThumb = `<div style="width:42px; height:42px; border-radius:8px; background:#e0f2fe; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0;"><i class="bi bi-file-earmark-arrow-down"></i></div>`;
                    }

                    item.innerHTML = `
                        ${iconOrThumb}
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:13px; font-weight:700; color:#0f172a; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(att.name)}</div>
                            <div style="font-size:11.5px; color:#0284c7; font-weight:600; margin-top:2px; display:flex; align-items:center; gap:4px;">
                                <span>Click to View / Download</span> <i class="bi bi-box-arrow-up-right" style="font-size:10px;"></i>
                            </div>
                        </div>
                    `;
                    attListElem.appendChild(item);
                });
            } else {
                attSection.style.display = 'none';
            }
        }

        // Open modal
        const overlay = document.getElementById('view-bug-modal-overlay');
        const modal = document.getElementById('view-bug-modal');
        if (overlay) overlay.classList.add('is-open');
        if (modal) modal.classList.add('is-open');
    } catch (e) {
        console.error('Error opening bug view modal with data:', e);
    }
}

function closeBugViewModal() {
    const overlay = document.getElementById('view-bug-modal-overlay');
    const modal = document.getElementById('view-bug-modal');
    if (overlay) overlay.classList.remove('is-open');
    if (modal) modal.classList.remove('is-open');
}

function copyBugDescription() {
    const text = document.getElementById('vbmDescription')?.textContent;
    if (!text) return;

    navigator.clipboard.writeText(text).then(() => {
        const btn = document.getElementById('vbmCopyBtn');
        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
            btn.style.background = '#dcfce7';
            btn.style.color = '#15803d';
            btn.style.borderColor = '#86efac';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.background = '#f1f5f9';
                btn.style.color = '#475569';
                btn.style.borderColor = '#cbd5e1';
            }, 2000);
        }
    }).catch(err => {
        console.error('Failed to copy description:', err);
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function toggleSummaryDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('summaryReportDropdownMenu');
    const chevron = document.getElementById('summaryDropdownChevron');
    if (!menu) return;
    const isShown = menu.style.display === 'block';
    menu.style.display = isShown ? 'none' : 'block';
    if (chevron) {
        chevron.style.transform = isShown ? 'rotate(0deg)' : 'rotate(180deg)';
    }
}

function closeSummaryDropdown() {
    const menu = document.getElementById('summaryReportDropdownMenu');
    const chevron = document.getElementById('summaryDropdownChevron');
    if (menu) menu.style.display = 'none';
    if (chevron) chevron.style.transform = 'rotate(0deg)';
}

document.addEventListener('click', function(e) {
    const dropdown = document.querySelector('.ptd-summary-dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        closeSummaryDropdown();
    }
});

function openTestingSummaryModal() {
    const overlay = document.getElementById('testing-summary-modal-overlay');
    const modal = document.getElementById('testing-summary-modal');
    if (overlay) overlay.classList.add('is-open');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('is-open');
    }
}

function closeTestingSummaryModal() {
    const overlay = document.getElementById('testing-summary-modal-overlay');
    const modal = document.getElementById('testing-summary-modal');
    if (overlay) overlay.classList.remove('is-open');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('is-open');
    }
}

function printTestingSummaryModal() {
    const printable = document.getElementById('testingSummaryPrintableArea');
    if (!printable) {
        window.print();
        return;
    }
    const printWindow = window.open('', '_blank', 'width=950,height=750');
    if (!printWindow) {
        window.print();
        return;
    }
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>QA Testing Summary Report</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 20px; color: #1e293b; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #e2e8f0; padding: 8px 10px; font-size: 12px; }
                th { background: #0f172a; color: white; }
                .tsm-att-img-preview { max-width: 65px; height: auto; }
            </style>
        </head>
        <body>
            <div style="margin-bottom: 18px; border-bottom: 2px solid #0284c7; padding-bottom: 10px;">
                <h2 style="color: #0284c7; margin: 0;">🧪 Project QA & Testing Summary Report</h2>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Sai Techno Solutions - Quality Assurance</div>
            </div>
            ${printable.innerHTML}
            <script>
                window.onload = function() { window.print(); window.close(); }
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function openImageLightbox(src, title) {
    const overlay = document.getElementById('image-lightbox-modal-overlay');
    const modal = document.getElementById('image-lightbox-modal');
    const img = document.getElementById('lightboxImage');
    const t = document.getElementById('lightboxTitle');
    const dl = document.getElementById('lightboxDownloadBtn');

    if (img) img.src = src;
    if (t) t.textContent = title || 'Screenshot Preview';
    if (dl) {
        dl.href = src;
        dl.setAttribute('download', title || 'screenshot.jpg');
    }

    if (overlay) overlay.classList.add('is-open');
    if (modal) modal.style.display = 'block';
}

function closeImageLightbox() {
    const overlay = document.getElementById('image-lightbox-modal-overlay');
    const modal = document.getElementById('image-lightbox-modal');
    if (overlay) overlay.classList.remove('is-open');
    if (modal) modal.style.display = 'none';
}

function openDevRemarksModal() {
    const overlay = document.getElementById('dev-remarks-modal-overlay');
    const modal = document.getElementById('dev-remarks-modal');
    if (overlay) overlay.classList.add('is-open');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('is-open');
    }
}

function closeDevRemarksModal() {
    const overlay = document.getElementById('dev-remarks-modal-overlay');
    const modal = document.getElementById('dev-remarks-modal');
    if (overlay) overlay.classList.remove('is-open');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('is-open');
    }
}

function copyDevRemarks() {
    const text = document.getElementById('devModalRemarks')?.textContent;
    if (!text) return;

    navigator.clipboard.writeText(text.trim()).then(() => {
        const btn = document.getElementById('btnCopyRemarks');
        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
            btn.style.background = '#dcfce7';
            btn.style.color = '#15803d';
            btn.style.borderColor = '#86efac';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.background = '#fff7ed';
                btn.style.color = '#c2410c';
                btn.style.borderColor = '#fed7aa';
            }, 2000);
        }
    }).catch(err => {
        console.error('Failed to copy remarks:', err);
    });
}

function copyDevCredentials() {
    const text = document.getElementById('devModalCredentials')?.textContent;
    if (!text) return;

    navigator.clipboard.writeText(text.trim()).then(() => {
        const btn = document.getElementById('btnCopyCreds');
        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
            btn.style.background = '#dcfce7';
            btn.style.color = '#15803d';
            btn.style.borderColor = '#86efac';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
                btn.style.background = '#f0f9ff';
                btn.style.color = '#0284c7';
                btn.style.borderColor = '#bae6fd';
            }, 2000);
        }
    }).catch(err => {
        console.error('Failed to copy credentials:', err);
    });
}
</script>
@endsection
