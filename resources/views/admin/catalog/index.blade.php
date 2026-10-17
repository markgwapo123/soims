@extends('layouts.app')

@section('title', 'Catalog Management | SOIMS')

@section('content')
    @php
        $groups = [
            ['title' => 'Departments', 'type' => 'departments', 'records' => $departments, 'add' => 'Add department'],
            ['title' => 'Offices', 'type' => 'offices', 'records' => $offices, 'add' => 'Add office'],
            ['title' => 'Categories', 'type' => 'categories', 'records' => $categories, 'add' => 'Add category'],
            ['title' => 'Items', 'type' => 'items', 'records' => $items, 'add' => 'Add item'],
        ];
    @endphp
    <div class="soims-card soims-catalog">
        <header class="soims-catalog-heading">
            <div>
                <p class="soims-catalog-eyebrow">Administration / Catalog</p>
                <h1 class="soims-title">Catalog management</h1>
            </div>
            @php($totalRecords = collect($groups)->sum(fn ($group) => $group['records']->count()))
            <span class="soims-catalog-total">{{ $totalRecords }} {{ $totalRecords === 1 ? 'record' : 'records' }}</span>
        </header>
        @if (session('status'))<p class="soims-status"><span class="soims-status__dot"></span>{{ session('status') }}</p>@endif
        <nav class="soims-catalog-shortcuts" aria-label="Catalog sections">
            @foreach ($groups as $group)
                <a class="soims-catalog-shortcut" href="#{{ $group['type'] }}">
                    <span>{{ $group['title'] }}</span>
                    <strong>{{ $group['records']->count() }}</strong>
                    <small>Browse section</small>
                </a>
            @endforeach
        </nav>
        @foreach ($groups as $group)
            <section class="soims-catalog-section" id="{{ $group['type'] }}">
            <header class="soims-catalog-section__heading">
                <div><h2 class="soims-section-title">{{ $group['title'] }}</h2><span>{{ $group['records']->count() }} {{ $group['records']->count() === 1 ? 'record' : 'records' }}</span></div>
                <a class="soims-button soims-button--secondary" href="{{ route('admin.catalog.create', $group['type']) }}">{{ $group['add'] }}</a>
            </header>
            <div class="soims-table-wrap soims-catalog-table-wrap"><table class="soims-table soims-catalog-table"><thead><tr><th>Code / Stock no.</th><th>Name</th><th>Status</th><th></th></tr></thead><tbody>
                @forelse ($group['records'] as $record)
                    <tr><td class="soims-catalog-code">{{ $record->code ?? $record->stock_no }}</td><td><strong>{{ $record->name }}</strong></td><td><span class="soims-catalog-status {{ $record->is_active ? 'is-active' : 'is-inactive' }}">{{ $record->is_active ? 'Active' : 'Inactive' }}</span></td><td><a class="soims-catalog-edit" href="{{ route('admin.catalog.edit', [$group['type'], $record->id]) }}">Edit</a></td></tr>
                @empty
                    <tr><td class="soims-catalog-empty" colspan="4">No {{ strtolower($group['title']) }} found.</td></tr>
                @endforelse
            </tbody></table></div>
            </section>
        @endforeach
    </div>
@endsection
