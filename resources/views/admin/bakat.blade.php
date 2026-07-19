@php
    $tcols = 'grid-template-columns:minmax(0,2fr) 1.6fr .7fr .7fr .7fr .9fr 1.1fr;gap:12px;align-items:center';

    // First subject a teacher works in (+N for the rest), for the compact table + podium captions.
    $subjectLabel = function ($teacherId, int $take = 2) use ($subjects, $subjectsByTeacher) {
        $ids = $subjectsByTeacher[$teacherId] ?? collect();
        if ($ids->isEmpty()) return '—';
        $names = $subjects->whereIn('id', $ids)->take($take)->map->displayName()->join(', ');
        $extra = $ids->count() - $take;
        return $extra > 0 ? $names.' +'.$extra : $names;
    };

    // Podium chrome per rank (gold / silver / bronze) — exact palette + raised-middle sizing.
    $podiumMeta = [
        1 => ['medal' => '🥇', 'ring' => '#F0C24B', 'bg' => '#FEF3D3', 'fg' => '#8A6A12', 'pad' => '34px', 'order' => 1],
        2 => ['medal' => '🥈', 'ring' => '#C7CDD6', 'bg' => '#EDF0F4', 'fg' => '#5B6472', 'pad' => '18px', 'order' => 0],
        3 => ['medal' => '🥉', 'ring' => '#D9A188', 'bg' => '#F8E7DE', 'fg' => '#9A5B3C', 'pad' => '18px', 'order' => 2],
    ];

    $pal = [['#DCF2EE', '#0F7A68'], ['#E4EEF9', '#2E6CA8'], ['#FBE4ED', '#B84A75'], ['#FEF0CE', '#8A6A12'], ['#FDE7E0', '#C24936']];
@endphp

