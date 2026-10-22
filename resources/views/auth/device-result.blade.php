@extends('layouts.auth')

@section('title', __('Device connection'))

@section('content')
    <h1 class="ps-title auth__title">{{ __('Device connection') }}</h1>
    <div class="ps-frame auth__frame">
        <div class="ps-frame__panel auth__center">
            <p class="auth__lead">{{ $message }}</p>
            <a class="ps-btn" href="https://cloud.plainshow.se/">{{ __('Open Cloud') }}</a>
        </div>
    </div>
@endsection
