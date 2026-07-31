@props(['title' => null])

@php
    /** @var \App\Services\BrandingService $branding */
    $branding = app(\App\Services\BrandingService::class);
    $theme = auth()->user()?->theme?->value;
    $accentPreset = $branding->accentPreset();
    $accentStyle = $accentPreset === 'custom' && $branding->accentCustom() !== null
        ? '--accent: '.$branding->accentCustom().';'
        : null;
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @if ($theme !== null && $theme !== 'system') data-theme="{{ $theme }}" @endif
    @if ($accentPreset !== 'custom') data-accent="{{ $accentPreset }}" @endif
    @if ($accentStyle) style="{{ $accentStyle }}" @endif
>
<head>
    @include('partials.head', ['title' => $title])
</head>
<body>
    {{ $slot }}
</body>
</html>
