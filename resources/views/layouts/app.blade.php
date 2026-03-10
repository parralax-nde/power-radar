<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Power Radar') – Power Radar</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    {{-- Custom styles --}}
    <style>
        :root {
            --pr-primary: #6C63FF;
            --pr-primary-dark: #5550d4;
            --pr-accent: #00D4FF;
            --pr-success: #00C896;
            --pr-danger: #FF4D6D;
            --pr-warning: #FFBE00;
            --pr-dark: #0F0F1A;
            --pr-surface: #1A1A2E;
            --pr-surface-2: #22223B;
            --pr-border: rgba(255,255,255,0.08);
            --pr-text: #E2E8F0;
            --pr-text-muted: #94A3B8;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--pr-dark);
            color: var(--pr-text);
            min-height: 100vh;
        }

        /* ── Navbar ── */
        .pr-navbar {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--pr-border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .pr-navbar .navbar-brand {
            font-weight: 800;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, var(--pr-primary) 0%, var(--pr-accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pr-navbar .nav-link {
            color: var(--pr-text-muted) !important;
            font-weight: 500;
            border-radius: 8px;
            padding: 0.4rem 0.8rem !important;
            transition: all 0.2s;
        }
        .pr-navbar .nav-link:hover,
        .pr-navbar .nav-link.active {
            color: var(--pr-text) !important;
            background: rgba(108, 99, 255, 0.15);
        }

        /* ── Cards ── */
        .pr-card {
            background: var(--pr-surface);
            border: 1px solid var(--pr-border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: box-shadow 0.2s;
        }
        .pr-card:hover { box-shadow: 0 4px 24px rgba(108, 99, 255, 0.15); }

        .pr-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        /* ── Metric cards ── */
        .metric-card {
            background: var(--pr-surface);
            border: 1px solid var(--pr-border);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin: 0.5rem 0 0.25rem;
        }
        .metric-label {
            font-size: 0.75rem;
            color: var(--pr-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        /* ── Glow effects ── */
        .glow-green { text-shadow: 0 0 18px var(--pr-success); color: var(--pr-success); }
        .glow-blue  { text-shadow: 0 0 18px var(--pr-accent); color: var(--pr-accent); }
        .glow-red   { text-shadow: 0 0 18px var(--pr-danger); color: var(--pr-danger); }
        .glow-yellow{ text-shadow: 0 0 18px var(--pr-warning); color: var(--pr-warning); }
        .glow-purple{ text-shadow: 0 0 18px var(--pr-primary); color: var(--pr-primary); }

        /* ── Badge / status pill ── */
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
        }
        .status-pill .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        .status-on  { background: rgba(0,200,150,0.15); color: var(--pr-success); }
        .status-on .dot  { background: var(--pr-success); }
        .status-off { background: rgba(255,77,109,0.15); color: var(--pr-danger); }
        .status-off .dot { background: var(--pr-danger); }
        .status-warn{ background: rgba(255,190,0,0.15); color: var(--pr-warning); }
        .status-warn .dot{ background: var(--pr-warning); }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%        { opacity: 0.3; }
        }

        /* ── Progress bar ── */
        .pr-progress {
            height: 8px;
            background: var(--pr-surface-2);
            border-radius: 999px;
            overflow: hidden;
        }
        .pr-progress-bar {
            height: 100%;
            border-radius: 999px;
            transition: width 0.5s ease, background 0.5s;
        }

        /* ── Buttons ── */
        .btn-pr-primary {
            background: linear-gradient(135deg, var(--pr-primary), var(--pr-accent));
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.5rem 1.25rem;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn-pr-primary:hover { opacity: 0.9; transform: translateY(-1px); color: #fff; }

        .btn-pr-danger {
            background: var(--pr-danger);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.5rem 1.25rem;
            transition: opacity 0.2s;
        }
        .btn-pr-danger:hover { opacity: 0.85; color: #fff; }

        .btn-pr-outline {
            background: transparent;
            border: 1px solid var(--pr-border);
            color: var(--pr-text);
            font-weight: 500;
            border-radius: 10px;
            padding: 0.5rem 1.25rem;
            transition: border-color 0.2s, background 0.2s;
        }
        .btn-pr-outline:hover {
            border-color: var(--pr-primary);
            background: rgba(108,99,255,0.08);
            color: var(--pr-text);
        }

        /* ── Forms ── */
        .pr-form-control {
            background: var(--pr-surface-2);
            border: 1px solid var(--pr-border);
            color: var(--pr-text);
            border-radius: 10px;
            padding: 0.6rem 1rem;
        }
        .pr-form-control:focus {
            background: var(--pr-surface-2);
            border-color: var(--pr-primary);
            color: var(--pr-text);
            box-shadow: 0 0 0 3px rgba(108,99,255,0.2);
            outline: none;
        }
        .pr-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--pr-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.4rem;
        }

        /* ── Table ── */
        .pr-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 4px;
        }
        .pr-table th {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--pr-text-muted);
            padding: 0.5rem 0.75rem;
        }
        .pr-table td {
            background: var(--pr-surface-2);
            padding: 0.75rem 0.75rem;
            font-size: 0.875rem;
        }
        .pr-table tr td:first-child { border-radius: 8px 0 0 8px; }
        .pr-table tr td:last-child  { border-radius: 0 8px 8px 0; }

        /* ── Hero gradient text ── */
        .gradient-text {
            background: linear-gradient(135deg, var(--pr-primary), var(--pr-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* ── Animated background ── */
        .hero-bg {
            background: radial-gradient(ellipse at 20% 50%, rgba(108,99,255,0.15) 0%, transparent 60%),
                        radial-gradient(ellipse at 80% 20%, rgba(0,212,255,0.1) 0%, transparent 50%),
                        var(--pr-dark);
        }

        /* ── Alert ── */
        .pr-alert {
            border-radius: 12px;
            border: 1px solid;
            padding: 0.875rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .pr-alert-success { background: rgba(0,200,150,0.1); border-color: rgba(0,200,150,0.3); color: var(--pr-success); }
        .pr-alert-danger  { background: rgba(255,77,109,0.1); border-color: rgba(255,77,109,0.3); color: var(--pr-danger); }
        .pr-alert-warning { background: rgba(255,190,0,0.1);  border-color: rgba(255,190,0,0.3);  color: var(--pr-warning); }

        /* ── Divider ── */
        .pr-divider { border-color: var(--pr-border); }

        /* ── Sparkline container ── */
        .chart-container { position: relative; height: 160px; }

        /* ── Icon box ── */
        .icon-box {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .icon-box-purple { background: rgba(108,99,255,0.15); color: var(--pr-primary); }
        .icon-box-blue   { background: rgba(0,212,255,0.15); color: var(--pr-accent); }
        .icon-box-green  { background: rgba(0,200,150,0.15); color: var(--pr-success); }
        .icon-box-red    { background: rgba(255,77,109,0.15); color: var(--pr-danger); }
        .icon-box-yellow { background: rgba(255,190,0,0.15); color: var(--pr-warning); }

        /* ── Responsive tweaks ── */
        @media (max-width: 576px) {
            .metric-value { font-size: 1.5rem; }
            .pr-card { padding: 1rem; border-radius: 12px; }
        }
    </style>
</head>
<body class="hero-bg">

{{-- Navbar --}}
<nav class="navbar navbar-expand-lg pr-navbar px-3 px-lg-4">
    <a class="navbar-brand" href="{{ route('dashboard') }}">
        <i class="bi bi-lightning-charge-fill me-1"></i>Power Radar
    </a>

    <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
        <i class="bi bi-list fs-4"></i>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
        <ul class="navbar-nav ms-auto gap-1">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2 me-1"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('devices.*') ? 'active' : '' }}"
                   href="{{ route('devices.index') }}">
                    <i class="bi bi-cpu me-1"></i>Devices
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('devices.create') }}">
                    <i class="bi bi-plus-circle me-1"></i>Add Device
                </a>
            </li>
        </ul>
    </div>
</nav>

{{-- Flash messages --}}
<div class="container-fluid px-3 px-lg-4 mt-3">
    @if (session('success'))
        <div class="pr-alert pr-alert-success d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-check-circle-fill"></i>{{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="pr-alert pr-alert-danger d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-circle-fill"></i>{{ session('error') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="pr-alert pr-alert-warning d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill"></i>{{ session('warning') }}
        </div>
    @endif
</div>

{{-- Page content --}}
<main class="container-fluid px-3 px-lg-4 pb-5">
    @yield('content')
</main>

{{-- Footer --}}
<footer class="text-center py-4" style="color: var(--pr-text-muted); font-size: 0.8rem;">
    <i class="bi bi-lightning-charge-fill" style="color: var(--pr-primary)"></i>
    Power Radar &mdash; Shelly 1PM Mini Gen3 Monitor
</footer>

{{-- Bootstrap JS --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

@stack('scripts')
</body>
</html>
