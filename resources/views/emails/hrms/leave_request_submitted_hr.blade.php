<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Leave Request Submitted</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:'Segoe UI', Arial, sans-serif;color:#1f2937;">
    <div style="padding:28px 14px;">
        <div style="max-width:640px;margin:0 auto;">
            <!-- Header -->
            <div style="background:linear-gradient(135deg,#fe5f04 0%,#ff8745 100%);border-radius:24px 24px 0 0;padding:26px 28px;box-shadow:0 12px 30px rgba(254,95,4,.18);">
                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="Myagenci" style="max-width:170px;height:auto;display:block;">
                        </td>
                        <td style="text-align:right;vertical-align:middle;">
                            <span style="display:inline-block;padding:6px 14px;border-radius:999px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);font-size:12px;font-weight:700;color:#fff;letter-spacing:.03em;">
                                HR Notification
                            </span>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:20px;font-size:24px;line-height:1.25;font-weight:800;color:#fff;">
                    New Leave Request Submitted
                </div>
                <div style="margin-top:8px;font-size:14px;line-height:1.6;color:rgba(255,255,255,.92);">
                    {{ $applicantName }} has submitted a new leave application in HRMS.
                </div>
            </div>

            <!-- Body -->
            <div style="background:#ffffff;border:1px solid #ece5de;border-top:0;border-radius:0 0 24px 24px;padding:28px;box-shadow:0 15px 35px rgba(18,18,18,.05);">
                <div style="font-size:14px;color:#4b5563;line-height:1.6;">
                    Hello HR Team,
                </div>
                <div style="margin-top:6px;font-size:14px;color:#4b5563;line-height:1.6;">
                    A new leave request has been submitted by <strong>{{ $applicantName }}</strong>. The details are provided below:
                </div>

                <!-- Info Table Card -->
                <div style="margin-top:20px;padding:20px;border-radius:18px;background:#fafaf9;border:1px solid #e7e5e4;">
                    <table role="presentation" style="width:100%;border-collapse:collapse;font-size:13px;">
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;width:38%;">Employee Name</td>
                            <td style="padding:8px 0;font-weight:700;color:#1c1917;text-align:right;">{{ $applicantName }}</td>
                        </tr>
                        @if($employeeCode && $employeeCode !== 'N/A')
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Employee ID</td>
                            <td style="padding:8px 0;font-weight:700;color:#1c1917;text-align:right;border-top:1px dashed #e7e5e4;">{{ $employeeCode }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Department / Role</td>
                            <td style="padding:8px 0;font-weight:600;color:#1c1917;text-align:right;border-top:1px dashed #e7e5e4;">{{ $departmentName }} &bull; {{ $designation }}</td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Leave Type</td>
                            <td style="padding:8px 0;text-align:right;border-top:1px dashed #e7e5e4;">
                                <span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#ffedd5;color:#c2410c;font-weight:700;font-size:12px;">
                                    {{ $leaveType }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Leave Period</td>
                            <td style="padding:8px 0;font-weight:700;color:#1c1917;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $startDate }} &rarr; {{ $endDate }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Total Days</td>
                            <td style="padding:8px 0;font-weight:800;color:#ea580c;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $totalDays }} {{ $totalDays == 1 ? 'day' : 'days' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Pending Approval With</td>
                            <td style="padding:8px 0;font-weight:600;color:#0369a1;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $currentApproverName }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Submitted At</td>
                            <td style="padding:8px 0;color:#57534e;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $submittedAt }}
                            </td>
                        </tr>
                    </table>

                    @if($reason)
                    <div style="margin-top:14px;padding-top:12px;border-top:1px solid #e7e5e4;">
                        <div style="font-size:12px;color:#78716c;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Reason</div>
                        <div style="margin-top:6px;font-size:13px;line-height:1.6;color:#292524;background:#ffffff;padding:10px 14px;border-radius:10px;border:1px solid #e7e5e4;">
                            {{ $reason }}
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Call to action button -->
                <div style="margin-top:24px;text-align:center;">
                    <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 28px;background:#fe5f04;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;border-radius:12px;box-shadow:0 6px 18px rgba(254,95,4,.3);">
                        View Leave Request
                    </a>
                </div>

                <!-- Footer -->
                <div style="margin-top:28px;padding-top:18px;border-top:1px solid #ece5de;font-size:12px;line-height:1.7;color:#78716c;text-align:center;">
                    This is an automated notification from <strong>myAgenci.ai HRMS</strong>.<br>
                    Recipient: <a href="mailto:hr@saitechnosolutions.net" style="color:#fe5f04;text-decoration:none;">hr@saitechnosolutions.net</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
