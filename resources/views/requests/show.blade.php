@extends('layouts.app')

@section('title', $supplyRequest->request_no.' | SOIMS')

@section('content')
    <div class="soims-card">
        <h1 class="soims-title">{{ $supplyRequest->request_no }}</h1>
        <p class="soims-lead">{{ $supplyRequest->purpose }}</p>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        <p>
            <span class="soims-badge soims-badge--info">{{ ucfirst(str_replace('_', ' ', $supplyRequest->status)) }}</span>
            {{ $supplyRequest->request_type === 'academic' ? 'Academic (reviewed by DAA)' : 'Non-Academic (reviewed by Administrative Officer)' }}
            &middot; {{ $supplyRequest->department?->name }} &middot; {{ $supplyRequest->submitted_at?->format('Y-m-d H:i') }}
        </p>
        <div class="soims-table-wrap">
            <table class="soims-table">
                <thead><tr><th>Stock no.</th><th>Item</th><th>Unit</th><th>Requested</th><th>Confirmed</th></tr></thead>
                <tbody>
                    @foreach ($supplyRequest->items as $line)
                        <tr><td>{{ $line->item->stock_no }}</td><td>{{ $line->item->name }}</td><td>{{ $line->item->unit_of_measure }}</td><td>{{ \App\Support\QuantityFormatter::display($line->requested_quantity) }}</td><td>@if ($line->confirmed_quantity !== null){{ ($line->confirmedItem ?? $line->item)->name }} &times; {{ \App\Support\QuantityFormatter::display($line->confirmed_quantity) }}@else - @endif</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($supplyRequest->approvals->isNotEmpty())
            <h2 class="soims-section-title">Review history</h2>
            <ul>
                @foreach ($supplyRequest->approvals as $approval)
                    <li>{{ ucfirst($approval->decision) }} on {{ $approval->decided_at->format('Y-m-d H:i') }}@if ($approval->comments) &mdash; {{ $approval->comments }}@endif</li>
                @endforeach
            </ul>
        @endif
        @if ($supplyRequest->modifications->isNotEmpty())
            <h2 class="soims-section-title">Modification history</h2>
            <ul>
                @foreach ($supplyRequest->modifications as $modification)
                    <li>{{ $modification->fieldLabel() }}: {{ $modification->describe($modification->previous_value) }} &rarr; {{ $modification->describe($modification->new_value) }} on {{ $modification->modified_at->format('Y-m-d H:i') }} &mdash; {{ $modification->reason }}</li>
                @endforeach
            </ul>
        @endif
        <a href="{{ route('requests.index') }}">Back to my requests</a>
    </div>
@endsection
