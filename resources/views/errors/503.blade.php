<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance — We'll Be Right Back</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: radial-gradient(120% 120% at 50% 10%, #1a1528 0%, #0d0a14 100%);
            --card-bg: rgba(255, 255, 255, 0.04);
            --card-border: rgba(255, 255, 255, 0.08);
            --primary: #fe5f04;
            --primary-glow: rgba(254, 95, 4, 0.35);
            --primary-gradient: linear-gradient(135deg, #fe5f04, #ff7c30);
            --text-main: #ffffff;
            --text-muted: #a19bb0;
            --accent-green: #10b981;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0c0914;
            background-image:
                radial-gradient(circle at 15% 20%, rgba(254, 95, 4, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 85% 80%, rgba(124, 58, 237, 0.1) 0%, transparent 45%),
                radial-gradient(circle at 50% 50%, rgba(15, 23, 42, 0.6) 0%, #07050a 100%);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
            position: relative;
        }

        /* Subtle animated background grid */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .maint-container {
            position: relative;
            z-index: 1;
            width: min(100%, 1040px);
            background: rgba(18, 14, 28, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.5),
                        0 0 80px rgba(254, 95, 4, 0.08);
            overflow: hidden;
        }

        .maint-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
        }

        @media (max-width: 868px) {
            .maint-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Content Left */
        .maint-content {
            padding: 56px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        @media (max-width: 576px) {
            .maint-content {
                padding: 36px 24px;
            }
        }

        .maint-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            border-radius: 999px;
            background: rgba(254, 95, 4, 0.12);
            border: 1px solid rgba(254, 95, 4, 0.25);
            color: #ff9d66;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            align-self: flex-start;
            margin-bottom: 24px;
        }

        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--primary);
            box-shadow: 0 0 0 0 rgba(254, 95, 4, 0.7);
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(254, 95, 4, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(254, 95, 4, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(254, 95, 4, 0);
            }
        }

        .maint-title {
            font-size: clamp(2.2rem, 4vw, 3.2rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.02em;
            background: linear-gradient(135deg, #ffffff 30%, #a19bb0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 16px;
        }

        .maint-desc {
            color: var(--text-muted);
            font-size: 1.05rem;
            line-height: 1.65;
            margin-bottom: 32px;
            font-weight: 400;
        }

        /* Feature pills */
        .maint-features {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-bottom: 36px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            font-size: 0.92rem;
            color: #e2dede;
        }

        .feature-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(254, 95, 4, 0.15);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Actions & Timer */
        .maint-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-refresh {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 14px;
            background: var(--primary-gradient);
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 24px var(--primary-glow);
            transition: all 0.25s ease;
        }

        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px var(--primary-glow);
            filter: brightness(1.08);
        }

        .btn-refresh svg {
            transition: transform 0.5s ease;
        }

        .btn-refresh:hover svg {
            transform: rotate(180deg);
        }

        .timer-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            color: var(--text-muted);
        }

        .timer-count {
            font-weight: 700;
            color: var(--primary);
            font-variant-numeric: tabular-nums;
        }

        /* Progress Bar */
        .progress-bar-wrap {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            overflow: hidden;
            margin-top: 24px;
        }

        .progress-bar-fill {
            height: 100%;
            width: 100%;
            background: var(--primary-gradient);
            transition: width 1s linear;
        }

        /* Visual Graphic Right */
        .maint-visual {
            background: linear-gradient(135deg, rgba(254, 95, 4, 0.06) 0%, rgba(124, 58, 237, 0.06) 100%);
            border-left: 1px solid rgba(255, 255, 255, 0.08);
            padding: 48px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        @media (max-width: 868px) {
            .maint-visual {
                border-left: none;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                padding: 36px 24px;
            }
        }

        .illustration-box {
            width: 100%;
            max-width: 320px;
            aspect-ratio: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Animated SVG elements */
        .gear-svg {
            animation: spin 20s linear infinite;
            transform-origin: center;
        }

        .gear-svg-reverse {
            animation: spin-reverse 15s linear infinite;
            transform-origin: center;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @keyframes spin-reverse {
            from { transform: rotate(360deg); }
            to { transform: rotate(0deg); }
        }

        .maint-status-card {
            width: 100%;
            max-width: 320px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 20px;
            margin-top: 24px;
        }

        .status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.85rem;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .status-row:last-child {
            border-bottom: none;
        }

        .status-label {
            color: var(--text-muted);
        }

        .status-val {
            font-weight: 700;
            color: #ffffff;
        }

        .status-val.active {
            color: var(--accent-green);
        }

        .footer-note {
            margin-top: 32px;
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.35);
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="maint-container">
        <div class="maint-grid">
            
            {{-- Left Column: Information & Actions --}}
            <div class="maint-content">
                <div class="maint-badge">
                    <span class="pulse-dot"></span>
                    Scheduled Maintenance
                </div>

                <h1 class="maint-title">We'll Be Right Back!</h1>
                <p class="maint-desc">
                    Our system is currently undergoing scheduled maintenance & performance upgrades. We are working hard to refine features and ensure peak reliability.
                </p>

                <div class="maint-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div><strong>Performance Boost:</strong> Optimizing system speed & database performance</div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div><strong>Data Integrity:</strong> All your files, records, and data are completely secure</div>
                    </div>
                </div>

                <div class="maint-actions">
                    <button class="btn-refresh" onclick="window.location.reload()">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Check System Status
                    </button>

                    <div class="timer-wrap">
                        <span>Auto refreshing in</span>
                        <span class="timer-count" id="timerCount">30s</span>
                    </div>
                </div>

                <div class="progress-bar-wrap">
                    <div class="progress-bar-fill" id="progressBar"></div>
                </div>
            </div>

            {{-- Right Column: Graphic Visual --}}
            <div class="maint-visual">
                <div class="illustration-box">
                    <svg width="220" height="220" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Outer Glow Circle -->
                        <circle cx="100" cy="100" r="85" stroke="rgba(254,95,4,0.15)" stroke-width="2" stroke-dasharray="6 6"/>
                        
                        <!-- Primary Large Gear -->
                        <g class="gear-svg">
                            <path d="M100 65C80.67 65 65 80.67 65 100C65 119.33 80.67 135 100 135C119.33 135 135 119.33 135 100C135 80.67 119.33 65 100 65ZM100 120C88.95 120 80 111.05 80 100C80 88.95 88.95 80 100 80C111.05 80 120 88.95 120 100C120 111.05 111.05 120 100 120Z" fill="url(#gearGrad)"/>
                            <path d="M93 50H107V65H93V50ZM93 135H107V150H93V135ZM135 93H150V107H135V93ZM50 93H65V107H50V93ZM125.6 65.2L136.2 54.6L146.8 65.2L136.2 75.8L125.6 65.2ZM53.2 137.6L63.8 127L74.4 137.6L63.8 148.2L53.2 137.6ZM136.2 145.4L125.6 134.8L136.2 124.2L146.8 134.8L136.2 145.4ZM63.8 54.6L74.4 65.2L63.8 75.8L53.2 65.2L63.8 54.6Z" fill="url(#gearGrad)"/>
                        </g>

                        <!-- Secondary Small Gear -->
                        <g class="gear-svg-reverse" transform="translate(40, -40)">
                            <circle cx="100" cy="100" r="20" fill="rgba(124,58,237,0.3)" stroke="#7c3aed" stroke-width="2"/>
                        </g>

                        <!-- Center Wrench / Tool Badge -->
                        <circle cx="100" cy="100" r="24" fill="#0d0a14" stroke="rgba(254,95,4,0.5)" stroke-width="2"/>
                        <path d="M94 106L106 94M96 92L99 95M101 105L104 108" stroke="#fe5f04" stroke-width="2.5" stroke-linecap="round"/>

                        <!-- Gradients -->
                        <defs>
                            <linearGradient id="gearGrad" x1="50" y1="50" x2="150" y2="150" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#fe5f04"/>
                                <stop offset="1" stop-color="#ff7c30"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>

                <div class="maint-status-card">
                    <div class="status-row">
                        <span class="status-label">HTTP Status</span>
                        <span class="status-val">503 Service Unavailable</span>
                    </div>
                    <div class="status-row">
                        <span class="status-label">Maintenance Mode</span>
                        <span class="status-val active">● Active</span>
                    </div>
                    <div class="status-row">
                        <span class="status-label">Estimated Downtime</span>
                        <span class="status-val">&lt; 15 mins</span>
                    </div>
                </div>

                <div class="footer-note">
                    &copy; {{ date('Y') }} {{ config('app.name', 'MyAgency') }}. All rights reserved.
                </div>
            </div>

        </div>
    </div>

    <script>
        // Auto Countdown Timer (30 Seconds)
        (function() {
            let totalSeconds = 30;
            let currentSeconds = totalSeconds;
            const timerEl = document.getElementById('timerCount');
            const progressEl = document.getElementById('progressBar');

            function updateTimer() {
                if (currentSeconds <= 0) {
                    timerEl.textContent = 'Refreshing...';
                    progressEl.style.width = '0%';
                    window.location.reload();
                    return;
                }
                
                timerEl.textContent = currentSeconds + 's';
                const percentage = (currentSeconds / totalSeconds) * 100;
                progressEl.style.width = percentage + '%';
                
                currentSeconds--;
            }

            updateTimer();
            setInterval(updateTimer, 1000);
        })();
    </script>
</body>
</html>
