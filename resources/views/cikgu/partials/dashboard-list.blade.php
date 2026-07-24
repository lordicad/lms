{{--
    One content leaderboard. Extracted because the dashboard now places these in three different
    spots — a pair in the main column, one full width beneath, one in the side column — and they
    were identical markup repeated in a loop before.
--}}
<div class="tp-card" style="overflow:hidden">
    <div style="padding:18px 22px;border-bottom:1px solid var(--tp-line);display:flex;flex-direction:column;gap:2px">
        <h2 class="tp-g" style="font-size:16px;font-weight:800;color:var(--tp-ink)">{{ $list['title'] }}</h2>
        <span style="font-size:12.5px;color:var(--tp-muted)">{{ $list['sub'] }}</span>
    </div>

    @forelse ($list['items'] as $i => $item)
        <div style="display:flex;align-items:center;gap:14px;padding:13px 22px;border-bottom:1px solid var(--tp-line)">
            {{-- Rank badge: a drawn medal — ribbon + disc — for the top three, gold / silver /
                 bronze, with a plain number for the rest. An SVG rather than the medal emoji so it
                 renders the same on every device. --}}
            @php($medal = [
                0 => ['disc' => '#F4B63F', 'ring' => '#DE9F22', 'ribA' => '#E8A63A', 'ribB' => '#C7891D', 'num' => '#fff', 'shadow' => true],
                1 => ['disc' => '#C7CCD4', 'ring' => '#A9AFB9', 'ribA' => '#C0C5CE', 'ribB' => '#9EA4AF', 'num' => '#5A5F68', 'shadow' => false],
                2 => ['disc' => '#D69A5F', 'ring' => '#BC8146', 'ribA' => '#CD9155', 'ribB' => '#A6723B', 'num' => '#fff', 'shadow' => true],
            ][$i] ?? null)
            @if ($medal)
                <span style="position:relative;width:28px;height:34px;flex-shrink:0;display:block">
                    <svg width="28" height="34" viewBox="0 0 28 34" fill="none" style="display:block">
                        <path d="M6 1 L11 1 L18 16 L13 16 Z" fill="{{ $medal['ribB'] }}" />
                        <path d="M22 1 L17 1 L10 16 L15 16 Z" fill="{{ $medal['ribA'] }}" />
                        <circle cx="14" cy="23" r="10.5" fill="{{ $medal['disc'] }}" stroke="{{ $medal['ring'] }}" stroke-width="1.5" />
                        <circle cx="14" cy="23" r="7.5" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1" />
                    </svg>
                    <span style="position:absolute;left:0;top:12px;width:28px;height:22px;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;color:{{ $medal['num'] }};{{ $medal['shadow'] ? 'text-shadow:0 1px 1px rgba(0,0,0,.25)' : '' }}">{{ $i + 1 }}</span>
                </span>
            @else
                <span style="width:28px;flex-shrink:0;text-align:center;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;color:var(--tp-muted)">{{ $i + 1 }}</span>
            @endif
            <span style="width:36px;height:36px;border-radius:10px;background:rgb({{ $item['subject']->rgb }} / .14);display:grid;place-items:center;flex-shrink:0"><x-icon :name="$item['subject']->iconName()" class="h-[18px] w-[18px]" style="color:rgb({{ $item['subject']->rgb }})" /></span>
            <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item['title'] }}</span>
                <span style="font-size:12px;color:var(--tp-muted)">{{ $item['detail'] }}</span>
            </div>
            <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink);flex-shrink:0">{{ $item['value'] }}</span>
        </div>
    @empty
        <div style="padding:26px 22px;text-align:center;display:flex;flex-direction:column;gap:4px">
            <span style="font-size:13.5px;color:var(--tp-muted)">{{ __('Belum ada data.') }}</span>
            <span style="font-size:12.5px;color:var(--tp-muted)">{{ $list['empty'] ?? '' }}</span>
        </div>
    @endforelse
</div>
