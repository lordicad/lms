@php($forWord = $forWord ?? false)
{{-- The PDF gets a full styled HTML document; the Word (PhpWord) path receives a plain fragment,
     because PhpWord's HTML reader parses XML and rejects a DOCTYPE/<head>/<style> block. --}}
@unless ($forWord)
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Laporan Papan Pemuka WeLearn') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2a37; font-size: 12px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { font-size: 15px; margin: 18px 0 6px; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; }
        p { margin: 3px 0; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 11px; }
        th { background: #f1f5f9; }
        td.num, th.num { text-align: right; }
    </style>
</head>
<body>
@endunless
    <h1>{{ __('Laporan Papan Pemuka WeLearn') }}</h1>
    <p class="muted">{{ __('Dijana pada :date (:tz)', ['date' => $generatedAt->translatedFormat('j F Y, g:i A'), 'tz' => $timezone]) }}</p>
    <p class="muted">{{ __('Tempoh laporan (Aktiviti Platform):') }} <strong>{{ __($periodLabel) }}</strong></p>

    {{-- Top contributors --}}
    <h2>{{ __('Penyumbang Teratas') }}</h2>
    <p class="muted">{{ __('Sumbangan = bilangan Video + Bahan + Kuiz yang dicipta.') }}</p>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
            <tr>
                <th>#</th><th>{{ __('Cikgu') }}</th><th>{{ __('Sekolah') }}</th>
                <th class="num">{{ __('Sumbangan') }}</th><th class="num">{{ __('Video') }}</th>
                <th class="num">{{ __('Bahan') }}</th><th class="num">{{ __('Kuiz') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($contributors as $i => $c)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $c['name'] }}</td>
                    <td>{{ $c['school'] ?? '—' }}</td>
                    <td class="num">{{ $c['total'] }}</td>
                    <td class="num">{{ $c['videos'] }}</td>
                    <td class="num">{{ $c['materials'] }}</td>
                    <td class="num">{{ $c['quizzes'] }}</td>
                </tr>
            @empty
                <tr><td colspan="7">{{ __('Belum ada penyumbang.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Top content --}}
    <h2>{{ __('Kandungan Berprestasi Tinggi') }}</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
            <tr><th>{{ __('Jenis') }}</th><th>{{ __('Tajuk') }}</th><th>{{ __('Cikgu') }}</th><th class="num">{{ __('Jumlah') }}</th></tr>
        </thead>
        <tbody>
            @php($rows = [
                ['t' => __('Video paling ditonton'), 'd' => $topContent['video'], 'm' => __('tontonan')],
                ['t' => __('Bahan paling dimuat turun'), 'd' => $topContent['material'], 'm' => __('muat turun')],
                ['t' => __('Kuiz paling dicuba'), 'd' => $topContent['quiz'], 'm' => __('percubaan')],
            ])
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['t'] }}</td>
                    <td>{{ $row['d']['title'] ?? '—' }}</td>
                    <td>{{ $row['d']['teacher'] ?? '—' }}</td>
                    <td class="num">{{ $row['d'] ? $row['d']['count'].' '.$row['m'] : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Platform activity --}}
    <h2>{{ __('Aktiviti Platform') }} — {{ __($periodLabel) }}</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
            <tr>
                <th>{{ __('Tempoh') }}</th>
                <th class="num">{{ __('Tontonan video') }}</th>
                <th class="num">{{ __('Kuiz selesai') }}</th>
                <th class="num">{{ __('Kuiz lulus') }}</th>
                <th class="num">{{ __('Muat naik') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($activity['labels'] as $i => $label)
                <tr>
                    <td>{{ $label }}</td>
                    <td class="num">{{ $activity['series']['views'][$i] }}</td>
                    <td class="num">{{ $activity['series']['completed'][$i] }}</td>
                    <td class="num">{{ $activity['series']['passed'][$i] }}</td>
                    <td class="num">{{ $activity['series']['uploads'][$i] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Registrations --}}
    <h2>{{ __('Pendaftaran (7 hari lalu)') }}</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
            <tr><th>{{ __('Nama') }}</th><th>{{ __('Peranan') }}</th><th>{{ __('Tahun') }}</th><th>{{ __('Tarikh') }}</th></tr>
        </thead>
        <tbody>
            @forelse ($registrations as $u)
                <tr>
                    <td>{{ $u->name }}</td>
                    <td>{{ $u->isTeacher() ? __('Cikgu') : __('Murid') }}</td>
                    <td>{{ $u->grade?->name ?? '—' }}</td>
                    <td>{{ $u->created_at->translatedFormat('j M Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">{{ __('Tiada pendaftaran dalam 7 hari lalu.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pending oversight --}}
    <h2>{{ __('Tindakan Menunggu') }}</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <tbody>
            @foreach ($pending as $item)
                <tr>
                    <td><strong>{{ $item['title'] }}</strong></td>
                    <td>{{ $item['desc'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- All-time totals --}}
    <h2>{{ __('Jumlah Keseluruhan') }}</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <tbody>
            <tr><td>{{ __('Jumlah murid') }}</td><td class="num">{{ $totals['students'] }}</td></tr>
            <tr><td>{{ __('Jumlah cikgu') }}</td><td class="num">{{ $totals['teachers'] }}</td></tr>
            <tr><td>{{ __('Jumlah video') }}</td><td class="num">{{ $totals['videos'] }}</td></tr>
            <tr><td>{{ __('Jumlah kuiz') }}</td><td class="num">{{ $totals['quizzes'] }}</td></tr>
        </tbody>
    </table>
@unless ($forWord)
</body>
</html>
@endunless
