@props(['user', 'size' => 'md'])

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" {{ $attributes->merge(['class' => "avatar avatar-{$size} avatar-img"]) }}>
@else
    <span {{ $attributes->merge(['class' => "avatar avatar-{$size}"]) }} aria-hidden="true">{{ $user->initials }}</span>
@endif
