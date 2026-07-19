<x-cikgu-layout
    :title="__('Bakat Kandungan')"
    :heading="__('Bakat Kandungan')"
    :sub="__('Perkembangan kandungan yang anda muat naik — tontonan, kegemaran, muat turun dan percubaan kuiz')">

    @php
        $medals = ['🥇', '🥈', '🥉'];

        $lists = [
            [
                'icon' => '🎬', 'title' => __('Video Paling Ditonton'), 'sub' => __('Tontonan pada video anda'),
                'items' => $topVideos->map(fn ($l) => [
                    'subject' => $l->chapter->subject, 'title' => $l->title,
                    'detail' => $l->chapter->subject->name.' · Bab '.$l->chapter->number, 'value' => $l->views_count,
                ]),
            ],
            [
                'icon' => '❤️', 'title' => __('Video Paling Digemari'), 'sub' => __('Murid menandakan ♥ pada video anda'),
                'items' => $topFavourites->map(fn ($e) => [
                    'subject' => $e->lesson->chapter->subject, 'title' => $e->lesson->title,
                    'detail' => $e->lesson->chapter->subject->name.' · Bab '.$e->lesson->chapter->number, 'value' => $e->favourites,
                ]),
            ],
            [
                'icon' => '📄', 'title' => __('Bahan Paling Dimuat Turun'), 'sub' => __('Muat turun pada bahan anda'),
                'items' => $topMaterials->map(fn ($m) => [
                    'subject' => $m->chapter->subject, 'title' => $m->title,
                    'detail' => $m->chapter->subject->name.' · Bab '.$m->chapter->number, 'value' => $m->download_count,
                ]),
            ],
            [
                'icon' => '📝', 'title' => __('Kuiz Paling Dicuba'), 'sub' => __('Percubaan murid pada kuiz anda'),
                'items' => $topQuizzes->map(fn ($q) => [
                    'subject' => $q->chapter->subject, 'title' => $q->title,
                    'detail' => $q->chapter->subject->name.' · Bab '.$q->chapter->number, 'value' => $q->completed_attempts_count,
                ]),
            ],
        ];

        $summary = [
            ['icon' => '👁', 'tint' => '#E4EEF9', 'label' => __('Jumlah tontonan video'), 'value' => number_format($stats['views'])],
            ['icon' => '❤️', 'tint' => '#FBE4ED', 'label' => __('Video digemari'), 'value' => number_format($stats['favourites'])],
            ['icon' => '⬇️', 'tint' => '#DCF2EE', 'label' => __('Bahan dimuat turun'), 'value' => number_format($stats['downloads'])],
            ['icon' => '📝', 'tint' => '#FEF0CE', 'label' => __('Percubaan kuiz'), 'value' => number_format($stats['attempts'])],
        ];
    @endphp

    {{-- Summary --}}
    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
        @foreach ($summary as $s)
            <div class="tp-stat">
                <div style="display:flex;align-items:center;gap:10px">
                    <span class="tp-stat-ico" style="background:{{ $s['tint'] }}">{{ $s['icon'] }}</span>
                    <span class="tp-stat-label">{{ $s['label'] }}</span>
                </div>
                <span class="tp-stat-value">{{ $s['value'] }}</span>
            </div>
        @endforeach
    </div>

    {{-- Leaderboards --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">
        @foreach ($lists as $list)
            <div class="tp-card" style="overflow:hidden">
                <div style="padding:18px 22px;border-bottom:1px solid rgba(46,44,80,.07);display:flex;flex-direction:column;gap:2px">
                    <h2 class="tp-g" style="font-size:16px;font-weight:800;color:#28293F">{{ $list['icon'] }} {{ $list['title'] }}</h2>
                    <span style="font-size:12.5px;color:#8B8AA3">{{ $list['sub'] }}</span>
                </div>

                @forelse ($list['items'] as $i => $item)
                    <div style="display:flex;align-items:center;gap:14px;padding:13px 22px;border-bottom:1px solid rgba(46,44,80,.05)">
                        <span style="font-size:14px;width:22px;text-align:center;flex-shrink:0">{{ $medals[$i] ?? $i + 1 }}</span>
                        <span style="width:36px;height:36px;border-radius:10px;background:rgb({{ $item['subject']->rgb }} / .14);display:grid;place-items:center;font-size:14px;flex-shrink:0">{{ $item['subject']->icon ?? '🎬' }}</span>
                        <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                            <span class="tp-g" style="font-weight:800;font-size:14px;color:#28293F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item['title'] }}</span>
                            <span style="font-size:12px;color:#8B8AA3">{{ $item['detail'] }}</span>
                        </div>
                        <span class="tp-g" style="font-weight:800;font-size:14.5px;color:#28293F;flex-shrink:0">{{ $item['value'] }}</span>
                    </div>
                @empty
                    <div style="padding:22px;text-align:center;color:#8B8AA3;font-size:13.5px">{{ __('Belum ada data.') }}</div>
                @endforelse
            </div>
        @endforeach
    </div>
</x-cikgu-layout>
