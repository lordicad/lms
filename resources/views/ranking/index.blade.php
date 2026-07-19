<x-student-layout :title="__('Papan Ranking')">
    @php($me = auth()->user())
    @php($podiumRows = $top->take(3)->values())
    @php($listRows = $top->slice(3)->values())
    {{-- Podium order on screen: 2nd · 1st · 3rd, with 1st raised. --}}
    @php($podium = collect([
        ['row' => $podiumRows[1] ?? null, 'medal' => '🥈', 'ring' => 'ring-ink-2/30',  'tall' => false],
        ['row' => $podiumRows[0] ?? null, 'medal' => '🥇', 'ring' => 'ring-warn/50',    'tall' => true],
        ['row' => $podiumRows[2] ?? null, 'medal' => '🥉', 'ring' => 'ring-danger/40',  'tall' => false],
    ])->filter(fn ($p) => $p['row']))

    <div class="mx-auto max-w-3xl space-y-6">
        <header class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
            <h1 class="text-[22px] font-extrabold text-ink">{{ __('Papan Ranking') }}</h1>
            <span class="text-sm text-ink-2">{{ $grade?->name ?? __('tahun anda') }}</span>
        </header>

        {{-- Subject filter. A grouped <select>, not tabs: 27 subjects across 5 categories. --}}
        <form method="GET" action="{{ route('ranking.index') }}" class="flex items-center gap-2.5">
            <label for="subjek" class="shrink-0 text-[13.5px] font-bold text-ink-2">{{ __('Subjek:') }}</label>
            <select id="subjek" name="subjek"
                    class="min-h-[44px] rounded-full border border-line bg-surface px-4 text-[14px] font-bold text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/25"
                    onchange="this.form.submit()">
                <option value="">{{ __('Keseluruhan') }}</option>
                @foreach ($subjects->groupBy('category') as $category => $group)
                    <optgroup label="{{ \App\Models\Subject::categoryLabel($category) }}">
                        @foreach ($group as $option)
                            <option value="{{ $option->slug }}" @selected($subject?->id === $option->id)>{{ $option->displayName() }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn-secondary btn-sm">{{ __('Tapis') }}</button></noscript>
        </form>

        @if ($top->isEmpty())
            <x-empty emoji="🏆" :title="__('Belum ada ranking')"
                     :text="__('Belum ada murid yang menyelesaikan kuiz :subject dalam :grade. Jadilah yang pertama!', ['subject' => $subject ? $subject->name : '', 'grade' => $grade?->name ?? __('tahun anda')])">
                <a href="{{ route('belajar.index') }}" class="btn-primary">{{ __('Cari Kuiz') }}</a>
            </x-empty>
        @else
            {{-- Podium — top 3 --}}
            <div class="grid grid-cols-3 items-end gap-3 sm:gap-4">
                @foreach ($podium as $p)
                    @php($row = $p['row'])
                    @php($isMe = $row->student->id === $me->id)
                    <div class="relative flex flex-col items-center gap-2 rounded-panel border border-line bg-surface px-2 pb-5 text-center shadow-card {{ $p['tall'] ? 'pt-10' : 'pt-6' }} {{ $isMe ? 'ring-2 ring-brand' : '' }}">
                        <span class="absolute -top-3.5 text-[26px]" aria-hidden="true">{{ $p['medal'] }}</span>
                        <span class="ring-4 {{ $p['ring'] }} rounded-full">
                            <x-avatar :user="$row->student" size="lg" />
                        </span>
                        <span class="line-clamp-1 text-[15px] font-extrabold text-ink">{{ \Illuminate\Support\Str::before($row->student->name, ' ') }}</span>
                        <span class="text-[12.5px] text-ink-2">{{ __(':count kuiz', ['count' => $row->quizzes]) }}</span>
                        <span class="chip bg-brand-soft text-[13px] font-extrabold text-brand">{{ $row->points }} {{ __('mata') }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Ranks 4–10 --}}
            @if ($listRows->isNotEmpty())
                <div class="overflow-hidden rounded-panel border border-line bg-surface shadow-card">
                    @foreach ($listRows as $row)
                        @php($isMe = $row->student->id === $me->id)
                        <div class="flex items-center gap-3.5 border-b border-line px-5 py-3 last:border-b-0 {{ $isMe ? 'bg-brand-soft' : '' }}">
                            <span class="w-8 shrink-0 text-center text-[14px] font-extrabold tabular-nums text-ink-2">{{ $row->rank }}</span>
                            <x-avatar :user="$row->student" size="sm" />
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[14.5px] font-extrabold text-ink">
                                    {{ $row->student->name }}
                                    @if ($isMe)<span class="text-brand">{{ __('(Anda)') }}</span>@endif
                                </span>
                                <span class="block text-[12px] text-ink-2">{{ __(':quizzes kuiz · :accuracy% purata', ['quizzes' => $row->quizzes, 'accuracy' => $row->accuracy]) }}</span>
                            </span>
                            <span class="shrink-0 text-[14.5px] font-extrabold text-brand">{{ $row->points }} {{ __('mata') }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- The student's own row, pinned, when they sit outside the top 10. --}}
            @if ($showMyRow && $myRow)
                <div class="sticky bottom-4 flex items-center gap-3.5 rounded-panel bg-brand px-5 py-3.5 text-on-brand shadow-hero">
                    <span class="w-8 shrink-0 text-center text-[14px] font-extrabold tabular-nums text-on-brand/75">{{ $myRow->rank }}</span>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-on-brand text-[13px] font-extrabold text-brand">{{ $me->initials() }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-[14.5px] font-extrabold">{{ $me->name }} {{ __('(Anda)') }}</span>
                        <span class="block text-[12px] text-on-brand/80">{{ __(':quizzes kuiz · :accuracy% purata', ['quizzes' => $myRow->quizzes, 'accuracy' => $myRow->accuracy]) }}</span>
                    </span>
                    <span class="shrink-0 text-[14.5px] font-extrabold">{{ $myRow->points }} {{ __('mata') }}</span>
                </div>
            @elseif (! $myRow)
                <div class="rounded-panel border border-line bg-surface p-5 text-center shadow-card">
                    <p class="font-bold text-ink">{{ __('Anda belum ada mata :subject.', ['subject' => $subject ? 'untuk '.$subject->name : '']) }}</p>
                    <p class="mt-1 text-ink-2">{{ __('Selesaikan satu kuiz untuk masuk ke dalam ranking.') }}</p>
                    <a href="{{ route('belajar.index') }}" class="btn-primary mt-4">{{ __('Cari Kuiz') }}</a>
                </div>
            @endif
        @endif

        <p class="text-center text-[13px] text-ink-2">
            {{ __('Hanya percubaan pertama setiap kuiz dikira. Latihan semula tidak menambah mata.') }}
        </p>
    </div>
</x-student-layout>
