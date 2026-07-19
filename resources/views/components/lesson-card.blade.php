@props(['lesson', 'showProgress' => true, 'grid' => false, 'showViews' => false])

{{--
    Lesson card, light-native: a white card with an inset (4px-framed) thumbnail on top and the
    metadata below it (the YouTube / Apple TV pattern) — no text on the image, so any cover works.
    Progress bar, duration and the favourite heart stay on the image (the streaming signature) and
    are passed into the thumb's slot so they clip to its rounded frame. One stretched link over the
    title keeps the whole card a single focusable target; the heart sits above it on its own.
    Progress/favourite come from student-scoped eager loads when present, else a per-lesson query.
--}}

@php
    $user = auth()->user();
    $subject = $lesson->chapter->subject;

    $progress = $lesson->relationLoaded('progress')
        ? $lesson->progress->first()
        : $lesson->progressFor($user);

    $favourited = $lesson->relationLoaded('favourites')
        ? $lesson->favourites->isNotEmpty()
        : $lesson->isFavouritedBy($user);

    $hasProgress = $showProgress && $progress && $progress->percent > 0;
@endphp

<article class="group relative {{ $grid ? 'w-full' : 'w-[72vw] shrink-0 sm:w-[200px] lg:w-[260px]' }}" style="--sc: {{ $subject->rgb }}">
    <div class="overflow-hidden rounded-card border border-line bg-surface shadow-card transition duration-200 ease-smooth group-hover:-translate-y-0.5 group-hover:border-line-strong group-hover:shadow-lift [&:has(a:focus-visible)]:-translate-y-0.5 [&:has(a:focus-visible)]:ring-2 [&:has(a:focus-visible)]:ring-brand [&:has(a:focus-visible)]:ring-offset-2 [&:has(a:focus-visible)]:ring-offset-bg">
        {{-- Framed thumbnail (4px inset). Controls live inside the slot, clipped to the frame. --}}
        <div class="p-1">
            <x-lesson-thumb :lesson="$lesson">
                {{-- Favourite heart, top-right. --}}
                <div class="absolute right-2 top-2 z-[5]">
                    <x-favourite-button :lesson="$lesson" :favourited="$favourited" reveal />
                </div>

                {{-- Centered play affordance (WeLearn prototype). --}}
                <span class="glass-pill-light absolute left-1/2 top-1/2 z-[4] grid h-9 w-9 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full text-subject-ink">
                    <x-icon name="play" class="h-4 w-4" />
                </span>

                {{-- Duration (or resume position when continuing), bottom-right. --}}
                @if ($hasProgress && $progress->duration_seconds)
                    <span class="absolute bottom-2 right-2 z-[5] glass-pill rounded-full px-2 py-0.5 text-[11px] font-semibold tabular-nums text-white">
                        {{ $progress->positionLabel() }} / {{ $progress->durationLabel() }}
                    </span>
                @elseif ($lesson->durationLabel())
                    <span class="absolute bottom-2 right-2 z-[5] glass-pill rounded-full px-2 py-1 text-[11px] font-semibold tabular-nums text-white">{{ $lesson->durationLabel() }}</span>
                @endif

                {{-- Progress bar: flush to the image's bottom edge, on a translucent track. --}}
                @if ($hasProgress)
                    <div class="absolute inset-x-0 bottom-0 z-[4] h-[3px]" style="background: rgba(15, 23, 42, 0.15);">
                        <div class="h-full bg-brand" style="width: {{ min(100, $progress->percent) }}%"></div>
                    </div>
                @endif
            </x-lesson-thumb>
        </div>

        {{-- Metadata, below the image. --}}
        <div class="px-3 pb-3 pt-2.5">
            <h3 class="line-clamp-2 text-[15px] font-semibold leading-snug text-ink">
                <a href="{{ route('video.show', $lesson) }}"
                   class="rounded-sm outline-none after:absolute after:inset-0 after:content-['']">
                    {{ $lesson->title }}
                </a>
            </h3>

            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="inline-flex items-center rounded-full bg-subject-wash px-2.5 py-0.5 text-[11px] font-extrabold text-subject-ink">{{ $subject->displayName() }}</span>
                <span class="text-[11px] font-bold text-ink-2">Bab {{ $lesson->chapter->number }}</span>
                @if ($showViews)
                    <span class="ml-auto text-[11px] font-bold text-ink-2">👁 {{ $lesson->views_count }}</span>
                @endif
            </div>
        </div>
    </div>
</article>
