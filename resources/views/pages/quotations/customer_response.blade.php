<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Response - {{ $quotation->quotation_no }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 14px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f8f8f8;
            color: #121212;
        }
        .response-card {
            width: min(540px, 100%);
            background: #fff;
            border: 1px solid #e1dee3;
            border-radius: 16px;
            padding: 32px 28px;
            text-align: center;
            box-shadow: 0 18px 48px rgba(18, 18, 18, .08);
        }
        .response-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: 0.3px;
        }
        .response-pill.agree,
        .response-pill.approved {
            background: #edfaf3;
            color: #1a7a52;
            border: 1px solid #b6ead0;
        }
        .response-pill.disagree,
        .response-pill.rejected {
            background: #fef2f2;
            color: #b42318;
            border: 1px solid #fecaca;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.25;
            color: #111827;
        }
        p {
            margin: 0 0 18px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
        }
        .details-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin: 20px 0;
            text-align: left;
            font-size: 13px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 7px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
        }
        .detail-val {
            font-weight: 700;
            color: #111827;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }
        .signature-preview {
            margin-top: 18px;
            padding: 14px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            text-align: center;
        }
        .signature-preview img {
            max-width: 100%;
            max-height: 90px;
            object-fit: contain;
            display: block;
            margin: 8px auto 0;
        }
        .btn-pdf {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 14px;
            padding: 11px 22px;
            background: #111827;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            transition: background .15s ease;
        }
        .btn-pdf:hover {
            background: #1f2937;
        }
        .meta {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #f0eef2;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="response-card">
        <div class="response-pill {{ $response }}">{{ $responseLabel }}</div>
        <h1>Thank you for your response</h1>
        <p>
            Your response for quotation <strong>#{{ $quotation->quotation_no }}</strong>
            has been successfully recorded as <strong>{{ $responseLabel }}</strong>.
        </p>

        <div class="details-box">
            <div class="detail-row">
                <span class="detail-label">Quotation No</span>
                <span class="detail-val">{{ $quotation->quotation_no }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount</span>
                <span class="detail-val" style="color:#15803d;">₹{{ number_format((float) $quotation->total_amount, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="detail-val">{{ $responseLabel }}</span>
            </div>
            @if($quotation->customer_responded_at)
            <div class="detail-row">
                <span class="detail-label">Timestamp</span>
                <span class="detail-val">{{ $quotation->customer_responded_at->format('d M Y, h:i A') }}</span>
            </div>
            @endif
            @if($quotation->customer_location)
            <div class="detail-row">
                <span class="detail-label">Location</span>
                <span class="detail-val">{{ $quotation->customer_location }}</span>
            </div>
            @endif

            @if($quotation->customer_signature)
            <div class="signature-preview">
                <div style="font-size:11px; font-weight:700; color:#6b7280; text-transform:uppercase;">Digital Signature</div>
                <img src="{{ asset($quotation->customer_signature) }}" alt="Customer Signature">
            </div>
            @endif
        </div>

        <a href="{{ route('quotation.pdf', $quotation->id) }}" class="btn-pdf" target="_blank">
            📄 Download Quotation PDF
        </a>

        <div class="meta">
            Recorded at {{ $quotation->customer_responded_at?->format('d M Y, h:i:s A') }}
        </div>
    </div>
</body>
</html>
