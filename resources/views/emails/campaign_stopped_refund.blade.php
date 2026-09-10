<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Stopped & Refund Notification</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2937;">
    @php
        $clientName = $campaign->lead?->company_name ?: ($campaign->lead?->contact_name ?: 'Client');
        $contactPerson = $campaign->lead?->contact_name ?: '—';
        $contactMobile = $campaign->lead?->mobile_number ?: '—';
        $stoppedBy = $stoppedByUser?->name ?? (auth()->user()?->name ?? 'Digital Marketing Team');
        $stopDateFormatted = $campaign->stop_date ? \Carbon\Carbon::parse($campaign->stop_date)->format('d M Y') : now()->format('d M Y');
        $runDays = $campaign->calculateRunDays();
        $dailyBudget = $campaign->calculateDailyBudget();
    @endphp

    <div style="padding:28px 14px;">
        <div style="max-width:680px;margin:0 auto;">
            <div style="background:linear-gradient(135deg,#dc2626 0%,#ea580c 100%);border-radius:24px 24px 0 0;padding:28px 28px 22px;box-shadow:0 20px 45px rgba(18,18,18,.08);">
                <table role="presentation" style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="vertical-align:middle;">
                            <span style="font-size:20px;font-weight:900;color:#fff;letter-spacing:-0.5px;">MYAGENCI</span>
                        </td>
                        <td style="text-align:right;vertical-align:top;">
                            <span style="display:inline-block;padding:6px 14px;border-radius:999px;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3);font-size:12px;font-weight:800;letter-spacing:.04em;color:#fff;">
                                CAMPAIGN STOPPED
                            </span>
                        </td>
                    </tr>
                </table>
                <div style="margin-top:20px;font-size:24px;line-height:1.2;font-weight:800;color:#fff;">
                    ⚠️ Campaign Stopped & Refund Notice
                </div>
                <div style="margin-top:8px;font-size:14px;line-height:1.6;color:rgba(255,255,255,.92);">
                    Campaign <strong>{{ $campaign->campaign_name }}</strong> for client <strong>{{ $clientName }}</strong> has been stopped. Details are provided below for review & refund processing.
                </div>
            </div>

            <div style="background:#ffffff;border:1px solid #ece5de;border-top:0;border-radius:0 0 24px 24px;padding:28px;box-shadow:0 20px 45px rgba(18,18,18,.06);">
                <div style="font-size:15px;line-height:1.7;color:#374151;">
                    Dear <strong>Chief Business Officer</strong>,
                </div>

                <div style="margin-top:10px;font-size:14px;line-height:1.6;color:#4b5563;">
                    A digital marketing campaign has been stopped and marked for refund review. Please review the campaign run details and budget calculations below:
                </div>

                <div style="margin-top:20px;display:table;width:100%;table-layout:fixed;border-collapse:separate;border-spacing:8px 0;">
                    <div style="display:table-cell;background:#fff5f5;border:1px solid #fecaca;border-radius:14px;padding:14px;text-align:center;">
                        <div style="font-size:11px;font-weight:800;color:#991b1b;text-transform:uppercase;letter-spacing:.05em;">Days Run</div>
                        <div style="font-size:22px;font-weight:900;color:#dc2626;margin-top:4px;">{{ $runDays }} {{ $runDays == 1 ? 'Day' : 'Days' }}</div>
                    </div>
                    <div style="display:table-cell;background:#fffaf5;border:1px solid #fed7aa;border-radius:14px;padding:14px;text-align:center;">
                        <div style="font-size:11px;font-weight:800;color:#9a3412;text-transform:uppercase;letter-spacing:.05em;">Daily Budget</div>
                        <div style="font-size:22px;font-weight:900;color:#ea580c;margin-top:4px;">₹{{ number_format($dailyBudget, 2) }}</div>
                    </div>
                    <div style="display:table-cell;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:14px;padding:14px;text-align:center;">
                        <div style="font-size:11px;font-weight:800;color:#166534;text-transform:uppercase;letter-spacing:.05em;">Refund Amount</div>
                        <div style="font-size:22px;font-weight:900;color:#15803d;margin-top:4px;">
                            @if($campaign->refund_amount && (float)$campaign->refund_amount > 0)
                                ₹{{ number_format((float)$campaign->refund_amount, 2) }}
                            @else
                                <span style="font-size:15px;color:#64748b;">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div style="margin-top:22px;padding:20px;border-radius:16px;background:#f8fafc;border:1px solid #e2e8f0;">
                    <div style="font-size:12px;color:#0f172a;font-weight:800;text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;border-bottom:1px solid #e2e8f0;padding-bottom:8px;">
                        📋 Campaign & Client Summary
                    </div>
                    <table role="presentation" style="width:100%;border-collapse:collapse;font-size:13px;">
                        <tr>
                            <td style="padding:6px 0;color:#64748b;width:38%;">Client / Company:</td>
                            <td style="padding:6px 0;font-weight:700;color:#0f172a;">{{ $clientName }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Contact Person:</td>
                            <td style="padding:6px 0;font-weight:600;color:#334155;">{{ $contactPerson }} ({{ $contactMobile }})</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Campaign Name:</td>
                            <td style="padding:6px 0;font-weight:800;color:#0f172a;">{{ $campaign->campaign_name }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Ad Account Name:</td>
                            <td style="padding:6px 0;font-weight:700;color:#2563eb;">{{ $campaign->ad_account_name ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Platform:</td>
                            <td style="padding:6px 0;font-weight:600;color:#334155;">{{ $campaign->platform ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Campaign Budget:</td>
                            <td style="padding:6px 0;font-weight:700;color:#0f172a;">
                                @if($campaign->budget_amount)
                                    ₹{{ number_format((float)$campaign->budget_amount, 2) }} <span style="font-size:11px;font-weight:500;color:#64748b;">({{ $campaign->budget_type ?: 'Total' }})</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Active Period:</td>
                            <td style="padding:6px 0;font-weight:600;color:#334155;">
                                {{ $campaign->start_date ? \Carbon\Carbon::parse($campaign->start_date)->format('d M Y') : '—' }}
                                →
                                {{ $stopDateFormatted }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Paused Duration:</td>
                            <td style="padding:6px 0;font-weight:600;color:#b45309;">
                                {{ (int) $campaign->total_paused_days }} {{ Str::plural('day', (int) $campaign->total_paused_days) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Stop Date:</td>
                            <td style="padding:6px 0;font-weight:700;color:#dc2626;">{{ $stopDateFormatted }}</td>
                        </tr>
                        <tr>
                            <td style="padding:6px 0;color:#64748b;">Stopped By:</td>
                            <td style="padding:6px 0;font-weight:600;color:#0f172a;">{{ $stoppedBy }}</td>
                        </tr>
                    </table>
                </div>

                @if($campaign->stop_reason || $campaign->remarks)
                    <div style="margin-top:18px;padding:16px 18px;border-radius:14px;background:#fef2f2;border:1px solid #fee2e2;">
                        <div style="font-size:11px;color:#991b1b;font-weight:800;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px;">
                            🛑 Reason for Stopping & Refund Remarks:
                        </div>
                        <div style="font-size:13px;color:#7f1d1d;line-height:1.5;white-space:pre-line;">
                            {{ $campaign->stop_reason ?: $campaign->remarks }}
                        </div>
                    </div>
                @endif

                <div style="margin-top:28px;text-align:center;">
                    <a href="{{ url('/projects/campaigns/' . $campaign->lead_id) }}" style="display:inline-block;padding:12px 28px;border-radius:10px;background:#ea580c;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;box-shadow:0 4px 12px rgba(234,88,12,0.3);">
                        View Campaign in Production &rarr;
                    </a>
                </div>

                <div style="margin-top:28px;padding-top:20px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;text-align:center;">
                    Automated notification sent by Myagenci System.
                </div>
            </div>
        </div>
    </div>
</body>
</html>