<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Pending Approval #{{ $quotation->quotation_no }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:'Segoe UI', Arial, sans-serif; color:#1f2937;">
    <div style="padding: 28px 14px;">
        <div style="max-width: 620px; margin: 0 auto;">
            {{-- Header --}}
            <div style="background: linear-gradient(135deg, #fe5f04 0%, #ff8745 100%); border-radius: 20px 20px 0 0; padding: 26px 28px; box-shadow: 0 16px 36px rgba(254, 95, 4, 0.15);">
                <table role="presentation" style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="myAgenci.ai" style="max-width: 150px; height: auto; display: block;">
                        </td>
                        <td style="text-align:right; vertical-align:middle;">
                            <span style="display:inline-block; padding: 5px 12px; border-radius: 999px; background: rgba(255,255,255,0.22); border: 1px solid rgba(255,255,255,0.35); font-size: 11px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px; text-transform: uppercase;">
                                PENDING APPROVAL
                            </span>
                        </td>
                    </tr>
                </table>
                <h2 style="margin: 18px 0 6px; font-size: 22px; font-weight: 800; color: #ffffff; line-height: 1.25;">
                    Quotation Review Required
                </h2>
                <p style="margin: 0; font-size: 13px; color: rgba(255,255,255,0.92); line-height: 1.5;">
                    A new quotation has been prepared and requires HR / Admin approval before executives can view the PDF, download, or email it to the client.
                </p>
            </div>

            {{-- Body --}}
            <div style="background: #ffffff; border: 1px solid #e5e7eb; border-top: 0; border-radius: 0 0 20px 20px; padding: 28px 26px; box-shadow: 0 16px 36px rgba(15, 23, 42, 0.05);">
                <p style="font-size: 15px; margin-top: 0; color: #111827; font-weight: 700;">
                    Hello {{ $recipientName ?? 'HR Team' }},
                </p>
                <p style="font-size: 14px; color: #4b5563; line-height: 1.6; margin-bottom: 20px;">
                    <strong>{{ $quotation->createdBy?->name ?? 'Sales Person' }}</strong> has generated Quotation <strong>#{{ $quotation->quotation_no }}</strong> for <strong>{{ $quotation->lead?->contact_name ?: ($quotation->lead?->company_name ?: 'Customer') }}</strong>. Please review the quotation details below:
                </p>

                {{-- Summary Box --}}
                <div style="background: #fafafa; border: 1px solid #f1f5f9; border-radius: 14px; padding: 18px 20px; margin-bottom: 22px;">
                    <table role="presentation" style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #64748b; font-weight: 700; width: 140px;">Quotation No:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #0f172a; font-weight: 800;">{{ $quotation->quotation_no }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #64748b; font-weight: 700;">Customer / Lead:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #0f172a; font-weight: 700;">
                                {{ $quotation->lead?->contact_name ?: '—' }}
                                @if($quotation->lead?->company_name && $quotation->lead?->company_name !== $quotation->lead?->contact_name)
                                    <span style="color:#64748b; font-weight:500;">({{ $quotation->lead->company_name }})</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #64748b; font-weight: 700;">Prepared By:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #0f172a; font-weight: 700;">
                                {{ $quotation->createdBy?->name ?? '—' }}
                                @if($quotation->createdBy?->email)
                                    <span style="color:#64748b; font-size:12px; font-weight:400;">({{ $quotation->createdBy->email }})</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #64748b; font-weight: 700;">Quotation Date:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #334155;">{{ $quotation->quotation_date ? $quotation->quotation_date->format('d M Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #64748b; font-weight: 700;">Valid Until:</td>
                            <td style="padding: 7px 0; font-size: 14px; color: #334155;">{{ $quotation->valid_until ? $quotation->valid_until->format('d M Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 7px 0; font-size: 13px; color: #64748b; font-weight: 700;">Total Amount:</td>
                            <td style="padding: 7px 0;">
                                <span style="display:inline-block; padding: 5px 14px; background: #fff7ed; color: #ea580c; border: 1px solid #ffedd5; border-radius: 8px; font-size: 16px; font-weight: 800;">
                                    ₹{{ number_format((float) $quotation->total_amount, 2) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- Items Table --}}
                @if($quotation->items->isNotEmpty())
                    <div style="margin-bottom: 24px;">
                        <div style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
                            Line Items ({{ $quotation->items->count() }})
                        </div>
                        <table role="presentation" style="width:100%; border-collapse:collapse; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; font-size: 13px;">
                            <thead>
                                <tr style="background:#f8fafc; text-align:left; color:#64748b; font-weight:700;">
                                    <th style="padding:8px 12px; border-bottom:1px solid #e2e8f0;">Item / Product</th>
                                    <th style="padding:8px 12px; border-bottom:1px solid #e2e8f0; text-align:center;">Qty</th>
                                    <th style="padding:8px 12px; border-bottom:1px solid #e2e8f0; text-align:right;">Unit Price</th>
                                    <th style="padding:8px 12px; border-bottom:1px solid #e2e8f0; text-align:right;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($quotation->items as $item)
                                    @php
                                        $itemName = $item->item_name ?: ($item->product?->product_name ?: $item->product_name);
                                    @endphp
                                    <tr style="border-bottom:1px solid #f1f5f9;">
                                        <td style="padding:8px 12px; color:#1e293b; font-weight:600;">
                                            {{ $itemName }}
                                            @if($item->description)
                                                <div style="font-size:11px; color:#64748b; font-weight:400; margin-top:2px;">{{ Str::limit($item->description, 70) }}</div>
                                            @endif
                                        </td>
                                        <td style="padding:8px 12px; text-align:center; color:#334155;">{{ $item->qty ?: $item->quantity }}</td>
                                        <td style="padding:8px 12px; text-align:right; color:#334155;">₹{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td style="padding:8px 12px; text-align:right; color:#0f172a; font-weight:700;">₹{{ number_format((float) ($item->total ?: $item->total_amount ?: (($item->qty ?: $item->quantity) * $item->unit_price - ($item->discount ?: 0))), 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- Action CTA --}}
                <div style="text-align: center; margin: 28px 0 18px;">
                    <a href="{{ route('quotations.show', $quotation) }}"
                       target="_blank"
                       style="display: inline-block; padding: 13px 32px; background: #fe5f04; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; border-radius: 999px; box-shadow: 0 4px 14px rgba(254, 95, 4, 0.35); text-transform: uppercase; letter-spacing: 0.5px;">
                        Review & Approve Quotation
                    </a>
                </div>

                <p style="text-align: center; margin: 0; font-size: 12px; color: #64748b;">
                    You can also view all pending quotations in the
                    <a href="{{ route('quotations.index', ['tab' => 'pending']) }}" target="_blank" style="color: #fe5f04; font-weight: 700; text-decoration: underline;">
                        Pending Approval Queue
                    </a>
                </p>

                <hr style="border: none; border-top: 1px solid #f1f5f9; margin: 26px 0 18px;">

                <p style="margin: 0; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                    This is an automated notification from {{ config('mail.from.name') ?: config('app.name') }}. You are receiving this because you hold an authorized approval role.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
