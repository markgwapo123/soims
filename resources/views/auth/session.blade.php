@extends('layouts.app')

@section('title', 'Authenticated session | SOIMS')
@section('main-class', 'soims-main--center')

@section('content')
    <x-ui.card class="soims-card--medium">
        <h1 class="soims-title">Authenticated session</h1>
        <p class="soims-lead">The Laravel session guard is active. Business dashboards and role-specific modules are intentionally deferred to later phases.</p>
        <div class="soims-identity">
            <strong>{{ auth()->user()->name }}</strong>
            {{ auth()->user()->email }}
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="secondary">Sign out</x-ui.button>
        </form>
    </x-ui.card>
@endsection
