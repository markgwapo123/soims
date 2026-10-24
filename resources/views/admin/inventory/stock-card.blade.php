@extends('layouts.app')
@section('title', 'Stock Card | SOIMS')
@section('content')
<div class="soims-card"><h1 class="soims-title">Stock card</h1><p class="soims-lead">{{ $item->stock_no }} - {{ $item->name }}</p><div class="soims-table-wrap"><table class="soims-table"><thead><tr><th>Date</th><th>Type</th><th>Quantity in</th><th>Reference</th><th>Remarks</th></tr></thead><tbody>@forelse($transactions as $transaction)<tr><td>{{ $transaction->occurred_at->format('Y-m-d H:i') }}</td><td>{{ ucfirst($transaction->type) }}</td><td>{{ $transaction->type === 'receipt' ? \App\Support\QuantityFormatter::display($transaction->quantity) : '' }}</td><td>{{ $transaction->reference_type ?? '-' }}</td><td>{{ $transaction->reason ?? '-' }}</td></tr>@empty<tr><td colspan="5">No movements found.</td></tr>@endforelse</tbody></table></div></div>
@endsection
