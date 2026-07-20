@php
    $user = auth()->user();

    $pal = [['#DCF2EE', '#0F7A68'], ['#E4EEF9', '#2E6CA8'], ['#FBE4ED', '#B84A75'], ['#FEF0CE', '#8A6A12'], ['#FDE7E0', '#C24936']];

    // Interactive Platform Activity series.
    $series = [
        ['key' => 'views', 'label' => __('Tontonan video'), 'color' => '#17907B'],
        ['key' => 'completed', 'label' => __('Kuiz selesai'), 'color' => '#4276AE'],
        ['key' => 'passed', 'label' => __('Kuiz lulus'), 'color' => '#E3A31C'],
        ['key' => 'uploads', 'label' => __('Muat naik'), 'color' => '#B84A75'],
    ];
    $activitySeries = collect($series)->map(fn ($s) => [
        ...$s,
        'data' => $activity['series'][$s['key']],
    ])->all();

    $periods = [
        '7d' => __('7 hari'),
        '30d' => __('30 hari'),
        '12m' => __('12 bulan'),
    ];

    $summary = [
        ['icon' => '👨‍🎓', 'tint' => '#E4EEF9', 'label' => __('Jumlah murid'), 'value' => $totals['students']],
        ['icon' => '🧑‍🏫', 'tint' => '#DCF2EE', 'label' => __('Jumlah cikgu'), 'value' => $totals['teachers']],
        ['icon' => '🎬', 'tint' => '#FEF0CE', 'label' => __('Jumlah video'), 'value' => $totals['videos']],
        ['icon' => '📝', 'tint' => '#FBE4ED', 'label' => __('Jumlah kuiz'), 'value' => $totals['quizzes']],
    ];

    $card = 'background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;padding:22px;box-shadow:0 2px 10px rgba(46,44,80,.04)';
    $h2 = "margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)";
@endphp

