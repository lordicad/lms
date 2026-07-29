<x-student-layout :title="__('Ranking')">
    <style>
        /* Top-3 podium. Rank colours are passed per card as --c-* custom properties. */
        .lb-podium-wrap { position: relative; width: 100%; max-width: 780px; margin: 4px auto 0; }
        .lb-podium { position: relative; z-index: 1; display: grid; grid-template-columns: 1fr 1.12fr 1fr; gap: clamp(16px, 2.6vw, 38px); align-items: end; }

        /* Cream podium steps peeking out beneath the cards. */
        .lb-base { position: absolute; left: 7%; right: 7%; bottom: -12px; height: 56px; z-index: 0; display: flex; align-items: flex-end; justify-content: center; pointer-events: none; }
        .lb-step { background: linear-gradient(180deg, #FFF9EE, #FFF3D9); border-radius: 16px 16px 0 0; }
        .lb-step--center { width: 40%; height: 56px; box-shadow: 0 14px 26px rgba(211, 162, 76, .14); }
        .lb-step--side { width: 30%; height: 36px; }

        .lb-card { position: relative; overflow: hidden; border-radius: 28px; background: linear-gradient(180deg, var(--c-bg), var(--c-bg2)); border: 1.5px solid var(--c-border); box-shadow: 0 18px 45px rgba(36,43,67,.08), 0 4px 12px rgba(36,43,67,.04); display: flex; flex-direction: column; align-items: center; text-align: center; }
        .lb-card--first { min-height: 282px; padding: 16px 18px 20px; gap: 8px; }
        .lb-card--second, .lb-card--third { min-height: 226px; padding: 15px 16px 16px; gap: 7px; }
        /* Warm glow behind the champion's avatar. */
        .lb-card--first::before { content: ''; position: absolute; top: 62px; left: 50%; width: 210px; height: 210px; transform: translateX(-50%); border-radius: 50%; background: radial-gradient(circle, rgba(246,185,26,.15), transparent 68%); z-index: 0; pointer-events: none; }

        .lb-medal { position: relative; display: block; z-index: 2; }
        .lb-medal svg { display: block; width: 100%; height: 100%; }
        .lb-num { position: absolute; top: 0; left: 0; right: 0; height: 65%; display: grid; place-items: center; font-family: 'Geist', sans-serif; font-weight: 800; color: var(--c-mnum); }
        .lb-card--first .lb-medal { width: 44px; height: 52px; }
        .lb-card--first .lb-num { font-size: 14px; }
        .lb-card--second .lb-medal, .lb-card--third .lb-medal { width: 38px; height: 45px; }
        .lb-card--second .lb-num, .lb-card--third .lb-num { font-size: 12px; }

        .lb-avatar { position: relative; z-index: 1; border-radius: 50%; display: grid; place-items: center; overflow: hidden; background: radial-gradient(circle at 50% 32%, #fff, var(--c-avfill) 80%); border: 3px solid var(--c-avring); color: var(--c-avink); font-family: 'Geist', sans-serif; font-weight: 800; }
        .lb-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .lb-card--first .lb-avatar { width: 78px; height: 78px; font-size: 30px; margin-top: -6px; }
        .lb-card--second .lb-avatar, .lb-card--third .lb-avatar { width: 66px; height: 66px; font-size: 25px; margin-top: -5px; }

        .lb-name { position: relative; z-index: 1; font-family: 'Geist', sans-serif; font-weight: 800; color: #10172F; letter-spacing: -.01em; }
        .lb-quizzes { position: relative; z-index: 1; font-weight: 600; color: #787B9D; }
        .lb-pill { position: relative; z-index: 1; border-radius: 999px; font-family: 'Geist', sans-serif; font-weight: 800; background: var(--c-pillbg); color: var(--c-pillink); }
        .lb-card--first .lb-name { font-size: 20px; }
        .lb-card--first .lb-quizzes { font-size: 13px; }
        .lb-card--first .lb-pill { font-size: 14px; padding: 7px 20px; margin-top: 10px; }
        .lb-card--second .lb-name, .lb-card--third .lb-name { font-size: 16px; }
        .lb-card--second .lb-quizzes, .lb-card--third .lb-quizzes { font-size: 12px; }
        .lb-card--second .lb-pill, .lb-card--third .lb-pill { font-size: 12.5px; padding: 5px 16px; margin-top: 8px; }

        .lb-deco { position: absolute; pointer-events: none; z-index: 0; }

        /* Ranks 4+ table. */
        .lb-tbl { background: var(--wl-surface); border: 1px solid var(--wl-line); border-radius: 18px; overflow: hidden; box-shadow: 0 4px 16px rgba(46,44,80,.04); }
        .lb-tbl-head, .lb-tr { display: grid; grid-template-columns: 58px minmax(0,1fr) 218px 104px; align-items: center; gap: 16px; padding: 14px 32px 14px 22px; }
        .lb-tbl-head { border-bottom: 1px solid var(--wl-line); }
        .lb-th { font-family: 'Geist', sans-serif; font-size: 12.5px; font-weight: 700; color: var(--wl-muted); }
        .lb-th.c { text-align: center; }
        .lb-th.r { text-align: right; }
        .lb-tr + .lb-tr { border-top: 1px solid var(--wl-line); }
        .lb-tr--me { background: #DCF2EE; }
        .lb-rank { width: 40px; height: 40px; border-radius: 12px; background: #EEF0F4; display: grid; place-items: center; font-family: 'Geist', sans-serif; font-weight: 800; font-size: 15px; color: #4A4B63; }
        .lb-part { display: flex; align-items: center; gap: 13px; min-width: 0; }
        .lb-pav { width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; font-family: 'Geist', sans-serif; font-weight: 800; font-size: 15px; flex-shrink: 0; }
        .lb-pinfo { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
        .lb-pname { font-family: 'Geist', sans-serif; font-weight: 800; font-size: 14.5px; color: var(--wl-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lb-psub { font-size: 12px; color: var(--wl-muted); }
        .lb-score { display: flex; align-items: center; gap: 12px; }
        .lb-bar { flex: 1; height: 8px; border-radius: 999px; background: #EAECEF; overflow: hidden; }
        .lb-bar > span { display: block; height: 100%; border-radius: 999px; }
        .lb-pct { font-size: 13px; font-weight: 700; color: #4A4B63; min-width: 46px; text-align: right; }
        .lb-pts { font-family: 'Geist', sans-serif; font-weight: 800; font-size: 14.5px; color: #17907B; text-align: right; }
        .lb-chev { color: var(--wl-muted-2); display: grid; place-items: center; }

        @media (max-width: 720px) {
            .lb-tbl-head, .lb-tr { grid-template-columns: 42px minmax(0,1fr) auto; gap: 12px; padding: 12px 15px; }
            .lb-th.score, .lb-score, .lb-chev { display: none; }
        }

        @media (prefers-reduced-motion: no-preference) {
            .lb-card { opacity: 0; transform: translateY(8px); animation: lbIn .45s ease-out forwards; }
            .lb-card--second { animation-delay: .04s; }
            .lb-card--third { animation-delay: .09s; }
            .lb-card--first { animation-delay: .15s; }
        }
        @keyframes lbIn { to { opacity: 1; transform: none; } }

        /* Tablet: shrink proportionally, keep three in a row. */
        @media (max-width: 1100px) {
            .lb-card--first { min-height: 316px; padding: 28px 16px 22px; }
            .lb-card--second, .lb-card--third { min-height: 236px; padding: 22px 14px 16px; }
            .lb-card--first .lb-avatar { width: 84px; height: 84px; font-size: 33px; }
            .lb-card--second .lb-avatar, .lb-card--third .lb-avatar { width: 66px; height: 66px; font-size: 25px; }
            .lb-card--first .lb-name { font-size: 20px; }
            .lb-card--second .lb-name, .lb-card--third .lb-name { font-size: 16px; }
        }

        /* Mobile: stack, Rank 1 first, no elevation, hide the podium base. */
        @media (max-width: 640px) {
            .lb-podium { grid-template-columns: 1fr; gap: 32px; }
            .lb-base { display: none; }
            .lb-card { min-height: 0 !important; padding: 32px 20px 24px !important; }
            .lb-card--first { order: -1; }
            .lb-card--first::before { top: 68px; }
        }
    </style>

    @php($me = auth()->user())
    @php($palette = [
        ['#DCF2EE', '#0F7A68', '#2BB39B'],
        ['#E4EEF9', '#2E6CA8', '#82B3E1'],
        ['#FBE4ED', '#B84A75', '#F5B5CC'],
        ['#FEF0CE', '#8A6A12', '#FBB92A'],
        ['#FDE7E0', '#C24936', '#EB5E5A'],
    ])
    @php($initial = fn ($name) => mb_strtoupper(mb_substr($name, 0, 1)))

    <div style="display:flex;flex-direction:column;gap:20px">
        <div style="display:flex;align-items:center;gap:16px">
            <span style="width:56px;height:56px;border-radius:16px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="trophy" style="width:28px;height:28px" /></span>
            <div style="display:flex;flex-direction:column;gap:2px;min-width:0">
                <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:26px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ __('Ranking') }}</h2>
                <span style="font-size:14px;font-weight:600;color:var(--wl-muted)">{{ __('Murid berprestasi terbaik') }}{{ $grade ? ' · '.$grade->name : '' }}</span>
            </div>
        </div>

        <form method="GET" action="{{ route('ranking.index') }}" style="display:flex;align-items:center;gap:10px">
            <span style="font-family:'Geist',sans-serif;font-size:13.5px;font-weight:700;color:var(--wl-muted-2)">{{ __('Subjek:') }}</span>
            <select name="subjek" onchange="this.form.submit()" class="js-styled-select"
                    style="min-height:44px;border:1.5px solid var(--wl-line-2);border-radius:12px;padding:0 36px 0 14px;-webkit-appearance:none;-moz-appearance:none;appearance:none;background:var(--wl-surface) url(&quot;data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='24'%20height='24'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%2328293F'%20stroke-width='2.5'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M6%209l6%206%206-6'/%3E%3C/svg%3E&quot;) no-repeat right 12px center;background-size:12px;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--wl-ink);cursor:pointer">
                <option value="">{{ __('Keseluruhan') }}</option>
                @foreach ($subjects->groupBy('category') as $category => $group)
                    <optgroup label="{{ \App\Models\Subject::categoryLabel($category) }}">
                        @foreach ($group as $option)
                            <option value="{{ $option->slug }}" @selected($subject?->id === $option->id)>{{ $option->displayName() }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <noscript><button type="submit" style="min-height:44px;border-radius:12px;border:1.5px solid var(--wl-line-3);background:var(--wl-surface);padding:0 16px;cursor:pointer">{{ __('Tapis') }}</button></noscript>
        </form>

        @if ($top->isEmpty())
            <div style="background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center">
                <span style="font-size:32px">🏆</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--wl-ink)">{{ __('Belum ada ranking') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--wl-muted);max-width:360px">{{ __('Belum ada murid yang menyelesaikan kuiz dalam :grade. Jadilah yang pertama!', ['grade' => $grade?->name ?? __('tahun anda')]) }}</p>
            </div>
        @else
            {{-- Build the top-3 as plain records; the data order stays 1·2·3. --}}
            @php($podiumRows = $top->take(3)->values())
            @php($topStudents = $podiumRows->map(fn ($r, $i) => (object) [
                'rank' => $i + 1,
                'name' => $r->student->name,
                'initial' => $initial($r->student->name),
                'avatarUrl' => method_exists($r->student, 'avatarUrl') ? $r->student->avatarUrl() : null,
                'quizzes' => $r->quizzes,
                'points' => $r->points,
            ]))
            {{-- Display order is Rank 2 · Rank 1 · Rank 3 (derived, not a mutation of the data). --}}
            @php($displayOrder = collect([1, 0, 2])->map(fn ($i) => $topStudents[$i] ?? null)->filter()->values())
            {{-- Per-rank palette, handed to each card as CSS custom properties. --}}
            @php($cfg = [
                1 => ['cls' => 'lb-card--first', 'vars' => '--c-bg:#FFFBF2;--c-bg2:#FFF7E7;--c-border:#F4C45E;--c-disc:#F6B91A;--c-dring:#DDA00E;--c-dhi:#FCD277;--c-mnum:#7A4E10;--c-mribA:#EDA130;--c-mribB:#D68A1C;--c-avfill:#E9F8EF;--c-avring:#0DA77E;--c-avink:#07815E;--c-pillbg:#DDF4E7;--c-pillink:#087A58;--c-spark:#EFCE7C;--c-leaf:#EAD79A'],
                2 => ['cls' => 'lb-card--second', 'vars' => '--c-bg:#F5F9FF;--c-bg2:#ECF5FF;--c-border:#91BAF3;--c-disc:#CBD3DF;--c-dring:#A6ADBA;--c-dhi:#E6EBF2;--c-mnum:#37414F;--c-mribA:#447FD4;--c-mribB:#3565B0;--c-avfill:#EAF3FF;--c-avring:#78A9EE;--c-avink:#1762B6;--c-pillbg:#E1EEFF;--c-pillink:#1761BB;--c-spark:#B9CFEA;--c-leaf:#C4D8F0'],
                3 => ['cls' => 'lb-card--third', 'vars' => '--c-bg:#FFF6FA;--c-bg2:#FFEFF5;--c-border:#F1A8C1;--c-disc:#C87B48;--c-dring:#AE6636;--c-dhi:#E0A97A;--c-mnum:#6B3E18;--c-mribA:#C22A69;--c-mribB:#A82258;--c-avfill:#FFEAF2;--c-avring:#EC77A4;--c-avink:#C22A69;--c-pillbg:#FDE1EC;--c-pillink:#C32767;--c-spark:#F0BFD4;--c-leaf:#F3C9DC'],
            ])

            <section class="lb-podium-wrap" aria-label="{{ __('Murid berprestasi terbaik') }}">
                <h3 class="sr-only">{{ __('Murid berprestasi terbaik') }}</h3>

                {{-- Decorative cream podium steps behind the cards' feet. --}}
                <div class="lb-base" aria-hidden="true">
                    <span class="lb-step lb-step--side"></span>
                    <span class="lb-step lb-step--center"></span>
                    <span class="lb-step lb-step--side"></span>
                </div>

                <div class="lb-podium">
                    @foreach ($displayOrder as $s)
                        @php($c = $cfg[$s->rank])
                        <article class="lb-card {{ $c['cls'] }}" style="{{ $c['vars'] }}">
                            <span class="sr-only">{{ __('Kedudukan :rank', ['rank' => $s->rank]) }}</span>

                            {{-- Decorations: two sparkles, a corner leaf sprig, a soft corner blob. --}}
                            <svg class="lb-deco" style="top:13%;left:12%;opacity:.85" width="14" height="14" viewBox="0 0 16 16" fill="var(--c-spark)" aria-hidden="true"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>
                            <svg class="lb-deco" style="top:10%;right:13%;opacity:.6" width="10" height="10" viewBox="0 0 16 16" fill="var(--c-spark)" aria-hidden="true"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>
                            <svg class="lb-deco" style="bottom:-4px;{{ $s->rank === 3 ? 'right:-2px;transform:scaleX(-1)' : 'left:-2px' }};opacity:.38" width="62" height="62" viewBox="0 0 60 60" fill="var(--c-leaf)" aria-hidden="true"><path d="M8 52c0-15 11-26 27-28-2 7-6 12-11 16 6-2 12-1 17 2-8 4-17 3-24-1 4 6 3 13-2 18-4-3-7-9-7-15z"/></svg>
                            <svg class="lb-deco" style="bottom:-20px;{{ $s->rank === 3 ? 'left:-18px' : 'right:-18px' }};opacity:.3" width="94" height="72" viewBox="0 0 94 72" fill="var(--c-spark)" aria-hidden="true"><path d="M0 72C0 40 22 22 52 26c30 4 42 26 42 46H0z"/></svg>

                            {{-- Medal: metal disc + rank number, ribbon tails below. --}}
                            <span class="lb-medal" aria-hidden="true">
                                <svg viewBox="0 0 44 52" fill="none">
                                    <path d="M16 22 L22 22 L18.5 43 L15 39.5 L12 43 Z" fill="var(--c-mribB)"/>
                                    <path d="M22 22 L28 22 L32 43 L28.5 39.5 L25 43 Z" fill="var(--c-mribA)"/>
                                    <circle cx="22" cy="17" r="13" fill="var(--c-disc)" stroke="var(--c-dring)" stroke-width="2"/>
                                    <circle cx="22" cy="17" r="9.5" fill="none" stroke="var(--c-dhi)" stroke-width="1.5"/>
                                    <ellipse cx="18" cy="12" rx="4.5" ry="3" fill="#fff" opacity=".5"/>
                                </svg>
                                <span class="lb-num">{{ $s->rank }}</span>
                            </span>

                            {{-- Avatar: image when present, first initial otherwise. --}}
                            <span class="lb-avatar">
                                @if ($s->avatarUrl)
                                    <img src="{{ $s->avatarUrl }}" alt="{{ $s->name }}">
                                @else
                                    <span aria-hidden="true">{{ $s->initial }}</span>
                                @endif
                            </span>

                            <span class="lb-name">{{ \Illuminate\Support\Str::before($s->name, ' ') ?: $s->name }}</span>
                            <span class="lb-quizzes">{{ __(':count kuiz', ['count' => $s->quizzes]) }}</span>
                            <span class="lb-pill">{{ __(':count mata', ['count' => number_format($s->points)]) }}</span>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- Ranks 4–10 --}}
            @php($listRows = $top->slice(3)->values())
            @if ($listRows->isNotEmpty())
                <div class="lb-tbl">
                    <div class="lb-tbl-head">
                        <span class="lb-th">{{ __('Kedudukan') }}</span>
                        <span class="lb-th">{{ __('Peserta') }}</span>
                        <span class="lb-th c score">{{ __('Skor Purata') }}</span>
                        <span class="lb-th r">{{ __('Jumlah Mata') }}</span>
                    </div>
                    @foreach ($listRows as $i => $row)
                        @php($pal = $palette[($i + 3) % count($palette)])
                        @php($isMe = $row->student->id === $me->id)
                        @php($acc = min(100, max(0, $row->accuracy)))
                        <div class="lb-tr {{ $isMe ? 'lb-tr--me' : '' }}">
                            <span class="lb-rank">{{ $row->rank }}</span>
                            <div class="lb-part">
                                <span class="lb-pav" style="background:{{ $pal[0] }};color:{{ $pal[1] }}">{{ $initial($row->student->name) }}</span>
                                <div class="lb-pinfo">
                                    <span class="lb-pname">{{ $row->student->name }}@if ($isMe) <span style="color:#0F7A68">{{ __('(Anda)') }}</span>@endif</span>
                                    <span class="lb-psub">{{ __(':count kuiz', ['count' => $row->quizzes]) }} · {{ $row->accuracy }}% {{ __('purata') }}</span>
                                </div>
                            </div>
                            <div class="lb-score">
                                <span class="lb-bar"><span style="width:{{ $acc }}%;background:{{ $pal[2] }}"></span></span>
                                <span class="lb-pct">{{ $row->accuracy }}%</span>
                            </div>
                            <span class="lb-pts">{{ __(':count mata', ['count' => number_format($row->points)]) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Sticky your-rank bar --}}
            @if ($showMyRow && $myRow)
                <div style="position:sticky;bottom:16px;background:#17907B;border-radius:16px;padding:14px 20px;display:flex;align-items:center;gap:14px;box-shadow:0 10px 30px rgba(23,144,123,.35)">
                    <span style="width:32px;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:rgba(255,255,255,.75);text-align:center">{{ $myRow->rank }}</span>
                    <span style="width:38px;height:38px;border-radius:50%;background:var(--wl-surface);display:grid;place-items:center;font-family:'Geist',sans-serif;font-size:14px;font-weight:800;color:#17907B;flex-shrink:0">{{ $initial($me->name) }}</span>
                    <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:#fff">{{ $me->name }} {{ __('(Anda)') }}</span>
                        <span style="font-size:12px;color:rgba(255,255,255,.8)">{{ __(':count kuiz', ['count' => $myRow->quizzes]) }} · {{ $myRow->accuracy }}% {{ __('purata') }}</span>
                    </div>
                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:#fff">{{ __(':count mata', ['count' => number_format($myRow->points)]) }}</span>
                </div>
            @endif
        @endif
    </div>
</x-student-layout>
