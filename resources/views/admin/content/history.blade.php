@php
    $formatValue = function (string $json): string {
        $decoded = json_decode($json, true);

        return is_string($decoded) ? $decoded : (string) json_encode($decoded, JSON_UNESCAPED_UNICODE);
    };
@endphp
<x-layout.app :title="__('content.historyTitle')">
    <p class="muted"><code>{{ $page }}</code> · <code>{{ $key }}</code></p>

    @foreach ($blocks as $block)
        <x-card :title="strtoupper($block->locale)">
            <p class="muted">{{ __('content.currentValue') }}</p>
            <pre style="white-space: pre-wrap; overflow-x: auto;"><code>{{ $formatValue($block->value) }}</code></pre>

            @forelse ($block->revisions as $revision)
                <div class="comment">
                    <pre style="white-space: pre-wrap; overflow-x: auto;"><code>{{ $formatValue($revision->value) }}</code></pre>
                    <div style="display: flex; align-items: center; gap: var(--space-3);">
                        <span class="muted">{{ $revision->author?->name ?? '—' }} · {{ $revision->created_at->diffForHumans() }}</span>
                        <form method="POST" action="{{ route('content.revert', $revision) }}">
                            @csrf
                            <x-button type="submit" variant="secondary" size="sm">{{ __('content.restore') }}</x-button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="muted">{{ __('content.noRevisions') }}</p>
            @endforelse
        </x-card>
    @endforeach

    <div>
        <a href="{{ route('content.edit', $page) }}" class="btn btn-ghost">{{ __('ui.back') }}</a>
    </div>
</x-layout.app>
