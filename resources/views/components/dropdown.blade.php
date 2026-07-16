@props(['align' => 'right', 'width' => '56'])

@php
    $alignment = match ($align) {
        'left' => 'origin-top-left start-0',
        default => 'origin-top-right end-0',
    };

    $widthClass = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        default => 'w-56',
    };
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-40 mt-2 {{ $widthClass }} {{ $alignment }} overflow-hidden rounded-card border border-line bg-surface shadow-lift"
         @click="open = false">
        {{ $content }}
    </div>
</div>
