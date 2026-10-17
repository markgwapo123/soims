@extends('layouts.app')

@section('title', $propertyReturn->prs_no.' | SOIMS')

@section('content')
    <section class="soims-property-returns soims-property-returns--detail">
        <header class="soims-property-returns__heading">
            <div>
                <p class="soims-property-returns__eyebrow">Property Return Slip</p>
                <h1 class="soims-title">{{ $propertyReturn->prs_no }}</h1>
                <p class="soims-lead">{{ ucfirst(str_replace('_', ' ', $propertyReturn->purpose)) }} · {{ $propertyReturn->office_name }}</p>
            </div>
            <span class="soims-property-returns__status is-{{ $propertyReturn->status }}">{{ ucfirst($propertyReturn->status) }}</span>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        @error('prs')<div class="soims-error">{{ $message }}</div>@enderror
        <dl class="soims-property-returns__metadata">
            <div><dt>Office</dt><dd>{{ $propertyReturn->office_name }}</dd></div>
            <div><dt>Received</dt><dd>{{ $propertyReturn->received_at?->format('Y-m-d H:i') ?? '-' }}</dd></div>
            <div><dt>Returned by</dt><dd>{{ $propertyReturn->returner?->name ?? '-' }}</dd></div>
            <div><dt>Received from</dt><dd>{{ $propertyReturn->received_from ?? '-' }}</dd></div>
            <div><dt>Returned to</dt><dd>{{ $propertyReturn->returned_to ?? '-' }}</dd></div>
            <div><dt>End user</dt><dd>{{ $propertyReturn->end_user ?? '-' }}</dd></div>
        </dl>
        @if ($propertyReturn->certification)<p class="soims-property-returns__certification"><strong>Certification</strong>{{ $propertyReturn->certification }}</p>@endif
        @if ($propertyReturn->purpose === \App\Models\PropertyReturn::PURPOSE_OTHER)
            <p class="soims-property-returns__notice">This return is marked RETURNED for evaluation and is not automatically placed in stock.</p>
        @endif

        <section class="soims-property-returns__selection" aria-label="Returned assets">
            <header class="soims-property-returns__section-heading">
                <div><h2 class="soims-property-returns__section-title">Returned assets</h2><p>Asset details are recorded as a return snapshot.</p></div>
                <span>{{ $propertyReturn->items->count() }} {{ $propertyReturn->items->count() === 1 ? 'item' : 'items' }}</span>
            </header>
            <div class="soims-table-wrap">
            <table class="soims-table soims-property-returns__table">
                <thead><tr><th>Property no.</th><th>Description</th><th>Quantity</th><th>Unit value</th><th>Remarks</th><th>Asset status</th></tr></thead>
                <tbody>
                    @foreach ($propertyReturn->items as $line)
                        <tr>
                            <td>{{ $line->property_no_snapshot }}</td>
                            <td>{{ $line->description_snapshot }}</td>
                            <td>{{ $line->quantity }}</td>
                            <td>{{ $line->unit_value ?? '-' }}</td>
                            <td>{{ $line->remarks ?? '-' }}</td>
                            <td>{{ $line->accountableItem?->status ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </section>

        @if ($propertyReturn->status === \App\Models\PropertyReturn::STATUS_DRAFT)
            <div class="soims-property-returns__actions">
                <form method="POST" action="{{ route('property-returns.complete', $propertyReturn) }}">@csrf<x-ui.button type="submit">Complete PRS</x-ui.button></form>
                <form method="POST" action="{{ route('property-returns.cancel', $propertyReturn) }}">@csrf<x-ui.button type="submit" variant="danger">Cancel PRS</x-ui.button></form>
            </div>
        @endif
        <a class="soims-property-returns__back" href="{{ route('property-returns.index') }}">Back to property returns</a>
    </section>
@endsection