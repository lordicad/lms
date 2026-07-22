@php($teacher = auth()->user())

<x-cikgu-layout
    :title="__('Utama')"
    :heading="__('Selamat datang, :name', ['name' => 'Cikgu '.$teacher->username])"
    :sub="__('Ringkasan kelas anda pada hari ini, :date', ['date' => now()->translatedFormat('l, j F Y')])">

    {{-- Content leaderboards, first on the page: which of a teacher's own videos, materials and
         quizzes are actually being used is the thing they open this page to find out. --}}
    {{-- Two columns, not auto-fit: four cards across a wide screen left the fourth stranded on a
         row of its own, so the pairing is fixed to give an even 2x2 block. --}}
    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;align-items:start">
        @foreach ($lists as $list)
            <div class="tp-card" style="overflow:hidden">
                <div style="padding:18px 22px;border-bottom:1px solid var(--tp-line);display:flex;flex-direction:column;gap:2px">
                    <h2 class="tp-g" style="font-size:16px;font-weight:800;color:var(--tp-ink)">{{ $list['icon'] }} {{ $list['title'] }}</h2>
                    <span style="font-size:12.5px;color:var(--tp-muted)">{{ $list['sub'] }}</span>
                </div>

                @forelse ($list['items'] as $i => $item)
                    <div style="display:flex;align-items:center;gap:14px;padding:13px 22px;border-bottom:1px solid var(--tp-line)">
                        <span style="font-size:14px;width:22px;text-align:center;flex-shrink:0">{{ ['🥇', '🥈', '🥉'][$i] ?? $i + 1 }}</span>
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

    {{-- Pass/fail across every completed attempt on this teacher's quizzes. --}}
    <div class="tp-card" style="padding:22px">
        <h2 class="tp-g" style="font-size:16px;font-weight:800;color:var(--tp-ink);margin-bottom:14px">📝 {{ __('Lulus / Gagal Kuiz') }}</h2>

        @if ($passFail['total'] === 0)
            <p style="text-align:center;color:var(--tp-muted);padding:30px 0;font-weight:700">{{ __('Belum ada percubaan kuiz selesai lagi.') }}</p>
        @else
            <div style="display:flex;flex-wrap:wrap;gap:28px;align-items:center">
                <div style="flex:0 1 300px;min-width:240px">
                    <x-chart :config="$passFailConfig" :height="240" :title="__('Lulus lawan gagal')" :table="false"
                        :rows="[['label' => __('Lulus'), 'value' => $passFail['passed']], ['label' => __('Gagal'), 'value' => $passFail['failed']]]" />
                </div>
                <div style="display:flex;flex-direction:column;gap:14px;flex:1;min-width:200px">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span style="width:14px;height:14px;border-radius:4px;background:#0F7A68;flex-shrink:0"></span>
                        <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink)">{{ __('Lulus') }}</span>
                        <span class="tp-g" style="margin-left:auto;font-weight:800;color:#0F7A68">{{ number_format($passFail['passed']) }} <span style="color:var(--tp-muted);font-weight:700">({{ round($passFail['passed'] / max(1, $passFail['total']) * 100) }}%)</span></span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px">
                        <span style="width:14px;height:14px;border-radius:4px;background:#C24936;flex-shrink:0"></span>
                        <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink)">{{ __('Gagal') }}</span>
                        <span class="tp-g" style="margin-left:auto;font-weight:800;color:#C24936">{{ number_format($passFail['failed']) }} <span style="color:var(--tp-muted);font-weight:700">({{ round($passFail['failed'] / max(1, $passFail['total']) * 100) }}%)</span></span>
                    </div>
                    <div style="border-top:1px solid var(--tp-line);padding-top:12px;display:flex;align-items:center;gap:12px">
                        <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink)">{{ __('Jumlah percubaan selesai') }}</span>
                        <span class="tp-g" style="margin-left:auto;font-weight:800;color:var(--tp-ink)">{{ number_format($passFail['total']) }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div style="display:flex;flex-direction:column;gap:20px;min-width:0">
        {{-- Recent videos --}}
        <div class="tp-card" style="overflow:hidden">
            <div style="display:flex;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid var(--tp-line)">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink);flex:1">{{ __('Video Terbaru Saya') }}</h2>
            </div>

            @forelse ($recentLessons as $lesson)
                <div class="tp-row">
                    <span style="width:64px;height:42px;border-radius:9px;overflow:hidden;background:#E4EEF9;display:grid;place-items:center;color:rgba(66,118,174,.8);font-size:12px;flex-shrink:0">
                        @if ($lesson->thumbnailUrl())
                            <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                        @else
                            ▶
                        @endif
                    </span>
                    <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                        <a href="{{ route('video.show', $lesson) }}" class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</a>
                        <span style="font-size:12.5px;color:var(--tp-muted)">{{ $lesson->chapter->subject->name }} · {{ $lesson->chapter->grade->name }} · Bab {{ $lesson->chapter->number }}</span>
                    </div>
                    <span class="tp-meta" style="flex-shrink:0">👁 {{ $lesson->views_count }}</span>
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
            </div>

            @forelse ($recentQuizzes as $quiz)
                @php($pct = $totalStudents > 0 ? min(100, round($quiz->taken_students_count / $totalStudents * 100)) : 0)
                <div class="tp-row">
                    <span style="width:40px;height:40px;border-radius:11px;background:{{ $quiz->isInteractive() ? '#DCF2EE' : '#E4EEF9' }};display:grid;place-items:center;font-size:16px;flex-shrink:0">📝</span>
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

    {{-- Engagement summary (same figures as the Talent page). --}}
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


</x-cikgu-layout>
