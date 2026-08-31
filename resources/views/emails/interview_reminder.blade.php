<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment Interview Schedule Reminder</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;">
    @php
        $count = $interviews->count();
        $dateFormatted = $targetDate->format('d M Y (l)');
    @endphp

    <div style="padding:28px 12px;">
        <div style="max-width:820px;margin:0 auto;">
            {{-- Top Header Banner --}}
            <div style="background:linear-gradient(135deg,#fe5f04 0%,#ff8745 100%);border-radius:24px 24px 0 0;padding:26px 28px 20px;box-shadow:0 15px 35px rgba(254,95,4,.18);">
                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="Myagenci" style="max-width:180px;height:auto;display:block;">
                        </td>
                        <td style="text-align:right;vertical-align:top;">
                            <span style="display:inline-block;padding:6px 14px;border-radius:999px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);font-size:12px;font-weight:700;letter-spacing:.04em;color:#fff;">
                                🔔 Recruitment Daily Reminder
                            </span>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:20px;font-size:24px;line-height:1.25;font-weight:800;color:#fff;">
                    Interview Schedule Reminder
                </div>
                <div style="margin-top:6px;font-size:15px;color:rgba(255,255,255,.95);">
                    📅 Interview Date: <strong>{{ $dateFormatted }}</strong> &bull; Candidates: <strong>{{ $count }}</strong>
                </div>
            </div>

            {{-- Main Content Container --}}
            <div style="background:#ffffff;border:1px solid #e5ded7;border-top:0;border-radius:0 0 24px 24px;padding:26px;box-shadow:0 15px 35px rgba(0,0,0,.04);">
                
                {{-- Quick Summary Stats --}}
                <table role="presentation" style="width:100%;border-collapse:separate;border-spacing:12px;margin-bottom:20px;">
                    <tr>
                        <td style="background:#fff8f4;border:1px solid #ffd9c2;border-radius:14px;padding:16px 20px;width:50%;">
                            <div style="font-size:11px;font-weight:800;color:#c2410c;text-transform:uppercase;letter-spacing:.05em;">📅 Interview Date</div>
                            <div style="font-size:20px;font-weight:800;color:#111827;margin-top:4px;">{{ $targetDate->format('d M Y (l)') }}</div>
                        </td>
                        <td style="background:#f0f7ff;border:1px solid #cfe2fe;border-radius:14px;padding:16px 20px;width:50%;">
                            <div style="font-size:11px;font-weight:800;color:#1d4ed8;text-transform:uppercase;letter-spacing:.05em;">👥 Total Candidates Scheduled</div>
                            <div style="font-size:20px;font-weight:800;color:#111827;margin-top:4px;">{{ $count }} {{ Str::plural('Candidate', $count) }}</div>
                        </td>
                    </tr>
                </table>

                <div style="font-size:14px;line-height:1.6;color:#4b5563;margin-bottom:18px;">
                    Hello Team,<br>
                    Here is the consolidated interview schedule for <strong>{{ $targetDate->format('d M Y (l)') }}</strong>:
                </div>

                {{-- Schedule Table --}}
                <div style="overflow-x:auto;border:1px solid #ece6df;border-radius:16px;margin-bottom:24px;">
                    <table role="presentation" style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">
                        <thead>
                            <tr style="background:#faf8f5;border-bottom:2px solid #ecdcd0;color:#7c5f4d;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;">
                                <th style="padding:12px 14px;">Time & Round</th>
                                <th style="padding:12px 14px;">Candidate</th>
                                <th style="padding:12px 14px;">Position</th>
                                <th style="padding:12px 14px;">Interviewer</th>
                                <th style="padding:12px 14px;">Contact</th>
                                <th style="padding:12px 14px;text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($interviews as $index => $interview)
                                @php
                                    $cand = $interview->candidate;
                                    $rowBg = $index % 2 === 0 ? '#ffffff' : '#fdfcfa';
                                @endphp
                                <tr style="background:{{ $rowBg }};border-bottom:1px solid #f0eae3;">
                                    {{-- Time & Mode --}}
                                    <td style="padding:14px;vertical-align:top;white-space:nowrap;">
                                        <div style="font-size:14px;font-weight:800;color:#1d4ed8;">
                                            ⏰ {{ $interview->scheduled_at ? $interview->scheduled_at->format('h:i A') : 'N/A' }}
                                        </div>
                                        <div style="font-size:11px;font-weight:700;color:#6b7280;margin-top:3px;">
                                            {{ $interview->round ?: 'Round 1' }} &bull; {{ $interview->mode_label }}
                                        </div>
                                    </td>

                                    {{-- Candidate Name & ID --}}
                                    <td style="padding:14px;vertical-align:top;">
                                        <div style="font-weight:800;color:#111827;font-size:14px;">
                                            {{ $cand?->name ?: 'Candidate #' . ($index + 1) }}
                                        </div>
                                        @if($cand?->candidate_no)
                                            <span style="display:inline-block;margin-top:3px;padding:2px 7px;border-radius:6px;font-size:10px;font-weight:700;background:#fff2e8;color:#c2410c;border:1px solid #ffd6bc;">
                                                {{ $cand->candidate_no }}
                                            </span>
                                        @endif
                                        @if($cand?->experience_years !== null)
                                            <div style="font-size:11px;color:#6b7280;margin-top:3px;">
                                                Exp: {{ $cand->experience_years }} Yr(s)
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Job Position --}}
                                    <td style="padding:14px;vertical-align:top;">
                                        <strong style="color:#fe5f04;font-size:13px;">{{ $cand?->job_title ?: 'Position not specified' }}</strong>
                                        @if($cand?->location)
                                            <div style="font-size:11px;color:#6b7280;margin-top:3px;">📍 {{ $cand->location }}</div>
                                        @endif
                                    </td>

                                    {{-- Interviewer --}}
                                    <td style="padding:14px;vertical-align:top;">
                                        <div style="font-weight:700;color:#111827;">
                                            {{ $interview->interviewer_name ?: ($interview->interviewer?->name ?: 'Not assigned') }}
                                        </div>
                                    </td>

                                    {{-- Contact --}}
                                    <td style="padding:14px;vertical-align:top;">
                                        <div style="font-weight:700;color:#2563eb;">
                                            📞 {{ $cand?->mobile_number ?: 'N/A' }}
                                        </div>
                                        @if($cand?->email)
                                            <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                                                {{ $cand->email }}
                                            </div>
                                        @endif
                                    </td>

                                    {{-- Actions --}}
                                    <td style="padding:14px;vertical-align:top;text-align:center;white-space:nowrap;">
                                        @if($interview->recruitment_candidate_id)
                                            <a href="{{ route('recruitment.show', $interview->recruitment_candidate_id) }}" style="display:inline-block;padding:5px 12px;border-radius:8px;background:#fe5f04;color:#ffffff;text-decoration:none;font-size:11px;font-weight:700;margin-bottom:4px;">
                                                View
                                            </a><br>
                                        @endif
                                        @if($interview->interview_link)
                                            <a href="{{ $interview->interview_link }}" target="_blank" style="display:inline-block;padding:4px 10px;border-radius:6px;background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;text-decoration:none;font-size:10px;font-weight:700;margin-bottom:4px;">
                                                Join Link
                                            </a><br>
                                        @endif
                                        @if($cand?->resume_path)
                                            <a href="{{ asset('storage/' . $cand->resume_path) }}" target="_blank" style="display:inline-block;padding:4px 10px;border-radius:6px;background:#f3f4f6;color:#374151;border:1px solid #d1d5db;text-decoration:none;font-size:10px;font-weight:700;">
                                                Resume
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                                @if($interview->notes)
                                    <tr style="background:{{ $rowBg }};border-bottom:1px solid #f0eae3;">
                                        <td colspan="6" style="padding:6px 14px 12px;font-size:11px;color:#7c5f4d;font-style:italic;">
                                            <strong>Note:</strong> {{ $interview->notes }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Direct Portal Link --}}
                <div style="text-align:center;margin:20px 0 10px;">
                    <a href="{{ route('recruitment.index') }}" style="display:inline-block;background:linear-gradient(135deg,#fe5f04,#ff7c30);color:#ffffff !important;text-decoration:none;font-weight:800;font-size:14px;padding:12px 28px;border-radius:10px;box-shadow:0 4px 14px rgba(254,95,4,0.25);">
                        Open Recruitment Portal &rarr;
                    </a>
                </div>

                {{-- Footer --}}
                <div style="margin-top:26px;padding-top:18px;border-top:1px solid #ece5de;font-size:12px;line-height:1.7;color:#6b7280;text-align:center;">
                    This is an automated daily reminder for upcoming recruitment interviews.<br>
                    <span style="font-weight:800;color:#111827;">Myagenci</span> &bull; HRMS & Recruitment System
                </div>
            </div>
        </div>
    </div>
</body>
</html>
