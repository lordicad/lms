@props(['active' => false, 'block' => false, 'pill' => false])

@php
    $base = $block
        ? 'block w-full rounded-control px-4 py-3 text-base font-bold transition-colors'
        : 'inline-flex min-h-[44px] items-center rounded-control px-3 text-[15px] font-bold transition-colors';

    if ($pill) {
        // The background highlight is drawn by the shared sliding pill, so this
        // link carries only its text colour and sits above the pill.
        $base .= ' relative z-10';
        $state = $active ? 'text-brand' : 'text-ink-2 hover:text-ink';
    } else {
        $state = $active
            ? 'bg-brand-soft text-brand'
            : 'text-ink-2 hover:bg-surface-2 hover:text-ink';
    }
@endphp

<a {{ $attributes->merge(['class' => "$base $state"]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
