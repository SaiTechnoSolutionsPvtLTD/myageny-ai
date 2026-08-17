<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OVP Approved</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 20px; color: #333333;">
    <div style="max-width: 680px; margin: 0 auto; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid #e5e7eb;">
        
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 26px 32px; color: #ffffff;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h2 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: 0.5px;">✓ OVP Approved</h2>
                    <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.95;">Lead #{{ $lead?->id }} - {{ $lead?->company_name ?: $lead?->contact_name }}</p>
                </div>
                <div style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap;">
                    {{ $departmentName }}
                </div>
            </div>
        </div>

        {{-- Content --}}
        <div style="padding: 30px;">
            <p style="font-size: 15px; margin-top: 0; color: #111827;">Hello Tamilarasan,</p>
            <p style="font-size: 14px; color: #4b5563; line-height: 1.6; margin-bottom: 24px;">
                An OVP item review has been <strong style="color: #059669;">APPROVED</strong> by <strong>{{ $reviewedBy?->name ?? 'Admin/Reviewer' }}</strong> ({{ $reviewedBy?->email }}). Below are the full review details:
            </p>

            {{-- Lead Info Box --}}
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.5px;">📋 Lead Details</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600; width: 140px;">Lead ID:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">#{{ $lead?->id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Company / Client:</td>
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
                        <td style="padding: 4px 0; color: #64748b; font-weight: 600;">Branch:</td>
                        <td style="padding: 4px 0; color: #0f172a;">{{ $lead?->branch?->name ?: 'N/A' }}</td>
                    </tr>
                </table>
            </div>

            {{-- OVP Review & Product Details Box --}}
            <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 800; color: #15803d; text-transform: uppercase; letter-spacing: 0.5px;">📦 OVP & Product Details</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 4px 0; color: #166534; font-weight: 600; width: 140px;">Product Name:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 800; font-size: 15px;">{{ $initiation->product_name ?: $leadProduct?->product_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #166534; font-weight: 600;">Department:</td>
                        <td style="padding: 4px 0; color: #047857; font-weight: 700;">{{ $departmentName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #166534; font-weight: 600;">Working Days:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">{{ $initiation->total_working_days ?: 1 }} Days</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0; color: #166534; font-weight: 600;">Approved By:</td>
                        <td style="padding: 4px 0; color: #0f172a; font-weight: 700;">{{ $reviewedBy?->name }} ({{ now()->format('d M Y, h:i A') }})</td>
                    </tr>
                    @if($initiation->requirements)
                    <tr>
                        <td style="padding: 4px 0; color: #166534; font-weight: 600; vertical-align: top;">Requirements:</td>
                        <td style="padding: 4px 0; color: #334155; line-height: 1.5;">{{ $initiation->requirements }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            {{-- Custom Form Data if available --}}
            @if(!empty($initiation->custom_form_data) && is_array($initiation->custom_form_data))
            <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 12px; font-size: 13px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">⚙️ OVP Custom Form Data</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    @foreach($initiation->custom_form_data as $key => $val)
                        @continue(is_array($val))
                        <tr>
                            <td style="padding: 5px 0; color: #64748b; font-weight: 600; width: 180px; text-transform: capitalize;">{{ str_replace('_', ' ', $key) }}:</td>
                            <td style="padding: 5px 0; color: #0f172a; font-weight: 600;">{{ is_bool($val) ? ($val ? 'Yes' : 'No') : $val }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            @endif

            {{-- Action Button --}}
            <div style="margin-top: 28px; text-align: center;">
                <a href="{{ route('ovp-module.index', ['bucket' => 'approved']) }}"
                   style="display: inline-block; background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);">
                    View Approved OVP Items
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
