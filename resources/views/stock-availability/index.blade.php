@extends('layouts.app')

@section('title', 'Stock Availability | SOIMS')

@section('content')
    <section class="soims-stock">
        <header class="soims-stock__heading">
            <div>
                <p class="soims-stock__eyebrow">Supply / Request planning</p>
                <h1 class="soims-title">Stock availability</h1>
                <p class="soims-lead">View current item availability before preparing or submitting a supply request.</p>
            </div>
            @if (auth()->user()->hasPermission('submit_supply_requests') && ! auth()->user()->hasRole('supply_administrator'))
                <a class="soims-button soims-button--primary" href="{{ route('requests.create') }}">New request</a>
            @endif
        </header>

        @if ($summary)
            <section class="soims-stock__summary" aria-label="Inventory overview">
                <article><span>Total items</span><strong>{{ number_format($summary['total_items']) }}</strong></article>
                <article><span>Available consumable stock</span><strong>{{ \App\Support\QuantityFormatter::display($summary['available_stock']) }}</strong></article>
                <article><span>Low-stock items</span><strong>{{ number_format($summary['low_stock_items']) }}</strong></article>
                <article><span>Out-of-stock items</span><strong>{{ number_format($summary['out_of_stock_items']) }}</strong></article>
                <article><span>Available accountable assets</span><strong>{{ number_format($summary['available_assets']) }}</strong></article>
            </section>
        @endif

        <form class="soims-stock__filters" method="GET" action="{{ route('stock-availability.index') }}">
            <div class="soims-field">
                <label class="soims-label" for="stock-search">Search items</label>
                <input class="soims-input" id="stock-search" name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Item name, stock number, or category">
            </div>
            <div class="soims-field">
                <label class="soims-label" for="stock-category">Category</label>
                <select class="soims-input" id="stock-category" name="category_id">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="soims-field">
                <label class="soims-label" for="stock-type">Item type</label>
                <select class="soims-input" id="stock-type" name="type">
                    <option value="">All types</option>
                    <option value="consumable" @selected(($filters['type'] ?? '') === 'consumable')>Consumable</option>
                    <option value="accountable" @selected(($filters['type'] ?? '') === 'accountable')>Accountable</option>
                </select>
            </div>
            <div class="soims-field">
                <label class="soims-label" for="stock-status">Availability</label>
                <select class="soims-input" id="stock-status" name="availability">
                    <option value="">All statuses</option>
                    <option value="available" @selected(($filters['availability'] ?? '') === 'available')>Available</option>
                    <option value="low_stock" @selected(($filters['availability'] ?? '') === 'low_stock')>Low stock</option>
                    <option value="out_of_stock" @selected(($filters['availability'] ?? '') === 'out_of_stock')>Out of stock</option>
                </select>
            </div>
            <div class="soims-stock__filter-actions">
                <x-ui.button type="submit">Apply filters</x-ui.button>
                @if ($hasFilters)<a href="{{ route('stock-availability.index') }}">Clear</a>@endif
            </div>
        </form>

        <div class="soims-stock__list-heading">
            <h2 class="soims-section-title">{{ $canManageInventory ? 'Inventory overview' : 'Available supplies' }}</h2>
            <span>{{ $items->total() }} {{ $items->total() === 1 ? 'item' : 'items' }}</span>
        </div>
        <div class="soims-table-wrap">
            <table class="soims-table soims-stock__table {{ $canManageInventory ? 'is-internal' : 'is-request-view' }}">
                @if ($canManageInventory)
                    <thead><tr><th>Stock no.</th><th>Item</th><th>Category</th><th>Type</th><th class="soims-quantity-center">Current stock</th><th class="soims-quantity-center">Available</th><th>Status</th></tr></thead>
                @else
                    <thead><tr><th>Item</th><th>Description</th><th>Category</th><th>Unit</th><th class="soims-quantity-center">Available</th><th>Status</th></tr></thead>
                @endif
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            @if ($canManageInventory)
                                <td class="soims-stock__identifier">{{ $item->stock_no }}</td>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td>{{ $item->category?->name ?? 'Uncategorized' }}</td>
                                <td>{{ ucfirst($item->classification) }}</td>
                                <td class="soims-quantity-center">{{ $item->stock_current_quantity }} {{ $item->classification === 'consumable' ? $item->unit_of_measure : 'assets' }}</td>
                                <td class="soims-quantity-center">{{ $item->stock_available_quantity }} {{ $item->classification === 'consumable' ? $item->unit_of_measure : 'assets' }}</td>
                            @else
                                <td><strong>{{ $item->name }}</strong></td>
                                <td>{{ $item->description ?: '—' }}</td>
                                <td>{{ $item->category?->name ?? 'Uncategorized' }}</td>
                                <td>{{ $item->unit_of_measure }}</td>
                                <td class="soims-quantity-center">{{ $item->stock_available_quantity }}</td>
                            @endif
                            <td><span class="soims-stock__status is-{{ $item->stock_status }}">{{ $statusLabels[$item->stock_status] }}</span></td>
                        </tr>
                    @empty
                        <tr><td class="soims-stock__empty" colspan="{{ $canManageInventory ? 7 : 6 }}">{{ $hasFilters ? 'No items match your search and filters.' : 'No inventory items found.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </section>
@endsection