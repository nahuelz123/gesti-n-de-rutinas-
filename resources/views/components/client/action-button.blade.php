@props(['variant' => 'primary'])

<button {{ $attributes->merge(['class' => 'client-btn client-btn-' . $variant]) }}>
    {{ $slot }}
</button>
