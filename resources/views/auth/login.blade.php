<x-layout.guest :title="__('auth.login')">
    <x-card :title="__('auth.login')">
        <form method="POST" action="{{ route('login.store') }}" class="card-body" style="padding: 0; gap: var(--space-4);">
            @csrf

            <x-field :label="__('auth.email')" for="email">
                <x-input id="email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" :invalid="$errors->has('email')" />
            </x-field>

            <x-field :label="__('auth.passwordLabel')" for="password">
                <x-input id="password" name="password" type="password" required autocomplete="current-password" :invalid="$errors->has('password')" />
            </x-field>

            <label class="checkbox-row">
                <input type="checkbox" name="remember">
                <span>{{ __('auth.remember') }}</span>
            </label>

            <x-button type="submit" variant="primary">{{ __('auth.submit') }}</x-button>
        </form>
    </x-card>
</x-layout.guest>
