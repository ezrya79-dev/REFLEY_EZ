@props(['user', 'size' => null])

@if ($user->avatarUrl() !== null)
    <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" {{ $attributes->class(['avatar', 'avatar-lg' => $size === 'lg']) }}>
@else
    <span {{ $attributes->class(['avatar', 'avatar-lg' => $size === 'lg']) }} aria-hidden="true">{{ $user->initials() }}</span>
@endif
