<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->title }}</title>
    <style>
        :root {
            --font-family: 'Inter', sans-serif;
            --text-primary: #121212;
            --text-secondary: #7c7c7c;
            --border-color: #e1dee3;
            --primary-orange: #fe5f04;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: var(--font-family);
            background: linear-gradient(180deg, #fff7f1 0%, #f7f3ef 40%, #f4f5f7 100%);
            color: var(--text-primary);
        }
        .eob-alert { padding: 12px 16px; border-radius: 12px; font-size: 13px; border: 1px solid transparent; margin-bottom: 18px; }
        .eob-alert-success { background:#f0fdf4; border-color:#bbf7d0; color:#166534; }
        .eob-alert-error { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
        .eob-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:16px; }
        .eob-group { display:flex; flex-direction:column; gap:6px; }
        .eob-group.full { grid-column:1 / -1; }
        .eob-label { font-size:13px; font-weight:700; color:#444; }
        .eob-label-required { color:#dc2626; margin-left:4px; font-weight:800; }
        .eob-input, .eob-select, .eob-textarea {
            width:100%;
            padding:11px 12px;
            border:1px solid #e1dee3;
            border-radius:10px;
            font-size:14px;
            font-family:inherit;
            outline:none;
            background:#fff;
            color:#121212;
        }
        .eob-textarea { min-height:110px; resize:vertical; }
        .eob-input:focus, .eob-select:focus, .eob-textarea:focus { border-color:#fe5f04; box-shadow:0 0 0 3px rgba(254,95,4,.1); }
        .eob-error { font-size:12px; color:#dc2626; }
        .eob-btn {
            display:inline-flex; align-items:center; justify-content:center; gap:6px;
            padding:10px 18px; border-radius:10px; font-size:14px; font-weight:700;
            border:1px solid transparent; text-decoration:none; cursor:pointer; font-family:inherit;
            background:linear-gradient(135deg,#fe5f04,#ff7c30); color:#fff;
        }
        .df-help { font-size: 12px; color: #8a8a8a; }
        .check-row { display:flex; align-items:center; gap:8px; cursor:pointer; }
        .check-row input[type=checkbox], .check-row input[type=radio] { width:16px; height:16px; accent-color:#fe5f04; }
        .df-shell { display:flex; flex-direction:column; gap:10px; }
        .df-public-wrap { min-height: 100vh; padding: 28px 16px; }
        .df-public-card {
            width: 100%; max-width: 880px; margin: 0 auto; background: rgba(255,255,255,.95);
            border: 1px solid #e1dee3; border-radius: 24px; box-shadow: 0 18px 45px rgba(18,18,18,.06); overflow: hidden;
        }
        .df-public-head { padding: 24px 28px 18px; border-bottom: 1px solid #f0eef2; }
        .df-public-title { font-size: 26px; font-weight: 800; color: #121212; }
        .df-public-sub { margin-top: 8px; color: #7c7c7c; line-height: 1.6; }
        .df-public-body { padding: 24px 28px 28px; }
        @media (max-width: 768px) {
            .eob-form-grid { grid-template-columns: 1fr; }
            .df-public-head, .df-public-body { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="df-public-wrap">
        <div class="df-public-card">
            <div class="df-public-head">
                <div class="df-public-title">{{ $form->title }}</div>
                @if($form->description)
                    <div class="df-public-sub">{{ $form->description }}</div>
                @endif
            </div>
            <div class="df-public-body">
                @if(session('success'))
                    <div class="eob-alert eob-alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="eob-alert eob-alert-error">Please review the highlighted fields and try again.</div>
                @endif

                <form method="POST" action="{{ route('dynamic-forms.public.submit', $form->public_token) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="eob-form-grid">
                        @foreach($form->fields as $field)
                            <div class="eob-group {{ $field->field_type === 'textarea' ? 'full' : '' }}">
                                <label class="eob-label">{{ $field->label }} @if($field->is_required)<span class="eob-label-required">*</span>@endif</label>

                                @if($field->field_type === 'textarea')
                                    <textarea name="field_{{ $field->id }}" class="eob-textarea" placeholder="{{ $field->placeholder }}">{{ old('field_' . $field->id) }}</textarea>
                                @elseif($field->field_type === 'select')
                                    <select name="field_{{ $field->id }}" class="eob-select">
                                        <option value="">Select</option>
                                        @foreach($field->options ?? [] as $option)
                                            <option value="{{ $option }}" @selected(old('field_' . $field->id) === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @elseif($field->field_type === 'radio')
                                    <div class="df-shell">
                                        @foreach($field->options ?? [] as $option)
                                            <label class="check-row">
                                                <input type="radio" name="field_{{ $field->id }}" value="{{ $option }}" @checked(old('field_' . $field->id) === $option)>
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($field->field_type === 'checkbox')
                                    <div class="df-shell">
                                        @foreach($field->options ?? [] as $option)
                                            <label class="check-row">
                                                <input type="checkbox" name="field_{{ $field->id }}[]" value="{{ $option }}" @checked(in_array($option, old('field_' . $field->id, []), true))>
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($field->field_type === 'file')
                                    <input type="file" name="field_{{ $field->id }}" class="eob-input">
                                @else
                                    <input type="{{ $field->field_type === 'number' ? 'number' : 'text' }}" name="field_{{ $field->id }}" class="eob-input" value="{{ old('field_' . $field->id) }}" placeholder="{{ $field->placeholder }}">
                                @endif

                                @if($field->help_text)
                                    <div class="df-help">{{ $field->help_text }}</div>
                                @endif
                                @error('field_' . $field->id)<div class="eob-error">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>

                    <div style="margin-top:20px;">
                        <button type="submit" class="eob-btn">Submit Response</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
