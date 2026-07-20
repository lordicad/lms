<x-cikgu-layout :title="__('Papan Pemuka')"
    :heading="__('Selamat datang, Cikgu :name', ['name' => \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first()])"
    :sub="__('Ringkasan kandungan dan prestasi anda')">

    @php
        // Card B — weekly upload trend (stacked bar).
        $trendConfig = [
            'type' => 'bar',
            'data' => [
                'labels' => $weeklyTrend['labels'],
                'datasets' => [
                    ['label' => __('Video'), 'data' => $weeklyTrend['videos'], 'backgroundColor' => '#0F7A68', 'borderRadius' => 4],
                    ['label' => __('Bahan'), 'data' => $weeklyTrend['materials'], 'backgroundColor' => '#2E6CA8', 'borderRadius' => 4],
                    ['label' => __('Kuiz'), 'data' => $weeklyTrend['quizzes'], 'backgroundColor' => '#8A6A12', 'borderRadius' => 4],
                ],
            ],
            'options' => [
                'scales' => [
                    'x' => ['stacked' => true],
                    'y' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
                ],
            ],
        ];
        $trendRows = collect($weeklyTrend['labels'])->map(fn ($label, $i) => [
            'label' => $label,
            'value' => $weeklyTrend['videos'][$i] + $weeklyTrend['materials'][$i] + $weeklyTrend['quizzes'][$i],
        ])->all();

        // Card C — pass/fail doughnut.
        $passFailConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => [__('Lulus'), __('Gagal')],
                'datasets' => [[
                    'data' => [$passFail['passed'], $passFail['failed']],
                    'backgroundColor' => ['#0F7A68', '#C24936'],
                    'borderWidth' => 0,
                ]],
            ],
            'options' => ['cutout' => '62%', 'plugins' => ['legend' => ['position' => 'bottom']]],
        ];
        $pfTotal = max(1, $passFail['total']);
    @endphp

    <div style="display:flex;flex-direction:column;gap:22px">

        {{-- Quick totals --}}
        <div class="tp-stats">
            <a href="{{ route('cikgu.video.index') }}" class="tp-stat" style="text-decoration:none">
                <span class="tp-stat-label">{{ __('Video Saya') }}</span>
                <span class="tp-stat-value">{{ $lessonCount }}</span>
                <span class="tp-meta">{{ __('Jumlah tontonan: :n', ['n' => number_format($viewCount)]) }}</span>
            </a>
            <a href="{{ route('cikgu.bahan.index') }}" class="tp-stat" style="text-decoration:none">
                <span class="tp-stat-label">{{ __('Bahan Saya') }}</span>
                <span class="tp-stat-value">{{ $materialCount }}</span>
            </a>
            <a href="{{ route('cikgu.kuiz.index') }}" class="tp-stat" style="text-decoration:none">
                <span class="tp-stat-label">{{ __('Kuiz Saya') }}</span>
                <span class="tp-stat-value">{{ $quizCount }}</span>
            </a>
            <a href="{{ route('cikgu.ranking') }}" class="tp-stat" style="text-decoration:none">
                <span class="tp-stat-label">{{ __('Percubaan kuiz selesai') }}</span>
                <span class="tp-stat-value">{{ number_format($passFail['total']) }}</span>
            </a>
        </div>

        {{-- A. Interactive content-performance doughnut --}}
        <div class="tp-card" style="padding:22px" x-data="metricDoughnut({ metrics: @js($contentMetrics), initial: 'views' })">
            <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:12px;margin-bottom:16px">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Prestasi Kandungan') }}</h2>
                <div role="tablist" aria-label="{{ __('Pilih metrik') }}" style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach ($contentMetrics as $key => $metric)
                        <button type="button" role="tab" @click="setMetric('{{ $key }}')"
                                :aria-selected="current === '{{ $key }}' ? 'true' : 'false'"
                                :style="current === '{{ $key }}'
                                    ? 'background:#0F7A68;color:#fff'
                                    : 'background:#EEF1F6;color:#4A4B63'"
                                style="border:0;border-radius:999px;padding:7px 14px;font-family:'Geist',sans-serif;font-weight:800;font-size:12.5px;cursor:pointer;min-height:38px">
                            {{ $metric['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>

            <template x-if="hasData">
                <div>
                    <div style="position:relative;width:100%;height:320px;overflow:hidden">
                        <canvas x-ref="canvas" role="img" :aria-label="metrics[current] ? metrics[current].label : ''"></canvas>
                    </div>

                    <details style="margin-top:12px">
                        <summary style="cursor:pointer;font-size:13px;font-weight:700;color:#6C6F87">{{ __('Lihat data sebagai jadual') }}</summary>
                        <div style="overflow-x:auto;margin-top:8px">
                            <table style="width:100%;border-collapse:collapse;font-size:14px">
                                <thead>
                                    <tr>
                                        <th scope="col" style="text-align:left;padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ __('Tajuk') }}</th>
                                        <th scope="col" style="text-align:right;padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ __('Jumlah') }}</th>
                                        <th scope="col" style="text-align:right;padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ __('Peratus') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in rows" :key="row.label">
                                        <tr>
                                            <td style="padding:6px 10px;border-bottom:1px solid var(--tp-line)">
                                                <template x-if="row.url"><a :href="row.url" style="color:#2E6CA8;font-weight:700" x-text="row.label"></a></template>
                                                <template x-if="! row.url"><span x-text="row.label"></span></template>
                                            </td>
                                            <td style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--tp-line)" x-text="row.value"></td>
                                            <td style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--tp-line)" x-text="row.percent + '%'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            </template>

            <template x-if="! hasData">
                <p style="text-align:center;color:var(--tp-muted);padding:40px 0;font-weight:700">{{ __('Belum ada data untuk metrik ini. Ia akan muncul apabila murid mula menonton, menggemari, memuat turun dan mencuba kandungan anda.') }}</p>
            </template>
        </div>

        {{-- B + C side by side --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px">
            {{-- B. Weekly upload trend --}}
            <div class="tp-card" style="padding:22px">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink);margin-bottom:14px">{{ __('Aktiviti muat naik (7 hari)') }}</h2>
                <x-chart :config="$trendConfig" :height="260" :title="__('Aktiviti muat naik 7 hari lalu')"
                    :rows="$trendRows" :columns="[__('Hari'), __('Jumlah muat naik')]" />
            </div>

            {{-- C. Student quiz pass/fail --}}
            <div class="tp-card" style="padding:22px">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink);margin-bottom:14px">{{ __('Lulus / Gagal kuiz murid') }}</h2>

                @if ($passFail['total'] === 0)
                    <p style="text-align:center;color:var(--tp-muted);padding:40px 0;font-weight:700">{{ __('Belum ada percubaan kuiz selesai lagi.') }}</p>
                @else
                    <div x-data="appChart(@js($passFailConfig))">
                        <div style="position:relative;width:100%;height:240px;overflow:hidden">
                            <canvas x-ref="canvas" role="img" aria-label="{{ __('Lulus lawan gagal') }}"></canvas>
                            <div style="position:absolute;inset:0;display:grid;place-items:center;pointer-events:none">
                                <div style="text-align:center">
                                    <div class="tp-g" style="font-size:30px;font-weight:800;color:var(--tp-ink)">{{ number_format($passFail['total']) }}</div>
                                    <div style="font-size:12px;color:var(--tp-muted)">{{ __('percubaan') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="overflow-x:auto;margin-top:10px">
                        <table style="width:100%;border-collapse:collapse;font-size:14px">
                            <thead><tr>
                                <th scope="col" style="text-align:left;padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ __('Keputusan') }}</th>
                                <th scope="col" style="text-align:right;padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ __('Jumlah') }}</th>
                                <th scope="col" style="text-align:right;padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ __('Peratus') }}</th>
                            </tr></thead>
                            <tbody>
                                <tr>
                                    <td style="padding:6px 10px">{{ __('Lulus') }}</td>
                                    <td style="padding:6px 10px;text-align:right">{{ $passFail['passed'] }}</td>
                                    <td style="padding:6px 10px;text-align:right">{{ round($passFail['passed'] / $pfTotal * 100) }}%</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 10px">{{ __('Gagal') }}</td>
                                    <td style="padding:6px 10px;text-align:right">{{ $passFail['failed'] }}</td>
                                    <td style="padding:6px 10px;text-align:right">{{ round($passFail['failed'] / $pfTotal * 100) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- D. Transparent Talent signal (merged from the old Bakat page) --}}
        <div class="tp-card" style="padding:22px">
            <x-talent-scorecard :result="$talent" />
        </div>

        {{-- Recent videos --}}
        @if ($recentLessons->isNotEmpty())
            <div class="tp-card" style="padding:22px">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px">
                    <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Video Terbaru Saya') }}</h2>
                    <a href="{{ route('cikgu.video.index') }}" class="tp-btn-ghost">{{ __('Semua Video') }}</a>
                </div>
                <div class="tp-list">
                    @foreach ($recentLessons as $lesson)
                        <a href="{{ route('video.show', $lesson) }}" class="tp-listcard" style="text-decoration:none">
                            <span style="width:40px;height:40px;border-radius:12px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0">🎬</span>
                            <div style="display:flex;flex-direction:column;gap:3px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $lesson->title }}</span>
                                <span class="tp-meta">{{ $lesson->chapter->subject->displayName() }} · {{ $lesson->chapter->grade->name }} · {{ __('Bab :n', ['n' => $lesson->chapter->number]) }}</span>
                            </div>
                            <span class="tp-meta" style="flex-shrink:0">👁 {{ $lesson->views_count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-cikgu-layout>
