<x-layout.app :title="__('users.createTitle')">
    <x-card :title="__('users.createTitle')">
        <form method="POST" action="{{ route('users.store') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
            @csrf
            @include('admin.users._form', ['user' => null])

            <div class="form-actions">
                <x-button type="submit" variant="primary">{{ __('ui.create') }}</x-button>
                <a href="{{ route('users.index') }}" class="btn btn-ghost">{{ __('ui.cancel') }}</a>
            </div>
        </form>
    </x-card>
</x-layout.app>
