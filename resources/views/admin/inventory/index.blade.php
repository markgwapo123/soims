@extends('layouts.app')
@section('title', 'Inventory | SOIMS')
@section('content')
<section class="soims-inventory">
	<header class="soims-inventory__heading">
		<div>
			<p class="soims-inventory__eyebrow">Supply / Stock control</p>
			<h1 class="soims-title">Inventory</h1>
			<p class="soims-lead">Current balances are updated through transactional inventory movements.</p>
		</div>
		<a class="soims-button soims-button--primary" href="{{ route('admin.inventory.receive') }}">Add Items</a>
	</header>

	@if (session('status'))<p class="soims-status">{{ session('status') }}</p>@endif

	<section class="soims-inventory__list" aria-label="Inventory balances">
		<div class="soims-inventory__list-heading">
			<h2 class="soims-section-title">Stock balances</h2>
			<span>{{ $inventories->total() }} {{ $inventories->total() === 1 ? 'item' : 'items' }}</span>
		</div>
		<div class="soims-table-wrap">
			<table class="soims-table soims-inventory__table">
				<thead><tr><th>Stock No.</th><th>Item</th><th class="soims-inventory__quantity-cell soims-quantity-center">On hand</th><th class="soims-inventory__quantity-cell soims-quantity-center">Reserved</th><th class="soims-inventory__quantity-cell soims-quantity-center">Available</th><th></th></tr></thead>
				<tbody>
					@forelse ($inventories as $inventory)
						<tr>
							<td class="soims-inventory__stock-no">{{ $inventory->item->stock_no }}</td>
							<td><strong>{{ $inventory->item->name }}</strong></td>
							<td class="soims-inventory__quantity-cell soims-quantity-center">{{ \App\Support\QuantityFormatter::display($inventory->quantity_on_hand) }}</td>
							<td class="soims-inventory__quantity-cell soims-quantity-center">{{ \App\Support\QuantityFormatter::display($inventory->quantity_reserved) }}</td>
							<td class="soims-inventory__quantity-cell soims-quantity-center"><span class="soims-inventory__availability {{ (float) $inventory->quantity_available > 0 ? 'is-available' : 'is-unavailable' }}">{{ \App\Support\QuantityFormatter::display($inventory->quantity_available) }}</span></td>
							<td><a class="soims-inventory__stock-card" href="{{ route('admin.inventory.stock-card', $inventory->item) }}">Stock card</a></td>
						</tr>
					@empty
						<tr><td class="soims-inventory__empty" colspan="6">No inventory balances yet. Add items to start tracking available quantities.</td></tr>
					@endforelse
				</tbody>
			</table>
		</div>
		{{ $inventories->links() }}
	</section>
</section>
<style>
    .soims-inventory__table th.soims-inventory__quantity-cell soims-quantity-center,
    .soims-inventory__table td.soims-inventory__quantity-cell soims-quantity-center {
        text-align: center;
    }
</style>
@endsection
