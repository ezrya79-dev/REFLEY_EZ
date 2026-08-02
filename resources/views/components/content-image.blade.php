@props(['page', 'key', 'sizes' => '100vw'])

@php
    $value = app(\App\Services\ContentService::class)
        ->get($page, $key, app()->getLocale());
    $media = null;

    if (is_array($value) && isset($value['media_id'])) {
        $media = \App\Models\Media::query()->find($value['media_id']);
    }
@endphp
@if ($media !== null)
    <img
        src="{{ $media->derivativeUrl(960) }}"
        srcset="{{ $media->derivativeUrl(480) }} 480w, {{ $media->derivativeUrl(960) }} 960w, {{ $media->derivativeUrl(1600) }} 1600w"
        sizes="{{ $sizes }}"
        alt="{{ $media->alt(app()->getLocale()) }}"
        loading="lazy"
        {{ $attributes }}
    >
@endif
