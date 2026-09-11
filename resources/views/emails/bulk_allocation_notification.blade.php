<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Project Assignment</title>
</head>
<body style="font-family: 'Inter', system-ui, -apple-system, sans-serif; background-color: #fff7ed; margin: 0; padding: 24px; color: #1e293b; -webkit-font-smoothing: antialiased;">
    <div style="max-width: 680px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(234, 88, 12, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.04); border: 1px solid #ffedd5;">
        
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #ea580c 0%, #f97316 100%); padding: 28px 36px; color: #ffffff;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="vertical-align: middle;">
                        <h2 style="margin: 0; font-size: 22px; font-weight: 800; letter-spacing: -0.02em;">🚀 Bulk Project Assignment</h2>
                        <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.95; font-weight: 500;">{{ $allocatedCount }} project(s) allocated to you</p>
                    </td>
                    <td style="vertical-align: middle; text-align: right;">
                        <span style="background: rgba(255,255,255,0.22); backdrop-filter: blur(4px); padding: 6px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; border: 1px solid rgba(255,255,255,0.3);">
                            {{ $allocatedCount }} Projects
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Content Body --}}
        <div style="padding: 32px 36px;">
            <p style="font-size: 15px; margin-top: 0; color: #0f172a; font-weight: 600;">
                Hello {{ $assignedUser->name }},
            </p>
            <p style="font-size: 14px; color: #475569; line-height: 1.65; margin-bottom: 24px;">
                You have been assigned <strong>{{ $allocatedCount }}</strong> project(s) by <strong>{{ $allocatedBy?->name ?? 'Admin' }}</strong>. Below is the list of allocated projects:
            </p>

            @if(!empty($notes))
            <div style="background-color: #fefce8; border: 1px solid #fef08a; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #854d0e;">
                <strong>Notes:</strong> {{ $notes }}
            </div>
            @endif

            {{-- Projects Table --}}
            <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <th style="padding: 10px 14px; text-align: left; color: #64748b; font-weight: 700;">#</th>
                            <th style="padding: 10px 14px; text-align: left; color: #64748b; font-weight: 700;">Project / Product</th>
                            <th style="padding: 10px 14px; text-align: left; color: #64748b; font-weight: 700;">Client / Company</th>
                            <th style="padding: 10px 14px; text-align: left; color: #64748b; font-weight: 700;">Lead ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projects as $index => $proj)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 14px; color: #94a3b8; font-weight: 600;">{{ $index + 1 }}</td>
                            <td style="padding: 10px 14px; color: #0f172a; font-weight: 700;">{{ $proj->product_name ?: ($proj->leadProduct?->product_name ?: 'Product') }}</td>
                            <td style="padding: 10px 14px; color: #334155;">{{ $proj->company_name ?: ($proj->lead?->company_name ?: ($proj->client_name ?: 'No Company')) }}</td>
                            <td style="padding: 10px 14px; color: #ea580c; font-weight: 700;">#{{ $proj->lead_id }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- CTA Button --}}
            <div style="text-align: center; margin: 30px 0 10px;">
                <a href="{{ url('/projects-details') }}" style="display: inline-block; background: linear-gradient(135deg, #ea580c 0%, #f97316 100%); color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 10px; font-weight: 700; font-size: 14px; box-shadow: 0 4px 12px rgba(234, 88, 12, 0.25);">
                    View My Allocated Projects &rarr;
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div style="background-color: #f8fafc; padding: 18px 36px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8;">
            This is an automated notification from {{ config('app.name', 'MyAgency') }}. Please do not reply directly to this email.
        </div>
    </div>
</body>
</html>
