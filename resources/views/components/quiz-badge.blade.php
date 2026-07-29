@props([
    'badge',            // badge key from App\Support\QuizBadges
    'isNew' => false,   // just earned on this attempt — shows a "Baru" tag
    'count' => null,    // times earned across quizzes — shows ×N on the profile
    'muted' => false,   // not yet earned — greyed on the profile collection
])

@php($m = \App\Support\QuizBadges::meta($badge))

<div style="display:flex;flex-direction:column;align-items:center;gap:7px;width:100px;{{ $muted ? 'opacity:.4;filter:grayscale(.65)' : '' }}">
    <div class="{{ $isNew ? 'qb-pop' : '' }}" style="position:relative">
        <span style="width:66px;height:66px;border-radius:50%;background:{{ $m['bg'] }};border:2px solid {{ $m['ring'] }};display:grid;place-items:center;color:{{ $m['ink'] }};box-shadow:0 6px 16px rgba(46,44,80,.13)">
            <x-icon :name="$m['icon']" style="width:28px;height:28px" />
        </span>
        @if ($isNew)
            <span style="position:absolute;top:-7px;right:-12px;background:#EB5E5A;color:#fff;border-radius:999px;padding:2px 9px;font-family:'Geist',sans-serif;font-size:10px;font-weight:800;box-shadow:0 3px 8px rgba(235,94,90,.4)">{{ __('Baru') }}</span>
        @elseif ($count !== null && $count > 1)
            <span style="position:absolute;bottom:-3px;right:-5px;background:{{ $m['ring'] }};color:#fff;border-radius:999px;min-width:23px;height:23px;padding:0 6px;display:grid;place-items:center;font-family:'Geist',sans-serif;font-size:11px;font-weight:800;border:2px solid var(--wl-surface)">×{{ $count }}</span>
        @endif
    </div>
    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:12px;color:var(--wl-ink);text-align:center;line-height:1.2">{{ $m['label'] }}</span>
    <span style="font-size:10.5px;font-weight:600;color:var(--wl-muted);text-align:center;line-height:1.3">{{ $m['desc'] }}</span>
</div>
