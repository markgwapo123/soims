@extends('layouts.app')

@section('title', 'Reports | SOIMS')

@section('content')
    <div class="soims-card soims-reports">
        <header class="soims-report-heading">
            <div>
                <p class="soims-report-eyebrow">Workspace / Reporting</p>
                <h1 class="soims-title">Reports</h1>
            </div>
            <div class="soims-report-heading__actions">
                <span class="soims-report-count">{{ $rows->total() }} {{ $rows->total() === 1 ? 'record' : 'records' }}</span>
                <a class="soims-button soims-button--secondary" data-no-loading href="{{ route('reports.export', array_filter(['type' => $type, 'from' => $filters['from'] ?? null, 'to' => $filters['to'] ?? null])) }}">Export CSV</a>
            </div>
        </header>
        <section class="soims-report-filter-panel" aria-labelledby="report-filter-title">
            <h2 class="soims-report-filter-title" id="report-filter-title">Report filters</h2>
            <form class="soims-report-filters" method="GET" action="{{ route('reports.index') }}">
                <div class="soims-field">
                    <label class="soims-label" for="type">Report</label>
                    <select class="soims-input" id="type" name="type">
                        @foreach ($types as $value => $label)<option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <x-ui.input label="From" name="from" type="date" value="{{ $filters['from'] ?? '' }}" />
                <x-ui.input label="To" name="to" type="date" value="{{ $filters['to'] ?? '' }}" />
                <div class="soims-report-filter-actions"><x-ui.button type="submit">Apply filters</x-ui.button></div>
            </form>
        </section>
        @if ($errors->any())<p class="soims-error">{{ $errors->first() }}</p>@endif
        <header class="soims-report-result-heading">
            <h2 class="soims-section-title">{{ $title }}</h2>
            <span>Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }}</span>
        </header>
        <div class="soims-table-wrap soims-report-table-wrap">
            <table class="soims-table soims-report-table">
                <thead><tr>@foreach ($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>@foreach (array_keys($columns) as $key)<td>{{ $row->{$key} ?? '-' }}</td>@endforeach</tr>
                    @empty
                        <tr><td colspan="{{ count($columns) }}">No report records match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
    </div>
@endsection