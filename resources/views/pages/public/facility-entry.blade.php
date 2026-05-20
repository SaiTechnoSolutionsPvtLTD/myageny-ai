<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Management Entry</title>
    <style>
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; font-family:Inter, Arial, sans-serif; background:#f4f5f7; color:#121212; display:flex; align-items:center; justify-content:center; padding:22px; }
        .fe-card { width:min(100%, 680px); background:#fff; border:1px solid #e1dee3; border-radius:18px; overflow:hidden; box-shadow:0 18px 48px rgba(18,18,18,.08); }
        .fe-head { padding:22px 24px; border-bottom:1px solid #f1eff3; }
        .fe-title { font-size:22px; font-weight:800; margin:0; }
        .fe-sub { margin-top:6px; color:#7c7c7c; font-size:13px; line-height:1.5; }
        .fe-body { padding:24px; display:flex; flex-direction:column; gap:16px; }
        .fe-field { display:flex; flex-direction:column; gap:6px; }
        .fe-label { font-size:13px; font-weight:700; color:#444; }
        .fe-req { color:#fe5f04; }
        .fe-input { width:100%; padding:11px 12px; border:1px solid #e1dee3; border-radius:10px; font-size:14px; font-family:inherit; outline:none; }
        .fe-textarea { width:100%; min-height:110px; padding:11px 12px; border:1px solid #e1dee3; border-radius:10px; font-size:14px; font-family:inherit; outline:none; resize:vertical; }
        .fe-input:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
        .fe-textarea:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.10); }
        .fe-input[readonly] { background:#f8f8f8; color:#555; }
        .fe-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
        .fe-error { color:#dc2626; font-size:12px; }
        .fe-foot { padding:18px 24px 24px; display:flex; justify-content:flex-end; border-top:1px solid #f1eff3; }
        .fe-btn { border:none; border-radius:10px; padding:11px 18px; background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff; font-weight:800; cursor:pointer; }
        .fe-btn:disabled { opacity:.55; cursor:not-allowed; }
        .fe-alert { padding:12px 14px; border-radius:12px; background:#fff7ed; color:#9a3412; border:1px solid #fed7aa; font-size:13px; }
        @media (max-width: 640px) { .fe-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
    <form method="POST" action="{{ route('facility-entry.store') }}" class="fe-card">
        @csrf
        <div class="fe-head">
            <h1 class="fe-title">Facility Management Entry</h1>
            <div class="fe-sub">Scan and submit the required facility activity. No login is required for this form.</div>
        </div>
        <div class="fe-body">
            @if($facilityTitles->isEmpty())
                <div class="fe-alert">No facility titles are available right now. Please contact the admin team.</div>
            @endif

            <div class="fe-field">
                <label class="fe-label">Facility Title <span class="fe-req">*</span></label>
                <select name="facility_title_id" class="fe-input" required @disabled($facilityTitles->isEmpty())>
                    <option value="">Select title</option>
                    @foreach($facilityTitles as $facilityTitle)
                        <option value="{{ $facilityTitle->id }}" @selected((string) old('facility_title_id') === (string) $facilityTitle->id)>{{ $facilityTitle->name }}</option>
                    @endforeach
                </select>
                @error('facility_title_id')<div class="fe-error">{{ $message }}</div>@enderror
            </div>

            <div class="fe-grid">
                <div class="fe-field">
                    <label class="fe-label">Date</label>
                    <input type="text" class="fe-input" value="{{ $currentDateTime->format('d M Y') }}" readonly>
                </div>
                <div class="fe-field">
                    <label class="fe-label">Time</label>
                    <input type="text" class="fe-input" value="{{ $currentDateTime->format('h:i A') }}" readonly>
                </div>
            </div>

            <div class="fe-field">
                <label class="fe-label">Remarks</label>
                <textarea name="remarks" class="fe-textarea" placeholder="Optional remarks">{{ old('remarks') }}</textarea>
                @error('remarks')<div class="fe-error">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="fe-foot">
            <button type="submit" class="fe-btn" @disabled($facilityTitles->isEmpty())>Submit Entry</button>
        </div>
    </form>
</body>
</html>
