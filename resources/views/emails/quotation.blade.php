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

            <p style="margin:0;font-size:15px;line-height:1.6;">
                Regards,<br>
                {{ config('mail.from.name') ?: config('app.name') }}
            </p>
        </div>
    </div>
</body>
</html>
