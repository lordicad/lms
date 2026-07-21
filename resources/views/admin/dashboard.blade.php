@php
    $user = auth()->user();

    /*
     * Feather glyphs (1.8 stroke), inlined so they match the WeLearn Admin mockup pixel-for-pixel.
     * $ic() renders one at a given size; the stroke weight stays 1.8 except for the trend arrow.
     */
    $paths = [
        'students' => '<path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/>',
        'teachers' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'video' => '<rect x="2" y="5" width="14" height="14" rx="2"/><polygon points="16 11 22 7 22 17 16 13"/>',
        'quiz' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/>',
        'pdf' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'award' => '<circle cx="12" cy="8" r="6"/><path d="M15.5 13.5L17 22l-5-3-5 3 1.5-8.5"/>',
        'star' => '<polygon points="12 2 15.1 8.3 22 9.3 17 14.1 18.2 21 12 17.8 5.8 21 7 14.1 2 9.3 8.9 8.3 12 2"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'activity' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>',
        'userx' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="22" y2="13"/><line x1="22" y1="8" x2="17" y2="13"/>',
        'check' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'chev' => '<polyline points="9 18 15 12 9 6"/>',
        'up' => '<line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>',
    ];

    $ic = function (string $name, int $size = 20, float $stroke = 1.8) use ($paths) {
        return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            .' stroke-width="'.$stroke.'" stroke-linecap="round" stroke-linejoin="round">'.$paths[$name].'</svg>';
    };

    // Avatar tint rotation, shared with the rest of the admin portal.
    $pal = [['#DCF2EE', '#0F7A68'], ['#E4EEF9', '#2E6CA8'], ['#FBE4ED', '#B84A75'], ['#FEF0CE', '#8A6A12'], ['#FDE7E0', '#C24936']];

    $periods = ['7d' => __('7 hari'), '30d' => __('30 hari'), '12m' => __('12 bulan')];

    // Four all-time totals, each with the real "new in the last 7 days" delta beside it.
    $summary = [
        ['icon' => 'students', 'tint' => '#E4EEF9', 'fg' => '#2E6CA8', 'label' => __('Jumlah murid'), 'value' => $totals['students'], 'trend' => $totals['students_new']],
        ['icon' => 'teachers', 'tint' => '#DCF2EE', 'fg' => '#0F7A68', 'label' => __('Jumlah cikgu'), 'value' => $totals['teachers'], 'trend' => $totals['teachers_new']],
        ['icon' => 'video', 'tint' => '#FEF0CE', 'fg' => '#8A6A12', 'label' => __('Jumlah video'), 'value' => $totals['videos'], 'trend' => $totals['videos_new']],
        ['icon' => 'quiz', 'tint' => '#FBE4ED', 'fg' => '#B84A75', 'label' => __('Jumlah kuiz'), 'value' => $totals['quizzes'], 'trend' => $totals['quizzes_new']],
    ];

    // Podium chrome per rank (gold / silver / bronze).
    $medals = [
        1 => ['bg' => '#FEF3D3', 'fg' => '#B7860B', 'ring' => '#F0C24B'],
        2 => ['bg' => '#EDF0F4', 'fg' => '#5B6472', 'ring' => '#C7CDD6'],
        3 => ['bg' => '#F8E7DE', 'fg' => '#9A5B3C', 'ring' => '#D9A188'],
    ];

    $topItems = [
        ['icon' => 'video', 'tint' => '#FEF0CE', 'fg' => '#8A6A12', 'micro' => __('Video paling ditonton'), 'unit' => __('tontonan'), 'data' => $topContent['video'], 'url' => route('admin.kandungan.video')],
        ['icon' => 'download', 'tint' => '#E4EEF9', 'fg' => '#2E6CA8', 'micro' => __('Bahan paling dimuat turun'), 'unit' => __('muat turun'), 'data' => $topContent['material'], 'url' => route('admin.kandungan.bahan')],
        ['icon' => 'quiz', 'tint' => '#FBE4ED', 'fg' => '#B84A75', 'micro' => __('Kuiz paling dicuba'), 'unit' => __('percubaan selesai'), 'data' => $topContent['quiz'], 'url' => route('admin.kandungan.kuiz')],
    ];

    // The four activity series. The chart is a donut of each series' total over the chosen period,
    // so the values here are sums of the same buckets the data table lists row by row.
    $seriesMeta = [
        ['key' => 'views', 'color' => '#17907B', 'tint' => '#E6F5F1', 'label' => __('Tontonan video')],
        ['key' => 'completed', 'color' => '#4276AE', 'tint' => '#E7F0FA', 'label' => __('Kuiz selesai')],
        ['key' => 'passed', 'color' => '#E3A31C', 'tint' => '#FEF3D6', 'label' => __('Kuiz lulus')],
        ['key' => 'uploads', 'color' => '#B84A75', 'tint' => '#FBE7F0', 'label' => __('Muat naik')],
    ];
    $donutMeta = collect($seriesMeta)->map(fn ($s) => [
        ...$s,
        'val' => array_sum($activity['series'][$s['key']]),
    ])->all();

    // Shared style fragments, so a value is written once and cannot drift between sections.
    $card = 'background:var(--tp-surface);border:1px solid var(--dl);border-radius:18px;padding:22px;box-shadow:var(--tp-shadow);display:flex;flex-direction:column;gap:14px';
    $h2 = "margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)";
    $chip = 'width:34px;height:34px;border-radius:10px;display:grid;place-items:center;flex-shrink:0';
    $row = 'display:flex;align-items:center;gap:13px;padding:13px 14px;border:1px solid var(--dl);border-radius:14px';
    $outlineBtn = "cursor:pointer;border-radius:11px;border:1.5px solid var(--tp-teal);background:var(--tp-surface);color:var(--tp-teal);font-family:'Geist',sans-serif;font-weight:800";
