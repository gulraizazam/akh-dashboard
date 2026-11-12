@extends('layouts.app')
@section('title', 'Sign in')

@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="d-inline-flex align-items-center justify-content-center mb-3"
                      style="width:56px; height:56px; border-radius:14px;
                             background: linear-gradient(135deg,#6366f1,#4338ca);">
                    <i class="ti ti-chart-pie-filled text-white" style="font-size:28px;"></i>
                </span>
                <h2 class="h3 mb-1">Welcome back</h2>
                <div class="text-secondary small">Sign in to the {{ config('app.name') }}</div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    <i class="ti ti-alert-circle me-1"></i> {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input type="email" name="email" class="form-control form-control-lg"
                           value="{{ old('email') }}" placeholder="you@akg.com" required autofocus>
                </div>

                <div class="mb-2">
                    <label class="form-label d-flex align-items-center">
                        Password
                    </label>
                    <input type="password" name="password" class="form-control form-control-lg"
                           placeholder="••••••••" required>
                </div>

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <label class="form-check m-0">
                        <input type="checkbox" name="remember" class="form-check-input">
                        <span class="form-check-label small">Remember me</span>
                    </label>
                </div>

                <button class="btn btn-primary w-100 btn-lg" type="submit">
                    <i class="ti ti-login me-1"></i> Sign in
                </button>
            </form>
        </div>
        <div class="card-footer text-center text-secondary small bg-transparent border-0 pb-4">
            &copy; {{ date('Y') }} {{ config('app.name') }}
        </div>
    </div>
</div>
@endsection
