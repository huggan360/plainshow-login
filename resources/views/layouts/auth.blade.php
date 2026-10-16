@php($uiVersion = \Composer\InstalledVersions::getReference('plainshow/kit') ? substr(\Composer\InstalledVersions::getReference('plainshow/kit'), 0, 8) : 'dev')
<!doctype html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="dark light">
    <title>@yield('title', 'Sign in') | PlainShow</title>
    <link rel="icon" href="/ui/brand/favicon.ico?v={{ $uiVersion }}" sizes="any">
    <link rel="icon" href="/ui/brand/plainshow-icon.webp?v={{ $uiVersion }}" type="image/webp">
    <link rel="apple-touch-icon" href="/ui/brand/apple-touch-icon.png?v={{ $uiVersion }}">
    <link rel="preload" href="/ui/fonts/source-serif-4-latin-wght.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/ui/fonts/outfit-latin-wght.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/ui/css/boxicons.css?v={{ $uiVersion }}">
    <link rel="stylesheet" href="/ui/css/plainshow.css?v={{ $uiVersion }}">
    <link rel="stylesheet" href="/css/login.css?v={{ $uiVersion }}">
    <script src="/ui/js/plainshow.js?v={{ $uiVersion }}" defer></script>
    <script src="/ui/js/backdrop.js?v={{ $uiVersion }}" defer></script>
    @stack('head')
</head>
<body class="ps auth">
    <canvas class="ps-backdrop" aria-hidden="true"></canvas>
    <main class="auth__main">
        <a class="ps-brand auth__brand" href="{{ config('plainshow.core_url') }}">
            <img class="ps-brand__logo ps-brand__logo--lg" src="/ui/brand/plainshow-logo.webp?v={{ $uiVersion }}" alt="PlainShow" width="159" height="52">
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
