@extends('layouts.app')

@section('title', 'Modify Confirmed Requests | SOIMS')

@section('content')
    <div class="soims-card">
        <h1 class="soims-title">Modify confirmed requests</h1>
        <p class="soims-lead">Confirmed requests whose items or quantities you may change.</p>
        <div class="soims-table-wrap">
            <table class="soims-table">
                <thead><tr><th>Request no.</th><th>Requested by</th><th>Department</th><th>Type</th><th>Items</th><th>Confirmed</th><th></th></tr></thead>
                <tbody>
                    @forelse ($requests as $supplyRequest)
                        <tr>
                            <td>{{ $supplyRequest->request_no }}</td>
                            <td>{{ $supplyRequest->requestor->name }}</td>
                            <td>{{ $supplyRequest->department?->name }}</td>
                            <td>{{ $supplyRequest->request_type === 'academic' ? 'Academic' : 'Non-Academic' }}</td>
                            <td>{{ $supplyRequest->items_count }}</td>
                            <td>{{ $supplyRequest->confirmed_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('modifications.edit', $supplyRequest) }}">Modify</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No confirmed requests available to modify.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->links() }}
    </div>
@endsection
