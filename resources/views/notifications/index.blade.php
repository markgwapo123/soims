@extends('layouts.app')

@section('title', 'Notifications | SOIMS')

@section('content')
    <div class="soims-card soims-notifications">
        <header class="soims-notifications__heading">
            <div>
                <p class="soims-notifications__eyebrow">Workspace / Inbox</p>
                <h1 class="soims-title">Notifications</h1>
            </div>
            <div class="soims-notifications__summary">
                <span class="soims-notifications__unread-count">{{ $unreadCount }} unread</span>
                <span class="soims-notifications__total-count">{{ $notifications->total() }} total</span>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        <x-ui.button type="submit" variant="secondary">Mark all read</x-ui.button>
                    </form>
                @endif
            </div>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        <ul class="soims-notification-list">
            @forelse ($notifications as $notification)
                <li class="soims-notification {{ $notification->is_read_by_viewer ? 'is-read' : 'is-unread' }}">
                    <div class="soims-notification__content">
                        <div class="soims-notification__heading">
                            <h2>{{ $notification->title }}</h2>
                            <span class="soims-notification__status {{ $notification->is_read_by_viewer ? 'is-read' : 'is-unread' }}">
                                <span aria-hidden="true"></span>{{ $notification->is_read_by_viewer ? 'Read' : 'Unread' }}
                            </span>
                        </div>
                        <p class="soims-notification__message">{{ $notification->message }}</p>
                        <div class="soims-notification__footer">
                            <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->format('M j, Y · H:i') }}</time>
                            <div class="soims-notification__actions">
                                @if ($notification->related_url)<a href="{{ $notification->related_url }}">Open request</a>@endif
                                @unless ($notification->is_read_by_viewer)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="secondary">Mark read</x-ui.button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li class="soims-notification-empty">
                    <span class="soims-notification-empty__mark" aria-hidden="true">✓</span>
                    <h2>You're all caught up</h2>
                    <p>You have no notifications right now.</p>
                </li>
            @endforelse
        </ul>
        {{ $notifications->links() }}
    </div>
@endsection