@extends('layouts.app')

@section('title', 'QA & Testing Summary Report - ' . $projectName)

@push('styles')
<style>
.tsr-container {
    max-width: 1180px;
    margin: 28px auto 60px auto;
    padding: 0 20px;
}
.tsr-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.tsr-btn-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    text-decoration: none;
    padding: 9px 18px;
    border-radius: 12px;
    background: #ffffff;
    border: 1.5px solid #e2e8f0;
    transition: all 0.2s ease;
}
.tsr-btn-back:hover {
    background: #f8fafc;
    color: #0f172a;
    border-color: #cbd5e1;
}
.tsr-btn-pdf {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 800;
    color: #ffffff;
    background: linear-gradient(135deg, #0284c7, #0369a1);
    padding: 10px 22px;
    border-radius: 12px;
    text-decoration: none;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}
.tsr-btn-pdf:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(2, 132, 199, 0.35);
    color: #ffffff;
}
.tsr-btn-print {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    font-weight: 800;
    color: #334155;
    background: #ffffff;
    padding: 10px 18px;
    border-radius: 12px;
    border: 1.5px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.2s ease;
}
.tsr-btn-print:hover {
    background: #f8fafc;
    border-color: #94a3b8;
}
.tsr-sheet {
    background: #ffffff;
    border-radius: 24px;
    border: 1.5px solid #e2e8f0;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
    padding: 36px 42px;
}
.tsr-section-title {
    font-size: 14px;
    font-weight: 800;
    color: #0f172a;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 2px solid #f1f5f9;
}
.tsr-meta-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 18px 22px;
}
.tsr-metric-card {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 16px;
    padding: 16px 14px;
    text-align: center;
    transition: all 0.2s ease;
}
.tsr-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
}
.tsr-att-thumb {
    width: 60px;
    height: 48px;
    border-radius: 8px;
    object-fit: cover;
    border: 1.5px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.2s ease;
}
.tsr-att-thumb:hover {
    transform: scale(1.08);
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.2);
    border-color: #0284c7;
}
@media print {
    .tsr-topbar, .app-header, .app-sidebar, .footer { display: none !important; }
    .tsr-container { margin: 0; max-width: 100%; padding: 0; }
    .tsr-sheet { border: none; box-shadow: none; padding: 0; }
}
</style>
@endpush

