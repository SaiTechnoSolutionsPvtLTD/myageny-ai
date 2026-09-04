<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Price Request</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333333;">
    <div style="max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #fe5f04 0%, #ff8c3a 100%); padding: 24px 30px; color: #ffffff;">
            <h2 style="margin: 0; font-size: 20px; font-weight: 800; letter-spacing: 0.5px;">New Price Change Request</h2>
            <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.9;">Lead #{{ $lead?->id }} - {{ $lead?->company_name ?: $lead?->contact_name }}</p>
        </div>

        {{-- Content --}}
        <div style="padding: 28px 30px;">
            <p style="font-size: 15px; margin-top: 0;">Hello Tamilarasan,</p>
            <p style="font-size: 14px; color: #4b5563; line-height: 1.6;">
                A new price change request has been submitted by <strong>{{ $requestedBy?->name }}</strong> ({{ $requestedBy?->email }}) for deal <strong>"{{ $dealName }}"</strong>.
            </p>

            {{-- Lead Summary Box --}}
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; margin: 20px 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600; width: 130px;">Lead ID:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">#{{ $lead?->id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Company / Name:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">{{ $lead?->company_name ?: $lead?->contact_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Mobile:</td>
                        <td style="padding: 4px 0; color: #0f172a;">{{ $lead?->mobile_number ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Deal Name:</td>
                        <td style="padding: 4px 0; color: #fe5f04; font-weight: 700;">{{ $dealName }}</td>
                    </tr>
                </table>
            </div>

            {{-- Products Table --}}
            <h4 style="font-size: 14px; margin: 24px 0 12px; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;">Requested Items</h4>
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; border: 1px solid #e2e8f0;">
                <thead>
                    <tr style="background-color: #f1f5f9; color: #475569;">
                        <th style="padding: 10px 12px; border-bottom: 1px solid #cbd5e1;">Product</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #cbd5e1;">Base Price</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #cbd5e1;">Asked Price</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #cbd5e1;">Qty</th>
                        <th style="padding: 10px 12px; border-bottom: 1px solid #cbd5e1;">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($priceRequests as $req)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 12px; font-weight: 700; color: #0f172a;">{{ $req->product_name }}</td>
                            <td style="padding: 10px 12px; color: #64748b; text-decoration: line-through;">₹{{ number_format($req->original_unit_price, 2) }}</td>
                            <td style="padding: 10px 12px; font-weight: 700; color: #16a34a;">₹{{ number_format($req->requested_unit_price, 2) }}</td>
                            <td style="padding: 10px 12px;">{{ $req->quantity }}</td>
                            <td style="padding: 10px 12px; color: #64748b; font-style: italic;">{{ $req->remarks ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>

        {{-- Footer --}}
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 30px; text-align: center; font-size: 12px; color: #94a3b8;">
            <p style="margin: 0;">Automated Notification | {{ config('app.name', 'My Agency') }} CRM</p>
        </div>
    </div>
</body>
</html>
