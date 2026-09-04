<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Approval Approved</title>
</head>
<body style="font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f4f6f9; margin: 0; padding: 24px; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <div style="max-width: 680px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0;">
        
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 28px 36px; color: #ffffff;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle;">
                        <h2 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.02em;">✓ Production Approved</h2>
                        <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.95; font-weight: 500;">Lead #{{ $lead?->id }} — {{ $lead?->company_name ?: $lead?->contact_name }}</p>
                    </td>
                    <td style="vertical-align: middle; text-align: right;">
                        <span style="background: rgba(255,255,255,0.22); backdrop-filter: blur(4px); padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; border: 1px solid rgba(255,255,255,0.3);">
                            {{ $departmentName }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Content Body --}}
        <div style="padding: 32px 36px;">
            <p style="font-size: 15px; margin-top: 0; color: #0f172a; font-weight: 600;">
                Hello {{ $salesPerson?->name ? $salesPerson->name . ' & Team' : 'Team' }},
            </p>
            <p style="font-size: 14px; color: #475569; line-height: 1.65; margin-bottom: 24px;">
                Great news! The production approval request for <strong>{{ $initiation->product_name ?: $leadProduct?->product_name }}</strong> has been <strong style="color: #059669; font-weight: 800;">APPROVED</strong> by <strong>{{ $reviewedBy?->name ?? 'Management Reviewer' }}</strong>. Below are the complete review details:
            </p>

            {{-- Lead Summary Card --}}
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 14px; font-size: 12px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.08em;">📋 Lead Information</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600; width: 150px;">Lead Reference:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 800;">#{{ $lead?->id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Client / Company:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">{{ $lead?->company_name ?: $lead?->contact_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Contact Person:</td>
                        <td style="padding: 5px 0; color: #0f172a;">{{ $lead?->contact_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Mobile Number:</td>
                        <td style="padding: 5px 0; color: #0f172a;">{{ $lead?->mobile_number ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Lead Sales Person:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">{{ $salesPerson?->name ?: 'N/A' }} {{ $salesPerson?->email ? '(' . $salesPerson->email . ')' : '' }}</td>
                    </tr>
                </table>
            </div>

            {{-- Production Approval Details Card --}}
            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 14px; font-size: 12px; font-weight: 800; color: #15803d; text-transform: uppercase; letter-spacing: 0.08em;">📦 Production Approval Details</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 5px 0; color: #166534; font-weight: 600; width: 150px;">Product Name:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 800; font-size: 15px;">{{ $initiation->product_name ?: $leadProduct?->product_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #166534; font-weight: 600;">Department:</td>
                        <td style="padding: 5px 0; color: #047857; font-weight: 700;">{{ $departmentName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #166534; font-weight: 600;">Approved By:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">{{ $reviewedBy?->name ?? 'Reviewer' }} ({{ now()->format('d M Y, h:i A') }})</td>
                    </tr>
                    @if($initiation->production_approval_remarks)
                    <tr>
                        <td style="padding: 5px 0; color: #166534; font-weight: 600; vertical-align: top;">Approval Remarks:</td>
                        <td style="padding: 5px 0; color: #1e293b; line-height: 1.55; font-weight: 500;">{{ $initiation->production_approval_remarks }}</td>
                    </tr>
                    @endif
                    @if($initiation->lead_budget_amount)
                    <tr>
                        <td style="padding: 5px 0; color: #166534; font-weight: 600;">Approved Budget:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 800; font-size: 14px;">₹{{ number_format((float) $initiation->lead_budget_amount, 2) }} <span style="font-weight: 600; font-size: 12px; color: #64748b;">({{ $initiation->budget_amount_type ?: 'Standard' }})</span></td>
                    </tr>
                    @endif
                </table>
            </div>

        </div>

        {{-- Footer --}}
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 36px; text-align: center; font-size: 12px; color: #94a3b8;">
            <p style="margin: 0 0 4px;">Sent to: <strong>{{ $salesPerson?->email ?: 'Sales Team' }}</strong> &amp; <strong>{{ $departmentEmail ?? 'projects@saitechnosolutions.net' }}</strong></p>
            <p style="margin: 0;">Automated System Notification | {{ config('app.name', 'My Agency') }} CRM</p>
        </div>
    </div>
</body>
</html>
