<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Passbook Statement - {{ $accountLabel }}</title>
    <style>
        @page {
            margin: 18mm 22mm 18mm 22mm;
            size: a4 landscape;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #1e293b;
            background: #ffffff;
            line-height: 1.4;
        }

        /* Running Footer */
        .page-footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            height: 16px;
            border-top: 1px solid #cbd5e1;
            padding-top: 3px;
        }

        /* Header Section */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.2;
        }
        .company-meta {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.35;
        }
        .passbook-badge {
            display: inline-block;
            background: #fff7ed;
            color: #ea580c;
            border: 1px solid #ffedd5;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .account-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
        }
        .statement-meta {
            font-size: 8px;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.3;
        }

        /* Summary Cards */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .summary-card {
            padding: 8px 12px;
            vertical-align: middle;
        }
        .summary-lbl {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .summary-val {
            font-size: 13.5px;
            font-weight: bold;
            margin-top: 2px;
            line-height: 1.1;
        }

        /* Ledger Table */
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border: 1px solid #cbd5e1;
        }
        .ledger-table th {
            background: #f8fafc;
            color: #000000;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 6px 8px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .ledger-table td {
            padding: 5px 8px;
            font-size: 8.5px;
            border: 1px solid #cbd5e1;
            color: #000000;
            vertical-align: middle;
        }

        .badge-alloc {
            display: inline-block;
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge-spend {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        /* Signatures block */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
            page-break-inside: avoid;
        }
        .sign-box {
            vertical-align: bottom;
            width: 33.33%;
        }
        .sign-title {
            font-size: 8px;
            font-weight: bold;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 42px;
        }
        .sign-line {
            border-top: 1px solid #94a3b8;
            padding-top: 4px;
        }
        .sign-name {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
        }
        .sign-role {
            font-size: 7.5px;
            color: #64748b;
            margin-top: 1px;
        }
    </style>
</head>
<body>

    {{-- Running Footer --}}
    <div class="page-footer">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="text-align:left; font-size:7.5px; color:#94a3b8;">
                    Ad Budget Passbook Statement &bull; {{ $accountLabel }} &bull; Generated: {{ $generatedAt->format('d M Y, h:i A') }}
                </td>
                <td style="text-align:right; font-size:7.5px; color:#94a3b8;">
                    Official Accounts Record &bull; {{ $companyName }}
                </td>
            </tr>
        </table>
    </div>

    {{-- Header Section --}}
    <table class="header-table">
        <tr>
            <td style="width:55%; vertical-align:top;">
                <table style="border-collapse:collapse;">
                    <tr>
                        @if($logoBase64)
                        <td style="vertical-align:top; padding-right:12px;">
                            <img src="{{ $logoBase64 }}" style="max-height:42px; max-width:140px;" alt="Logo">
                        </td>
                        @endif
                        <td style="vertical-align:top;">
                            <div class="company-name">{{ $companyName }}</div>
                            @if($companyAddress)<div class="company-meta">{{ $companyAddress }}</div>@endif
                            <div class="company-meta">
                                @if($companyEmail)<span>Email: {{ $companyEmail }}</span>@endif
                                @if($companyPhone)<span style="margin-left:8px;">Tel: {{ $companyPhone }}</span>@endif
                                @if($companyGstin)<span style="margin-left:8px;">GSTIN: {{ $companyGstin }}</span>@endif
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width:45%; vertical-align:top; text-align:right;">
                <div class="passbook-badge">AD BUDGET PASSBOOK STATEMENT</div>
                <div class="account-title">{{ $accountLabel }}</div>
                @if($selectedAccount && $selectedAccount->account_id)
                    <div class="statement-meta">Account ID: <strong>{{ $selectedAccount->account_id }}</strong> &bull; Platform: <strong>{{ $selectedAccount->platform ?: 'Digital Ad' }}</strong></div>
                @endif
                <div class="statement-meta">
                    <strong>Statement Period:</strong>
                    @if($dateFrom && $dateTo)
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    @elseif($dateFrom)
                        From {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }} onwards
                    @elseif($dateTo)
                        Up to {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    @else
                        All Recorded Transactions Till Date
                    @endif
                </div>
                <div class="statement-meta" style="color:#94a3b8; font-size:7.5px;">
                    Generated by: {{ $generatedBy }} &bull; {{ $generatedAt->format('d M Y, h:i A') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Highlights / Financial Summary --}}
    <table class="summary-table">
        <tr>
            <td class="summary-card" style="width:25%; background:#f8fafc; border:1px solid #cbd5e1; border-radius:5px 0 0 5px;">
                <div class="summary-lbl" style="color:#64748b;">Opening Balance B/F</div>
                <div class="summary-val" style="color:#334155;">₹{{ number_format($openingBalance, 2) }}</div>
            </td>
            <td class="summary-card" style="width:25%; background:#fff7ed; border:1px solid #fed7aa; border-left:none;">
                <div class="summary-lbl" style="color:#ea580c;">Total Allocated (Credit +)</div>
                <div class="summary-val" style="color:#c2410c;">+₹{{ number_format($periodAllocated, 2) }}</div>
            </td>
            <td class="summary-card" style="width:25%; background:#eff6ff; border:1px solid #bfdbfe; border-left:none;">
                <div class="summary-lbl" style="color:#2563eb;">Total Ad Spend (Debit -)</div>
                <div class="summary-val" style="color:#1d4ed8;">-₹{{ number_format($periodSpent, 2) }}</div>
            </td>
            <td class="summary-card" style="width:25%; background:{{ $periodClosingBalance >= 0 ? '#f0fdf4' : '#fef2f2' }}; border:1px solid {{ $periodClosingBalance >= 0 ? '#bbf7d0' : '#fecaca' }}; border-left:none; border-radius:0 5px 5px 0;">
                <div class="summary-lbl" style="color:{{ $periodClosingBalance >= 0 ? '#15803d' : '#b91c1c' }};">
                    {{ $selectedAccount ? 'Closing Balance' : 'Net Running Balance' }}
                </div>
                <div class="summary-val" style="color:{{ $periodClosingBalance >= 0 ? '#16a34a' : '#dc2626' }};">
                    ₹{{ number_format($periodClosingBalance, 2) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Passbook Ledger Table --}}
    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width:4%; text-align:center;">#</th>
                <th style="width:10%; text-align:center;">Date</th>
                @if(!$selectedAccount)
                    <th style="width:14%; text-align:left;">Ad Account</th>
                @endif
                <th style="text-align:left;">Particulars / Description</th>
                <th style="width:9%; text-align:center;">Type</th>
                <th style="width:15%; text-align:left;">User / Ref</th>
                <th style="width:11%; text-align:right;">Allocated (Cr +)</th>
                <th style="width:11%; text-align:right;">Spend (Dr -)</th>
                <th style="width:12%; text-align:right;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @if($dateFrom && $openingBalance != 0)
                <tr style="background:#f8fafc;">
                    <td style="text-align:center; color:#64748b;">—</td>
                    <td style="text-align:center; color:#475569; font-weight:bold;">{{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }}</td>
                    @if(!$selectedAccount)
                        <td style="color:#64748b;">—</td>
                    @endif
                    <td style="font-weight:bold; color:#475569;">
                        [Opening Balance Brought Forward]
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:7px; font-weight:bold; color:#64748b;">OB</span>
                    </td>
                    <td style="color:#64748b;">System</td>
                    <td style="text-align:right; color:#94a3b8;">—</td>
                    <td style="text-align:right; color:#94a3b8;">—</td>
                    <td style="text-align:right; font-weight:bold; color:#0f172a;">
                        ₹{{ number_format($openingBalance, 2) }}
                    </td>
                </tr>
            @endif

            @forelse($rows as $idx => $row)
                @php
                    $dateObj    = \Carbon\Carbon::parse($row['date']);
                    $allocated  = (float) $row['allocated_amount'];
                    $spent      = (float) $row['spent_amount'];
                    $runningBal = (float) $row['running_balance'];
                    $isAlloc    = $row['type'] === 'allocation';
                    $rowBg      = $idx % 2 === 0 ? '#ffffff' : '#f8fafc';
                @endphp
                <tr style="background:{{ $rowBg }};">
                    <td style="text-align:center; color:#64748b; font-weight:bold;">
                        {{ $idx + 1 }}
                    </td>
                    <td style="text-align:center; white-space:nowrap;">
                        <strong style="color:#0f172a; font-size:8.5px;">{{ $dateObj->format('d-m-Y') }}</strong>
                        <div style="font-size:7px; color:#94a3b8;">{{ $dateObj->format('D') }}</div>
                    </td>
                    @if(!$selectedAccount)
                        <td>
                            <strong style="color:#0f172a;">{{ $row['ad_account']?->account_name ?? '—' }}</strong>
                            <div style="font-size:7px; color:#64748b;">{{ $row['ad_account']?->platform }}</div>
                        </td>
                    @endif
                    <td>
                        <strong style="color:#0f172a; font-size:9px;">{{ $row['description'] }}</strong>
                    </td>
                    <td style="text-align:center;">
                        @if($isAlloc)
                            <span class="badge-alloc">+ ALLOC</span>
                        @else
                            <span class="badge-spend">- SPEND</span>
                        @endif
                    </td>
                    <td>
                        <strong style="color:#334155; font-size:8.5px;">{{ $row['user_name'] }}</strong>
                        <div style="font-size:7px; color:#94a3b8;">Ref #{{ $row['ref_id'] }}</div>
                    </td>
                    <td style="text-align:right; font-weight:bold; font-size:9px; color:{{ $allocated > 0 ? '#ea580c' : '#cbd5e1' }};">
                        {{ $allocated > 0 ? '+₹' . number_format($allocated, 2) : '—' }}
                    </td>
                    <td style="text-align:right; font-weight:bold; font-size:9px; color:{{ $spent > 0 ? '#2563eb' : '#cbd5e1' }};">
                        {{ $spent > 0 ? '-₹' . number_format($spent, 2) : '—' }}
                    </td>
                    <td style="text-align:right; font-weight:bold; font-size:9.5px; color:{{ $runningBal >= 0 ? '#15803d' : '#b91c1c' }}; background:{{ $runningBal >= 0 ? '#f0fdf4' : '#fef2f2' }};">
                        ₹{{ number_format($runningBal, 2) }}
                        @if(!$selectedAccount && isset($row['account_balance']))
                            <div style="font-size:6.5px; color:#64748b; font-weight:normal;">Acc: ₹{{ number_format($row['account_balance'], 2) }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $selectedAccount ? 8 : 9 }}" style="text-align:center; padding:25px; color:#94a3b8; font-style:italic;">
                        No ledger transactions found matching the selected criteria.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background:#f1f5f9; font-weight:bold; border-top:2px solid #0f172a;">
                <td colspan="{{ $selectedAccount ? 5 : 6 }}" style="text-align:right; padding:8px 10px; font-size:8.5px; text-transform:uppercase; color:#0f172a;">
                    TOTAL FOR STATEMENT PERIOD:
                </td>
                <td style="text-align:right; padding:8px 8px; font-size:9.5px; font-weight:bold; color:#c2410c;">
                    +₹{{ number_format($periodAllocated, 2) }}
                </td>
                <td style="text-align:right; padding:8px 8px; font-size:9.5px; font-weight:bold; color:#1d4ed8;">
                    -₹{{ number_format($periodSpent, 2) }}
                </td>
                <td style="text-align:right; padding:8px 8px; font-size:10px; font-weight:bold; color:{{ $periodClosingBalance >= 0 ? '#15803d' : '#b91c1c' }}; background:{{ $periodClosingBalance >= 0 ? '#dcfce7' : '#fee2e2' }};">
                    ₹{{ number_format($periodClosingBalance, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Signatures & Certification --}}
    <table class="sign-table">
        <tr>
            <td class="sign-box" style="text-align:left; padding-right:20px;">
                <div class="sign-title">PREPARED BY</div>
                <div class="sign-line">
                    <div class="sign-name">{{ $generatedBy }}</div>
                    <div class="sign-role">Accounts Department</div>
                </div>
            </td>
            <td class="sign-box" style="text-align:center; padding:0 20px;">
                <div class="sign-title">VERIFIED BY</div>
                <div class="sign-line">
                    <div class="sign-name">Accounts Manager</div>
                    <div class="sign-role">Finance & Audit</div>
                </div>
            </td>
            <td class="sign-box" style="text-align:right; padding-left:20px;">
                <div class="sign-title">AUTHORIZED SIGNATORY</div>
                <div class="sign-line">
                    <div class="sign-name">For {{ $companyName }}</div>
                    <div class="sign-role">Official Stamp & Signature</div>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
