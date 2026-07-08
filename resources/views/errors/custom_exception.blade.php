<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Error</title>
    <style>
        :root {
            --bg: #fcf8f8;
            --panel: #ffffff;
            --text: #1f1111;
            --muted: #6b5a5a;
            --accent: #dc2626;
            --accent-dark: #991b1b;
            --border: #f3e8e8;
            --shadow: 0 24px 70px rgba(220, 38, 38, 0.08);
            --code-bg: #1e1e1e;
            --code-text: #f8f8f2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(220, 38, 38, 0.07), transparent 34%),
                radial-gradient(circle at bottom right, rgba(220, 38, 38, 0.05), transparent 28%),
                var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: min(100%, 980px);
            background: var(--panel);
            border: 1px solid rgba(243, 232, 232, 0.9);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
        }

        .content {
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(220, 38, 38, 0.08);
            color: var(--accent-dark);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            align-self: flex-start;
        }

        .badge::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--accent);
            box-shadow: 0 0 0 6px rgba(220, 38, 38, 0.16);
        }

        h1 {
            margin: 20px 0 12px;
            font-size: clamp(1.8rem, 3.5vw, 2.8rem);
            line-height: 1.1;
            font-weight: 800;
            color: #111;
        }

        .error-desc {
            color: var(--muted);
            font-size: 0.98rem;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .error-box {
            padding: 16px 20px;
            border-radius: 16px;
            background: #fff5f5;
            border: 1px solid #fecaca;
            margin-bottom: 24px;
        }

        .error-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--accent-dark);
            margin-bottom: 6px;
        }

        .error-message {
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 13px;
            line-height: 1.5;
            color: #7f1d1d;
            word-break: break-all;
        }

        .error-meta {
            font-size: 12px;
            color: var(--muted);
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .error-meta span {
            background: #fff;
            padding: 3px 8px;
            border-radius: 6px;
            border: 1px solid #fee2e2;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            border: none;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .button-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
            box-shadow: 0 12px 24px rgba(220, 38, 38, 0.16);
        }

        .button-secondary {
            background: #fdfdfd;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .visual {
            position: relative;
            background:
                linear-gradient(180deg, rgba(220, 38, 38, 0.05), rgba(220, 38, 38, 0.01)),
                #faf5f5;
            padding: 40px 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .visual-box {
            width: 100%;
            max-width: 340px;
            padding: 24px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 45px rgba(220, 38, 38, 0.06);
        }

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            color: var(--accent);
            font-size: 24px;
            font-weight: 900;
        }

        .visual-box h2 {
            margin: 18px 0 8px;
            font-size: 1.25rem;
            color: #111;
        }

        .visual-box p {
            font-size: 0.9rem;
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }

        .code-pill {
            margin-top: 18px;
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 10px;
            background: #fff;
            border: 1px dashed rgba(220, 38, 38, 0.2);
            color: var(--accent-dark);
            font-weight: 700;
            font-size: 12px;
            letter-spacing: 0.05em;
        }

        .debug-trace {
            margin-top: 24px;
            display: grid;
            gap: 8px;
        }

        .debug-title {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .debug-content {
            background: var(--code-bg);
            color: var(--code-text);
            padding: 16px;
            border-radius: 12px;
            font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 11px;
            line-height: 1.6;
            overflow-x: auto;
            max-height: 220px;
        }

        @media (max-width: 820px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .content,
            .visual {
                padding: 36px 24px;
            }

            .actions {
                flex-direction: column;
            }

            .button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <section class="card">
        <div class="layout">
            <div class="content">
                <span class="badge">Application Exception</span>
                <h1>An error occurred.</h1>
                <p class="error-desc">We encountered an unexpected error while processing your request. The technical details are provided below in English.</p>

                <div class="error-box">
                    <div class="error-title">{{ $title ?? 'RuntimeException' }}</div>
                    <div class="error-message">{{ $message ?? 'An unexpected server error occurred.' }}</div>
                    
                    @if(config('app.debug') && isset($exception))
                        <div class="error-meta">
                            <span>File: {{ basename($exception->getFile()) }}</span>
                            <span>Line: {{ $exception->getLine() }}</span>
                        </div>
                    @endif
                </div>

                <div class="actions">
                    <a href="{{ url('/') }}" class="button button-primary">Go to Homepage</a>
                    <button onclick="window.location.reload();" class="button button-secondary">Reload Page</button>
                    <button onclick="copyErrorDetails();" id="copy-btn" class="button button-secondary">Copy Error Details</button>
                </div>

                @if(config('app.debug') && isset($exception))
                    <div class="debug-trace">
                        <div class="debug-title">Stack Trace</div>
                        <pre class="debug-content"><code>{{ $exception->getTraceAsString() }}</code></pre>
                    </div>
                @endif
            </div>

            <div class="visual">
                <div class="visual-box">
                    <div class="status">500</div>
                    <h2>System Exception</h2>
                    <p>This exception indicates a server-side execution issue. Please contact the technical administrator if the issue persists.</p>
                    <div class="code-pill">STATUS 500</div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function copyErrorDetails() {
            const errorTitle = "{{ $title ?? 'RuntimeException' }}";
            const errorMessage = `{!! addslashes($message ?? 'An unexpected server error occurred.') !!}`;
            const file = "{{ isset($exception) ? addslashes($exception->getFile()) : 'N/A' }}";
            const line = "{{ isset($exception) ? $exception->getLine() : 'N/A' }}";

            const textToCopy = `Error Type: ${errorTitle}\nMessage: ${errorMessage}\nLocation: ${file} (Line: ${line})`;

            navigator.clipboard.writeText(textToCopy).then(() => {
                const btn = document.getElementById('copy-btn');
                const origText = btn.textContent;
                btn.textContent = 'Copied!';
                btn.style.borderColor = '#22c55e';
                btn.style.color = '#15803d';
                setTimeout(() => {
                    btn.textContent = origText;
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }, 2000);
            }).catch(err => {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>