<x-admin-layout :title="__('Utama')"
                :heading="__('Selamat datang, :name', ['name' => $user->name])"
                :sub="__('Gambaran keseluruhan platform WeLearn')">

    <div style="display:flex;flex-direction:column;gap:22px">

        {{-- 1. Heading, period + export actions --}}
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:14px">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="font-size:13px;font-weight:700;color:var(--tp-muted)">{{ __('Tempoh laporan:') }}</span>
                @foreach ($periods as $key => $label)
                    <a href="{{ route('admin.dashboard', ['period' => $key]) }}"
                       @if ($period === $key) aria-current="true" @endif
                       style="min-height:38px;display:inline-flex;align-items:center;border-radius:999px;padding:0 14px;font-family:'Geist',sans-serif;font-weight:800;font-size:12.5px;text-decoration:none;{{ $period === $key ? 'background:#17907B;color:#fff' : 'background:var(--tp-input);color:var(--tp-muted-2)' }}">{{ $label }}</a>
                @endforeach
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('admin.laporan.pdf', ['period' => $period]) }}" class="tp-btn-outline" style="min-height:42px;border-radius:11px;font-size:13px;padding:0 16px;border-width:1.5px;display:inline-flex;align-items:center;gap:6px">📄 {{ __('Jana PDF') }}</a>
                <a href="{{ route('admin.laporan.word', ['period' => $period]) }}" class="tp-btn-outline" style="min-height:42px;border-radius:11px;font-size:13px;padding:0 16px;border-width:1.5px;display:inline-flex;align-items:center;gap:6px">📝 {{ __('Jana Word') }}</a>
            </div>
        </div>

        {{-- 2. Top 3 contributors --}}
        <div style="{{ $card }}">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px">
                <h2 style="{{ $h2 }}">🏅 {{ __('Penyumbang Teratas') }}</h2>
                <a href="{{ route('admin.penyumbang') }}" class="tp-btn-outline" style="min-height:40px;border-radius:11px;font-size:13px;padding:0 14px;border-width:1.5px">{{ __('Lihat semua penyumbang') }}</a>
            </div>
            <p style="margin:0 0 14px;font-size:12.5px;color:var(--tp-muted)">{{ __('Sumbangan = bilangan Video + Bahan + Kuiz yang dicipta.') }}</p>

            @if ($topContributors->isEmpty())
                <p style="font-size:13.5px;color:var(--tp-muted)">{{ __('Belum ada penyumbang lagi.') }}</p>
            @else
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
                    @foreach ($topContributors as $i => $c)
                        @php($p = $pal[$i % count($pal)])
                        <div style="border:1px solid var(--tp-line);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:8px">
                            <div style="display:flex;align-items:center;gap:12px">
                                <span style="width:40px;height:40px;border-radius:12px;background:{{ $p[0] }};color:{{ $p[1] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;flex-shrink:0">{{ ['🥇','🥈','🥉'][$i] ?? ($i + 1) }}</span>
                                <div style="min-width:0">
                                    <div style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['name'] }}</div>
                                    @if ($c['school'])<div style="font-size:12px;color:var(--tp-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c['school'] }}</div>@endif
                                </div>
                            </div>
                            <div style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:var(--tp-ink)">{{ number_format($c['total']) }} <span style="font-size:13px;color:var(--tp-muted);font-weight:700">{{ __('sumbangan') }}</span></div>
                            <div style="display:flex;gap:12px;font-size:12.5px;color:var(--tp-muted-2)">
                                <span>🎬 {{ $c['videos'] }}</span>
                                <span>📄 {{ $c['materials'] }}</span>
                                <span>📝 {{ $c['quizzes'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 3. Top-performing content with teacher attribution --}}
        <div style="{{ $card }}">
            <h2 style="{{ $h2 }};margin-bottom:14px">⭐ {{ __('Kandungan Berprestasi Tinggi') }}</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
                @php($topItems = [
                    ['emoji' => '🎬', 'label' => __('Video paling ditonton'), 'metric' => __('tontonan'), 'data' => $topContent['video'], 'link' => route('admin.kandungan.video')],
                    ['emoji' => '📄', 'label' => __('Bahan paling dimuat turun'), 'metric' => __('muat turun'), 'data' => $topContent['material'], 'link' => route('admin.kandungan.bahan')],
                    ['emoji' => '📝', 'label' => __('Kuiz paling dicuba'), 'metric' => __('percubaan selesai'), 'data' => $topContent['quiz'], 'link' => route('admin.kandungan.kuiz')],
                ])
                @foreach ($topItems as $item)
                    <a href="{{ $item['link'] }}" style="text-decoration:none;border:1px solid var(--tp-line);border-radius:14px;padding:16px;display:flex;flex-direction:column;gap:6px">
                        <span style="font-size:12px;font-weight:800;color:var(--tp-muted)">{{ $item['emoji'] }} {{ $item['label'] }}</span>
                        @if ($item['data'])
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--tp-ink);line-height:1.3">{{ $item['data']['title'] }}</span>
                            <span style="font-size:12.5px;color:var(--tp-muted-2)">{{ __('Cikgu: :name', ['name' => $item['data']['teacher'] ?? __('Tidak diketahui')]) }}</span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:18px;color:#17907B">{{ number_format($item['data']['count']) }} <span style="font-size:12px;color:var(--tp-muted);font-weight:700">{{ $item['metric'] }}</span></span>
                        @else
                            <span style="font-size:13px;color:var(--tp-muted)">{{ __('Tiada data lagi.') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- 4. Interactive platform activity --}}
        <div style="{{ $card }}" x-data="activityChart({ labels: @js($activity['labels']), series: @js($activitySeries) })">
            <h2 style="{{ $h2 }};margin-bottom:14px">📊 {{ __('Aktiviti Platform') }} <span style="font-size:13px;font-weight:700;color:var(--tp-muted)">· {{ $periods[$period] }}</span></h2>

            {{-- Accessible series toggles --}}
            <div style="display:flex;flex-wrap:wrap;gap:14px;margin-bottom:12px">
                @foreach ($series as $s)
                    <label style="display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:var(--tp-ink);cursor:pointer">
                        <input type="checkbox" checked @change="toggle('{{ $s['key'] }}')" style="width:16px;height:16px;accent-color:{{ $s['color'] }}">
                        <span style="width:11px;height:11px;border-radius:3px;background:{{ $s['color'] }};display:inline-block"></span>
                        {{ $s['label'] }}
                    </label>
                @endforeach
            </div>

            <div style="position:relative;width:100%;height:300px;overflow:hidden">
                <canvas x-ref="canvas" role="img" aria-label="{{ __('Aktiviti platform mengikut tempoh') }}"></canvas>
            </div>

            {{-- Accessible tabular alternative (server-rendered, works without JS) --}}
            <details style="margin-top:12px">
                <summary style="cursor:pointer;font-size:13px;font-weight:700;color:var(--tp-muted)">{{ __('Lihat data sebagai jadual') }}</summary>
                <div style="overflow-x:auto;margin-top:8px">
                    <table style="width:100%;border-collapse:collapse;font-size:13.5px;min-width:520px">
                        <thead><tr>
                            <th scope="col" style="text-align:left;padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ __('Tempoh') }}</th>
                            @foreach ($series as $s)
                                <th scope="col" style="text-align:right;padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ $s['label'] }}</th>
                            @endforeach
                        </tr></thead>
                        <tbody>
                            @foreach ($activity['labels'] as $i => $label)
                                <tr>
                                    <td style="padding:6px 10px;border-bottom:1px solid var(--tp-line)">{{ $label }}</td>
                                    @foreach ($series as $s)
                                        <td style="padding:6px 10px;text-align:right;border-bottom:1px solid var(--tp-line)">{{ $activity['series'][$s['key']][$i] }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </div>

        {{-- 5. Registrations in the last 7 days --}}
        <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;box-shadow:0 2px 10px rgba(46,44,80,.04);overflow:hidden">
            <div style="display:flex;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid var(--tp-line)">
                <div style="flex:1">
                    <h2 style="{{ $h2 }}">{{ __('Pendaftaran (7 hari lalu)') }}</h2>
                    <p style="margin:2px 0 0;font-size:12.5px;color:var(--tp-muted)">{{ __(':count akaun baharu sejak :date', ['count' => $registrationsCount, 'date' => now()->subDays(7)->translatedFormat('j M')]) }}</p>
                </div>
                @if ($registrationsCount > $registrations->count())
                    <a href="{{ route('admin.pengguna') }}" class="tp-btn-outline" style="min-height:42px;border-radius:11px;font-size:13px;padding:0 16px;border-width:1.5px">{{ __('Lihat Semua') }}</a>
                @endif
            </div>

            @forelse ($registrations as $i => $u)
                @php($p = $pal[$i % count($pal)])
                <div style="display:flex;align-items:center;gap:14px;padding:13px 22px;border-bottom:1px solid var(--tp-line)">
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

        {{-- 6. Pending oversight --}}
        <div style="{{ $card }};display:flex;flex-direction:column;gap:14px">
            <h2 style="{{ $h2 }}">⏳ {{ __('Tindakan Menunggu') }}</h2>
            @foreach ($pending as $p)
                <div style="display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-radius:12px;background:{{ $p['bg'] }}">
                    <span style="font-size:15px;flex-shrink:0">{{ $p['icon'] }}</span>
                    <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink)">{{ $p['title'] }}</span>
                        <span style="font-size:12.5px;color:var(--tp-muted-2);line-height:1.45">{{ $p['desc'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 7. All-time summary cards, at the bottom --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
            @foreach ($summary as $s)
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:6px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="width:40px;height:40px;border-radius:12px;background:{{ $s['tint'] }};display:grid;place-items:center;font-size:17px">{{ $s['icon'] }}</span>
                        <span style="font-size:13.5px;font-weight:700;color:var(--tp-muted)">{{ $s['label'] }}</span>
                    </div>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:var(--tp-ink)">{{ number_format($s['value']) }}</span>
                </div>
            @endforeach
        </div>
    </div>
</x-admin-layout>
