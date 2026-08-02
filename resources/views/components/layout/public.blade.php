@props(['title' => null, 'page' => null])

@php
    /** @var \App\Services\BrandingService $branding */
    $branding = app(\App\Services\BrandingService::class);
    $content = app(\App\Services\ContentService::class);
    $seoTitle = $page !== null ? $content->get($page, 'seo.title', app()->getLocale()) : null;
    $seoDescription = $page !== null ? $content->get($page, 'seo.description', app()->getLocale()) : null;
@endphp
<x-layout.html-shell :title="$seoTitle ?? $title" :meta-description="$seoDescription">
    <div class="public-shell">
        <header class="public-header">
            <a href="{{ route('pages.home') }}" class="sidebar-brand">
                @if ($branding->logoUrl() !== null)
                    <img src="{{ $branding->logoUrl() }}" alt="">
                @else
                    <span class="brand-mark" aria-hidden="true">{{ mb_substr($branding->appName(), 0, 1) }}</span>
                @endif
                <span>{{ $branding->appName() }}</span>
            </a>

            <nav class="public-nav" aria-label="{{ $branding->appName() }}">
                <a href="{{ route('pages.about') }}" class="nav-link {{ request()->routeIs('pages.about') ? 'is-active' : '' }}">{{ __('content.navAbout') }}</a>

                <span class="chip-row" role="group" aria-label="{{ __('profile.language') }}">
                    <a href="{{ route('pages.locale', 'fr') }}" class="chip {{ app()->getLocale() === 'fr' ? 'is-active' : '' }}">FR</a>
                    <a href="{{ route('pages.locale', 'en') }}" class="chip {{ app()->getLocale() === 'en' ? 'is-active' : '' }}">EN</a>
                </span>

                <a href="{{ route('login') }}" class="btn btn-primary btn-sm">{{ __('auth.login') }}</a>
            </nav>
        </header>

        <main class="public-main">
            {{ $slot }}
        </main>

        <footer class="public-footer">
            <span class="muted">© {{ now()->year }} {{ $branding->appName() }}</span>
            <nav class="public-footer-nav">
                <a href="{{ route('pages.legal') }}">{{ __('content.navLegal') }}</a>
                <a href="{{ route('pages.privacy') }}">{{ __('content.navPrivacy') }}</a>
            </nav>
        </footer>
    </div>
</x-layout.html-shell>
