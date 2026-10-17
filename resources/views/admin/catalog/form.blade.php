@extends('layouts.app')

@section('title', ucfirst($record ? 'Edit' : 'Add').' '.str_replace('_', ' ', $type).' | SOIMS')

@section('content')
    <x-ui.card class="soims-card--medium">
        <header class="soims-catalog-form__heading">
            <p class="soims-catalog-eyebrow">Administration / Catalog</p>
            <h1 class="soims-title">{{ $record ? 'Edit' : 'Add' }} {{ str_replace('_', ' ', $type) }}</h1>
        </header>
        @php
            $isEdit = (bool) $record;
            $routeType = rtrim($type, 's');
            $action = match ($type) {
                'departments' => $isEdit ? route('admin.catalog.departments.update', $record) : route('admin.catalog.departments.store'),
                'offices' => $isEdit ? route('admin.catalog.offices.update', $record) : route('admin.catalog.offices.store'),
                'categories' => $isEdit ? route('admin.catalog.categories.update', $record) : route('admin.catalog.categories.store'),
                'items' => $isEdit ? route('admin.catalog.items.update', $record) : route('admin.catalog.items.store'),
            };
        @endphp
        <form class="soims-catalog-form" method="POST" action="{{ $action }}">
            @csrf @if ($isEdit) @method('PUT') @endif
            <x-ui.input label="Code / Stock number" name="{{ $type === 'items' ? 'stock_no' : 'code' }}" value="{{ old($type === 'items' ? 'stock_no' : 'code', $record?->code ?? $record?->stock_no) }}" required />
            <x-ui.input label="Name" name="name" value="{{ old('name', $record?->name) }}" required />
            @if ($type === 'departments')
                <div class="soims-field"><label class="soims-label" for="type">Type</label><select class="soims-input" name="type" id="type" required>@foreach(['academic','non_academic','administrative'] as $option)<option value="{{ $option }}" @selected(old('type', $record?->type) === $option)>{{ ucfirst(str_replace('_', ' ', $option)) }}</option>@endforeach</select></div>
            @elseif ($type === 'offices')
                <div class="soims-field"><label class="soims-label" for="department_id">Department</label><select class="soims-input" name="department_id" id="department_id"><option value="">No department</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) old('department_id', $record?->department_id) === (string) $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            @elseif ($type === 'categories')
                <x-ui.input label="Description" name="description" value="{{ old('description', $record?->description) }}" />
            @else
                <x-ui.input label="Description" name="description" value="{{ old('description', $record?->description) }}" />
                <div class="soims-field"><label class="soims-label" for="category_id">Category</label><select class="soims-input" name="category_id" id="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $record?->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="soims-field"><label class="soims-label" for="classification">Classification</label><select class="soims-input" name="classification" id="classification" required><option value="consumable" @selected(old('classification', $record?->classification) === 'consumable')>Consumable</option><option value="accountable" @selected(old('classification', $record?->classification) === 'accountable')>Accountable</option></select></div>
                <x-ui.input label="Unit of measure" name="unit_of_measure" value="{{ old('unit_of_measure', $record?->unit_of_measure) }}" required />
                <x-ui.input label="Default unit price" name="default_unit_price" type="number" step="0.01" value="{{ old('default_unit_price', $record?->default_unit_price) }}" />
                <x-ui.input label="Reorder level" name="reorder_level" type="number" step="0.001" value="{{ old('reorder_level', $record?->reorder_level) }}" />
            @endif
            <div class="soims-catalog-form__actions"><x-ui.button type="submit">Save</x-ui.button><a class="soims-button soims-button--secondary" href="{{ route('admin.catalog.index') }}">Cancel</a></div>
        </form>
        @if ($isEdit)
            <form method="POST" action="{{ route('admin.catalog.'.($record->is_active ? 'deactivate' : 'activate'), [$type, $record->id]) }}" class="soims-danger-form">
                @csrf
                <x-ui.button type="submit" variant="{{ $record->is_active ? 'danger' : 'primary' }}">{{ $record->is_active ? 'Deactivate' : 'Activate' }}</x-ui.button>
            </form>
        @endif
    </x-ui.card>
@endsection
