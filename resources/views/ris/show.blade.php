@extends('layouts.app')

@section('title', $ris->ris_no.' | SOIMS')

@section('content')
    <section class="soims-ris soims-ris--document">
        <header class="soims-ris__heading">
            <div>
                <p class="soims-ris__eyebrow">Requisition and Issue Slip</p>
                <h1 class="soims-title">{{ $ris->ris_no }}</h1>
                <p class="soims-lead">{{ $ris->entity_name }}</p>
            </div>
            <span class="soims-ris__status is-{{ $ris->status }}">{{ ucfirst($ris->status) }}</span>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        <dl class="soims-ris__metadata">
            <div><dt>Request</dt><dd>{{ $ris->supplyRequest?->request_no ?? '-' }}</dd></div>
            <div><dt>Revision</dt><dd>{{ $ris->revision_no }}</dd></div>
            <div><dt>Division</dt><dd>{{ $ris->division ?? '-' }}</dd></div>
            <div><dt>Office</dt><dd>{{ $ris->office ?? '-' }}</dd></div>
            <div><dt>Fund cluster</dt><dd>{{ $ris->fund_cluster ?? '-' }}</dd></div>
            <div><dt>Responsibility center</dt><dd>{{ $ris->responsibility_center_code ?? '-' }}</dd></div>
        </dl>
        <p class="soims-ris__purpose"><strong>Purpose</strong><span>{{ $ris->purpose }}</span></p>

        <section class="soims-ris__section" aria-label="Issue slip items">
            <div class="soims-table-wrap">
            <table class="soims-table soims-ris__table soims-ris__detail-table">
                <thead><tr><th>Stock no.</th><th>Unit</th><th>Description</th><th>Requested</th><th>Available</th><th>Issue</th><th>Not available</th><th>Unit price</th><th>Amount</th><th>Availability</th></tr></thead>
                <tbody>
                    @foreach ($ris->items as $line)
                        <tr>
                            <td>{{ $line->stock_no_snapshot }}</td><td>{{ $line->unit_snapshot }}</td><td>{{ $line->description_snapshot }}</td>
                            <td>{{ \App\Support\QuantityFormatter::display($line->requested_quantity) }}</td><td>{{ \App\Support\QuantityFormatter::display($line->available_quantity) }}</td><td>{{ \App\Support\QuantityFormatter::display($line->issue_quantity) }}</td><td>{{ \App\Support\QuantityFormatter::display($line->not_available_quantity) }}</td>
                            <td>{{ $line->unit_price }}</td><td>{{ $line->amount }}</td><td>{{ $line->availability() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </section>

        <div class="soims-ris__people">
            <p><strong>Requested by</strong>{{ $ris->requester?->name ?? '-' }}</p>
            <p><strong>Approved by</strong>{{ $ris->approver?->name ?? '-' }}</p>
            <p><strong>Issued by</strong>{{ $ris->issuer?->name ?? '-' }}</p>
            <p><strong>Received by</strong>{{ $ris->received_printed_name ?? '-' }}@if ($ris->received_designation) ({{ $ris->received_designation }})@endif</p>
        </div>
        @if ($ris->status === \App\Models\Ris::STATUS_PREPARED)
            <form class="soims-ris__release" method="POST" action="{{ route('ris.release', $ris) }}">
                @csrf
                @error('ris')<div class="soims-error">{{ $message }}</div>@enderror
                <label class="soims-label" for="release-notes">Release notes <span>Optional</span></label>
                <textarea class="soims-input" id="release-notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                <div class="soims-ris__actions"><x-ui.button type="submit">Release supplies</x-ui.button></div>
                <p class="soims-ris__note">Stock is deducted when this RIS is released.</p>
            </form>
        @elseif ($ris->release)
            <div class="soims-ris__release-info">
                <p><strong>Released by:</strong> {{ $ris->release->releaser?->name ?? '-' }} · {{ $ris->release->released_at?->format('Y-m-d H:i') }}</p>
                <p><strong>Release notes:</strong> {{ $ris->release->notes ?? '-' }}</p>
            </div>
        @endif
        <a class="soims-ris__back" href="{{ route('ris.index') }}">Back to RIS</a>
    </section>
@endsection
