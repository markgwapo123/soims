@extends('layouts.app')

@section('title', 'My Requests | SOIMS')

@section('content')
    <div class="soims-card soims-my-requests">
        <header class="soims-requests-heading">
            <div>
                <p class="soims-requests-eyebrow">Workspace / Requests</p>
                <h1 class="soims-title">My supply requests</h1>
            </div>
            <div class="soims-requests-heading__actions">
                <span class="soims-requests-count">{{ $requests->total() }} {{ $requests->total() === 1 ? 'request' : 'requests' }}</span>
                <a class="soims-button soims-button--primary" href="{{ route('requests.create') }}">New request</a>
            </div>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        <div class="soims-table-wrap soims-request-table-wrap">
            <table class="soims-table soims-request-table">
                <thead><tr><th>Request no.</th><th>Type</th><th>Items</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($requests as $supplyRequest)
                        <tr>
                            <td><strong class="soims-request-number">{{ $supplyRequest->request_no }}</strong></td>
                            <td>{{ $supplyRequest->request_type === 'academic' ? 'Academic' : 'Non-Academic' }}</td>
                            <td>{{ $supplyRequest->items_count }} {{ $supplyRequest->items_count === 1 ? 'item' : 'items' }}</td>
                            <td><time class="soims-request-date" datetime="{{ $supplyRequest->submitted_at?->toIso8601String() }}">{{ $supplyRequest->submitted_at?->format('M j, Y · H:i') }}</time></td>
                            <td><span class="soims-request-status soims-request-status--{{ $supplyRequest->status }}">{{ ucfirst(str_replace('_', ' ', $supplyRequest->status)) }}</span></td>
                            <td><a class="soims-request-view" href="{{ route('requests.show', $supplyRequest) }}">View details</a></td>
                        </tr>
                    @empty
                        <tr><td class="soims-request-empty" colspan="6">
                            <h2>No requests yet</h2>
                            <p>Your submitted requests will appear here.</p>
                            <a class="soims-button soims-button--primary" href="{{ route('requests.create') }}">Create your first request</a>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
