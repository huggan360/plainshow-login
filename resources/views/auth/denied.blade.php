@extends('layouts.auth')

@section('title', __('No access'))

@section('content')
    <h1 class="ps-title auth__title">{{ __('No access to :service', ['service' => $service]) }}</h1>

    <div class="ps-frame auth__frame">
        <div class="ps-frame__panel">
            <p class="auth__lead">{{ __('Your account cannot use :service right now. An administrator can give you access, or the service may be switched off.', ['service' => $service]) }}</p>
            <div class="ps-inline">
                <a class="ps-btn ps-btn--primary" href="{{ config('plainshow.core_url') }}">{{ __('Go to the dashboard') }}</a>
                <a class="ps-btn" href="{{ route('logout') }}">{{ __('Sign out') }}</a>
            </div>
        </div>
    </div>
@endsection
