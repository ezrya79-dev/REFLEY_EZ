<x-layout.app :title="__('roadmap.title')">
    <x-slot:topbar>
        <x-button variant="primary" x-data x-on:click="$dispatch('open-modal', 'propose-idea')">
            {{ __('roadmap.propose') }}
        </x-button>
    </x-slot:topbar>

    @if ($errors->has('feature'))
        <x-alert variant="danger">{{ $errors->first('feature') }}</x-alert>
    @endif

    {{-- Filtre par catégorie (modules / parties de l'application). --}}
    <nav class="chip-row" aria-label="{{ __('roadmap.category') }}">
        <a href="{{ route('roadmap.index') }}" class="chip {{ $activeCategory === null ? 'is-active' : '' }}">
            {{ __('roadmap.allCategories') }}
        </a>
        @foreach ($categories as $slug)
            <a
                href="{{ route('roadmap.index', ['categorie' => $slug]) }}"
                class="chip {{ $activeCategory === $slug ? 'is-active' : '' }}"
            >{{ __('roadmap.cat'.str_replace('-', '', ucwords($slug, '-'))) }}</a>
        @endforeach
    </nav>

    {{-- Tableau visuel : une colonne par statut. --}}
    <div class="board">
        @foreach ($columns as $status)
            @php $items = $features->get($status->value, collect()); @endphp
            <section class="board-col" aria-label="{{ __($status->labelKey()) }}">
                <header class="board-col-header">
                    <x-badge :variant="$status->badgeVariant()">{{ __($status->labelKey()) }}</x-badge>
                    <span class="muted">{{ $items->count() }}</span>
                </header>

                @forelse ($items as $feature)
                    <article class="idea-card">
                        <a href="{{ route('roadmap.show', $feature) }}" class="idea-title">{{ $feature->title }}</a>
                        <div class="idea-meta">
                            <x-badge variant="neutral">{{ __($feature->categoryLabelKey()) }}</x-badge>
                            @if ($feature->priority !== \App\Enums\FeaturePriority::None)
                                <x-badge variant="warn">{{ __($feature->priority->labelKey()) }}</x-badge>
                            @endif
                        </div>
                        <div class="idea-actions">
                            <form method="POST" action="{{ route('roadmap.vote', $feature) }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="vote-btn {{ $feature->hasVoteFrom(auth()->user()) ? 'is-voted' : '' }}"
                                    title="{{ __('roadmap.vote') }}"
                                >▲ {{ $feature->votes_count }}</button>
                            </form>
                            <span class="muted">💬 {{ $feature->comments_count }}</span>
                        </div>
                    </article>
                @empty
                    <p class="muted">{{ __('roadmap.emptyColumn') }}</p>
                @endforelse
            </section>
        @endforeach
    </div>

    {{-- Idées refusées : visibles mais repliées — l'honnêteté du cycle de vie. --}}
    @if ($declined->isNotEmpty())
        <details>
            <summary class="muted">{{ __('roadmap.declinedSection') }} ({{ $declined->count() }})</summary>
            <div class="card-body">
                @foreach ($declined as $feature)
                    <a href="{{ route('roadmap.show', $feature) }}" class="idea-title">{{ $feature->title }}</a>
                @endforeach
            </div>
        </details>
    @endif

    {{-- Proposition d'idée : ouverte à tout le monde. --}}
    <x-modal name="propose-idea" :title="__('roadmap.proposeTitle')">
        <form method="POST" action="{{ route('roadmap.store') }}" id="propose-form" style="display: flex; flex-direction: column; gap: var(--space-4);">
            @csrf

            <x-field :label="__('roadmap.ideaTitle')" for="title">
                <x-input id="title" name="title" type="text" maxlength="120" :value="old('title')" required :invalid="$errors->has('title')" />
            </x-field>

            <x-field :label="__('roadmap.category')" for="category">
                <x-select id="category" name="category" :invalid="$errors->has('category')">
                    @foreach ($categories as $slug)
                        <option value="{{ $slug }}" @selected(old('category') === $slug)>
                            {{ __('roadmap.cat'.str_replace('-', '', ucwords($slug, '-'))) }}
                        </option>
                    @endforeach
                </x-select>
            </x-field>

            <x-field :label="__('roadmap.ideaDescription')" for="description">
                <textarea id="description" name="description" class="input" rows="5" maxlength="5000" required>{{ old('description') }}</textarea>
            </x-field>
        </form>

        <x-slot:actions>
            <x-button type="submit" variant="primary" form="propose-form">{{ __('roadmap.propose') }}</x-button>
        </x-slot:actions>
    </x-modal>
</x-layout.app>
