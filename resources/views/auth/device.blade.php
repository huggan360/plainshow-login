@extends('layouts.auth')

@section('title', 'Connect a device')

@section('content')
    <h1 class="ps-title auth__title">Connect your device</h1>
    <div class="ps-frame auth__frame">
        <div class="ps-frame__panel">
            <p class="auth__lead">Allow {{ $client }} to access Cloud as {{ $account }}?</p>
            <form method="post" action="{{ $action }}">
                <input type="hidden" name="stateToken" value="{{ $state }}">
                <input type="hidden" name="requesttoken" value="{{ $token }}">
                <button class="ps-btn ps-btn--primary ps-btn--block" type="submit">Connect device</button>
            </form>
            <p class="auth__hint"><a href="{{ $server }}/">Cancel and return to Cloud</a></p>
        </div>
    </div>
@endsection
