<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Petty Cash Account Report (DR & CR)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #1f2937;
            margin: 20px;
            background: #fff;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #fe5f04;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
            text-transform: uppercase;
        }
        .subtitle {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .meta-item label {
            font-size: 11px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: bold;
            display: block;
        }
        .meta-item span {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .summary-cards {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            text-align: center;
        }
        .card.ob { background: #eff6ff; border-color: #bfdbfe; }
        .card.dr { background: #ecfdf5; border-color: #a7f3d0; }
        .card.cr { background: #fef2f2; border-color: #fecaca; }
        .card.cb { background: #fff7ed; border-color: #ffedd5; }

        .card-label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #475569; }
        .card-value { font-size: 15px; font-weight: bold; margin-top: 4px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            text-align: left;
        }
        th {
            background-color: #f1f5f9;
            font-size: 11px;
            text-transform: uppercase;
            color: #334155;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .bg-opening { background-color: #f8fafc; font-weight: bold; }
        .bg-total { background-color: #f1f5f9; font-weight: bold; }
        .bg-closing { background-color: #fff7ed; font-weight: bold; }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            padding-top: 20px;
        }
        .sig-box {
            text-align: center;
            width: 200px;
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            font-size: 11px;
            font-weight: bold;
            color: #475569;
        }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="background: #fe5f04; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            Print / Save as PDF
        </button>
    </div>

    <div class="header">
        <div>
            <div class="title">Petty Cash Account Report (DR & CR)</div>
            <div class="subtitle">Official Statement of Debit & Credit Cash Transactions</div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 16px; font-weight: bold; color: #fe5f04;">{{ auth()->user()?->company?->company_name ?? 'myAgenci.ai' }}</div>
            <div style="font-size: 11px; color: #64748b;">Generated: {{ now()->format('d M Y, h:i A') }}</div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-item">
            <label>Statement Period</label>
            <span>{{ $startDate }} to {{ $endDate }}</span>
        </div>
        <div class="meta-item">
            <label>Total Transactions</label>
            <span>{{ count($transactions) }} Record(s)</span>
        </div>
    </div>

    <div class="summary-cards">
        <div class="card ob">
            <div class="card-label">Opening Balance</div>
            <div class="card-value" style="color: #1d4ed8;">₹ {{ number_format($openingBalance, 2) }}</div>
        </div>
        <div class="card dr">
            <div class="card-label">Total Debit (DR)</div>
            <div class="card-value" style="color: #b91c1c;">₹ {{ number_format($totalDebit, 2) }}</div>
        </div>
        <div class="card cr">
            <div class="card-label">Total Credit (CR)</div>
            <div class="card-value" style="color: #047857;">₹ {{ number_format($totalCredit, 2) }}</div>
        </div>
        <div class="card cb">
            <div class="card-label">Closing Balance</div>
            <div class="card-value" style="color: #c2410c;">₹ {{ number_format($closingBalance, 2) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 90px;">Date</th>
                <th style="width: 110px;">Voucher / Ref</th>
                <th style="width: 140px;">Name (Person / Vendor)</th>
                <th>Particulars / Narration</th>
                <th class="text-right" style="width: 100px;">Debit (DR)</th>
                <th class="text-right" style="width: 100px;">Credit (CR)</th>
                <th class="text-right" style="width: 120px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance Row -->
            <tr class="bg-opening">
                <td>{{ $startDate }}</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td>OPENING BALANCE B/F</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">₹ {{ number_format($openingBalance, 2) }}</td>
            </tr>

            @forelse($transactions as $tx)
                <tr>
                    <td>{{ $tx['entry_date_formatted'] }}</td>
                    <td>{{ $tx['voucher_no'] }}</td>
                    <td>{{ $tx['name'] }}</td>
                    <td>{{ $tx['particulars'] }}</td>
                    <td class="text-right" style="color: #b91c1c;">
                        {{ $tx['debit'] > 0 ? '₹ ' . number_format($tx['debit'], 2) : '-' }}
                    </td>
                    <td class="text-right" style="color: #047857;">
                        {{ $tx['credit'] > 0 ? '₹ ' . number_format($tx['credit'], 2) : '-' }}
                    </td>
                    <td class="text-right font-bold">
                        ₹ {{ number_format($tx['running_balance'], 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">
                        No transactions recorded within this date range.
                    </td>
                </tr>
            @endforelse

            <!-- Totals Row -->
            <tr class="bg-total">
                <td colspan="4" class="text-right">TOTAL DEBIT & CREDIT</td>
                <td class="text-right" style="color: #b91c1c;">₹ {{ number_format($totalDebit, 2) }}</td>
                <td class="text-right" style="color: #047857;">₹ {{ number_format($totalCredit, 2) }}</td>
                <td class="text-right">-</td>
            </tr>

            <!-- Closing Balance Row -->
            <tr class="bg-closing">
                <td colspan="4" class="text-right">CLOSING BALANCE C/F</td>
                <td class="text-right" colspan="2">Net Statement Balance</td>
                <td class="text-right" style="color: #c2410c; font-size: 14px;">₹ {{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signatures">
        <div class="sig-box">Prepared By</div>
        <div class="sig-box">Verified By Accountant</div>
        <div class="sig-box">Authorized Signature</div>
    </div>

</body>
</html>
