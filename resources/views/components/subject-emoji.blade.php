@props(['subject', 'class' => 'text-2xl'])

{{--
    A vector icon per subject (from the shared icon set, mapped by slug in Subject::iconName()).
    Callers still size it with the old text-* class they used for the emoji; that maps to a pixel
    size set inline here - inline wins over the SVG's own width/height and any utility class, so the
    size does not depend on a CSS rebuild. Decorative, and strokes in currentColor so inside a
    coloured chip it matches the chip's text.
--}}

@php
    $sizeMap = [
        'text-sm' => 16,
        'text-base' => 18,
        'text-lg' => 20,
        'text-xl' => 24,
        'text-2xl' => 28,
    ];

    $px = $sizeMap[trim($class)] ?? 20;
@endphp

<x-icon :name="$subject->iconName()" style="width:{{ $px }}px;height:{{ $px }}px;display:inline-block;vertical-align:-.15em;flex-shrink:0" />
