<x-layout.app :title="__('users.editTitle')">
    <x-card :title="__('users.editTitle')">
        @if ($errors->has('role'))
            <x-alert variant="danger">{{ $errors->first('role') }}</x-alert>
        @endif

        <form method="POST" action="{{ route('users.update', $user) }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
            @csrf
            @method('PUT')
            @include('admin.users._form', ['user' => $user])

            <div class="form-actions">
                <x-button type="submit" variant="primary">{{ __('ui.save') }}</x-button>
                <a href="{{ route('users.index') }}" class="btn btn-ghost">{{ __('ui.cancel') }}</a>
            </div>
        </form>
    </x-card>
</x-layout.app>
