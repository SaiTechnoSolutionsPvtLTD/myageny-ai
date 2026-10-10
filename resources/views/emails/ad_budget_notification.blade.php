<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Budget Notification</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; color: #334155; }
        .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .email-header { background: linear-gradient(135deg, #fe5f04, #ff7c30); padding: 24px; text-align: center; color: #ffffff; }
        .email-header h1 { margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        .email-header p { margin: 6px 0 0; font-size: 13px; opacity: 0.9; }
        .email-body { padding: 28px 24px; }
        .greeting { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 16px; }
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 999px; font-size: 12px; font-weight: 800; text-transform: uppercase; margin-bottom: 16px; }
        .badge-pending { background-color: #fef3c7; color: #b45309; }
        .badge-approved { background-color: #dcfce7; color: #15803d; }
        .badge-rejected { background-color: #fee2e2; color: #b91c1c; }
        
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; background: #f8fafc; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
        .info-table th, .info-table td { padding: 12px 16px; font-size: 13px; text-align: left; }
        .info-table th { background: #f1f5f9; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 11px; width: 35%; border-bottom: 1px solid #e2e8f0; }
        .info-table td { color: #0f172a; border-bottom: 1px solid #e2e8f0; font-weight: 600; }
        .info-table tr:last-child th, .info-table tr:last-child td { border-bottom: none; }

        .date-chip { display: inline-block; padding: 3px 8px; background: #eff6ff; color: #1d4ed8; border-radius: 4px; font-size: 11px; font-weight: 700; margin: 2px; }
        .attachment-item { display: block; padding: 8px 12px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 12px; color: #2563eb; text-decoration: none; margin-top: 6px; font-weight: 600; }
        
        .cta-btn { display: block; width: fit-content; margin: 24px auto 0; padding: 12px 28px; background: #fe5f04; color: #ffffff !important; text-decoration: none; font-size: 14px; font-weight: 800; border-radius: 8px; text-align: center; box-shadow: 0 4px 12px rgba(254,95,4,0.3); }
        .email-footer { background: #f8fafc; padding: 16px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Sai Techno Solutions — Ad Budget System</h1>
            <p>Digital Marketing Campaign Budget Request Update</p>
        </div>

        <div class="email-body">
            <div class="greeting">Hello {{ $recipientName }},</div>

            @if($event === 'request_created')
                <p style="font-size: 13px; line-height: 1.5; color: #475569;">
                    A new <strong>{{ ucfirst($adBudgetRequest->type) }} Ad Budget Request</strong> (#{{ $adBudgetRequest->id }}) has been raised by <strong>{{ $adBudgetRequest->requester?->name ?? 'Team Member' }}</strong> and requires review.
                </p>
                <div class="status-badge badge-pending">Status: Pending DM TL Approval</div>
            @elseif($event === 'tl_approved')
                <p style="font-size: 13px; line-height: 1.5; color: #475569;">
                    The DM TL <strong>{{ $adBudgetRequest->tlApprover?->name ?? 'TL' }}</strong> has approved Request #{{ $adBudgetRequest->id }}. It is now pending Accounts Team approval (<strong>hr@saitechnosolutions.net</strong>).
                </p>
                <div class="status-badge badge-pending">Status: Pending HR / Accounts Approval</div>
            @elseif($event === 'accounts_approved')
                <p style="font-size: 13px; line-height: 1.5; color: #475569;">
                    Great news! Accounts Team (<strong>hr@saitechnosolutions.net</strong>) has verified and <strong>approved</strong> Ad Budget Request #{{ $adBudgetRequest->id }}.
                </p>
                <div class="status-badge badge-approved">Status: Accounts Approved & Finalized</div>
            @elseif($event === 'rejected')
                <p style="font-size: 13px; line-height: 1.5; color: #475569;">
                    Ad Budget Request #{{ $adBudgetRequest->id }} has been <strong>rejected</strong>.
                </p>
                <div class="status-badge badge-rejected">Status: Rejected</div>
            @endif

            <table class="info-table">
                <tr>
                    <th>Request ID</th>
                    <td>#{{ $adBudgetRequest->id }} ({{ strtoupper($adBudgetRequest->type) }})</td>
                </tr>
                <tr>
                    <th>Ad Account</th>
                    <td>
                        {{ $adBudgetRequest->adAccount?->account_name ?? '—' }} 
                        @if($adBudgetRequest->adAccount?->platform)
                            <span style="color:#64748b; font-weight: normal;">({{ $adBudgetRequest->adAccount->platform }})</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Requested Amount</th>
                    <td>₹{{ number_format($adBudgetRequest->amount, 2) }}</td>
                </tr>
                @if($adBudgetRequest->approved_amount > 0)
                <tr>
                    <th>Approved Amount</th>
                    <td style="color: #16a34a; font-size: 15px;">₹{{ number_format($adBudgetRequest->approved_amount, 2) }}</td>
                </tr>
                @endif
                @if($adBudgetRequest->payment_date)
                <tr>
                    <th>Payment Date</th>
                    <td>{{ \Carbon\Carbon::parse($adBudgetRequest->payment_date)->format('d M Y') }}</td>
                </tr>
                @endif
                <tr>
                    <th>Selected Dates</th>
                    <td>
                        @if(!empty($adBudgetRequest->selected_dates))
                            @foreach((array)$adBudgetRequest->selected_dates as $dt)
                                <span class="date-chip">{{ $dt }}</span>
                            @endforeach
                        @else
                            —
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Requested By</th>
                    <td>{{ $adBudgetRequest->requester?->name ?? '—' }} ({{ $adBudgetRequest->requester?->email ?? '' }})</td>
                </tr>
                @if($adBudgetRequest->remarks)
                <tr>
                    <th>Requester Remarks</th>
                    <td>{{ $adBudgetRequest->remarks }}</td>
                </tr>
                @endif
                @if($adBudgetRequest->tl_remarks)
                <tr>
                    <th>DM TL Remarks</th>
                    <td>{{ $adBudgetRequest->tl_remarks }}</td>
                </tr>
                @endif
                @if($adBudgetRequest->accounts_remarks)
                <tr>
                    <th>Accounts Remarks</th>
                    <td>{{ $adBudgetRequest->accounts_remarks }}</td>
                </tr>
                @endif
            </table>

            @if(!empty($adBudgetRequest->attachments) && is_array($adBudgetRequest->attachments))
                <div style="margin-top: 16px;">
                    <div style="font-size: 12px; font-weight: 700; color: #334155; uppercase;">Attachments / Payment Proofs:</div>
                    @foreach($adBudgetRequest->attachments as $file)
                        <a href="{{ url($file['url'] ?? '#') }}" class="attachment-item" target="_blank">
                            📎 {{ $file['name'] ?? 'Attachment' }}
                        </a>
                    @endforeach
                </div>
            @endif

            <a href="{{ route($adBudgetRequest->type === 'partner' ? 'accounts.ad-budget.partners' : 'accounts.ad-budget.clients') }}" class="cta-btn">
                View Request Details &rarr;
            </a>
        </div>

        <div class="email-footer">
            This is an automated notification from Sai Techno Solutions ERP System.<br>
            Please do not reply directly to this email. Contact Accounts (hr@saitechnosolutions.net) for queries.
        </div>
    </div>
</body>
</html>
