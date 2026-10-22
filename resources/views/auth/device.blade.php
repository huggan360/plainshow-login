@extends('layouts.auth')

@section('title', __('Connect a device'))

@section('content')
    <h1 class="ps-title auth__title">{{ __('Connect your device') }}</h1>
    <div class="ps-frame auth__frame">
        <div class="ps-frame__panel">
            <p class="auth__lead">{{ __('Allow :client to access Cloud as :account?', ['client' => $client, 'account' => $account]) }}</p>
            <form method="post" action="{{ $action }}">
                <input type="hidden" name="stateToken" value="{{ $state }}">
                <input type="hidden" name="requesttoken" value="{{ $token }}">
                <button class="ps-btn ps-btn--primary ps-btn--block" type="submit">{{ __('Connect device') }}</button>
            </form>
            <p class="auth__hint"><a href="{{ $server }}/">{{ __('Cancel and return to Cloud') }}</a></p>
        </div>
    </div>
@endsection
