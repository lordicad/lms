@props(['subject', 'class' => 'text-2xl'])

{{--
    A vector icon per subject (from the shared icon set, mapped by slug in Subject::iconName()).
    Callers still size it with the old text-* class they used for the emoji; that maps to an icon
    dimension here. Decorative — every subject also carries its name — and it strokes in
    currentColor, so inside a coloured chip it matches the chip's text.
--}}

@php
    $sizeMap = [
        'text-sm' => 'h-4 w-4',
        'text-base' => 'h-[18px] w-[18px]',
        'text-lg' => 'h-5 w-5',
        'text-xl' => 'h-6 w-6',
        'text-2xl' => 'h-7 w-7',
    ];

    $iconClass = $sizeMap[trim($class)] ?? 'h-5 w-5';
@endphp

<x-icon :name="$subject->iconName()" :class="$iconClass" style="display:inline-block;vertical-align:-.15em;flex-shrink:0" />
