<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $payload['title'] ?? 'HRMS Update' }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,sans-serif;color:#1f2937;">
    @php
        $title = $payload['title'] ?? 'HRMS Update';
        $message = $payload['message'] ?? 'You have a new HRMS update.';
        $detail = $payload['detail'] ?? null;
        $actionUrl = $payload['action_url'] ?? null;
        $requestType = ucfirst((string) ($payload['request_type'] ?? 'request'));
        $status = strtolower((string) ($payload['status'] ?? 'pending'));
        $requesterName = $payload['requester_name'] ?? null;
        $actorName = $payload['actor_name'] ?? null;
        $previousApprovals = $payload['previous_approvals'] ?? null;
        $statusColors = match ($status) {
            'approved' => ['bg' => '#ecfdf3', 'text' => '#047857', 'border' => '#a7f3d0'],
            'rejected' => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'border' => '#fecaca'],
            default => ['bg' => '#fff7ed', 'text' => '#c2410c', 'border' => '#fed7aa'],
        };
    @endphp

    <div style="padding:28px 14px;">
        <div style="max-width:680px;margin:0 auto;">
            <div style="background:linear-gradient(135deg,#fe5f04 0%,#ff8745 100%);border-radius:28px 28px 0 0;padding:28px 28px 22px;box-shadow:0 20px 45px rgba(18,18,18,.08);">
                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="Myagenci" style="max-width:190px;height:auto;display:block;">
                        </td>
                        <td style="text-align:right;vertical-align:top;">
                            <span style="display:inline-block;padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.24);font-size:12px;font-weight:700;letter-spacing:.04em;color:#fff;">
                                {{ $requestType }} Notification
                            </span>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:22px;font-size:28px;line-height:1.2;font-weight:800;color:#fff;">{{ $title }}</div>
                <div style="margin-top:10px;font-size:14px;line-height:1.7;color:rgba(255,255,255,.9);max-width:560px;">
                    {{ $message }}
                </div>
            </div>

            <div style="background:#ffffff;border:1px solid #ece5de;border-top:0;border-radius:0 0 28px 28px;padding:28px;box-shadow:0 20px 45px rgba(18,18,18,.06);">
                <div style="font-size:15px;line-height:1.7;color:#4b5563;">
                    Hello {{ $notifiable->name ?? 'Team' }},
                </div>

                <div style="margin-top:18px;padding:18px 20px;border-radius:20px;background:#fff8f3;border:1px solid #ffd8bf;">
                    <table role="presentation" style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="font-size:12px;color:#9a3412;font-weight:700;text-transform:uppercase;letter-spacing:.06em;padding-bottom:10px;">
                                Status
                            </td>
                            <td style="text-align:right;padding-bottom:10px;">
                                <span style="display:inline-block;padding:7px 12px;border-radius:999px;background:{{ $statusColors['bg'] }};color:{{ $statusColors['text'] }};border:1px solid {{ $statusColors['border'] }};font-size:12px;font-weight:800;text-transform:capitalize;">
                                    {{ $status ?: 'pending' }}
                                </span>
                            </td>
                        </tr>
                        @if($requesterName)
                            <tr>
                                <td style="padding:10px 0 0;font-size:13px;color:#7c5f4d;">Requester</td>
                                <td style="padding:10px 0 0;text-align:right;font-size:14px;font-weight:700;color:#1f2937;">{{ $requesterName }}</td>
                            </tr>
                        @endif
                        @if($actorName)
                            <tr>
                                <td style="padding:10px 0 0;font-size:13px;color:#7c5f4d;">Latest Action By</td>
                                <td style="padding:10px 0 0;text-align:right;font-size:14px;font-weight:700;color:#1f2937;">{{ $actorName }}</td>
                            </tr>
                        @endif
                        @if($detail)
                            <tr>
                                <td style="padding:10px 0 0;font-size:13px;color:#7c5f4d;">Summary</td>
                                <td style="padding:10px 0 0;text-align:right;font-size:14px;font-weight:700;color:#1f2937;">{{ $detail }}</td>
                            </tr>
                        @endif
                    </table>
                </div>

                @if($previousApprovals)
                    <div style="margin-top:18px;padding:16px 18px;border-radius:18px;background:#f8fafc;border:1px solid #e2e8f0;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#64748b;">Approval History</div>
                        <div style="margin-top:8px;font-size:14px;line-height:1.7;color:#334155;">{{ $previousApprovals }}</div>
                    </div>
                @endif

                @if($actionUrl)
                    <div style="margin-top:22px;padding:18px 20px;border-radius:18px;background:#fff7f1;border:1px dashed #fdba8c;">
                        <div style="font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#c2410c;">Open Request</div>
                        <div style="margin-top:8px;font-size:14px;line-height:1.7;color:#7c2d12;">
                            Please open the link below to view the full request details:
                        </div>
                        <div style="margin-top:10px;word-break:break-word;">
                            <a href="{{ $actionUrl }}" style="color:#fe5f04;font-size:14px;font-weight:700;text-decoration:none;">{{ $actionUrl }}</a>
                        </div>
                    </div>
                @endif

                <div style="margin-top:24px;padding-top:18px;border-top:1px solid #ece5de;font-size:13px;line-height:1.8;color:#6b7280;">
                    Regards,<br>
                    <span style="font-weight:800;color:#111827;">Myagenci</span><br>
                    <span style="color:#9ca3af;">HRMS Notifications</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
