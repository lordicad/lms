<x-cikgu-layout
    :title="__('Ranking Murid')"
    :heading="__('Ranking Murid')"
    :sub="__('Ranking penuh semua murid. Mata hanya daripada percubaan pertama setiap kuiz, jadi latihan ulangan tidak menaikkan ranking.')">

    @php
        $palette = [['#DCF2EE','#0F7A68'],['#E4EEF9','#2E6CA8'],['#FBE4ED','#B84A75'],['#FEF0CE','#8A6A12'],['#FDE7E0','#C24936']];
        $cols = '56px minmax(0,2fr) 1fr 1fr 1fr 1fr 1fr';
    @endphp

    <form method="GET" action="{{ route('cikgu.ranking') }}" class="tp-toolbar">
        <div class="tp-field">
            <label for="tahun" class="tp-label">{{ __('Tahun') }}</label>
            <select id="tahun" name="tahun" class="tp-filter-select" style="min-width:130px" onchange="this.form.submit()">
                <option value="">{{ __('Semua tahun') }}</option>
                @foreach ($grades as $option)
                    <option value="{{ $option->level }}" @selected($grade?->id === $option->id)>{{ $option->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="tp-field">
            <label for="subjek" class="tp-label">{{ __('Subjek') }}</label>
            <select id="subjek" name="subjek" class="tp-filter-select" style="min-width:200px" onchange="this.form.submit()">
                <option value="">{{ __('Semua subjek') }}</option>
                @foreach ($subjects->groupBy('category') as $category => $group)
                    <optgroup label="{{ \App\Models\Subject::categoryLabel($category) }}">
                        @foreach ($group as $option)
                            <option value="{{ $option->slug }}" @selected($subject?->id === $option->id)>{{ $option->displayName() }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>

        <div class="tp-field">
            <label for="kuiz" class="tp-label">{{ __('Kuiz') }}</label>
            <select id="kuiz" name="kuiz" class="tp-filter-select" style="min-width:180px" onchange="this.form.submit()">
                <option value="">{{ __('Semua kuiz') }}</option>
                @foreach ($quizzes as $option)
                    <option value="{{ $option->id }}" @selected($quiz?->id === $option->id)>{{ $option->title }}</option>
                @endforeach
            </select>
        </div>

        @if (request()->hasAny(['tahun', 'subjek', 'kuiz']))
            <a href="{{ route('cikgu.ranking') }}" style="min-height:46px;display:inline-flex;align-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-muted-2)">{{ __('Kosongkan') }}</a>
        @endif
    </form>

    @if ($rows->isEmpty())
        <div class="tp-empty">
            <span style="font-size:30px">🏁</span>
            <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada data ranking') }}</h3>
            <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:420px">{{ __('Ranking akan muncul setelah murid menyelesaikan kuiz interaktif yang diterbitkan.') }}</p>
        </div>
    @else
        <div class="tp-card" style="overflow:hidden">
            <div style="overflow-x:auto">
                <div style="min-width:820px">
                    <div style="display:grid;grid-template-columns:{{ $cols }};gap:12px;align-items:center;padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                        <span class="tp-g" style="font-size:12px;font-weight:800;color:var(--tp-muted)">#</span>
                        <span class="tp-g" style="font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Murid') }}</span>
                        <span class="tp-g" style="font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tahun') }}</span>
                        <span class="tp-g" style="font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Mata') }}</span>
                        <span class="tp-g" style="font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Betul') }}</span>
                        <span class="tp-g" style="font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Ketepatan') }}</span>
                        <span class="tp-g" style="font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Kuiz') }}</span>
                    </div>

                    @foreach ($rows as $row)
                        @php($p = $palette[$loop->index % count($palette)])
                        @php($accBg = $row->accuracy >= 70 ? '#DCF2EE' : ($row->accuracy >= 50 ? '#FEF0CE' : '#FDE7E0'))
                        @php($accFg = $row->accuracy >= 70 ? '#0F7A68' : ($row->accuracy >= 50 ? '#8A6A12' : '#C24936'))
                        <div class="tp-row" style="display:grid;grid-template-columns:{{ $cols }};gap:12px;align-items:center;padding:13px 20px">
                            <span style="font-size:15px">
                                @if ($row->rank === 1) 🥇 @elseif ($row->rank === 2) 🥈 @elseif ($row->rank === 3) 🥉 @else {{ $row->rank }} @endif
                            </span>
                            <div style="display:flex;align-items:center;gap:12px;min-width:0">
                                <span style="width:36px;height:36px;border-radius:10px;background:{{ $p[0] }};color:{{ $p[1] }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;flex-shrink:0">{{ $row->student->initials() }}</span>
                                <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $row->student->name }}</span>
                            </div>
                            <span style="font-size:13.5px;font-weight:700;color:var(--tp-muted-2)">{{ $row->student->grade?->name ?? '-' }}</span>
                            <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink)">{{ $row->points }}</span>
                            <span style="font-size:13.5px;font-weight:700;color:var(--tp-muted-2)">{{ $row->correct }}/{{ $row->questions }}</span>
                            <span style="justify-self:start;background:{{ $accBg }};color:{{ $accFg }};border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">{{ $row->accuracy }}%</span>
                            <span style="font-size:13.5px;font-weight:700;color:var(--tp-muted-2)">{{ $row->quizzes }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- total(), not count(): count() would only report the rows on this page. --}}
        <span style="font-size:13px;color:var(--tp-muted)">{{ __(':count murid dalam senarai ini.', ['count' => $rows->total()]) }}</span>

        <div>{{ $rows->links() }}</div>
    @endif
</x-cikgu-layout>
