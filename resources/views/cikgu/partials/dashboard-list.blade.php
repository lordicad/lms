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
            {{-- Rank badge: the illustrated medal PNG (gold/silver/bronze, number drawn on it) for the
                 top three, a plain number for the rest. --}}
            @if ($i < 3)
                <img src="{{ asset('images/medal'.($i + 1).'.png') }}" alt="{{ $i + 1 }}" style="width:28px;height:28px;object-fit:contain;flex-shrink:0">
            @else
                <span style="width:28px;flex-shrink:0;text-align:center;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;color:var(--tp-muted)">{{ $i + 1 }}</span>
            @endif
            <span style="width:36px;height:36px;border-radius:10px;background:rgb({{ $item['subject']->rgb }} / .14);display:grid;place-items:center;flex-shrink:0"><x-icon :name="$item['subject']->iconName()" class="h-[18px] w-[18px]" style="color:rgb({{ $item['subject']->rgb }})" /></span>
            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item['title'] }}</span>
                {{-- Subject pill on its own line, chapter on the next: they crowd and wrap when inline. --}}
                <span style="align-self:flex-start;background:color-mix(in oklab, {{ $item['subject']->color ?: '#17907B' }} var(--pill-bw), var(--pill-bb));color:color-mix(in oklab, {{ $item['subject']->color ?: '#17907B' }} var(--pill-fw), var(--pill-fb));border-radius:999px;padding:2px 9px;font-weight:800;font-size:11px">{{ $item['subject']->name }}</span>
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
