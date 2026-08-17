<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Price Request {{ ucfirst($priceRequest->status) }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333333;">
    <div style="max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
        
        {{-- Header --}}
        <div style="background: {{ $priceRequest->status === 'approved' ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)' : 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)' }}; padding: 24px 30px; color: #ffffff;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 0.5px;">
                Price Request {{ $priceRequest->status === 'approved' ? 'Approved ✓' : 'Rejected ✕' }}
            </h2>
            <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.9;">
                Lead #{{ $priceRequest->lead_id }} - {{ $priceRequest->lead?->company_name ?: $priceRequest->lead?->contact_name }}
            </p>
        </div>

        {{-- Content --}}
        <div style="padding: 28px 30px;">
            <p style="font-size: 15px; margin-top: 0;">Hello {{ $recipientName }},</p>
            <p style="font-size: 14px; color: #4b5563; line-height: 1.6;">
                The price change request for deal <strong>"{{ $priceRequest->deal_name }}"</strong> has been
                <strong style="color: {{ $priceRequest->status === 'approved' ? '#059669' : '#dc2626' }};">{{ strtoupper($priceRequest->status) }}</strong>
                by <strong>{{ $actionBy?->name ?? 'Admin' }}</strong>.
            </p>

            {{-- Summary Card --}}
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 18px 20px; margin: 20px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600; width: 140px;">Lead:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">#{{ $priceRequest->lead_id }} ({{ $priceRequest->lead?->company_name ?: $priceRequest->lead?->contact_name }})</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Deal Name:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">{{ $priceRequest->deal_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Product:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">{{ $priceRequest->product_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Base Price:</td>
                        <td style="padding: 5px 0; color: #64748b; text-decoration: line-through;">₹{{ number_format($priceRequest->original_unit_price, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Requested Price:</td>
                        <td style="padding: 5px 0; color: {{ $priceRequest->status === 'approved' ? '#059669' : '#dc2626' }}; font-weight: 800; font-size: 15px;">
                            ₹{{ number_format($priceRequest->requested_unit_price, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Quantity & Discount:</td>
                        <td style="padding: 5px 0; color: #0f172a;">{{ $priceRequest->quantity }} Qty ({{ number_format($priceRequest->discount_percent, 2) }}% Discount)</td>
                    </tr>
                    @if($priceRequest->remarks)
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Requester Remarks:</td>
                        <td style="padding: 5px 0; color: #4b5563; font-style: italic;">{{ $priceRequest->remarks }}</td>
                    </tr>
                    @endif
                    @if($priceRequest->status === 'rejected' && $priceRequest->rejection_reason)
                    <tr>
                        <td style="padding: 5px 0; color: #dc2626; font-weight: 700;">Rejection Reason:</td>
                        <td style="padding: 5px 0; color: #dc2626; font-weight: 700; background-color: #fef2f2; padding: 6px 10px; border-radius: 6px;">
                            {{ $priceRequest->rejection_reason }}
                        </td>
                    </tr>
                    @endif
                </table>
            </div>

            {{-- Action Button --}}
            <div style="margin-top: 28px; text-align: center;">
                <a href="{{ route('leads.show', $priceRequest->lead_id) }}"
                   style="display: inline-block; background: {{ $priceRequest->status === 'approved' ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)' : 'linear-gradient(135deg, #4b5563 0%, #6b7280 100%)' }}; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
                    View Lead Profile
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 30px; text-align: center; font-size: 12px; color: #94a3b8;">
            <p style="margin: 0;">Automated Notification | {{ config('app.name', 'My Agency') }} CRM</p>
        </div>
    </div>
</body>
</html>
