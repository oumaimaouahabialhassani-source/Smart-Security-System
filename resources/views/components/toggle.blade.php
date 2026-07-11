@props(['name', 'label', 'checked' => false, 'hint' => null])

<label class="toggle-row">
    <span class="toggle-info">
        <span class="toggle-label">{{ $label }}</span>
        @if ($hint)
            <span class="toggle-hint">{{ $hint }}</span>
        @endif
    </span>
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" class="toggle-input" name="{{ $name }}" value="1" @checked($checked)>
    <span class="toggle-track" aria-hidden="true"><span class="toggle-thumb"></span></span>
</label>
