<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isRescheduled ?? false ? 'Interview Rescheduled' : 'Interview Scheduled' }}: {{ $candidate->name }} - {{ $candidate->job_title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#1f2937;">
    @php
        $title = ($isRescheduled ?? false) ? 'Interview Rescheduled' : 'Interview Scheduled';
        $status = strtolower((string) ($interview->status ?? 'scheduled'));
        $statusColors = match ($status) {
            'completed' => ['bg' => '#ecfdf3', 'text' => '#047857', 'border' => '#a7f3d0'],
            'cancelled' => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'border' => '#fecaca'],
            default => ['bg' => '#fff7ed', 'text' => '#c2410c', 'border' => '#fed7aa'],
        };
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
                                Recruitment Notification
                            </span>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:22px;font-size:26px;line-height:1.2;font-weight:800;color:#fff;">{{ $title }}</div>
                <div style="margin-top:10px;font-size:14px;line-height:1.7;color:rgba(255,255,255,.9);max-width:560px;">
                    Interview details for candidate <strong>{{ $candidate->name }}</strong> ({{ $candidate->job_title }}).
                </div>
            </div>

            {{-- Main Body Card --}}
            <div style="background:#ffffff;border:1px solid #ece5de;border-top:0;border-radius:0 0 28px 28px;padding:28px;box-shadow:0 20px 45px rgba(18,18,18,.06);">
                <div style="font-size:15px;line-height:1.7;color:#4b5563;">
                    Hello <strong>{{ $interviewer->name }}</strong>,
                </div>

                <div style="margin-top:14px;font-size:14px;line-height:1.6;color:#374151;">
                    @if($isRescheduled ?? false)
                        The interview schedule for <strong>{{ $candidate->name }}</strong> has been updated. Please review the revised timing and details below:
                    @else
                        You have been assigned to conduct an interview for candidate <strong>{{ $candidate->name }}</strong>. Details are given below:
                    @endif
                </div>

                {{-- Interview Details Card --}}
                <div style="margin-top:18px;padding:18px 20px;border-radius:20px;background:#fff8f3;border:1px solid #ffd8bf;">
                    <div style="font-size:13px;color:#9a3412;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                        📅 Interview Details
                    </div>
                    <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr>
                            <td style="padding:7px 0;color:#7c5f4d;width:140px;">Scheduled At</td>
                            <td style="padding:7px 0;font-weight:800;color:#1d4ed8;">
                                {{ $interview->scheduled_at?->format('d M Y, h:i A') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:7px 0;color:#7c5f4d;">Round</td>
                            <td style="padding:7px 0;font-weight:700;color:#1f2937;">{{ $interview->round ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:7px 0;color:#7c5f4d;">Mode</td>
                            <td style="padding:7px 0;font-weight:700;color:#1f2937;">{{ $interview->mode_label }}</td>
                        </tr>
                        <tr>
                            <td style="padding:7px 0;color:#7c5f4d;">Status</td>
                            <td style="padding:7px 0;">
                                <span style="display:inline-block;padding:4px 10px;border-radius:999px;background:{{ $statusColors['bg'] }};color:{{ $statusColors['text'] }};border:1px solid {{ $statusColors['border'] }};font-size:12px;font-weight:800;text-transform:capitalize;">
                                    {{ $interview->status_label }}
                                </span>
                            </td>
                        </tr>
                        @if($interview->notes)
                            <tr>
                                <td style="padding:7px 0;color:#7c5f4d;vertical-align:top;">Notes</td>
                                <td style="padding:7px 0;color:#374151;line-height:1.5;">{!! nl2br(e($interview->notes)) !!}</td>
                            </tr>
                        @endif
                    </table>
                </div>

                {{-- Candidate Profile Card --}}
                <div style="margin-top:18px;padding:18px 20px;border-radius:20px;background:#f8fafc;border:1px solid #e2e8f0;">
                    <div style="font-size:13px;color:#475569;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px;">
                        👤 Candidate Profile
                    </div>
                    <table role="presentation" style="width:100%;border-collapse:collapse;font-size:14px;">
                        <tr>
                            <td style="padding:6px 0;color:#64748b;width:140px;">Candidate No</td>
                            <td style="padding:6px 0;font-weight:700;color:#1e293b;">{{ $candidate->candidate_no }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Candidate Name</td>
                            <td style="padding:6px 0;font-weight:700;color:#1e293b;">{{ $candidate->name }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Applied For</td>
                            <td style="padding:6px 0;font-weight:700;color:#1e293b;">{{ $candidate->job_title }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Mobile Number</td>
                            <td style="padding:6px 0;font-weight:700;color:#2563eb;">{{ $candidate->mobile_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Email</td>
                            <td style="padding:6px 0;color:#1e293b;">{{ $candidate->email ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Location</td>
                            <td style="padding:6px 0;color:#1e293b;">{{ $candidate->location ?: 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Candidate Type</td>
                            <td style="padding:6px 0;color:#1e293b;">{{ ucfirst($candidate->candidate_type ?: 'fresher') }}</td>
                        </tr>
                        @if($candidate->experience_years !== null)
                            <tr>
                                <td style="padding:6px 0;color:#64748b;">Experience</td>
                                <td style="padding:6px 0;color:#1e293b;">{{ $candidate->experience_years }} Year(s)</td>
                            </tr>
                        @endif
                        @if($candidate->expected_ctc !== null)
                            <tr>
                                <td style="padding:6px 0;color:#64748b;">Expected CTC</td>
                                <td style="padding:6px 0;color:#1e293b;">₹ {{ number_format((float) $candidate->expected_ctc, 2) }}</td>
                            </tr>
                        @endif
                    </table>
                </div>

                {{-- Action / Links Card --}}
                @if($interview->interview_link || $candidate->resume_path)
                    <div style="margin-top:18px;padding:16px 20px;border-radius:18px;background:#fff7f1;border:1px dashed #fdba8c;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#c2410c;margin-bottom:8px;">Quick Links</div>
                        @if($interview->interview_link)
                            <div style="margin-bottom:8px;">
                                🔗 <strong>Meeting Link:</strong>
                                <a href="{{ $interview->interview_link }}" target="_blank" style="color:#fe5f04;font-weight:700;text-decoration:none;word-break:break-all;">{{ $interview->interview_link }}</a>
                            </div>
                        @endif
                        @if($candidate->resume_path)
                            <div>
                                📄 <strong>Resume:</strong>
                                <a href="{{ asset('storage/' . $candidate->resume_path) }}" target="_blank" style="color:#fe5f04;font-weight:700;text-decoration:none;">View Candidate Resume</a>
                            </div>
                        @endif
                    </div>
                @endif

                <div style="margin-top:24px;padding-top:18px;border-top:1px solid #ece5de;font-size:13px;line-height:1.8;color:#6b7280;">
                    Regards,<br>
                    <span style="font-weight:800;color:#111827;">Myagenci</span><br>
                    <span style="color:#9ca3af;">Recruitment Portal</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
