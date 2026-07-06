@props(['status'])

<span {{ $attributes->merge(['class' => 'badge '.$status->badge()]) }}>
    <span class="badge-indicator" aria-hidden="true"></span>{{ $status->label() }}
</span>
