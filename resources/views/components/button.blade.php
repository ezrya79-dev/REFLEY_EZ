@props(['variant' => 'primary', 'type' => 'button', 'size' => null])

<button type="{{ $type }}" {{ $attributes->class(['btn', 'btn-'.$variant, 'btn-sm' => $size === 'sm']) }}>
    {{ $slot }}
</button>
