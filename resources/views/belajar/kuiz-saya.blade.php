<x-student-layout :title="__('Kuiz')">
    <style>
        /* Achievements: milestone badges spread evenly across the row, wrapping on narrow screens. */
        .kz-ach { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 22px 8px; justify-items: center; }
        /* Completed (wide) beside Suggested Quiz (narrow); stacked on small screens. */
        .kz-cols { display: grid; grid-template-columns: minmax(0, 1.75fr) minmax(0, 1fr); gap: 20px; align-items: start; }
        @media (max-width: 900px) { .kz-cols { grid-template-columns: 1fr; } }
    </style>

    <div style="display:flex;flex-direction:column;gap:24px">
        <div style="display:flex;align-items:flex-start;gap:16px">
            <span class="hi-tile" style="width:48px;height:48px;border-radius:14px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="quiz" style="width:24px;height:24px" /></span>
            <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ __('Kuiz') }}</h2>
                <span style="font-size:14px;font-weight:600;color:var(--wl-muted)">{{ $grade?->name ?? __('Tahun anda belum ditetapkan') }}</span>
            </div>
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

            {{-- Stats strip: left-accent gradient cards with an icon disc. Same design in both themes;
                 dark mode only swaps the colours (bg, text, disc, decorations). --}}
            @php($statsDark = ($theme ?? 'light') === 'dark')
            @php($statCards = $statsDark ? [
                ['accent' => '#2DD4BF', 'bg' => 'linear-gradient(135deg,#234A3F,#1A3A31)', 'ink' => '#EAF7F2', 'spark' => '#3A6B5C', 'disc' => '#26382F', 'type' => 'check', 'value' => $doneCount, 'label' => __('Kuiz selesai')],
                ['accent' => '#F0B733', 'bg' => 'linear-gradient(135deg,#443A22,#352C18)', 'ink' => '#F6EBCF', 'spark' => '#6A5B30', 'disc' => '#3A3220', 'type' => 'star', 'value' => $avgScore !== null ? $avgScore.'%' : '—', 'label' => __('Purata markah')],
                ['accent' => '#5A9BE0', 'bg' => 'linear-gradient(135deg,#2A3E56,#20304A)', 'ink' => '#E2ECF8', 'spark' => '#436286', 'disc' => '#293A50', 'type' => 'trophy', 'value' => $rank ? '#'.$rank : '—', 'label' => __('Ranking'), 'leaf' => true],
            ] : [
                ['accent' => '#17907B', 'bg' => 'linear-gradient(135deg,#EAF7F2,#D6EFE7)', 'ink' => '#0F7A68', 'spark' => '#A9DECF', 'disc' => '#fff', 'type' => 'check', 'value' => $doneCount, 'label' => __('Kuiz selesai')],
                ['accent' => '#E0A21C', 'bg' => 'linear-gradient(135deg,#FEF6E4,#FBE9C2)', 'ink' => '#8A6A12', 'spark' => '#F1D592', 'disc' => '#fff', 'type' => 'star', 'value' => $avgScore !== null ? $avgScore.'%' : '—', 'label' => __('Purata markah')],
                ['accent' => '#3E86C9', 'bg' => 'linear-gradient(135deg,#EFF4FC,#DFEAF7)', 'ink' => '#2E6CA8', 'spark' => '#B6CEEC', 'disc' => '#fff', 'type' => 'trophy', 'value' => $rank ? '#'.$rank : '—', 'label' => __('Ranking'), 'leaf' => true],
            ])
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                @foreach ($statCards as $c)
                    <div style="position:relative;overflow:hidden;background:{{ $c['bg'] }};border-left:5px solid {{ $c['accent'] }};border-radius:18px;padding:18px 20px;display:flex;align-items:center;gap:16px">
                        {{-- Decorations. --}}
                        <svg style="position:absolute;top:14px;right:18px;opacity:.6" width="16" height="16" viewBox="0 0 16 16" fill="{{ $c['spark'] }}"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>
                        <span style="position:absolute;right:-34px;bottom:-46px;width:130px;height:100px;border-radius:50%;background:{{ $c['accent'] }};opacity:.07"></span>
                        @if (! empty($c['leaf']))
                            <svg style="position:absolute;right:6px;bottom:0;opacity:.4" width="54" height="54" viewBox="0 0 60 60" fill="{{ $c['spark'] }}"><path d="M8 52c0-15 11-26 27-28-2 7-6 12-11 16 6-2 12-1 17 2-8 4-17 3-24-1 4 6 3 13-2 18-4-3-7-9-7-15z"/></svg>
                        @endif
                        {{-- Icon disc. --}}
                        <span style="position:relative;z-index:1;width:46px;height:46px;flex-shrink:0;border-radius:50%;background:{{ $c['disc'] }};display:grid;place-items:center;box-shadow:0 4px 12px rgba(46,44,80,.12)">
                            @if ($c['type'] === 'check')
                                <span style="width:27px;height:27px;border-radius:8px;background:{{ $c['accent'] }};display:grid;place-items:center;color:#fff"><x-icon name="check" style="width:16px;height:16px" /></span>
                            @elseif ($c['type'] === 'star')
                                <svg width="27" height="27" viewBox="0 0 24 24" fill="{{ $c['accent'] }}"><path d="M12 3l2.35 4.76 5.25.76-3.8 3.7.9 5.23L12 15.9l-4.7 2.47.9-5.23-3.8-3.7 5.25-.76z"/></svg>
                            @else
                                <span style="color:{{ $c['accent'] }}"><x-icon name="trophy" style="width:25px;height:25px" /></span>
                            @endif
                        </span>
                        {{-- Value + label. --}}
                        <div style="position:relative;z-index:1;display:flex;flex-direction:column;gap:1px;min-width:0">
                            <span style="font-family:'Geist',sans-serif;font-size:23px;font-weight:800;color:{{ $c['ink'] }}">{{ $c['value'] }}</span>
                            <span style="font-size:12.5px;font-weight:700;color:{{ $c['ink'] }}">{{ $c['label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Achievements: perfect-score milestone badges, evenly spread. --}}
            <div style="display:flex;flex-direction:column;gap:12px;margin-top:34px">
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="width:34px;height:34px;border-radius:10px;background:#FEF0CE;color:#E0A21C;display:grid;place-items:center;flex-shrink:0"><x-icon name="trophy" style="width:19px;height:19px" /></span>
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Lencana Pencapaian') }}</h3>
                </div>
                @php($spk = 'M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z')
                <div style="position:relative;overflow:hidden;background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;padding:24px 20px;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                    {{-- Botanical corners (soft blob + olive-branch sprig) and scattered sparkles, redrawn from the reference. --}}
                    @once
                        {{-- The pastel shade is fine on the light card but glows on the dark one, so fade it at night. --}}
                        <style>
                            html.theme-dark .ach-shade-l { opacity:.2 !important; }
                            html.theme-dark .ach-shade-r { opacity:.16 !important; }
                        </style>
                    @endonce
                    <div aria-hidden="true" style="position:absolute;inset:0;z-index:0;pointer-events:none;overflow:hidden">
                        {{-- Soft pastel-green shade behind each branch (dimmed in dark mode via .ach-shade-*). --}}
                        <div class="ach-shade-l" style="position:absolute;left:-26px;bottom:-30px;width:164px;height:185px;border-radius:58% 42% 48% 52% / 62% 55% 45% 38%;background:#E7EEE8;opacity:.42;filter:blur(12px)"></div>
                        <div class="ach-shade-r" style="position:absolute;right:-44px;bottom:-30px;width:146px;height:167px;border-radius:42% 58% 52% 48% / 58% 45% 55% 42%;background:#E7EEE8;opacity:.36;filter:blur(12px)"></div>
                        {{-- Leaf branches from the uploaded illustration, cropped into each corner. --}}
                        <div style="position:absolute;left:0;bottom:0;width:90px;height:117px;opacity:.92;background:url('{{ asset('images/leaf.png') }}') no-repeat -10px -24px / 238px 158px"></div>
                        <div style="position:absolute;right:-4px;bottom:0;width:77px;height:100px;opacity:.92;transform:scaleX(-1) rotate(9deg);transform-origin:50% 100%;background:url('{{ asset('images/leaf.png') }}') no-repeat -9px -20px / 203px 135px"></div>
                        <svg style="position:absolute;top:30px;left:9%" width="11" height="11" viewBox="0 0 16 16" fill="#7FC8B8"><path d="{{ $spk }}"/></svg>
                        <svg style="position:absolute;top:20px;left:17%" width="18" height="18" viewBox="0 0 16 16" fill="#93A3E8"><path d="{{ $spk }}"/></svg>
                        <svg style="position:absolute;top:64px;left:13%" width="12" height="12" viewBox="0 0 16 16" fill="#7FC8B8"><path d="{{ $spk }}"/></svg>
                        <svg style="position:absolute;top:80px;left:22%" width="15" height="15" viewBox="0 0 16 16" fill="#B79FE0"><path d="{{ $spk }}"/></svg>
                        <svg style="position:absolute;top:18px;left:70%" width="17" height="17" viewBox="0 0 16 16" fill="#9AA6E8"><path d="{{ $spk }}"/></svg>
                        <svg style="position:absolute;top:32px;left:78%" width="12" height="12" viewBox="0 0 16 16" fill="#F1C069"><path d="{{ $spk }}"/></svg>
                        <svg style="position:absolute;top:72px;left:74%" width="15" height="15" viewBox="0 0 16 16" fill="#F1C069"><path d="{{ $spk }}"/></svg>
                        <svg style="position:absolute;top:86px;left:78%" width="12" height="12" viewBox="0 0 16 16" fill="#7FC8B8"><path d="{{ $spk }}"/></svg>
                    </div>
                    <div class="kz-ach" style="position:relative;z-index:1">
                        @foreach (\App\Support\QuizBadges::milestones() as $key => $threshold)
                            <x-quiz-badge :badge="$key" :muted="$perfectCount < $threshold" />
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Completed (left) beside Suggested Quiz (right). --}}
            <div class="kz-cols" style="margin-top:34px">
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
                                @php($tagBg = 'color-mix(in oklab, '.($sub->color ?: '#17907B').' var(--pill-bw), var(--pill-bb))')
                                @php($tagColor = 'color-mix(in oklab, '.($sub->color ?: '#17907B').' var(--pill-fw), var(--pill-fb))')
                                <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--wl-line)">
                                    <span style="width:40px;height:40px;border-radius:12px;background:{{ $tagBg }};display:grid;place-items:center;flex-shrink:0"><x-subject-emoji :subject="$sub" class="text-base" /></span>
                                    <div style="display:flex;flex-direction:column;gap:6px;min-width:0;flex:1">
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--wl-ink)">{{ $quiz->localizedTitle() }}</span>
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
                        @php($tagBg = "color-mix(in oklab, {$col} var(--pill-bw), var(--pill-bb))")
                        @php($tagColor = "color-mix(in oklab, {$col} var(--pill-fw), var(--pill-fb))")
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
