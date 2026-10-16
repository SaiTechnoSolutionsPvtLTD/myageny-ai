<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice #{{ $invoice->invoice_number }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Arial, sans-serif; color:#1e293b; line-height:1.6;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:30px 15px;">
        <tr>
            <td align="center">
                <!-- Email Container -->
                <table width="640" border="0" cellpadding="0" cellspacing="0" style="max-width:640px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.06); border:1px solid #e2e8f0;">
                    
                    <!-- Top Brand Bar -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #fe5f04, #ff8533); padding:24px 32px; color:#ffffff;">
                            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <h1 style="margin:0; font-size:22px; font-weight:800; color:#ffffff; letter-spacing:-0.02em;">
                                            {{ $setting->company_name ?: ($companyName ?? config('app.name')) }}
                                        </h1>
                                        <p style="margin:4px 0 0; font-size:13px; color:#ffedd5; font-weight:500;">
                                            TAX INVOICE &bull; {{ $invoice->invoice_number }}
                                        </p>
                                    </td>
                                    <td align="right" valign="top">
                                        <span style="background:rgba(255,255,255,0.22); color:#ffffff; font-size:12px; font-weight:700; padding:6px 14px; border-radius:20px; display:inline-block; border:1px solid rgba(255,255,255,0.35);">
                                            {{ strtoupper($invoice->status ?? 'ISSUED') }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:32px 32px 24px;">
                            <p style="margin:0 0 16px; font-size:15px; color:#334155;">
                                Dear <strong>{{ $invoice->customer_name ?: 'Valued Customer' }}</strong>,
                            </p>
                            <p style="margin:0 0 24px; font-size:14px; color:#475569; line-height:1.6;">
                                Thank you for your business! Please find below the invoice summary for <strong>#{{ $invoice->invoice_number }}</strong>. A PDF copy has also been attached to this email for your records.
                            </p>

                            <!-- Invoice Details Highlight Card -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:24px; padding:16px 20px;">
                                <tr>
                                    <td width="50%" style="padding:6px 0;">
                                        <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; font-weight:700; display:block;">Invoice Date</span>
                                        <span style="font-size:14px; color:#0f172a; font-weight:600;">
                                            {{ $invoice->invoice_date ? $invoice->invoice_date->format('d M, Y') : now()->format('d M, Y') }}
                                        </span>
                                    </td>
                                    <td width="50%" style="padding:6px 0;">
                                        <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; font-weight:700; display:block;">Due Date</span>
                                        <span style="font-size:14px; color:#dc2626; font-weight:600;">
                                            {{ $invoice->due_date ? $invoice->due_date->format('d M, Y') : 'Due on Receipt' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td width="50%" style="padding:6px 0;">
                                        <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; font-weight:700; display:block;">Billed To</span>
                                        <span style="font-size:13px; color:#0f172a; font-weight:600;">
                                            {{ $invoice->customer_name }}
                                            @if($invoice->company_name) ({{ $invoice->company_name }}) @endif
                                        </span>
                                    </td>
                                    <td width="50%" style="padding:6px 0;">
                                        <span style="font-size:11px; text-transform:uppercase; letter-spacing:0.05em; color:#64748b; font-weight:700; display:block;">Balance Due</span>
                                        <span style="font-size:17px; color:#ea580c; font-weight:800;">
                                            ₹ {{ number_format((float) $invoice->balance_due, 2) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Itemized Table -->
                            <table width="100%" border="0" cellpadding="8" cellspacing="0" style="border-collapse:collapse; margin-bottom:24px; font-size:13px;">
                                <thead>
                                    <tr style="background:#f1f5f9; border-bottom:2px solid #cbd5e1; color:#475569; text-align:left;">
                                        <th style="padding:10px 12px; font-weight:700;">#</th>
                                        <th style="padding:10px 12px; font-weight:700;">Item & Description</th>
                                        <th style="padding:10px 12px; text-align:center; font-weight:700;">Qty</th>
                                        <th style="padding:10px 12px; text-align:right; font-weight:700;">Rate</th>
                                        <th style="padding:10px 12px; text-align:right; font-weight:700;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice->items as $idx => $item)
                                        <tr style="border-bottom:1px solid #e2e8f0;">
                                            <td style="padding:10px 12px; color:#64748b;">{{ $idx + 1 }}</td>
                                            <td style="padding:10px 12px;">
                                                <strong style="color:#0f172a;">{{ $item->item_name }}</strong>
                                                @if($item->description)
                                                    <div style="font-size:11.5px; color:#64748b; margin-top:2px;">{{ $item->description }}</div>
                                                @endif
                                            </td>
                                            <td style="padding:10px 12px; text-align:center; color:#334155;">{{ $item->qty }}</td>
                                            <td style="padding:10px 12px; text-align:right; color:#334155;">₹ {{ number_format((float) $item->rate, 2) }}</td>
                                            <td style="padding:10px 12px; text-align:right; font-weight:600; color:#0f172a;">₹ {{ number_format((float) $item->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <!-- Totals Breakdown -->
                            <table width="100%" border="0" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td width="50%"></td>
                                    <td width="50%">
                                        <table width="100%" border="0" cellpadding="4" cellspacing="0" style="font-size:13px; color:#334155;">
                                            <tr>
                                                <td style="color:#64748b;">Subtotal:</td>
                                                <td align="right" style="font-weight:600;">₹ {{ number_format((float) $invoice->subtotal, 2) }}</td>
                                            </tr>
                                            @if($invoice->total_tax > 0)
                                                @if($invoice->total_cgst > 0)
                                                    <tr>
                                                        <td style="color:#64748b;">CGST:</td>
                                                        <td align="right" style="font-weight:600;">₹ {{ number_format((float) $invoice->total_cgst, 2) }}</td>
                                                    </tr>
                                                @endif
                                                @if($invoice->total_sgst > 0)
                                                    <tr>
                                                        <td style="color:#64748b;">SGST:</td>
                                                        <td align="right" style="font-weight:600;">₹ {{ number_format((float) $invoice->total_sgst, 2) }}</td>
                                                    </tr>
                                                @endif
                                                @if($invoice->total_igst > 0)
                                                    <tr>
                                                        <td style="color:#64748b;">IGST:</td>
                                                        <td align="right" style="font-weight:600;">₹ {{ number_format((float) $invoice->total_igst, 2) }}</td>
                                                    </tr>
                                                @endif
                                            @endif
                                            @if($invoice->round_off != 0)
                                                <tr>
                                                    <td style="color:#64748b;">Round Off:</td>
                                                    <td align="right" style="font-weight:600;">{{ $invoice->round_off > 0 ? '+' : '' }}₹ {{ number_format((float) $invoice->round_off, 2) }}</td>
                                                </tr>
                                            @endif
                                            <tr style="border-top:2px solid #cbd5e1; font-size:15px; font-weight:800; color:#0f172a;">
                                                <td style="padding-top:8px;">Total Amount:</td>
                                                <td align="right" style="padding-top:8px; color:#fe5f04;">₹ {{ number_format((float) $invoice->total_amount, 2) }}</td>
                                            </tr>
                                            @if((float)$invoice->total_credited > 0)
                                                <tr style="font-size:13px; color:#b91c1c;">
                                                    <td>Credits Applied (CN):</td>
                                                    <td align="right" style="font-weight:700;">(-) ₹ {{ number_format((float) $invoice->total_credited, 2) }}</td>
                                                </tr>
                                            @endif
                                            @if($invoice->paid_amount > 0)
                                                <tr style="font-size:13px; color:#16a34a;">
                                                    <td>Amount Paid:</td>
                                                    <td align="right" style="font-weight:700;">₹ {{ number_format((float) $invoice->paid_amount, 2) }}</td>
                                                </tr>
                                            @endif
                                            @if((float)$invoice->total_tds > 0)
                                                <tr style="font-size:13px; color:#b91c1c;">
                                                    <td>Amount Withheld:</td>
                                                    <td align="right" style="font-weight:700;">(-) ₹ {{ number_format((float) $invoice->total_tds, 2) }}</td>
                                                </tr>
                                            @endif
                                            <tr style="font-size:14px; font-weight:800; color:#dc2626;">
                                                <td>Balance Due:</td>
                                                <td align="right">₹ {{ number_format((float) $invoice->balance_due, 2) }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Bank Details / Payment Modes (If configured) -->
                            @if(!empty($setting->bank_name) && !empty($setting->account_number))
                                <div style="background:#f8fafc; border-left:4px solid #fe5f04; padding:14px 18px; border-radius:6px; margin-bottom:24px;">
                                    <h4 style="margin:0 0 8px; font-size:13px; font-weight:700; color:#0f172a;">
                                        Bank Account Details for Remittance
                                    </h4>
                                    <table width="100%" border="0" cellpadding="2" cellspacing="0" style="font-size:12px; color:#475569;">
                                        <tr>
                                            <td width="130" style="color:#64748b;">Bank Name:</td>
                                            <td><strong>{{ $setting->bank_name }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td style="color:#64748b;">Account Holder:</td>
                                            <td><strong>{{ $setting->account_name ?: $setting->company_name }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td style="color:#64748b;">Account Number:</td>
                                            <td><strong style="letter-spacing:0.04em;">{{ $setting->account_number }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td style="color:#64748b;">IFSC Code:</td>
                                            <td><strong>{{ $setting->ifsc_code }}</strong></td>
                                        </tr>
                                        @if($setting->upi_id)
                                            <tr>
                                                <td style="color:#64748b;">UPI ID:</td>
                                                <td><strong style="color:#ea580c;">{{ $setting->upi_id }}</strong></td>
                                            </tr>
                                        @endif
                                    </table>
                                </div>
                            @endif

                            <!-- View Invoice CTA Button -->
                            <div style="text-align:center; margin:30px 0 10px;">
                                <a href="{{ route('accounts.sales.invoices.show', $invoice->id) }}" style="background:linear-gradient(135deg, #fe5f04, #ff7c30); color:#ffffff; font-size:14px; font-weight:700; padding:12px 28px; border-radius:8px; text-decoration:none; display:inline-block; box-shadow:0 4px 12px rgba(254,95,4,0.35);">
                                    View Invoice Online &rarr;
                                </a>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:20px 32px; text-align:center; font-size:12px; color:#64748b;">
                            <p style="margin:0 0 4px;">
                                {{ $setting->company_name ?: ($companyName ?? config('app.name')) }}
                                @if($setting->company_phone) &bull; {{ $setting->company_phone }} @endif
                                @if($setting->company_email) &bull; {{ $setting->company_email }} @endif
                            </p>
                            @if($setting->company_gstin)
                                <p style="margin:0; font-size:11px; color:#94a3b8;">
                                    GSTIN: {{ $setting->company_gstin }}
                                </p>
                            @endif
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>