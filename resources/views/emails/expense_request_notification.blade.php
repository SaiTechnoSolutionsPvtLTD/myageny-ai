<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Approval Request - myAgenci.ai</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:'Segoe UI', Arial, sans-serif; color:#1f2937;">
    <div style="padding: 28px 14px;">
        <div style="max-width: 600px; margin: 0 auto;">
            {{-- Header --}}
            <div style="background: linear-gradient(135deg, #fe5f04 0%, #ff8745 100%); border-radius: 24px 24px 0 0; padding: 28px 30px; box-shadow: 0 20px 45px rgba(254, 95, 4, 0.15);">
                <table role="presentation" style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="myAgenci.ai" style="max-width: 170px; height: auto; display: block;">
                        </td>
                        <td style="text-align:right; vertical-align:middle;">
                            <span style="display:inline-block; padding: 6px 14px; border-radius: 999px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); font-size: 12px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px;">
                                STAGE {{ $stepNumber ?? 1 }} APPROVAL
                            </span>
                        </td>
                    </tr>
                </table>
                <h2 style="margin: 20px 0 6px; font-size: 24px; font-weight: 800; color: #ffffff; line-height: 1.2;">
                    Expense Approval Request
                </h2>
                <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.9); line-height: 1.5;">
                    A new expense request requires your review according to the HRMS approval pipeline hierarchy.
                </p>
            </div>

            {{-- Body --}}
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-top: 0; border-radius: 0 0 24px 24px; padding: 32px 30px; box-shadow: 0 20px 45px rgba(15, 23, 42, 0.05);">
                <p style="font-size: 15px; margin-top: 0; color: #111827; font-weight: 700;">
                    Hello Approver,
                </p>
                <p style="font-size: 14px; color: #4b5563; line-height: 1.6; margin-bottom: 24px;">
                    <strong>{{ $applicantName }}</strong> ({{ $applicantRole }}) from <strong>{{ $applicantBranch }}</strong> has submitted an expense reimbursement request. Please review the details below:
                </p>

                {{-- Summary Table --}}
                <div style="background: #fafafa; border: 1px solid #f3f4f6; border-radius: 16px; padding: 20px; margin-bottom: 24px;">
                    <table role="presentation" style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #6b7280; font-weight: 700; width: 140px;">Applicant:</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #111827; font-weight: 800;">{{ $applicantName }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #6b7280; font-weight: 700;">Role & Branch:</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #111827; font-weight: 700;">{{ $applicantRole }} &bull; {{ $applicantBranch }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #6b7280; font-weight: 700;">Expense Category:</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #111827; font-weight: 800;">{{ $categoryName }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #6b7280; font-weight: 700;">Requested Amount:</td>
                            <td style="padding: 8px 0;">
                                <span style="display: inline-block; padding: 6px 16px; background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; border-radius: 10px; font-size: 18px; font-weight: 800;">
                                    ₹{{ number_format($amount, 2) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #6b7280; font-weight: 700; vertical-align: top;">Description & Reason:</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #374151; line-height: 1.6; white-space: pre-wrap;">{{ $description }}</td>
                        </tr>
                        @php
                            $emailAttUrls = !empty($attachmentUrls) ? $attachmentUrls : (!empty($attachmentUrl) ? [$attachmentUrl] : []);
                        @endphp
                        @if(!empty($emailAttUrls))
                        <tr>
                            <td style="padding: 8px 0; font-size: 13px; color: #6b7280; font-weight: 700; vertical-align: top;">Attachment(s) / Receipt(s):</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #fe5f04; font-weight: 700;">
                                @foreach($emailAttUrls as $idx => $url)
                                    <div style="margin-bottom: 4px;">
                                        <a href="{{ $url }}" target="_blank" style="color: #fe5f04; text-decoration: underline; font-weight: 700;">
                                            📎 {{ count($emailAttUrls) > 1 ? ('View Receipt #' . ($idx + 1)) : 'View Receipt / Bill' }}
                                        </a>
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>

                <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #f3f4f6; text-align: center; font-size: 12px; color: #9ca3af; line-height: 1.6;">
                    This is an automated notification from <strong>myAgenci.ai HRMS Workflow System</strong>.<br>
                    Please do not reply directly to this email.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
