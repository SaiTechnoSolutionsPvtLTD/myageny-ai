<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRMS Calendar Task Notification</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f5f7; margin: 0; padding: 0; color: #121212; }
        .wrapper { max-width: 600px; margin: 24px auto; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e1dee3; box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
        .header { background: linear-gradient(135deg, #fe5f04, #ff8f42); padding: 24px 30px; text-align: left; color: #ffffff; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.3px; }
        .header p { margin: 4px 0 0; font-size: 13px; opacity: 0.9; }
        .content { padding: 30px; }
        .greeting { font-size: 16px; font-weight: 700; color: #121212; margin-bottom: 12px; }
        .sub-text { font-size: 14px; color: #555555; line-height: 1.5; margin-bottom: 24px; }
        .task-box { background: #fff7ed; border: 1px solid #ffedd5; border-left: 4px solid #fe5f04; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px; }
        .task-field { margin-bottom: 12px; }
        .task-field:last-child { margin-bottom: 0; }
        .task-lbl { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #7c7c7c; letter-spacing: 0.4px; margin-bottom: 3px; }
        .task-val { font-size: 15px; font-weight: 700; color: #121212; }
        .task-remarks { font-size: 14px; color: #333333; line-height: 1.6; white-space: pre-line; background: #ffffff; padding: 12px 14px; border-radius: 8px; border: 1px solid #ffe4d6; margin-top: 4px; }
        .cta-box { text-align: center; margin: 28px 0 10px; }
        .btn-cta { display: inline-block; background: linear-gradient(135deg, #fe5f04, #ff8f42); color: #ffffff !important; text-decoration: none; font-weight: 800; font-size: 14px; padding: 12px 28px; border-radius: 10px; box-shadow: 0 4px 14px rgba(254,95,4,0.3); }
        .footer { background: #faf9fb; padding: 18px 30px; border-top: 1px solid #e1dee3; text-align: center; font-size: 12px; color: #9e9e9e; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>📅 HRMS Calendar Task Reminder</h1>
            <p>Scheduled Task Notification</p>
        </div>

        <div class="content">
            <div class="greeting">Hello {{ $hrmsTask->user?->name ?? 'Team Member' }},</div>
            <div class="sub-text">
                You have an HRMS calendar task scheduled for today. Please review the details below:
            </div>

            <div class="task-box">
                <div class="task-field">
                    <div class="task-lbl">Scheduled Date & Time</div>
                    <div class="task-val">
                        📅 {{ $hrmsTask->task_date ? $hrmsTask->task_date->format('d M Y') : 'N/A' }} 
                        @if($hrmsTask->task_time)
                            &bull; ⏰ {{ date('h:i A', strtotime($hrmsTask->task_time)) }}
                        @endif
                    </div>
                </div>

                <div class="task-field">
                    <div class="task-lbl">Remarks / Task Description</div>
                    <div class="task-remarks">{{ $hrmsTask->remarks }}</div>
                </div>

                @if($hrmsTask->creator)
                    <div class="task-field" style="margin-top:12px;">
                        <div class="task-lbl">Created By</div>
                        <div class="task-val" style="font-size:13px; font-weight:600;">{{ $hrmsTask->creator->name }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="footer">
            Sent automatically by {{ config('app.name', 'Agency CRM') }} HRMS System.
        </div>
    </div>
</body>
</html>
