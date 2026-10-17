@extends('layouts.app')

@section('title', 'RIS | SOIMS')

@section('content')
    <section class="soims-ris">
        <header class="soims-ris__heading">
            <div>
                <p class="soims-ris__eyebrow">Supply / Issuance</p>
                <h1 class="soims-title">Requisition and Issue Slips</h1>
                <p class="soims-lead">Prepare slips for confirmed requests and review issue history.</p>
            </div>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif

        <section class="soims-ris__section" aria-labelledby="ris-awaiting-title">
            <header class="soims-ris__section-heading">
                <div><h2 id="ris-awaiting-title" class="soims-section-title">Confirmed requests awaiting RIS</h2><p>Requests ready for issue-slip preparation.</p></div>
                <span>{{ $awaiting->count() }} {{ $awaiting->count() === 1 ? 'request' : 'requests' }}</span>
            </header>
            <div class="soims-table-wrap">
                <table class="soims-table soims-ris__table">
                    <thead><tr><th>Request no.</th><th>Requested by</th><th>Department</th><th>Type</th><th>Items</th><th>Confirmed</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($awaiting as $supplyRequest)
                            <tr>
                                <td class="soims-ris__number">{{ $supplyRequest->request_no }}</td>
                                <td>{{ $supplyRequest->requestor->name }}</td>
                                <td>{{ $supplyRequest->department?->name ?? '-' }}</td>
                                <td>{{ $supplyRequest->request_type === 'academic' ? 'Academic' : 'Non-Academic' }}</td>
                                <td>{{ $supplyRequest->items_count }}</td>
                                <td>{{ $supplyRequest->confirmed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                <td><a class="soims-ris__action" href="{{ route('ris.create', $supplyRequest) }}">Prepare RIS</a></td>
                            </tr>
                        @empty
                            <tr><td class="soims-ris__empty" colspan="7">No confirmed requests are waiting for a RIS.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="soims-ris__section" aria-labelledby="ris-history-title">
            <header class="soims-ris__section-heading">
                <div><h2 id="ris-history-title" class="soims-section-title">RIS history</h2><p>Prepared slips and completed releases.</p></div>
                <span>{{ $documents->total() }} {{ $documents->total() === 1 ? 'slip' : 'slips' }}</span>
            </header>
            <div class="soims-table-wrap">
                <table class="soims-table soims-ris__table">
                    <thead><tr><th>RIS no.</th><th>Request</th><th>Items</th><th>Issued by</th><th>Prepared</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($documents as $document)
                            <tr>
                                <td class="soims-ris__number">{{ $document->ris_no }}</td>
                                <td>{{ $document->supplyRequest?->request_no ?? '-' }}</td>
                                <td>{{ $document->items_count }}</td>
                                <td>{{ $document->issuer?->name ?? '-' }}</td>
                                <td>{{ $document->created_at->format('Y-m-d H:i') }}</td>
                                <td><span class="soims-ris__status is-{{ $document->status }}">{{ ucfirst($document->status) }}</span></td>
                                <td><a class="soims-ris__action" href="{{ route('ris.show', $document) }}">View slip</a></td>
                            </tr>
                        @empty
                            <tr><td class="soims-ris__empty" colspan="7">No RIS prepared yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $documents->links() }}
        </section>
    </section>
@endsection
