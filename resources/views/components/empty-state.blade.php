@props(['title' => null])

<div class="empty-state">
    <span class="empty-title">{{ $title ?? __('ui.emptyTitle') }}</span>
    <span>{{ $slot->isEmpty() ? __('ui.emptyBody') : $slot }}</span>
</div>
