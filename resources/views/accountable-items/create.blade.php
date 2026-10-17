@extends('layouts.app')

@section('title', 'Register Accountable Asset | SOIMS')

@section('content')
    <div class="soims-card">
        <h1 class="soims-title">Register accountable asset</h1>
        <form method="POST" action="{{ route('accountable-items.store') }}">
            @csrf
            <div class="soims-field">
                <label class="soims-label" for="item_id">Catalog item</label>
                <select class="soims-input" name="item_id" id="item_id" required>
                    <option value="">Select an accountable item</option>
                    @foreach ($items as $item)
                        <option value="{{ $item->id }}" @selected((string) old('item_id') === (string) $item->id)>{{ $item->stock_no }} · {{ $item->name }}</option>
                    @endforeach
                </select>
                @error('item_id')<div class="soims-error">{{ $message }}</div>@enderror
            </div>
            <x-ui.input label="Property number" name="property_no" value="{{ old('property_no') }}" required />
            <x-ui.input label="Barcode value" name="barcode" value="{{ old('barcode') }}" required />
            <x-ui.input label="Serial number" name="serial_no" value="{{ old('serial_no') }}" />
            <x-ui.input label="Date acquired" name="date_acquired" type="date" value="{{ old('date_acquired') }}" />
            <x-ui.input label="Unit value" name="unit_value" type="number" step="0.01" min="0" value="{{ old('unit_value') }}" />
            <x-ui.input label="Status" name="status" value="{{ old('status') }}" required />
            <div class="soims-actions"><x-ui.button type="submit">Register asset</x-ui.button><a href="{{ route('accountable-items.index') }}">Cancel</a></div>
        </form>
    </div>
@endsection