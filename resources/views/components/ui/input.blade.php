@props(['label', 'name', 'type' => 'text', 'value' => ''])

<div class="soims-field">
    <label class="soims-label" for="{{ $name }}">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" {{ $attributes->merge(['class' => 'soims-input']) }}>
    @error($name)
        <div class="soims-error">{{ $message }}</div>
    @enderror
</div>
