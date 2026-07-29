<x-student-layout :title="__('Kuiz')">
    <style>
        /* Achievements: milestone badges spread evenly across the row, wrapping on narrow screens. */
        .kz-ach { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 22px 8px; justify-items: center; }
        /* Completed (wide) beside Suggested Quiz (narrow); stacked on small screens. */
        .kz-cols { display: grid; grid-template-columns: minmax(0, 1.75fr) minmax(0, 1fr); gap: 20px; align-items: start; }
        @media (max-width: 900px) { .kz-cols { grid-template-columns: 1fr; } }
    </style>

    <div style="display:flex;flex-direction:column;gap:22px">
        <div style="display:flex;align-items:baseline;gap:12px;flex-wrap:wrap">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:var(--wl-ink)">{{ __('Kuiz') }}</h2>
            <span style="font-size:14px;color:var(--wl-muted)">{{ $grade?->name ?? __('Tahun anda belum ditetapkan') }}</span>
        </div>

        @if ($quizzes->isEmpty())
            <div style="background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center">
                <span style="font-size:32px">📝</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--wl-ink)">{{ __('Belum ada kuiz') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--wl-muted);max-width:360px">{{ __('Belum ada kuiz untuk Tahun anda. Sila semak semula kemudian.') }}</p>
            </div>
        @else
            @php($done = $quizzes->filter(fn ($q) => $rankedAttempts->has($q->id)))
            @php($recommended = $quizzes->reject(fn ($q) => $rankedAttempts->has($q->id)))
            @php($suggested = $recommended->first())

            {{-- Stats strip --}}
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                <div style="background:#DCF2EE;border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:14px">
                    <span style="width:44px;height:44px;border-radius:14px;background:var(--wl-surface);color:#0F7A68;display:grid;place-items:center"><x-icon name="check-circle" style="width:22px;height:22px" /></span>
                    <div style="display:flex;flex-direction:column">
                        <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#0F7A68">{{ $doneCount }}</span>
                        <span style="font-size:12.5px;font-weight:700;color:#0F7A68">{{ __('Kuiz selesai') }}</span>
                    </div>
                </div>
                <div style="background:#FEF0CE;border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:14px">
                    <span style="width:44px;height:44px;border-radius:14px;background:var(--wl-surface);color:#8A6A12;display:grid;place-items:center"><x-icon name="star" style="width:22px;height:22px" /></span>
                    <div style="display:flex;flex-direction:column">
                        <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#8A6A12">{{ $avgScore !== null ? $avgScore.'%' : '—' }}</span>
                        <span style="font-size:12.5px;font-weight:700;color:#8A6A12">{{ __('Purata markah') }}</span>
                    </div>
                </div>
                <div style="background:#E4EEF9;border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:14px">
                    <span style="width:44px;height:44px;border-radius:14px;background:var(--wl-surface);color:#2E6CA8;display:grid;place-items:center"><x-icon name="trophy" style="width:22px;height:22px" /></span>
                    <div style="display:flex;flex-direction:column">
                        <span style="font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:#2E6CA8">{{ $rank ? '#'.$rank : '—' }}</span>
                        <span style="font-size:12.5px;font-weight:700;color:#2E6CA8">{{ __('Ranking') }}</span>
                    </div>
                </div>
            </div>

            {{-- Achievements: perfect-score milestone badges, evenly spread. --}}
            <div style="display:flex;flex-direction:column;gap:12px">
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="width:34px;height:34px;border-radius:10px;background:#FEF0CE;color:#E0A21C;display:grid;place-items:center;flex-shrink:0"><x-icon name="trophy" style="width:19px;height:19px" /></span>
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Lencana Pencapaian') }}</h3>
                    <a href="{{ route('profile.edit') }}" style="margin-left:auto;display:inline-flex;align-items:center;gap:4px;font-family:'Geist',sans-serif;font-size:13px;font-weight:700;color:var(--wl-muted);text-decoration:none">{{ __('Lihat semua') }}<x-icon name="arrow-right" style="width:15px;height:15px" /></a>
                </div>
                <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:24px 20px;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                    <div class="kz-ach">
                        @foreach (\App\Support\QuizBadges::milestones() as $key => $threshold)
                            <x-quiz-badge :badge="$key" :muted="$perfectCount < $threshold" />
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Completed (left) beside Suggested Quiz (right). --}}
            <div class="kz-cols">
                {{-- Completed --}}
                <div style="display:flex;flex-direction:column;gap:12px;min-width:0">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="width:30px;height:30px;border-radius:9px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="book" style="width:17px;height:17px" /></span>
                        <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Telah Selesai') }}</h3>
                    </div>
                    <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;overflow:hidden;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                        @if ($done->isNotEmpty())
                            @foreach ($done as $quiz)
                                @php($attempt = $rankedAttempts[$quiz->id])
                                @php($pct = $attempt->percentage())
                                @php($sc = $pct >= 80 ? '#17907B' : ($pct >= 50 ? '#E3A31C' : '#EB5E5A'))
                                @php($sub = $quiz->chapter->subject)
                                @php($tagBg = 'color-mix(in oklab, '.($sub->color ?: '#17907B').' 15%, #fff)')
                                @php($tagColor = 'color-mix(in oklab, '.($sub->color ?: '#17907B').' 82%, #000)')
                                <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--wl-line)">
                                    <span style="width:40px;height:40px;border-radius:12px;background:{{ $tagBg }};display:grid;place-items:center;flex-shrink:0"><x-subject-emoji :subject="$sub" class="text-base" /></span>
                                    <div style="display:flex;flex-direction:column;gap:6px;min-width:0;flex:1">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--wl-ink)">{{ $quiz->title }}</span>
                                        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                                            <span style="background:{{ $tagBg }};color:{{ $tagColor }};border-radius:999px;padding:3px 11px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ $sub->displayName() }}</span>
                                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;font-weight:700;color:var(--wl-muted)"><x-icon name="book" style="width:15px;height:15px;color:#0F7A68" />Bab {{ $quiz->chapter->number }}</span>
                                            @if ($attempt->completed_at)
                                                <span style="display:inline-flex;align-items:center;gap:5px;font-size:12.5px;font-weight:700;color:var(--wl-muted)"><x-icon name="clock" style="width:15px;height:15px;color:#0F7A68" />{{ $attempt->completed_at->translatedFormat('d M Y') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:{{ $sc }}">{{ $pct }}%</span>
                                        <div style="width:90px;height:6px;border-radius:999px;background:var(--wl-line);overflow:hidden">
                                            <div style="height:100%;border-radius:999px;background:{{ $sc }};width:{{ $pct }}%"></div>
                                        </div>
                                    </div>
                                    <a href="{{ route('keputusan.show', $attempt) }}" class="wl-btn-secondary" style="min-height:38px;display:inline-flex;align-items:center;border-radius:10px;border:1.5px solid var(--wl-line-2);background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:700;font-size:12.5px;padding:0 14px;text-decoration:none">{{ __('Semak') }}</a>
                                </div>
                            @endforeach
                            <div style="padding:14px;display:flex;justify-content:center">
                                <a href="{{ route('subjek.index') }}" class="wl-btn-secondary" style="min-height:40px;display:inline-flex;align-items:center;gap:8px;border-radius:12px;border:1.5px solid var(--wl-line-2);background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:700;font-size:13px;padding:0 18px;text-decoration:none"><x-icon name="dashboard" style="width:16px;height:16px" />{{ __('Lihat semua kuiz') }}</a>
                            </div>
                        @else
                            <div style="padding:44px;text-align:center;color:var(--wl-muted);font-size:14px;font-weight:600">{{ __('Anda belum menyelesaikan sebarang kuiz.') }}</div>
                        @endif
                    </div>
                </div>

                {{-- Suggested Quiz --}}
                <div style="display:flex;flex-direction:column;gap:12px;min-width:0">
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="width:30px;height:30px;border-radius:9px;background:#FEF0CE;color:#8A6A12;display:grid;place-items:center;flex-shrink:0"><x-icon name="bulb" style="width:17px;height:17px" /></span>
                        <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Kuiz Dicadangkan') }}</h3>
                    </div>
                    @if ($suggested)
                        @php($sub = $suggested->chapter->subject)
                        @php($col = $sub->color ?: '#17907B')
                        @php($tagBg = "color-mix(in oklab, {$col} 15%, #fff)")
                        @php($tagColor = "color-mix(in oklab, {$col} 82%, #000)")
                        <div class="wl-lift" style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;overflow:hidden;box-shadow:0 4px 16px rgba(46,44,80,.04);display:flex;flex-direction:column">
                            {{-- Decorative banner. --}}
                            <div style="position:relative;height:120px;background:linear-gradient(160deg,#DCF2EE,#EAF6F1);display:grid;place-items:center;overflow:hidden">
                                <span style="color:#17907B"><x-icon name="book" style="width:44px;height:44px" /></span>
                                <span style="position:absolute;top:18px;right:22px;color:#8FCFBE"><x-icon name="clock" style="width:26px;height:26px" /></span>
                                <svg style="position:absolute;bottom:-6px;left:14px;opacity:.6" width="16" height="16" viewBox="0 0 16 16" fill="#8FCFBE"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>
                            </div>
                            <div style="padding:18px 20px;display:flex;flex-direction:column;gap:10px">
                                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                    <span style="background:{{ $tagBg }};color:{{ $tagColor }};border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ $sub->displayName() }}</span>
                                    <span style="font-size:12px;font-weight:700;color:var(--wl-muted)">Bab {{ $suggested->chapter->number }}</span>
                                </div>
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:16px;color:var(--wl-ink)">{{ $suggested->title }}</span>
                                <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">@if ($suggested->isInteractive()){{ $suggested->questions_count }} {{ __('soalan') }}@if ($suggested->duration_minutes) · {{ $suggested->duration_minutes }} {{ __('minit') }}@endif @else {{ __('Kuiz Bercetak') }} @endif</span>
                                <a href="{{ route('kuiz.intro', ['quiz' => $suggested, 'from' => 'quizzes']) }}" class="wl-btn-primary" style="margin-top:6px;width:100%;min-height:46px;display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;text-decoration:none">{{ $suggested->isFile() ? __('Lihat Kuiz') : __('Mula Kuiz') }}<x-icon name="arrow-right" style="width:16px;height:16px" /></a>
                            </div>
                        </div>
                    @else
                        <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:36px 24px;text-align:center;box-shadow:0 4px 16px rgba(46,44,80,.04);display:flex;flex-direction:column;align-items:center;gap:8px">
                            <span style="color:#17907B"><x-icon name="check-circle" style="width:34px;height:34px" /></span>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ __('Semua kuiz selesai!') }}</span>
                            <span style="font-size:13px;color:var(--wl-muted)">{{ __('Anda telah mencuba semua kuiz Tahun ini.') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-student-layout>
