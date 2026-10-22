@extends('layouts.auth')

@section('title', __('Sign in'))

@section('content')
    @php($destination = \App\Support\Destination::fromIntended())
    <h1 class="ps-title auth__title">
        @if ($destination)
            {{ __('Continue to :name', ['name' => $destination['name']]) }}
        @else
            {{ __('Sign in to PlainShow') }}
        @endif
    </h1>

    <div class="ps-frame auth__frame" data-glow="{{ $destination['glow'] ?? 'accent' }}">
        <div class="ps-frame__panel">
            @if ($status)
                <div class="ps-alert ps-alert--success auth__alert" role="status"><i class="bx bx-check-circle"></i><span>{{ $status }}</span></div>
            @endif

            <form method="post" action="{{ url('/login') }}" novalidate>
                @csrf
                <div class="ps-field">
                    <label class="ps-label" for="login">{{ __('Username or email') }}</label>
                    <div class="ps-input-wrap">
                        <i class="bx bx-user" aria-hidden="true"></i>
                        <input class="ps-input" id="login" name="login" type="text" value="{{ old('login') }}" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus @error('login') aria-invalid="true" aria-describedby="login-error" @enderror>
                    </div>
                    @error('login')<p class="ps-error" id="login-error">{{ $message }}</p>@enderror
                </div>

                <div class="ps-field">
                    <div class="ps-between auth__label-row">
                        <label class="ps-label" for="password">{{ __('Password') }}</label>
                        <button type="button" class="auth__reveal" data-reveal="password" aria-controls="password" aria-pressed="false">{{ __('Show') }}</button>
                    </div>
                    <div class="ps-input-wrap">
                        <i class="bx bx-lock-alt" aria-hidden="true"></i>
                        <input class="ps-input" id="password" name="password" type="password" autocomplete="current-password" required @error('password') aria-invalid="true" @enderror>
                    </div>
                    @error('password')<p class="ps-error">{{ $message }}</p>@enderror
                </div>

                <div class="ps-between auth__options">
                    <label class="ps-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> {{ __('Keep me signed in') }}</label>
                </div>

                <button type="submit" class="ps-btn ps-btn--primary ps-btn--lg ps-btn--block"><span>{{ __('Continue') }}</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>

    <p class="auth__hint">{{ __('Forgot your password? Ask an administrator for a reset link.') }}</p>

    <ul class="auth__apps" aria-label="{{ __('Signs you in to') }}">
        <li><i class="bx bx-grid-alt" aria-hidden="true"></i>{{ __('Dashboard') }}</li>
        <li><i class="bx bx-news" aria-hidden="true"></i>Blog</li>
        <li><i class="bx bx-code-curly" aria-hidden="true"></i>LaTeX</li>
        <li><i class="bx bx-server" aria-hidden="true"></i>Web</li>
        <li><i class="bx bx-cloud" aria-hidden="true"></i>Cloud</li>
    </ul>
@endsection

@push('scripts')
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        document.querySelectorAll('[data-reveal]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.getAttribute('data-reveal'));
                var showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                button.textContent = showing ? @json(__('Show')) : @json(__('Hide'));
                button.setAttribute('aria-pressed', showing ? 'false' : 'true');
                input.focus();
            });
        });
    </script>
@endpush
