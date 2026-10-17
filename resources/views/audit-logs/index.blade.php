@extends('layouts.app')

@section('title', 'Audit Logs | SOIMS')

@section('content')
    <div class="soims-card">
        <header class="soims-audit-heading">
            <div>
                <p class="soims-audit-eyebrow">Governance</p>
                <h1 class="soims-title">Audit logs</h1>
            </div>
            <span class="soims-audit-count">{{ $logs->total() }} {{ $logs->total() === 1 ? 'event' : 'events' }}</span>
        </header>
        <section class="soims-audit-filter-panel" aria-labelledby="audit-filter-title">
            <h2 class="soims-audit-filter-title" id="audit-filter-title">Filter events</h2>
            <form class="soims-audit-filters" method="GET" action="{{ route('audit-logs.index') }}">
                <div class="soims-field"><label class="soims-label" for="user_id">Actor</label><select class="soims-input" id="user_id" name="user_id"><option value="">All actors</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected(($filters['user_id'] ?? null) == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                <div class="soims-field"><label class="soims-label" for="module">Module</label><select class="soims-input" id="module" name="module"><option value="">All modules</option>@foreach ($modules as $module)<option value="{{ $module }}" @selected(($filters['module'] ?? '') === $module)>{{ ucfirst(str_replace('_', ' ', $module)) }}</option>@endforeach</select></div>
                <div class="soims-field"><label class="soims-label" for="action">Action</label><select class="soims-input" id="action" name="action"><option value="">All actions</option>@foreach ($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ ucfirst(str_replace('_', ' ', $action)) }}</option>@endforeach</select></div>
                <x-ui.input label="Record ID" name="record_id" type="number" min="1" value="{{ $filters['record_id'] ?? '' }}" />
                <x-ui.input label="From" name="from" type="date" value="{{ $filters['from'] ?? '' }}" />
                <x-ui.input label="To" name="to" type="date" value="{{ $filters['to'] ?? '' }}" />
                <div class="soims-audit-filter-actions">
                    <x-ui.button type="submit">Apply filters</x-ui.button>
                    <a href="{{ route('audit-logs.index') }}">Clear filters</a>
                </div>
            </form>
        </section>
        @if ($errors->any())<p class="soims-error">{{ $errors->first() }}</p>@endif
        <div class="soims-table-wrap soims-audit-table-wrap"><table class="soims-table soims-audit-table">
            <thead><tr><th>Occurred</th><th>Actor</th><th>Role</th><th>Action</th><th>Module</th><th>Record</th><th></th></tr></thead>
            <tbody>@forelse ($logs as $log)
                <tr>
                    <td><time class="soims-audit-time" datetime="{{ $log->occurred_at?->toIso8601String() }}">{{ $log->occurred_at?->format('Y-m-d H:i:s') }}</time></td>
                    <td class="soims-audit-actor">{{ $log->user?->name ?? 'System / deleted user' }}</td>
                    <td><span class="soims-audit-role">{{ $log->role_snapshot ?? '-' }}</span></td>
                    <td><span class="soims-audit-tag soims-audit-tag--action">{{ str_replace('_', ' ', $log->action) }}</span></td>
                    <td><span class="soims-audit-tag">{{ str_replace('_', ' ', $log->module) }}</span></td>
                    <td>{{ class_basename($log->record_type ?? 'Record') }} <span class="soims-audit-record-id">#{{ $log->record_id ?? '-' }}</span></td>
                    <td><a class="soims-audit-detail-link" href="{{ route('audit-logs.show', $log) }}">View</a></td>
                </tr>
            @empty<tr><td class="soims-audit-empty" colspan="7">No audit events match these filters.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $logs->links() }}
    </div>
@endsection