<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Auth — WhatsApp Campaigner')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .auth-wrapper {
            width: 100%;
            max-width: 420px;
        }
        .auth-card {
            background: rgba(15,23,42,0.98);
            border-radius: 20px;
            padding: 24px 22px 26px;
            border: 1px solid rgba(31,41,55,0.9);
            box-shadow: 0 18px 40px rgba(15,23,42,0.9);
        }
        .auth-title {
            margin: 0 0 6px;
            font-size: 20px;
            font-weight: 600;
        }
        .auth-subtitle {
            margin: 0 0 18px;
            font-size: 13px;
            color: #9ca3af;
        }
        .auth-field {
            margin-bottom: 12px;
        }
        .auth-field label {
            display: block;
            font-size: 12px;
            margin-bottom: 4px;
            color: #e5e7eb;
        }
        .auth-field input {
            width: 100%;
            padding: 8px 10px;
            border-radius: 10px;
            border: 1px solid #1f2937;
            background: #020617;
            color: #f9fafb;
            font-size: 13px;
        }
        .auth-footer {
            margin-top: 14px;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }
        .auth-footer a {
            color: #38bdf8;
            text-decoration: none;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        .auth-error {
            font-size: 12px;
            color: #f97316;
            margin-top: 3px;
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
            <div class="logo-circle">W</div>
            <div class="logo-text">
                <span class="logo-title">WhatsApp</span>
                <span class="logo-subtitle">Campaigner</span>
            </div>
        </div>

        @yield('content')
    </div>
</div>
</body>
</html>
