@props(['label', 'for', 'error' => null, 'hint' => null])

<div class="field">
    <label class="field-label" for="{{ $for }}">{{ $label }}</label>
    {{ $slot }}
    @if ($hint !== null)
        <span class="field-hint">{{ $hint }}</span>
    @endif
    @error($error ?? $for)
        <span class="field-error" role="alert">{{ $message }}</span>
    @enderror
</div>
