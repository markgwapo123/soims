@extends('layouts.app')

@section('title', $asset->property_no.' | SOIMS')

@section('content')
    <div class="soims-card">
        <h1 class="soims-title">{{ $asset->property_no }}</h1>
        <p class="soims-lead">{{ $asset->item->name }} · {{ $asset->barcode }}</p>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        @error('assignment')<div class="soims-error">{{ $message }}</div>@enderror
        <dl class="soims-details">
            <dt>Stock number</dt><dd>{{ $asset->item->stock_no }}</dd>
            <dt>Serial number</dt><dd>{{ $asset->serial_no ?? '-' }}</dd>
            <dt>Status</dt><dd>{{ $asset->status }}</dd>
            <dt>Date acquired</dt><dd>{{ $asset->date_acquired?->format('Y-m-d') ?? '-' }}</dd>
            <dt>Unit value</dt><dd>{{ $asset->unit_value ?? '-' }}</dd>
            <dt>Current user</dt><dd>{{ $asset->currentUser?->name ?? '-' }}</dd>
            <dt>Department</dt><dd>{{ $asset->currentDepartment?->name ?? '-' }}</dd>
            <dt>Office</dt><dd>{{ $asset->currentOffice?->name ?? '-' }}</dd>
            <dt>Location</dt><dd>{{ $asset->current_location ?? '-' }}</dd>
            <dt>Received</dt><dd>{{ $asset->received_at?->format('Y-m-d H:i') ?? '-' }}</dd>
        </dl>

        @if ($activeAssignment)
            <h2 class="soims-section-title">Transfer assignment</h2>
            <p>Currently assigned to {{ $activeAssignment->assignedUser?->name ?? $activeAssignment->assignedOffice?->name ?? $activeAssignment->assignedDepartment?->name ?? '-' }} at {{ $activeAssignment->location }}.</p>
            <form method="POST" action="{{ route('accountable-items.transfer', $asset) }}">
                @csrf
                @include('accountable-items._assignment-fields')
                <x-ui.input label="Transfer reason" name="reason" value="{{ old('reason') }}" />
                <div class="soims-actions"><x-ui.button type="submit">Transfer asset</x-ui.button></div>
            </form>
        @else
            <h2 class="soims-section-title">Assign asset</h2>
            <form method="POST" action="{{ route('accountable-items.assign', $asset) }}">
                @csrf
                @include('accountable-items._assignment-fields')
                <div class="soims-actions"><x-ui.button type="submit">Assign asset</x-ui.button></div>
            </form>
        @endif

        <h2 class="soims-section-title">Assignment history</h2>
        <div class="soims-table-wrap">
            <table class="soims-table">
                <thead><tr><th>Assigned to</th><th>Location</th><th>Assigned by</th><th>Assigned</th><th>Released</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($asset->assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->assignedUser?->name ?? $assignment->assignedOffice?->name ?? $assignment->assignedDepartment?->name ?? '-' }}</td>
                            <td>{{ $assignment->location }}</td>
                            <td>{{ $assignment->assignedBy?->name ?? '-' }}</td>
                            <td>{{ $assignment->assigned_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $assignment->released_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ $assignment->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No assignment history recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <a href="{{ route('accountable-items.index') }}">Back to asset register</a>
    </div>
@endsection