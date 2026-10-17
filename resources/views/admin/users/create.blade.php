@extends('layouts.app')

@section('title', 'Add User | SOIMS')

@section('content')
    <x-ui.card class="soims-card--medium soims-users__form-card">
        <header class="soims-users__form-heading">
            <p class="soims-users__eyebrow">Administration / Access</p>
            <h1 class="soims-title">Add user</h1>
            <p class="soims-lead">Create an account and assign its initial role.</p>
        </header>
        <form class="soims-users__form" method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <section class="soims-users__form-section" aria-labelledby="user-account-title">
                <h2 id="user-account-title" class="soims-users__section-title">Account details</h2>
                <div class="soims-users__fields">
                    <x-ui.input label="Full name" name="name" value="{{ old('name') }}" required />
                    <x-ui.input label="Username" name="username" value="{{ old('username') }}" required />
                    <x-ui.input label="Email" name="email" type="email" value="{{ old('email') }}" required />
                    <x-ui.input label="Password" name="password" type="password" required autocomplete="new-password" />
                    <x-ui.input label="Confirm password" name="password_confirmation" type="password" required autocomplete="new-password" />
                </div>
            </section>
            <section class="soims-users__form-section" aria-labelledby="user-access-title">
                <h2 id="user-access-title" class="soims-users__section-title">Organizational access</h2>
                <div class="soims-users__fields">
                    <div class="soims-field">
                        <label class="soims-label" for="department_id">Department</label>
                        <select class="soims-input" id="department_id" name="department_id">
                            <option value="">None</option>
                            @foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id') === (string) $department->id)>{{ $department->name }} ({{ str_replace('_', ' ', $department->type) }})</option>@endforeach
                        </select>
                        @error('department_id') <div class="soims-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="soims-field">
                        <label class="soims-label" for="office_id">Office</label>
                        <select class="soims-input" id="office_id" name="office_id">
                            <option value="">None</option>
                            @foreach ($offices as $office)<option value="{{ $office->id }}" @selected((string) old('office_id') === (string) $office->id)>{{ $office->name }}</option>@endforeach
                        </select>
                        @error('office_id') <div class="soims-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="soims-field">
                        <label class="soims-label" for="role_key">Role</label>
                        <select class="soims-input" id="role_key" name="role_key" required>
                            <option value="">Select a role</option>
                            @foreach ($roles as $role)<option value="{{ $role->key }}" @selected(old('role_key') === $role->key)>{{ $role->name }}</option>@endforeach
                        </select>
                        @error('role_key') <div class="soims-error">{{ $message }}</div> @enderror
                    </div>
                </div>
            </section>
            <div class="soims-users__actions"><x-ui.button type="submit">Create account</x-ui.button><a class="soims-button soims-button--secondary" href="{{ route('admin.users.index') }}">Cancel</a></div>
        </form>
    </x-ui.card>
@endsection
