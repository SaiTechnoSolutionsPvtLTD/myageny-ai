<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Moved to Testing: {{ $project->leadProduct?->name ?: ($project->product?->name ?? 'Project #' . $project->id) }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#1f2937;">
    @php
        $projectName = $project->leadProduct?->name ?: ($project->product?->name ?? 'Project #' . $project->id);
        $clientName = $project->company_name ?: ($project->client_name ?: 'Client');
        $movedBy = $testingDetail->movedBy?->name ?? auth()->user()?->name ?? 'Development Team';
        $testingTlName = $testingDetail->testingTl?->name ?? 'Testing Team Lead';
        $transferTime = $testingDetail->created_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A');
    @endphp

    <div style="padding:28px 14px;">
        <div style="max-width:680px;margin:0 auto;">
            {{-- Header Card --}}
            <div style="background:linear-gradient(135deg,#fe5f04 0%,#ff8745 100%);border-radius:28px 28px 0 0;padding:28px 28px 22px;box-shadow:0 20px 45px rgba(18,18,18,.08);">
                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="Myagenci" style="max-width:190px;height:auto;display:block;">
                        </td>
                        <td style="text-align:right;vertical-align:top;">
                            <span style="display:inline-block;padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.24);font-size:12px;font-weight:700;letter-spacing:.04em;color:#fff;">
                                Testing Handover
                            </span>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:22px;font-size:26px;line-height:1.2;font-weight:800;color:#fff;">🧪 Project Moved to Testing</div>
                <div style="margin-top:10px;font-size:14px;line-height:1.7;color:rgba(255,255,255,.9);max-width:560px;">
                    Project <strong>{{ $projectName }}</strong> has been transferred from Development to Testing.
                </div>
            </div>

            {{-- Main Body Card --}}
            <div style="background:#ffffff;border:1px solid #ece5de;border-top:0;border-radius:0 0 28px 28px;padding:28px;box-shadow:0 20px 45px rgba(18,18,18,.06);">
                <div style="font-size:15px;line-height:1.7;color:#4b5563;">
                    Hello <strong>{{ $testingTlName }}</strong>,
                </div>

                <div style="margin-top:14px;font-size:14px;line-height:1.6;color:#374151;">
                    The Development Team has completed work on <strong>{{ $projectName }}</strong> and handed over the project for QA/Testing. Detailed project specifications and access credentials are provided below:
                </div>

                {{-- Project Details Card --}}
                <div style="margin-top:18px;padding:18px 20px;border-radius:20px;background:#fff8f3;border:1px solid #ffd8bf;">
                    <div style="font-size:13px;color:#9a3412;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                        📌 Project Summary
                    </div>
                    <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;width:35%;">Project Name:</td>
                            <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $projectName }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;">Client / Company:</td>
                            <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $clientName }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;">Handover Date & Time:</td>
                            <td style="padding:6px 0;font-weight:700;color:#2563eb;">{{ $transferTime }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;">Transferred By:</td>
                            <td style="padding:6px 0;font-weight:700;color:#111827;">{{ $movedBy }}</td>
                        </tr>
                        @if($project->project_delivery_date)
                        <tr>
                            <td style="padding:6px 0;color:#6b7280;">Delivery Target:</td>
                            <td style="padding:6px 0;font-weight:700;color:#dc2626;">{{ \Carbon\Carbon::parse($project->project_delivery_date)->format('d M Y') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                {{-- Testing Credentials & Access Card --}}
                <div style="margin-top:18px;padding:18px 20px;border-radius:20px;background:#f0f9ff;border:1px solid #bae6fd;">
                    <div style="font-size:13px;color:#0369a1;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                        🔑 Testing Access & Credentials
                    </div>

                    @if($testingDetail->credentials)
                        <div style="margin-bottom:14px;">
                            <div style="font-size:12px;color:#64748b;font-weight:600;margin-bottom:4px;">LOGIN CREDENTIALS & ACCESS DETAILS:</div>
                            <div style="background:#ffffff;padding:12px 14px;border-radius:10px;border:1px solid #cbd5e1;font-family:monospace;font-size:13px;color:#0f172a;white-space:pre-line;">
                                {!! e($testingDetail->credentials) !!}
                            </div>
                        </div>
                    @endif

                    @if($testingDetail->notes)
                        <div>
                            <div style="font-size:12px;color:#64748b;font-weight:600;margin-bottom:4px;">DEVELOPER REMARKS / NOTES:</div>
                            <div style="background:#ffffff;padding:12px 14px;border-radius:10px;border:1px solid #cbd5e1;font-size:13px;color:#334155;white-space:pre-line;">
                                {!! e($testingDetail->notes) !!}
                            </div>
                        </div>
                    @endif
                </div>


                <div style="margin-top:28px;padding-top:20px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;">
                    Automated notification sent by Myagenci System. CC: projects@saitechnosolutions.net & Project Team Leads.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
