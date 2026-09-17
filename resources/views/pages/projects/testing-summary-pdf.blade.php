<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>QA Testing Summary - {{ $projectName }}</title>
    <style>
        @page {
            margin: 14mm 20mm 14mm 20mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #1e293b;
            background: #ffffff;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }

        /* Running footer */
        .page-footer {
            position: fixed;
            bottom: -9mm;
            left: 0;
            right: 0;
            height: 15px;
            border-top: 1px solid #e2e8f0;
            padding-top: 2px;
            font-size: 7px;
            color: #94a3b8;
        }
        .page-footer table {
            width: 100%;
            border-collapse: collapse;
        }
        .page-footer td {
            padding: 0;
            border: none;
        }

        /* Top Header */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td {
            border: none;
            padding: 0;
            vertical-align: top;
        }
        .report-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #0284c7;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 2px 7px;
            border-radius: 3px;
            margin-bottom: 3px;
            border: 1px solid #bae6fd;
        }
        .header-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: -0.01em;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .header-sub {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.35;
        }
        .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #0284c7;
            text-align: right;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .company-sub {
            font-size: 7.5px;
            color: #64748b;
            text-align: right;
            margin-top: 2px;
            line-height: 1.35;
        }
        .header-divider {
            width: 100%;
            height: 2px;
            background: #0284c7;
            margin-bottom: 10px;
            border-radius: 2px;
        }

        /* Section Title */
        .section-title {
            background: #f1f5f9;
            border-left: 3px solid #0284c7;
            padding: 4px 8px;
            font-size: 8.5px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 9px;
            margin-bottom: 5px;
            border-radius: 2px;
        }

        /* Grid Data Table (Info) */
        .grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .grid-table td {
            padding: 4px 7px;
            vertical-align: top;
            border: 1px solid #e2e8f0;
            font-size: 7.5px;
        }
        .label-cell {
            background: #f8fafc;
            color: #475569;
            font-weight: bold;
            width: 20%;
        }
        .val-cell {
            background: #ffffff;
            color: #0f172a;
            width: 30%;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
        }
        .badge-ready { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-completed { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-ongoing { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
        .badge-retesting { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
        .badge-open { background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; }
        .badge-high { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-med { background: #fff7ed; color: #ea580c; border: 1px solid #fed7aa; }
        .badge-low { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; }
        .badge-closed { background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; }
        .badge-reopen { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Table pagination & page break rules */
        thead {
            display: table-header-group;
        }
        tr {
            page-break-inside: avoid;
        }
        .keep-together {
            page-break-inside: avoid;
        }

        /* Bug Register Table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            margin-bottom: 8px;
        }
        .data-table th {
            background: #0f172a;
            color: #ffffff;
            font-size: 7px;
            font-weight: bold;
            padding: 4px 5px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border: 1px solid #0f172a;
        }
        .data-table td {
            padding: 4px 5px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 7px;
            line-height: 1.3;
        }
        .data-table tr:nth-child(even) td {
            background: #f8fafc;
        }
        .attachment-box {
            display: inline-block;
            margin-top: 1px;
            margin-bottom: 1px;
        }

        /* Sign-off box & signatures */
        .signoff-box {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 3px solid #047857;
            border-radius: 3px;
            padding: 7px 10px;
            margin-bottom: 8px;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            padding-top: 4px;
        }
        .footer-sign-cell {
            width: 33.33%;
            text-align: center;
            padding: 3px 8px;
            vertical-align: bottom;
            border: none;
        }
        .sign-line {
            border-top: 1px dashed #94a3b8;
            margin-top: 22px;
            padding-top: 3px;
            font-size: 7px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    {{-- Running Footer --}}
    <div class="page-footer">
        <table>
            <tr>
                <td style="text-align: left;">Sai Techno Solutions &bull; Confidential QA Testing &amp; Verification Audit</td>
                <td style="text-align: right;">Generated: {{ $generatedAt }} &bull; Ref: QA-{{ $project->id }}</td>
            </tr>
        </table>
    </div>

    {{-- Top Header Table --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="report-badge">Quality Assurance &bull; Project Audit</div>
                <div class="header-title">Project QA &amp; Testing Summary</div>
                <div class="header-sub">
                    <strong>Project:</strong> {{ $projectName }}<br>
                    <strong>Generated:</strong> {{ $generatedAt }} &bull; <strong>Ref:</strong> QA-{{ $project->id }}-{{ date('Ymd') }}
                </div>
            </td>
            <td style="width: 40%;">
                <div class="company-name">{{ $company->company_name ?? config('app.name', 'Sai Techno Solutions') }}</div>
                <div class="company-sub">
                    @if(!empty($company->email)) {{ $company->email }}<br> @endif
                    @if(!empty($company->mobile_number)) Tel: {{ $company->mobile_number }}<br> @endif
                    Quality Assurance &amp; Project Testing Department
                </div>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    {{-- 1. Project Details Section --}}
    <div class="section-title">1. PROJECT &amp; CLIENT INFORMATION</div>
    <table class="grid-table">
        <tr>
            <td class="label-cell">Project Name</td>
            <td class="val-cell" style="font-weight: bold; color: #0284c7;">{{ $projectName }}</td>
            <td class="label-cell">Project ID / Code</td>
            <td class="val-cell">#{{ $project->id }}</td>
        </tr>
        <tr>
            <td class="label-cell">Client / Company</td>
            <td class="val-cell"><strong>{{ $clientName }}</strong></td>
            <td class="label-cell">Client Contact</td>
            <td class="val-cell">{{ $clientPhone ?: ($clientEmail ?: 'N/A') }}</td>
        </tr>
        <tr>
            <td class="label-cell">Department</td>
            <td class="val-cell">{{ $project->department?->name ?? 'Development' }}</td>
            <td class="label-cell">Target Delivery Date</td>
            <td class="val-cell">{{ $project->project_delivery_date?->format('d M Y') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Assigned Developer(s)</td>
            <td class="val-cell">
                @if($devUsers->isNotEmpty())
                    {{ $devUsers->pluck('name')->implode(', ') }}
                @else
                    <span style="color: #94a3b8;">Not specified</span>
                @endif
            </td>
            <td class="label-cell">Team Lead(s)</td>
            <td class="val-cell">
                @if($tlUsers->isNotEmpty())
                    {{ $tlUsers->pluck('name')->implode(', ') }}
                @else
                    <span style="color: #94a3b8;">Not specified</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- 2. Testing Details & Handover Summary --}}
    <div class="section-title">2. QA TESTING OVERVIEW &amp; HANDOVER DETAILS</div>
    <table class="grid-table">
        <tr>
            <td class="label-cell">Current QA Status</td>
            <td class="val-cell" style="font-weight: bold;">
                @php
                    $badgeClass = match($qaRawStatus) {
                        'ready_launch', 'ready_to_launch' => 'badge-ready',
                        'completed' => 'badge-completed',
                        'retesting' => 'badge-retesting',
                        'ongoing' => 'badge-ongoing',
                        default => 'badge-open',
                    };
                @endphp
                <span class="badge {{ $badgeClass }}">&bull; {{ $qaStatusInfo['label'] }}</span>
            </td>
            <td class="label-cell">Handover Date</td>
            <td class="val-cell">{{ $latestHandover?->created_at?->format('d M Y, h:i A') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Handed Over By</td>
            <td class="val-cell">{{ $latestHandover?->movedBy?->name ?? 'Development Team' }}</td>
            <td class="label-cell">QA Lead / Tester</td>
            <td class="val-cell">{{ $latestHandover?->testingTl?->name ?? 'QA Testing Team' }}</td>
        </tr>
        @if($latestHandover?->testing_link)
        <tr>
            <td class="label-cell">Staging / Test Link</td>
            <td class="val-cell" colspan="3">
                <a href="{{ $latestHandover->testing_link }}" target="_blank" style="color: #0284c7; text-decoration: underline; font-weight: bold;">
                    {{ $latestHandover->testing_link }}
                </a>
            </td>
        </tr>
        @endif
        @if($latestHandover?->credentials)
        <tr>
            <td class="label-cell">Test Credentials</td>
            <td class="val-cell" colspan="3" style="font-family: monospace; font-size: 8px; background: #f8fafc;">
                {{ $latestHandover->credentials }}
            </td>
        </tr>
        @endif
        @if($latestHandover?->notes)
        <tr>
            <td class="label-cell">Handover Notes</td>
            <td class="val-cell" colspan="3" style="white-space: pre-wrap; color: #334155;">{{ $latestHandover->notes }}</td>
        </tr>
        @endif
    </table>

    {{-- 3. Executive Bug Metrics & Quality Health --}}
    <div class="section-title">3. QA QUALITY METRICS &amp; BUG RESOLUTION STATUS</div>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px;">
        <tr>
            <td style="width: 13.5%; text-align: center; padding: 7px 3px; background: #f0f9ff; border: 1px solid #bae6fd; border-top: 3px solid #0284c7; border-radius: 3px;">
                <div style="font-size: 14px; font-weight: bold; color: #0284c7; line-height: 1;">{{ $totalBugs }}</div>
                <div style="font-size: 7px; font-weight: bold; color: #0369a1; text-transform: uppercase; margin-top: 3px;">Total Bugs</div>
            </td>
            <td style="width: 1%; border: none; background: transparent;"></td>
            <td style="width: 13.5%; text-align: center; padding: 7px 3px; background: #fef2f2; border: 1px solid #fecaca; border-top: 3px solid #dc2626; border-radius: 3px;">
                <div style="font-size: 14px; font-weight: bold; color: #dc2626; line-height: 1;">{{ $highBugs }}</div>
                <div style="font-size: 7px; font-weight: bold; color: #b91c1c; text-transform: uppercase; margin-top: 3px;">High Priority</div>
            </td>
            <td style="width: 1%; border: none; background: transparent;"></td>
            <td style="width: 13.5%; text-align: center; padding: 7px 3px; background: #fff7ed; border: 1px solid #fed7aa; border-top: 3px solid #ea580c; border-radius: 3px;">
                <div style="font-size: 14px; font-weight: bold; color: #ea580c; line-height: 1;">{{ $medBugs }}</div>
                <div style="font-size: 7px; font-weight: bold; color: #c2410c; text-transform: uppercase; margin-top: 3px;">Med Priority</div>
            </td>
            <td style="width: 1%; border: none; background: transparent;"></td>
            <td style="width: 13.5%; text-align: center; padding: 7px 3px; background: #f0fdf4; border: 1px solid #bbf7d0; border-top: 3px solid #16a34a; border-radius: 3px;">
                <div style="font-size: 14px; font-weight: bold; color: #16a34a; line-height: 1;">{{ $devCompleted }}</div>
                <div style="font-size: 7px; font-weight: bold; color: #15803d; text-transform: uppercase; margin-top: 3px;">Dev Done</div>
            </td>
            <td style="width: 1%; border: none; background: transparent;"></td>
            <td style="width: 13.5%; text-align: center; padding: 7px 3px; background: #ecfdf5; border: 1px solid #a7f3d0; border-top: 3px solid #047857; border-radius: 3px;">
                <div style="font-size: 14px; font-weight: bold; color: #047857; line-height: 1;">{{ $testerClosed }}</div>
                <div style="font-size: 7px; font-weight: bold; color: #065f46; text-transform: uppercase; margin-top: 3px;">QA Closed</div>
            </td>
            <td style="width: 1%; border: none; background: transparent;"></td>
            <td style="width: 13.5%; text-align: center; padding: 7px 3px; background: #fef2f2; border: 1px solid #fecaca; border-top: 3px solid #dc2626; border-radius: 3px;">
                <div style="font-size: 14px; font-weight: bold; color: #dc2626; line-height: 1;">{{ $totalReopens }}</div>
                <div style="font-size: 7px; font-weight: bold; color: #991b1b; text-transform: uppercase; margin-top: 3px;">Reopens</div>
            </td>
            <td style="width: 1%; border: none; background: transparent;"></td>
            <td style="width: 15%; text-align: center; padding: 7px 3px; background: #ecfdf5; border: 1px solid #6ee7b7; border-top: 3px solid #059669; border-radius: 3px;">
                <div style="font-size: 14px; font-weight: bold; color: #047857; line-height: 1;">{{ $passRate }}%</div>
                <div style="font-size: 7px; font-weight: bold; color: #065f46; text-transform: uppercase; margin-top: 3px;">Resolution %</div>
            </td>
        </tr>
    </table>

    {{-- 4. Automated QA Testing Summary & Bulletin Points --}}
    @php
        $pdfBulletins = $testingBulletins ?? null;
    @endphp
    <div class="section-title">4. QA AUTOMATED TESTING SUMMARY &amp; BULLETIN POINTS</div>
    <div style="border: 1px solid #cbd5e1; background: #ffffff; margin-bottom: 12px; padding: 8px 10px; border-radius: 3px;">
        {{-- Executive Bullet Points --}}
        @if(!empty($pdfBulletins['bulletinPoints']))
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3.5px solid #0284c7; padding: 7px 10px; margin-bottom: 8px; border-radius: 2px;">
            <div style="font-size: 7.5px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                EXECUTIVE TESTING HIGHLIGHTS &amp; SCOPE
            </div>
            <ul style="margin: 0; padding-left: 14px; font-size: 8px; line-height: 1.5; color: #1e293b;">
                @foreach($pdfBulletins['bulletinPoints'] as $pt)
                    @php
                        // Strip high unicode emojis for clean DejaVu Sans rendering
                        $cleanPt = preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $pt);
                    @endphp
                    <li style="margin-bottom: 3px;">{!! $cleanPt !!}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Modules Tested --}}
        @if(!empty($pdfBulletins['modulesCovered']))
        <div style="margin-bottom: 8px;">
            <div style="font-size: 7.5px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                FUNCTIONAL AREAS &amp; MODULES TESTED
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                @foreach(array_chunk($pdfBulletins['modulesCovered'], 2) as $modRow)
                <tr>
                    @foreach($modRow as $mod)
                    <td style="width: 49%; padding: 5px 8px; border: 1px solid #e2e8f0; background: #f8fafc; vertical-align: top; border-left: 2.5px solid #0284c7;">
                        <div style="font-weight: bold; color: #0f172a; font-size: 8px;">&bull; {{ $mod['title'] }}</div>
                        <div style="color: #64748b; font-size: 7.5px; margin-top: 1px; line-height: 1.35;">{{ $mod['desc'] }}</div>
                    </td>
                    @if(!$loop->last)
                    <td style="width: 2%; border: none;"></td>
                    @endif
                    @endforeach
                    @if(count($modRow) == 1)
                    <td style="width: 2%; border: none;"></td>
                    <td style="width: 49%; border: none;"></td>
                    @endif
                </tr>
                <tr><td colspan="3" style="height: 3px; border: none;"></td></tr>
                @endforeach
            </table>
        </div>
        @endif

        {{-- Defect Findings --}}
        @if(!empty($pdfBulletins['keyFindings']))
        <div>
            <div style="font-size: 7.5px; font-weight: bold; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px;">
                VERIFIED TEST SCENARIOS &amp; DEFECT POINTS ({{ count($pdfBulletins['keyFindings']) }})
            </div>
            <table style="width: 100%; border-collapse: collapse;">
                @foreach($pdfBulletins['keyFindings'] as $kf)
                @php
                    $pBadge = match($kf['priority']) {
                        'High' => 'badge-high',
                        'Low' => 'badge-low',
                        default => 'badge-med',
                    };
                    $tBadge = match($kf['raw_tester_status']) {
                        'closed' => 'badge-completed',
                        'reopen' => 'badge-reopen',
                        default => 'badge-med',
                    };
                @endphp
                <tr style="{{ $loop->iteration % 2 == 0 ? 'background: #f8fafc;' : 'background: #ffffff;' }}">
                    <td style="padding: 3.5px 5px; border: 1px solid #e2e8f0; font-size: 7.5px; width: 22px; color: #64748b; font-weight: bold; text-align: center;">
                        #{{ $kf['index'] }}
                    </td>
                    <td style="padding: 3.5px 5px; border: 1px solid #e2e8f0; width: 46px; text-align: center;">
                        <span class="badge {{ $pBadge }}">{{ $kf['priority'] }}</span>
                    </td>
                    <td style="padding: 3.5px 6px; border: 1px solid #e2e8f0; font-size: 7.5px; color: #1e293b;">
                        {{ $kf['summary'] }}
                        @if(!empty($kf['developer_remarks']))
                            <div style="margin-top: 2px; font-size: 6.8px; color: #0284c7; background: #f0f9ff; padding: 1.5px 4px; border-radius: 3px;">
                                <strong>[Dev Notes]</strong> {{ Str::limit($kf['developer_remarks'], 90) }}
                            </div>
                        @endif
                        @if(!empty($kf['tester_remarks']))
                            <div style="margin-top: 1.5px; font-size: 6.8px; color: #6d28d9; background: #faf5ff; padding: 1.5px 4px; border-radius: 3px;">
                                <strong>[QA Notes]</strong> {{ Str::limit($kf['tester_remarks'], 90) }}
                            </div>
                        @endif
                    </td>
                    <td style="padding: 3.5px 5px; border: 1px solid #e2e8f0; text-align: right; width: 100px;">
                        @if($kf['reopen_count'] > 0)
                            <span class="badge badge-reopen" style="margin-right: 2px;">{{ $kf['reopen_count'] }} Reopen(s)</span>
                        @endif
                        <span class="badge {{ $tBadge }}">QA: {{ ucfirst($kf['raw_tester_status']) }}</span>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
        @endif
    </div>

    {{-- 5. Detailed Bug Register Table with Attachments Preview --}}
    <div class="section-title">5. BUG REGISTER &amp; VERIFICATION DETAILS ({{ $totalBugs }} ISSUES)</div>
    @if(count($bugs) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 22px; text-align: center;">#</th>
                <th style="width: 48px; text-align: center;">Priority</th>
                <th style="width: 165px;">Bug Description</th>
                <th style="width: 75px;">Reported By &amp; Date</th>
                <th style="width: 58px; text-align: center;">Dev Status</th>
                <th style="width: 58px; text-align: center;">Tester Status</th>
                <th style="width: 38px; text-align: center;">Reopens</th>
                <th style="width: 95px;">Attachments &amp; Proofs</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bugs as $bugItem)
                @php
                    $pBadge = match($bugItem['priority']) {
                        'High' => 'badge-high',
                        'Low' => 'badge-low',
                        default => 'badge-med',
                    };
                    $dBadge = match($bugItem['raw_dev_status']) {
                        'completed' => 'badge-completed',
                        'ongoing' => 'badge-ongoing',
                        default => 'badge-med',
                    };
                    $tBadge = match($bugItem['raw_tester_status']) {
                        'closed' => 'badge-closed',
                        'reopen' => 'badge-reopen',
                        default => 'badge-med',
                    };
                @endphp
                <tr>
                    <td style="text-align: center; font-weight: bold; color: #64748b;">{{ $bugItem['index'] }}</td>
                    <td style="text-align: center;">
                        <span class="badge {{ $pBadge }}">{{ $bugItem['priority'] }}</span>
                    </td>
                    <td style="white-space: pre-wrap; word-break: break-word;">
                        <div>{{ $bugItem['description'] }}</div>
                        @if(!empty($bugItem['developer_remarks']))
                            <div style="margin-top: 4px; padding: 3px 5px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 2px; font-size: 6.5px; color: #0369a1;">
                                <strong>[Dev Notes]:</strong> {{ $bugItem['developer_remarks'] }}
                            </div>
                        @endif
                        @if(!empty($bugItem['tester_remarks']))
                            <div style="margin-top: 3px; padding: 3px 5px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 2px; font-size: 6.5px; color: #334155;">
                                <strong>[QA Notes]:</strong> {{ $bugItem['tester_remarks'] }}
                            </div>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $bugItem['created_by'] }}</strong><br>
                        <span style="color: #64748b; font-size: 7px;">{{ $bugItem['created_at'] }}</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $dBadge }}">{{ ucfirst($bugItem['raw_dev_status']) }}</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge {{ $tBadge }}">{{ ucfirst($bugItem['raw_tester_status']) }}</span>
                    </td>
                    <td style="text-align: center; font-weight: bold; {{ $bugItem['reopen_count'] > 0 ? 'color: #dc2626;' : 'color: #94a3b8;' }}">
                        {{ $bugItem['reopen_count'] }}
                    </td>
                    <td>
                        @if(!empty($bugItem['attachments']))
                            @foreach($bugItem['attachments'] as $att)
                                <div class="attachment-box">
                                    @if($att['is_image'] && !empty($att['base64']))
                                        <div style="margin-bottom: 2px;">
                                            <img src="{{ $att['base64'] }}" alt="{{ $att['name'] }}" style="max-width: 75px; max-height: 48px; border: 1px solid #cbd5e1; border-radius: 2px; display: block;">
                                        </div>
                                    @endif
                                    <a href="{{ $att['url'] }}" target="_blank" style="color: #0284c7; text-decoration: underline; font-size: 7px; word-break: break-all; display: block;">
                                        {{ \Illuminate\Support\Str::limit($att['name'], 20) }}
                                    </a>
                                </div>
                            @endforeach
                        @else
                            <span style="color: #94a3b8; font-size: 7.5px;">No Files</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="padding: 12px; text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 3px; margin-bottom: 12px; color: #16a34a; font-weight: bold;">
        All QA verification passed cleanly. No bugs reported.
    </div>
    @endif

    {{-- 6. QA Sign-Off & Verification Footer --}}
    <div class="keep-together">
        <div class="section-title" style="margin-top: 10px;">6. QA VERIFICATION &amp; PROJECT SIGN-OFF</div>
        <div class="signoff-box">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: top; width: 66%; border: none;">
                        <div style="font-size: 8.5px; font-weight: bold; color: #0f172a;">
                            Official QA Sign-Off Status: 
                            <span class="badge {{ $badgeClass }}" style="font-size: 8px;">&bull; {{ $qaStatusInfo['label'] }}</span>
                        </div>
                        <div style="font-size: 7px; color: #475569; margin-top: 3px; line-height: 1.35;">
                            @if($qaRawStatus === 'ready_launch')
                                This project has undergone comprehensive quality inspection and has been verified by the QA department. It is approved as <strong>Ready to Launch</strong> for production deployment.
                            @elseif($qaRawStatus === 'completed')
                                All reported testing scenarios and issues have been completed and verified.
                            @else
                                Project is currently in the active testing or retesting phase. Final production release requires full sign-off.
                            @endif
                        </div>
                    </td>
                    <td style="vertical-align: top; text-align: right; width: 34%; border: none;">
                        <div style="font-size: 7px; color: #64748b; text-transform: uppercase;">Automated Verification Seal</div>
                        <div style="display: inline-block; border: 1.5px solid {{ $qaStatusInfo['border'] }}; background: {{ $qaStatusInfo['bg'] }}; color: {{ $qaStatusInfo['color'] }}; padding: 3px 8px; border-radius: 3px; font-weight: bold; font-size: 7.5px; margin-top: 3px; text-transform: uppercase;">
                            &bull; {{ $qaStatusInfo['label'] }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Signatures Table --}}
        <table class="footer-table">
            <tr>
                <td class="footer-sign-cell">
                    <div class="sign-line">QA Engineer / Tester</div>
                    <div style="font-size: 7px; color: #64748b; margin-top: 2px;">{{ $latestHandover?->testingTl?->name ?? 'QA Department' }}</div>
                </td>
                <td class="footer-sign-cell">
                    <div class="sign-line">Assigned Developer / TL</div>
                    <div style="font-size: 7px; color: #64748b; margin-top: 2px;">{{ $latestHandover?->movedBy?->name ?? ($devUsers->first()?->name ?? 'Development Lead') }}</div>
                </td>
                <td class="footer-sign-cell">
                    <div class="sign-line">Project Coordinator / Delivery Head</div>
                    <div style="font-size: 7px; color: #64748b; margin-top: 2px;">Sai Techno Solutions</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>