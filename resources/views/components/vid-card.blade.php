@props([
    'lesson',
    'thumbHeight' => 104,   // 110 continue, 104 popular/recent, 116 suggested
    'showProgress' => false,
    'showViews' => false,
    'showMeta' => false,    // "subject · bab · dur" meta line (continue watching)
])

{{-- Exact WeLearn prototype video card, wired to a real Lesson. --}}

@php
    $user = auth()->user();
    $subject = $lesson->chapter->subject;
    $fav = $lesson->relationLoaded('favourites') ? $lesson->favourites->isNotEmpty() : $lesson->isFavouritedBy($user);
    $progress = $lesson->relationLoaded('progress') ? $lesson->progress->first() : $lesson->progressFor($user);
    $pct = ($progress && $progress->percent) ? min(100, $progress->percent) : 0;
    $thumb = $lesson->thumbnailUrl();
    $col = $subject->color ?: '#17907B';
    $tagBg = "color-mix(in oklab, {$col} 15%, #fff)";
    $tagColor = "color-mix(in oklab, {$col} 82%, #000)";
    $grad = "linear-gradient(135deg, color-mix(in oklab, {$col} 30%, #fff), color-mix(in oklab, {$col} 12%, #fff))";
@endphp

<a href="{{ route('video.show', $lesson) }}" class="vid-card"
   style="display:flex;flex-direction:column;height:100%;box-sizing:border-box;text-decoration:none;background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;overflow:hidden;box-shadow:0 4px 16px rgba(46,44,80,.04);cursor:pointer">
    <div style="height:{{ $thumbHeight }}px;flex-shrink:0;background:{{ $grad }};display:grid;place-items:center;position:relative">
        @if ($thumb)
            <img src="{{ $thumb }}" alt="" loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
        @endif

        <button type="button" class="fav-btn" data-fav="{{ $fav ? 'true' : 'false' }}" title="{{ __('Kegemaran') }}"
                data-add="{{ route('kegemaran.simpan', $lesson) }}" data-remove="{{ route('kegemaran.padam', $lesson) }}"
                onclick="wlToggleFav(event, this)"
                style="position:absolute;top:10px;right:10px;width:34px;height:34px;border-radius:50%;border:none;cursor:pointer;background:rgba(255,255,255,.92);display:grid;place-items:center;font-size:15px;z-index:2;color:{{ $fav ? '#EB5E5A' : '#6C6F87' }};box-shadow:0 2px 8px var(--wl-line-3)">{{ $fav ? '♥' : '♡' }}</button>

        <span style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.9);display:grid;place-items:center;color:#4276AE;font-size:13px;z-index:1">▶</span>

        @if ($lesson->durationLabel())
            <span style="position:absolute;right:10px;bottom:10px;background:rgba(66,118,174,.85);color:#fff;font-size:11px;font-weight:700;border-radius:999px;padding:3px 9px">{{ $lesson->durationLabel() }}</span>
        @endif
    </div>

    <div style="padding:{{ $showMeta ? '14px 16px' : '13px 15px' }};flex:1 1 auto;display:flex;flex-direction:column;gap:{{ $showMeta ? '4px' : '8px' }}">
        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:{{ $showMeta ? '15px' : '14.5px' }};color:var(--wl-ink)">{{ $lesson->title }}</span>

        @if ($showMeta)
            <span style="margin-top:auto;font-size:12.5px;color:var(--wl-muted)">{{ $subject->displayName() }} · Bab {{ $lesson->chapter->number }}@if ($lesson->durationLabel()) · {{ $lesson->durationLabel() }}@endif</span>
            @if ($showProgress)
                <div style="height:6px;border-radius:999px;background:#EFEEE6;overflow:hidden;margin-top:6px">
                    <div style="height:100%;border-radius:999px;background:#17907B;width:{{ $pct }}%"></div>
                </div>
            @endif
        @else
            <div style="margin-top:auto;display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="background:{{ $tagBg }};color:{{ $tagColor }};border-radius:999px;padding:3px 10px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ $subject->displayName() }}</span>
                <span style="font-size:12px;font-weight:700;color:var(--wl-muted)">Bab {{ $lesson->chapter->number }}</span>
                @if ($showViews)
                    <span style="margin-left:auto;font-size:12px;font-weight:700;color:var(--wl-muted)">👁 {{ $lesson->views_count }}</span>
                @endif
            </div>
        @endif
    </div>
</a>

@once
    @push('scripts')
        <script>
            function wlToggleFav(e, btn) {
                e.preventDefault();
                e.stopPropagation();
                if (btn.dataset.busy) return;
                const on = btn.dataset.fav === 'true';
                const url = on ? btn.dataset.remove : btn.dataset.add;
                btn.dataset.busy = '1';
                // optimistic
                btn.dataset.fav = on ? 'false' : 'true';
                btn.textContent = on ? '♡' : '♥';
                btn.style.color = on ? '#6C6F87' : '#EB5E5A';
                const token = document.querySelector('meta[name=csrf-token]')?.content;
                fetch(url, { method: on ? 'DELETE' : 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } })
                    .then(r => { if (!r.ok) throw new Error('failed'); })
                    .catch(() => {
                        btn.dataset.fav = on ? 'true' : 'false';
                        btn.textContent = on ? '♥' : '♡';
                        btn.style.color = on ? '#EB5E5A' : '#6C6F87';
                    })
                    .finally(() => { delete btn.dataset.busy; });
            }
        </script>
    @endpush
@endonce
