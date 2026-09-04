<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Project TL Allocation</title>
</head>
<body style="font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #f4f6f9; margin: 0; padding: 24px; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <div style="max-width: 680px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04); border: 1px solid #e2e8f0;">
        
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 28px 36px; color: #ffffff;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle;">
                        <h2 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.02em;">🚀 New Project Allocated</h2>
                        <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.95; font-weight: 500;">Lead #{{ $lead?->id }} — {{ $lead?->company_name ?: $lead?->contact_name }}</p>
                    </td>
                    <td style="vertical-align: middle; text-align: right;">
                        <span style="background: rgba(255,255,255,0.2); backdrop-filter: blur(4px); padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; border: 1px solid rgba(255,255,255,0.3);">
                            {{ $departmentName }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Content Body --}}
        <div style="padding: 32px 36px;">
            <p style="font-size: 15px; margin-top: 0; color: #0f172a; font-weight: 600;">
                Hello Team Lead,
            </p>
            <p style="font-size: 14px; color: #475569; line-height: 1.65; margin-bottom: 24px;">
                You have been allocated as the <strong style="color: #1e40af; font-weight: 800;">Team Lead (TL)</strong> for the project <strong>{{ $initiation->product_name ?: $leadProduct?->product_name }}</strong> by <strong>{{ $allocatedBy?->name ?? 'Management' }}</strong>. Below are the complete project &amp; lead details:
            </p>

            {{-- Lead Summary Card --}}
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 14px; font-size: 12px; font-weight: 800; color: #1e40af; text-transform: uppercase; letter-spacing: 0.08em;">📋 Lead Information</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600; width: 150px;">Lead ID:</td>
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
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Email:</td>
                        <td style="padding: 5px 0; color: #0f172a;">{{ $lead?->email ?: 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #64748b; font-weight: 600;">Branch:</td>
                        <td style="padding: 5px 0; color: #0f172a;">{{ $lead?->branch?->name ?: 'N/A' }}</td>
                    </tr>
                </table>
            </div>

            {{-- Project Details Card --}}
            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 14px; font-size: 12px; font-weight: 800; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.08em;">📦 Project &amp; Work Details</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <tr>
                        <td style="padding: 5px 0; color: #1e40af; font-weight: 600; width: 150px;">Product Name:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 800; font-size: 15px;">{{ $initiation->product_name ?: $leadProduct?->product_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #1e40af; font-weight: 600;">Department:</td>
                        <td style="padding: 5px 0; color: #1d4ed8; font-weight: 700;">{{ $departmentName }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 5px 0; color: #1e40af; font-weight: 600;">Working Days:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">{{ $initiation->total_working_days ?: 1 }} Days</td>
                    </tr>
                    @if($initiation->project_delivery_date)
                    <tr>
                        <td style="padding: 5px 0; color: #1e40af; font-weight: 600;">Delivery Date:</td>
                        <td style="padding: 5px 0; color: #15803d; font-weight: 800;">{{ $initiation->project_delivery_date?->format('d M Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 5px 0; color: #1e40af; font-weight: 600;">Allocated By:</td>
                        <td style="padding: 5px 0; color: #0f172a; font-weight: 700;">{{ $allocatedBy?->name }} ({{ now()->format('d M Y, h:i A') }})</td>
                    </tr>
                    @if($initiation->requirements)
                    <tr>
                        <td style="padding: 5px 0; color: #1e40af; font-weight: 600; vertical-align: top;">Requirements:</td>
                        <td style="padding: 5px 0; color: #1e293b; line-height: 1.55;">{{ $initiation->requirements }}</td>
                    </tr>
                    @endif
                </table>
            </div>

            {{-- Custom Form Data if available --}}
            @if(!empty($initiation->custom_form_data) && is_array($initiation->custom_form_data))
            <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
                <h4 style="margin: 0 0 14px; font-size: 12px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 0.08em;">⚙️ Custom Form Specifications</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    @foreach($initiation->custom_form_data as $entry)
                        @php
                            $label = $entry['label'] ?? ($entry['field_name'] ?? '');
                            $val = $entry['value'] ?? '';
                        @endphp
                        @continue(!$label)
                        <tr>
                            <td style="padding: 6px 0; color: #64748b; font-weight: 600; width: 180px;">{{ $label }}:</td>
                            <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">
                                @if(is_array($val))
                                    {{ implode(', ', $val) }}
                                @elseif(is_bool($val))
                                    {{ $val ? 'Yes' : 'No' }}
                                @else
                                    {{ $val }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            @endif

        </div>

        {{-- Footer --}}
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 36px; text-align: center; font-size: 12px; color: #94a3b8;">
            <p style="margin: 0 0 4px;">Sent to: <strong>{{ $allocatedTls->pluck('name')->implode(', ') }}</strong></p>
            <p style="margin: 0;">Automated Project System Notification | {{ config('app.name', 'My Agency') }} CRM</p>
        </div>
    </div>
</body>
</html>
