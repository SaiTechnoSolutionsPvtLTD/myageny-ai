<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $form->title }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <!-- Premium Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --font-family: 'Outfit', sans-serif;
            --primary-orange: #fe5f04;
            --primary-gradient: linear-gradient(135deg, #fe5f04, #ff7c30);
            --bg-light: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --glass-bg: rgba(255, 255, 255, 0.85);
        }
        
        * {
            box-sizing: border-box;
            transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: var(--font-family);
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated floating gradients - Light, pastel, subtle */
        .bg-animated {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%);
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            opacity: 0.25;
            animation: float 25s infinite ease-in-out;
        }

        .blob-1 {
            top: -10%;
            left: -10%;
            width: 50vw;
            height: 50vw;
            background: radial-gradient(circle, #fed7aa 0%, rgba(254,215,170,0) 70%);
            animation-duration: 22s;
        }

        .blob-2 {
            bottom: -15%;
            right: -10%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, #e0e7ff 0%, rgba(224,231,255,0) 70%);
            animation-duration: 28s;
            animation-delay: -5s;
        }

        .blob-3 {
            top: 40%;
            left: 30%;
            width: 35vw;
            height: 35vw;
            background: radial-gradient(circle, #fce7f3 0%, rgba(252,231,243,0) 70%);
            animation-duration: 18s;
            animation-delay: -10s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            33% { transform: translate(40px, -60px) scale(1.08) rotate(120deg); }
            66% { transform: translate(-20px, 30px) scale(0.95) rotate(240deg); }
            100% { transform: translate(0, 0) scale(1) rotate(360deg); }
        }

        .df-public-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .df-public-card {
            width: 100%;
            max-width: 800px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.02);
            overflow: hidden;
        }

        .df-public-head {
            padding: 40px 40px 30px;
            border-bottom: 1px solid #f1f5f9;
            background: rgba(255, 255, 255, 0.4);
        }

        .df-public-title {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #0f172a, #1e293b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .df-public-sub {
            margin-top: 10px;
            color: var(--text-muted);
            font-size: 15px;
            line-height: 1.6;
        }

        .df-public-body {
            padding: 40px;
        }

        .eob-alert {
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid transparent;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .eob-alert-success {
            background: rgba(22, 163, 74, 0.08);
            border-color: rgba(22, 163, 74, 0.2);
            color: #166534;
        }

        .eob-alert-error {
            background: rgba(220, 38, 38, 0.08);
            border-color: rgba(220, 38, 38, 0.2);
            color: #991b1b;
        }

        .eob-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }

        .eob-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .eob-group.full {
            grid-column: 1 / -1;
        }

        .eob-label {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }

        .eob-label-required {
            color: #ef4444;
            margin-left: 4px;
        }

        .eob-input, .eob-select, .eob-textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            outline: none;
            background: #ffffff;
            color: var(--text-dark);
        }

        .eob-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .eob-input:focus, .eob-select:focus, .eob-textarea:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 3px rgba(254, 95, 4, 0.15);
            background: #ffffff;
        }

        .eob-error {
            font-size: 13px;
            color: #ef4444;
            margin-top: 4px;
        }

        .eob-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            border: none;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            background: var(--primary-gradient);
            color: #fff;
            box-shadow: 0 4px 14px rgba(254, 95, 4, 0.3);
            transform: translateY(0);
            transition: all 0.2s ease;
        }

        .eob-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(254, 95, 4, 0.4);
        }

        .eob-btn:active {
            transform: translateY(0);
        }

        .df-help {
            font-size: 13px;
            color: var(--text-muted);
        }

        .check-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            padding: 6px 0;
        }

        .check-row {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 10px 14px;
            border-radius: 10px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            user-select: none;
        }

        .check-row:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
        }

        .check-row input[type=checkbox], .check-row input[type=radio] {
            margin: 0;
            width: 18px;
            height: 18px;
            accent-color: var(--primary-orange);
            cursor: pointer;
        }

        .check-row span {
            font-size: 14px;
            color: #334155;
        }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .df-public-wrap {
                padding: 16px 12px;
            }

            .df-public-card {
                border-radius: 16px;
            }

            .df-public-head {
                padding: 24px 20px 20px;
            }

            .df-public-title {
                font-size: 24px;
            }

            .df-public-body {
                padding: 24px 20px 20px;
            }

            .eob-form-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .check-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Floating background animation -->
    <div class="bg-animated">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

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
                    <div class="eob-alert eob-alert-success">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                
                @if(isset($errors) && $errors->any())
                    <div class="eob-alert eob-alert-error">
                        <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Please review the highlighted fields and try again.</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('dynamic-forms.public.submit', $form->public_token) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="eob-form-grid">
                        @foreach($form->fields as $field)
                            <div class="eob-group {{ $field->field_type === 'textarea' ? 'full' : '' }}">
                                <label class="eob-label">
                                    {{ $field->label }}
                                    @if($field->is_required)
                                        <span class="eob-label-required">*</span>
                                    @endif
                                </label>

                                @if($field->field_type === 'textarea')
                                    <textarea name="field_{{ $field->id }}" class="eob-textarea" placeholder="{{ $field->placeholder }}">{{ old('field_' . $field->id) }}</textarea>
                                
                                @elseif($field->field_type === 'select')
                                    <select name="field_{{ $field->id }}" class="eob-select">
                                        <option value="" style="background-color: #ffffff;">Select</option>
                                        @foreach($field->options ?? [] as $option)
                                            <option value="{{ $option }}" @selected(old('field_' . $field->id) === $option) style="background-color: #ffffff;">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                
                                @elseif($field->field_type === 'radio')
                                    <div class="check-grid">
                                        @foreach($field->options ?? [] as $option)
                                            <label class="check-row">
                                                <input type="radio" name="field_{{ $field->id }}" value="{{ $option }}" @checked(old('field_' . $field->id) === $option)>
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                
                                @elseif($field->field_type === 'checkbox')
                                    <div class="check-grid">
                                        @foreach($field->options ?? [] as $option)
                                            <label class="check-row">
                                                <input type="checkbox" name="field_{{ $field->id }}[]" value="{{ $option }}" @checked(in_array($option, old('field_' . $field->id, []), true))>
                                                <span>{{ $option }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                
                                @elseif($field->field_type === 'file')
                                    <input type="file" name="field_{{ $field->id }}" class="eob-input" style="padding: 9px 12px;">
                                
                                @else
                                    <input type="{{ $field->field_type === 'number' ? 'number' : 'text' }}" name="field_{{ $field->id }}" class="eob-input" value="{{ old('field_' . $field->id) }}" placeholder="{{ $field->placeholder }}">
                                @endif

                                @if($field->help_text)
                                    <div class="df-help">{{ $field->help_text }}</div>
                                @endif
                                
                                @error('field_' . $field->id)
                                    <div class="eob-error">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach
                    </div>

                    <div style="margin-top: 32px; display: flex; justify-content: flex-end;">
                        <button type="submit" class="eob-btn">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            Submit Response
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
