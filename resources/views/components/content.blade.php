@props(['page', 'key'])

@php
    $value = app(\App\Services\ContentService::class)
        ->get($page, $key, app()->getLocale());
@endphp
{{ is_string($value) && $value !== '' ? $value : trim($slot) }}
