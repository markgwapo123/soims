@extends('layouts.app')

@section('title', $physicalInventory->inventory_no.' | SOIMS')

@section('content')
    <div class="soims-card soims-physical-count">
        <header class="soims-physical-count__heading">
            <div>
                <p class="soims-physical-inventory__eyebrow">Physical inventory / Count record</p>
                <h1 class="soims-title">{{ $physicalInventory->inventory_no }}</h1>
                <p class="soims-lead">Conducted {{ $physicalInventory->conducted_at?->format('Y-m-d H:i') ?? '-' }} by {{ $physicalInventory->conductor?->name ?? '-' }}</p>
            </div>
            <span class="soims-physical-inventory__status is-{{ $physicalInventory->status }}">{{ ucfirst($physicalInventory->status) }}</span>
        </header>
        @if ($physicalInventory->department || $physicalInventory->office)<p class="soims-physical-count__scope"><strong>Scope</strong>{{ $physicalInventory->department?->name }}{{ $physicalInventory->department && $physicalInventory->office ? ' / ' : '' }}{{ $physicalInventory->office?->name }}</p>@endif
        @if ($physicalInventory->remarks)<p>{{ $physicalInventory->remarks }}</p>@endif
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        @if ($errors->any())<div class="soims-error">{{ $errors->first() }}</div>@endif

        @if ($physicalInventory->status === \App\Models\PhysicalInventory::STATUS_DRAFT)
            <p class="soims-physical-count__instruction">Enter the physical quantity for every listed line. Newly found items can be recorded in the blank rows.</p>
            <form method="POST" action="{{ route('physical-inventories.complete', $physicalInventory) }}">
                @csrf
                <div class="soims-table-wrap"><table class="soims-table soims-physical-count__table">
                    <thead><tr><th>Article</th><th>Description</th><th>Property no.</th><th>Unit</th><th>Per card</th><th>Physical count</th><th>New property no.</th><th>Remarks</th></tr></thead>
                    <tbody>@foreach ($physicalInventory->items as $line)
                        <tr>
                            <td>{{ $line->article }}</td><td>{{ $line->description }}</td><td>{{ $line->old_property_no ?? '-' }}</td><td>{{ $line->unit_of_measure ?? '-' }}</td><td>{{ $line->quantity_per_property_card }}</td>
                            <td><input class="soims-input" type="number" min="0" step="0.001" name="counts[{{ $line->id }}][quantity_per_physical_count]" value="{{ old('counts.'.$line->id.'.quantity_per_physical_count') }}" required aria-label="Physical count for {{ $line->article }}"></td>
                            <td><input class="soims-input" name="counts[{{ $line->id }}][new_property_no]" value="{{ old('counts.'.$line->id.'.new_property_no') }}" aria-label="New property number for {{ $line->article }}"></td>
                            <td><input class="soims-input" name="counts[{{ $line->id }}][remarks]" value="{{ old('counts.'.$line->id.'.remarks') }}" aria-label="Count remarks for {{ $line->article }}"></td>
                        </tr>
                    @endforeach
                    @for ($index = 0; $index <= $physicalInventory->items->whereNull('item_id')->count(); $index++)
                        <tr>
                            <td><input class="soims-input" name="found_items[{{ $index }}][article]" value="{{ old('found_items.'.$index.'.article') }}" placeholder="Unlisted article" aria-label="Found item article"></td>
                            <td><input class="soims-input" name="found_items[{{ $index }}][description]" value="{{ old('found_items.'.$index.'.description') }}" placeholder="Description" aria-label="Found item description"></td>
                            <td>-</td>
                            <td><input class="soims-input" name="found_items[{{ $index }}][unit_of_measure]" value="{{ old('found_items.'.$index.'.unit_of_measure') }}" aria-label="Found item unit"></td>
                            <td>0.000</td>
                            <td><input class="soims-input" type="number" min="0" step="0.001" name="found_items[{{ $index }}][quantity_per_physical_count]" value="{{ old('found_items.'.$index.'.quantity_per_physical_count') }}" aria-label="Found item physical count"></td>
                            <td><input class="soims-input" name="found_items[{{ $index }}][new_property_no]" value="{{ old('found_items.'.$index.'.new_property_no') }}" aria-label="Found item new property number"></td>
                            <td><input class="soims-input" name="found_items[{{ $index }}][remarks]" value="{{ old('found_items.'.$index.'.remarks') }}" aria-label="Found item remarks"></td>
                        </tr>
                    @endfor
                    </tbody>
                </table></div>
                <div class="soims-physical-count__actions"><x-ui.button type="submit">Complete physical inventory</x-ui.button></div>
            </form>
            <form class="soims-physical-count__cancel" method="POST" action="{{ route('physical-inventories.cancel', $physicalInventory) }}">@csrf<x-ui.button type="submit" variant="danger">Cancel draft</x-ui.button></form>
        @else
            <div class="soims-table-wrap"><table class="soims-table soims-physical-count__table">
                <thead><tr><th>Article</th><th>Description</th><th>Property no.</th><th>Unit</th><th>Per card</th><th>Physical count</th><th>Shortage</th><th>Overage</th><th>Remarks</th></tr></thead>
                <tbody>@foreach ($physicalInventory->items as $line)
                    <tr><td>{{ $line->article }}</td><td>{{ $line->description }}</td><td>{{ $line->new_property_no ?? $line->old_property_no ?? '-' }}</td><td>{{ $line->unit_of_measure ?? '-' }}</td><td>{{ $line->quantity_per_property_card }}</td><td>{{ $line->quantity_per_physical_count }}</td><td>{{ $line->shortage_quantity }}</td><td>{{ $line->overage_quantity }}</td><td>{{ $line->remarks ?? '-' }}</td></tr>
                @endforeach</tbody>
            </table></div>
            @if ($physicalInventory->status === \App\Models\PhysicalInventory::STATUS_COMPLETED)
                <p class="soims-lead">Discrepancies are recorded for review. Inventory balances and stock ledger were not changed.</p>
            @endif
        @endif
        <a class="soims-physical-count__back" href="{{ route('physical-inventories.index') }}">Back to physical inventories</a>
    </div>
@endsection