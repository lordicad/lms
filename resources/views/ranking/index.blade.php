<x-student-layout :title="__('Ranking')">
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
            @php($podiumRows = $top->take(3)->values())
            {{-- Per rank: gradient card, a metal medal with its own ribbon colour (amber / blue /
                 red), a matching ringed avatar and points pill, and a sparkle set. --}}
            @php($podiumMeta = [
                0 => [
                    'cardBg' => 'linear-gradient(180deg,#FDF8E7,#FAF0D0)', 'cardBorder' => '#EFDFA6',
                    'disc' => '#F5B93E', 'discHi' => '#FCD277', 'ring' => '#DFA01E', 'num' => '#7C4E12',
                    'ribA' => '#EDA130', 'ribB' => '#D68A1C',
                    'avBg' => '#DBEFE6', 'avRing' => '#43B08C', 'avInk' => '#1E7C5F',
                    'pillBg' => '#D3F0E4', 'pillInk' => '#178A67', 'sparkle' => '#EFCE7C',
                ],
                1 => [
                    'cardBg' => 'linear-gradient(180deg,#F0F4FC,#E7EEF9)', 'cardBorder' => '#D9E3F2',
                    'disc' => '#CBCFD8', 'discHi' => '#EBEEF2', 'ring' => '#A6ACB6', 'num' => '#484D56',
                    'ribA' => '#4A7BC8', 'ribB' => '#3862A6',
                    'avBg' => '#D8E6F7', 'avRing' => '#7CADE0', 'avInk' => '#2E6CA8',
                    'pillBg' => '#D6E5F7', 'pillInk' => '#2E6CA8', 'sparkle' => '#BACFEB',
                ],
                2 => [
                    'cardBg' => 'linear-gradient(180deg,#FDF1F6,#FBE7F0)', 'cardBorder' => '#F4D8E4',
                    'disc' => '#CE8F55', 'discHi' => '#E5B584', 'ring' => '#B0733C', 'num' => '#6B3E18',
                    'ribA' => '#DB4E86', 'ribB' => '#C23A6E',
                    'avBg' => '#FBDDEB', 'avRing' => '#EB9BBB', 'avInk' => '#D6357F',
                    'pillBg' => '#FAD8E7', 'pillInk' => '#D6357F', 'sparkle' => '#F0BFD4',
                ],
            ])
            @php($arrange = collect([
                ['idx' => 1, 'row' => $podiumRows[1] ?? null],
                ['idx' => 0, 'row' => $podiumRows[0] ?? null],
                ['idx' => 2, 'row' => $podiumRows[2] ?? null],
            ])->filter(fn ($p) => $p['row']))

            {{-- Podium: the champion's column is wider, and centre-aligned so its bigger card rises
                 above the two side cards. --}}
            <div style="display:grid;grid-template-columns:1fr 1.18fr 1fr;gap:16px;align-items:center">
                @foreach ($arrange as $p)
                    @php($row = $p['row'])
                    @php($idx = $p['idx'])
                    @php($m = $podiumMeta[$idx])
                    @php($big = $idx === 0)
                    <div style="position:relative;background:{{ $m['cardBg'] }};border:1.5px solid {{ $m['cardBorder'] }};border-radius:22px;padding:{{ $big ? '32px 18px 22px' : '24px 14px 16px' }};display:flex;flex-direction:column;align-items:center;gap:{{ $big ? 9 : 7 }}px;text-align:center;box-shadow:0 10px 26px rgba(46,44,80,.07)">
                        {{-- Faint leaf sprigs in two corners. --}}
                        <svg width="46" height="46" viewBox="0 0 46 46" fill="{{ $m['sparkle'] }}" style="position:absolute;left:4px;bottom:4px;opacity:.4">
                            <path d="M12 34c-2-4 0-8 5-9 1 4-1 8-5 9zM18 28c-2-4 0-8 5-9 1 4-1 8-5 9zM8 38c-2-4 0-8 5-9 1 4-1 8-5 9z" />
                        </svg>
                        <svg width="40" height="40" viewBox="0 0 46 46" fill="{{ $m['sparkle'] }}" style="position:absolute;right:4px;top:6px;opacity:.35;transform:rotate(180deg)">
                            <path d="M12 34c-2-4 0-8 5-9 1 4-1 8-5 9zM18 28c-2-4 0-8 5-9 1 4-1 8-5 9zM8 38c-2-4 0-8 5-9 1 4-1 8-5 9z" />
                        </svg>
                        {{-- Sparkles. --}}
                        <svg width="{{ $big ? 15 : 13 }}" height="{{ $big ? 15 : 13 }}" viewBox="0 0 16 16" fill="{{ $m['sparkle'] }}" style="position:absolute;top:{{ $big ? '42px' : '34px' }};left:{{ $big ? '24px' : '18px' }};opacity:.9"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>
                        <svg width="{{ $big ? 11 : 9 }}" height="{{ $big ? 11 : 9 }}" viewBox="0 0 16 16" fill="{{ $m['sparkle'] }}" style="position:absolute;top:{{ $big ? '34px' : '28px' }};right:{{ $big ? '28px' : '20px' }};opacity:.7"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>

                        {{-- Rosette medal: metal disc + rank number, ribbon tails below, overlapping
                             the top edge. --}}
                        <span style="position:absolute;top:{{ $big ? '-22px' : '-18px' }};left:50%;transform:translateX(-50%);width:{{ $big ? 44 : 36 }}px;height:{{ $big ? 52 : 43 }}px;z-index:1">
                            <svg width="{{ $big ? 44 : 36 }}" height="{{ $big ? 52 : 43 }}" viewBox="0 0 44 52" fill="none" style="display:block">
                                <path d="M15 22 L22 22 L18 48 L14.5 43.5 L11 48 Z" fill="{{ $m['ribB'] }}" />
                                <path d="M22 22 L29 22 L33 48 L29.5 43.5 L26 48 Z" fill="{{ $m['ribA'] }}" />
                                <circle cx="22" cy="17" r="13" fill="{{ $m['disc'] }}" stroke="{{ $m['ring'] }}" stroke-width="2" />
                                <circle cx="22" cy="17" r="9.5" fill="none" stroke="{{ $m['discHi'] }}" stroke-width="1.5" />
                                <ellipse cx="18" cy="12" rx="4.5" ry="3" fill="#fff" opacity=".5" />
                            </svg>
                            <span style="position:absolute;left:0;top:{{ $big ? 4 : 2 }}px;width:{{ $big ? 44 : 36 }}px;height:{{ $big ? 26 : 24 }}px;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:{{ $big ? 15 : 13 }}px;color:{{ $m['num'] }}">{{ $idx + 1 }}</span>
                        </span>

                        <span style="margin-top:{{ $big ? '16px' : '12px' }};width:{{ $big ? '74px' : '58px' }};height:{{ $big ? '74px' : '58px' }};border-radius:50%;background:radial-gradient(circle at 50% 32%, #fff, {{ $m['avBg'] }} 80%);display:grid;place-items:center;font-family:'Geist',sans-serif;font-size:{{ $big ? '28px' : '22px' }};font-weight:800;color:{{ $m['avInk'] }};border:3px solid {{ $m['avRing'] }};position:relative;z-index:1">{{ $initial($row->student->name) }}</span>
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:{{ $big ? '17px' : '15px' }};color:var(--wl-ink);position:relative;z-index:1">{{ \Illuminate\Support\Str::before($row->student->name, ' ') }}</span>
                        <span style="font-size:12.5px;color:var(--wl-muted);position:relative;z-index:1">{{ __(':count kuiz', ['count' => $row->quizzes]) }}</span>
                        <span style="margin-top:2px;background:{{ $m['pillBg'] }};color:{{ $m['pillInk'] }};border-radius:999px;padding:{{ $big ? '6px 18px' : '5px 15px' }};font-family:'Geist',sans-serif;font-size:{{ $big ? '14px' : '13px' }};font-weight:800;position:relative;z-index:1">{{ __(':count mata', ['count' => number_format($row->points)]) }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Ranks 4–10 --}}
            @php($listRows = $top->slice(3)->values())
            @if ($listRows->isNotEmpty())
                <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:18px;overflow:hidden;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                    @foreach ($listRows as $i => $row)
                        @php($pal = $palette[($i + 3) % count($palette)])
                        @php($isMe = $row->student->id === $me->id)
                        <div style="display:flex;align-items:center;gap:14px;padding:13px 20px;border-bottom:1px solid var(--wl-line);{{ $isMe ? 'background:#DCF2EE' : 'background:var(--wl-surface)' }}">
                            <span style="width:32px;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--wl-muted);text-align:center">{{ $row->rank }}</span>
                            <span style="width:38px;height:38px;border-radius:50%;background:{{ $pal[0] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-size:14px;font-weight:800;color:{{ $pal[1] }};flex-shrink:0">{{ $initial($row->student->name) }}</span>
                            <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--wl-ink)">{{ $row->student->name }} @if ($isMe)<span style="color:#0F7A68">{{ __('(Anda)') }}</span>@endif</span>
                                <span style="font-size:12px;color:var(--wl-muted)">{{ __(':count kuiz', ['count' => $row->quizzes]) }} · {{ $row->accuracy }}% {{ __('purata') }}</span>
                            </div>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:#17907B">{{ __(':count mata', ['count' => number_format($row->points)]) }}</span>
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
