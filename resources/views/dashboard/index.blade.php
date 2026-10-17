@extends('layouts.app')

@section('title', 'Dashboard | SOIMS')

@section('header-actions')
    <div class="soims-dashboard__identity soims-dashboard__identity--topbar">
        <span class="soims-dashboard__identity-name">{{ auth()->user()->name }}</span>
        <span class="soims-dashboard__identity-role">{{ implode(', ', $roles) }}</span>
    </div>
@endsection

@section('content')
    <div class="soims-dashboard">
        <header class="soims-dashboard__heading">
            <div>
                <p class="soims-dashboard__eyebrow">Supply Office · WVSU-HCC</p>
                <h1 class="soims-title">Dashboard</h1>
            </div>
        </header>

        <section class="soims-dashboard__metrics" aria-label="Summary">
            @forelse ($stats as $stat)
                <a class="soims-metric" href="{{ $stat['url'] }}">
                    <span class="soims-metric__label">{{ $stat['label'] }}</span>
                    <strong class="soims-metric__value">{{ $stat['value'] }}</strong>
                </a>
            @empty
                <div class="soims-empty">No dashboard metrics are available for this account.</div>
            @endforelse
        </section>

        @if ($stockSummary)
            <section class="soims-dashboard__section soims-dashboard__stock">
                <header class="soims-dashboard__stock-heading">
                    <div>
                        <h2 class="soims-section-title">Inventory overview</h2>
                        <p>Live quantities from the inventory register.</p>
                    </div>
                    <a href="{{ route('stock-availability.index') }}">View all stock</a>
                </header>
                <div class="soims-dashboard__stock-metrics">
                    <div><span>Total items</span><strong>{{ number_format($stockSummary['total_items']) }}</strong></div>
                    <div><span>Available stock</span><strong>{{ number_format($stockSummary['available_stock'], 3) }}</strong></div>
                    <div><span>Low stock</span><strong>{{ number_format($stockSummary['low_stock_items']) }}</strong></div>
                    <div><span>Out of stock</span><strong>{{ number_format($stockSummary['out_of_stock_items']) }}</strong></div>
                    <div><span>Available assets</span><strong>{{ number_format($stockSummary['available_assets']) }}</strong></div>
                </div>
                <form class="soims-dashboard__stock-search" method="GET" action="{{ route('dashboard') }}">
                    <label class="soims-sr-only" for="dashboard-stock-search">Search inventory</label>
                    <input class="soims-input" id="dashboard-stock-search" name="stock_search" type="search" value="{{ $stockFilters['search'] ?? '' }}" placeholder="Search item, stock number, or category">
                    <x-ui.button type="submit">Search stock</x-ui.button>
                    @if (!empty($stockFilters['search']))<a href="{{ route('dashboard') }}">Clear</a>@endif
                </form>
                <div class="soims-table-wrap">
                    <table class="soims-table soims-dashboard__stock-table">
                        <thead><tr><th>Item</th><th>Type</th><th>Current stock</th><th>Available</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($stockItems as $item)
                                <tr>
                                    <td><strong>{{ $item->name }}</strong><small>{{ $item->stock_no }}</small></td>
                                    <td>{{ ucfirst($item->classification) }}</td>
                                    <td>{{ $item->stock_current_quantity }} {{ $item->classification === 'consumable' ? $item->unit_of_measure : 'assets' }}</td>
                                    <td>{{ $item->stock_available_quantity }} {{ $item->classification === 'consumable' ? $item->unit_of_measure : 'assets' }}</td>
                                    <td><span class="soims-stock__status is-{{ $item->stock_status }}">{{ ['available' => 'Available', 'low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock'][$item->stock_status] }}</span></td>
                                </tr>
                            @empty
                                <tr><td class="soims-stock__empty" colspan="5">{{ !empty($stockFilters['search']) ? 'No items match your search.' : 'No inventory items found.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $stockItems->links() }}
            </section>
        @elseif ($canViewStockAvailability)
            <section class="soims-dashboard__section soims-dashboard__stock">
                <header class="soims-dashboard__stock-heading">
                    <div>
                        <h2 class="soims-section-title">Stock availability</h2>
                        <p>Check available supplies before preparing a request.</p>
                    </div>
                    <a href="{{ route('stock-availability.index') }}">View all stock</a>
                </header>
                <form class="soims-dashboard__stock-search" method="GET" action="{{ route('dashboard') }}">
                    <label class="soims-sr-only" for="dashboard-stock-search">Search supplies</label>
                    <input class="soims-input" id="dashboard-stock-search" name="stock_search" type="search" value="{{ $stockFilters['search'] ?? '' }}" placeholder="Search item, stock number, or category">
                    <x-ui.button type="submit">Search supplies</x-ui.button>
                    @if (!empty($stockFilters['search']))<a href="{{ route('dashboard') }}">Clear</a>@endif
                </form>
                <div class="soims-table-wrap">
                    <table class="soims-table soims-dashboard__stock-table">
                        <thead><tr><th>Item</th><th>Category</th><th>Unit</th><th>Available</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse ($stockItems as $item)
                                <tr>
                                    <td><strong>{{ $item->name }}</strong><small>{{ $item->description }}</small></td>
                                    <td>{{ $item->category?->name ?? 'Uncategorized' }}</td>
                                    <td>{{ $item->unit_of_measure }}</td>
                                    <td>{{ $item->stock_available_quantity }}</td>
                                    <td><span class="soims-stock__status is-{{ $item->stock_status }}">{{ ['available' => 'Available', 'low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock'][$item->stock_status] }}</span></td>
                                </tr>
                            @empty
                                <tr><td class="soims-stock__empty" colspan="5">{{ !empty($stockFilters['search']) ? 'No items match your search.' : 'No inventory items found.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $stockItems->links() }}
            </section>
        @endif

        @if ($receivingTransactions->isNotEmpty())
            <section class="soims-dashboard__section">
                <h2 class="soims-section-title">Recent receiving transactions</h2>
                <div class="soims-table-wrap">
                    <table class="soims-table">
                        <thead><tr><th>Item</th><th>Quantity</th><th>Received</th><th>Recorded by</th></tr></thead>
                        <tbody>
                            @foreach ($receivingTransactions as $transaction)
                                <tr><td>{{ $transaction->item?->name ?? '-' }}</td><td>{{ $transaction->quantity }}</td><td>{{ $transaction->occurred_at?->format('Y-m-d H:i') }}</td><td>{{ $transaction->performer?->name ?? '-' }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($recentRequests->isNotEmpty())
            <section class="soims-dashboard__section">
                <h2 class="soims-section-title">{{ auth()->user()->hasPermission('approve_supply_requests') ? 'Recent requests to review' : 'Recent requests' }}</h2>
                <div class="soims-table-wrap">
                    <table class="soims-table">
                        <thead><tr><th>Request</th><th>Requestor</th><th>Department</th><th>Status</th><th>Updated</th></tr></thead>
                        <tbody>
                            @foreach ($recentRequests as $request)
                                <tr><td><a href="{{ auth()->user()->hasPermission('approve_supply_requests') ? route('approvals.show', $request) : route('requests.show', $request) }}">{{ $request->request_no }}</a></td><td>{{ $request->requestor?->name ?? '-' }}</td><td>{{ $request->department?->name ?? '-' }}</td><td><span class="soims-request-status soims-request-status--{{ $request->status }}">{{ ucfirst(str_replace('_', ' ', $request->status)) }}</span></td><td>{{ $request->updated_at?->format('Y-m-d H:i') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($recentReleases->isNotEmpty())
            <section class="soims-dashboard__section">
                <h2 class="soims-section-title">Recent issuances</h2>
                <div class="soims-table-wrap">
                    <table class="soims-table">
                        <thead><tr><th>RIS</th><th>Request</th><th>Released by</th><th>Released</th></tr></thead>
                        <tbody>
                            @foreach ($recentReleases as $release)
                                <tr><td><a href="{{ route('ris.show', $release->ris) }}">{{ $release->ris?->ris_no ?? '-' }}</a></td><td>{{ $release->ris?->supplyRequest?->request_no ?? '-' }}</td><td>{{ $release->releaser?->name ?? '-' }}</td><td>{{ $release->released_at?->format('Y-m-d H:i') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if ($activity->isNotEmpty())
            <section class="soims-dashboard__section soims-dashboard__section--activity">
                <div class="soims-dashboard__activity-panel">
                    <header class="soims-dashboard__activity-heading">
                        <h2 class="soims-section-title">Recent activity</h2>
                        <span>{{ $activity->count() }} {{ $activity->count() === 1 ? 'entry' : 'entries' }}</span>
                    </header>
                    <ul class="soims-activity">
                        @foreach ($activity as $entry)
                            @if ($entry instanceof \App\Models\AuditLog)
                                <li><strong>{{ ucfirst($entry->action) }}</strong> · {{ $entry->module }} · {{ $entry->record_type === \App\Models\User::class && $entry->subject_name ? $entry->subject_name : class_basename($entry->record_type ?? 'Record').' #'.($entry->record_id ?? '-') }} <time>{{ $entry->occurred_at?->format('Y-m-d H:i') }}</time></li>
                            @else
                                <li><strong>{{ $entry->request_no }}</strong> · {{ ucfirst(str_replace('_', ' ', $entry->status)) }} <time>{{ $entry->updated_at?->format('Y-m-d H:i') }}</time></li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    </div>
@endsection