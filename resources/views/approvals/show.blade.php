@extends('layouts.app')

@section('title', 'Review '.$supplyRequest->request_no.' | SOIMS')

@section('content')
    <div class="soims-card soims-approval-detail">
        <header class="soims-approval-detail__heading">
            <div>
                <p class="soims-approvals-eyebrow">Approvals / Request detail</p>
                <h1 class="soims-title">{{ $supplyRequest->request_no }}</h1>
            </div>
            <span class="soims-request-status soims-request-status--{{ $supplyRequest->status }}">{{ ucfirst(str_replace('_', ' ', $supplyRequest->status)) }}</span>
        </header>
        <p class="soims-approval-purpose"><span>Purpose</span>{{ $supplyRequest->purpose }}</p>
        <dl class="soims-approval-summary">
            <div><dt>Request type</dt><dd>{{ $supplyRequest->request_type === 'academic' ? 'Academic' : 'Non-Academic' }}</dd></div>
            <div><dt>Requested by</dt><dd>{{ $supplyRequest->requestor->name }}</dd></div>
            <div><dt>Department</dt><dd>{{ $supplyRequest->department?->name ?? '-' }}</dd></div>
            <div><dt>Submitted</dt><dd>{{ $supplyRequest->submitted_at?->format('M j, Y · H:i') }}</dd></div>
        </dl>
        <section class="soims-approval-items" aria-labelledby="approval-items-title">
            <h2 class="soims-section-title" id="approval-items-title">Requested items</h2>
            <div class="soims-table-wrap">
                <table class="soims-table soims-approval-items__table">
                <thead><tr><th>Stock no.</th><th>Item</th><th>Unit</th><th>Requested</th><th>Confirmed</th></tr></thead>
                <tbody>
                    @foreach ($supplyRequest->items as $line)
                        <tr><td class="soims-approval-stock-no">{{ $line->item->stock_no }}</td><td><strong>{{ $line->item->name }}</strong></td><td>{{ $line->item->unit_of_measure }}</td><td>{{ $line->requested_quantity }}</td><td>{{ $line->confirmed_quantity ?? '-' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </section>

        @if ($supplyRequest->approvals->isNotEmpty())
            <section class="soims-approval-history" aria-labelledby="approval-history-title">
                <h2 class="soims-section-title" id="approval-history-title">Approval history</h2>
                <ol>
                @foreach ($supplyRequest->approvals as $approval)
                    <li><strong>{{ ucfirst($approval->decision) }}</strong><span>by {{ $approval->approver->name }} · {{ $approval->decided_at->format('M j, Y · H:i') }}</span>@if ($approval->comments)<p>{{ $approval->comments }}</p>@endif</li>
                @endforeach
                </ol>
            </section>
        @endif

        @if ($supplyRequest->status === 'submitted')
            <form method="POST" action="{{ route('approvals.decide', $supplyRequest) }}" class="soims-approval-decision">
                @csrf
                <div class="soims-field">
                    <label class="soims-label" for="remarks">Remarks (required when denying)</label>
                    <textarea class="soims-input" id="remarks" name="remarks" rows="3">{{ old('remarks') }}</textarea>
                    @error('remarks')<div class="soims-error">{{ $message }}</div>@enderror
                    @error('decision')<div class="soims-error">{{ $message }}</div>@enderror
                </div>
                <div class="soims-approval-decision__actions">
                    <x-ui.button type="submit" name="decision" value="approve">Confirm request</x-ui.button>
                    <x-ui.button type="submit" name="decision" value="deny" variant="danger">Deny request</x-ui.button>
                </div>
            </form>
        @endif

        @can('modify', $supplyRequest)
            <p><a href="{{ route('modifications.edit', $supplyRequest) }}">Modify confirmed items</a></p>
        @endcan

        <a class="soims-approval-back" href="{{ route('approvals.index') }}">Back to approvals</a>
    </div>
@endsection
