@php
    $r = $result;
    $bars = [
        ['label' => __('Penglibatan'),        'hint' => __('Tontonan + kegemaran murid (unik)'),          'value' => number_format((int) round($r->raw['engagement'])),                                                     'norm' => $r->norm['engagement'] ?? 0],
        ['label' => __('Kualiti'),            'hint' => __('Kadar kegemaran per penonton'),               'value' => round($r->raw['quality'] * 100, 1).'%',                                                                'norm' => $r->norm['quality'] ?? 0],
        ['label' => __('Hasil Pembelajaran'), 'hint' => __('Beza markah kuiz penonton vs purata bab'),    'value' => $r->raw['outcome'] === null ? '—' : (($r->raw['outcome'] > 0 ? '+' : '').$r->raw['outcome']),          'norm' => $r->norm['outcome'] ?? 0],
        ['label' => __('Keluasan'),           'hint' => __('Bilangan bab disumbang'),                     'value' => (int) $r->raw['breadth'],                                                                              'norm' => $r->norm['breadth'] ?? 0],
    ];
    $lcols = 'grid-template-columns:minmax(0,2fr) 1fr .7fr .7fr .9fr;gap:12px;align-items:center';
@endphp

<x-admin-layout :title="$r->teacher->name"
                :heading="$r->teacher->name"
                :sub="$r->teacher->email ? $r->teacher->email.($r->channels > 0 ? ' · '.trans_choice('{1}:n channel YouTube disahkan|[2,*]:n channel YouTube disahkan', $r->channels, ['n' => $r->channels]) : '') : '—'">

    <div style="display:flex;flex-direction:column;gap:20px">

        <a href="{{ route('admin.bakat') }}" class="tp-linkbtn is-muted" style="align-self:flex-start;padding:0">
            ← {{ __('Kembali ke senarai') }}
        </a>

        {{-- Talent scorecard: the transparent headline + four sub-scores, in the WeLearn palette. --}}
        <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;padding:24px;box-shadow:0 2px 10px rgba(46,44,80,.04);display:flex;flex-direction:column;gap:20px">
            <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:16px">
                <div style="display:flex;flex-direction:column;gap:2px">
                    <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--tp-muted)">{{ __('Skor Bakat') }}</span>
                    @if ($r->sufficient && $r->headline !== null)
                        <span style="font-family:'Geist',sans-serif;font-size:48px;font-weight:800;line-height:1;color:var(--tp-ink)">{{ $r->headline }}<span style="font-size:22px;font-weight:800;color:var(--tp-muted)">/100</span></span>
                    @else
                        <span style="font-family:'Geist',sans-serif;font-size:24px;font-weight:800;color:var(--tp-muted)">{{ __('Data belum mencukupi') }}</span>
                    @endif
                </div>
                <span style="font-size:13px;color:var(--tp-muted)">
                    {{ __(':n murid terlibat', ['n' => $r->engaged_students]) }}
                    @unless ($r->sufficient)
                        · <span style="color:#8A6A12;font-weight:700">{{ __('perlu sekurang-kurangnya :min untuk skor', ['min' => config('talent.min_engaged_students')]) }}</span>
                    @endunless
                </span>
            </div>

            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">
                @foreach ($bars as $bar)
                    <div style="border:1px solid var(--tp-line);border-radius:14px;padding:16px">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink)">{{ $bar['label'] }}</span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink)">{{ $bar['value'] }}</span>
                        </div>
                        <div style="margin-top:10px;height:8px;border-radius:999px;background:var(--tp-line);overflow:hidden">
                            <div style="height:100%;border-radius:999px;background:#17907B;width:{{ round(($bar['norm']) * 100) }}%"></div>
                        </div>
                        <p style="margin:8px 0 0;font-size:12px;color:var(--tp-muted)">{{ $bar['hint'] }}</p>
                    </div>
                @endforeach
            </div>

            <x-talent-disclaimer />
        </div>

        {{-- Per-lesson breakdown --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Pecahan mengikut video') }}</h2>

            @if ($r->lessons->isEmpty())
                <div class="tp-empty">
                    <span style="font-size:30px">🎬</span>
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada video dikira') }}</h3>
                    <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Guru ini belum ada video yang dikira untuk skor bakat.') }}</p>
                </div>
            @else
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <div style="overflow-x:auto">
                        <div style="min-width:640px">
                            <div style="display:grid;{{ $lcols }};padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Video') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Milik') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Jangkauan') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Kegemaran') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tamat tonton') }}</span>
                            </div>
                            @foreach ($r->lessons as $entry)
                                <div class="tp-tr" style="display:grid;{{ $lcols }};padding:12px 20px;border-bottom:1px solid var(--tp-line)">
                                    <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $entry->lesson->title }}</span>
                                        <span style="font-size:11.5px;color:var(--tp-muted)">{{ $entry->lesson->chapter->subject->name }} · {{ __('Bab') }} {{ $entry->lesson->chapter->number }}</span>
                                    </div>
                                    <span><x-ownership-badge :lesson="$entry->lesson" /></span>
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ number_format($entry->reach) }}</span>
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ number_format($entry->favourites) }}</span>
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ number_format($entry->completion) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-admin-layout>
