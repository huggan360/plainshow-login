@extends('layouts.auth')

@section('title', __('Signed out'))

@push('head')
    <noscript><meta http-equiv="refresh" content="3;url={{ $return }}"></noscript>
@endpush

@section('content')
    <h1 class="ps-title auth__title">{{ __('Signing you out') }}</h1>

    <div class="ps-frame auth__frame">
        <div class="ps-frame__panel auth__center">
            <span class="ps-loader" aria-hidden="true"></span>
            <p class="auth__lead">{{ __('Closing your session in every PlainShow app.') }}</p>
            <a class="ps-btn" href="{{ $return }}" id="continue">{{ __('Continue') }}</a>
        </div>
    </div>

    @foreach ($frames as $frame)
        <iframe class="auth__frame-hidden" src="{{ $frame }}" title="{{ __('Sign out') }}" data-signout></iframe>
    @endforeach
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        (function () {
            var frames = document.querySelectorAll('[data-signout]');
            var left = frames.length;
            var target = document.getElementById('continue').href;
            var go = function () { window.location.replace(target); };
            if (!left) { go(); return; }
            frames.forEach(function (frame) {
                frame.addEventListener('load', function () { if (--left === 0) go(); });
            });
            window.setTimeout(go, 4000);
        })();
    </script>
@endpush
