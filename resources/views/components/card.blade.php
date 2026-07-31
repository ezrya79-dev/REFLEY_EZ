@props(['title' => null])

<section {{ $attributes->class(['card']) }}>
    @if ($title !== null || isset($actions))
        <header class="card-header">
            <h2 class="card-title">{{ $title }}</h2>
            {{ $actions ?? '' }}
        </header>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</section>
