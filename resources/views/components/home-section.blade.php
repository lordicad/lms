@props([
    'title',
    'seeAll' => null,
    'seeAllLabel' => null,
    'cols' => 4,
])

{{--
    A titled home section: a header row (title + optional "Lihat semua") over a responsive card
    grid. The prototype lays these out as fixed 3- or 4-up grids; here they collapse gracefully on
    smaller screens. `cols` picks the desktop column count (3 or 4).
--}}

@php
    $gridCols = $cols === 3
        ? 'grid-cols-2 lg:grid-cols-3'
        : 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-4';
@endphp

<section {{ $attributes }}>
    <div class="mb-4 flex items-end justify-between gap-3">
        <h2 class="text-[21px] font-extrabold tracking-[-0.01em] text-ink">{{ $title }}</h2>

        @if ($seeAll)
            <a href="{{ $seeAll }}" class="text-[13.5px] font-bold text-brand hover:text-brand-strong">
                {{ $seeAllLabel ?? __('Lihat semua') }}
            </a>
        @endif
    </div>

    <div class="grid {{ $gridCols }} gap-4">
        {{ $slot }}
    </div>
</section>
