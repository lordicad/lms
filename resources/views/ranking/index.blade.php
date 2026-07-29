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
            {{-- Each rank: a gold/silver/bronze medal + a soft card tint; the avatar and points pill
                 keep the green/blue/pink palette. Rendered 2 · 1 · 3 with the champion raised. --}}
            @php($podiumMeta = [
                0 => ['cardBg' => '#FCF6E6', 'cardBorder' => '#F0E2BC', 'disc' => '#F4B63F', 'discHi' => '#F9CB63', 'ring' => '#E0A11F', 'ribA' => '#E89A2E', 'ribB' => '#D9861C', 'num' => '#8A5A12', 'sparkle' => '#E7C877'],
                1 => ['cardBg' => '#EEF2F8', 'cardBorder' => '#DBE4F0', 'disc' => '#C9CDD6', 'discHi' => '#E6E9EE', 'ring' => '#A9AFB9', 'ribA' => '#BABFC8', 'ribB' => '#A7ADB7', 'num' => '#5A5F68', 'sparkle' => '#C6CDD8'],
                2 => ['cardBg' => '#FBEEF3', 'cardBorder' => '#F3D9E3', 'disc' => '#D69A5F', 'discHi' => '#E7BB8B', 'ring' => '#BC8146', 'ribA' => '#C98A50', 'ribB' => '#B0733C', 'num' => '#7A4A1E', 'sparkle' => '#EFBFD5'],
            ])
            @php($arrange = collect([
                ['idx' => 1, 'row' => $podiumRows[1] ?? null],
                ['idx' => 0, 'row' => $podiumRows[0] ?? null],
                ['idx' => 2, 'row' => $podiumRows[2] ?? null],
            ])->filter(fn ($p) => $p['row']))

            {{-- Podium --}}
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;align-items:end">
                @foreach ($arrange as $p)
                    @php($row = $p['row'])
                    @php($idx = $p['idx'])
                    @php($pal = $palette[$idx])
                    @php($m = $podiumMeta[$idx])
                    @php($big = $idx === 0)
                    <div style="position:relative;overflow:hidden;background:{{ $m['cardBg'] }};border:1.5px solid {{ $m['cardBorder'] }};border-radius:22px;padding:{{ $big ? '34px 18px 22px' : '28px 16px 18px' }};margin-top:{{ $big ? '0' : '26px' }};display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;box-shadow:0 10px 26px rgba(46,44,80,.07)">
                        {{-- Sparkles. --}}
                        <svg width="{{ $big ? 15 : 13 }}" height="{{ $big ? 15 : 13 }}" viewBox="0 0 16 16" fill="{{ $m['sparkle'] }}" style="position:absolute;top:{{ $big ? '34px' : '30px' }};left:16px;opacity:.85"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>
                        <svg width="{{ $big ? 11 : 9 }}" height="{{ $big ? 11 : 9 }}" viewBox="0 0 16 16" fill="{{ $m['sparkle'] }}" style="position:absolute;top:{{ $big ? '28px' : '24px' }};right:18px;opacity:.7"><path d="M8 0c0 4.4-3.6 8-8 8 4.4 0 8 3.6 8 8 0-4.4 3.6-8 8-8-4.4 0-8-3.6-8-8z"/></svg>

                        {{-- Rosette medal: a gold/silver/bronze disc with the rank number, ribbon tails
                             hanging below, overlapping the top edge. --}}
                        <span style="position:absolute;top:{{ $big ? '-22px' : '-18px' }};left:50%;transform:translateX(-50%);width:{{ $big ? 48 : 40 }}px;height:{{ $big ? 57 : 47 }}px;z-index:1">
                            <svg width="{{ $big ? 48 : 40 }}" height="{{ $big ? 57 : 47 }}" viewBox="0 0 44 52" fill="none" style="display:block">
                                <path d="M15 22 L22 22 L18 48 L14.5 43.5 L11 48 Z" fill="{{ $m['ribB'] }}" />
                                <path d="M22 22 L29 22 L33 48 L29.5 43.5 L26 48 Z" fill="{{ $m['ribA'] }}" />
                                <circle cx="22" cy="17" r="13" fill="{{ $m['disc'] }}" stroke="{{ $m['ring'] }}" stroke-width="2" />
                                <circle cx="22" cy="17" r="9.5" fill="none" stroke="{{ $m['discHi'] }}" stroke-width="1.5" />
                            </svg>
                            <span style="position:absolute;left:0;top:{{ $big ? 5 : 3 }}px;width:{{ $big ? 48 : 40 }}px;height:{{ $big ? 28 : 25 }}px;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:{{ $big ? 16 : 14 }}px;color:{{ $m['num'] }}">{{ $idx + 1 }}</span>
                        </span>

                        <span style="margin-top:{{ $big ? '18px' : '13px' }};width:{{ $big ? '74px' : '60px' }};height:{{ $big ? '74px' : '60px' }};border-radius:50%;background:{{ $pal[0] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-size:{{ $big ? '28px' : '22px' }};font-weight:800;color:{{ $pal[1] }};border:3px solid {{ $pal[2] }}">{{ $initial($row->student->name) }}</span>
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:{{ $big ? '17px' : '15px' }};color:var(--wl-ink)">{{ \Illuminate\Support\Str::before($row->student->name, ' ') }}</span>
                        <span style="font-size:12.5px;color:var(--wl-muted)">{{ __(':count kuiz', ['count' => $row->quizzes]) }}</span>
                        <span style="margin-top:2px;background:{{ $pal[0] }};color:{{ $pal[1] }};border-radius:999px;padding:5px 16px;font-family:'Geist',sans-serif;font-size:13.5px;font-weight:800">{{ __(':count mata', ['count' => number_format($row->points)]) }}</span>
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
