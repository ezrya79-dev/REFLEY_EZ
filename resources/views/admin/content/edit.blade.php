<x-layout.app :title="__($pageConfig['titleKey'])">
    <x-slot:topbar>
        <a href="{{ route($pageConfig['route']) }}" class="btn btn-ghost btn-sm" target="_blank">{{ __('content.view') }}</a>
    </x-slot:topbar>

    {{-- Onglets de langue : chaque locale a ses propres valeurs (repli EN → FR). --}}
    <nav class="chip-row" aria-label="{{ __('profile.language') }}">
        <a href="{{ route('content.edit', ['page' => $page, 'locale' => 'fr']) }}" class="chip {{ $locale === 'fr' ? 'is-active' : '' }}">Français</a>
        <a href="{{ route('content.edit', ['page' => $page, 'locale' => 'en']) }}" class="chip {{ $locale === 'en' ? 'is-active' : '' }}">English</a>
    </nav>

    <form method="POST" action="{{ route('content.update', $page) }}" style="display: flex; flex-direction: column; gap: var(--space-5);">
        @csrf
        @method('PUT')
        <input type="hidden" name="locale" value="{{ $locale }}">

        @foreach ($zones as $zone)
            @php $value = $values[$zone['key']] ?? null; @endphp
            <x-card>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: var(--space-3);">
                    <strong><code>{{ $zone['key'] }}</code></strong>
                    <a href="{{ route('content.history', ['page' => $page, 'key' => $zone['key']]) }}" class="muted">{{ __('content.historyLink') }}</a>
                </div>

                @if ($zone['type'] === 'text')
                    <x-input name="blocks[{{ $zone['key'] }}]" type="text" :value="is_string($value) ? $value : ''" maxlength="500" />
                @elseif ($zone['type'] === 'markdown')
                    <div x-data="{ preview: null, async load(source) {
                        const response = await fetch('{{ route('content.preview') }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ source }),
                        });
                        this.preview = (await response.json()).html;
                    } }" style="display: flex; flex-direction: column; gap: var(--space-3);">
                        <textarea name="blocks[{{ $zone['key'] }}]" class="input" rows="8" x-ref="source">{{ is_string($value) ? $value : '' }}</textarea>
                        <div>
                            <x-button variant="ghost" size="sm" x-on:click="load($refs.source.value)">{{ __('content.preview') }}</x-button>
                        </div>
                        <template x-if="preview !== null">
                            <div class="card"><div class="card-body md-content" x-html="preview"></div></div>
                        </template>
                    </div>
                @else
                    @php $currentMediaId = is_array($value) ? ($value['media_id'] ?? null) : null; @endphp
                    <div style="display: flex; gap: var(--space-4); align-items: center; flex-wrap: wrap;">
                        @if ($currentMediaId !== null)
                            @php $current = $mediaLibrary->firstWhere('id', $currentMediaId); @endphp
                            @if ($current !== null)
                                <img src="{{ $current->derivativeUrl(480) }}" alt="{{ $current->alt($locale) }}" class="logo-preview">
                            @endif
                        @endif
                        <x-select name="images[{{ $zone['key'] }}]" style="max-width: 20rem;">
                            <option value="">{{ __('content.noImage') }}</option>
                            @foreach ($mediaLibrary as $media)
                                <option value="{{ $media->id }}" @selected($currentMediaId === $media->id)>
                                    #{{ $media->id }} — {{ $media->alt_fr ?? basename($media->path) }}
                                </option>
                            @endforeach
                        </x-select>
                        <a href="{{ route('media.index') }}" class="muted">{{ __('content.manageMedia') }}</a>
                    </div>
                @endif
            </x-card>
        @endforeach

        <div class="form-actions">
            <x-button type="submit" variant="primary">{{ __('ui.save') }}</x-button>
            <a href="{{ route('content.index') }}" class="btn btn-ghost">{{ __('ui.back') }}</a>
        </div>
    </form>
</x-layout.app>
