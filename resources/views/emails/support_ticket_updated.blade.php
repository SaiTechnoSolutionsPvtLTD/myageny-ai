<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Ticket Updated: {{ $ticket->subject }}</title>
</head>
<body style="margin:0;padding:0;background:#f6f6f6;font-family:Arial,sans-serif;color:#222;">
    <div style="max-width:640px;margin:0 auto;padding:28px 16px;">
        <div style="background:#ffffff;border:1px solid #e6e2e8;border-radius:12px;padding:24px;box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);">
            <div style="border-bottom:1px solid #f0eef2;padding-bottom:14px;margin-bottom:18px;">
                <h2 style="margin:0;font-size:18px;color:#fe5f04;">Support Ticket Update</h2>
                <p style="margin:4px 0 0;font-size:13px;color:#666;">Updated via Support Portal</p>
            </div>
            
            <p style="margin:0 0 14px;font-size:15px;">Dear {{ $creator->name }},</p>

            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                Your support ticket has been updated by <strong>{{ $updater->name }}</strong>.
            </p>

            <table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:14px;">
                <tr>
                    <td style="padding:9px 0;color:#666;border-bottom:1px solid #f0eef2;width:120px;vertical-align:top;">Ticket ID</td>
                    <td style="padding:9px 0;border-bottom:1px solid #f0eef2;">
                        <strong>#{{ $ticket->id }}</strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding:9px 0;color:#666;border-bottom:1px solid #f0eef2;vertical-align:top;">Subject</td>
                    <td style="padding:9px 0;border-bottom:1px solid #f0eef2;">
                        <strong>{{ $ticket->subject }}</strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding:9px 0;color:#666;border-bottom:1px solid #f0eef2;vertical-align:top;">New Status</td>
                    <td style="padding:9px 0;border-bottom:1px solid #f0eef2;">
                        <span style="background:#fff1e8;color:#c2410c;padding:3px 8px;border-radius:4px;font-size:12px;font-weight:700;text-transform:uppercase;">
                            {{ $ticket->status }}
                        </span>
                    </td>
                </tr>
            </table>

            @if($ticket->remark)
                <div style="background:#fff9f3;border:1px solid #ffedd5;border-radius:8px;padding:16px;margin-bottom:20px;">
                    <h4 style="margin:0 0 8px;font-size:14px;color:#c2410c;">Remarks / Update Message:</h4>
                    <div style="font-size:14px;line-height:1.6;color:#333;font-style:italic;">
                        {{ $ticket->remark }}
                    </div>
                </div>
            @endif

            <p style="margin:24px 0 0;font-size:15px;line-height:1.6;border-top:1px solid #f0eef2;padding-top:16px;">
                Please log in to the portal to view full details or reply if needed.
            </p>
            
            <p style="margin:20px 0 0;font-size:14px;color:#888;line-height:1.5;">
                Regards,<br>
                <strong>{{ config('app.name', 'My Agency') }} Support System</strong>
            </p>
        </div>
    </div>
</body>
</html>
