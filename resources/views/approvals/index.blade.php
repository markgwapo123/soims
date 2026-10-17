@extends('layouts.app')

@section('title', 'Request Approvals | SOIMS')

@section('content')
    <div class="soims-card soims-approvals">
        <header class="soims-approvals-heading">
            <div>
                <p class="soims-approvals-eyebrow">Workspace / Review queue</p>
                <h1 class="soims-title">Request approvals</h1>
            </div>
            <span class="soims-approvals-count">{{ $requests->total() }} {{ $requests->total() === 1 ? 'request' : 'requests' }}</span>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        <nav class="soims-approval-tabs" aria-label="Request status">
            <a class="{{ $status === 'submitted' ? 'is-active' : '' }}" href="{{ route('approvals.index') }}" @if ($status === 'submitted') aria-current="page" @endif>Pending</a>
            <a class="{{ $status === 'confirmed' ? 'is-active' : '' }}" href="{{ route('approvals.index', ['status' => 'confirmed']) }}" @if ($status === 'confirmed') aria-current="page" @endif>Confirmed</a>
            <a class="{{ $status === 'denied' ? 'is-active' : '' }}" href="{{ route('approvals.index', ['status' => 'denied']) }}" @if ($status === 'denied') aria-current="page" @endif>Denied</a>
        </nav>
        <div class="soims-table-wrap">
            <table class="soims-table soims-approval-table">
                <thead><tr><th>Request no.</th><th>Requested by</th><th>Department</th><th>Type</th><th>Items</th><th>Submitted</th><th></th></tr></thead>
                <tbody>
                    @forelse ($requests as $supplyRequest)
                        <tr>
                            <td><strong class="soims-approval-request-no">{{ $supplyRequest->request_no }}</strong></td>
                            <td>{{ $supplyRequest->requestor->name }}</td>
                            <td>{{ $supplyRequest->department?->name }}</td>
                            <td>{{ $supplyRequest->request_type === 'academic' ? 'Academic' : 'Non-Academic' }}</td>
                            <td>{{ $supplyRequest->items_count }} {{ $supplyRequest->items_count === 1 ? 'item' : 'items' }}</td>
                            <td><time class="soims-approval-date" datetime="{{ $supplyRequest->submitted_at?->toIso8601String() }}">{{ $supplyRequest->submitted_at?->format('M j, Y · H:i') }}</time></td>
                            <td><a class="soims-approval-open" href="{{ route('approvals.show', $supplyRequest) }}">{{ $status === 'submitted' ? 'Review' : 'View details' }}</a></td>
                        </tr>
                    @empty
                        <tr><td class="soims-approval-empty" colspan="7">
                            <h2>No {{ $status === 'submitted' ? 'pending' : $status }} requests</h2>
                            <p>{{ $status === 'submitted' ? 'This review queue is clear.' : 'There are no requests in this status yet.' }}</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
