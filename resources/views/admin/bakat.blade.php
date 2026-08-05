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

    // The teacher's subject models, in the canonical order, for colour-tinted pills in the table.
    $teacherSubjects = fn ($teacherId) => $subjects->whereIn('id', $subjectsByTeacher[$teacherId] ?? collect())->values();

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
                heading-icon="presentation"
                :sub="__('Gambaran keseluruhan cikgu, penyumbang terbaik dan kandungan paling berjaya')">

    <style>
        /* Subject pills in the teacher list. The "+N" chip reveals the full subject list on hover. */
        .subj-cell { position:relative; display:flex; flex-wrap:wrap; gap:5px; justify-content:center; align-items:center; }
        .subj-pill { display:inline-flex; align-items:center; border-radius:999px; padding:3px 10px; font-size:12px; font-weight:800; line-height:1.2; white-space:nowrap; }
        .subj-more { position:relative; cursor:default; background:var(--tp-line); color:var(--tp-muted-2); }
        /* Teleported to <body>, so positioned fixed at the chip (left/top set inline) and lifted
           above it. z-index over the shell; not clipped by the list card's overflow. */
        .subj-tip {
            position:fixed; transform:translate(-50%, calc(-100% - 10px));
            z-index:9990; display:flex; flex-wrap:wrap; gap:5px; justify-content:center; width:max-content; max-width:240px;
            padding:9px; border-radius:12px; background:var(--tp-surface); border:1px solid var(--tp-line-2);
            box-shadow:0 10px 26px rgba(46,44,80,.16); pointer-events:none;
        }
        .subj-tip::after {
            content:''; position:absolute; top:100%; left:50%; transform:translateX(-50%);
            border:6px solid transparent; border-top-color:var(--tp-surface);
        }
    </style>

    <div style="display:flex;flex-direction:column;gap:48px">

        {{-- Teacher stats, filter and list are grouped so the gaps between them stay tight. --}}
        <div style="display:flex;flex-direction:column;gap:16px">

        {{-- ============================ Teacher stats ============================ --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Cikgu') }}</h2>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:13.5px;font-weight:700;color:var(--tp-muted)"><x-icon name="user" class="h-4 w-4" />{{ __('Jumlah cikgu') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:var(--tp-ink)">{{ number_format($totalTeachers) }}</span>
                </div>
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="font-size:13.5px;font-weight:700;color:#0F7A68">✓ {{ __('Aktif') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:var(--tp-ink)">{{ number_format($activeCount) }}</span>
                </div>
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="font-size:13.5px;font-weight:700;color:var(--tp-muted)">✕ {{ __('Tidak aktif') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:var(--tp-ink)">{{ number_format($inactiveCount) }}</span>
                </div>
            </div>
        </div>

        {{-- Teacher filter — carries the contributor filter through so it is not reset. --}}
        @php
            // The Subjek list follows the chosen Tahun: only subjects offered that year (all when none).
            $availabilityById = \App\Models\Subject::availabilityMap();
            $visibleSubjects = $gradeLevel
                ? $subjects->filter(fn ($s) => in_array($gradeLevel, $availabilityById[$s->id] ?? [], true))->values()
                : $subjects;
        @endphp
        <form method="GET" action="{{ route('admin.bakat') }}" style="display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap">
            @if ($contribSubject) <input type="hidden" name="p_subjek" value="{{ $contribSubject }}"> @endif
            @if ($contribGrade) <input type="hidden" name="p_tahun" value="{{ $contribGrade }}"> @endif

            <div style="display:flex;flex-direction:column;gap:6px;flex:1;min-width:220px">
                <label for="cari-cikgu" style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:var(--tp-muted-2)">{{ __('Cari') }}</label>
                <input id="cari-cikgu" type="search" name="q" value="{{ $search }}"
                       placeholder="{{ __('Nama atau emel cikgu') }}" class="tp-input">
            </div>

            {{-- Order: Tahun before Subjek (brief §1). --}}
            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:var(--tp-muted-2)">{{ __('Tahun') }}</label>
                <select name="tahun" class="tp-filter-select" style="min-width:150px" onchange="this.form.submit()">
                    <option value="">{{ __('Semua tahun') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->level }}" @selected($gradeLevel === $grade->level)>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;color:var(--tp-muted-2)">{{ __('Subjek') }}</label>
                <select name="subjek" class="tp-filter-select" style="min-width:220px" onchange="this.form.submit()">
                    <option value="">{{ __('Semua subjek') }}</option>
                    @foreach ($visibleSubjects as $subject)
                        <option value="{{ $subject->slug }}" @selected($subjectSlug === $subject->slug)>{{ $subject->displayName() }}</option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="tp-btn-ghost">{{ __('Tapis') }}</button></noscript>
            @if ($subjectSlug || $gradeLevel || $search !== '')
                <a href="{{ route('admin.bakat', array_filter(['p_subjek' => $contribSubject, 'p_tahun' => $contribGrade])) }}" class="tp-btn-ghost" style="color:#C24936">{{ __('Kosongkan') }}</a>
            @endif
        </form>

        {{-- Teacher table --}}
        @if ($teachers->isEmpty())
            <div class="tp-empty">
                <x-icon name="user" class="h-8 w-8" style="color:var(--tp-muted)" />
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Tiada cikgu untuk dipaparkan') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Tiada cikgu yang sepadan dengan tapisan ini.') }}</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:8px">
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <div style="overflow-x:auto">
                        <div style="min-width:900px">
                            <div style="display:grid;{{ $tcols }};padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Nama Cikgu') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Subjek') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Video') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Bahan') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Kuiz') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Status') }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Tindakan') }}</span>
                            </div>
                            @foreach ($teachers as $teacher)
                                <div class="tp-tr" style="display:grid;{{ $tcols }};padding:12px 20px;border-bottom:1px solid var(--tp-line)">
                                    <a href="{{ route('admin.bakat.show', $teacher) }}" style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink)">{{ $teacher->name }}</span>
                                        @if ($teacher->email)
                                            <span style="font-size:11.5px;color:var(--tp-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $teacher->email }}</span>
                                        @endif
                                    </a>
                                    @php($tsubs = $teacherSubjects($teacher->id))
                                    <div class="subj-cell" style="justify-self:center">
                                        @forelse ($tsubs->take(2) as $s)
                                            <span class="subj-pill" style="background:rgb({{ $s->rgb }} / .14);color:rgb({{ $s->rgb }})">{{ $s->displayName() }}</span>
                                        @empty
                                            <span style="color:var(--tp-muted-2);font-weight:700">—</span>
                                        @endforelse

                                        @if ($tsubs->count() > 2)
                                            <span class="subj-pill subj-more" tabindex="0" aria-label="{{ $tsubs->map->displayName()->join(', ') }}"
                                                  x-data="{ open: false, x: 0, y: 0 }"
                                                  @mouseenter="const r = $el.getBoundingClientRect(); x = r.left + r.width / 2; y = r.top; open = true"
                                                  @mouseleave="open = false" @focus="const r = $el.getBoundingClientRect(); x = r.left + r.width / 2; y = r.top; open = true" @blur="open = false">
                                                +{{ $tsubs->count() - 2 }}
                                                {{-- Teleported to <body> so the list card's overflow:hidden can't clip it; positioned fixed above the chip. --}}
                                                <template x-teleport="body">
                                                    <span class="subj-tip" x-show="open" x-cloak :style="`left:${x}px;top:${y}px`">
                                                        @foreach ($tsubs as $s)
                                                            <span class="subj-pill" style="background:rgb({{ $s->rgb }} / .14);color:rgb({{ $s->rgb }})">{{ $s->displayName() }}</span>
                                                        @endforeach
                                                    </span>
                                                </template>
                                            </span>
                                        @endif
                                    </div>
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ number_format($teacher->video_count) }}</span>
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ number_format($teacher->material_count) }}</span>
                                    <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ number_format($teacher->quiz_count) }}</span>
                                    @if ($teacher->isActive())
                                        <span style="justify-self:center;background:#DCF2EE;color:#0F7A68;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ __('Aktif') }}</span>
                                    @else
                                        <span style="justify-self:center;background:#F1F0E8;color:var(--tp-muted-2);border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ __('Tidak aktif') }}</span>
                                    @endif
                                    <form method="POST" action="{{ route('admin.guru.status', $teacher) }}" style="justify-self:center;white-space:nowrap">
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
                <span style="font-size:12.5px;color:var(--tp-muted)">{{ __('Nyahaktif hanya menghalang cikgu daripada log masuk. Video, bahan dan kuiz mereka kekal diterbitkan untuk murid.') }}</span>
                <div>{{ $teachers->links() }}</div>
            </div>
        @endif
        </div>{{-- /teacher stats + filter + list group --}}
    </div>
</x-admin-layout>
