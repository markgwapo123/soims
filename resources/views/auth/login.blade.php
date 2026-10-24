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
            <div class="soims-field">
                <label class="soims-label" for="password">Password</label>
                <div class="soims-password-wrap">
                    <input class="soims-input soims-password-input" id="password" name="password" type="password" required autocomplete="current-password" />
                    <button class="soims-password-toggle" id="toggle-password" type="button" aria-label="Show password" aria-pressed="false" aria-controls="password">
                        <svg class="soims-password-eye" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                            <circle cx="12" cy="12" r="3"/>
                            <path class="soims-password-slash" d="M3 3l18 18" hidden/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <div class="soims-error">{{ $message }}</div>
                @enderror
            </div>
            <label class="soims-check"><input name="remember" type="checkbox" value="1" @checked(old('remember'))> Remember this device</label>
            <x-ui.button type="submit">Sign in</x-ui.button>
        </form>
    </x-ui.card>
<style>
    .soims-password-wrap { position: relative; }
    .soims-password-input { width: 100%; padding-right: 54px; }
    .soims-password-toggle {
        position: absolute; top: 50%; right: 12px; transform: translateY(-50%);
        display: inline-flex; align-items: center; justify-content: center;
        width: 38px; height: 38px; padding: 0; border: 0;
        border-radius: 8px; background: transparent; color: #526b84; cursor: pointer;
    }
    .soims-password-toggle:hover { background: rgba(33, 137, 195, .09); color: #176ba0; }
    .soims-password-toggle:focus-visible { outline: 2px solid #2189c3; outline-offset: 2px; }
    .soims-password-slash[hidden] { display: none; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const toggle = document.getElementById('toggle-password');
    const slash = toggle.querySelector('.soims-password-slash');
    toggle.addEventListener('click', function () {
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(visible));
        toggle.setAttribute('aria-label', visible ? 'Hide password' : 'Show password');
        slash.hidden = !visible;
    });
});
</script>
@endsection
