@extends('layouts.app')

@section('title', 'Sign in | SOIMS')
@section('main-class', 'soims-main--center')

@section('content')
    <x-ui.card class="soims-card--narrow soims-login">
        <header class="soims-login__heading">
            <p class="soims-login__eyebrow">Supply Office / Authorized access</p>
            <h1 class="soims-title">Sign in to SOIMS</h1>
            <p class="soims-lead">Use your authorized account to continue.</p>
        </header>
        <form class="soims-login__form" method="POST" action="{{ route('login.store') }}">
            @csrf
            <x-ui.input label="Username or email" name="identifier" value="{{ old('identifier') }}" required autofocus autocomplete="username" />
            <x-ui.input label="Password" name="password" type="password" required autocomplete="current-password" />
            <label class="soims-check"><input name="remember" type="checkbox" value="1" @checked(old('remember'))> Remember this device</label>
            <x-ui.button type="submit">Sign in</x-ui.button>
        </form>
    </x-ui.card>
@endsection
