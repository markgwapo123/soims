@extends('layouts.app')

@section('title', 'Property Returns | SOIMS')

@section('content')
    <section class="soims-property-returns">
        <header class="soims-property-returns__heading">
            <div>
                <p class="soims-property-returns__eyebrow">Accountability / Returns</p>
                <h1 class="soims-title">Property returns</h1>
                <p class="soims-lead">Track returned assets from draft through final disposition.</p>
            </div>
            <a class="soims-button soims-button--primary" href="{{ route('property-returns.create') }}">New PRS</a>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        <div class="soims-property-returns__list-heading">
            <h2 class="soims-section-title">Return slips</h2>
            <span>{{ $returns->total() }} {{ $returns->total() === 1 ? 'slip' : 'slips' }}</span>
        </div>
        <div class="soims-table-wrap">
            <table class="soims-table soims-property-returns__table">
                <thead><tr><th>PRS no.</th><th>Purpose</th><th>Items</th><th>Received</th><th>Returned by</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($returns as $propertyReturn)
                        <tr>
                            <td class="soims-property-returns__number">{{ $propertyReturn->prs_no }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $propertyReturn->purpose)) }}</td>
                            <td>{{ $propertyReturn->items_count }}</td>
                            <td>{{ $propertyReturn->received_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $propertyReturn->returner?->name ?? '-' }}</td>
                            <td><span class="soims-property-returns__status is-{{ $propertyReturn->status }}">{{ ucfirst($propertyReturn->status) }}</span></td>
                            <td><a class="soims-property-returns__view" href="{{ route('property-returns.show', $propertyReturn) }}">View PRS</a></td>
                        </tr>
                    @empty
                        <tr><td class="soims-property-returns__empty" colspan="7">No property returns recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $returns->links() }}
    </section>
@endsection