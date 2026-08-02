<x-layout.app :title="__('content.title')">
    <x-slot:topbar>
        <form method="POST" action="{{ route('content.rescan') }}">
            @csrf
            <x-button type="submit" variant="secondary" size="sm">{{ __('content.rescan') }}</x-button>
        </form>
    </x-slot:topbar>

    <x-table>
        <x-slot:head>
            <tr>
                <th>{{ __('content.pageColumn') }}</th>
                <th>{{ __('content.zonesColumn') }}</th>
                <th><span class="cell-actions">{{ __('ui.actions') }}</span></th>
            </tr>
        </x-slot:head>

        @foreach ($pages as $slug => $pageConfig)
            <tr>
                <td>
                    {{ __($pageConfig['titleKey']) }}
                    <br><code>/{{ $slug === 'accueil' ? '' : $slug }}</code>
                </td>
                <td>{{ count($map[$slug] ?? []) }}</td>
                <td>
                    <span class="cell-actions">
                        <a href="{{ route($pageConfig['route']) }}" class="btn btn-ghost btn-sm" target="_blank">{{ __('content.view') }}</a>
                        <a href="{{ route('content.edit', $slug) }}" class="btn btn-secondary btn-sm">{{ __('ui.edit') }}</a>
                    </span>
                </td>
            </tr>
        @endforeach
    </x-table>
</x-layout.app>
