@props(['visit', 'size' => 'md'])

@if ($visit->photo_url)
    <img src="{{ $visit->photo_url }}" alt="{{ $visit->full_name }}" {{ $attributes->merge(['class' => "avatar avatar-{$size} avatar-img"]) }}>
@else
    <span {{ $attributes->merge(['class' => "avatar avatar-{$size}"]) }} aria-hidden="true">{{ $visit->initials }}</span>
@endif
