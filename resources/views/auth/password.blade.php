@extends('layouts.app')

@section('title', 'Change password | SOIMS')
@section('main-class', 'soims-main--center')

@section('content')
    <x-ui.card class="soims-card--narrow">
        <h1 class="soims-title">Change password</h1>
        <p class="soims-lead">Verify your current password before choosing a new one.</p>
        <form method="POST" action="{{ route('account.password.update') }}">
            @csrf @method('PUT')
            <x-ui.input label="Current password" name="current_password" type="password" required autocomplete="current-password" />
            <x-ui.input label="New password" name="password" type="password" required autocomplete="new-password" />
            <x-ui.input label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />
            <x-ui.button type="submit">Change password</x-ui.button>
        </form>
    </x-ui.card>
@endsection
