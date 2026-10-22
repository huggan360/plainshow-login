@extends('layouts.auth')

@section('title', __('Choose a password'))

@section('content')
    <h1 class="ps-title auth__title">{{ __('Choose a new password') }}</h1>

    <div class="ps-frame auth__frame" data-glow="warm">
        <div class="ps-frame__panel">
            @if (! $valid)
                <div class="ps-alert ps-alert--error" role="alert"><i class="bx bx-error-circle"></i><span>{{ __('This reset link has expired or was already used. Ask an administrator for a new one.') }}</span></div>
            @else
                <p class="auth__lead">{!! __('For :username. Use at least 10 characters.', ['username' => '<strong>'.e($username).'</strong>']) !!}</p>
                <form method="post" action="{{ route('password.update') }}" novalidate>
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="username" value="{{ $username }}">
                    <input type="text" name="login" value="{{ $username }}" autocomplete="username" class="ps-sr-only" tabindex="-1" aria-hidden="true">
                    <div class="ps-field">
                        <label class="ps-label" for="password">{{ __('New password') }}</label>
                        <input class="ps-input" id="password" name="password" type="password" autocomplete="new-password" minlength="10" required autofocus @error('password') aria-invalid="true" @enderror>
                        @error('password')<p class="ps-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="ps-field">
                        <label class="ps-label" for="password_confirmation">{{ __('Repeat it') }}</label>
                        <input class="ps-input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </div>
                    <button type="submit" class="ps-btn ps-btn--primary ps-btn--lg ps-btn--block">{{ __('Save password') }}</button>
                </form>
            @endif
        </div>
    </div>
@endsection
