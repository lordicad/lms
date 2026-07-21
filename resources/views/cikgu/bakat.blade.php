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

    {{-- Pass / fail across all the teacher's quizzes --}}
    @php
        $pfTotal = max(1, $passFail['total']);
        $passFailConfig = [
            'type' => 'pie',
            'data' => [
                'labels' => [__('Lulus'), __('Gagal')],
                'datasets' => [[
                    'data' => [$passFail['passed'], $passFail['failed']],
                    'backgroundColor' => ['#0F7A68', '#C24936'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => ['plugins' => ['legend' => ['position' => 'bottom']]],
        ];
    @endphp
    <div class="tp-card" style="padding:22px;margin:20px 0">
        <h2 class="tp-g" style="font-size:16px;font-weight:800;color:var(--tp-ink);margin-bottom:14px">📝 {{ __('Lulus / Gagal Kuiz') }}</h2>

        @if ($passFail['total'] === 0)
            <p style="text-align:center;color:var(--tp-muted);padding:30px 0;font-weight:700">{{ __('Belum ada percubaan kuiz selesai lagi.') }}</p>
        @else
            <div style="display:flex;flex-wrap:wrap;gap:28px;align-items:center">
                <div style="flex:0 1 300px;min-width:240px">
                    <x-chart :config="$passFailConfig" :height="240" :title="__('Lulus lawan gagal')"
                        :table="false"
                        :rows="[
                            ['label' => __('Lulus'), 'value' => $passFail['passed']],
                            ['label' => __('Gagal'), 'value' => $passFail['failed']],
                        ]" />
                </div>
                <div style="display:flex;flex-direction:column;gap:14px;flex:1;min-width:200px">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span style="width:14px;height:14px;border-radius:4px;background:#0F7A68;flex-shrink:0"></span>
                        <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink)">{{ __('Lulus') }}</span>
                        <span class="tp-g" style="margin-left:auto;font-weight:800;color:#0F7A68">{{ number_format($passFail['passed']) }} <span style="color:var(--tp-muted);font-weight:700">({{ round($passFail['passed'] / $pfTotal * 100) }}%)</span></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px">
                        <span style="width:14px;height:14px;border-radius:4px;background:#C24936;flex-shrink:0"></span>
                        <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink)">{{ __('Gagal') }}</span>
                        <span class="tp-g" style="margin-left:auto;font-weight:800;color:#C24936">{{ number_format($passFail['failed']) }} <span style="color:var(--tp-muted);font-weight:700">({{ round($passFail['failed'] / $pfTotal * 100) }}%)</span></span>
                    </div>
                    <div style="border-top:1px solid var(--tp-line);padding-top:12px;display:flex;align-items:center;gap:12px">
                        <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink)">{{ __('Jumlah percubaan selesai') }}</span>
                        <span class="tp-g" style="margin-left:auto;font-weight:800;color:var(--tp-ink)">{{ number_format($passFail['total']) }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Leaderboards --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">
        @foreach ($lists as $list)
            <div class="tp-card" style="overflow:hidden">
                <div style="padding:18px 22px;border-bottom:1px solid var(--tp-line);display:flex;flex-direction:column;gap:2px">
                    <h2 class="tp-g" style="font-size:16px;font-weight:800;color:var(--tp-ink)">{{ $list['icon'] }} {{ $list['title'] }}</h2>
                    <span style="font-size:12.5px;color:var(--tp-muted)">{{ $list['sub'] }}</span>
                </div>

                @forelse ($list['items'] as $i => $item)
                    <div style="display:flex;align-items:center;gap:14px;padding:13px 22px;border-bottom:1px solid var(--tp-line)">
                        <span style="font-size:14px;width:22px;text-align:center;flex-shrink:0">{{ $medals[$i] ?? $i + 1 }}</span>
                        <span style="width:36px;height:36px;border-radius:10px;background:rgb({{ $item['subject']->rgb }} / .14);display:grid;place-items:center;font-size:14px;flex-shrink:0">{{ $item['subject']->icon ?? '🎬' }}</span>
                        <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                            <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item['title'] }}</span>
                            <span style="font-size:12px;color:var(--tp-muted)">{{ $item['detail'] }}</span>
                        </div>
                        <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink);flex-shrink:0">{{ $item['value'] }}</span>
                    </div>
                @empty
                    <div style="padding:22px;text-align:center;color:var(--tp-muted);font-size:13.5px">{{ __('Belum ada data.') }}</div>
                @endforelse
            </div>
        @endforeach
    </div>
</x-cikgu-layout>
