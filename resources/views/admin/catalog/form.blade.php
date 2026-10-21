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
                @unless($isEdit)
                    <x-ui.input label="Quantity to add (consumables only)" name="quantity" type="number" step="0.001" value="{{ old('quantity', '0') }}" required />
                    <p class="soims-lead">New consumable: initial stock. Existing consumable: quantity is ADDED to current stock. Accountable items must have quantity 0 here.</p>
                    <div id="item-lookup-result" role="status" aria-live="polite"></div>
                    <div id="existing-confirm-wrap" class="soims-field" hidden>
                        <label><input type="checkbox" name="confirm_existing" value="1" id="confirm_existing" @checked(old('confirm_existing'))> I confirm this is the correct existing item and want to ADD the entered quantity.</label>
                    </div>
                @endunless
            @endif
            @if ($errors->any())
                <div class="soims-error" role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
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
    @if($type === 'items' && !$isEdit)
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('.soims-catalog-form');
        const stock = form.elements.namedItem('stock_no');
        const info = document.getElementById('item-lookup-result');
        const wrap = document.getElementById('existing-confirm-wrap');
        const confirm = document.getElementById('confirm_existing');
        const quantity = form.elements.namedItem('quantity');
        const classification = form.elements.namedItem('classification');
        let latest = 0;
        async function check() {
            const id = ++latest;
            wrap.hidden = true;
            confirm.checked = false;
            info.textContent = '';
            if (!stock.value.trim()) return;
            info.textContent = 'Checking Stock Number...';
            try {
                const url = new URL(@json(route('admin.catalog.items.lookup')), window.location.origin);
                url.searchParams.set('stock_no', stock.value.trim());
                const response = await fetch(url, {headers: {'Accept': 'application/json'}, credentials: 'same-origin'});
                if (!response.ok) throw new Error('Lookup failed');
                const data = await response.json();
                if (id !== latest) return;
                if (!data.exists) { info.textContent = 'New item: the entered quantity will be initial stock.'; return; }
                info.textContent = `Existing item: ${data.name} | Classification: ${data.classification} | Current stock: ${data.quantity_on_hand} ${data.unit_of_measure}.`;
                if (data.classification !== 'consumable' || !data.is_active) {
                    info.textContent += ' Stock addition is not available for this item.';
                    return;
                }
                form.elements.namedItem('name').value = data.name;
                form.elements.namedItem('category_id').value = String(data.category_id);
                form.elements.namedItem('unit_of_measure').value = data.unit_of_measure;
                classification.value = data.classification;
                wrap.hidden = false;
            } catch (error) {
                if (id === latest) info.textContent = 'Could not check item. Please retry before saving.';
            }
        }
        stock.addEventListener('change', check);
        stock.addEventListener('blur', check);
        classification.addEventListener('change', () => {
            if (classification.value === 'accountable' && Number(quantity.value) > 0) {
                info.textContent = 'Accountable items must use quantity 0 here; register assets in Accountable Items.';
            }
        });
        if (stock.value.trim()) check();
    });
    </script>
    @endif
@endsection
