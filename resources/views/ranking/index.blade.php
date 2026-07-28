<x-student-layout :title="__('Ranking Kuiz')">
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
        <div style="display:flex;align-items:baseline;gap:12px;flex-wrap:wrap">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;color:var(--wl-ink)">{{ __('Ranking Kuiz') }}</h2>
            <span style="font-size:14px;color:var(--wl-muted)">{{ $grade?->name ?? __('tahun anda') }}</span>
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
            @php($arrange = collect([
                ['row' => $podiumRows[1] ?? null, 'idx' => 1, 'medal' => '🥈', 'pad' => '10px'],
                ['row' => $podiumRows[0] ?? null, 'idx' => 0, 'medal' => '🥇', 'pad' => '30px'],
                ['row' => $podiumRows[2] ?? null, 'idx' => 2, 'medal' => '🥉', 'pad' => '10px'],
            ])->filter(fn ($p) => $p['row']))

            {{-- Podium --}}
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;align-items:end">
                @foreach ($arrange as $p)
                    @php($row = $p['row'])
                    @php($pal = $palette[$p['idx'] % count($palette)])
                    <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:20px;padding:20px 16px {{ $p['pad'] }};display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;box-shadow:0 6px 20px var(--wl-line);position:relative">
                        <span style="position:absolute;top:-14px;font-size:26px">{{ $p['medal'] }}</span>
                        <span style="width:56px;height:56px;border-radius:50%;background:{{ $pal[0] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:{{ $pal[1] }};border:3px solid {{ $pal[2] }}">{{ $initial($row->student->name) }}</span>
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ \Illuminate\Support\Str::before($row->student->name, ' ') }}</span>
                        <span style="font-size:12.5px;color:var(--wl-muted)">{{ __(':count kuiz', ['count' => $row->quizzes]) }}</span>
                        <span style="background:{{ $pal[0] }};color:{{ $pal[1] }};border-radius:999px;padding:4px 14px;font-family:'Geist',sans-serif;font-size:13px;font-weight:800">{{ __(':count mata', ['count' => number_format($row->points)]) }}</span>
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
