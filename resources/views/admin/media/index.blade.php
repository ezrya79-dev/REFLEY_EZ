<x-layout.app :title="__('media.title')">

    @if ($errors->has('media') || $errors->has('file'))
        <x-alert variant="danger">{{ $errors->first('media') ?: $errors->first('file') }}</x-alert>
    @endif

    <x-card :title="__('media.uploadTitle')">
        <form method="POST" action="{{ route('media.store') }}" enctype="multipart/form-data" style="display: flex; gap: var(--space-4); align-items: flex-end; flex-wrap: wrap;">
            @csrf

            <div class="field">
                <label class="field-label" for="file">{{ __('media.file') }}</label>
                <input id="file" name="file" type="file" accept="image/jpeg,image/png,image/webp" required>
                <span class="field-hint">{{ __('media.fileHint') }}</span>
            </div>

            <x-field :label="__('media.altFr')" for="alt_fr">
                <x-input id="alt_fr" name="alt_fr" type="text" maxlength="255" />
            </x-field>

            <x-button type="submit" variant="primary">{{ __('media.upload') }}</x-button>
        </form>
    </x-card>

    @if ($items->isEmpty())
        <x-empty-state />
    @else
        <div class="media-grid">
            @foreach ($items as $media)
                <div class="card">
                    <img src="{{ $media->derivativeUrl(480) }}" alt="{{ $media->alt_fr ?? '' }}" class="media-thumb" loading="lazy">
                    <div class="card-body">
                        <p class="muted">#{{ $media->id }} · {{ $media->width }}×{{ $media->height }}</p>

                        <form method="POST" action="{{ route('media.update', $media) }}" style="display: flex; flex-direction: column; gap: var(--space-2);">
                            @csrf
                            @method('PUT')
                            <x-input name="alt_fr" type="text" :value="$media->alt_fr" placeholder="{{ __('media.altFr') }}" maxlength="255" />
                            <x-input name="alt_en" type="text" :value="$media->alt_en" placeholder="{{ __('media.altEn') }}" maxlength="255" />
                            <div class="form-actions" style="padding-top: 0;">
                                <x-button type="submit" variant="secondary" size="sm">{{ __('ui.save') }}</x-button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('media.destroy', $media) }}" onsubmit="return confirm('{{ __('ui.confirmDelete') }}');">
                            @csrf
                            @method('DELETE')
                            <x-button type="submit" variant="danger" size="sm">{{ __('ui.delete') }}</x-button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layout.app>
