@extends('layouts.app')
@section('title', 'Receive Stock | SOIMS')
@section('content')
<x-ui.card class="soims-card--medium soims-receive-card">
	<header class="soims-receive-heading">
		<p class="soims-receive-eyebrow">Inventory / Stock in</p>
		<h1 class="soims-title">Receive stock</h1>
		<p class="soims-lead">Record incoming stock. A receipt movement will be added to the inventory ledger.</p>
	</header>

	<form class="soims-receive-form" method="POST" action="{{ route('admin.inventory.receive.store') }}">
		@csrf
		<section class="soims-receive-group" aria-labelledby="receive-details-title">
			<h2 id="receive-details-title" class="soims-receive-group__title">Receipt details</h2>
			<div class="soims-receive-fields">
				<div class="soims-field soims-receive-fields__item">
					<label class="soims-label" for="item_id">Item</label>
					<select class="soims-input" id="item_id" name="item_id" required>
						<option value="">Select item</option>
						@foreach ($items as $item)
							<option value="{{ $item->id }}" @selected((string) old('item_id') === (string) $item->id)>{{ $item->stock_no }} - {{ $item->name }}</option>
						@endforeach
					</select>
					@error('item_id')<div class="soims-error">{{ $message }}</div>@enderror
				</div>
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
			<x-ui.button type="submit">Receive stock</x-ui.button>
			<a class="soims-button soims-button--secondary" href="{{ route('admin.inventory.index') }}">Cancel</a>
		</div>
	</form>
</x-ui.card>
@endsection
