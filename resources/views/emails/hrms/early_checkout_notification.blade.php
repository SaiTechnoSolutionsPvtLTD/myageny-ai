<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Early Checkout Alert</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:'Segoe UI', Arial, sans-serif;color:#1f2937;">
    <div style="padding:28px 14px;">
        <div style="max-width:640px;margin:0 auto;">
            <!-- Header -->
            <div style="background:linear-gradient(135deg,#d97706 0%,#f59e0b 100%);border-radius:24px 24px 0 0;padding:26px 28px;box-shadow:0 12px 30px rgba(217,119,6,.18);">
                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="Myagenci" style="max-width:170px;height:auto;display:block;">
                        </td>
                        <td style="text-align:right;vertical-align:middle;">
                            <span style="display:inline-block;padding:6px 14px;border-radius:999px;background:rgba(255,255,255,.22);border:1px solid rgba(255,255,255,.35);font-size:12px;font-weight:700;color:#fff;letter-spacing:.03em;">
                                Attendance Alert
                            </span>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:20px;font-size:24px;line-height:1.25;font-weight:800;color:#fff;">
                    Early Checkout Alert
                </div>
                <div style="margin-top:8px;font-size:14px;line-height:1.6;color:rgba(255,255,255,.94);">
                    <strong>{{ $employeeName }}</strong> checked out today before the evening <strong>{{ $standardCheckoutTime }}</strong> schedule.
                </div>
            </div>

            <!-- Body -->
            <div style="background:#ffffff;border:1px solid #ece5de;border-top:0;border-radius:0 0 24px 24px;padding:28px;box-shadow:0 15px 35px rgba(18,18,18,.05);">
                <div style="font-size:14px;color:#4b5563;line-height:1.6;">
                    Hello HR Team,
                </div>
                <div style="margin-top:6px;font-size:14px;color:#4b5563;line-height:1.6;">
                    An employee checkout before <strong>06:30 PM</strong> was recorded today. The attendance details are below:
                </div>

                <!-- Alert Highlight Banner -->
                <div style="margin-top:18px;padding:14px 18px;border-radius:14px;background:#fef3c7;border:1px solid #fde68a;">
                    <table role="presentation" style="width:100%;border-collapse:collapse;">
                        <tr>
                            <td style="width:28px;vertical-align:top;font-size:18px;line-height:1;">⚠️</td>
                            <td style="font-size:13px;line-height:1.6;color:#92400e;">
                                <strong>Early Departure:</strong> Checked out at <strong>{{ $logoutTime }}</strong> ({{ $earlyDifference }}).
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Info Table Card -->
                <div style="margin-top:20px;padding:20px;border-radius:18px;background:#fafaf9;border:1px solid #e7e5e4;">
                    <table role="presentation" style="width:100%;border-collapse:collapse;font-size:13px;">
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;width:38%;">Employee Name</td>
                            <td style="padding:8px 0;font-weight:700;color:#1c1917;text-align:right;">{{ $employeeName }}</td>
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
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Attendance Date</td>
                            <td style="padding:8px 0;font-weight:700;color:#1c1917;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $attendanceDate }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Check-in (In Time)</td>
                            <td style="padding:8px 0;font-weight:600;color:#15803d;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $loginTime }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Check-out (Out Time)</td>
                            <td style="padding:8px 0;font-weight:800;color:#b45309;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $logoutTime }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Standard Shift End</td>
                            <td style="padding:8px 0;font-weight:600;color:#57534e;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $standardCheckoutTime }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Working Hours Logged</td>
                            <td style="padding:8px 0;font-weight:700;color:#1c1917;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $workingHours }}
                            </td>
                        </tr>
                        @if($location && $location !== 'N/A')
                        <tr>
                            <td style="padding:8px 0;color:#78716c;font-weight:500;border-top:1px dashed #e7e5e4;">Checkout Location</td>
                            <td style="padding:8px 0;color:#44403c;text-align:right;border-top:1px dashed #e7e5e4;">
                                {{ $location }}
                            </td>
                        </tr>
                        @endif
                    </table>

                    @if($remarks)
                    <div style="margin-top:14px;padding-top:12px;border-top:1px solid #e7e5e4;">
                        <div style="font-size:12px;color:#78716c;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Remarks / Reason</div>
                        <div style="margin-top:6px;font-size:13px;line-height:1.6;color:#292524;background:#ffffff;padding:10px 14px;border-radius:10px;border:1px solid #e7e5e4;">
                            {{ $remarks }}
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Footer -->
                <div style="margin-top:28px;padding-top:18px;border-top:1px solid #ece5de;font-size:12px;line-height:1.7;color:#78716c;text-align:center;">
                    This is an automated attendance monitoring alert from <strong>myAgenci.ai HRMS</strong>.<br>
                    Recipient: <a href="mailto:hr@saitechnosolutions.net" style="color:#d97706;text-decoration:none;">hr@saitechnosolutions.net</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
