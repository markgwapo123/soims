@extends('layouts.app')

@section('title', 'Modify '.$supplyRequest->request_no.' | SOIMS')

@section('content')
    <div class="soims-card">
        <h1 class="soims-title">Modify {{ $supplyRequest->request_no }}</h1>
        <p class="soims-lead">Original requested values are never changed. Each change is recorded with its previous value, your name, and the reason.</p>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        @error('lines')<div class="soims-error">{{ $message }}</div>@enderror
        @error('lines.*')<div class="soims-error">{{ $message }}</div>@enderror

        <form method="POST" action="{{ route('modifications.update', $supplyRequest) }}">
            @csrf @method('PUT')
            <div class="soims-table-wrap">
                <table class="soims-table">
                    <thead><tr><th>Requested item</th><th>Requested qty</th><th>Confirmed item</th><th>Confirmed qty</th></tr></thead>
                    <tbody>
                        @foreach ($supplyRequest->items as $index => $line)
                            @php($currentItemId = $line->confirmed_item_id ?? $line->item_id)
                            <tr>
                                <td>{{ $line->item->stock_no }} - {{ $line->item->name }}</td>
                                <td>{{ \App\Support\QuantityFormatter::display($line->requested_quantity) }}</td>
                                <td>
                                    <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                    <select class="soims-input" name="lines[{{ $index }}][confirmed_item_id]" aria-label="Confirmed item for {{ $line->item->name }}">
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}" @selected($item->id === $currentItemId)>{{ $item->stock_no }} - {{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="soims-input" type="number" min="0" step="0.001" name="lines[{{ $index }}][confirmed_quantity]" value="{{ $line->confirmed_quantity }}" aria-label="Confirmed quantity for {{ $line->item->name }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="soims-field">
                <label class="soims-label" for="reason">Reason (required)</label>
                <textarea class="soims-input" id="reason" name="reason" rows="3" required>{{ old('reason') }}</textarea>
                @error('reason')<div class="soims-error">{{ $message }}</div>@enderror
            </div>
            <div class="soims-actions"><x-ui.button type="submit">Save modification</x-ui.button><a href="{{ route('modifications.index') }}">Back</a></div>
        </form>

        <h2 class="soims-section-title">Modification history</h2>
        @forelse ($supplyRequest->modifications as $modification)
            <p>{{ $modification->fieldLabel() }}: {{ $modification->describe($modification->previous_value) }} &rarr; {{ $modification->describe($modification->new_value) }}
                &middot; {{ $modification->modifier->name }} ({{ $modification->role_snapshot }}) &middot; {{ $modification->modified_at->format('Y-m-d H:i') }} &mdash; {{ $modification->reason }}</p>
        @empty
            <p>No modifications yet.</p>
        @endforelse
    </div>
@endsection
