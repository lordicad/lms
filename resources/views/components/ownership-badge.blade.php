@props(['lesson'])

{{-- Ownership badge for a teacher's lesson: upload (own work), YouTube-Anda (verified own), or
     YouTube-Rujukan (a reference video, greyed, excluded from the talent signal). --}}

@php
    $map = [
        \App\Models\Lesson::OWNERSHIP_UPLOAD => ['label' => __('Muat naik'), 'class' => 'bg-success-soft text-success', 'icon' => 'upload'],
        \App\Models\Lesson::OWNERSHIP_OWNED => ['label' => __('YouTube — Anda'), 'class' => 'bg-brand-soft text-brand', 'icon' => 'youtube'],
        \App\Models\Lesson::OWNERSHIP_REFERENCE => ['label' => __('YouTube — Rujukan'), 'class' => 'bg-surface-2 text-ink-2', 'icon' => 'youtube'],
    ];
    $badge = $map[$lesson->ownership] ?? $map[\App\Models\Lesson::OWNERSHIP_UPLOAD];
@endphp

<span {{ $attributes->merge(['class' => 'chip '.$badge['class']]) }}>
    <x-icon :name="$badge['icon']" class="h-4 w-4" />
    {{ $badge['label'] }}
</span>
