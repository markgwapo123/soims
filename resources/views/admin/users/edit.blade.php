@extends('layouts.app')

@section('title', 'Edit User | SOIMS')

@section('content')
    <x-ui.card class="soims-card--medium soims-users__form-card">
        <header class="soims-users__form-heading">
            <p class="soims-users__eyebrow">Administration / Access</p>
            <h1 class="soims-title">Edit user</h1>
            <p class="soims-lead">Update account details or change the assigned role.</p>
        </header>
        @if (session('status'))
            <p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>
        @endif
        <form class="soims-users__form" method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf @method('PUT')
            <section class="soims-users__form-section" aria-labelledby="user-account-title">
                <h2 id="user-account-title" class="soims-users__section-title">Account details</h2>
                <div class="soims-users__fields">
                    <x-ui.input label="Full name" name="name" value="{{ old('name', $user->name) }}" required />
                    <x-ui.input label="Username" name="username" value="{{ $user->username }}" disabled />
                    <x-ui.input label="Email" name="email" type="email" value="{{ old('email', $user->email) }}" />
                    <x-ui.input label="New password" name="password" type="password" autocomplete="new-password" />
                    <x-ui.input label="Confirm new password" name="password_confirmation" type="password" autocomplete="new-password" />
                </div>
            </section>
            <section class="soims-users__form-section" aria-labelledby="user-access-title">
                <h2 id="user-access-title" class="soims-users__section-title">Organizational access</h2>
                <div class="soims-users__fields">
                    <div class="soims-field">
                        <label class="soims-label" for="department_id">Department</label>
                        <select class="soims-input" id="department_id" name="department_id">
                            <option value="">None</option>
                            @foreach ($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id', $user->department_id) === (string) $department->id)>{{ $department->name }} ({{ str_replace('_', ' ', $department->type) }})</option>@endforeach
                        </select>
                        @error('department_id') <div class="soims-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="soims-field">
                        <label class="soims-label" for="office_id">Office</label>
                        <select class="soims-input" id="office_id" name="office_id">
                            <option value="">None</option>
                            @foreach ($offices as $office)<option value="{{ $office->id }}" @selected((string) old('office_id', $user->office_id) === (string) $office->id)>{{ $office->name }}</option>@endforeach
                        </select>
                        @error('office_id') <div class="soims-error">{{ $message }}</div> @enderror
                    </div>
                    <div class="soims-field">
                        <label class="soims-label" for="role_key">Role</label>
                        <select class="soims-input" id="role_key" name="role_key" required>
                            @foreach ($roles as $role)<option value="{{ $role->key }}" @selected($user->roles->contains('key', $role->key))>{{ $role->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </section>
            <div class="soims-users__actions"><x-ui.button type="submit">Save changes</x-ui.button></div>
        </form>
        <form method="POST" action="{{ route('admin.users.password', $user) }}" class="soims-danger-form soims-users__danger-form">
            @csrf @method('PUT')
            <h2 class="soims-section-title">Reset password</h2>
            <x-ui.input label="New password" name="password" type="password" required autocomplete="new-password" />
            <x-ui.input label="Confirm new password" name="password_confirmation" type="password" required autocomplete="new-password" />
            <x-ui.button type="submit" variant="secondary">Reset password</x-ui.button>
        </form>
        @if ($user->is_active)
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="soims-danger-form soims-users__danger-form">
                @csrf @method('DELETE')
                <x-ui.button type="submit" variant="danger">Deactivate account</x-ui.button>
            </form>
        @else
            <form method="POST" action="{{ route('admin.users.activate', $user) }}" class="soims-danger-form soims-users__danger-form">
                @csrf
                <x-ui.button type="submit">Activate account</x-ui.button>
            </form>
        @endif
    </x-ui.card>
@endsection
