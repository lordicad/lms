@php($teacher = auth()->user())

<x-cikgu-layout
    :title="__('Utama')"
    :heading="__('Selamat datang, :name', ['name' => 'Cikgu '.$teacher->username])"
    :sub="__('Ringkasan kelas anda pada hari ini, :date', ['date' => now()->translatedFormat('l, j F Y')])">

    <style>
        /* The work on the left, the read-only panels on the right. */
        .dash-grid { display:grid; grid-template-columns:minmax(0,2.1fr) minmax(0,1fr); gap:20px; align-items:start; }
        .dash-col  { display:flex; flex-direction:column; gap:20px; min-width:0; }
        .dash-kpis { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
        .dash-pair { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; align-items:start; }
        @media (max-width: 1180px) {
            .dash-grid { grid-template-columns:1fr; }
        }
        @media (max-width: 900px) {
            .dash-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .dash-pair { grid-template-columns:1fr; }
        }
    </style>

    {{-- The one action a teacher opens this page to take. --}}
    <div style="display:flex;justify-content:flex-end;margin-top:-8px">
        <a href="{{ route('cikgu.video.create') }}" class="tp-btn" style="min-height:46px">
            <x-icon name="plus" class="h-4 w-4" />{{ __('Video Baru') }}
        </a>
    </div>

    {{-- Engagement summary. The "this week" line only appears where something actually records a
         date — materials keep a running count and nothing more, so that card shows the total alone
         rather than a figure nobody could stand behind. --}}
    <div class="dash-kpis">
        @foreach ($summary as $s)
            <div class="tp-stat">
                <div style="display:flex;align-items:center;gap:10px">
                    <span class="tp-stat-ico" style="background:{{ $s['tint'] }};color:{{ $s['ink'] }}">
                        <x-icon :name="$s['icon']" class="h-[18px] w-[18px]" />
                    </span>
                    <span class="tp-stat-label">{{ $s['label'] }}</span>
                </div>
                <span class="tp-stat-value">{{ $s['value'] }}</span>
                @if ($s['trend'])
                    <span style="display:flex;align-items:center;gap:5px;font-size:12px;font-weight:800;color:#0F7A68">
                        <x-icon name="arrow-right" class="h-3 w-3" style="transform:rotate(-45deg)" />
                        {{ __(':count minggu ini', ['count' => number_format($s['trend']['count'])]) }}
                    </span>
                @endif
            </div>
        @endforeach
    </div>

    <div class="dash-grid">
        <div class="dash-col">
            {{-- Content leaderboards: which of a teacher's own videos, materials and quizzes are
                 actually being used is the thing they open this page to find out. --}}
            <div class="dash-pair">
                @foreach ($lists as $index => $list)
                    @if ($index < 2)
                        @include('cikgu.partials.dashboard-list', ['list' => $list])
                    @endif
                @endforeach
            </div>

            @include('cikgu.partials.dashboard-list', ['list' => $lists[2]])

            {{-- Recent videos --}}
            <div class="tp-card" style="overflow:hidden">
                <div style="display:flex;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid var(--tp-line)">
                    <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink);flex:1">{{ __('Video Terbaru Saya') }}</h2>
                    <a href="{{ route('cikgu.video.index') }}" class="tp-g" style="font-size:13px;font-weight:800">{{ __('Lihat semua') }}</a>
                </div>

                @forelse ($recentLessons as $lesson)
                    <div class="tp-row">
                        <span style="width:64px;height:42px;border-radius:9px;overflow:hidden;background:#E4EEF9;display:grid;place-items:center;color:rgba(66,118,174,.8);flex-shrink:0">
                            @if ($lesson->thumbnailUrl())
                                <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                            @else
                                <x-icon name="play" class="h-4 w-4" />
                            @endif
                        </span>
                        <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                            <a href="{{ route('video.show', $lesson) }}" class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</a>
                            <span style="font-size:12.5px;color:var(--tp-muted)">{{ $lesson->chapter->subject->name }} · {{ $lesson->chapter->grade->name }} · Bab {{ $lesson->chapter->number }}</span>
                        </div>
                        <span class="tp-meta" style="flex-shrink:0">👁 {{ $lesson->views_count }}</span>
                        <span class="tp-meta" style="flex-shrink:0">{{ $lesson->updated_at->translatedFormat('j M Y') }}</span>
                        <span class="tp-badge {{ $lesson->is_published ? 'tp-badge-ok' : 'tp-badge-draft' }}">{{ $lesson->is_published ? __('Diterbitkan') : __('Draf') }}</span>
                    </div>
                @empty
                    <div style="padding:28px 22px;text-align:center;color:var(--tp-muted);font-size:14px">{{ __('Belum ada video. Muat naik video pertama anda.') }}</div>
                @endforelse
            </div>

            {{-- Recent quizzes --}}
            <div class="tp-card" style="overflow:hidden">
                <div style="display:flex;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid var(--tp-line)">
                    <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink);flex:1">{{ __('Kuiz Saya') }}</h2>
                    <a href="{{ route('cikgu.kuiz.index') }}" class="tp-g" style="font-size:13px;font-weight:800">{{ __('Lihat semua') }}</a>
                </div>

                @forelse ($recentQuizzes as $quiz)
                    @php($pct = $totalStudents > 0 ? min(100, round($quiz->taken_students_count / $totalStudents * 100)) : 0)
                    <div class="tp-row">
                        <span style="width:40px;height:40px;border-radius:11px;background:{{ $quiz->isInteractive() ? '#DCF2EE' : '#E4EEF9' }};display:grid;place-items:center;color:{{ $quiz->isInteractive() ? '#0F7A68' : '#2E6CA8' }};flex-shrink:0">
                            <x-icon name="quiz" class="h-[18px] w-[18px]" />
                        </span>
                        <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                            <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ $quiz->title }}</span>
                            <span style="font-size:12.5px;color:var(--tp-muted)">{{ $quiz->chapter->subject->name }} · Bab {{ $quiz->chapter->number }} · {{ $quiz->isInteractive() ? __('Interaktif') : __('Bercetak') }}</span>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0">
                            <span class="tp-meta">{{ __(':taken/:total murid', ['taken' => $quiz->taken_students_count, 'total' => $totalStudents]) }}</span>
                            <div style="width:120px;height:7px;border-radius:999px;background:var(--tp-line);overflow:hidden">
                                <div style="height:100%;border-radius:999px;background:#17907B;width:{{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div style="padding:28px 22px;text-align:center;color:var(--tp-muted);font-size:14px">{{ __('Belum ada kuiz. Cipta kuiz pertama anda.') }}</div>
                @endforelse
            </div>
        </div>

        {{-- Side column --}}
        <div class="dash-col">
            @include('cikgu.partials.dashboard-list', ['list' => $lists[3]])

            {{-- Pass/fail across every completed attempt on this teacher's quizzes. --}}
            <div class="tp-card" style="padding:22px">
                <h2 class="tp-g" style="font-size:16px;font-weight:800;color:var(--tp-ink);margin-bottom:14px">{{ __('Lulus / Gagal Kuiz') }}</h2>

                @if ($passFail['total'] === 0)
                    <p style="text-align:center;color:var(--tp-muted);padding:24px 0;font-weight:700">{{ __('Belum ada percubaan kuiz selesai lagi.') }}</p>
                @else
                    <div style="display:flex;flex-direction:column;gap:16px">
                        <x-chart :config="$passFailConfig" :height="200" :title="__('Lulus lawan gagal')" :table="false"
                            :rows="[['label' => __('Lulus'), 'value' => $passFail['passed']], ['label' => __('Gagal'), 'value' => $passFail['failed']]]" />

                        <div style="display:flex;flex-direction:column;gap:12px">
                            <div style="display:flex;align-items:center;gap:10px">
                                <span style="width:12px;height:12px;border-radius:4px;background:#0F7A68;flex-shrink:0"></span>
                                <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink)">{{ __('Lulus') }}</span>
                                <span class="tp-g" style="margin-left:auto;font-weight:800;font-size:14px;color:#0F7A68">{{ number_format($passFail['passed']) }} <span style="color:var(--tp-muted);font-weight:700">({{ round($passFail['passed'] / max(1, $passFail['total']) * 100) }}%)</span></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px">
                                <span style="width:12px;height:12px;border-radius:4px;background:#C24936;flex-shrink:0"></span>
                                <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink)">{{ __('Gagal') }}</span>
                                <span class="tp-g" style="margin-left:auto;font-weight:800;font-size:14px;color:#C24936">{{ number_format($passFail['failed']) }} <span style="color:var(--tp-muted);font-weight:700">({{ round($passFail['failed'] / max(1, $passFail['total']) * 100) }}%)</span></span>
                            </div>
                            <div style="border-top:1px solid var(--tp-line);padding-top:12px;display:flex;align-items:center;gap:10px">
                                <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink)">{{ __('Jumlah percubaan selesai') }}</span>
                                <span class="tp-g" style="margin-left:auto;font-weight:800;font-size:14px;color:var(--tp-ink)">{{ number_format($passFail['total']) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Quick actions: the four things a teacher creates, each already a page of its own. --}}
            <div class="tp-card" style="overflow:hidden">
                <div style="padding:18px 22px;border-bottom:1px solid var(--tp-line)">
                    <h2 class="tp-g" style="font-size:16px;font-weight:800;color:var(--tp-ink)">{{ __('Tindakan Pantas') }}</h2>
                </div>
                @foreach ([
                    ['route' => 'cikgu.video.create', 'icon' => 'upload', 'label' => __('Muat Naik Video')],
                    ['route' => 'cikgu.bahan.create', 'icon' => 'file', 'label' => __('Muat Naik Bahan')],
                    ['route' => 'cikgu.kuiz.create', 'icon' => 'quiz', 'label' => __('Cipta Kuiz')],
                    ['route' => 'cikgu.bab.index', 'icon' => 'book', 'label' => __('Lihat Bab')],
                ] as $action)
                    <a href="{{ route($action['route']) }}"
                       style="display:flex;align-items:center;gap:12px;padding:13px 22px;border-bottom:1px solid var(--tp-line);text-decoration:none;color:var(--tp-ink)">
                        <x-icon :name="$action['icon']" class="h-[18px] w-[18px]" style="color:var(--tp-muted-2)" />
                        <span class="tp-g" style="font-weight:800;font-size:14px;flex:1">{{ $action['label'] }}</span>
                        <x-icon name="chevron-right" class="h-4 w-4" style="color:var(--tp-muted)" />
                    </a>
                @endforeach
            </div>
        </div>
    </div>

</x-cikgu-layout>
