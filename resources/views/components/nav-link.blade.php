@props(['href', 'active' => false])

<a href="{{ $href }}" {{ $attributes->class(['nav-link', 'is-active' => $active]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
