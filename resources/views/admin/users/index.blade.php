<x-layout.app :title="__('users.title')">
    <x-slot:topbar>
        <a href="{{ route('users.create') }}" class="btn btn-primary">{{ __('ui.create') }}</a>
    </x-slot:topbar>

    @if ($errors->has('user'))
        <x-alert variant="danger">{{ $errors->first('user') }}</x-alert>
    @endif

    @if ($users->isEmpty())
        <x-empty-state />
    @else
        <x-table>
            <x-slot:head>
                <tr>
                    <th>{{ __('users.name') }}</th>
                    <th>{{ __('users.email') }}</th>
                    <th>{{ __('users.role') }}</th>
                    <th>{{ __('users.active') }}</th>
                    <th><span class="cell-actions">{{ __('ui.actions') }}</span></th>
                </tr>
            </x-slot:head>

            @foreach ($users as $user)
                <tr>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: var(--space-2);">
                            <x-avatar :user="$user" />
                            {{ $user->name }}
                        </span>
                    </td>
                    <td>{{ $user->email }}</td>
                    <td><x-badge variant="accent">{{ __($user->role->labelKey()) }}</x-badge></td>
                    <td>
                        @if ($user->is_active)
                            <x-badge variant="success">{{ __('users.statusActive') }}</x-badge>
                        @else
                            <x-badge variant="danger">{{ __('users.statusInactive') }}</x-badge>
                        @endif
                    </td>
                    <td>
                        <span class="cell-actions">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-secondary btn-sm">{{ __('ui.edit') }}</a>
                            <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('{{ __('ui.confirmDelete') }}');">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" size="sm">{{ __('ui.delete') }}</x-button>
                            </form>
                        </span>
                    </td>
                </tr>
            @endforeach
        </x-table>
    @endif
</x-layout.app>
