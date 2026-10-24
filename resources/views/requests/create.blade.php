@extends('layouts.app')

@section('title', 'New Request | SOIMS')

@section('content')
    <div class="soims-card soims-request-create">
        <header class="soims-request-create__heading">
            <p class="soims-request-create__eyebrow">Workspace / Requests</p>
            <h1 class="soims-title">New supply request</h1>
        </header>
        <form class="soims-request-create__form" method="POST" action="{{ route('requests.store') }}">
            @csrf
            <div class="soims-field soims-request-purpose">
                <label class="soims-label" for="purpose">Purpose</label>
                <textarea class="soims-input" id="purpose" name="purpose" rows="3" required>{{ old('purpose') }}</textarea>
                @error('purpose')<div class="soims-error">{{ $message }}</div>@enderror
            </div>
            @error('items')<div class="soims-error">{{ $message }}</div>@enderror
            @error('items.*')<div class="soims-error">{{ $message }}</div>@enderror
            <section class="soims-request-items" aria-labelledby="request-items-title">
                <header class="soims-request-items__heading">
                    <h2 id="request-items-title">Requested items</h2>
                    <span>{{ $items->count() }} available</span>
                </header>
            <div class="soims-table-wrap">
                <table class="soims-table soims-request-items__table">
                    <thead><tr><th>Stock no.</th><th>Item</th><th>Unit</th><th>Available</th><th>Request quantity</th></tr></thead>
                    <tbody>
                        @forelse ($items as $index => $item)
                            <tr>
                                <td><span class="soims-request-stock-no">{{ $item->stock_no }}</span></td>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td>{{ $item->unit_of_measure }}</td>
                                <td class="soims-request-available">{{ \App\Support\QuantityFormatter::display($item->inventory?->quantity_available ?? 0) }}</td>
                                <td>
                                    <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                    <input class="soims-input soims-request-quantity" type="number" min="0" step="0.001" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity') }}" aria-label="Quantity for {{ $item->name }}">
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No items are available to request.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </section>
            <div class="soims-request-create__actions">
                 <x-ui.button type="submit" :disabled="$items->isEmpty()">Submit request</x-ui.button>
                <a class="soims-button soims-button--secondary" href="{{ route('requests.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
