<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Tasks & Reminders Digest</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f5f7; margin: 0; padding: 0; color: #121212; }
        .wrapper { max-width: 650px; margin: 20px auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e1dee3; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #fe5f04, #ff7c30); padding: 24px 30px; text-align: left; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.3px; }
        .header p { margin: 4px 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 30px; }
        .greeting { font-size: 16px; font-weight: 700; color: #121212; margin-bottom: 12px; }
        .sub-text { font-size: 14px; color: #555555; line-height: 1.5; margin-bottom: 24px; }
        .stats-grid { display: flex; gap: 14px; margin-bottom: 26px; }
        .stat-card { flex: 1; padding: 14px 18px; border-radius: 12px; border: 1px solid #e1dee3; background: #faf8fb; }
        .stat-card.overdue { background: #fef2f2; border-color: #fecaca; }
        .stat-card.today { background: #fff7ed; border-color: #ffedd5; }
        .stat-val { font-size: 22px; font-weight: 800; margin-bottom: 2px; }
        .stat-card.overdue .stat-val { color: #dc2626; }
        .stat-card.today .stat-val { color: #ea580c; }
        .stat-lbl { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #7c7c7c; letter-spacing: 0.4px; }
        .section-title { font-size: 15px; font-weight: 800; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 2px solid #f0edf2; color: #121212; display: flex; align-items: center; justify-content: space-between; }
        .section-title.overdue-title { color: #dc2626; border-color: #fecaca; }
        .section-title.today-title { color: #ea580c; border-color: #ffedd5; }
        .task-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
        .task-table th { background: #f8f7f9; text-align: left; padding: 10px 12px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #7c7c7c; border-bottom: 1px solid #e1dee3; }
        .task-table td { padding: 12px; border-bottom: 1px solid #f1eef2; vertical-align: top; }
        .lead-name { font-weight: 700; color: #fe5f04; text-decoration: none; }
        .task-title { font-weight: 700; color: #121212; font-size: 13px; }
        .task-desc { font-size: 12px; color: #666666; margin-top: 3px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .badge-high { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-medium { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-low { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .cta-box { text-align: center; margin: 30px 0 10px; }
        .btn-cta { display: inline-block; background: linear-gradient(135deg, #fe5f04, #ff7c30); color: #ffffff !important; text-decoration: none; font-weight: 800; font-size: 14px; padding: 12px 28px; border-radius: 10px; box-shadow: 0 4px 14px rgba(254,95,4,0.3); }
        .footer { background: #faf9fb; padding: 18px 30px; border-top: 1px solid #e1dee3; text-align: center; font-size: 12px; color: #9e9e9e; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>🔔 Daily Tasks & Reminders Digest</h1>
            <p>{{ now()->format('D, d M Y') }} — Sales Team Digest</p>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $user->name }},</div>
            <div class="sub-text">
                Here is your scheduled daily task and reminder summary. You have action items that require your attention today.
            </div>

            <div class="stats-grid">
                <div class="stat-card today">
                    <div class="stat-val">{{ $todayReminders->count() }}</div>
                    <div class="stat-lbl">Today Planned</div>
                </div>
                <div class="stat-card overdue">
                    <div class="stat-val">{{ $overdueReminders->count() }}</div>
                    <div class="stat-lbl">Overdue Tasks</div>
                </div>
            </div>

            {{-- ⚠️ OVERDUE SECTION --}}
            @if($overdueReminders->isNotEmpty())
                <div class="section-title overdue-title">
                    <span>⚠️ Overdue Reminders ({{ $overdueReminders->count() }})</span>
                </div>
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Lead Account</th>
                            <th>Task Title & Remarks</th>
                            <th>Scheduled Date</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overdueReminders as $rem)
                            <tr>
                                <td>
                                    @if($rem->lead)
                                        <div class="lead-name">{{ $rem->lead->company_name ?: $rem->lead->contact_name }}</div>
                                        <div style="font-size:11px; color:#7c7c7c;">{{ $rem->lead->contact_name }} ({{ $rem->lead->mobile_number }})</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div class="task-title">{{ $rem->title }}</div>
                                    @if($rem->description)
                                        <div class="task-desc">{{ Str::limit($rem->description, 80) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight:700; color:#dc2626;">📅 {{ $rem->remind_at ? $rem->remind_at->format('d M Y') : 'N/A' }}</div>
                                    <div style="font-size:11px; color:#7c7c7c;">⏰ {{ $rem->remainder_time ? $rem->remainder_time->format('h:i A') : '10:00 AM' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $rem->priority ?? 'high' }}">{{ ucfirst($rem->priority ?? 'high') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- 📅 TODAY PLANNED SECTION --}}
            @if($todayReminders->isNotEmpty())
                <div class="section-title today-title">
                    <span>📅 Today Planned Reminders ({{ $todayReminders->count() }})</span>
                </div>
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Lead Account</th>
                            <th>Task Title & Remarks</th>
                            <th>Scheduled Time</th>
                            <th>Priority</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todayReminders as $rem)
                            <tr>
                                <td>
                                    @if($rem->lead)
                                        <div class="lead-name">{{ $rem->lead->company_name ?: $rem->lead->contact_name }}</div>
                                        <div style="font-size:11px; color:#7c7c7c;">{{ $rem->lead->contact_name }} ({{ $rem->lead->mobile_number }})</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <div class="task-title">{{ $rem->title }}</div>
                                    @if($rem->description)
                                        <div class="task-desc">{{ Str::limit($rem->description, 80) }}</div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight:700; color:#ea580c;">⏰ {{ $rem->remainder_time ? $rem->remainder_time->format('h:i A') : '10:00 AM' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $rem->priority ?? 'high' }}">{{ ucfirst($rem->priority ?? 'high') }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="footer">
            Sent automatically by {{ config('app.name', 'Agency CRM') }}. Please do not reply directly to this email.
        </div>
    </div>
</body>
</html>
