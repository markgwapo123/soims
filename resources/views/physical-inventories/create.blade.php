@extends('layouts.app')

@section('title', 'New Physical Inventory | SOIMS')

@section('content')
    <section class="soims-physical-inventory soims-physical-inventory--create">
        <header class="soims-physical-inventory__heading">
            <div>
                <p class="soims-physical-inventory__eyebrow">Supply / Verification</p>
                <h1 class="soims-title">New physical inventory</h1>
                <p class="soims-lead">Set the count details, then select the consumables and accountable assets to verify.</p>
            </div>
        </header>
        @if ($errors->any())<div class="soims-error">{{ $errors->first() }}</div>@endif
        <form class="soims-physical-inventory__form" method="POST" action="{{ route('physical-inventories.store') }}">
            @csrf
            <section class="soims-physical-inventory__setup" aria-labelledby="physical-count-details">
                <h2 id="physical-count-details" class="soims-physical-inventory__section-title">Count details</h2>
                <div class="soims-physical-inventory__fields">
                    <x-ui.input label="Inventory number" name="inventory_no" value="{{ old('inventory_no') }}" required />
                    <x-ui.input label="Conducted at" name="conducted_at" type="datetime-local" value="{{ old('conducted_at', now()->format('Y-m-d\\TH:i')) }}" required />
                    <div class="soims-field">
                        <label class="soims-label" for="department_id">Department</label>
                        <select class="soims-input" id="department_id" name="department_id"><option value="">All / not specified</option>@foreach ($departments as $department)<option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>@endforeach</select>
                    </div>
                    <div class="soims-field">
                        <label class="soims-label" for="office_id">Office</label>
                        <select class="soims-input" id="office_id" name="office_id"><option value="">All / not specified</option>@foreach ($offices as $office)<option value="{{ $office->id }}" @selected(old('office_id') == $office->id)>{{ $office->name }}</option>@endforeach</select>
                    </div>
                    <div class="soims-field soims-physical-inventory__remarks"><label class="soims-label" for="remarks">Remarks</label><textarea class="soims-input" id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea></div>
                </div>
            </section>

            <section class="soims-physical-inventory__selection" aria-labelledby="consumable-count-title">
                <div class="soims-physical-inventory__section-heading">
                    <div><h2 id="consumable-count-title" class="soims-physical-inventory__section-title">Consumable balances</h2><p>Select the stock balances included in this count.</p></div>
                    <span>{{ $inventories->count() }} {{ $inventories->count() === 1 ? 'balance' : 'balances' }}</span>
                </div>
                @error('inventory_ids')<div class="soims-error">{{ $message }}</div>@enderror
                <div class="soims-table-wrap"><table class="soims-table soims-physical-inventory__table">
                    <thead><tr><th>Select</th><th>Stock no.</th><th>Article</th><th>Unit</th><th>Quantity per card</th></tr></thead>
                    <tbody>@forelse ($inventories as $inventory)
                        <tr><td><input type="checkbox" name="inventory_ids[]" value="{{ $inventory->id }}" @checked(in_array((string) $inventory->id, old('inventory_ids', []), true)) aria-label="Count {{ $inventory->item->name }}"></td><td>{{ $inventory->item->stock_no }}</td><td><strong>{{ $inventory->item->name }}</strong></td><td>{{ $inventory->item->unit_of_measure }}</td><td>{{ \App\Support\QuantityFormatter::display($inventory->quantity_on_hand) }}</td></tr>
                    @empty<tr><td class="soims-physical-inventory__empty" colspan="5">No consumable balances are registered.</td></tr>@endforelse</tbody>
                </table></div>
            </section>

            <section class="soims-physical-inventory__selection" aria-labelledby="accountable-count-title">
                <div class="soims-physical-inventory__section-heading">
                    <div><h2 id="accountable-count-title" class="soims-physical-inventory__section-title">Accountable assets</h2><p>Select assets to verify their recorded location and condition.</p></div>
                    <span>{{ $assets->count() }} {{ $assets->count() === 1 ? 'asset' : 'assets' }}</span>
                </div>
                @error('accountable_item_ids')<div class="soims-error">{{ $message }}</div>@enderror
                <div class="soims-table-wrap"><table class="soims-table soims-physical-inventory__table">
                    <thead><tr><th>Select</th><th>Property no.</th><th>Article</th><th>Serial no.</th><th>Status</th><th>Location</th></tr></thead>
                    <tbody>@forelse ($assets as $asset)
                        <tr><td><input type="checkbox" name="accountable_item_ids[]" value="{{ $asset->id }}" @checked(in_array((string) $asset->id, old('accountable_item_ids', []), true)) aria-label="Count {{ $asset->property_no }}"></td><td>{{ $asset->property_no }}</td><td><strong>{{ $asset->item->name }}</strong></td><td>{{ $asset->serial_no ?? '-' }}</td><td>{{ $asset->status }}</td><td>{{ $asset->current_location ?? '-' }}</td></tr>
                    @empty<tr><td class="soims-physical-inventory__empty" colspan="6">No accountable assets are registered.</td></tr>@endforelse</tbody>
                </table></div>
            </section>

            <div class="soims-physical-inventory__actions"><x-ui.button type="submit">Create count draft</x-ui.button><a class="soims-button soims-button--secondary" href="{{ route('physical-inventories.index') }}">Cancel</a></div>
        </form>
    </section>
@endsection