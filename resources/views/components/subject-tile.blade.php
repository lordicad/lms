@props(['subject', 'grade'])

{{--
    Subject tile: a two-stop gradient built from the subject colour, a vector subject icon, and
    the name in up to two lines (never a mid-word ellipsis). Zero-video subjects are present but
    visibly dormant. (Image-backed tiles were dropped for the gradient everywhere: it avoids a
    per-subject thumbnail query and this build's subjects are mostly empty in production.)
--}}

@php
    $count = $subject->lessons_count ?? 0;
    $dormant = $count === 0;
@endphp

<a href="{{ route('belajar.subjek', ['subject' => $subject->slug, 'grade' => $grade->level]) }}"
   style="--sc: {{ $subject->rgb }}"
   @class([
       'group relative block overflow-hidden rounded-card border border-line shadow-card',
       'transition duration-200 ease-smooth hover:-translate-y-0.5 hover:border-line-strong hover:shadow-lift',
       'focus-visible:-translate-y-0.5',
       'opacity-60' => $dormant,
   ])>
    <div class="aspect-[16/10] w-full bg-subject/15"
         style="background-image: linear-gradient(150deg, color-mix(in oklab, rgb(var(--sc)) 32%, rgb(var(--c-surface))), color-mix(in oklab, rgb(var(--sc)) 9%, rgb(var(--c-surface))));"></div>

    <div class="absolute inset-0 flex flex-col justify-between p-4">
        <x-subject-icon :subject="$subject" class="h-7 w-7" />

        <div>
            <p class="text-[14px] font-semibold leading-tight text-ink" style="overflow-wrap: anywhere;">{{ $subject->displayName() }}</p>
            <p class="mt-1 micro text-ink-2">{{ __(':count video', ['count' => $count]) }}</p>
        </div>
    </div>
</a>
