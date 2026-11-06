<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation #{{ $quotation->quotation_no }} Approved</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:'Segoe UI', Arial, sans-serif; color:#1f2937;">
    <div style="padding: 28px 14px;">
        <div style="max-width: 620px; margin: 0 auto;">
            {{-- Header --}}
            <div style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%); border-radius: 20px 20px 0 0; padding: 26px 28px; box-shadow: 0 16px 36px rgba(22, 163, 74, 0.15);">
                <table role="presentation" style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="myAgenci.ai" style="max-width: 150px; height: auto; display: block;">
                        </td>
                        <td style="text-align:right; vertical-align:middle;">
                            <span style="display:inline-block; padding: 5px 12px; border-radius: 999px; background: rgba(255,255,255,0.22); border: 1px solid rgba(255,255,255,0.35); font-size: 11px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase;">
                                ✓ APPROVED
                            </span>
                        </td>
                    </tr>
                </table>
                <h2 style="margin: 18px 0 6px; font-size: 22px; font-weight: 800; color: #ffffff; line-height: 1.25;">
                    Quotation Approved!
                </h2>
                <p style="margin: 0; font-size: 13px; color: rgba(255,255,255,0.92); line-height: 1.5;">
                    Your quotation has been reviewed and approved. You can now download the PDF, print, or email it to the customer.
                </p>
            </div>

            {{-- Body --}}
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-top: 0; border-radius: 0 0 20px 20px; padding: 28px 26px; box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);">
                <p style="font-size: 15px; margin-top: 0; color: #111827; font-weight: 700;">
                    Hello {{ $recipientName ?? 'Sales Person' }},
                </p>
                <p style="font-size: 14px; color: #4b5563; line-height: 1.6; margin-bottom: 20px;">
                    Great news! Quotation <strong>#{{ $quotation->quotation_no }}</strong> for <strong>{{ $quotation->lead?->contact_name ?: ($quotation->lead?->company_name ?: 'Customer') }}</strong> has been approved by <strong>{{ $quotation->approver?->name ?? 'Admin / HR' }}</strong>.
                </p>

                {{-- Summary Box --}}
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 18px 20px; margin-bottom: 22px;">
                    <table role="presentation" style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #166534; font-weight: 700; width: 140px;">Quotation No:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #0f172a; font-weight: 800;">{{ $quotation->quotation_no }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #166534; font-weight: 700;">Customer / Lead:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #0f172a; font-weight: 700;">
                                {{ $quotation->lead?->contact_name ?: '—' }}
                                @if($quotation->lead?->company_name && $quotation->lead?->company_name !== $quotation->lead?->contact_name)
                                    <span style="color:#64748b; font-weight:500;">({{ $quotation->lead->company_name }})</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #166534; font-weight: 700;">Approved By:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #15803d; font-weight: 800;">
                                {{ $quotation->approver?->name ?? 'Admin / HR' }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #166534; font-weight: 700;">Approved Date:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #334155;">
                                {{ $quotation->approved_at ? $quotation->approved_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #166534; font-weight: 700;">Total Amount:</td>
                            <td style="padding: 7px 0;">
                                <span style="display:inline-block; padding: 5px 14px; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 8px; font-size: 16px; font-weight: 800;">
                                    ₹{{ number_format((float) $quotation->total_amount, 2) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- Action CTA Buttons --}}
                <div style="text-align: center; margin: 28px 0 20px;">
                    <a href="{{ route('quotations.show', $quotation) }}"
                       target="_blank"
                       style="display: inline-block; padding: 12px 26px; background: #16a34a; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; border-radius: 999px; box-shadow: 0 4px 14px rgba(22, 163, 74, 0.35); text-transform: uppercase; letter-spacing: 0.5px; margin-right: 8px;">
                        View Quotation
                    </a>
                    <a href="{{ route('quotation.pdf', $quotation->id) }}"
                       target="_blank"
                       style="display: inline-block; padding: 12px 26px; background: #fe5f04; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; border-radius: 999px; box-shadow: 0 4px 14px rgba(254, 95, 4, 0.35); text-transform: uppercase; letter-spacing: 0.5px;">
                        Download PDF
                    </a>
                </div>

                <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 26px 0 18px;">

                <p style="margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                    This is an automated notification from {{ config('mail.from.name') ?: config('app.name') }}. You are receiving this because you created this quotation.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
