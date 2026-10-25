<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Quotation {{ $quotation->quotation_no }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px 14px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f8f8f8;
            color: #121212;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            width: min(520px, 100%);
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            text-align: center;
            padding: 32px 28px;
        }
        .icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 16px;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 22px;
            font-weight: 800;
            color: #111827;
        }
        p {
            margin: 0 0 20px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
        }
        .quote-info {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 24px;
            text-align: left;
            font-size: 13px;
        }
        .quote-info div {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }
        .quote-info div:last-child {
            margin-bottom: 0;
            font-weight: 700;
            border-top: 1px dashed #d1d5db;
            padding-top: 6px;
        }
        .btn-reject {
            width: 100%;
            padding: 14px 20px;
            background: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
            transition: background .15s ease;
        }
        .btn-reject:hover {
            background: #b91c1c;
        }
        .cancel-link {
            display: inline-block;
            margin-top: 16px;
            font-size: 13px;
            color: #6b7280;
            text-decoration: none;
        }
        .cancel-link:hover {
            color: #111827;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-circle">✕</div>
        <h1>Reject Quotation?</h1>
        <p>
            Are you sure you want to reject quotation <strong>#{{ $quotation->quotation_no }}</strong>?
            This will update the status in our system.
        </p>

        <div class="quote-info">
            <div>
                <span style="color:#6b7280;">Quotation:</span>
                <span><strong>{{ $quotation->quotation_no }}</strong></span>
            </div>
            <div>
                <span style="color:#6b7280;">Customer:</span>
                <span>{{ $lead->contact_name ?: 'Customer' }}</span>
            </div>
            <div>
                <span style="color:#6b7280;">Total Amount:</span>
                <span style="color:#dc2626;">₹{{ number_format((float) $quotation->total_amount, 2) }}</span>
            </div>
        </div>

        <form action="{{ $submitUrl }}" method="POST">
            @csrf
            <button type="submit" class="btn-reject">Confirm Rejection</button>
        </form>
    </div>
</body>
</html>
