<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Entry Submitted</title>
    <link rel="icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <style>
        body { margin:0; min-height:100vh; font-family:Inter, Arial, sans-serif; background:#f4f5f7; color:#121212; display:flex; align-items:center; justify-content:center; padding:22px; }
        .card { width:min(100%, 520px); background:#fff; border:1px solid #e1dee3; border-radius:18px; padding:28px; text-align:center; box-shadow:0 18px 48px rgba(18,18,18,.08); }
        .title { font-size:22px; font-weight:800; margin:0; }
        .copy { margin-top:10px; color:#666; line-height:1.6; font-size:14px; }
        .ref { margin-top:18px; display:inline-flex; padding:8px 12px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-weight:800; font-size:12px; }
    </style>
</head>
<body>
    <div class="card">
        <h1 class="title">Entry Submitted</h1>
        <div class="copy">Your facility management entry for {{ $facilityEntry->title }} has been recorded successfully.</div>
        <div class="ref">Facility ID #{{ $facilityEntry->id }}</div>
    </div>
</body>
</html>
