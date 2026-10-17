@extends('layouts.app')

@section('title', 'Physical Inventory | SOIMS')

@section('content')
    <section class="soims-physical-inventory">
        <header class="soims-physical-inventory__heading">
            <div>
                <p class="soims-physical-inventory__eyebrow">Supply / Verification</p>
                <h1 class="soims-title">Physical inventory</h1>
                <p class="soims-lead">Review recorded counts and their reconciliation status.</p>
            </div>
            <a class="soims-button soims-button--primary" href="{{ route('physical-inventories.create') }}">New count</a>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif

        <section class="soims-physical-inventory__list" aria-label="Physical inventory records">
            <div class="soims-physical-inventory__list-heading">
                <h2 class="soims-section-title">Count history</h2>
                <span>{{ $inventories->total() }} {{ $inventories->total() === 1 ? 'count' : 'counts' }}</span>
            </div>
            <div class="soims-table-wrap">
                <table class="soims-table soims-physical-inventory__table">
                    <thead><tr><th>Inventory no.</th><th>Conducted</th><th>Scope</th><th>Conducted by</th><th>Lines</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($inventories as $physicalInventory)
                            <tr>
                                <td class="soims-physical-inventory__number">{{ $physicalInventory->inventory_no }}</td>
                                <td>{{ $physicalInventory->conducted_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td>{{ $physicalInventory->office?->name ?? $physicalInventory->department?->name ?? 'All / selected items' }}</td>
                                <td>{{ $physicalInventory->conductor?->name ?? '-' }}</td>
                                <td>{{ $physicalInventory->items_count }}</td>
                                <td><span class="soims-physical-inventory__status is-{{ $physicalInventory->status }}">{{ ucfirst($physicalInventory->status) }}</span></td>
                                <td><a class="soims-physical-inventory__view" href="{{ route('physical-inventories.show', $physicalInventory) }}">View count</a></td>
                            </tr>
                        @empty
                            <tr><td class="soims-physical-inventory__empty" colspan="7">No physical counts recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $inventories->links() }}
        </section>
    </section>
@endsection