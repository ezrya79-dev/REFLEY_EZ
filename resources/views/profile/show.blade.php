<x-layout.app :title="__('profile.title')">

    <x-card :title="__('profile.photoTitle')">
        <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" x-data="avatarEditor()" style="display: flex; flex-direction: column; gap: var(--space-4);">
            @csrf

            <div style="display: flex; gap: var(--space-5); align-items: flex-start; flex-wrap: wrap;">
                <x-avatar :user="$user" size="lg" />

                <div class="field" style="flex: 1; min-width: 16rem;">
                    <label class="field-label" for="photo">{{ __('profile.photoChoose') }}</label>
                    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" x-ref="file" x-on:change="pick($event)">
                    <span class="field-hint">{{ __('profile.photoHint') }}</span>
                    @error('photo')
                        <span class="field-error" role="alert">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <template x-if="image">
                <div style="display: flex; flex-direction: column; gap: var(--space-3); max-width: 16rem;">
                    <canvas
                        class="avatar-editor-canvas"
                        width="256"
                        height="256"
                        x-ref="canvas"
                        x-on:pointerdown.prevent="startDrag($event)"
                        x-on:pointermove.prevent="drag($event)"
                        x-on:pointerup="endDrag()"
                        x-on:pointerleave="endDrag()"
                    ></canvas>
                    <input type="range" min="1" max="3" step="0.05" x-on:input="setZoom($event.target.value)" aria-label="Zoom">
                </div>
            </template>

            <div class="form-actions">
                <x-button type="submit" variant="primary" x-on:click="apply($refs.file)">{{ __('profile.photoSave') }}</x-button>
            </div>
        </form>

        @if ($user->avatar_path !== null)
            <form method="POST" action="{{ route('profile.photo.delete') }}">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="secondary" size="sm">{{ __('profile.photoDelete') }}</x-button>
            </form>
        @endif
    </x-card>

    <x-card :title="__('profile.passwordTitle')">
        <form method="POST" action="{{ route('profile.password') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
            @csrf

            <x-field :label="__('profile.currentPassword')" for="current_password">
                <x-input id="current_password" name="current_password" type="password" required autocomplete="current-password" :invalid="$errors->has('current_password')" />
            </x-field>

            <x-field :label="__('profile.newPassword')" for="password">
                <x-input id="password" name="password" type="password" required autocomplete="new-password" :invalid="$errors->has('password')" />
            </x-field>

            <x-field :label="__('profile.confirmPassword')" for="password_confirmation">
                <x-input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" />
            </x-field>

            <div class="form-actions">
                <x-button type="submit" variant="primary">{{ __('ui.save') }}</x-button>
            </div>
        </form>
    </x-card>

    <x-card :title="__('profile.preferencesTitle')">
        <form method="POST" action="{{ route('profile.preferences') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
            @csrf

            <x-field :label="__('profile.theme')" for="theme">
                <x-select id="theme" name="theme" :invalid="$errors->has('theme')">
                    @foreach (\App\Enums\Theme::cases() as $theme)
                        <option value="{{ $theme->value }}" @selected($user->theme === $theme)>{{ __($theme->labelKey()) }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field :label="__('profile.language')" for="locale">
                <x-select id="locale" name="locale" :invalid="$errors->has('locale')">
                    <option value="fr" @selected($user->locale === 'fr')>Français</option>
                    <option value="en" @selected($user->locale === 'en')>English</option>
                </x-select>
            </x-field>

            <div class="form-actions">
                <x-button type="submit" variant="primary">{{ __('ui.save') }}</x-button>
            </div>
        </form>
    </x-card>

</x-layout.app>
