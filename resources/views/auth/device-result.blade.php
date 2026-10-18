@extends('layouts.auth')

@section('title', 'Device connection')

@section('content')
    <h1 class="ps-title auth__title">Device connection</h1>
    <div class="ps-frame auth__frame">
        <div class="ps-frame__panel auth__center">
            <p class="auth__lead">{{ $message }}</p>
            <a class="ps-btn" href="https://cloud.plainshow.se/">Open Cloud</a>
        </div>
    </div>
@endsection
