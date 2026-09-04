<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>myAgenci.ai — Sign In</title>
    <link rel="icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #fe5f04;
            --primary-light: #ff7c30;
            --primary-pale: #fff3ec;
            --purple: #60308c;
            --purple-light: #7b45a8;
            --dark: #0e0a14;
            --dark-2: #1a1225;
            --dark-3: #251a32;
            --mid: #3d2d54;
            --text-muted: #9e8fb5;
            --text-light: #c8bdd8;
            --white: #ffffff;
            --border: rgba(255,255,255,0.08);
            --border-hover: rgba(254,95,4,0.4);
            --success: #2ecc71;
            --error: #ff4757;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--dark);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            overflow: hidden;
            position: relative;
        }

        /* ── Ambient background ── */
        .bg-orbs {
            position: fixed; inset: 0; pointer-events: none; z-index: 0;
            overflow: hidden;
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.18;
            animation: drift 18s ease-in-out infinite;
        }
        .orb-1 { width: 600px; height: 600px; background: var(--purple); top: -200px; left: -150px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: var(--primary); bottom: -100px; right: -80px; animation-delay: -6s; }
        .orb-3 { width: 300px; height: 300px; background: #2a1040; top: 40%; left: 30%; animation-delay: -12s; }

        @keyframes drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -20px) scale(1.05); }
            66% { transform: translate(-20px, 30px) scale(0.95); }
        }

        /* Noise texture */
        body::after {
            content: '';
            position: fixed; inset: 0; z-index: 1;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.03'/%3E%3C/svg%3E");
            pointer-events: none;
        }

        /* ── Layout ── */
        .page-wrapper {
            position: relative; z-index: 2;
            display: flex; width: 100%; min-height: 100vh;
        }

        /* ── Left Panel ── */
        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(96,48,140,0.15) 0%, transparent 60%);
            pointer-events: none;
        }

        /* Grid lines decoration */
        .grid-lines {
            position: absolute; inset: 0; pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            mask-image: radial-gradient(ellipse at 30% 50%, black 30%, transparent 70%);
        }

        .brand {
            display: flex; align-items: center; gap: 12px;
        }
        .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Space Mono', monospace;
            font-weight: 700; font-size: 14px; color: white;
            box-shadow: 0 8px 24px rgba(254,95,4,0.4);
        }
        .brand-name {
            font-size: 18px; font-weight: 800; letter-spacing: -0.3px;
            background: linear-gradient(90deg, #fff 60%, var(--text-light));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .left-content { flex: 1; display: flex; flex-direction: column; justify-content: center; }

        .tagline {
            font-family: 'Space Mono', monospace;
            font-size: 11px; letter-spacing: 3px; text-transform: uppercase;
            color: var(--primary); margin-bottom: 24px;
            display: flex; align-items: center; gap: 8px;
        }
        .tagline::before {
            content: '';
            display: inline-block; width: 24px; height: 1px;
            background: var(--primary);
        }

        .hero-title {
            font-size: clamp(36px, 4vw, 52px);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -1.5px;
            margin-bottom: 24px;
        }
        .hero-title .line-accent {
            background: linear-gradient(90deg, var(--primary) 0%, #d58900 50%, var(--primary) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-size: 200% 100%;
            animation: shimmer 4s ease infinite;
        }
        @keyframes shimmer {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        .hero-desc {
            font-size: 15px; line-height: 1.7;
            color: var(--text-muted); max-width: 400px;
            margin-bottom: 40px;
        }

        /* Feature pills */
        .feature-pills { display: flex; flex-wrap: wrap; gap: 10px; }
        .pill {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 14px; border-radius: 100px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            font-size: 12px; font-weight: 500; color: var(--text-light);
            transition: all 0.2s ease;
        }
        .pill:hover { border-color: var(--border-hover); color: white; }
        .pill-dot {
            width: 6px; height: 6px; border-radius: 50%;
        }
        .dot-orange { background: var(--primary); }
        .dot-purple { background: var(--purple-light); }
        .dot-green { background: #2ecc71; }
        .dot-blue { background: #3498db; }

        /* Stats row */
        .stats-row {
            display: flex; gap: 32px;
        }
        .stat-item { }
        .stat-num {
            font-size: 28px; font-weight: 800;
            background: linear-gradient(135deg, var(--white), var(--text-light));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            line-height: 1;
        }
        .stat-label { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

        /* ── Right Panel (Login Form) ── */
        .right-panel {
            width: 480px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 40px;
            background: rgba(255,255,255,0.02);
            border-left: 1px solid var(--border);
            backdrop-filter: blur(20px);
            position: relative;
        }

        .form-card {
            width: 100%;
            max-width: 380px;
        }

        .form-header { margin-bottom: 36px; }
        .form-eyebrow {
            font-family: 'Space Mono', monospace;
            font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
            color: var(--text-muted); margin-bottom: 12px;
        }
        .form-title {
            font-size: 28px; font-weight: 800; letter-spacing: -0.8px;
            line-height: 1.2; margin-bottom: 8px;
        }
        .form-subtitle {
            font-size: 13px; color: var(--text-muted); line-height: 1.6;
        }

        /* ── Error/Success Alerts ── */
        .alert {
            padding: 12px 16px; border-radius: 10px;
            font-size: 13px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            animation: slideIn 0.3s ease;
        }
        .alert-error {
            background: rgba(255,71,87,0.1);
            border: 1px solid rgba(255,71,87,0.3);
            color: #ff6b7a;
        }
        .alert-success {
            background: rgba(46,204,113,0.1);
            border: 1px solid rgba(46,204,113,0.3);
            color: #2ecc71;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ── Form Groups ── */
        .form-group { margin-bottom: 20px; }
        .form-label {
            display: block;
            font-size: 12px; font-weight: 600;
            color: var(--text-light); letter-spacing: 0.3px;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }
        .input-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted);
            width: 16px; height: 16px;
            pointer-events: none;
            transition: color 0.2s;
        }
        .input-wrapper:focus-within .input-icon { color: var(--primary); }

        .form-input {
            width: 100%;
            padding: 13px 14px 13px 42px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--white);
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.2s ease;
            outline: none;
        }
        .form-input::placeholder { color: var(--text-muted); }
        .form-input:focus {
            border-color: var(--primary);
            background: rgba(254,95,4,0.05);
            box-shadow: 0 0 0 3px rgba(254,95,4,0.12);
        }
        .form-input.is-invalid {
            border-color: var(--error);
            background: rgba(255,71,87,0.05);
        }
        .invalid-feedback {
            font-size: 11px; color: var(--error);
            margin-top: 5px; display: flex; align-items: center; gap: 4px;
        }

        .toggle-password {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--text-muted); padding: 0;
            transition: color 0.2s;
        }
        .toggle-password:hover { color: var(--text-light); }

        /* ── Branch Select ── */
        .form-select {
            width: 100%;
            padding: 13px 14px 13px 42px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--white);
            font-size: 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            outline: none;
            appearance: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .form-select option { background: var(--dark-2); color: var(--white); }
        .form-select:focus {
            border-color: var(--primary);
            background: rgba(254,95,4,0.05);
            box-shadow: 0 0 0 3px rgba(254,95,4,0.12);
        }
        .select-arrow {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            pointer-events: none; color: var(--text-muted);
        }

        /* ── Remember / Forgot ── */
        .form-row-flex {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 28px;
        }
        .checkbox-label {
            display: flex; align-items: center; gap: 8px;
            font-size: 12px; color: var(--text-muted); cursor: pointer;
            user-select: none;
        }
        .checkbox-label input[type="checkbox"] { display: none; }
        .custom-checkbox {
            width: 16px; height: 16px;
            border: 1px solid var(--border);
            border-radius: 4px;
            background: rgba(255,255,255,0.04);
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .checkbox-label input:checked + .custom-checkbox {
            background: var(--primary);
            border-color: var(--primary);
        }
        .checkbox-label input:checked + .custom-checkbox::after {
            content: '';
            width: 8px; height: 5px;
            border-left: 1.5px solid white;
            border-bottom: 1.5px solid white;
            transform: rotate(-45deg) translate(1px, -1px);
            display: block;
        }
        .forgot-link {
            font-size: 12px; color: var(--primary);
            text-decoration: none; font-weight: 500;
            transition: opacity 0.2s;
        }
        .forgot-link:hover { opacity: 0.7; }

        /* ── Submit Button ── */
        .btn-login {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border: none; border-radius: 10px;
            color: white; font-size: 14px; font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            position: relative; overflow: hidden;
            transition: all 0.2s ease;
            box-shadow: 0 8px 24px rgba(254,95,4,0.35);
            letter-spacing: 0.3px;
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 32px rgba(254,95,4,0.45);
        }
        .btn-login:active { transform: translateY(0); }
        .btn-login::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
            opacity: 0; transition: opacity 0.2s;
        }
        .btn-login:hover::before { opacity: 1; }

        /* Loading spinner */
        .btn-login .spinner {
            display: none; width: 16px; height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .btn-login.loading .spinner { display: inline-block; }
        .btn-login.loading .btn-text { display: none; }

        /* ── Divider ── */
        .divider {
            display: flex; align-items: center; gap: 12px;
            margin: 24px 0;
        }
        .divider-line { flex: 1; height: 1px; background: var(--border); }
        .divider-text { font-size: 11px; color: var(--text-muted); white-space: nowrap; }

        /* ── Footer ── */
        .form-footer {
            margin-top: 28px; text-align: center;
            font-size: 12px; color: var(--text-muted);
        }
        .form-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; }

        /* Security badge */
        .security-badge {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            margin-top: 20px;
            font-size: 11px; color: var(--text-muted);
        }
        .security-badge svg { color: #2ecc71; }

        /* ── Responsive ── */
        @media (max-width: 900px) {
            .left-panel { display: none; }
            .right-panel { width: 100%; border-left: none; }
        }
    </style>
</head>
<body>
<div class="bg-orbs">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
</div>

<div class="page-wrapper">
    <!-- Left Panel -->
    <div class="left-panel">
        <div class="grid-lines"></div>

        <div class="brand">
            {{--  <div class="brand-icon">MA</div>
            <span class="brand-name">myAgenci.ai</span>  --}}

            <img src="{{ asset('images/my_agenci_logo_2.png') }}" alt="Logo" class="logo-img" style="width:250px">
        </div>

        <div class="left-content">
            <div class="tagline">Intelligent CRM Platform</div>
            <h1 class="hero-title">
                Manage Your
                Agency with<br>
                <span class="line-accent">AI Precision</span>
            </h1>
            <p class="hero-desc">
                One unified platform for lead management, HR operations, sales tracking, and project delivery — powered by intelligent automation.
            </p>
            <div class="feature-pills">
                <div class="pill"><span class="pill-dot dot-orange"></span>Lead Management</div>
                <div class="pill"><span class="pill-dot dot-purple"></span>HR & Payroll</div>
                <div class="pill"><span class="pill-dot dot-green"></span>Sales Pipeline</div>
                <div class="pill"><span class="pill-dot dot-blue"></span>Project Tracking</div>
                <div class="pill"><span class="pill-dot dot-orange"></span>AI Insights</div>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-num">2.4k+</div>
                <div class="stat-label">Active Leads</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">98%</div>
                <div class="stat-label">Uptime SLA</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">14</div>
                <div class="stat-label">Branches</div>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="right-panel">
        <div class="form-card">
            <div class="form-header">
                <div class="form-eyebrow">Welcome back</div>
                <h2 class="form-title">Sign in to your<br>workspace</h2>
                <p class="form-subtitle">Enter your credentials to access the platform</p>
            </div>

            {{-- Session / Validation Errors --}}
            @if (session('error'))
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    {{ $errors->first() }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="loginForm">
                @csrf



                {{-- Email --}}
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}"
                            placeholder="you@agency.com"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                        >
                    </div>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-wrapper">
                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                            placeholder="••••••••••"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-password" onclick="togglePassword()" id="toggleBtn">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Remember / Forgot --}}
                <div class="form-row-flex">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span class="custom-checkbox"></span>
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Sign In to Workspace</span>
                </button>
            </form>

            <div class="security-badge">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>256-bit SSL encrypted · Secure session</span>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
        } else {
            input.type = 'password';
            icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        }
    }

    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('loginBtn');
        btn.classList.add('loading');
    });
</script>
</body>
</html>
