@props(['type' => 'button', 'variant' => 'primary'])

<button type="{{ $type }}" {{ $attributes->merge(['class' => "soims-button soims-button--{$variant}"]) }}>
    {{ $slot }}
</button>
