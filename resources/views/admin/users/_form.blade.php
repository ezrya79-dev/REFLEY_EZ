{{-- Champs partagés création / édition. $user est null en création. --}}
<x-field :label="__('users.name')" for="name">
    <x-input id="name" name="name" type="text" :value="old('name', $user?->name)" required :invalid="$errors->has('name')" />
</x-field>

<x-field :label="__('users.email')" for="email">
    <x-input id="email" name="email" type="email" :value="old('email', $user?->email)" required :invalid="$errors->has('email')" />
</x-field>

<x-field :label="$user === null ? __('users.password') : __('users.passwordOptional')" for="password">
    <x-input id="password" name="password" type="password" autocomplete="new-password" :invalid="$errors->has('password')" />
</x-field>

<x-field :label="__('users.role')" for="role" error="role">
    <x-select id="role" name="role" :invalid="$errors->has('role')">
        @foreach (\App\Enums\UserRole::cases() as $role)
            <option value="{{ $role->value }}" @selected(old('role', $user?->role?->value) === $role->value)>{{ __($role->labelKey()) }}</option>
        @endforeach
    </x-select>
</x-field>

<label class="checkbox-row">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user?->is_active ?? true))>
    <span>{{ __('users.active') }}</span>
</label>