<x-admin-layout :title="__('Cikgu')"
                :heading="__('Cikgu')"
                :sub="__('Gambaran keseluruhan cikgu, penyumbang terbaik dan kandungan paling berjaya')">

    <div style="display:flex;flex-direction:column;gap:48px">

        {{-- ============================ Teacher stats ============================ --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:#28293F">{{ __('Cikgu') }}</h2>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="font-size:13.5px;font-weight:700;color:#8B8AA3">🧑‍🏫 {{ __('Jumlah cikgu') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:#28293F">{{ number_format($totalTeachers) }}</span>
                </div>
                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="font-size:13.5px;font-weight:700;color:#0F7A68">✓ {{ __('Aktif') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:#28293F">{{ number_format($activeCount) }}</span>
                </div>
                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="font-size:13.5px;font-weight:700;color:#8B8AA3">✕ {{ __('Tidak aktif') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:#28293F">{{ number_format($inactiveCount) }}</span>
                </div>
            </div>
        </div>

        {{-- Teacher filter — carries the contributor filter through so it is not reset. --}}
        <form method="GET" action="{{ route('admin.bakat') }}" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap">
            @if ($contribSubject) <input type="hidden" name="p_subjek" value="{{ $contribSubject }}"> @endif
            @if ($contribGrade) <input type="hidden" name="p_tahun" value="{{ $contribGrade }}"> @endif
            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:#6C6F87">{{ __('Subjek') }}</label>
                <select name="subjek" class="tp-filter-select" style="min-width:220px" onchange="this.form.submit()">
                    <option value="">{{ __('Semua subjek') }}</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->slug }}" @selected($subjectSlug === $subject->slug)>{{ $subject->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:#6C6F87">{{ __('Tahun') }}</label>
                <select name="tahun" class="tp-filter-select" style="min-width:150px" onchange="this.form.submit()">
                    <option value="">{{ __('Semua tahun') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->level }}" @selected($gradeLevel === $grade->level)>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="tp-btn-ghost">{{ __('Tapis') }}</button></noscript>
            @if ($subjectSlug || $gradeLevel)
                <a href="{{ route('admin.bakat', array_filter(['p_subjek' => $contribSubject, 'p_tahun' => $contribGrade])) }}" class="tp-btn-ghost">{{ __('Kosongkan') }}</a>
            @endif
        </form>

        {{-- Teacher table --}}
        @if ($teachers->isEmpty())
            <div class="tp-empty">
                <span style="font-size:30px">🧑‍🏫</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:#28293F">{{ __('Tiada cikgu untuk dipaparkan') }}</h3>
                <p style="margin:0;font-size:14.5px;color:#8B8AA3;max-width:380px">{{ __('Tiada cikgu yang sepadan dengan tapisan ini.') }}</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:8px">
                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <div style="overflow-x:auto">
                        <div style="min-width:900px">
                            <div style="display:grid;{{ $tcols }};padding:14px 20px;border-bottom:1px solid rgba(46,44,80,.08)">
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:#8B8AA3">{{ __('Nama Cikgu') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:#8B8AA3">{{ __('Subjek') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:#8B8AA3">{{ __('Video') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:#8B8AA3">{{ __('Bahan') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:#8B8AA3">{{ __('Kuiz') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:#8B8AA3">{{ __('Status') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:#8B8AA3;text-align:right">{{ __('Tindakan') }}</span>
                            </div>
                            @foreach ($teachers as $teacher)
                                <div class="tp-tr" style="display:grid;{{ $tcols }};padding:12px 20px;border-bottom:1px solid rgba(46,44,80,.05)">
                                    <a href="{{ route('admin.bakat.show', $teacher) }}" style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:#28293F">{{ $teacher->name }}</span>
                                        @if ($teacher->email)
                                            <span style="font-size:11.5px;color:#8B8AA3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $teacher->email }}</span>
                                        @endif
                                    </a>
                                    <span style="font-size:13px;font-weight:700;color:#4276AE">{{ $subjectLabel($teacher->id) }}</span>
                                    <span style="font-size:13px;font-weight:700;color:#6C6F87">{{ number_format($teacher->video_count) }}</span>
                                    <span style="font-size:13px;font-weight:700;color:#6C6F87">{{ number_format($teacher->material_count) }}</span>
                                    <span style="font-size:13px;font-weight:700;color:#6C6F87">{{ number_format($teacher->quiz_count) }}</span>
                                    @if ($teacher->isActive())
                                        <span style="justify-self:start;background:#DCF2EE;color:#0F7A68;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ __('Aktif') }}</span>
                                    @else
                                        <span style="justify-self:start;background:#F1F0E8;color:#6C6F87;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ __('Tidak aktif') }}</span>
                                    @endif
                                    <form method="POST" action="{{ route('admin.guru.status', $teacher) }}" style="justify-self:end">
                                        @csrf
                                        <button type="submit" class="tp-linkbtn {{ $teacher->isActive() ? 'is-muted is-danger' : '' }}">
                                            {{ $teacher->isActive() ? '🚫 '.__('Nyahaktif') : '✓ '.__('Aktifkan') }}<span class="sr-only">{{ $teacher->name }}</span>
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <span style="font-size:12.5px;color:#8B8AA3">{{ __('Nyahaktif hanya menghalang cikgu daripada log masuk. Video, bahan dan kuiz mereka kekal diterbitkan untuk murid.') }}</span>
                <div>{{ $teachers->links() }}</div>
            </div>
        @endif

        {{-- ========================= Top contributors ========================= --}}
        <div style="display:flex;flex-direction:column;gap:14px">
            <div style="display:flex;flex-direction:column;gap:2px">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:#28293F">🏆 {{ __('Penyumbang Terbaik') }}</h2>
                <span style="font-size:13px;color:#8B8AA3">{{ __('Cikgu dengan kandungan diterbitkan paling banyak') }}</span>
            </div>

            @if ($contributors->isEmpty())
                <div class="tp-empty">
                    <span style="font-size:30px">🏆</span>
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:#28293F">{{ __('Tiada penyumbang untuk dipaparkan') }}</h3>
                </div>
            @else
                {{-- Podium: ranks 1-3, winner raised in the middle. --}}
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;align-items:end">
                    @foreach ($contributors->take(3) as $i => $teacher)
                        @php($m = $podiumMeta[$i + 1])
                        <div style="background:#fff;border:1.5px solid {{ $m['ring'] }};border-radius:20px;padding:{{ $m['pad'] }} 20px 20px;display:flex;flex-direction:column;align-items:center;gap:7px;box-shadow:0 6px 20px rgba(46,44,80,.06);order:{{ $m['order'] }}">
                            <span style="font-size:30px;margin-top:-34px;filter:drop-shadow(0 3px 6px rgba(46,44,80,.2))">{{ $m['medal'] }}</span>
                            <span style="width:56px;height:56px;border-radius:50%;background:{{ $m['bg'] }};color:{{ $m['fg'] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:18px;border:3px solid {{ $m['ring'] }}">{{ $teacher->initials() }}</span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15.5px;color:#28293F;text-align:center">{{ $teacher->name }}</span>
                            <span style="font-size:12.5px;font-weight:700;color:#8B8AA3;text-align:center">{{ $subjectLabel($teacher->id, 1) }}</span>
                            <span style="border-radius:999px;padding:5px 15px;font-family:'Geist',sans-serif;font-size:13px;font-weight:800;background:{{ $m['bg'] }};color:{{ $m['fg'] }}">{{ number_format($teacher->published_content) }} {{ __('kandungan') }}</span>
                            <div style="display:flex;gap:16px;margin-top:2px">
                                <span style="font-size:12px;font-weight:700;color:#8B8AA3">👁 {{ number_format((int) $teacher->views_sum) }}</span>
                                <span style="font-size:12px;font-weight:700;color:#8B8AA3">♥ {{ number_format((int) $teacher->favourites_sum) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Ranks 4-10 --}}
                @if ($contributors->count() > 3)
                    <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                        @foreach ($contributors->slice(3) as $i => $teacher)
                            @php($p = $pal[$i % count($pal)])
                            <div class="tp-tr" style="display:flex;align-items:center;gap:14px;padding:12px 20px;border-bottom:1px solid rgba(46,44,80,.05)">
                                <span style="width:30px;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:#8B8AA3;text-align:center;flex-shrink:0">{{ $i + 4 }}</span>
                                <span style="width:36px;height:36px;border-radius:50%;background:{{ $p[0] }};color:{{ $p[1] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;flex-shrink:0">{{ $teacher->initials() }}</span>
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:#28293F">{{ $teacher->name }}</span>
                                    <span style="font-size:12px;color:#8B8AA3">{{ $subjectLabel($teacher->id, 1) }}</span>
                                </div>
                                <span style="font-size:12.5px;font-weight:700;color:#8B8AA3;flex-shrink:0">👁 {{ number_format((int) $teacher->views_sum) }}</span>
                                <span style="font-size:12.5px;font-weight:700;color:#8B8AA3;flex-shrink:0">♥ {{ number_format((int) $teacher->favourites_sum) }}</span>
                                <span style="background:#F1F0E8;color:#4A4B63;border-radius:999px;padding:4px 13px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800;flex-shrink:0">{{ number_format($teacher->published_content) }} {{ __('kandungan') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <span style="font-size:12.5px;color:#8B8AA3">{{ __('Kandungan diterbitkan = video + bahan + kuiz yang diterbitkan.') }}</span>
            @endif
        </div>

        {{-- ======================= Top-performing content ======================= --}}
        <div style="display:flex;flex-direction:column;gap:14px;margin-top:24px">
            <div style="display:flex;flex-direction:column;gap:2px">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:#28293F">⭐ {{ __('Kandungan Paling Berjaya') }}</h2>
                <span style="font-size:13px;color:#8B8AA3">{{ __('Merentas seluruh platform, tanpa tapisan') }}</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                {{-- Most-viewed video --}}
                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04);display:flex;flex-direction:column">
                    <div style="background:#E4EEF9;padding:12px 20px;display:flex;align-items:center;gap:9px">
                        <span style="font-size:15px">🎥</span>
                        <span style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#2E6CA8">{{ __('Video Paling Ditonton') }}</span>
                    </div>
                    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:5px">
                        @if ($topVideo)
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:16px;color:#28293F">{{ $topVideo->title }}</span>
                            <span style="font-size:12.5px;color:#8B8AA3">{{ $topVideo->chapter->subject->displayName() }} · {{ $topVideo->teacher?->name }}</span>
                            <div style="display:flex;gap:24px;margin-top:12px">
                                <div style="display:flex;flex-direction:column">
                                    <span style="font-size:12px;font-weight:700;color:#8B8AA3">{{ __('Tontonan') }}</span>
                                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#28293F">{{ number_format($topVideo->views_count) }}</span>
                                </div>
                                <div style="display:flex;flex-direction:column">
                                    <span style="font-size:12px;font-weight:700;color:#8B8AA3">{{ __('Kegemaran') }}</span>
                                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#28293F">{{ number_format($topVideo->favourites_count) }}</span>
                                </div>
                            </div>
                        @else
                            <span style="font-size:13.5px;color:#8B8AA3">{{ __('Belum ada video.') }}</span>
                        @endif
                    </div>
                </div>
                {{-- Most-downloaded material --}}
                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04);display:flex;flex-direction:column">
                    <div style="background:#DCF2EE;padding:12px 20px;display:flex;align-items:center;gap:9px">
                        <span style="font-size:15px">📁</span>
                        <span style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#0F7A68">{{ __('Bahan Paling Dimuat Turun') }}</span>
                    </div>
                    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:5px">
                        @if ($topMaterial)
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:16px;color:#28293F">{{ $topMaterial->title }}</span>
                            <span style="font-size:12.5px;color:#8B8AA3">{{ $topMaterial->chapter->subject->displayName() }} · {{ $topMaterial->teacher?->name }}</span>
                            <div style="display:flex;gap:24px;margin-top:12px">
                                <div style="display:flex;flex-direction:column">
                                    <span style="font-size:12px;font-weight:700;color:#8B8AA3">{{ __('Muat Turun') }}</span>
                                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#28293F">{{ number_format($topMaterial->download_count) }}</span>
                                </div>
                            </div>
                        @else
                            <span style="font-size:13.5px;color:#8B8AA3">{{ __('Belum ada bahan.') }}</span>
                        @endif
                    </div>
                </div>
                {{-- Most-attempted quiz --}}
                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04);display:flex;flex-direction:column">
                    <div style="background:#FEF0CE;padding:12px 20px;display:flex;align-items:center;gap:9px">
                        <span style="font-size:15px">📝</span>
                        <span style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:#8A6A12">{{ __('Kuiz Paling Dicuba') }}</span>
                    </div>
                    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:5px">
                        @if ($topQuiz)
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:16px;color:#28293F">{{ $topQuiz->title }}</span>
                            <span style="font-size:12.5px;color:#8B8AA3">{{ $topQuiz->chapter->subject->displayName() }} · {{ $topQuiz->teacher?->name }}</span>
                            <div style="display:flex;gap:24px;margin-top:12px">
                                <div style="display:flex;flex-direction:column">
                                    <span style="font-size:12px;font-weight:700;color:#8B8AA3">{{ __('Percubaan') }}</span>
                                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#28293F">{{ number_format($topQuiz->attempt_total) }}</span>
                                </div>
                                <div style="display:flex;flex-direction:column">
                                    <span style="font-size:12px;font-weight:700;color:#8B8AA3">{{ __('Lulus') }}</span>
                                    <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#0F7A68">{{ number_format($topQuiz->pass_total) }}</span>
                                </div>
                            </div>
                        @else
                            <span style="font-size:13.5px;color:#8B8AA3">{{ __('Belum ada percubaan kuiz.') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
