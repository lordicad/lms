@props(['lesson'])

{{--
    16:9 poster with the house treatment. Priority: uploaded thumbnail, then YouTube hqdefault,
    then a generated subject-colour card (this build has no ffmpeg, so there is no captured video
    frame to prefer). The cover is only gently desaturated at rest and eases to full colour + a
    1.03 zoom inside its rounded frame on hover (see .thumb-img). Metadata now lives below the
    image (see lesson-card), so there is no scrim. An inset hairline ring keeps a near-white slide
    screenshot from dissolving into a white card. On-image controls (progress, duration, heart)
    are passed in as the slot so they clip to this same rounded frame. Fixed frame = no reflow.
--}}

@php
    $subject = $lesson->chapter->subject;
    $thumb = $lesson->thumbnailUrl();
@endphp

<div class="thumb-frame aspect-video w-full overflow-hidden rounded-[10px] bg-surface-2" style="--sc: {{ $subject->rgb }}">
    <div class="skeleton absolute inset-0"></div>

    @if ($thumb)
        <img src="{{ $thumb }}" alt="{{ __('Gambar kecil untuk :title', ['title' => $lesson->title]) }}"
             loading="lazy" decoding="async" class="thumb-img absolute inset-0 z-[1]">
    @else
        <div class="absolute inset-0 z-[1]"
             style="background-image: linear-gradient(140deg, color-mix(in oklab, rgb(var(--sc)) 32%, rgb(var(--c-surface))), color-mix(in oklab, rgb(var(--sc)) 10%, rgb(var(--c-surface))));"></div>
        <div class="absolute inset-0 z-[2] flex items-center justify-center">
            <x-subject-icon :subject="$subject" class="h-12 w-12 opacity-90" />
        </div>
    @endif

    {{ $slot }}

    {{-- Inset hairline ring: the safeguard for white-on-white thumbnails. --}}
    <div class="thumb-ring z-[3] rounded-[10px]"></div>
</div>
