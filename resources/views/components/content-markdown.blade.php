@props(['page', 'key'])

@php
    $service = app(\App\Services\ContentService::class);
    $value = $service->get($page, $key, app()->getLocale());
    $source = is_string($value) && $value !== '' ? $value : trim($slot);
@endphp
<div {{ $attributes->class(['md-content']) }}>
    {!! $service->renderMarkdown($source) !!}
</div>
