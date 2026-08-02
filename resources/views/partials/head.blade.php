@php
    /** @var \App\Services\BrandingService $branding */
    $branding = app(\App\Services\BrandingService::class);
    $icons = $branding->iconUrls();
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? $branding->appName() }}</title>
@if (isset($metaDescription) && is_string($metaDescription) && $metaDescription !== '')
    <meta name="description" content="{{ $metaDescription }}">
@endif
@if (isset($icons[32]))
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $icons[32] }}">
@endif
@if (isset($icons[16]))
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $icons[16] }}">
@endif
@if (isset($icons[180]))
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $icons[180] }}">
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
