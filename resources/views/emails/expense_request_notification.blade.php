<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Expense Approval Request</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
        .email-card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; }
        .email-header { background: linear-gradient(135deg, #fe5f04, #ff7c30); padding: 24px 30px; color: #ffffff; }
        .email-header h2 { margin: 0; font-size: 20px; font-weight: 800; }
        .email-header p { margin: 6px 0 0; font-size: 13px; opacity: .9; }
        .email-body { padding: 30px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .info-table td { padding: 12px 14px; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .info-table td.label { font-weight: 700; color: #6b7280; width: 140px; }
        .info-table td.value { font-weight: 800; color: #111827; }
        .amount-badge { display: inline-block; padding: 6px 14px; background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; border-radius: 8px; font-size: 16px; font-weight: 800; }
        .btn-action { display: inline-block; padding: 12px 28px; background: #fe5f04; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 800; font-size: 14px; margin-top: 10px; }
        .email-footer { background: #fafafa; padding: 16px 30px; text-align: center; font-size: 12px; color: #9ca3af; border-top: 1px solid #f3f4f6; }
    </style>
</head>
<body>
    <div class="email-card">
        <div class="email-header">
            <h2>Expense Approval Required</h2>
            <p>A new expense request has been submitted and awaits your review.</p>
        </div>

        <div class="email-body">
            <p style="font-size:15px; margin-top:0;">Hello,</p>
            <p style="font-size:14px; color:#4b5563; line-height:1.6;">
                <strong>{{ $applicantName }}</strong> ({{ $applicantRole }}) from <strong>{{ $applicantBranch }}</strong> has submitted an expense approval request for your review based on the configured expense approval pipeline.
            </p>

            <table class="info-table">
                <tr>
                    <td class="label">Applicant:</td>
                    <td class="value">{{ $applicantName }}</td>
                </tr>
                <tr>
                    <td class="label">Role:</td>
                    <td class="value">{{ $applicantRole }}</td>
                </tr>
                <tr>
                    <td class="label">Branch:</td>
                    <td class="value">{{ $applicantBranch }}</td>
                </tr>
                <tr>
                    <td class="label">Category:</td>
                    <td class="value">{{ $categoryName }}</td>
                </tr>
                <tr>
                    <td class="label">Amount:</td>
                    <td class="value">
                        <span class="amount-badge">₹{{ number_format($amount, 2) }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="label">Description:</td>
                    <td class="value" style="font-weight:normal; color:#374151; white-space:pre-wrap;">{{ $description }}</td>
                </tr>
            </table>

            <div style="text-align: center; margin-top: 24px;">
                <a href="{{ $actionUrl }}" class="btn-action">Review Expense Request in HRMS</a>
            </div>
        </div>

        <div class="email-footer">
            Sent automatically by myAgenci.ai HRMS Workflow System.
        </div>
    </div>
</body>
</html>
