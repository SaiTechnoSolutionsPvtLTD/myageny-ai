<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $announcement->title }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 28px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .email-header {
            background: linear-gradient(135deg, #fe5f04 0%, #ff8038 100%);
            padding: 26px 32px;
            color: #ffffff;
        }
        .header-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(4px);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 10px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.3;
        }
        .email-body {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .intro-text {
            font-size: 14px;
            color: #475569;
            line-height: 1.55;
            margin-bottom: 22px;
        }
        .meta-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 22px;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 13px;
        }
        .meta-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .meta-row:first-child {
            padding-top: 0;
        }
        .meta-label {
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.04em;
        }
        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }
        .priority-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 2px 8px;
            border-radius: 6px;
            letter-spacing: 0.04em;
        }
        .priority-high {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .priority-medium {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .priority-low {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .message-box {
            background: #fff7ed;
            border: 1.5px solid #ffedd5;
            border-left: 5px solid #fe5f04;
            border-radius: 12px;
            padding: 20px 22px;
            margin-bottom: 26px;
        }
        .message-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            color: #c2410c;
            letter-spacing: 0.06em;
            margin-bottom: 10px;
        }
        .message-content {
            font-size: 14.5px;
            line-height: 1.65;
            color: #1e293b;
            white-space: pre-line;
        }
        .action-box {
            text-align: center;
            margin: 28px 0 10px;
        }
        .btn-action {
            display: inline-block;
            background: linear-gradient(135deg, #fe5f04 0%, #ff8038 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-weight: 800;
            font-size: 14px;
            padding: 13px 32px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(254, 95, 4, 0.35);
        }
        .email-footer {
            background: #f8fafc;
            padding: 20px 32px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <span class="header-badge">📢 HRMS Official Announcement</span>
            <h1>{{ $announcement->title }}</h1>
        </div>

        <div class="email-body">
            <div class="greeting">Hello {{ $recipientName }},</div>
            <div class="intro-text">
                A new company announcement has been published in the HRMS Portal for your branch. Please find the details below:
            </div>

            <div class="meta-card">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 6px 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; width: 35%;">Announcement Date</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 700; color: #0f172a; text-align: right;">
                            📅 {{ optional($announcement->announcement_date)->format('d M Y') ?: now()->format('d M Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-top: 1px dashed #e2e8f0;">Priority</td>
                        <td style="padding: 6px 0; text-align: right; border-top: 1px dashed #e2e8f0;">
                            @php
                                $priority = strtolower((string) $announcement->priority);
                            @endphp
                            <span class="priority-badge priority-{{ $priority }}">
                                {{ ucfirst($priority) }} Priority
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 6px 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-top: 1px dashed #e2e8f0;">Audience / Branch</td>
                        <td style="padding: 6px 0; font-size: 13px; font-weight: 700; color: #0f172a; text-align: right; border-top: 1px dashed #e2e8f0;">
                            🏢 {{ $announcement->getTargetBranchesLabel() }}
                        </td>
                    </tr>
                    @if($announcement->creator)
                        <tr>
                            <td style="padding: 6px 0; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-top: 1px dashed #e2e8f0;">Published By</td>
                            <td style="padding: 6px 0; font-size: 13px; font-weight: 600; color: #334155; text-align: right; border-top: 1px dashed #e2e8f0;">
                                {{ $announcement->creator->name }}
                            </td>
                        </tr>
                    @endif
                </table>
            </div>

            <div class="message-box">
                <div class="message-title">Announcement Message</div>
                <div class="message-content">{!! nl2br(e($announcement->message)) !!}</div>
            </div>

            <div class="action-box">
                <a href="{{ route('hrms-announcements.index') }}" class="btn-action" target="_blank">
                    Open HRMS Portal
                </a>
            </div>
        </div>

        <div class="email-footer">
            This is an automated notification from {{ config('app.name', 'Agency CRM') }} HRMS.<br>
            Please log into the portal to review latest updates and company notices.
        </div>
    </div>
</body>
</html>
