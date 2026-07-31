@props(['invalid' => false])

<select {{ $attributes->class(['select'])->merge(['aria-invalid' => $invalid ? 'true' : null]) }}>
    {{ $slot }}
</select>