@section('content')
<div class="tsr-container">
    {{-- Top action bar --}}
    <div class="tsr-topbar">
        <a href="{{ route('projects.testing-details', $project) }}" class="tsr-btn-back">
            <i class="bi bi-arrow-left"></i> Back to Testing Details
        </a>
        <div style="display: flex; align-items: center; gap: 12px;">
            <button type="button" onclick="window.print()" class="tsr-btn-print">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="{{ route('projects.testing-summary-report.export-pdf', $project) }}" class="tsr-btn-pdf">
                <i class="bi bi-file-earmark-pdf-fill"></i> Download PDF Report
            </a>
        </div>
    </div>

    {{-- Main Document Sheet --}}
    <div class="tsr-sheet">
        {{-- Document Header --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; padding-bottom: 24px; border-bottom: 3px solid #0284c7; margin-bottom: 28px; flex-wrap: wrap;">
            <div>
                <div style="font-size: 24px; font-weight: 900; color: #0284c7; letter-spacing: -0.02em; display: flex; align-items: center; gap: 10px;">
                    <span>🧪</span> Project QA &amp; Testing Summary
                </div>
                <div style="font-size: 13.5px; color: #64748b; margin-top: 6px; font-weight: 600;">
                    Official Verification, Bug Tracker &amp; Sign-off Summary &bull; 
                    Generated on <strong>{{ $generatedAt }}</strong>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 18px; font-weight: 900; color: #0f172a;">{{ $company->company_name ?? config('app.name', 'Sai Techno Solutions') }}</div>
                <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                    Quality Assurance &amp; Project Testing Unit<br>
                    <strong>Report ID:</strong> QA-{{ $project->id }}-{{ date('Ymd') }}
                </div>
            </div>
        </div>

        {{-- 1. Project & Client Details --}}
        <div style="margin-bottom: 28px;">
            <div class="tsr-section-title">
                <i class="bi bi-building"></i> 1. PROJECT &amp; CLIENT INFORMATION
            </div>
            <div class="tsr-meta-card">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Project Name</div>
                        <div style="font-size: 16px; font-weight: 800; color: #0284c7; margin-top: 2px;">{{ $projectName }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Client / Company</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-top: 2px;">{{ $clientName }}</div>
                        @if($clientPhone || $clientEmail)
                            <div style="font-size: 12px; color: #64748b;">{{ $clientPhone }} {{ $clientEmail ? '• ' . $clientEmail : '' }}</div>
                        @endif
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Project ID &amp; Dept</div>
                        <div style="font-size: 14px; font-weight: 700; color: #334155; margin-top: 2px;">
                            #{{ $project->id }} &bull; {{ $project->department?->name ?? 'Development' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Target Delivery</div>
                        <div style="font-size: 14px; font-weight: 700; color: #334155; margin-top: 2px;">
                            {{ $project->project_delivery_date?->format('d M Y') ?? 'N/A' }}
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Assigned Developer(s)</div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                            @if($devUsers->isNotEmpty())
                                {{ $devUsers->pluck('name')->implode(', ') }}
                            @else
                                <span style="color: #94a3b8;">Not specified</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Team Lead(s)</div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                            @if($tlUsers->isNotEmpty())
                                {{ $tlUsers->pluck('name')->implode(', ') }}
                            @else
                                <span style="color: #94a3b8;">Not specified</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Testing Details & Handover Summary --}}
        <div style="margin-bottom: 28px;">
            <div class="tsr-section-title">
                <i class="bi bi-check2-circle"></i> 2. QA TESTING OVERVIEW &amp; HANDOVER DETAILS
            </div>
            <div class="tsr-meta-card">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Current QA Status</div>
                        <div style="margin-top: 4px;">
                            <span style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 12px; font-size: 13px; font-weight: 800; background: {{ $qaStatusInfo['bg'] }}; color: {{ $qaStatusInfo['color'] }}; border: 1.5px solid {{ $qaStatusInfo['border'] }};">
                                {{ $qaStatusInfo['icon'] }} {{ $qaStatusInfo['label'] }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Handover Date &amp; Developer</div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 4px;">
                            {{ $latestHandover?->created_at?->format('d M Y, h:i A') ?? 'N/A' }}
                        </div>
                        <div style="font-size: 12px; color: #64748b;">By: <strong>{{ $latestHandover?->movedBy?->name ?? 'Dev Team' }}</strong></div>
                    </div>
                    <div>
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">QA Tester / Lead</div>
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 4px;">
                            {{ $latestHandover?->testingTl?->name ?? 'QA Testing Team' }}
                        </div>
                    </div>
                    @if($latestHandover?->testing_link)
                    <div style="grid-column: 1 / -1;">
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Staging / Testing URL</div>
                        <div style="margin-top: 4px;">
                            <a href="{{ $latestHandover->testing_link }}" target="_blank" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 700; color: #0284c7; text-decoration: underline;">
                                <span>{{ $latestHandover->testing_link }}</span> <i class="bi bi-box-arrow-up-right" style="font-size: 11px;"></i>
                            </a>
                        </div>
                    </div>
                    @endif
                    @if($latestHandover?->credentials)
                    <div style="grid-column: 1 / -1;">
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Testing Credentials</div>
                        <div style="margin-top: 4px; font-family: monospace; font-size: 12.5px; color: #334155; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 12px;">
                            {{ $latestHandover->credentials }}
                        </div>
                    </div>
                    @endif
                    @if($latestHandover?->notes)
                    <div style="grid-column: 1 / -1;">
                        <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">Handover Notes</div>
                        <div style="margin-top: 4px; font-size: 13px; color: #334155; line-height: 1.5; white-space: pre-wrap;">
                            {{ $latestHandover->notes }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 3. Executive Bug Metrics (KPIs) --}}
        <div style="margin-bottom: 28px;">
            <div class="tsr-section-title">
                <i class="bi bi-bar-chart-line-fill"></i> 3. QA QUALITY METRICS &amp; BUG RESOLUTION STATUS
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px;">
                <div class="tsr-metric-card">
                    <div style="font-size: 24px; font-weight: 900; color: #0284c7;">{{ $totalBugs }}</div>
                    <div style="font-size: 11.5px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-top: 4px;">Total Bugs</div>
                </div>
                <div class="tsr-metric-card">
                    <div style="font-size: 24px; font-weight: 900; color: #dc2626;">{{ $highBugs }}</div>
                    <div style="font-size: 11.5px; font-weight: 800; color: #dc2626; text-transform: uppercase; margin-top: 4px;">High Priority</div>
                </div>
                <div class="tsr-metric-card">
                    <div style="font-size: 24px; font-weight: 900; color: #ea580c;">{{ $medBugs }}</div>
                    <div style="font-size: 11.5px; font-weight: 800; color: #ea580c; text-transform: uppercase; margin-top: 4px;">Med Priority</div>
                </div>
                <div class="tsr-metric-card">
                    <div style="font-size: 24px; font-weight: 900; color: #16a34a;">{{ $devCompleted }}</div>
                    <div style="font-size: 11.5px; font-weight: 800; color: #16a34a; text-transform: uppercase; margin-top: 4px;">Dev Done</div>
                </div>
                <div class="tsr-metric-card">
                    <div style="font-size: 24px; font-weight: 900; color: #047857;">{{ $testerClosed }}</div>
                    <div style="font-size: 11.5px; font-weight: 800; color: #047857; text-transform: uppercase; margin-top: 4px;">QA Closed</div>
                </div>
                <div class="tsr-metric-card">
                    <div style="font-size: 24px; font-weight: 900; color: #dc2626;">{{ $totalReopens }}</div>
                    <div style="font-size: 11.5px; font-weight: 800; color: #dc2626; text-transform: uppercase; margin-top: 4px;">Reopens</div>
                </div>
                <div class="tsr-metric-card" style="background: #ecfdf5; border-color: #a7f3d0;">
                    <div style="font-size: 24px; font-weight: 900; color: #047857;">{{ $passRate }}%</div>
                    <div style="font-size: 11.5px; font-weight: 800; color: #047857; text-transform: uppercase; margin-top: 4px;">Resolution %</div>
                </div>
            </div>
        </div>

        {{-- 4. Automated QA Testing Summary & Bulletin Points --}}
        @php
            $bulletins = $testingBulletins ?? null;
        @endphp
        <div style="margin-bottom: 28px;">
            <div class="tsr-section-title">
                <i class="bi bi-robot"></i> 4. QA AUTOMATED TESTING SUMMARY &amp; BULLETIN POINTS
            </div>
            
            <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 18px; padding: 22px; box-shadow: 0 4px 14px rgba(15,23,42,0.03);">
                {{-- Executive Bullet Points --}}
                @if(!empty($bulletins['bulletinPoints']))
                <div style="margin-bottom: 18px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px 22px;">
                    <div style="font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <span>📌</span> Key Testing Takeaways &amp; Executive Summary
                    </div>
                    <ul style="margin: 0; padding-left: 22px; color: #1e293b; font-size: 13.5px; line-height: 1.7;">
                        @foreach($bulletins['bulletinPoints'] as $pt)
                            <li style="margin-bottom: 8px;">{!! $pt !!}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Tested Modules / Functional Scope --}}
                @if(!empty($bulletins['modulesCovered']))
                <div style="margin-bottom: 20px;">
                    <div style="font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <span>🎯</span> Functional Areas &amp; Modules Tested by QA
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px;">
                        @foreach($bulletins['modulesCovered'] as $mod)
                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; display: flex; align-items: flex-start; gap: 12px;">
                            <span style="font-size: 22px; line-height: 1;">{{ $mod['icon'] }}</span>
                            <div>
                                <div style="font-size: 13px; font-weight: 800; color: #0f172a;">{{ $mod['title'] }}</div>
                                <div style="font-size: 12px; color: #64748b; margin-top: 3px; line-height: 1.4;">{{ $mod['desc'] }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Key Defect Findings Bulletins --}}
                @if(!empty($bulletins['keyFindings']))
                <div>
                    <div style="font-size: 12px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
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
                                    <span style="font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px; background: {{ $pStyle['bg'] }}; color: {{ $pStyle['color'] }}; border: 1px solid {{ $pStyle['border'] }};">
                                        {{ $kf['priority'] }}
                                    </span>
                                    <span style="font-size: 13.5px; font-weight: 600; color: #1e293b; line-height: 1.4;">
                                        {{ $kf['summary'] }}
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; background: {{ $dStyle['bg'] }}; color: {{ $dStyle['color'] }}; border: 1px solid {{ $dStyle['border'] }};">
                                        {{ $dStyle['label'] }}
                                    </span>
                                    @if($kf['reopen_count'] > 0)
                                    <span style="font-size: 11px; font-weight: 800; padding: 2px 8px; border-radius: 6px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;" title="Reopened {{ $kf['reopen_count'] }} time(s)">
                                        🔁 {{ $kf['reopen_count'] }} reopen(s)
                                    </span>
                                    @endif
                                    <span style="font-size: 11.5px; font-weight: 800; padding: 3px 10px; border-radius: 6px; background: {{ $tStyle['bg'] }}; color: {{ $tStyle['color'] }}; border: 1px solid {{ $tStyle['border'] }};">
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

        {{-- 5. Detailed Bug Register Table --}}
        <div style="margin-bottom: 30px;">
            <div class="tsr-section-title">
                <i class="bi bi-bug-fill"></i> 5. Detailed Bug Register &amp; Proofs ({{ $totalBugs }} Issues)
            </div>
            @if(count($bugs) > 0)
            <div style="overflow-x: auto; border-radius: 16px; border: 1.5px solid #e2e8f0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: #0f172a; color: #ffffff;">
                            <th style="padding: 12px 14px; text-align: center; width: 40px;">#</th>
                            <th style="padding: 12px 14px; width: 90px;">Priority</th>
                            <th style="padding: 12px 14px;">Bug Description</th>
                            <th style="padding: 12px 14px; width: 160px;">Reported By &amp; Date</th>
                            <th style="padding: 12px 14px; text-align: center; width: 110px;">Dev Status</th>
                            <th style="padding: 12px 14px; text-align: center; width: 110px;">Tester Status</th>
                            <th style="padding: 12px 14px; text-align: center; width: 80px;">Reopen</th>
                            <th style="padding: 12px 14px; width: 180px;">Attachments &amp; Proofs</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bugs as $bugItem)
                            @php
                                $pBadge = match($bugItem['priority']) {
                                    'High' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca'],
                                    'Low' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd'],
                                    default => ['bg' => '#fff7ed', 'color' => '#c2410c', 'border' => '#fed7aa'],
                                };
                                $dBadge = match($bugItem['raw_dev_status']) {
                                    'completed' => ['bg' => '#f0fdf4', 'color' => '#16a34a', 'border' => '#bbf7d0', 'label' => 'Completed'],
                                    'ongoing' => ['bg' => '#f0f9ff', 'color' => '#0284c7', 'border' => '#bae6fd', 'label' => 'Ongoing'],
                                    default => ['bg' => '#fff7ed', 'color' => '#ea580c', 'border' => '#fed7aa', 'label' => 'Pending'],
                                };
                                $tBadge = match($bugItem['raw_tester_status']) {
                                    'closed' => ['bg' => '#f8fafc', 'color' => '#475569', 'border' => '#cbd5e1', 'label' => 'Closed'],
                                    'reopen' => ['bg' => '#fef2f2', 'color' => '#dc2626', 'border' => '#fecaca', 'label' => 'Reopen'],
                                    default => ['bg' => '#fff7ed', 'color' => '#ea580c', 'border' => '#fed7aa', 'label' => 'Pending'],
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid #e2e8f0; vertical-align: top;">
                                <td style="padding: 12px 14px; text-align: center; font-weight: 800; color: #64748b;">
                                    {{ $bugItem['index'] }}
                                </td>
                                <td style="padding: 12px 14px;">
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 800; background: {{ $pBadge['bg'] }}; color: {{ $pBadge['color'] }}; border: 1px solid {{ $pBadge['border'] }};">
                                        {{ $bugItem['priority'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; white-space: pre-wrap; word-break: break-word; font-weight: 500; color: #1e293b;">
                                    <div>{{ $bugItem['description'] }}</div>
                                    @if(!empty($bugItem['developer_remarks']) || !empty($bugItem['tester_remarks']))
                                        <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 4px;">
                                            @if(!empty($bugItem['developer_remarks']))
                                                <div style="font-size: 11.5px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 5px 10px; color: #0369a1;">
                                                    <strong>🛠️ Dev Remarks:</strong> {{ $bugItem['developer_remarks'] }}
                                                </div>
                                            @endif
                                            @if(!empty($bugItem['tester_remarks']))
                                                <div style="font-size: 11.5px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 6px; padding: 5px 10px; color: #6d28d9;">
                                                    <strong>🧪 QA Remarks:</strong> {{ $bugItem['tester_remarks'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px;">
                                    <div style="font-weight: 700; color: #0f172a;">{{ $bugItem['created_by'] }}</div>
                                    <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">{{ $bugItem['created_at'] }}</div>
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 800; background: {{ $dBadge['bg'] }}; color: {{ $dBadge['color'] }}; border: 1px solid {{ $dBadge['border'] }};">
                                        {{ $dBadge['label'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 10px; font-size: 12px; font-weight: 800; background: {{ $tBadge['bg'] }}; color: {{ $tBadge['color'] }}; border: 1px solid {{ $tBadge['border'] }};">
                                        {{ $tBadge['label'] }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; text-align: center;">
                                    @if($bugItem['reopen_count'] > 0)
                                        <span style="display: inline-block; padding: 3px 8px; border-radius: 10px; font-size: 11.5px; font-weight: 800; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                                            {{ $bugItem['reopen_count'] }}
                                        </span>
                                    @else
                                        <span style="color: #94a3b8; font-weight: 600;">0</span>
                                    @endif
                                </td>
                                <td style="padding: 12px 14px;">
                                    @if(!empty($bugItem['attachments']))
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            @foreach($bugItem['attachments'] as $att)
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    @if($att['is_image'])
                                                        <a href="{{ $att['url'] }}" target="_blank" title="View Full Image">
                                                            <img src="{{ $att['url'] }}" alt="{{ $att['name'] }}" class="tsr-att-thumb">
                                                        </a>
                                                    @endif
                                                    <div style="min-width: 0; flex: 1;">
                                                        <a href="{{ $att['url'] }}" target="_blank" style="font-size: 11.5px; font-weight: 700; color: #0284c7; text-decoration: none; word-break: break-all; display: block;" title="{{ $att['name'] }}">
                                                            <i class="bi bi-paperclip"></i> {{ \Illuminate\Support\Str::limit($att['name'], 18) }}
                                                        </a>
                                                        <a href="{{ $att['url'] }}" target="_blank" style="font-size: 10.5px; color: #64748b; text-decoration: underline;">Open Link</a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color: #94a3b8; font-size: 12px;">No Files</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="text-align: center; padding: 34px 20px; background: #f8fafc; border-radius: 16px; border: 1.5px solid #e2e8f0; color: #15803d; font-weight: 800; font-size: 15px;">
                🎉 No bugs reported. All verification passed successfully!
            </div>
            @endif
        </div>

        {{-- 6. QA Sign-Off Certificate --}}
        <div style="margin-bottom: 28px;">
            <div class="tsr-section-title">
                <i class="bi bi-award-fill"></i> 6. QA Verification &amp; Project Sign-off
            </div>
            <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 18px; padding: 22px 28px; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 260px;">
                    <div style="font-size: 16px; font-weight: 900; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span>Verification Sign-Off:</span>
                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 10px; font-size: 13px; font-weight: 800; background: {{ $qaStatusInfo['bg'] }}; color: {{ $qaStatusInfo['color'] }}; border: 1.5px solid {{ $qaStatusInfo['border'] }};">
                            {{ $qaStatusInfo['icon'] }} {{ $qaStatusInfo['label'] }}
                        </span>
                    </div>
                    <div style="font-size: 13px; color: #475569; margin-top: 8px; line-height: 1.55;">
                        @if($qaRawStatus === 'ready_launch')
                            This project has successfully completed quality inspection and bug fixing. It is officially certified as <strong>Ready for Launch</strong> by the QA department.
                        @elseif($qaRawStatus === 'completed')
                            Testing completed. Verification items have been completed and approved.
                        @else
                            The project is currently under active quality inspection or developer fixes. Final sign-off will be issued upon resolving pending items.
                        @endif
                    </div>
                </div>
                <div style="text-align: center; padding: 12px 20px; border-radius: 14px; background: #ffffff; border: 1.5px solid #cbd5e1;">
                    <div style="font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase;">QA VERIFICATION SEAL</div>
                    <div style="font-size: 28px; margin: 4px 0;">{{ $qaStatusInfo['icon'] }}</div>
                    <div style="font-size: 12px; font-weight: 900; color: {{ $qaStatusInfo['color'] }};">{{ $qaStatusInfo['label'] }}</div>
                </div>
            </div>
        </div>

        {{-- Signatures row --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-top: 36px; padding-top: 24px; border-top: 1.5px dashed #cbd5e1;">
            <div style="text-align: center;">
                <div style="height: 40px;"></div>
                <div style="border-top: 1.5px solid #94a3b8; padding-top: 6px; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase;">
                    QA Engineer / Tester
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">{{ $latestHandover?->testingTl?->name ?? 'QA Department' }}</div>
            </div>
            <div style="text-align: center;">
                <div style="height: 40px;"></div>
                <div style="border-top: 1.5px solid #94a3b8; padding-top: 6px; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase;">
                    Assigned Developer / TL
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">{{ $latestHandover?->movedBy?->name ?? ($devUsers->first()?->name ?? 'Development Lead') }}</div>
            </div>
            <div style="text-align: center;">
                <div style="height: 40px;"></div>
                <div style="border-top: 1.5px solid #94a3b8; padding-top: 6px; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase;">
                    Project Delivery Head
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Sai Techno Solutions</div>
            </div>
        </div>
    </div>
</div>
@endsection
