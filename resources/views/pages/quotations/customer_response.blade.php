<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Response</title>
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
            padding: 24px;
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            color: #121212;
        }
        .response-card {
            width: min(480px, 100%);
            background: #fff;
            border: 1px solid #e1dee3;
            border-radius: 14px;
            padding: 28px;
            text-align: center;
            box-shadow: 0 18px 48px rgba(18, 18, 18, .08);
        }
        .response-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 7px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .response-pill.agree {
            background: #edfaf3;
            color: #1a7a52;
            border: 1px solid #b6ead0;
        }
        .response-pill.disagree {
            background: #fef2f2;
            color: #b42318;
            border: 1px solid #fecaca;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 24px;
            line-height: 1.25;
        }
        p {
            margin: 0;
            color: #666;
            font-size: 15px;
            line-height: 1.6;
        }
        .meta {
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1px solid #f0eef2;
            font-size: 13px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="response-card">
        <div class="response-pill {{ $response }}">{{ $responseLabel }}</div>
        <h1>Thank you for your response</h1>
        <p>
            Your response for quotation <strong>{{ $quotation->quotation_no }}</strong>
            has been recorded as <strong>{{ $responseLabel }}</strong>.
        </p>
        <div class="meta">
            Responded at {{ $quotation->customer_responded_at?->format('d M Y, h:i A') }}
        </div>
    </div>
</body>
</html>
