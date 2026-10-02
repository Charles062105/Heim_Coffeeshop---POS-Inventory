@props(['value'])

<label {{ $attributes->merge(['class' => 'brand-label']) }}>
    {{ $value ?? $slot }}
</label>
