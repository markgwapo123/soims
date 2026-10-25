@extends('layouts.app')

@section('title', 'Prepare RIS | SOIMS')

@section('content')
    <section class="soims-ris soims-ris--document">
        <header class="soims-ris__heading">
            <div>
                <p class="soims-ris__eyebrow">Requisition / Issue preparation</p>
                <h1 class="soims-title">Prepare RIS</h1>
                <p class="soims-lead">Request {{ $supplyRequest->request_no }} · {{ $supplyRequest->requestor->name }} · {{ $supplyRequest->department?->name ?? 'No department' }}</p>
            </div>
            <a class="soims-button soims-button--secondary" href="{{ route('ris.index') }}">Back to RIS</a>
        </header>
        <p class="soims-ris__purpose"><strong>Purpose</strong><span>{{ $supplyRequest->purpose }}</span></p>
        @error('ris')<div class="soims-error">{{ $message }}</div>@enderror
        @error('issue')<div class="soims-error">{{ $message }}</div>@enderror

        <form class="soims-ris__form" method="POST" action="{{ route('ris.store', $supplyRequest) }}">
            @csrf
            <section class="soims-ris__section" aria-labelledby="ris-items-title">
                <header class="soims-ris__section-heading">
                    <div><h2 id="ris-items-title" class="soims-section-title">Confirmed items</h2><p>Issue quantity cannot exceed current available stock.</p></div>
                    <span>{{ $lines->count() }} {{ $lines->count() === 1 ? 'item' : 'items' }}</span>
                </header>
                <div class="soims-table-wrap">
                    <table class="soims-table soims-ris__table soims-ris__issue-table">
                        <thead><tr><th>Stock no.</th><th>Item</th><th>Unit</th><th>Requested</th><th>Available</th><th>Issue quantity</th></tr></thead>
                        <tbody>
                            @forelse ($lines as $row)
                                <tr>
                                    <td class="soims-ris__number">{{ $row['item']->stock_no }}</td>
                                    <td><strong>{{ $row['item']->name }}</strong></td>
                                    <td>{{ $row['item']->unit_of_measure }}</td>
                                    <td>{{ \App\Support\QuantityFormatter::display($row['requested']) }}</td>
                                    <td><span class="soims-ris__quantity">{{ \App\Support\QuantityFormatter::display($row['available']) }}</span></td>
                                    <td><input class="soims-input soims-ris__quantity-input" type="number" min="0" max="{{ floor($row['max_issue']) }}" step="1" name="issue[{{ $row['line']->id }}]" value="{{ old('issue.'.$row['line']->id, (int) floor($row['max_issue'])) }}" aria-label="Issue quantity for {{ $row['item']->name }}"></td>
                                </tr>
                            @empty
                                <tr><td class="soims-ris__empty" colspan="6">This request has no items with a confirmed quantity.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="soims-ris__note">Issue quantities default to available stock, up to the requested amount. Any unissued quantity is recorded as unavailable.</p>
            </section>
            <section class="soims-ris__section" aria-labelledby="ris-receipt-title">
                <header class="soims-ris__section-heading"><div><h2 id="ris-receipt-title" class="soims-section-title">Receipt details</h2><p>Identify the receiving person on the issue slip.</p></div></header>
                <div class="soims-ris__fields">
                    <x-ui.input label="Fund cluster" name="fund_cluster" value="{{ old('fund_cluster') }}" />
                    <x-ui.input label="Responsibility center code" name="responsibility_center_code" value="{{ old('responsibility_center_code') }}" />
                    <x-ui.input label="Received by (printed name)" name="received_printed_name" value="{{ old('received_printed_name', $supplyRequest->requestor->name) }}" />
                    <x-ui.input label="Designation" name="received_designation" value="{{ old('received_designation') }}" />
                </div>
            </section>
            <div class="soims-ris__actions"><x-ui.button type="submit">Prepare RIS</x-ui.button><a class="soims-button soims-button--secondary" href="{{ route('ris.index') }}">Cancel</a></div>
        </form>
    </section>
@endsection
