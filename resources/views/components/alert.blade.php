@props(['type' => 'success'])

@php
    $classes = match ($type) {
        'danger' => 'alert-danger',
        'warn' => 'alert-warn',
        default => 'alert-success',
    };

    $icon = match ($type) {
        'danger' => 'x-circle',
        'warn' => 'alert',
        default => 'check-circle',
    };
@endphp

<div {{ $attributes->merge(['class' => $classes]) }} role="status">
    <x-icon :name="$icon" class="mt-0.5 h-5 w-5 shrink-0" />
    <div>{{ $slot }}</div>
</div>
