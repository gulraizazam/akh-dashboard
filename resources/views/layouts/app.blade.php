<!DOCTYPE html>
<html
    lang="en"
    class="layout-menu-fixed layout-compact"
    data-assets-path="{{ asset('assets') }}/"
    data-template="vertical-menu-template-free">
<head>
    @include('layouts.partials.head')
</head>
<body>

<div class="layout-wrapper layout-content-navbar">
    <div class="layout-container">

        {{-- ---------- Sidebar ---------- --}}
        <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
            <div class="app-brand demo">
                <a href="{{ route('dashboard') }}" class="app-brand-link">
                    <span class="app-brand-logo demo">
                        <span class="text-primary"><i class="bx bx-bar-chart-alt-2" style="font-size:1.75rem;"></i></span>
                    </span>
                    <span class="app-brand-text demo menu-text fw-bold ms-2">{{ config('app.name') }}</span>
                </a>
                <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
                    <i class="bx bx-chevron-left bx-sm align-middle"></i>
                </a>
            </div>

            <div class="menu-inner-shadow"></div>

            <ul class="menu-inner py-1">
                <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="menu-link">
                        <i class="menu-icon tf-icons bx bx-home-smile"></i>
                        <div>Analytics</div>
                    </a>
                </li>

                <li class="menu-header small text-uppercase"><span class="menu-header-text">Brands</span></li>
                @foreach (config('brands.items') as $key => $brand)
                    <li class="menu-item {{ request('brand') === $key ? 'active' : '' }}">
                        <a href="{{ route('dashboard', ['brand' => $key, 'period' => request('period', 'thismonth')]) }}" class="menu-link">
                            <i class="menu-icon tf-icons bx bx-buildings"></i>
                            <div>{{ $brand['label'] }}</div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </aside>

        <div class="layout-page">

            {{-- ---------- Navbar ---------- --}}
            <nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
                 id="layout-navbar">
                <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 d-xl-none">
                    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
                        <i class="bx bx-menu bx-md"></i>
                    </a>
                </div>

                <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
                    <div class="navbar-nav align-items-center me-auto">
                        <div class="nav-item d-flex align-items-center">
                            <span class="fw-semibold">@yield('title', 'Dashboard')</span>
                        </div>
                    </div>

                    <ul class="navbar-nav flex-row align-items-center ms-md-auto">
                        <li class="nav-item navbar-dropdown dropdown-user dropdown">
                            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                                <div class="avatar avatar-online">
                                    <span class="avatar-initial rounded-circle bg-label-primary">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    </span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <div class="dropdown-item-text">
                                        <span class="fw-semibold d-block">{{ auth()->user()->name }}</span>
                                        <small class="text-muted">{{ auth()->user()->email }}</small>
                                    </div>
                                </li>
                                <li><div class="dropdown-divider my-1"></div></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="bx bx-power-off bx-md me-3"></i><span>Log Out</span>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>

            {{-- ---------- Content ---------- --}}
            <div class="content-wrapper">
                <div class="container-xxl flex-grow-1 container-p-y">
                    @yield('content')
                </div>

                <footer class="content-footer footer bg-footer-theme">
                    <div class="container-xxl d-flex flex-wrap justify-content-between py-4 flex-md-row flex-column">
                        <div class="mb-2 mb-md-0">
                            &copy; {{ date('Y') }} {{ config('app.name') }}
                        </div>
                    </div>
                </footer>

                <div class="content-backdrop fade"></div>
            </div>
        </div>
    </div>

    <div class="layout-overlay layout-menu-toggle"></div>
</div>

@include('layouts.partials.scripts')
</body>
</html>
