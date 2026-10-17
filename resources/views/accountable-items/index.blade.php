@extends('layouts.app')

@section('title', 'Accountable Assets | SOIMS')

@section('content')
    <section class="soims-accountable">
        <header class="soims-accountable__heading">
            <div>
                <p class="soims-accountable__eyebrow">Accountability / Asset control</p>
                <h1 class="soims-title">Accountable asset register</h1>
                <p class="soims-lead">Find registered assets and review their current custodian and location.</p>
            </div>
            <a class="soims-button soims-button--primary" href="{{ route('accountable-items.create') }}">Register asset</a>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        <form class="soims-accountable__search" method="GET" action="{{ route('accountable-items.index') }}">
            <div class="soims-field">
                <label class="soims-label" for="asset-search">Barcode, property number, or serial number</label>
                <input class="soims-input" id="asset-search" name="search" type="search" value="{{ $search }}" autocomplete="off">
            </div>
            <div class="soims-accountable__search-actions">
                <x-ui.button type="submit">Find asset</x-ui.button>
                @if ($search)<a href="{{ route('accountable-items.index') }}">Clear search</a>@endif
            </div>
        </form>

        <div class="soims-accountable__results-heading">
            <h2 class="soims-section-title">Registered assets</h2>
            <span>{{ $assets->total() }} {{ $assets->total() === 1 ? 'asset' : 'assets' }}{{ $search ? ' · Search results' : '' }}</span>
        </div>
        <div class="soims-table-wrap">
            <table class="soims-table soims-accountable__table">
                <thead><tr><th>Property no.</th><th>Barcode</th><th>Item</th><th>Serial no.</th><th>Status</th><th>Current custodian</th><th>Location</th><th></th></tr></thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td class="soims-accountable__identifier">{{ $asset->property_no }}</td>
                            <td class="soims-accountable__barcode">{{ $asset->barcode }}</td>
                            <td><strong>{{ $asset->item->name }}</strong><small>{{ $asset->item->stock_no }}</small></td>
                            <td>{{ $asset->serial_no ?? '-' }}</td>
                            <td><span class="soims-accountable__status is-{{ \Illuminate\Support\Str::slug($asset->status) }}">{{ ucfirst(str_replace('_', ' ', $asset->status)) }}</span></td>
                            <td>{{ $asset->currentUser?->name ?? $asset->currentOffice?->name ?? $asset->currentDepartment?->name ?? '-' }}</td>
                            <td>{{ $asset->current_location ?? '-' }}</td>
                            <td><a class="soims-accountable__view" href="{{ route('accountable-items.show', $asset) }}">View asset</a></td>
                        </tr>
                    @empty
                        <tr><td class="soims-accountable__empty" colspan="8">{{ $search ? 'No assets match that identifier.' : 'No accountable assets registered yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $assets->links() }}
    </section>
@endsection