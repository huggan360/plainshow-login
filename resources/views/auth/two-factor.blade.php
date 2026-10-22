@extends('layouts.auth')

@section('title', __('Two-factor authentication'))

@section('content')
    <h1 class="ps-title auth__title">{{ __('Confirm it is you') }}</h1>

    <div class="ps-frame auth__frame" data-glow="accent">
        <div class="ps-frame__panel">
            <p class="auth__lead">{{ __('Enter the 6-digit code from your authenticator app, or one of your recovery codes.') }}</p>
            <form method="post" action="{{ url('/two-factor') }}" novalidate>
                @csrf
                <div class="ps-field">
                    <label class="ps-label" for="code">{{ __('Code') }}</label>
                    <input class="ps-input ps-input--code" id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="32" required autofocus @error('code') aria-invalid="true" @enderror>
                    @error('code')<p class="ps-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="ps-btn ps-btn--primary ps-btn--lg ps-btn--block"><span>{{ __('Verify') }}</span><i class="bx bx-right-arrow-alt" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>

    <p class="auth__hint"><a href="{{ route('login') }}">{{ __('Use a different account') }}</a></p>
@endsection
