<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Ready to Launch: {{ $project->leadProduct?->name ?: ($project->product?->name ?? 'Project #' . $project->id) }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#1f2937;">
    @php
        $projectName = $project->leadProduct?->name ?: ($project->product?->name ?? 'Project #' . $project->id);
        $clientName = $project->company_name ?: ($project->client_name ?: ($project->lead?->company_name ?: 'Client'));
        $movedBy = $testingDetail->movedBy?->name ?? 'Development Team';
        $testingTlName = $testingDetail->testingTl?->name ?? 'QA / Testing Team';
        $approvalTime = now()->format('d M Y, h:i A');

        $bugs = $project->bugs ?? collect();
        $totalBugs = $bugs->count();
        $resolvedBugs = $bugs->whereIn('status', ['fixed', 'closed', 'resolved'])->count();
        $pendingBugs = $totalBugs - $resolvedBugs;
        $projectUrl = route('projects.show', ['productionInitiation' => $project->id, 'tab' => 'testing']);
    @endphp

    <div style="padding:28px 14px;">
        <div style="max-width:680px;margin:0 auto;">
            {{-- Header Card --}}
            <div style="background:linear-gradient(135deg,#059669 0%,#10b981 100%);border-radius:28px 28px 0 0;padding:28px 28px 24px;box-shadow:0 20px 45px rgba(18,18,18,.08);">
                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="Myagenci" style="max-width:190px;height:auto;display:block;">
                        </td>
                        <td style="text-align:right;vertical-align:top;">
                            <span style="display:inline-block;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);font-size:12px;font-weight:800;letter-spacing:.04em;color:#fff;text-transform:uppercase;">
                                🚀 QA Signoff
                            </span>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:22px;font-size:26px;line-height:1.2;font-weight:800;color:#fff;">
                    🚀 Project Ready to Launch
                </div>
                <div style="margin-top:10px;font-size:14px;line-height:1.7;color:rgba(255,255,255,.95);max-width:580px;">
                    Quality Assurance has been completed for <strong>{{ $projectName }}</strong>. The QA Status is marked as <strong>Ready Launch</strong> and the project is cleared for production release.
                </div>
            </div>

            {{-- Main Body Card --}}
            <div style="background:#ffffff;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 28px 28px;padding:28px;box-shadow:0 20px 45px rgba(18,18,18,.06);">
                <div style="font-size:15px;line-height:1.7;color:#334155;">
                    Hello <strong>Development Team, Leads & Coordinators</strong>,
                </div>

                <div style="margin-top:12px;font-size:14px;line-height:1.6;color:#475569;">
                    The Testing / QA Department has completed validation for project <strong>{{ $projectName }}</strong> and approved it for launch.
                </div>

                {{-- Status Highlight Badge Box --}}
                <div style="margin-top:18px;padding:16px 20px;border-radius:16px;background:#ecfdf5;border:1.5px solid #a7f3d0;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <div style="font-size:11px;font-weight:800;color:#047857;text-transform:uppercase;letter-spacing:.08em;">Official QA Status</div>
                        <div style="font-size:18px;font-weight:900;color:#065f46;margin-top:2px;">🚀 Ready to Launch (QA Approved)</div>
                    </div>
                    <div style="text-align:right;">
                        <span style="display:inline-block;padding:6px 14px;border-radius:20px;background:#059669;color:#ffffff;font-size:12px;font-weight:800;">
                            ✅ QA Verified
                        </span>
                    </div>
                </div>

                {{-- Project Details Table --}}
                <div style="margin-top:20px;padding:20px;border-radius:20px;background:#f8fafc;border:1px solid #e2e8f0;">
                    <div style="font-size:12.5px;color:#0f172a;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                        📌 Project Summary
                    </div>
                    <table role="presentation" style="width:100%;border-collapse:collapse;font-size:13.5px;">
                        <tr>
                            <td style="padding:7px 0;color:#64748b;width:38%;">Project Name:</td>
                            <td style="padding:7px 0;font-weight:700;color:#0f172a;">{{ $projectName }}</td>
                        </tr>
                        <tr>
                            <td style="padding:7px 0;color:#64748b;">Client / Company:</td>
                            <td style="padding:7px 0;font-weight:700;color:#0f172a;">{{ $clientName }}</td>
                        </tr>
                        <tr>
                            <td style="padding:7px 0;color:#64748b;">Developer:</td>
                            <td style="padding:7px 0;font-weight:700;color:#0284c7;">{{ $movedBy }}</td>
                        </tr>
                        <tr>
                            <td style="padding:7px 0;color:#64748b;">Testing / QA Lead:</td>
                            <td style="padding:7px 0;font-weight:700;color:#0f172a;">{{ $testingTlName }}</td>
                        </tr>
                        <tr>
                            <td style="padding:7px 0;color:#64748b;">Approval Date & Time:</td>
                            <td style="padding:7px 0;font-weight:700;color:#059669;">{{ $approvalTime }}</td>
                        </tr>
                        @if($project->project_delivery_date)
                        <tr>
                            <td style="padding:7px 0;color:#64748b;">Target Delivery Date:</td>
                            <td style="padding:7px 0;font-weight:700;color:#dc2626;">{{ \Carbon\Carbon::parse($project->project_delivery_date)->format('d M Y') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                {{-- QA Validation / Bugs Summary --}}
                <div style="margin-top:18px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px;text-align:center;">
                    <div style="padding:14px;border-radius:14px;background:#f0f9ff;border:1px solid #bae6fd;">
                        <div style="font-size:11px;font-weight:800;color:#0369a1;text-transform:uppercase;">Total Bugs Reported</div>
                        <div style="font-size:22px;font-weight:900;color:#0284c7;margin-top:4px;">{{ $totalBugs }}</div>
                    </div>
                    <div style="padding:14px;border-radius:14px;background:#ecfdf5;border:1px solid #a7f3d0;">
                        <div style="font-size:11px;font-weight:800;color:#047857;text-transform:uppercase;">Resolved / Closed</div>
                        <div style="font-size:22px;font-weight:900;color:#059669;margin-top:4px;">{{ $resolvedBugs }}</div>
                    </div>
                    <div style="padding:14px;border-radius:14px;background:{{ $pendingBugs > 0 ? '#fff7ed' : '#f8fafc' }};border:1px solid {{ $pendingBugs > 0 ? '#fed7aa' : '#e2e8f0' }};">
                        <div style="font-size:11px;font-weight:800;color:{{ $pendingBugs > 0 ? '#c2410c' : '#64748b' }};text-transform:uppercase;">Pending Bugs</div>
                        <div style="font-size:22px;font-weight:900;color:{{ $pendingBugs > 0 ? '#ea580c' : '#475569' }};margin-top:4px;">{{ $pendingBugs }}</div>
                    </div>
                </div>

                @if($testingDetail->notes)
                    <div style="margin-top:18px;padding:16px 18px;border-radius:16px;background:#fefce8;border:1px solid #fef08a;">
                        <div style="font-size:11.5px;color:#854d0e;font-weight:800;text-transform:uppercase;margin-bottom:6px;">
                            📝 QA Remarks / Notes:
                        </div>
                        <div style="font-size:13px;color:#713f12;line-height:1.6;white-space:pre-line;">{!! e($testingDetail->notes) !!}</div>
                    </div>
                @endif

                {{-- Action Button --}}
                <div style="margin-top:28px;text-align:center;">
                    <a href="{{ $projectUrl }}" target="_blank" style="display:inline-block;padding:14px 28px;border-radius:12px;background:linear-gradient(135deg,#059669 0%,#10b981 100%);color:#ffffff;font-size:14.5px;font-weight:800;text-decoration:none;box-shadow:0 6px 18px rgba(5,150,105,0.35);">
                        View Project in CRM &rarr;
                    </a>
                </div>

                <div style="margin-top:28px;padding-top:20px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;">
                    Automated notification sent by Myagenci System. Recipients: Project Developers, Team Leads & Development Project Coordinator (projects@saitechnosolutions.net).
                </div>
            </div>
        </div>
    </div>
</body>
</html>
