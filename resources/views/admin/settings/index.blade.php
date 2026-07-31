<x-layout.app :title="__('settings.title')">

    @if (session('smtp_test_ok'))
        <x-alert variant="success">{{ __('settings.smtpTestOk', ['message' => session('smtp_test_ok')]) }}</x-alert>
    @endif
    @if (session('smtp_test_error'))
        <x-alert variant="danger">{{ __('settings.smtpTestError', ['message' => session('smtp_test_error')]) }}</x-alert>
    @endif

    <x-tabs :tabs="[
        'branding' => __('settings.tabBranding'),
        'connectors' => __('settings.tabConnectors'),
        'permissions' => __('settings.tabPermissions'),
    ]">

        {{-- ------------------------------------------------ Marque --}}
        <div class="tab-panel" x-show="tab === 'branding'">
            <x-card :title="__('settings.tabBranding')">
                <form method="POST" action="{{ route('settings.branding') }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: var(--space-4);" x-data="{ preset: '{{ old('accent_preset', $branding->accentPreset()) }}' }">
                    @csrf

                    <x-field :label="__('settings.appName')" for="app_name">
                        <x-input id="app_name" name="app_name" type="text" :value="old('app_name', $branding->appName())" required :invalid="$errors->has('app_name')" />
                    </x-field>

                    <div class="field">
                        <span class="field-label">{{ __('settings.accent') }}</span>
                        <div style="display: flex; gap: var(--space-2); align-items: center; flex-wrap: wrap;">
                            @foreach ($accents as $key => $hex)
                                <label>
                                    <input type="radio" name="accent_preset" value="{{ $key }}" x-model="preset" style="position: absolute; opacity: 0;">
                                    <span class="accent-swatch" :class="{ 'is-selected': preset === '{{ $key }}' }" style="background: {{ $hex }};" title="{{ $key }}"></span>
                                </label>
                            @endforeach
                            <label class="checkbox-row">
                                <input type="radio" name="accent_preset" value="custom" x-model="preset">
                                <span>{{ __('settings.accentCustomOption') }}</span>
                            </label>
                        </div>
                        @error('accent_preset')
                            <span class="field-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <template x-if="preset === 'custom'">
                        <x-field :label="__('settings.accentCustom')" for="accent_custom">
                            <x-input id="accent_custom" name="accent_custom" type="text" placeholder="#0f766e" :value="old('accent_custom', $branding->accentCustom())" :invalid="$errors->has('accent_custom')" />
                        </x-field>
                    </template>

                    <div class="field">
                        <span class="field-label">{{ __('settings.logo') }}</span>
                        @if ($branding->logoUrl() !== null)
                            <img src="{{ $branding->logoUrl() }}" alt="" class="logo-preview">
                        @endif
                        <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
                        <span class="field-hint">{{ __('settings.logoHint') }}</span>
                        @error('logo')
                            <span class="field-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <x-field :label="__('settings.emailFromName')" for="email_from_name">
                        <x-input id="email_from_name" name="email_from_name" type="text" :value="old('email_from_name', $branding->emailFromName())" required :invalid="$errors->has('email_from_name')" />
                    </x-field>

                    <x-field :label="__('settings.emailFromAddress')" for="email_from_address">
                        <x-input id="email_from_address" name="email_from_address" type="email" :value="old('email_from_address', $branding->emailFromAddress())" required :invalid="$errors->has('email_from_address')" />
                    </x-field>

                    <div class="form-actions">
                        <x-button type="submit" variant="primary">{{ __('ui.save') }}</x-button>
                    </div>
                </form>

                @if ($branding->logoUrl() !== null)
                    <form method="POST" action="{{ route('settings.logo.delete') }}">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="secondary" size="sm">{{ __('settings.logoDelete') }}</x-button>
                    </form>
                @endif
            </x-card>
        </div>

        {{-- ------------------------------------------------ Connecteurs --}}
        <div class="tab-panel" x-show="tab === 'connectors'" x-cloak>
            @can(\App\Enums\Permission::ManageConnectors->value)
                <x-card :title="__('settings.smtpTitle')">
                    {{-- Écriture seule : le formulaire ne ré-affiche jamais le secret. --}}
                    <form method="POST" action="{{ route('settings.smtp') }}" style="display: flex; flex-direction: column; gap: var(--space-4);">
                        @csrf

                        <x-field :label="__('settings.smtpHost')" for="host">
                            <x-input id="host" name="host" type="text" :value="old('host')" required :invalid="$errors->has('host')" />
                        </x-field>

                        <x-field :label="__('settings.smtpPort')" for="port">
                            <x-input id="port" name="port" type="number" min="1" max="65535" :value="old('port', 587)" required :invalid="$errors->has('port')" />
                        </x-field>

                        <x-field :label="__('settings.smtpUsername')" for="username">
                            <x-input id="username" name="username" type="text" :value="old('username')" autocomplete="off" :invalid="$errors->has('username')" />
                        </x-field>

                        <x-field :label="__('settings.smtpPassword')" for="password" :hint="$smtpConfigured ? __('settings.smtpPasswordConfigured') : null">
                            <x-input id="password" name="password" type="password" autocomplete="new-password" :invalid="$errors->has('password')" />
                        </x-field>

                        <x-field :label="__('settings.smtpEncryption')" for="encryption">
                            <x-select id="encryption" name="encryption" :invalid="$errors->has('encryption')">
                                <option value="tls" @selected(old('encryption', 'tls') === 'tls')>TLS</option>
                                <option value="none" @selected(old('encryption') === 'none')>—</option>
                            </x-select>
                        </x-field>

                        <div class="form-actions">
                            <x-button type="submit" variant="primary">{{ __('ui.save') }}</x-button>
                        </div>
                    </form>

                    @if ($smtpConfigured)
                        <form method="POST" action="{{ route('settings.smtp.test') }}">
                            @csrf
                            <x-button type="submit" variant="secondary">{{ __('settings.smtpTest') }}</x-button>
                        </form>
                    @endif
                </x-card>
            @endcan
        </div>

        {{-- ------------------------------------------------ Permissions --}}
        <div class="tab-panel" x-show="tab === 'permissions'" x-cloak>
            <x-card :title="__('rbac.matrixTitle')">
                <p class="muted">{{ __('rbac.matrixHint') }}</p>

                <x-table>
                    <x-slot:head>
                        <tr>
                            <th></th>
                            @foreach ($roles as $role)
                                <th>{{ __($role->labelKey()) }}</th>
                            @endforeach
                        </tr>
                    </x-slot:head>

                    @foreach ($permissions as $permission)
                        <tr>
                            <td>
                                {{ __($permission->labelKey()) }}
                                <br><code>{{ $permission->value }}</code>
                            </td>
                            @foreach ($roles as $role)
                                <td>
                                    @if ($role->hasPermission($permission))
                                        <span class="matrix-check" aria-label="{{ __('ui.yes') }}">✓</span>
                                    @else
                                        <span class="matrix-none" aria-label="{{ __('ui.no') }}">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        </div>

    </x-tabs>
</x-layout.app>
