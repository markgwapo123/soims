@extends('layouts.app')

@section('title', 'Audit Event #'.$auditLog->id.' | SOIMS')

@section('content')
    <div class="soims-card">
        <h1 class="soims-title">Audit event #{{ $auditLog->id }}</h1>
        <dl class="soims-audit-details">
            <dt>Occurred</dt><dd>{{ $auditLog->occurred_at?->format('Y-m-d H:i:s') }}</dd>
            <dt>Actor</dt><dd>{{ $auditLog->user?->name ?? 'System / deleted user' }} ({{ $auditLog->role_snapshot ?? 'role unavailable' }})</dd>
            <dt>Action</dt><dd>{{ $auditLog->action }}</dd>
            <dt>Module</dt><dd>{{ $auditLog->module }}</dd>
            <dt>Record</dt><dd>{{ $auditLog->record_type ?? '-' }} #{{ $auditLog->record_id ?? '-' }}</dd>
            <dt>Reason</dt><dd>{{ $auditLog->reason ?? '-' }}</dd>
            <dt>IP address</dt><dd>{{ $auditLog->ip_address ?? '-' }}</dd>
            <dt>User agent</dt><dd>{{ $auditLog->user_agent ?? '-' }}</dd>
        </dl>
        <div class="soims-audit-values">
            <section><h2 class="soims-section-title">Before</h2><pre>{{ json_encode($auditLog->previous_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'No previous value recorded.' }}</pre></section>
            <section><h2 class="soims-section-title">After</h2><pre>{{ json_encode($auditLog->new_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: 'No new value recorded.' }}</pre></section>
        </div>
        <a href="{{ route('audit-logs.index') }}">Back to audit logs</a>
    </div>
@endsection