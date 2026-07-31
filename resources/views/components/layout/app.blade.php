@props(['title' => null])

@php
    /** @var \App\Services\BrandingService $branding */
    $branding = app(\App\Services\BrandingService::class);
@endphp
<x-layout.html-shell :title="$title">
    <div class="app-shell">
        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="sidebar-brand">
                @if ($branding->logoUrl() !== null)
                    <img src="{{ $branding->logoUrl() }}" alt="">
                @else
                    <span class="brand-mark" aria-hidden="true">{{ mb_substr($branding->appName(), 0, 1) }}</span>
                @endif
                <span>{{ $branding->appName() }}</span>
            </a>

            <nav class="sidebar-nav" aria-label="{{ __('ui.dashboard') }}">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('ui.dashboard') }}
                </x-nav-link>
                @can(\App\Enums\Permission::ManageUsers->value)
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                        {{ __('ui.users') }}
                    </x-nav-link>
                @endcan
                @can(\App\Enums\Permission::ManageSettings->value)
                    <x-nav-link :href="route('settings.index')" :active="request()->routeIs('settings.*')">
                        {{ __('ui.settings') }}
                    </x-nav-link>
                @endcan
            </nav>

            <div class="sidebar-footer">
                <a href="{{ route('profile.show') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">
                    <x-avatar :user="auth()->user()" />
                    <span>{{ auth()->user()->name }}</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">{{ __('auth.logout') }}</button>
                </form>
            </div>
        </aside>

        <div class="main">
            <header class="topbar">
                <div class="topbar-title">{{ $title ?? __('ui.dashboard') }}</div>
                {{ $topbar ?? '' }}
            </header>

            <main class="content">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />
</x-layout.html-shell>
