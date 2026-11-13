@extends('layouts.guest')
@section('title', 'Login')

@push('styles')
{{-- Sneat keeps the auth layout out of core.css: this is what caps the card
     at 460px and centres it in the viewport. --}}
<link rel="stylesheet" href="{{ asset('assets/vendor/css/pages/page-auth.css') }}">
@endpush

@section('content')
    <div class="authentication-wrapper authentication-basic container-p-y">
        <div class="authentication-inner">

            <div class="card px-sm-6 px-0">
                <div class="card-body">

                    <div class="app-brand justify-content-center mb-5">
                        <a href="{{ route('login') }}" class="app-brand-link gap-2">
                            <span class="app-brand-logo demo text-primary">
                                <i class="bx bx-bar-chart-alt-2" style="font-size:2.25rem;"></i>
                            </span>
                            <span class="app-brand-text demo text-heading fw-bold ms-1">{{ config('app.name') }}</span>
                        </a>
                    </div>

                    <h4 class="mb-1">Welcome back 👋</h4>
                    <p class="mb-5">Sign in to see every brand in one place.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bx bx-error-circle me-2"></i>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-5">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="{{ old('email') }}" placeholder="you@nexthealth.com.pk"
                                   autofocus required>
                        </div>

                        <div class="mb-5 form-password-toggle">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group input-group-merge">
                                <input type="password" id="password" class="form-control" name="password"
                                       placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                       aria-describedby="password" required>
                                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
                            </div>
                        </div>

                        <div class="mb-5 d-flex justify-content-between align-items-center">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>

                        <button class="btn btn-primary d-grid w-100" type="submit">Sign in</button>
                    </form>

                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script>
// Sneat's password-toggle markup needs the eye icon wired up.
document.querySelectorAll('.form-password-toggle .input-group-text').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = btn.parentElement.querySelector('input');
        const icon  = btn.querySelector('i');
        const show  = input.type === 'password';
        input.type  = show ? 'text' : 'password';
        icon.classList.toggle('bx-hide', !show);
        icon.classList.toggle('bx-show', show);
    });
});
</script>
@endpush
