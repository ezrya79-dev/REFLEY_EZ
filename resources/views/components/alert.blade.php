@props(['variant' => 'success'])

<div {{ $attributes->class(['alert', 'alert-'.$variant]) }} role="alert">
    {{ $slot }}
</div>
