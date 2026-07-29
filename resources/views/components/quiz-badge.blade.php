@props([
    'badge',            // badge key from App\Support\QuizBadges
    'isNew' => false,   // just earned on this attempt — pops in with a sparkle burst
    'tag' => true,      // show the built-in "Baru" pill (off when the wrapper draws its own)
    'count' => null,    // times earned across quizzes — shows ×N on the profile
    'muted' => false,   // not yet earned — greyed on the profile collection
])

@php($m = \App\Support\QuizBadges::meta($badge))
@php($grey = $muted ? 'filter:grayscale(1)' : '')

{{-- Animations, emitted once per page however many badges render. Earned badges pop in and throw
     a sparkle burst; any badge wiggles and lifts on hover/tap. All off under reduced motion. --}}
@once
    <style>
        .qb-badge { transition: transform .18s ease-out; }
        .qb-badge:hover, .qb-badge:active { transform: translateY(-4px) scale(1.06); }
        .qb-badge:hover .qb-medal, .qb-badge:active .qb-medal { animation: qbWiggle .5s ease-in-out; }
        .qb-pop { animation: qbPop .55s cubic-bezier(.2,.8,.3,1.3) both; }
        .qb-spark { position: absolute; z-index: 2; pointer-events: none; opacity: 0; animation: qbSpark .9s ease-out both; }
        @keyframes qbPop { from { opacity: 0; transform: scale(.5) translateY(8px); } to { opacity: 1; transform: none; } }
        @keyframes qbWiggle { 0%, 100% { transform: rotate(0); } 20% { transform: rotate(-8deg); } 60% { transform: rotate(8deg); } 80% { transform: rotate(-4deg); } }
        @keyframes qbSpark { 0% { opacity: 0; transform: translate(0, 0) scale(.2); } 35% { opacity: 1; } 100% { opacity: 0; transform: translate(var(--dx), var(--dy)) scale(1); } }
        @media (prefers-reduced-motion: reduce) {
            .qb-pop, .qb-spark, .qb-badge:hover .qb-medal, .qb-badge:active .qb-medal { animation: none; }
            .qb-spark { display: none; }
            .qb-badge:hover, .qb-badge:active { transform: none; }
        }
    </style>
@endonce

{{-- Rosette medal: ribbon tails + scalloped medal (three rotated squares) + tinted disc + icon,
     matching the "Lencana Saya" badges. --}}
<div class="qb-badge" style="display:flex;flex-direction:column;align-items:center;gap:6px;width:104px;{{ $muted ? 'opacity:.85' : '' }}">
    <div class="qb-medal {{ $isNew ? 'qb-pop' : '' }}" style="position:relative;width:78px;height:90px;display:flex;justify-content:center">
        @if ($isNew)
            {{-- Sparkle burst, each shard flung to its own offset. --}}
            @foreach ([['-30','-20','0s'], ['32','-16','.05s'], ['-22','24','.1s'], ['26','26','.04s'], ['2','-34','.08s'], ['-6','32','.13s']] as [$dx, $dy, $delay])
                <span class="qb-spark" style="top:28px;left:33px;--dx:{{ $dx }}px;--dy:{{ $dy }}px;animation-delay:{{ $delay }};color:{{ $m['ring'] }}">
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>
                </span>
            @endforeach
        @endif

        <span style="position:absolute;top:36px;left:21px;width:26px;height:46px;background:{{ $m['ribbon'] }};filter:brightness(.82) saturate(1.15){{ $muted ? ' grayscale(1)' : '' }};transform:rotate(28deg);transform-origin:50% 0;clip-path:polygon(0 0,100% 0,100% 100%,50% 74%,0 100%)"></span>
        <span style="position:absolute;top:36px;right:21px;width:26px;height:46px;background:{{ $m['ribbon'] }};filter:brightness(.82) saturate(1.15){{ $muted ? ' grayscale(1)' : '' }};transform:rotate(-28deg);transform-origin:50% 0;clip-path:polygon(0 0,100% 0,100% 100%,50% 74%,0 100%)"></span>
        <span style="position:absolute;top:6px;left:13px;width:52px;height:52px;background:{{ $m['ribbon'] }};border-radius:9px;{{ $grey }}"></span>
        <span style="position:absolute;top:6px;left:13px;width:52px;height:52px;background:{{ $m['ribbon'] }};border-radius:9px;transform:rotate(30deg);{{ $grey }}"></span>
        <span style="position:absolute;top:6px;left:13px;width:52px;height:52px;background:{{ $m['ribbon'] }};border-radius:9px;transform:rotate(60deg);{{ $grey }}"></span>
        <span style="position:absolute;top:5px;width:54px;height:54px;border-radius:50%;background:{{ $m['tint'] }};display:grid;place-items:center;box-shadow:0 4px 12px rgba(46,44,80,.16);{{ $grey }}">
            <span style="width:41px;height:41px;border-radius:50%;background:var(--wl-surface);border:2px solid {{ $m['ring'] }};display:grid;place-items:center;color:{{ $m['ink'] }}">
                <x-icon :name="$m['icon']" style="width:21px;height:21px;{{ $muted ? 'filter:grayscale(1) opacity(.55)' : '' }}" />
            </span>
        </span>

        @if ($isNew && $tag)
            <span style="position:absolute;top:-3px;right:-8px;background:#EB5E5A;color:#fff;border-radius:999px;padding:2px 9px;font-family:'Geist',sans-serif;font-size:10px;font-weight:800;box-shadow:0 3px 8px rgba(235,94,90,.4);z-index:3">{{ __('Baru') }}</span>
        @elseif (! $isNew && $count !== null && $count > 1)
            <span style="position:absolute;top:40px;right:0;background:{{ $m['ring'] }};color:#fff;border-radius:999px;min-width:23px;height:23px;padding:0 6px;display:grid;place-items:center;font-family:'Geist',sans-serif;font-size:11px;font-weight:800;border:2px solid var(--wl-surface);z-index:3">×{{ $count }}</span>
        @endif
    </div>
    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13px;color:var(--wl-ink);text-align:center;line-height:1.2">{{ $m['label'] }}</span>
    <span style="font-size:11.5px;font-weight:700;color:var(--wl-muted);text-align:center;line-height:1.3">{{ $m['desc'] }}</span>
</div>
