<button {{ $attributes->merge(['type' => 'submit', 'class' => 'brand-button']) }}>
    {{ $slot }}
</button>
