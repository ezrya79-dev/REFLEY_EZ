@props(['name', 'title' => null])

{{-- S'ouvre via $dispatch('open-modal', '{{ $name }}') depuis n'importe quel bouton. --}}
<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:keydown.escape.window="open = false"
>
    <template x-if="open">
        <div class="modal-backdrop" x-on:click.self="open = false">
            <div class="modal" role="dialog" aria-modal="true" @if ($title) aria-label="{{ $title }}" @endif>
                @if ($title !== null)
                    <h3 class="modal-title">{{ $title }}</h3>
                @endif
                {{ $slot }}
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" x-on:click="open = false">{{ __('ui.close') }}</button>
                    {{ $actions ?? '' }}
                </div>
            </div>
        </div>
    </template>
</div>
