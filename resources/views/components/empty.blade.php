@props(['icon' => null, 'emoji' => null, 'title', 'text' => null])

{{-- Empty states always say what to do next, never just "no data". --}}

<div {{ $attributes->merge(['class' => 'card card-pad flex flex-col items-center gap-3 py-12 text-center']) }}>
    @if ($icon)
        <span class="flex h-14 w-14 items-center justify-center rounded-full bg-surface-2 text-ink-2" aria-hidden="true">
            <x-icon :name="$icon" class="h-7 w-7" />
        </span>
    @elseif ($emoji)
        <span class="text-5xl" aria-hidden="true">{{ $emoji }}</span>
    @endif

    <h3 class="text-lg font-bold text-ink">{{ $title }}</h3>

    @if ($text)
        <p class="max-w-prose text-ink-2 font-reading">{{ $text }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-2 flex flex-wrap justify-center gap-3">
            {{ $slot }}
        </div>
    @endif
</div>
