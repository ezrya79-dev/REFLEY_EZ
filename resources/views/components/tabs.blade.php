@props(['tabs' => [], 'initial' => null])

{{-- $tabs : ['clé' => 'Libellé'] ; chaque panneau est un slot nommé de la même clé. --}}
<div x-data="{ tab: '{{ $initial ?? array_key_first($tabs) }}' }">
    <div class="tabs" role="tablist">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                class="tab"
                role="tab"
                :class="{ 'is-active': tab === '{{ $key }}' }"
                :aria-selected="tab === '{{ $key }}' ? 'true' : 'false'"
                x-on:click="tab = '{{ $key }}'"
            >{{ $label }}</button>
        @endforeach
    </div>
    {{ $slot }}
</div>
