<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $quotation->quotation_no }}</title>
</head>
<body style="margin:0;padding:0;background:#f6f6f6;font-family:Arial,sans-serif;color:#222;">
    <div style="max-width:640px;margin:0 auto;padding:28px 16px;">
        <div style="background:#ffffff;border:1px solid #e6e2e8;border-radius:12px;padding:24px;">
            <p style="margin:0 0 14px;font-size:15px;">Dear {{ $lead->contact_name ?: 'Customer' }},</p>

            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                Please find attached quotation {{ $quotation->quotation_no }} for your review.
            </p>

            <table style="width:100%;border-collapse:collapse;margin:0 0 20px;font-size:14px;">
                <tr>
                    <td style="padding:9px 0;color:#666;border-bottom:1px solid #f0eef2;">Quotation No</td>
                    <td style="padding:9px 0;text-align:right;border-bottom:1px solid #f0eef2;">
                        <strong>{{ $quotation->quotation_no }}</strong>
                    </td>
                </tr>
                <tr>
                    <td style="padding:9px 0;color:#666;border-bottom:1px solid #f0eef2;">Quotation Date</td>
                    <td style="padding:9px 0;text-align:right;border-bottom:1px solid #f0eef2;">
                        {{ $quotation->quotation_date?->format('d M Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:9px 0;color:#666;border-bottom:1px solid #f0eef2;">Valid Until</td>
                    <td style="padding:9px 0;text-align:right;border-bottom:1px solid #f0eef2;">
                        {{ $quotation->valid_until?->format('d M Y') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding:9px 0;color:#666;">Total Amount</td>
                    <td style="padding:9px 0;text-align:right;">
                        <strong>Rs. {{ number_format((float) $quotation->total_amount, 2) }}</strong>
                    </td>
                </tr>
            </table>

            <p style="margin:0 0 18px;font-size:15px;line-height:1.6;">
                Kindly check the attached PDF for complete quotation details.
            </p>

            @php
                $approveActionUrl = $agreeUrl ?? $approveUrl ?? null;
                $rejectActionUrl = $disagreeUrl ?? $rejectUrl ?? null;
            @endphp

            @if(!empty($approveActionUrl) && !empty($rejectActionUrl))
            <div style="margin:24px 0;padding:22px 18px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;text-align:center;">
                <p style="margin:0 0 16px;font-size:15px;font-weight:700;color:#111827;">
                    Please review this quotation and submit your decision:
                </p>
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                    <tr>
                        <td align="center" style="padding:0 8px;">
                            <a href="{{ $approveActionUrl }}" target="_blank" style="display:inline-block;padding:12px 28px;background:#16a34a;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:8px;box-shadow:0 2px 4px rgba(22,163,74,0.25);">
                                &#10003; Approve
                            </a>
                        </td>
                        <td align="center" style="padding:0 8px;">
                            <a href="{{ $rejectActionUrl }}" target="_blank" style="display:inline-block;padding:12px 28px;background:#dc2626;color:#ffffff;text-decoration:none;font-size:14px;font-weight:700;border-radius:8px;box-shadow:0 2px 4px rgba(220,38,38,0.25);">
                                &#10005; Reject
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="margin:14px 0 0;font-size:12px;color:#6b7280;line-height:1.5;">
                    Your response will directly update the quotation status in our system.
                </p>
            </div>
            @endif

            <p style="margin:0;font-size:15px;line-height:1.6;">
                Regards,<br>
                {{ config('mail.from.name') ?: config('app.name') }}
            </p>
        </div>
    </div>
</body>
</html>