@endphp

<x-admin-layout :title="__('Utama')"
                :heading="__('Selamat datang, :name', ['name' => $user->name])"
                :sub="__('Gambaran keseluruhan platform WeLearn pada hari ini')">

    <style>
        /*
         * Scoped to the Home page. Light values are the mockup's exact hex/alpha; the dark column
         * mirrors the portal's --tp-* ramp so the one theme toggle still recolours everything.
         */
        .dash {
            --dl: rgba(46, 44, 80, .08);      /* card + row hairline */
            --dl-2: rgba(46, 44, 80, .14);    /* export button border */
            --dl-mid: rgba(46, 44, 80, .07);  /* list header rule */
            --dl-soft: rgba(46, 44, 80, .05); /* list row rule */
            --dl-tick: rgba(46, 44, 80, .1);  /* table head rule */
            --dl-track: rgba(46, 44, 80, .06);/* donut track */
            --d-label: #4A4B63;               /* legend + toggle labels */
            --d-micro: #A7A6B8;               /* micro caption */
        }

        html.theme-dark .dash {
            --dl: rgba(255, 255, 255, .09);
            --dl-2: rgba(255, 255, 255, .16);
            --dl-mid: rgba(255, 255, 255, .08);
            --dl-soft: rgba(255, 255, 255, .06);
            --dl-tick: rgba(255, 255, 255, .12);
            --dl-track: rgba(255, 255, 255, .07);
            --d-label: #C9D2DC;
            --d-micro: #8A94A3;
        }

        .hub-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
        .hub-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }

        @media (max-width: 900px) {
            .hub-4 { grid-template-columns: repeat(2, 1fr); }
            .hub-2 { grid-template-columns: 1fr; }
        }

        @media (max-width: 560px) {
            .hub-4 { grid-template-columns: 1fr; }
        }

        .dash-export:hover { border-color: var(--tp-teal); color: var(--tp-teal); background: var(--tp-active-bg); }
        .dash-outline:hover { background: var(--tp-active-bg); color: var(--tp-teal); }
        .dash-row:hover { background: var(--tp-surface-2); }
        .dash-pending { transition: transform .14s, box-shadow .14s; }
        .dash-pending:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(46, 44, 80, .1); }
        .dash-seg { transition: stroke-dasharray .9s cubic-bezier(.2, .8, .2, 1), opacity .18s, stroke-width .18s; cursor: pointer; }
        .dash-leg { display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 11px; cursor: pointer; transition: background .16s; }
        .dash-details summary { list-style: none; }
        .dash-details summary::-webkit-details-marker { display: none; }
    </style>

    {{-- One wrapper so the page-scoped tokens above have a host; the 24px rhythm matches .tp-main. --}}
    <div class="dash" style="display:flex;flex-direction:column;gap:24px">

        {{-- ===================== Export toolbar ===================== --}}
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:var(--tp-surface);border:1px solid var(--dl);border-radius:14px;padding:12px 16px;box-shadow:var(--tp-shadow)">
            <span style="font-size:12.5px;font-weight:700;color:var(--tp-muted);flex:1;min-width:180px">{{ __('Laporan bulanan menggunakan nombor yang sama seperti halaman ini.') }}</span>
            @foreach ([['pdf', __('Jana PDF'), route('admin.laporan.pdf', ['period' => $period])], ['quiz', __('Jana Word'), route('admin.laporan.word', ['period' => $period])]] as [$icon, $label, $href])
                <a href="{{ $href }}" class="dash-export" style="display:inline-flex;align-items:center;gap:8px;min-height:42px;border-radius:11px;border:1.5px solid var(--dl-2);background:var(--tp-surface);color:var(--tp-ink);font-family:'Geist',sans-serif;font-weight:800;font-size:13px;padding:0 15px">
                    <span style="display:block;width:18px;height:18px">{!! $ic($icon, 18) !!}</span>{{ $label }}
                </a>
            @endforeach
        </div>

        {{-- ===================== 1. Summary totals ===================== --}}
        <div class="hub-4">
            @foreach ($summary as $s)
                <div style="background:var(--tp-surface);border:1px solid var(--dl);border-radius:16px;padding:18px 20px;display:flex;flex-direction:column;gap:10px;box-shadow:var(--tp-shadow)">
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <span style="width:40px;height:40px;border-radius:12px;background:{{ $s['tint'] }};color:{{ $s['fg'] }};display:grid;place-items:center">{!! $ic($s['icon'], 20) !!}</span>
                        @if ($s['trend'] > 0)
                            <span title="{{ __('Baharu dalam 7 hari lalu') }}" style="display:inline-flex;align-items:center;gap:3px;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;color:#0F7A68">
                                <span style="display:inline-flex;width:13px;height:13px">{!! $ic('up', 13, 2.4) !!}</span>+{{ number_format($s['trend']) }}
                            </span>
                        @endif
                    </div>
                    <span style="font-family:'Geist',sans-serif;font-size:29px;font-weight:800;color:var(--tp-ink);line-height:1;font-variant-numeric:tabular-nums">{{ number_format($s['value']) }}</span>
                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted)">{{ $s['label'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- ===================== 2. Pending actions ===================== --}}
        <div style="{{ $card }}">
            <div style="display:flex;align-items:center;gap:10px">
                <span style="{{ $chip }};background:#FDECEB;color:#C24936">{!! $ic('clock', 18) !!}</span>
                <h2 style="{{ $h2 }}">{{ __('Tindakan Menunggu') }}</h2>
            </div>

            @foreach ($pending as $p)
                {{-- Each signal links to the page that resolves it; the all-clear item has nowhere to go. --}}
                <{{ $p['url'] ? 'a' : 'div' }} @if ($p['url']) href="{{ $p['url'] }}" class="dash-pending" @endif
                    style="display:flex;align-items:center;gap:13px;padding:13px 15px;border-radius:12px;background:{{ $p['bg'] }};text-align:left;width:100%;box-sizing:border-box">
                    {{-- The row sits on a fixed pastel in both themes, so its ink stays dark rather
                         than following --tp-ink, which would go near-white and vanish in dark mode. --}}
                    <span style="width:32px;height:32px;border-radius:9px;background:#fff;color:{{ $p['fg'] }};display:grid;place-items:center;flex-shrink:0">{!! $ic($p['icon'], 18) !!}</span>
                    <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:#28293F">{{ $p['title'] }}</span>
                        <span style="font-size:12.5px;color:#4A4B63;line-height:1.4">{{ $p['desc'] }}</span>
                    </div>
                    @if ($p['url'])
                        <span style="width:20px;height:20px;color:{{ $p['fg'] }};flex-shrink:0">{!! $ic('chev', 20) !!}</span>
                    @endif
                </{{ $p['url'] ? 'a' : 'div' }}>
            @endforeach
        </div>

        {{-- ===================== 3. Highlights ===================== --}}
        <div class="hub-2">
            {{-- Top contributors --}}
            <div style="{{ $card }}">
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="{{ $chip }};background:#FEF3D3;color:#B7860B">{!! $ic('award', 18) !!}</span>
                    <h2 style="{{ $h2 }};flex:1">{{ __('Penyumbang Teratas') }}</h2>
                    <a href="{{ route('admin.penyumbang') }}" class="dash-outline" style="{{ $outlineBtn }};display:inline-flex;align-items:center;min-height:40px;font-size:12.5px;padding:0 14px;white-space:nowrap">{{ __('Lihat Semua Penyumbang') }}</a>
                </div>
                <span style="font-size:12px;color:var(--tp-muted);font-weight:600;margin-top:-4px">{{ __('Sumbangan = bilangan Video + Bahan + Kuiz yang dicipta.') }}</span>

                @forelse ($topContributors as $i => $c)
                    @php($m = $medals[$i + 1] ?? $medals[3])
                    <div style="{{ $row }}">
                        <span style="position:relative;width:40px;height:40px;border-radius:12px;background:{{ $m['bg'] }};color:{{ $m['fg'] }};display:grid;place-items:center;flex-shrink:0;box-shadow:inset 0 0 0 1.5px {{ $m['ring'] }}">
                            {!! $ic('award', 20) !!}
                            <span style="position:absolute;bottom:-5px;right:-5px;width:18px;height:18px;border-radius:50%;background:{{ $m['fg'] }};color:#fff;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:10.5px;border:2px solid #fff">{{ $i + 1 }}</span>
                        </span>
                        <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['name'] }}</span>
                            <span style="font-size:11.5px;color:var(--tp-muted);font-weight:600">{{ $c['videos'] }} {{ __('video') }} · {{ $c['materials'] }} {{ __('bahan') }} · {{ $c['quizzes'] }} {{ __('kuiz') }}</span>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;flex-shrink:0">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:22px;color:var(--tp-teal);line-height:1">{{ number_format($c['total']) }}</span>
                            <span style="font-size:10.5px;color:var(--d-micro);font-weight:700">{{ __('sumbangan') }}</span>
                        </div>
                    </div>
                @empty
                    <span style="font-size:13.5px;color:var(--tp-muted)">{{ __('Belum ada penyumbang lagi.') }}</span>
                @endforelse
            </div>

            {{-- Top-performing content --}}
            <div style="{{ $card }}">
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="{{ $chip }};background:#E6F5F1;color:#0F7A68">{!! $ic('star', 18) !!}</span>
                    <h2 style="{{ $h2 }};flex:1">{{ __('Kandungan Berprestasi Tinggi') }}</h2>
                </div>

                @foreach ($topItems as $item)
                    <{{ $item['data'] ? 'a' : 'div' }} @if ($item['data']) href="{{ $item['url'] }}" class="dash-row" @endif style="{{ $row }}">
                        <span style="width:38px;height:38px;border-radius:11px;background:{{ $item['tint'] }};color:{{ $item['fg'] }};display:grid;place-items:center;flex-shrink:0">{!! $ic($item['icon'], 20) !!}</span>
                        <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                            <span style="font-size:10.5px;font-weight:800;color:var(--d-micro);text-transform:uppercase;letter-spacing:.04em">{{ $item['micro'] }}</span>
                            @if ($item['data'])
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $item['data']['title'] }}</span>
                                <span style="font-size:11.5px;color:var(--tp-muted);font-weight:600">{{ __('Cikgu: :name', ['name' => $item['data']['teacher'] ?? __('Tidak diketahui')]) }}</span>
                            @else
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-muted)">{{ __('Tiada data lagi.') }}</span>
                            @endif
                        </div>
                        @if ($item['data'])
                            <div style="display:flex;flex-direction:column;align-items:flex-end;flex-shrink:0">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:18px;color:var(--tp-teal);line-height:1">{{ number_format($item['data']['count']) }}</span>
                                <span style="font-size:10px;color:var(--d-micro);font-weight:700;text-align:right">{{ $item['unit'] }}</span>
                            </div>
                        @endif
                    </{{ $item['data'] ? 'a' : 'div' }}>
                @endforeach
            </div>
        </div>

        {{-- ===================== 4. Platform activity ===================== --}}
        <div style="background:var(--tp-surface);border:1px solid var(--dl);border-radius:18px;padding:22px;box-shadow:var(--tp-shadow);display:flex;flex-direction:column;gap:16px"
             x-data="{
                meta: @js($donutMeta),
                on: { views: true, completed: true, passed: true, uploads: true },
                hover: null,
                prog: 0,
                C: 2 * Math.PI * 70,
                get active() { return this.meta.filter(m => this.on[m.key]) },
                get total() { return this.active.reduce((a, s) => a + s.val, 0) },
                get segs() {
                    const t = this.total || 1;
                    let cum = 0;
                    return this.active.map((s, i) => {
                        const pct = s.val / t, start = cum;
                        cum += pct;
                        return { ...s, i, pct: Math.round(pct * 100),
                            dash: (pct * this.C * this.prog) + ' ' + (this.C - pct * this.C * this.prog),
                            rot: 'rotate(' + (start * 360 - 90) + ' 90 90)' };
                    })
                },
                /* A <template x-for> inside <svg> clones into the HTML namespace and never paints,
                   so the arcs are emitted server-side and each looks its own slice up by key. */
                seg(key) { return this.segs.find(s => s.key === key) ?? { i: -1, dash: '0 ' + this.C, rot: 'rotate(-90 90 90)' } },
                fmt(n) { return n.toLocaleString() },
                get centerValue() { const a = this.active; return this.hover === null || !a[this.hover] ? this.fmt(this.total) : this.fmt(a[this.hover].val) },
                get centerLabel() { const a = this.active; return this.hover === null || !a[this.hover] ? @js(__('Jumlah acara')) : a[this.hover].label },
                get centerSub() { const a = this.active; return this.hover === null || !a[this.hover] ? @js($periods[$period]) : Math.round(a[this.hover].val / (this.total || 1) * 100) + '%' },
             }"
             x-init="$nextTick(() => prog = 1)">

            <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap">
                <div style="display:flex;flex-direction:column;gap:7px;flex:1;min-width:220px">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span style="{{ $chip }};background:#E7F0FA;color:#2E6CA8">{!! $ic('activity', 18) !!}</span>
                        <h2 style="{{ $h2 }};white-space:nowrap">
                            {{ __('Aktiviti Platform') }}
                            <span style="font-weight:700;color:var(--d-micro);font-size:14px">· {{ $periods[$period] }}</span>
                        </h2>
                    </div>
                    <span style="font-size:12px;color:var(--tp-muted);font-weight:600;display:flex;align-items:center;gap:6px">{!! $ic('info', 14) !!}{{ __('Tempoh ini hanya mengubah carta di bawah.') }}</span>
                </div>

                <div style="display:flex;gap:6px;background:var(--tp-input);border-radius:999px;padding:4px;flex-shrink:0">
                    @foreach ($periods as $key => $label)
                        <a href="{{ route('admin.dashboard', ['period' => $key]) }}" @if ($period === $key) aria-current="true" @endif
                           style="display:inline-flex;align-items:center;min-height:36px;border-radius:999px;padding:0 15px;font-family:'Geist',sans-serif;font-weight:800;font-size:12.5px;transition:all .15s;{{ $period === $key ? 'background:var(--tp-teal);color:#fff' : 'background:var(--tp-input);color:var(--tp-muted-2)' }}">{{ $label }}</a>
                    @endforeach
                </div>
            </div>

            {{-- Series toggles: real checkboxes, so the donut is keyboard-operable. --}}
            <div style="display:flex;flex-wrap:wrap;gap:8px 18px">
                @foreach ($seriesMeta as $s)
                    <label style="display:inline-flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:700;color:var(--d-label);user-select:none">
                        <input type="checkbox" x-model="on.{{ $s['key'] }}" @change="hover = null" style="width:16px;height:16px;accent-color:{{ $s['color'] }};cursor:pointer">
                        <span style="width:11px;height:11px;border-radius:4px;background:{{ $s['color'] }}"></span>{{ $s['label'] }}
                    </label>
                @endforeach
            </div>

            {{-- Donut: each series' share of the period's total activity. --}}
            <div style="display:flex;align-items:center;justify-content:center;gap:28px;flex-wrap:wrap;padding:8px 0"
                 role="img" aria-label="{{ __('Carta aktiviti platform untuk tempoh :period', ['period' => $periods[$period]]) }}">
                <div style="position:relative;width:200px;height:200px;flex-shrink:0">
                    <svg viewBox="0 0 180 180" style="width:100%;height:100%">
                        <circle cx="90" cy="90" r="70" fill="none" stroke="var(--dl-track)" stroke-width="24"></circle>
                        @foreach ($seriesMeta as $s)
                            <circle class="dash-seg" cx="90" cy="90" r="70" fill="none" stroke-linecap="butt"
                                    stroke="{{ $s['color'] }}"
                                    :stroke-dasharray="seg('{{ $s['key'] }}').dash" :transform="seg('{{ $s['key'] }}').rot"
                                    :style="'opacity:' + (hover !== null && hover !== seg('{{ $s['key'] }}').i ? '.3' : '1') + ';stroke-width:' + (hover === seg('{{ $s['key'] }}').i ? 30 : 24)"
                                    @mouseenter="hover = seg('{{ $s['key'] }}').i" @mouseleave="hover = null"></circle>
                        @endforeach
                    </svg>
                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none">
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:32px;color:var(--tp-ink);line-height:1;font-variant-numeric:tabular-nums" x-text="centerValue"></span>
                        <span style="font-size:12.5px;font-weight:700;color:var(--tp-muted-2);margin-top:4px;text-align:center;white-space:nowrap" x-text="centerLabel"></span>
                        <span style="font-size:11.5px;font-weight:700;color:var(--d-micro)" x-text="centerSub"></span>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:2px;flex:1;min-width:220px;max-width:340px">
                    <template x-for="s in segs" :key="s.key">
                        <div class="dash-leg" :style="'background:' + (hover === s.i ? s.tint : 'transparent')"
                             @mouseenter="hover = s.i" @mouseleave="hover = null">
                            <span style="width:11px;height:11px;border-radius:4px;flex-shrink:0" :style="'background:' + s.color"></span>
                            <span style="font-size:13px;font-weight:700;color:var(--d-label);flex:1" x-text="s.label"></span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;font-variant-numeric:tabular-nums" :style="'color:' + s.color" x-text="fmt(s.val)"></span>
                            <span style="font-size:11.5px;font-weight:700;color:var(--d-micro);width:40px;text-align:right" x-text="s.pct + '%'"></span>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Accessible, no-JS alternative: the same buckets the donut sums. --}}
            <details class="dash-details" style="border-top:1px solid var(--dl);padding-top:12px">
                <summary style="cursor:pointer;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;color:var(--tp-teal)">{{ __('Lihat data sebagai jadual') }}</summary>
                <div style="overflow-x:auto;margin-top:12px">
                    <table style="width:100%;border-collapse:collapse;font-size:12.5px">
                        <thead>
                            <tr style="text-align:right">
                                <th scope="col" style="text-align:left;padding:7px 10px;font-family:'Geist',sans-serif;font-weight:800;color:var(--tp-muted-2);border-bottom:1.5px solid var(--dl-tick)">{{ __('Tarikh') }}</th>
                                @foreach ($seriesMeta as $s)
                                    <th scope="col" style="padding:7px 10px;font-family:'Geist',sans-serif;font-weight:800;color:{{ $s['color'] }};border-bottom:1.5px solid var(--dl-tick)">{{ $s['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($activity['labels'] as $i => $label)
                                <tr style="text-align:right">
                                    <td style="text-align:left;padding:6px 10px;font-weight:700;color:var(--d-label);border-bottom:1px solid var(--dl-soft)">{{ $label }}</td>
                                    @foreach ($seriesMeta as $s)
                                        <td style="padding:6px 10px;color:var(--tp-ink);font-variant-numeric:tabular-nums;border-bottom:1px solid var(--dl-soft)">{{ number_format($activity['series'][$s['key']][$i]) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </div>

        {{-- ===================== 5. Recent registrations ===================== --}}
        <div style="background:var(--tp-surface);border:1px solid var(--dl);border-radius:18px;box-shadow:var(--tp-shadow);overflow:hidden">
            <div style="display:flex;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid var(--dl-mid)">
                <div style="display:flex;flex-direction:column;gap:2px;flex:1;min-width:0">
                    <h2 style="{{ $h2 }}">{{ __('Pendaftaran (7 hari lalu)') }}</h2>
                    <span style="font-size:12.5px;color:var(--tp-muted);font-weight:600">{{ __(':count akaun baharu sejak :date', ['count' => $registrationsCount, 'date' => now()->subDays(7)->translatedFormat('j F')]) }}</span>
                </div>
                @if ($registrationsCount > $registrations->count())
                    <a href="{{ route('admin.pengguna') }}" class="dash-outline" style="{{ $outlineBtn }};display:inline-flex;align-items:center;min-height:42px;font-size:13px;padding:0 16px;white-space:nowrap">{{ __('Lihat Semua') }}</a>
                @endif
            </div>

            @forelse ($registrations as $i => $u)
                @php($p = $pal[$i % count($pal)])
                <div class="dash-row" style="display:flex;align-items:center;gap:14px;padding:13px 22px;border-bottom:1px solid var(--dl-soft)">
                    <span style="width:36px;height:36px;border-radius:10px;background:{{ $p[0] }};color:{{ $p[1] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;flex-shrink:0">{{ $u->initials() }}</span>
                    <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink)">{{ $u->name }}</span>
                        <span style="font-size:12px;color:var(--tp-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $u->isStudent() && $u->grade ? $u->grade->name.' · ' : '' }}{{ $u->email ?? $u->username }}</span>
                    </div>
                    <span style="flex-shrink:0;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800;{{ $u->isTeacher() ? 'background:#DCF2EE;color:#0F7A68' : 'background:#E4EEF9;color:#2E6CA8' }}">{{ $u->isTeacher() ? __('Cikgu') : __('Murid') }}</span>
                    <span style="font-size:12.5px;font-weight:700;color:var(--tp-muted);flex-shrink:0">
                        @if ($u->created_at->isToday()) {{ __('Hari ini') }}
                        @elseif ($u->created_at->isYesterday()) {{ __('Semalam') }}
                        @else {{ $u->created_at->translatedFormat('j M') }}
                        @endif
                    </span>
                </div>
            @empty
                <div style="padding:28px 22px;font-size:13.5px;color:var(--tp-muted)">{{ __('Tiada pendaftaran dalam 7 hari lalu.') }}</div>
            @endforelse
        </div>
    </div>
</x-admin-layout>
