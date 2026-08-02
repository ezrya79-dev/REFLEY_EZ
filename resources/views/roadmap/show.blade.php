<x-layout.app :title="$feature->title">

    <x-card>
        <div class="idea-meta">
            <x-badge :variant="$feature->status->badgeVariant()">{{ __($feature->status->labelKey()) }}</x-badge>
            <x-badge variant="neutral">{{ __($feature->categoryLabelKey()) }}</x-badge>
            @if ($feature->priority !== \App\Enums\FeaturePriority::None)
                <x-badge variant="warn">{{ __($feature->priority->labelKey()) }}</x-badge>
            @endif
            @if ($feature->difficulty !== \App\Enums\FeatureDifficulty::Unknown)
                <x-badge variant="accent">{{ __($feature->difficulty->labelKey()) }}</x-badge>
            @endif
        </div>

        <p style="white-space: pre-line;">{{ $feature->description }}</p>

        <p class="muted">
            {{ $feature->author?->name ?? '—' }} · {{ $feature->created_at->translatedFormat('d MMMM Y') }}
        </p>

        <div class="idea-actions">
            <form method="POST" action="{{ route('roadmap.vote', $feature) }}">
                @csrf
                <button type="submit" class="vote-btn {{ $feature->hasVoteFrom(auth()->user()) ? 'is-voted' : '' }}">
                    ▲ {{ $feature->votes_count }} {{ __('roadmap.votes') }}
                </button>
            </form>

            @if (auth()->user()->can(\App\Enums\Permission::ManageRoadmap->value)
                || ($feature->user_id === auth()->id() && $feature->status === \App\Enums\FeatureStatus::Proposed))
                <form method="POST" action="{{ route('roadmap.destroy', $feature) }}" onsubmit="return confirm('{{ __('ui.confirmDelete') }}');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm">{{ __('ui.delete') }}</x-button>
                </form>
            @endif

            <a href="{{ route('roadmap.index') }}" class="btn btn-ghost btn-sm">{{ __('ui.back') }}</a>
        </div>
    </x-card>

    {{-- Arbitrage : réservé à roadmap.manage. --}}
    @can(\App\Enums\Permission::ManageRoadmap->value)
        <x-card :title="__('roadmap.arbitrationTitle')">
            <form method="POST" action="{{ route('roadmap.update', $feature) }}" style="display: flex; gap: var(--space-4); flex-wrap: wrap; align-items: flex-end;">
                @csrf
                @method('PUT')

                <x-field :label="__('roadmap.statusLabel')" for="status">
                    <x-select id="status" name="status">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected($feature->status === $status)>{{ __($status->labelKey()) }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field :label="__('roadmap.priorityLabel')" for="priority">
                    <x-select id="priority" name="priority">
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected($feature->priority === $priority)>{{ __($priority->labelKey()) }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-field :label="__('roadmap.difficultyLabel')" for="difficulty">
                    <x-select id="difficulty" name="difficulty">
                        @foreach ($difficulties as $difficulty)
                            <option value="{{ $difficulty->value }}" @selected($feature->difficulty === $difficulty)>{{ __($difficulty->labelKey()) }}</option>
                        @endforeach
                    </x-select>
                </x-field>

                <x-button type="submit" variant="primary">{{ __('ui.save') }}</x-button>
            </form>
        </x-card>
    @endcan

    <x-card :title="__('roadmap.commentsTitle')">
        @forelse ($feature->comments as $comment)
            <div class="comment">
                <p style="white-space: pre-line; margin-bottom: var(--space-1);">{{ $comment->body }}</p>
                <p class="muted">{{ $comment->author?->name ?? '—' }} · {{ $comment->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <p class="muted">{{ __('roadmap.noComments') }}</p>
        @endforelse

        <form method="POST" action="{{ route('roadmap.comment', $feature) }}" style="display: flex; flex-direction: column; gap: var(--space-3);">
            @csrf
            <x-field :label="__('roadmap.commentLabel')" for="body">
                <textarea id="body" name="body" class="input" rows="3" maxlength="2000" required>{{ old('body') }}</textarea>
            </x-field>
            <div class="form-actions">
                <x-button type="submit" variant="secondary">{{ __('roadmap.comment') }}</x-button>
            </div>
        </form>
    </x-card>

</x-layout.app>
