<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.3.0/dist/tabler-icons.min.css">

    <style>
        :root {
            --tblr-font-sans-serif: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body { font-feature-settings: "cv02","cv03","cv04","cv11"; }
        .navbar-brand-image { height: 2rem; }

        /* ---- Stat tiles ---- */
        .stat-tile .stat-ico {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff; flex-shrink: 0;
        }
        .stat-tile .stat-val  { font-size: 1.5rem; font-weight: 700; line-height: 1.2; margin-bottom: .1rem; }
        .stat-tile .stat-lbl  { font-size: .7rem; letter-spacing: .4px; text-transform: uppercase; color: var(--tblr-secondary); }
        .bg-grad-indigo  { background: linear-gradient(135deg, #6366f1, #4338ca); }
        .bg-grad-teal    { background: linear-gradient(135deg, #14b8a6, #0f766e); }
        .bg-grad-cyan    { background: linear-gradient(135deg, #06b6d4, #0e7490); }
        .bg-grad-amber   { background: linear-gradient(135deg, #f59e0b, #b45309); }
        .bg-grad-rose    { background: linear-gradient(135deg, #f43f5e, #9f1239); }
        .bg-grad-slate   { background: linear-gradient(135deg, #64748b, #334155); }

        /* ---- Filter pill ---- */
        .filter-card .form-label { font-size: .72rem; font-weight: 600; letter-spacing: .3px; text-transform: uppercase; color: var(--tblr-secondary); }
        .filter-card .form-select, .filter-card .form-control { border-radius: 8px; }

        /* ---- Login ---- */
        .auth-shell {
            min-height: 100vh;
            background: radial-gradient(1200px 500px at 50% -100px, #6366f1 0%, #4338ca 55%, #1e1b4b 100%);
            display: flex; align-items: center; justify-content: center; padding: 2rem;
        }
        .auth-card {
            width: 100%; max-width: 420px; border: 0;
            box-shadow: 0 25px 60px -15px rgba(0,0,0,.35);
            border-radius: 16px;
        }
    </style>

    @stack('styles')
</head>
<body>
<div class="page">

    @auth
    <header class="navbar navbar-expand-md d-print-none sticky-top" data-bs-theme="dark"
            style="background: #11141c; border-bottom: 1px solid rgba(255,255,255,.05);">
        <div class="container-xl">
            <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3 mb-0">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-white text-decoration-none">
                    <span class="d-inline-flex align-items-center justify-content-center me-2"
                          style="width:34px; height:34px; border-radius:9px;
                                 background: linear-gradient(135deg,#6366f1,#4338ca);">
                        <i class="ti ti-chart-pie-filled" style="font-size:18px;"></i>
                    </span>
                    <span class="fw-bold">{{ config('app.name') }}</span>
                </a>
            </h1>
            <div class="navbar-nav flex-row order-md-last">
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white-50 small d-none d-sm-inline">
                        <i class="ti ti-user-circle me-1"></i>{{ auth()->user()->name }}
                    </span>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-light">
                            <i class="ti ti-logout me-1"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>
    @endauth

    <div class="page-wrapper">
        @yield('content')
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
@stack('scripts')
</body>
</html>
