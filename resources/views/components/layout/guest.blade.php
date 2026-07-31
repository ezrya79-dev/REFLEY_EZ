@props(['title' => null])

@php
    /** @var \App\Services\BrandingService $branding */
    $branding = app(\App\Services\BrandingService::class);
@endphp
<x-layout.html-shell :title="$title">
    <div class="guest-shell">
        <div class="guest-card">
            <div class="guest-brand">
                @if ($branding->logoUrl() !== null)
                    <img src="{{ $branding->logoUrl() }}" alt="">
                @endif
                <span>{{ $branding->appName() }}</span>
            </div>
            {{ $slot }}
        </div>
    </div>
</x-layout.html-shell>
