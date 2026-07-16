@props(['user', 'size' => 'md'])

@php
    $dimensions = match ($size) {
        'sm' => 'h-9 w-9 text-xs',
        'lg' => 'h-14 w-14 text-lg',
        default => 'h-11 w-11 text-sm',
    };
@endphp

@if ($user->avatarUrl())
    <img src="{{ $user->avatarUrl() }}" alt=""
         {{ $attributes->merge(['class' => "$dimensions shrink-0 rounded-full object-cover"]) }}>
@else
    {{-- Initials, not a generic silhouette: a child recognises their own name faster. --}}
    <span aria-hidden="true"
          {{ $attributes->merge(['class' => "$dimensions shrink-0 select-none rounded-full bg-brand-soft font-extrabold text-brand inline-flex items-center justify-center"]) }}>
        {{ $user->initials() }}
    </span>
@endif
