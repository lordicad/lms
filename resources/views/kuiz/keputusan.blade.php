<x-student-layout :title="__('Keputusan')">
    @php($pct = $attempt->percentage())
    {{-- True only right after finishing this quiz (flashed by submit()), so reviewing an old
         completed attempt via "Semak" shows no confetti / "new" badge fanfare. --}}
    @php($celebrate = session('quiz_celebrate') == $attempt->id)
    @php($good = $pct >= 80)
    @php($mid = $pct >= 50)
    @php($name = \Illuminate\Support\Str::before(auth()->user()->name, ' '))

    <div style="display:flex;flex-direction:column;gap:24px;max-width:760px;margin:0 auto;width:100%">
        {{-- Score card --}}
        @php($isDark = ($theme ?? 'light') === 'dark')
        @php($stats = $isDark ? [
            ['icon' => 'check-circle', 'bg' => 'rgba(45,212,191,.15)', 'ink' => '#5EEAD4', 'label' => __('Betul'), 'value' => $attempt->correct_count.'/'.$attempt->question_count],
            ['icon' => 'target-arrow', 'bg' => 'rgba(96,140,200,.16)', 'ink' => '#7FB2EA', 'label' => __('Ketepatan'), 'value' => $pct.'%'],
            ['icon' => 'clock', 'bg' => 'rgba(146,126,214,.20)', 'ink' => '#B7A6E8', 'label' => __('Masa'), 'value' => $attempt->humanDuration()],
        ] : [
            ['icon' => 'check-circle', 'bg' => '#DCF2EE', 'ink' => '#0F7A68', 'label' => __('Betul'), 'value' => $attempt->correct_count.'/'.$attempt->question_count],
            ['icon' => 'target-arrow', 'bg' => '#E4EEF9', 'ink' => '#2E6CA8', 'label' => __('Ketepatan'), 'value' => $pct.'%'],
            ['icon' => 'clock', 'bg' => '#E9E4F9', 'ink' => '#7C5CBF', 'label' => __('Masa'), 'value' => $attempt->humanDuration()],
        ])
        @php($cheer = match (true) {
            $pct >= 100 => ['icon' => 'target-arrow', 'bg' => '#EEF3FC', 'bgDark' => 'rgba(96,140,200,.14)', 'ink' => '#2E6CA8', 'inkDark' => '#7FB2EA', 'title' => __('Hebat! Semua jawapan betul.'), 'sub' => __('Teruskan mencabar diri untuk mencapai lebih!')],
            $pct >= 80 => ['icon' => 'target-arrow', 'bg' => '#E4F4EF', 'bgDark' => 'rgba(45,212,191,.13)', 'ink' => '#0F7A68', 'inkDark' => '#5EEAD4', 'title' => __('Bagus! Prestasi cemerlang.'), 'sub' => __('Sedikit lagi untuk markah sempurna!')],
            $pct >= 50 => ['icon' => 'target-arrow', 'bg' => '#FBF3DD', 'bgDark' => 'rgba(224,162,28,.15)', 'ink' => '#8A6A12', 'inkDark' => '#F0B733', 'title' => __('Usaha yang baik!'), 'sub' => __('Ulang kaji dan cuba tingkatkan markah anda.')],
            default => ['icon' => 'target-arrow', 'bg' => '#FBEAE4', 'bgDark' => 'rgba(194,73,54,.16)', 'ink' => '#C24936', 'inkDark' => '#E8836E', 'title' => __('Jangan putus asa!'), 'sub' => __('Tonton semula video dan cuba lagi - anda pasti boleh!')],
        })
        @php($star = 'M12 3l2.35 4.76 5.25.76-3.8 3.7.9 5.23L12 15.9l-4.7 2.47.9-5.23-3.8-3.7 5.25-.76z')
        <div style="position:relative;overflow:hidden;background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;padding:32px;box-shadow:0 8px 24px var(--wl-line)">
            {{-- Decorative confetti: rings, a dot cluster and scattered stars behind the content. --}}
            <div aria-hidden="true" style="position:absolute;inset:0;z-index:0;pointer-events:none;overflow:hidden">
                <span style="position:absolute;top:-45px;right:-35px;width:140px;height:140px;border-radius:50%;background:#FBE7C9;opacity:.45"></span>
                <svg style="position:absolute;top:70px;right:-70px;opacity:.5" width="220" height="220" viewBox="0 0 220 220" fill="none" stroke="#F2D6A6" stroke-width="2"><circle cx="150" cy="110" r="60"/><circle cx="150" cy="110" r="86"/></svg>
                <svg style="position:absolute;top:22px;left:26px;opacity:.5" width="120" height="130" viewBox="0 0 120 130" fill="#8FCFBE"><circle cx="10" cy="10" r="4"/><circle cx="30" cy="16" r="4"/><circle cx="50" cy="22" r="4"/><circle cx="14" cy="34" r="4"/><circle cx="34" cy="40" r="4"/><circle cx="54" cy="46" r="4"/><circle cx="18" cy="58" r="4"/><circle cx="38" cy="64" r="4"/><circle cx="58" cy="70" r="4"/><circle cx="22" cy="82" r="4"/><circle cx="42" cy="88" r="4"/></svg>
                <span style="position:absolute;top:118px;left:60px;transform:rotate(-12deg)"><svg width="30" height="30" viewBox="0 0 24 24" fill="#FBC24A"><path d="{{ $star }}"/></svg></span>
                <span style="position:absolute;top:250px;left:82px"><svg width="22" height="22" viewBox="0 0 24 24" fill="#4FC0A8"><path d="{{ $star }}"/></svg></span>
                <span style="position:absolute;top:176px;right:70px;transform:rotate(12deg)"><svg width="26" height="26" viewBox="0 0 24 24" fill="#6FA8E0"><path d="{{ $star }}"/></svg></span>
                <span style="position:absolute;top:250px;right:46px"><svg width="22" height="22" viewBox="0 0 24 24" fill="#EC9BBB"><path d="{{ $star }}"/></svg></span>
                <span style="position:absolute;top:270px;left:38px;width:10px;height:10px;border-radius:50%;background:#8FD3E8"></span>
                <span style="position:absolute;top:252px;right:156px;width:9px;height:9px;border-radius:50%;background:#F2C4A0"></span>
            </div>

            <div style="position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:14px;text-align:center">
                @php($resultImg = $good ? 'confetti.png' : ($mid ? 'muscle.png' : 'book1.png'))
                <img src="{{ asset('images/'.$resultImg) }}" alt="" style="width:68px;height:68px;object-fit:contain">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:26px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ $good ? __('Syabas, :name!', ['name' => $name]) : __('Kerja yang baik!') }}</h2>
                <span style="font-size:14.5px;color:var(--wl-muted)">{{ $good ? __('Keputusan cemerlang. Teruskan usaha!') : ($mid ? __('Usaha yang baik. Cuba tingkatkan lagi!') : __('Jangan putus asa - tonton semula video dan cuba lagi.')) }}</span>
                <div style="display:flex;align-items:baseline;gap:4px;margin-top:6px">
                    <span style="font-family:'Geist',sans-serif;font-size:48px;font-weight:800;color:var(--wl-ink)">{{ $attempt->score }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:var(--wl-muted)">/{{ $attempt->max_score }}</span>
                </div>
                <div style="width:70%;height:9px;border-radius:999px;background:#DCEAF8;overflow:hidden">
                    <div style="height:100%;border-radius:999px;background:#17907B;width:{{ $pct }}%"></div>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;width:100%;margin-top:10px">
                    @foreach ($stats as $s)
                        <div style="background:{{ $isDark ? '#2A3543' : '#FBFAF6' }};border:1px solid var(--wl-line-2);border-radius:14px;padding:14px 16px;display:flex;align-items:center;gap:12px;text-align:left">
                            <span style="width:40px;height:40px;flex-shrink:0;border-radius:50%;background:{{ $s['bg'] }};color:{{ $s['ink'] }};display:grid;place-items:center"><x-icon :name="$s['icon']" style="width:20px;height:20px" /></span>
                            <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">{{ $s['label'] }}</span>
                                <span style="font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--wl-ink)">{{ $s['value'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div style="width:100%;background:{{ $isDark ? 'rgba(45,212,191,.12)' : '#DCF2EE' }};border:1px solid {{ $isDark ? 'rgba(45,212,191,.3)' : 'rgba(23,144,123,.25)' }};border-radius:14px;padding:13px 16px;display:flex;align-items:center;gap:12px;margin-top:4px">
                    <span style="width:30px;height:30px;flex-shrink:0;border-radius:50%;background:#17907B;color:#fff;display:grid;place-items:center"><x-icon name="check" style="width:17px;height:17px" /></span>
                    <span style="font-family:'Geist',sans-serif;font-size:13.5px;font-weight:700;color:{{ $isDark ? '#5EEAD4' : '#0F7A68' }};text-align:left">{{ $attempt->counts_for_ranking ? __('Ini percubaan pertama anda, jadi :score mata dikira untuk ranking.', ['score' => $attempt->score]) : __('Ini latihan semula. Markah ini tidak menjejaskan ranking anda.') }}</span>
                </div>

                {{-- Badges earned, in their own bordered card. --}}
                @if (! empty($badgesEarned))
                    <div style="width:100%;border:1px solid var(--wl-line);border-radius:18px;padding:20px;display:flex;flex-direction:column;gap:16px;text-align:left;margin-top:4px">
                        <div style="display:flex;align-items:center;gap:12px">
                            <span style="width:40px;height:40px;flex-shrink:0;border-radius:12px;background:#FEF0CE;color:#E0A21C;display:grid;place-items:center"><x-icon name="trophy" style="width:22px;height:22px" /></span>
                            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;color:var(--wl-ink)">{{ __('Lencana Diperoleh') }}</h3>
                            @if ($celebrate && count($badgesNew))
                                <span style="background:#DCF2EE;color:#0F7A68;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">+{{ count($badgesNew) }} {{ __('baru') }}</span>
                            @endif
                        </div>
                        <div style="display:flex;flex-wrap:wrap;gap:14px;justify-content:center">
                            @foreach ($badgesEarned as $key)
                                @php($new = $celebrate && in_array($key, $badgesNew, true))
                                <div style="position:relative;flex:0 0 auto;border:1px solid var(--wl-line);border-radius:16px;padding:16px 12px 14px;background:var(--wl-surface)">
                                    @if ($new)
                                        <span style="position:absolute;top:8px;right:8px;background:#EB5E5A;color:#fff;border-radius:999px;padding:2px 10px;font-family:'Geist',sans-serif;font-size:10.5px;font-weight:800;box-shadow:0 3px 8px rgba(235,94,90,.4);z-index:4">{{ __('Baru') }}</span>
                                    @endif
                                    <x-quiz-badge :badge="$key" :is-new="$new" :tag="false" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Motivational note, tuned to the score. --}}
                <div style="width:100%;background:{{ $isDark ? $cheer['bgDark'] : $cheer['bg'] }};border-radius:16px;padding:15px 18px;display:flex;align-items:center;gap:14px;text-align:left">
                    <span style="width:44px;height:44px;flex-shrink:0;border-radius:50%;background:var(--wl-surface);color:{{ $isDark ? $cheer['inkDark'] : $cheer['ink'] }};display:grid;place-items:center"><x-icon :name="$cheer['icon']" style="width:22px;height:22px" /></span>
                    <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--wl-ink)">{{ $cheer['title'] }}</span>
                        <span style="font-size:13px;font-weight:600;color:var(--wl-muted)">{{ $cheer['sub'] }}</span>
                    </div>
                </div>

                <div style="display:flex;gap:12px;margin-top:8px;flex-wrap:wrap;justify-content:center;width:100%">
                    <a href="{{ route('kuiz.intro', $quiz) }}" class="wl-btn-secondary" style="flex:1;min-width:180px;min-height:48px;display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:14px;border:2px solid #17907B;background:#fff;color:#0F7A68;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px;text-decoration:none"><x-icon name="rotate" style="width:18px;height:18px" />{{ __('Cuba Lagi (Latihan)') }}</a>
                    <a href="{{ route('ranking.index') }}" class="wl-btn-primary" style="flex:1;min-width:180px;min-height:48px;display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:14px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px;text-decoration:none"><x-icon name="trophy" style="width:18px;height:18px" />{{ __('Lihat Ranking') }}</a>
                </div>
            </div>
        </div>

        {{-- Answer review --}}
        <div style="display:flex;flex-direction:column;gap:14px">
            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:18px;font-weight:800;color:var(--wl-ink)">{{ __('Semakan Jawapan') }}</h3>
            @foreach ($questions as $index => $question)
                @php($answer = $answersByQuestion[$question->id] ?? null)
                @php($ok = $answer?->is_correct)
                <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:20px;padding:24px;display:flex;flex-direction:column;gap:14px;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                        <span style="font-family:'Geist',sans-serif;font-size:13px;font-weight:800;color:var(--wl-muted)">{{ __('Soalan') }} {{ $index + 1 }}</span>
                        @if ($ok)
                            <span style="border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;{{ $isDark ? 'background:rgba(45,212,191,.15);color:#5EEAD4' : 'background:#DCF2EE;color:#0F7A68' }}">✓ {{ __('Betul.') }} {{ $answer->points_awarded }} {{ __('mata') }}</span>
                        @else
                            <span style="border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;{{ $isDark ? 'background:rgba(235,94,90,.16);color:#F0857F' : 'background:#FDE7E0;color:#C24936' }}">✗ {{ __('Salah. 0 mata') }}</span>
                        @endif
                    </div>
                    <h4 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;line-height:1.4;color:var(--wl-ink)">{{ $question->localizedText() }}</h4>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        @foreach ($question->options as $option)
                            @php($sel = $answer?->selected($option->id) ?? false)
                            @php($isC = $option->is_correct)
                            @php($border = 'var(--wl-line-2)')
                            @php($bg = $isDark ? 'var(--wl-page)' : '#fff')
                            @php($tagTxt = '')
                            @php($tagStyle = '')
                            @php($letterStyle = $isDark ? 'background:var(--wl-chip);color:var(--wl-muted)' : 'background:#F1F0E8;color:var(--wl-muted-2)')
                            @if ($sel && $isC)
                                @php($border = $isDark ? '#2DD4BF' : '#17907B') @php($bg = $isDark ? 'rgba(45,212,191,.14)' : '#DCF2EE') @php($tagTxt = __('Jawapan anda')) @php($tagStyle = 'background:#17907B;color:#fff') @php($letterStyle = 'background:#17907B;color:#fff')
                            @elseif ($sel && ! $isC)
                                @php($border = '#EB5E5A') @php($bg = $isDark ? 'rgba(235,94,90,.14)' : '#FDE7E0') @php($tagTxt = __('Jawapan anda')) @php($tagStyle = 'background:#EB5E5A;color:#fff') @php($letterStyle = 'background:#EB5E5A;color:#fff')
                            @elseif ($isC)
                                @php($border = $isDark ? '#2DD4BF' : '#17907B') @php($tagTxt = __('Jawapan betul')) @php($tagStyle = $isDark ? 'background:rgba(45,212,191,.16);color:#5EEAD4' : 'background:#DCF2EE;color:#0F7A68') @php($letterStyle = 'background:#17907B;color:#fff')
                            @endif
                            <div style="display:flex;align-items:center;gap:14px;border-radius:14px;padding:14px 18px;border:1.5px solid {{ $border }};background:{{ $bg }}">
                                <span style="width:30px;height:30px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;{{ $letterStyle }}">{{ $option->letter() }}</span>
                                <span style="font-family:'Geist',sans-serif;font-weight:700;font-size:14.5px;color:var(--wl-ink);flex:1">{{ $option->localizedText($question->source_locale) }}</span>
                                @if ($tagTxt)
                                    <span style="border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800;{{ $tagStyle }}">{{ $tagTxt }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- AI explainer: only for a wrong answer, and only when the Claude key is set.
                         Loads on demand, caches server-side, so a re-open is instant. --}}
                    @if (! $ok && $explainerEnabled)
                        <div x-data="answerExplain(@js(route('keputusan.terang', [$attempt, $question])))">
                            <button type="button" @click="load()" x-show="!open" x-cloak
                                    style="display:inline-flex;align-items:center;gap:8px;border:1.5px solid {{ $isDark ? '#2DD4BF' : '#17907B' }};background:{{ $isDark ? 'rgba(45,212,191,.1)' : '#F0FBF8' }};color:{{ $isDark ? '#5EEAD4' : '#0F7A68' }};border-radius:12px;padding:9px 16px;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;cursor:pointer">
                                <x-icon name="sparkles" style="width:17px;height:17px" />
                                {{ __('Terangkan dengan AI') }}
                            </button>

                            <div x-show="open" x-cloak
                                 style="border:1.5px solid {{ $isDark ? 'rgba(45,212,191,.3)' : 'rgba(23,144,123,.25)' }};background:{{ $isDark ? 'rgba(45,212,191,.08)' : '#F0FBF8' }};border-radius:14px;padding:16px 18px;display:flex;flex-direction:column;gap:8px">
                                <div style="display:flex;align-items:center;gap:9px">
                                    <span style="width:28px;height:28px;flex-shrink:0;border-radius:8px;background:#17907B;color:#fff;display:grid;place-items:center"><x-icon name="sparkles" style="width:16px;height:16px" /></span>
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:{{ $isDark ? '#5EEAD4' : '#0F7A68' }}">{{ __('Penerangan AI') }}</span>
                                </div>

                                <span x-show="loading" style="font-size:13.5px;color:var(--wl-muted);font-weight:600">{{ __('Sedang menjana penerangan...') }}</span>

                                <p x-show="!loading && !error" x-text="text"
                                   style="margin:0;font-family:'Nunito',sans-serif;font-size:14px;line-height:1.6;color:var(--wl-ink)"></p>

                                <div x-show="error" x-cloak style="display:flex;align-items:center;gap:10px">
                                    <span style="font-size:13.5px;color:#C24936;font-weight:700">{{ __('Penerangan gagal dijana.') }}</span>
                                    <button type="button" @click="load()" style="border:none;background:none;color:{{ $isDark ? '#5EEAD4' : '#0F7A68' }};font-family:'Geist',sans-serif;font-weight:800;font-size:13px;cursor:pointer;text-decoration:underline">{{ __('Cuba lagi') }}</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <a href="{{ route('kuiz-saya.index') }}" class="wl-btn-secondary" style="align-self:center;min-height:48px;display:inline-flex;align-items:center;gap:6px;border-radius:14px;border:2px solid {{ $isDark ? '#2DD4BF' : '#17907B' }};background:{{ $isDark ? 'var(--wl-surface)' : '#fff' }};color:{{ $isDark ? '#5EEAD4' : '#0F7A68' }};font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 24px;text-decoration:none"><x-icon name="arrow-left" style="width:17px;height:17px" />{{ __('Kembali') }}</a>
    </div>

    @once
        @push('scripts')
            <script>
                // On-demand AI explanation for a wrong answer. Fetches once, then keeps the text so
                // re-opening is instant; the server caches across students too.
                function answerExplain(url) {
                    return {
                        open: false,
                        loading: false,
                        error: false,
                        text: '',
                        load() {
                            this.open = true;
                            if (this.text) { this.error = false; return; }  // already fetched
                            this.loading = true;
                            this.error = false;
                            const token = document.querySelector('meta[name=csrf-token]')?.content;
                            fetch(url, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                            })
                                .then((r) => (r.ok ? r.json() : Promise.reject(r)))
                                .then((d) => { this.text = d.explanation || ''; this.loading = false; })
                                .catch(() => { this.error = true; this.loading = false; });
                        },
                    };
                }
            </script>
        @endpush
    @endonce

    {{-- One-off full-screen confetti rain - only for a perfect score, and only on a fresh finish
         (not when reviewing). Self-contained canvas (no library), skipped under reduced motion. --}}
    @if ($pct >= 100 && $celebrate)
        <script>
            (function () {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

                var colors = ['#2DD4BF', '#F3B94C', '#EB5E5A', '#A88FE4', '#6FA8E0', '#17907B'];
                var canvas = document.createElement('canvas');
                canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:9998';
                document.body.appendChild(canvas);
                var ctx = canvas.getContext('2d');

                function fit() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
                fit();
                window.addEventListener('resize', fit);

                var parts = [];
                for (var i = 0; i < 160; i++) {
                    parts.push({
                        x: Math.random() * canvas.width,
                        y: -20 - Math.random() * canvas.height * 0.4,
                        w: 6 + Math.random() * 6,
                        h: 9 + Math.random() * 8,
                        vx: (Math.random() - 0.5) * 3,
                        vy: 3 + Math.random() * 4,
                        rot: Math.random() * Math.PI,
                        vr: (Math.random() - 0.5) * 0.35,
                        sway: Math.random() * Math.PI * 2,
                        color: colors[i % colors.length]
                    });
                }

                var start = performance.now();
                var DURATION = 3200;

                function frame(now) {
                    var t = now - start;
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    var fade = t > DURATION - 700 ? Math.max(0, (DURATION - t) / 700) : 1;
                    for (var i = 0; i < parts.length; i++) {
                        var p = parts[i];
                        p.x += p.vx + Math.sin(now / 400 + p.sway) * 0.9;
                        p.y += p.vy;
                        p.vy += 0.035;
                        p.rot += p.vr;
                        ctx.save();
                        ctx.globalAlpha = fade;
                        ctx.translate(p.x, p.y);
                        ctx.rotate(p.rot);
                        ctx.fillStyle = p.color;
                        ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                        ctx.restore();
                    }
                    if (t < DURATION) {
                        requestAnimationFrame(frame);
                    } else {
                        window.removeEventListener('resize', fit);
                        canvas.remove();
                    }
                }
                requestAnimationFrame(frame);
            })();
        </script>
    @endif
</x-student-layout>
