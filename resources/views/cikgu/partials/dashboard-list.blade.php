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
            {{-- Rank badge: gold / silver / bronze circle for the top three, a plain number below.
                 Replaces the medal emoji so it renders the same on every device. --}}
            @php($rankBg = [0 => '#F3B94C', 1 => '#C2C6CE', 2 => '#D6A46A'][$i] ?? null)
            <span style="width:22px;height:22px;flex-shrink:0;display:grid;place-items:center;border-radius:50%;font-family:'Geist',sans-serif;font-weight:800;font-size:11.5px;{{ $rankBg ? 'background:'.$rankBg.';color:#fff' : 'color:var(--tp-muted)' }}">{{ $i + 1 }}</span>
            <span style="width:36px;height:36px;border-radius:10px;background:rgb({{ $item['subject']->rgb }} / .14);display:grid;place-items:center;font-size:14px;flex-shrink:0">{{ $item['subject']->icon ?? '🎬' }}</span>
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
