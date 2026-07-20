<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="'Bab '.$chapter->number.': '.$chapter->title">
    @php($col = $subject->color ?: '#17907B')
    @php($grad = "linear-gradient(135deg, color-mix(in oklab, {$col} 30%, #fff), color-mix(in oklab, {$col} 12%, #fff))")
    @php($tagBg = "color-mix(in oklab, {$col} 15%, #fff)")
    @php($tagColor = "color-mix(in oklab, {$col} 82%, #000)")

    <div style="display:flex;flex-direction:column;gap:22px">
        <a href="{{ route('belajar.subjek', ['subject' => $subject->slug, 'grade' => $grade->level]) }}" class="wl-back"
           style="align-self:flex-start;display:flex;align-items:center;gap:8px;font-family:'Geist',sans-serif;font-size:14px;font-weight:800;color:var(--wl-muted-2);text-decoration:none;padding:6px 0">← {{ __('Semua bab') }}</a>

        <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:20px 24px;display:flex;flex-direction:column;gap:4px;box-shadow:0 3px 12px rgba(46,44,80,.04)">
            <span style="font-family:'Geist',sans-serif;font-size:13px;font-weight:800;color:#2E6CA8"><x-subject-emoji :subject="$subject" class="text-sm" /> {{ $subject->name }} · {{ $grade->name }}</span>
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:24px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">Bab {{ $chapter->number }}: {{ $chapter->title }}</h2>
        </div>

        {{-- Video pelajaran --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Video pelajaran') }}</h3>
            @if ($lessons->isEmpty())
                <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:44px;display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center">
                    <span style="width:44px;height:44px;border-radius:50%;background:#F1F0E8;display:grid;place-items:center;font-size:18px">🎬</span>
                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ __('Tiada video untuk bab ini lagi') }}</span>
                    <span style="font-size:13.5px;color:var(--wl-muted)">{{ __('Cikgu belum memuat naik sebarang video untuk bab ini.') }}</span>
                </div>
            @else
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                    @foreach ($lessons as $lesson)
                        <a href="{{ route('video.show', $lesson) }}" class="vid-card"
                           style="display:block;text-decoration:none;background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;overflow:hidden;box-shadow:0 4px 16px rgba(46,44,80,.04);cursor:pointer">
                            <div style="height:130px;background:{{ $grad }};display:grid;place-items:center;position:relative">
                                @if ($lesson->thumbnailUrl())
                                    <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                                @endif
                                @if ($watchedIds->contains($lesson->id))
                                    <span style="position:absolute;top:10px;left:10px;background:#17907B;color:#fff;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800;z-index:2">✓ {{ __('Ditonton') }}</span>
                                @endif
                                <span style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.9);display:grid;place-items:center;color:#4276AE;font-size:14px;z-index:1">▶</span>
                                @if ($lesson->durationLabel())
                                    <span style="position:absolute;right:10px;bottom:10px;background:rgba(66,118,174,.85);color:#fff;font-size:11px;font-weight:700;border-radius:999px;padding:3px 9px">{{ $lesson->durationLabel() }}</span>
                                @endif
                            </div>
                            <div style="padding:14px 16px;display:flex;flex-direction:column;gap:4px">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ $lesson->title }}</span>
                                <span style="font-size:12.5px;color:var(--wl-muted)">{{ $lesson->teacher->name }}</span>
                                <span style="font-size:12px;font-weight:700;color:var(--wl-muted)">👁 {{ $lesson->views_count }} {{ __('tontonan') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Bahan sokongan --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Bahan sokongan') }}</h3>
            @if ($materials->isEmpty())
                <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:44px;display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center">
                    <span style="width:44px;height:44px;border-radius:50%;background:#F1F0E8;display:grid;place-items:center;font-size:18px">📄</span>
                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ __('Tiada bahan untuk bab ini lagi') }}</span>
                    <span style="font-size:13.5px;color:var(--wl-muted)">{{ __('Cikgu belum memuat naik slaid, PDF atau lembaran kerja untuk bab ini.') }}</span>
                </div>
            @else
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
                    @foreach ($materials as $material)
                        <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 3px 12px rgba(46,44,80,.04)">
                            <span style="width:38px;height:38px;border-radius:10px;background:#FDE7E0;display:grid;place-items:center;font-size:15px;flex-shrink:0">📄</span>
                            <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--wl-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->title }}</span>
                                <span style="font-size:12px;color:var(--wl-muted)">{{ $material->humanSize() }}</span>
                            </div>
                            <a href="{{ route('muat-turun.bahan', $material) }}" title="{{ __('Muat turun') }}" style="width:38px;height:38px;border-radius:10px;background:#DCF2EE;color:#0F7A68;font-size:15px;display:grid;place-items:center;text-decoration:none;flex-shrink:0">⬇</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Kuiz --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Kuiz') }}</h3>
            @if ($quizzes->isEmpty())
                <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:44px;display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center">
                    <span style="width:44px;height:44px;border-radius:50%;background:#F1F0E8;display:grid;place-items:center;font-size:18px">📝</span>
                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ __('Tiada kuiz untuk bab ini lagi') }}</span>
                    <span style="font-size:13.5px;color:var(--wl-muted)">{{ __('Tonton video dahulu. Kuiz akan muncul di sini apabila cikgu menyediakannya.') }}</span>
                </div>
            @else
                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px">
                    @foreach ($quizzes as $quiz)
                        <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span style="background:{{ $tagBg }};color:{{ $tagColor }};border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800"><x-subject-emoji :subject="$subject" class="text-sm" /> {{ $subject->displayName() }}</span>
                                @if ($quiz->isFile())
                                    <span style="font-size:12px;font-weight:700;color:var(--wl-muted)">{{ __('Kuiz Bercetak') }}</span>
                                @endif
                            </div>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15.5px;color:var(--wl-ink)">{{ $quiz->title }}</span>
                            <div style="display:flex;align-items:center;gap:12px;margin-top:auto">
                                @if ($quiz->my_attempts_count > 0)
                                    <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">{{ __('Dicuba :count kali', ['count' => $quiz->my_attempts_count]) }}</span>
                                @endif
                                <a href="{{ route('kuiz.intro', $quiz) }}" class="wl-btn-primary" style="margin-left:auto;min-height:42px;display:inline-flex;align-items:center;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;text-decoration:none">{{ $quiz->isFile() ? __('Lihat Kuiz') : __('Cuba Kuiz') }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-dynamic-component>
