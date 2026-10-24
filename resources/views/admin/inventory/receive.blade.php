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
@endsection
