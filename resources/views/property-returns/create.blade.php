@extends('layouts.app')

@section('title', 'New PRS | SOIMS')

@section('content')
    <section class="soims-property-returns soims-property-returns--create">
        <header class="soims-property-returns__heading">
            <div>
                <p class="soims-property-returns__eyebrow">Accountability / Returns</p>
                <h1 class="soims-title">New Property Return Slip</h1>
                <p class="soims-lead">Enter the return details, then choose the accountable assets included in this slip.</p>
            </div>
            <a class="soims-button soims-button--secondary" href="{{ route('property-returns.index') }}">Back to returns</a>
        </header>
        @error('items')<div class="soims-error">{{ $message }}</div>@enderror
        <form class="soims-property-returns__form" method="POST" action="{{ route('property-returns.store') }}">
            @csrf
            <section class="soims-property-returns__details" aria-labelledby="prs-details-title">
                <h2 id="prs-details-title" class="soims-property-returns__section-title">Return details</h2>
                <div class="soims-property-returns__fields">
                    <x-ui.input label="PRS number" name="prs_no" value="{{ old('prs_no') }}" required />
                    <div class="soims-field">
                        <label class="soims-label" for="purpose">Purpose</label>
                        <select class="soims-input" id="purpose" name="purpose" required>
                            <option value="">Select purpose</option>
                            @foreach (['repair' => 'Repair', 'disposal' => 'Disposal', 'returned_to_stock' => 'Returned to Stock', 'other' => 'Others'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('purpose') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('purpose')<div class="soims-error">{{ $message }}</div>@enderror
                    </div>
                    <x-ui.input label="Office name" name="office_name" value="{{ old('office_name') }}" required />
                    <x-ui.input label="Document number" name="document_no" value="{{ old('document_no') }}" />
                    <x-ui.input label="Issue number" name="issue_no" value="{{ old('issue_no') }}" />
                    <x-ui.input label="Received from" name="received_from" value="{{ old('received_from') }}" />
                    <x-ui.input label="Returned to / location" name="returned_to" value="{{ old('returned_to') }}" />
                    <x-ui.input label="End user" name="end_user" value="{{ old('end_user') }}" />
                    <div class="soims-field soims-property-returns__certification-field">
                        <label class="soims-label" for="certification">Certification</label>
                        <textarea class="soims-input" id="certification" name="certification" rows="3">{{ old('certification') }}</textarea>
                    </div>
                </div>
            </section>

            <section class="soims-property-returns__selection" aria-labelledby="prs-items-title">
                <header class="soims-property-returns__section-heading">
                    <div><h2 id="prs-items-title" class="soims-property-returns__section-title">Return items</h2><p>Select the assets to include in this return slip.</p></div>
                    <span>{{ $assets->count() }} {{ $assets->count() === 1 ? 'asset' : 'assets' }}</span>
                </header>
                <div class="soims-table-wrap">
                    <table class="soims-table soims-property-returns__table">
                        <thead><tr><th>Select</th><th>Property no.</th><th>Item</th><th>Current custodian</th><th>Location</th><th>Remarks</th></tr></thead>
                        <tbody>
                            @forelse ($assets as $asset)
                                <tr>
                                    <td><input type="checkbox" name="items[]" value="{{ $asset->id }}" @checked(in_array((string) $asset->id, old('items', []), true)) aria-label="Include {{ $asset->property_no }}"></td>
                                    <td class="soims-property-returns__number">{{ $asset->property_no }}</td>
                                    <td><strong>{{ $asset->item->name }}</strong></td>
                                    <td>{{ $asset->currentUser?->name ?? $asset->currentOffice?->name ?? $asset->currentDepartment?->name ?? '-' }}</td>
                                    <td>{{ $asset->current_location ?? '-' }}</td>
                                    <td><input class="soims-input" name="remarks[{{ $asset->id }}]" value="{{ old('remarks.'.$asset->id) }}" aria-label="Remarks for {{ $asset->property_no }}"></td>
                                </tr>
                            @empty
                                <tr><td class="soims-property-returns__empty" colspan="6">No accountable assets are registered.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
            <div class="soims-property-returns__actions"><x-ui.button type="submit">Save PRS draft</x-ui.button><a class="soims-button soims-button--secondary" href="{{ route('property-returns.index') }}">Cancel</a></div>
        </form>
    </section>
@endsection