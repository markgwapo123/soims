@extends('layouts.app')

@section('title', 'User Management | SOIMS')

@section('content')
    <section class="soims-users">
        <header class="soims-users__heading">
            <div>
                <p class="soims-users__eyebrow">Administration / Access</p>
                <h1 class="soims-title">User management</h1>
                <p class="soims-lead">Manage SOIMS accounts, assigned roles, and account status.</p>
            </div>
            <a class="soims-button soims-button--primary" href="{{ route('admin.users.create') }}">Add user</a>
        </header>
        @if (session('status'))
            <p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>
        @endif
        <div class="soims-users__register">
            <div class="soims-users__list-heading">
                <h2 class="soims-section-title">Accounts</h2>
                <span>{{ $users->total() }} {{ $users->total() === 1 ? 'account' : 'accounts' }}</span>
            </div>
            <div class="soims-table-wrap">
                <table class="soims-table soims-users__table">
                    <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></td>
                                <td class="soims-users__username">{{ $user->username }}</td>
                                <td><span class="soims-users__role">{{ $user->roles->pluck('name')->join(', ') ?: 'Unassigned' }}</span></td>
                                <td><span class="soims-users__status {{ $user->is_active ? 'is-active' : 'is-inactive' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td><a class="soims-users__edit" href="{{ route('admin.users.edit', $user) }}">Edit account</a></td>
                            </tr>
                        @empty
                            <tr><td class="soims-users__empty" colspan="5">No user accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>
    </section>
@endsection
