<!DOCTYPE html>
<html lang="en" class="light-style customizer-hide" data-assets-path="{{ asset('assets') }}/"
      data-template="vertical-menu-template-free">
<head>
    @include('layouts.partials.head')
</head>
<body>
    <div class="container-xxl">
        @yield('content')
    </div>

    @include('layouts.partials.scripts')
</body>
</html>
