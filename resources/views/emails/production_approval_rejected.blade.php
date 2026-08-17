<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Approval Rejected</title>
</head>
<body style="font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #fef2f2; margin: 0; padding: 24px; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <div style="max-width: 680px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(220, 38, 38, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.04); border: 1px solid #fee2e2;">
        
        {{-- Header (Red/Crimson Theme) --}}
        <div style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); padding: 28px 36px; color: #ffffff;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle;">
                        <h2 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.02em;">❌ Production Approval Rejected</h2>
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
                Hello {{ $salesPerson?->name ? $salesPerson->name . ' & Customer Success Team' : 'Team' }},
            </p>
            <p style="font-size: 14px; color: #475569; line-height: 1.65; margin-bottom: 24px;">
                The production approval request for <strong>{{ $initiation->product_name ?: $leadProduct?->product_name }}</strong> has been <strong style="color: #dc2626; font-weight: 800;">REJECTED</strong> by <strong>{{ $reviewedBy?->name ?? 'Management Reviewer' }}</strong>. Below are the complete review details and rejection reason:
            </p>

            {{-- Rejection Remarks Highlight Box --}}
            @if($initiation->production_approval_remarks)
            <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-left: 4px solid #e11d48; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 8px; font-size: 12px; font-weight: 800; color: #9f1239; text-transform: uppercase; letter-spacing: 0.08em;">⚠️ Rejection Remarks / Reason</h4>
                <p style="margin: 0; font-size: 14px; color: #881337; line-height: 1.6; font-weight: 600;">
                    "{{ $initiation->production_approval_remarks }}"
                </p>
            </div>
            @endif

            {{-- Lead Summary Card --}}
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 14px; font-size: 12px; font-weight: 800; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.08em;">📋 Lead Information</h4>
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
            <div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 14px; font-size: 12px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.08em;">📦 Item Details</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 5px 0; color: #991b1b; font-weight: 600; width: 150px;">Product Name:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 800; font-size: 15px;">{{ $initiation->product_name ?: $leadProduct?->product_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #991b1b; font-weight: 600;">Department:</td>
                        <td style="padding: 5px 0; color: #dc2626; font-weight: 700;">{{ $departmentName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #991b1b; font-weight: 600;">Rejected By:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">{{ $reviewedBy?->name ?? 'Reviewer' }} ({{ now()->format('d M Y, h:i A') }})</td>
                    </tr>
                </table>
            </div>

            {{-- Action Button --}}
            <div style="margin-top: 28px; text-align: center;">
                <a href="{{ route('production-approvals.index', ['bucket' => 'rejected']) }}"
                   style="display: inline-block; background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: #ffffff; text-decoration: none; font-weight: 800; font-size: 14px; padding: 13px 32px; border-radius: 10px; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35); transition: all 0.2s ease;">
                    View Rejected Production Items
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div style="background-color: #fff1f2; border-top: 1px solid #fecdd3; padding: 18px 36px; text-align: center; font-size: 12px; color: #9f1239;">
            <p style="margin: 0 0 4px;">Sent to: <strong>customersuccessteam.sts@gmail.com</strong>, <strong>customersuccess@saitechnosolutions.net</strong> &amp; <strong>{{ $salesPerson?->email ?: 'Sales Person' }}</strong></p>
            <p style="margin: 0;">Automated System Notification | {{ config('app.name', 'My Agency') }} CRM</p>
        </div>
    </div>
</body>
</html>
