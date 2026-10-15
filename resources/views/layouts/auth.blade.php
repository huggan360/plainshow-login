@php($uiVersion = \Composer\InstalledVersions::getReference('plainshow/kit') ? substr(\Composer\InstalledVersions::getReference('plainshow/kit'), 0, 8) : 'dev')
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="dark light">
    <title>@yield('title', 'Sign in') | PlainShow</title>
    <link rel="icon" href="/ui/brand/favicon.ico" sizes="any">
    <link rel="icon" href="/ui/brand/plainshow-icon.webp" type="image/webp">
    <link rel="apple-touch-icon" href="/ui/brand/apple-touch-icon.png">
    <link rel="stylesheet" href="/ui/css/boxicons.css?v={{ $uiVersion }}">
    <link rel="stylesheet" href="/ui/css/plainshow.css?v={{ $uiVersion }}">
    <link rel="stylesheet" href="/css/login.css">
    <script src="/ui/js/plainshow.js?v={{ $uiVersion }}" defer></script>
    @stack('head')
</head>
<body class="ps auth">
    <div class="auth__backdrop" aria-hidden="true"></div>
    <main class="auth__main">
        <a class="ps-brand auth__brand" href="{{ config('plainshow.core_url') }}">
            <img class="ps-brand__icon" src="/ui/brand/plainshow-icon.webp" alt="">
            <span class="ps-brand__word">plain<span class="ps-brand__show">show</span></span>
        </a>
        @yield('content')
        <footer class="auth__foot">
            <span>PlainShow</span>
            <button type="button" class="auth__theme" data-ps-theme-toggle><i class="bx bx-adjust" aria-hidden="true"></i><span data-ps-theme-label>Light theme</span></button>
        </footer>
    </main>
    @stack('scripts')
</body>
</html>
