<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Entry</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; font-family:Inter, Arial, sans-serif; background:#f4f5f7; color:#121212; display:flex; align-items:center; justify-content:center; padding:22px; }
        .ve-card { width:min(100%, 680px); background:#fff; border:1px solid #e1dee3; border-radius:18px; overflow:hidden; box-shadow:0 18px 48px rgba(18,18,18,.08); }
        .ve-head { padding:22px 24px; border-bottom:1px solid #f1eff3; }
        .ve-title { font-size:22px; font-weight:800; margin:0; }
        .ve-sub { margin-top:6px; color:#7c7c7c; font-size:13px; line-height:1.5; }
        .ve-body { padding:24px; display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .ve-field { display:flex; flex-direction:column; gap:6px; }
        .ve-field.full { grid-column:1 / -1; }
        .ve-label { font-size:13px; font-weight:700; color:#444; }
        .ve-req { color:#fe5f04; }
        .ve-input, .ve-textarea { width:100%; padding:11px 12px; border:1px solid #e1dee3; border-radius:10px; font-size:14px; font-family:inherit; outline:none; }
        .ve-textarea { min-height:96px; resize:vertical; }
        .ve-input:focus, .ve-textarea:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
        .ve-error { color:#dc2626; font-size:12px; }
        .ve-foot { padding:18px 24px 24px; display:flex; justify-content:flex-end; border-top:1px solid #f1eff3; }
        .ve-btn { border:none; border-radius:10px; padding:11px 18px; background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; font-weight:800; cursor:pointer; }
        @media (max-width: 640px) { .ve-body { grid-template-columns:1fr; } .ve-field.full { grid-column:auto; } }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('visitor-entry.store') }}" class="ve-card">
        @csrf
        <div class="ve-head">
            <h1 class="ve-title">Visitor Entry</h1>
            <div class="ve-sub">Please fill your visit details before meeting the team.</div>
        </div>
        <div class="ve-body">
            <div class="ve-field">
                <label class="ve-label">Visitor Name <span class="ve-req">*</span></label>
                <input type="text" name="visitor_name" class="ve-input" value="{{ old('visitor_name') }}" required>
                @error('visitor_name')<div class="ve-error">{{ $message }}</div>@enderror
            </div>
            <div class="ve-field">
                <label class="ve-label">Mobile Number <span class="ve-req">*</span></label>
                <input type="text" name="mobile_number" class="ve-input" value="{{ old('mobile_number') }}" required>
                @error('mobile_number')<div class="ve-error">{{ $message }}</div>@enderror
            </div>
            <div class="ve-field">
                <label class="ve-label">Date <span class="ve-req">*</span></label>
                <input type="date" name="visit_date" class="ve-input" value="{{ old('visit_date', now()->toDateString()) }}" required>
                @error('visit_date')<div class="ve-error">{{ $message }}</div>@enderror
            </div>
            <div class="ve-field">
                <label class="ve-label">In Time <span class="ve-req">*</span></label>
                <input type="time" name="in_time" class="ve-input" value="{{ old('in_time', now()->format('H:i')) }}" required>
                @error('in_time')<div class="ve-error">{{ $message }}</div>@enderror
            </div>
            <div class="ve-field">
                <label class="ve-label">Out Time</label>
                <input type="time" name="out_time" class="ve-input" value="{{ old('out_time') }}">
                @error('out_time')<div class="ve-error">{{ $message }}</div>@enderror
            </div>
            <div class="ve-field">
                <label class="ve-label">Whom To Meet <span class="ve-req">*</span></label>
                <input type="text" name="person_to_meet" class="ve-input" value="{{ old('person_to_meet') }}" required>
                @error('person_to_meet')<div class="ve-error">{{ $message }}</div>@enderror
            </div>
            <div class="ve-field full">
                <label class="ve-label">Purpose of Meet <span class="ve-req">*</span></label>
                <textarea name="remarks" class="ve-textarea" placeholder="Enter purpose of meeting" required>{{ old('remarks') }}</textarea>
                @error('remarks')<div class="ve-error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="ve-foot">
            <button type="submit" class="ve-btn">Submit Entry</button>
        </div>
    </form>
</body>
</html>
