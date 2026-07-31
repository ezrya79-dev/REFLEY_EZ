@props(['invalid' => false])

<input {{ $attributes->class(['input'])->merge(['aria-invalid' => $invalid ? 'true' : null]) }}>
