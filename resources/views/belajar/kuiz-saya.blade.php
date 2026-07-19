<x-student-layout :title="__('Kuiz')">
    <div style="display:flex;flex-direction:column;gap:22px">
        <div style="display:flex;align-items:baseline;gap:12px;flex-wrap:wrap">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#28293F">{{ __('Kuiz') }}</h2>
            <span style="font-size:14px;color:#8B8AA3">{{ $grade?->name ?? __('Tahun anda belum ditetapkan') }}</span>
        </div>

        @if ($quizzes->isEmpty())
            <div style="background:#fff;border:1px dashed rgba(46,44,80,.2);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center">
                <span style="font-size:32px">📝</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:#28293F">{{ __('Belum ada kuiz') }}</h3>
                <p style="margin:0;font-size:14.5px;color:#8B8AA3;max-width:360px">{{ __('Belum ada kuiz untuk Tahun anda. Sila semak semula kemudian.') }}</p>
            </div>
        @else
            @php($done = $quizzes->filter(fn ($q) => $rankedAttempts->has($q->id)))
            @php($recommended = $quizzes->reject(fn ($q) => $rankedAttempts->has($q->id)))

            {{-- Stats strip --}}
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                <div style="background:#DCF2EE;border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:14px">
                    <span style="width:44px;height:44px;border-radius:14px;background:#fff;display:grid;place-items:center;font-size:19px">✅</span>
                    <div style="display:flex;flex-direction:column">
                        <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#0F7A68">{{ $doneCount }}</span>
                        <span style="font-size:12.5px;font-weight:700;color:#0F7A68">{{ __('Kuiz selesai') }}</span>
                    </div>
                </div>
                <div style="background:#FEF0CE;border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:14px">
                    <span style="width:44px;height:44px;border-radius:14px;background:#fff;display:grid;place-items:center;font-size:19px">⭐</span>
                    <div style="display:flex;flex-direction:column">
                        <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#8A6A12">{{ $avgScore !== null ? $avgScore.'%' : '—' }}</span>
                        <span style="font-size:12.5px;font-weight:700;color:#8A6A12">{{ __('Purata markah') }}</span>
                    </div>
                </div>
                <div style="background:#E4EEF9;border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:14px">
                    <span style="width:44px;height:44px;border-radius:14px;background:#fff;display:grid;place-items:center;font-size:19px">🏆</span>
                    <div style="display:flex;flex-direction:column">
                        <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#2E6CA8">{{ $rank ? '#'.$rank : '—' }}</span>
                        <span style="font-size:12.5px;font-weight:700;color:#2E6CA8">{{ __('Ranking') }}</span>
                    </div>
                </div>
            </div>

            {{-- Telah Selesai --}}
            @if ($done->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:12px">
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:#28293F">{{ __('Telah Selesai') }}</h3>
                    <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;overflow:hidden;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                        @foreach ($done as $quiz)
                            @php($attempt = $rankedAttempts[$quiz->id])
                            @php($pct = $attempt->percentage())
                            @php($sc = $pct >= 80 ? '#17907B' : ($pct >= 50 ? '#E3A31C' : '#EB5E5A'))
                            @php($sub = $quiz->chapter->subject)
                            @php($tagBg = 'color-mix(in oklab, '.($sub->color ?: '#17907B').' 15%, #fff)')
                            <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid rgba(46,44,80,.06)">
                                <span style="width:40px;height:40px;border-radius:12px;background:{{ $tagBg }};display:grid;place-items:center;font-size:16px;flex-shrink:0"><x-subject-emoji :subject="$sub" class="text-base" /></span>
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:#28293F">{{ $quiz->title }}</span>
                                    <span style="font-size:12px;color:#8B8AA3">{{ $sub->displayName() }} · Bab {{ $quiz->chapter->number }} · {{ $attempt->completed_at?->translatedFormat('d M') }}</span>
                                </div>
                                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:{{ $sc }}">{{ $pct }}%</span>
                                    <div style="width:110px;height:6px;border-radius:999px;background:rgba(46,44,80,.08);overflow:hidden">
                                        <div style="height:100%;border-radius:999px;background:{{ $sc }};width:{{ $pct }}%"></div>
                                    </div>
                                </div>
                                <a href="{{ route('keputusan.show', $attempt) }}" class="wl-btn-secondary" style="min-height:38px;display:inline-flex;align-items:center;border-radius:10px;border:1.5px solid rgba(46,44,80,.12);background:#fff;color:#28293F;font-family:'Geist',sans-serif;font-weight:700;font-size:12.5px;padding:0 14px;text-decoration:none">{{ __('Semak') }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Kuiz Dicadangkan --}}
            @if ($recommended->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:12px">
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:#28293F">{{ __('Kuiz Dicadangkan') }}</h3>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                        @foreach ($recommended as $quiz)
                            @php($sub = $quiz->chapter->subject)
                            @php($col = $sub->color ?: '#17907B')
                            @php($tagBg = "color-mix(in oklab, {$col} 15%, #fff)")
                            @php($tagColor = "color-mix(in oklab, {$col} 82%, #000)")
                            <div class="wl-lift" style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:18px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                    <span style="background:{{ $tagBg }};color:{{ $tagColor }};border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800"><x-subject-emoji :subject="$sub" class="text-sm" /> {{ $sub->displayName() }}</span>
                                    <span style="font-size:12px;font-weight:700;color:#8B8AA3">Bab {{ $quiz->chapter->number }}</span>
                                </div>
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15.5px;color:#28293F">{{ $quiz->title }}</span>
                                <div style="display:flex;align-items:center;gap:12px;margin-top:auto">
                                    <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">@if ($quiz->isInteractive()){{ $quiz->questions_count }} {{ __('soalan') }}@if ($quiz->duration_minutes) · {{ $quiz->duration_minutes }} minit @endif @else {{ __('Kuiz Bercetak') }} @endif</span>
                                    <a href="{{ route('kuiz.intro', $quiz) }}" class="wl-btn-primary" style="margin-left:auto;min-height:42px;display:inline-flex;align-items:center;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;text-decoration:none">{{ $quiz->isFile() ? __('Lihat') : __('Mula Kuiz') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</x-student-layout>
