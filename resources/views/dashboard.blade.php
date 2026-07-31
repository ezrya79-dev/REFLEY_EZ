<x-layout.app :title="__('ui.dashboard')">
    <h1>{{ __('ui.welcome', ['name' => auth()->user()->name]) }}</h1>

    @if ($metrics !== null)
        <section aria-label="{{ __('ui.metricsTitle') }}" class="stat-grid">
            <div class="stat-tile">
                <span class="stat-value">{{ $metrics['users_total'] }}</span>
                <span class="stat-label">{{ __('ui.metricsUsersTotal') }}</span>
            </div>
            <div class="stat-tile">
                <span class="stat-value">{{ $metrics['users_active'] }}</span>
                <span class="stat-label">{{ __('ui.metricsUsersActive') }}</span>
            </div>
        </section>
    @endif
</x-layout.app>
