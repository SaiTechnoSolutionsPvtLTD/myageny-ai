<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OVP Rejected Notification</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333333;">
    <div style="max-width: 680px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
        
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); padding: 26px 32px; color: #ffffff;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 0.5px;">❌ OVP Review Rejected</h2>
                    <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.95;">Lead #{{ $lead?->id }} - {{ $lead?->company_name ?: $lead?->contact_name }}</p>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap;">
                    {{ $departmentName }}
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div style="padding: 30px;">
            <p style="font-size: 15px; margin-top: 0; color: #111827;">Hello {{ $assignedUser?->name ?? 'Team Member' }},</p>
            <p style="font-size: 14px; color: #4b5563; line-height: 1.6; margin-bottom: 24px;">
                The OVP review for your assigned lead <strong>#{{ $lead?->id }} ({{ $lead?->company_name ?: $lead?->contact_name }})</strong> has been <strong style="color: #dc2626;">REJECTED</strong> by <strong>{{ $reviewedBy?->name ?? 'OVP Reviewer' }}</strong>.
            </p>

            {{-- Mandatory Rejection Reason Callout --}}
            <div style="background-color: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 8px; font-size: 13px; font-weight: 800; color: #991b1b; text-transform: uppercase; letter-spacing: 0.5px;">⚠️ Rejection Reason / Remarks (Mandatory)</h4>
                <p style="margin: 0; font-size: 14px; color: #7f1d1d; font-weight: 700; line-height: 1.6; white-space: pre-wrap;">{{ $rejectionReason }}</p>
            </div>

            {{-- Lead Summary Box --}}
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 800; color: #334155; text-transform: uppercase; letter-spacing: 0.5px;">📋 Lead Details</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600; width: 140px;">Lead ID:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">#{{ $lead?->id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Client / Company:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">{{ $lead?->company_name ?: $lead?->contact_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Contact Person:</td>
                        <td style="padding: 4px 0; color: #0f172a;">{{ $lead?->contact_name ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Mobile:</td>
                        <td style="padding: 4px 0; color: #0f172a;">{{ $lead?->mobile_number ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Assigned User:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">{{ $assignedUser?->name }} ({{ $assignedUser?->email }})</td>
                    </tr>
                </table>
            </div>

            {{-- Product Details --}}
            <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">📦 Product Details</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600; width: 140px;">Product Name:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 800; font-size: 15px;">{{ $initiation->product_name ?: $leadProduct?->product_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Department:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">{{ $departmentName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Rejected By:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">{{ $reviewedBy?->name }} ({{ now()->format('d M Y, h:i A') }})</td>
                    </tr>
                </table>
            </div>

            {{-- Action Button --}}
            <div style="margin-top: 28px; text-align: center;">
                <a href="{{ url('/leads/' . $leadProduct?->lead_id) }}"
                   style="display: inline-block; background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 8px; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);">
                    View Lead Details
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 32px; text-align: center; font-size: 12px; color: #94a3b8;">
            <p style="margin: 0;">Automated Notification | {{ config('app.name', 'My Agency') }} CRM</p>
        </div>
    </div>
</body>
</html>
