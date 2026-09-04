<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <link rel="icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/de5a089ae19a67b2b6f7f59203abf1b0ba0f9474.png') }}">
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #ffffff;
            --text: #162033;
            --muted: #5f6b7a;
            --accent: #0f766e;
            --accent-dark: #0b5d57;
            --border: #d9e2ec;
            --shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
            --warning: #f97316;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(15, 118, 110, 0.14), transparent 34%),
                radial-gradient(circle at bottom right, rgba(249, 115, 22, 0.12), transparent 28%),
                var(--bg);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: min(100%, 920px);
            background: var(--panel);
            border: 1px solid rgba(217, 226, 236, 0.9);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .layout {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
        }

        .content {
            padding: 56px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(249, 115, 22, 0.1);
            color: #c2410c;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .badge::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: var(--warning);
            box-shadow: 0 0 0 6px rgba(249, 115, 22, 0.16);
        }

        h1 {
            margin: 24px 0 14px;
            font-size: clamp(2rem, 4vw, 3.5rem);
            line-height: 1.05;
        }

        p {
            margin: 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 32px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 190px;
            padding: 14px 22px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .button-primary {
            background: linear-gradient(135deg, var(--accent), #149b91);
            color: #fff;
            box-shadow: 0 16px 32px rgba(15, 118, 110, 0.22);
        }

        .button-secondary {
            background: #f8fafc;
            color: var(--text);
            border: 1px solid var(--border);
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .visual {
            position: relative;
            background:
                linear-gradient(180deg, rgba(15, 118, 110, 0.08), rgba(15, 118, 110, 0.02)),
                #eef6f7;
            padding: 48px 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .visual-box {
            width: 100%;
            max-width: 320px;
            padding: 28px;
            border-radius: 26px;
            background: rgba(255, 255, 255, 0.84);
            border: 1px solid rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            box-shadow: 0 24px 50px rgba(15, 23, 42, 0.12);
        }

        .status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            border-radius: 22px;
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
            color: #c2410c;
            font-size: 28px;
            font-weight: 800;
        }

        .visual-box h2 {
            margin: 20px 0 10px;
            font-size: 1.35rem;
        }

        .visual-box p {
            font-size: 0.96rem;
        }

        .code {
            margin-top: 22px;
            display: inline-flex;
            padding: 10px 14px;
            border-radius: 12px;
            background: #fff;
            border: 1px dashed rgba(15, 118, 110, 0.26);
            color: var(--accent-dark);
            font-weight: 700;
            letter-spacing: 0.08em;
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
                <span class="badge">Access Restricted</span>
                <h1>This action is unauthorized.</h1>
                <p>{{ $message ?? 'You do not have permission to access this page. Please return to your dashboard or contact an administrator if you need access.' }}</p>

                <div class="actions">
                    <a href="{{ route('dashboard') }}" class="button button-primary">Move to Dashboard</a>
                    <a href="{{ url()->previous() }}" class="button button-secondary">Go Back</a>
                </div>
            </div>

            <div class="visual">
                <div class="visual-box">
                    <div class="status">403</div>
                    <h2>Permission required</h2>
                    <p>Your account can sign in, but this specific page or action is outside the permissions currently assigned to you.</p>
                    <div class="code">ERROR 403</div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
