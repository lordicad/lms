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
                    'cardBg' => 'linear-gradient(180deg,#FEFBF1,#FBEFC9)', 'cardBorder' => '#F0E1B0',
                    'disc' => '#F4B63F', 'discHi' => '#F9CB63', 'ring' => '#E0A11F', 'num' => '#7A4E10',
                    'ribA' => '#E8912A', 'ribB' => '#CE7C1A',
                    'avBg' => '#DCEFE7', 'avRing' => '#3FAE89', 'avInk' => '#1F7A5E',
                    'pillBg' => '#DCF2EE', 'pillInk' => '#0F7A68', 'sparkle' => '#ECCB78',
                ],
                1 => [
                    'cardBg' => 'linear-gradient(180deg,#F5F8FD,#E7F0FB)', 'cardBorder' => '#D8E5F3',
                    'disc' => '#C9CDD6', 'discHi' => '#E6E9EE', 'ring' => '#A9AFB9', 'num' => '#4A4E57',
                    'ribA' => '#4266B0', 'ribB' => '#34528F',
                    'avBg' => '#DCE9F7', 'avRing' => '#7FB0E0', 'avInk' => '#2E6CA8',
                    'pillBg' => '#DCE9F7', 'pillInk' => '#2E6CA8', 'sparkle' => '#B9CFEA',
                ],
                2 => [
                    'cardBg' => 'linear-gradient(180deg,#FEF5F9,#FBE7F0)', 'cardBorder' => '#F5D9E6',
                    'disc' => '#CD8E52', 'discHi' => '#E0AE7C', 'ring' => '#B0733C', 'num' => '#6B3E18',
                    'ribA' => '#C63C33', 'ribB' => '#A82C24',
                    'avBg' => '#FBE0EC', 'avRing' => '#E89BB8', 'avInk' => '#C0327A',
                    'pillBg' => '#FBE0EC', 'pillInk' => '#C0327A', 'sparkle' => '#F1C3D8',
                ],
            ])
            @php($arrange = collect([
                ['idx' => 1, 'row' => $podiumRows[1] ?? null],
                ['idx' => 0, 'row' => $podiumRows[0] ?? null],
                ['idx' => 2, 'row' => $podiumRows[2] ?? null],
            ])->filter(fn ($p) => $p['row']))

            {{-- Podium: the champion's column is wider, and centre-aligned so its bigger card rises
                 above the two side cards. --}}
            <div style="display:grid;grid-template-columns:1fr 1.2fr 1fr;gap:18px;align-items:center">
                @foreach ($arrange as $p)
                    @php($row = $p['row'])
                    @php($idx = $p['idx'])
                    @php($m = $podiumMeta[$idx])
                    @php($big = $idx === 0)
                    <div style="position:relative;background:{{ $m['cardBg'] }};border:1.5px solid {{ $m['cardBorder'] }};border-radius:24px;padding:{{ $big ? '40px 22px 26px' : '28px 18px 20px' }};display:flex;flex-direction:column;align-items:center;gap:{{ $big ? 10 : 8 }}px;text-align:center;box-shadow:0 12px 30px rgba(46,44,80,.08)">
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
                        <span style="position:absolute;top:{{ $big ? '-25px' : '-19px' }};left:50%;transform:translateX(-50%);width:{{ $big ? 50 : 40 }}px;height:{{ $big ? 59 : 47 }}px;z-index:1">
                            <svg width="{{ $big ? 50 : 40 }}" height="{{ $big ? 59 : 47 }}" viewBox="0 0 44 52" fill="none" style="display:block">
                                <path d="M15 22 L22 22 L18 48 L14.5 43.5 L11 48 Z" fill="{{ $m['ribB'] }}" />
                                <path d="M22 22 L29 22 L33 48 L29.5 43.5 L26 48 Z" fill="{{ $m['ribA'] }}" />
                                <circle cx="22" cy="17" r="13" fill="{{ $m['disc'] }}" stroke="{{ $m['ring'] }}" stroke-width="2" />
                                <circle cx="22" cy="17" r="9.5" fill="none" stroke="{{ $m['discHi'] }}" stroke-width="1.5" />
                            </svg>
                            <span style="position:absolute;left:0;top:{{ $big ? 6 : 3 }}px;width:{{ $big ? 50 : 40 }}px;height:{{ $big ? 28 : 25 }}px;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:{{ $big ? 17 : 14 }}px;color:{{ $m['num'] }}">{{ $idx + 1 }}</span>
                        </span>

                        <span style="margin-top:{{ $big ? '20px' : '13px' }};width:{{ $big ? '78px' : '58px' }};height:{{ $big ? '78px' : '58px' }};border-radius:50%;background:radial-gradient(circle at 50% 30%, #fff, {{ $m['avBg'] }} 78%);display:grid;place-items:center;font-family:'Geist',sans-serif;font-size:{{ $big ? '30px' : '22px' }};font-weight:800;color:{{ $m['avInk'] }};border:3px solid {{ $m['avRing'] }};position:relative;z-index:1">{{ $initial($row->student->name) }}</span>
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:{{ $big ? '18px' : '15px' }};color:var(--wl-ink);position:relative;z-index:1">{{ \Illuminate\Support\Str::before($row->student->name, ' ') }}</span>
                        <span style="font-size:{{ $big ? '13px' : '12.5px' }};color:var(--wl-muted);position:relative;z-index:1">{{ __(':count kuiz', ['count' => $row->quizzes]) }}</span>
                        <span style="margin-top:{{ $big ? '4px' : '2px' }};background:{{ $m['pillBg'] }};color:{{ $m['pillInk'] }};border-radius:999px;padding:{{ $big ? '7px 20px' : '5px 16px' }};font-family:'Geist',sans-serif;font-size:{{ $big ? '15px' : '13.5px' }};font-weight:800;position:relative;z-index:1">{{ __(':count mata', ['count' => number_format($row->points)]) }}</span>
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
