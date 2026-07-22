@php
    $scols = 'grid-template-columns:minmax(0,1.8fr) .9fr 1fr 1.3fr 1fr .6fr .6fr;gap:12px;align-items:center';

    // Podium chrome per rank: medal, block height + gradient, avatar ring/tint. Exact from the prototype.
    $pMeta = [
        1 => ['medal' => '🥇', 'h' => 96, 'block' => 'linear-gradient(180deg,#F5CB5E,#E3A31C)', 'ring' => '#F0C24B', 'bg' => '#FEF3D3', 'fg' => '#8A6A12'],
        2 => ['medal' => '🥈', 'h' => 68, 'block' => 'linear-gradient(180deg,#D5DAE2,#AEB6C2)', 'ring' => '#C7CDD6', 'bg' => '#EDF0F4', 'fg' => '#5B6472'],
        3 => ['medal' => '🥉', 'h' => 50, 'block' => 'linear-gradient(180deg,#E0A987,#C07B52)', 'ring' => '#D9A188', 'bg' => '#F8E7DE', 'fg' => '#9A5B3C'],
    ];
@endphp

<x-admin-layout :title="__('Murid')"
                :heading="__('Murid')"
                :sub="__('Gambaran keseluruhan murid dan aktiviti mereka di platform')">

    <div style="display:flex;flex-direction:column;gap:22px">

        {{-- Stats: total + one per Tahun --}}
        <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:12px">
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:14px;padding:16px 18px;display:flex;flex-direction:column;gap:6px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <span style="font-size:12.5px;font-weight:700;color:var(--tp-muted)">👥 {{ __('Jumlah') }}</span>
                <span style="font-family:'Geist',sans-serif;font-size:24px;font-weight:800;color:var(--tp-ink)">{{ number_format($totalStudents) }}</span>
            </div>
            @foreach ($grades as $grade)
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:14px;padding:16px 18px;display:flex;flex-direction:column;gap:6px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="font-size:12.5px;font-weight:700;color:var(--tp-muted)">{{ $grade->name }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:24px;font-weight:800;color:var(--tp-ink)">{{ number_format($countsByGrade[$grade->level] ?? 0) }}</span>
                </div>
            @endforeach
        </div>

        {{-- Roster --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Senarai Murid') }}</h2>

            {{-- Tahun then Kelas: a class belongs to a year, so the Kelas list follows the Tahun on
                 screen the same way Subjek follows Tahun elsewhere. --}}
            <form method="GET" action="{{ route('admin.murid') }}" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;align-self:flex-start">
                @foreach ($podiums as $podium)
                    @if ($podium->subjectSlug)
                        <input type="hidden" name="subjek_{{ $podium->grade->level }}" value="{{ $podium->subjectSlug }}">
                    @endif
                @endforeach

                <div style="display:flex;flex-direction:column;gap:6px">
                    <label for="filter-tahun" style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:var(--tp-muted-2)">{{ __('Tahun') }}</label>
                    <select id="filter-tahun" name="tahun" class="tp-filter-select" style="min-width:150px" onchange="this.form.submit()">
                        <option value="">{{ __('Semua tahun') }}</option>
                        @foreach ($grades as $grade)
                            <option value="{{ $grade->level }}" @selected($gradeLevel === $grade->level)>{{ $grade->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex;flex-direction:column;gap:6px">
                    <label for="filter-kelas" style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:var(--tp-muted-2)">{{ __('Kelas') }}</label>
                    <select id="filter-kelas" name="kelas" class="tp-filter-select" style="min-width:170px" onchange="this.form.submit()">
                        <option value="">{{ __('Semua kelas') }}</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected($classId === $class->id)>
                                {{ $gradeLevel ? $class->name : $class->grade->name.' '.$class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <noscript><button type="submit" class="tp-btn-ghost">{{ __('Tapis') }}</button></noscript>

                @if ($gradeLevel || $classId)
                    <a href="{{ route('admin.murid') }}" style="min-height:46px;display:inline-flex;align-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-muted-2);text-decoration:none">{{ __('Kosongkan') }}</a>
                @endif
            </form>

            @if ($students->isEmpty())
                <div class="tp-empty">
                    <span style="font-size:30px">🧑‍🎓</span>
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Tiada murid untuk dipaparkan') }}</h3>
                    <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Tiada murid yang sepadan dengan tapisan ini.') }}</p>
                </div>
            @else
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <div style="overflow-x:auto">
                        <div style="min-width:860px">
                            <div style="display:grid;{{ $scols }};padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Nama Murid') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Tahun') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Video Ditonton') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Bahan Dimuat Turun') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Percubaan Kuiz') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Lulus') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Gagal') }}</span>
                            </div>
                            @foreach ($students as $student)
                                @php($has = $student->attempts_count > 0)
                                <div class="tp-tr" style="display:grid;{{ $scols }};padding:12px 20px;border-bottom:1px solid var(--tp-line)">
                                    <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink)">{{ $student->name }}</span>
                                        <span style="font-size:11.5px;color:var(--tp-muted)">{{ $student->username }}</span>
                                    </div>
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ $student->grade?->name ?? '—' }}</span>
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ number_format($student->videos_viewed) }}</span>
                                    {{-- Downloads are counted per file, never per student — an em dash beats inventing one. --}}
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted);text-align:center">—</span>
                                    <span style="font-size:13px;font-weight:700;color:#4276AE;text-align:center">{{ number_format($student->attempts_count) }}</span>
                                    <span style="text-align:center;font-size:13px;font-weight:800;color:{{ $has ? '#0F7A68' : '#8B8AA3' }}">{{ $has ? number_format($student->pass_count) : '—' }}</span>
                                    <span style="text-align:center;font-size:13px;font-weight:800;color:{{ $has ? '#C24936' : '#8B8AA3' }}">{{ $has ? number_format($student->attempts_count - $student->pass_count) : '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <span style="font-size:12.5px;color:var(--tp-muted)">{{ __('Muat turun bahan tidak direkodkan bagi setiap murid — hanya jumlah bagi setiap fail. Lulus bermaksud :percent% atau lebih.', ['percent' => \App\Models\QuizAttempt::PASS_AT]) }}</span>
                <div>{{ $students->links() }}</div>
            @endif
        </div>

        {{-- ========================= Top students per Tahun ========================= --}}
        <div style="display:flex;flex-direction:column;gap:24px;margin-top:48px">
            <div style="display:flex;flex-direction:column;gap:2px">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)">🌟 {{ __('Murid Terbaik') }}</h2>
                <span style="font-size:13px;color:var(--tp-muted);max-width:640px;line-height:1.5">{{ __('Tiga murid paling aktif dalam setiap Tahun. Aktiviti = video ditonton + percubaan kuiz + kegemaran. Ia mengukur penyertaan, bukan markah — jadi berbeza daripada papan Ranking yang dilihat murid.') }}</span>
            </div>

            @php($availabilityById = \App\Models\Subject::availabilityMap())
            @foreach ($podiums as $podium)
                {{-- Visual order 2nd, 1st, 3rd — winner raised in the middle; ranks with no student are dropped. --}}
                @php($slots = array_values(array_filter([
                    isset($podium->students[1]) ? [2, $podium->students[1]] : null,
                    isset($podium->students[0]) ? [1, $podium->students[0]] : null,
                    isset($podium->students[2]) ? [3, $podium->students[2]] : null,
                ])))
                {{-- This podium's Subjek list only offers the subjects taught in its own Tahun. --}}
                @php($podiumSubjects = $subjects->filter(fn ($s) => in_array($podium->grade->level, $availabilityById[$s->id] ?? [], true))->values())
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;padding:20px 24px;display:flex;flex-direction:column;gap:14px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span style="background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800">{{ $podium->grade->name }}</span>

                        <form method="GET" action="{{ route('admin.murid') }}" style="margin-left:auto">
                            @if ($gradeLevel) <input type="hidden" name="tahun" value="{{ $gradeLevel }}"> @endif
                            @foreach ($podiums as $other)
                                @if ($other->grade->level !== $podium->grade->level && $other->subjectSlug)
                                    <input type="hidden" name="subjek_{{ $other->grade->level }}" value="{{ $other->subjectSlug }}">
                                @endif
                            @endforeach
                            <select name="subjek_{{ $podium->grade->level }}" class="tp-filter-select" style="min-width:170px;min-height:42px;border-radius:11px;font-size:13px" onchange="this.form.submit()">
                                <option value="">{{ __('Semua subjek') }}</option>
                                @foreach ($podiumSubjects as $subject)
                                    <option value="{{ $subject->slug }}" @selected($podium->subjectSlug === $subject->slug)>{{ $subject->displayName() }}</option>
                                @endforeach
                            </select>
                            <noscript><button type="submit" class="tp-btn-ghost">{{ __('Tapis') }}</button></noscript>
                        </form>
                    </div>

                    @if (empty($slots))
                        <span style="font-size:13px;color:var(--tp-muted);text-align:center;padding:14px 0">{{ __('Tiada aktiviti murid untuk Tahun ini lagi.') }}</span>
                    @else
                        <div style="display:flex;align-items:flex-end;justify-content:center;gap:14px;padding:26px 0 0">
                            @foreach ($slots as [$rank, $student])
                                @php($m = $pMeta[$rank])
                                <div style="display:flex;flex-direction:column;align-items:center;gap:6px;width:150px">
                                    <span style="position:relative">
                                        <span style="width:52px;height:52px;border-radius:50%;background:{{ $m['bg'] }};color:{{ $m['fg'] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;border:3px solid {{ $m['ring'] }}">{{ $student->initials() }}</span>
                                        <span style="position:absolute;bottom:-8px;right:-8px;font-size:22px;filter:drop-shadow(0 2px 4px rgba(46,44,80,.25))">{{ $m['medal'] }}</span>
                                    </span>
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink);text-align:center">{{ $student->name }}</span>
                                    <span style="font-size:11.5px;font-weight:700;color:var(--tp-muted);text-align:center">{{ $student->effort }} {{ __('mata aktiviti') }}</span>
                                    <div style="width:100%;height:{{ $m['h'] }}px;border-radius:14px 14px 6px 6px;background:{{ $m['block'] }};display:grid;place-items:center;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:22px;box-shadow:0 6px 16px rgba(46,44,80,.15)">{{ $rank }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-admin-layout>
