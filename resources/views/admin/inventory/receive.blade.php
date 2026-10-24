@extends('layouts.app')
@section('title', 'Add Items | SOIMS')
@section('content')
<x-ui.card class="soims-card--medium soims-receive-card">
	<header class="soims-receive-heading">
		<p class="soims-receive-eyebrow">Inventory / Stock in</p>
		<h1 class="soims-title">Add Items</h1>
		<p class="soims-lead">Record incoming stock. A receipt movement will be added to the inventory ledger.</p>
	</header>

	<form class="soims-receive-form" method="POST" action="{{ route('admin.inventory.receive.store') }}">
		@csrf
		<section class="soims-receive-group" aria-labelledby="receive-details-title">
			<h2 id="receive-details-title" class="soims-receive-group__title">Item details</h2>
			<div class="soims-receive-fields">
				<input type="hidden" id="existing_item_id" name="item_id" value="{{ old('item_id') }}">
                <x-ui.input label="Item name" name="name" value="{{ old('name') }}" required />
				<x-ui.input label="Code / Stock number" name="stock_no" value="{{ old('stock_no') }}" required />
				<x-ui.input label="Description" name="description" value="{{ old('description') }}" />
				<x-ui.input label="Brand" name="brand" value="{{ old('brand') }}" />
				<x-ui.input label="Quality" name="quality" value="{{ old('quality') }}" />
				<x-ui.input label="Supplier" name="supplier" value="{{ old('supplier') }}" />
				<div class="soims-field">
					<label class="soims-label" for="category_id">Category</label>
					<select class="soims-input" id="category_id" name="category_id" required>
						<option value="">Select category</option>
						@foreach ($categories as $category)
							<option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>{{ $category->name }}</option>
						@endforeach
					</select>
					@error('category_id')<div class="soims-error">{{ $message }}</div>@enderror
				</div>
				<div class="soims-field">
					<label class="soims-label" for="classification">Classification</label>
					<select class="soims-input" id="classification" name="classification" required>
						<option value="">Select classification</option>
						<option value="consumable" @selected(old('classification') === 'consumable')>Consumable</option>
						<option value="accountable" @selected(old('classification') === 'accountable')>Accountable</option>
					</select>
					@error('classification')<div class="soims-error">{{ $message }}</div>@enderror
				</div>
				<div class="soims-field" id="existing-stock-details" hidden><p class="soims-label">Existing stock</p><p id="existing-stock-quantity" role="status" aria-live="polite"></p><p class="soims-lead">Enter additional quantity below. This will be added to the current stock.</p></div>
                <x-ui.input label="Unit of measure" name="unit_of_measure" value="{{ old('unit_of_measure') }}" required />
			</div>
		</section>

		<section class="soims-receive-group" aria-labelledby="receipt-details-title">
			<h2 id="receipt-details-title" class="soims-receive-group__title">Receipt details</h2>
			<div class="soims-receive-fields">
				<x-ui.input label="Quantity" name="quantity" type="number" step="0.001" value="{{ old('quantity') }}" required />
				<x-ui.input label="Location" name="location" value="{{ old('location') }}" />
				<x-ui.input label="Received date" name="received_date" type="date" value="{{ old('received_date', now()->toDateString()) }}" required />
			</div>
		</section>

		<section class="soims-receive-group" aria-labelledby="receive-lot-title">
			<h2 id="receive-lot-title" class="soims-receive-group__title">Lot details <span>Optional when not applicable</span></h2>
			<div class="soims-receive-fields">
				<x-ui.input label="Lot number" name="lot_no" value="{{ old('lot_no') }}" />
				<x-ui.input label="Expiration date" name="expiration_date" type="date" value="{{ old('expiration_date') }}" />
				<x-ui.input label="Unit cost" name="unit_cost" type="number" step="0.01" value="{{ old('unit_cost') }}" />
				<x-ui.input label="Reason / reference" name="reason" value="{{ old('reason') }}" />
			</div>
		</section>

		<div class="soims-receive-actions">
			<x-ui.button type="submit">Add Items</x-ui.button>
			<a class="soims-button soims-button--secondary" href="{{ route('admin.inventory.index') }}">Cancel</a>
		</div>
	</form>
</x-ui.card>

<script type="application/json" id="existing-items-data">{!! $existingItems->map(function ($item) { return [
'id' => $item->id,
'stock_no' => $item->stock_no,
'name' => $item->name,
'description' => $item->description,
'brand' => $item->brand,
'quality' => $item->quality,
'supplier' => $item->supplier,
'category_id' => $item->category_id,
'classification' => $item->classification,
'unit_of_measure' => $item->unit_of_measure,
'quantity' => $item->inventory?->quantity_on_hand ?? 0,
]; })->values()->toJson(JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.soims-receive-form');
    const field = name => form.elements.namedItem(name);
    const stockField = field('stock_no');
    const nameField = field('name');
    const itemIdField = document.getElementById('existing_item_id');
    const panel = document.getElementById('existing-stock-details');
    const quantityText = document.getElementById('existing-stock-quantity');
    const items = JSON.parse(document.getElementById('existing-items-data').textContent);

    function clearExisting() {
        itemIdField.value = '';
        panel.hidden = true;
        quantityText.textContent = '';
    }
    function fill(item) {
        itemIdField.value = item.id;
        for (const key of ['stock_no', 'name', 'description', 'brand', 'quality', 'supplier', 'category_id', 'classification', 'unit_of_measure']) {
            const control = field(key);
            if (control) control.value = item[key] ?? '';
        }
        quantityText.textContent = item.quantity + ' ' + (item.unit_of_measure || '');
        panel.hidden = false;
    }
    function match(key, value) {
        const term = value.trim().toLowerCase();
        if (!term) { clearExisting(); return; }
        const matches = items.filter(item => String(item[key]).toLowerCase() === term);
        if (matches.length === 1) fill(matches[0]);
        else clearExisting();
    }
    function onLookupInput(key, input) {
        const previousId = itemIdField.value;
        const previous = items.find(item => String(item.id) === String(previousId));
        const value = input.value;
        if (previous && String(previous[key]).toLowerCase() !== value.trim().toLowerCase()) {
            clearExisting();
            for (const other of ['stock_no', 'name', 'description', 'brand', 'quality', 'supplier', 'category_id', 'classification', 'unit_of_measure']) {
                if (other !== key && field(other)) field(other).value = '';
            }
        }
        match(key, value);
    }
    stockField.addEventListener('input', () => onLookupInput('stock_no', stockField));
    nameField.addEventListener('input', () => onLookupInput('name', nameField));
    if (itemIdField.value) {
        const existing = items.find(item => String(item.id) === String(itemIdField.value));
        if (existing) fill(existing);
    }
});
</script>
@endsection
