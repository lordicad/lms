@php($teacher = auth()->user())

<x-cikgu-layout
    :title="__('Utama')"
    :heading="__('Selamat datang, :name', ['name' => 'Cikgu '.$teacher->username])"
    :sub="__('Ringkasan kelas anda pada hari ini, :date', ['date' => now()->translatedFormat('l, j F Y')])">

    {{-- Stat cards --}}
    <div class="tp-stats">
        <a href="{{ route('cikgu.video.index') }}" class="tp-stat" style="text-decoration:none">
            <div style="display:flex;align-items:center;gap:10px">
                <span class="tp-stat-ico" style="background:#E4EEF9">🎬</span>
                <span class="tp-stat-label">{{ __('Video Saya') }}</span>
            </div>
            <span class="tp-stat-value">{{ $lessonCount }}</span>
            <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">{{ __('Jumlah tontonan: :count', ['count' => number_format($viewCount)]) }}</span>
        </a>

        <a href="{{ route('cikgu.bahan.index') }}" class="tp-stat" style="text-decoration:none">
            <div style="display:flex;align-items:center;gap:10px">
                <span class="tp-stat-ico" style="background:#DCF2EE">📄</span>
                <span class="tp-stat-label">{{ __('Bahan Saya') }}</span>
            </div>
            <span class="tp-stat-value">{{ $materialCount }}</span>
            <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">{{ __('Bahan pengajaran') }}</span>
        </a>

        <a href="{{ route('cikgu.kuiz.index') }}" class="tp-stat" style="text-decoration:none">
            <div style="display:flex;align-items:center;gap:10px">
                <span class="tp-stat-ico" style="background:#FEF0CE">📝</span>
                <span class="tp-stat-label">{{ __('Kuiz Saya') }}</span>
            </div>
            <span class="tp-stat-value">{{ $quizCount }}</span>
            <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">{{ __('Fail & interaktif') }}</span>
        </a>
    </div>

    <div style="display:flex;flex-direction:column;gap:20px;min-width:0">
        {{-- Recent videos --}}
        <div class="tp-card" style="overflow:hidden">
            <div style="display:flex;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid rgba(46,44,80,.07)">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:#28293F;flex:1">{{ __('Video Terbaru Saya') }}</h2>
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
                        <a href="{{ route('video.show', $lesson) }}" class="tp-g" style="font-weight:800;font-size:14.5px;color:#28293F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</a>
                        <span style="font-size:12.5px;color:#8B8AA3">{{ $lesson->chapter->subject->name }} · {{ $lesson->chapter->grade->name }} · Bab {{ $lesson->chapter->number }}</span>
                    </div>
                    <span class="tp-meta" style="flex-shrink:0">👁 {{ $lesson->views_count }}</span>
                    <span class="tp-badge {{ $lesson->is_published ? 'tp-badge-ok' : 'tp-badge-draft' }}">{{ $lesson->is_published ? __('Diterbitkan') : __('Draf') }}</span>
                </div>
            @empty
                <div style="padding:28px 22px;text-align:center;color:#8B8AA3;font-size:14px">{{ __('Belum ada video. Muat naik video pertama anda.') }}</div>
            @endforelse
        </div>

        {{-- Recent quizzes --}}
        <div class="tp-card" style="overflow:hidden">
            <div style="display:flex;align-items:center;gap:12px;padding:18px 22px;border-bottom:1px solid rgba(46,44,80,.07)">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:#28293F;flex:1">{{ __('Kuiz Saya') }}</h2>
            </div>

            @forelse ($recentQuizzes as $quiz)
                @php($pct = $totalStudents > 0 ? min(100, round($quiz->taken_students_count / $totalStudents * 100)) : 0)
                <div class="tp-row">
                    <span style="width:40px;height:40px;border-radius:11px;background:{{ $quiz->isInteractive() ? '#DCF2EE' : '#E4EEF9' }};display:grid;place-items:center;font-size:16px;flex-shrink:0">📝</span>
                    <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                        <span class="tp-g" style="font-weight:800;font-size:14.5px;color:#28293F">{{ $quiz->title }}</span>
                        <span style="font-size:12.5px;color:#8B8AA3">{{ $quiz->chapter->subject->name }} · Bab {{ $quiz->chapter->number }} · {{ $quiz->isInteractive() ? __('Interaktif') : __('Bercetak') }}</span>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;flex-shrink:0">
                        <span class="tp-meta">{{ __(':taken/:total murid', ['taken' => $quiz->taken_students_count, 'total' => $totalStudents]) }}</span>
                        <div style="width:120px;height:7px;border-radius:999px;background:rgba(46,44,80,.08);overflow:hidden">
                            <div style="height:100%;border-radius:999px;background:#17907B;width:{{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
            @empty
                <div style="padding:28px 22px;text-align:center;color:#8B8AA3;font-size:14px">{{ __('Belum ada kuiz. Cipta kuiz pertama anda.') }}</div>
            @endforelse
        </div>
    </div>
</x-cikgu-layout>
