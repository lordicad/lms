<x-student-layout :title="__('Kuiz')">
    <header class="mb-6 flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <h1 class="text-[22px] font-extrabold text-ink">{{ __('Kuiz') }}</h1>
        <span class="text-sm text-ink-2">{{ $grade?->name ?? __('Tahun anda belum ditetapkan') }}</span>
    </header>

    @if ($quizzes->isEmpty())
        <x-empty emoji="📝" :title="__('Belum ada kuiz')"
                 :text="__('Belum ada kuiz untuk Tahun anda. Sila semak semula kemudian.')" />
    @else
        @php($done = $quizzes->filter(fn ($q) => $rankedAttempts->has($q->id)))
        @php($recommended = $quizzes->reject(fn ($q) => $rankedAttempts->has($q->id)))

        {{-- Stats strip --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="flex items-center gap-3.5 rounded-panel bg-success-soft p-5">
                <span class="grid h-11 w-11 place-items-center rounded-[14px] bg-surface text-xl">✅</span>
                <div>
                    <p class="text-[22px] font-extrabold text-success">{{ $doneCount }}</p>
                    <p class="text-[12.5px] font-bold text-success">{{ __('Kuiz selesai') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3.5 rounded-panel bg-warn-soft p-5">
                <span class="grid h-11 w-11 place-items-center rounded-[14px] bg-surface text-xl">⭐</span>
                <div>
                    <p class="text-[22px] font-extrabold text-warn">{{ $avgScore !== null ? $avgScore.'%' : '—' }}</p>
                    <p class="text-[12.5px] font-bold text-warn">{{ __('Purata markah') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3.5 rounded-panel p-5" style="background:rgb(var(--c-brand-soft))">
                <span class="grid h-11 w-11 place-items-center rounded-[14px] bg-surface text-xl">🏆</span>
                <div>
                    <p class="text-[22px] font-extrabold text-brand">{{ $rank ? '#'.$rank : '—' }}</p>
                    <p class="text-[12.5px] font-bold text-brand">{{ __('Ranking') }}</p>
                </div>
            </div>
        </div>

        {{-- Completed --}}
        @if ($done->isNotEmpty())
            <section class="mt-8">
                <h2 class="mb-3 text-[17px] font-extrabold text-ink">{{ __('Telah Selesai') }}</h2>
                <div class="overflow-hidden rounded-panel border border-line bg-surface shadow-card">
                    @foreach ($done as $quiz)
                        @php($attempt = $rankedAttempts[$quiz->id])
                        @php($pct = $attempt->percentage())
                        @php($scoreColor = $pct >= 80 ? 'text-success' : ($pct >= 50 ? 'text-warn' : 'text-danger'))
                        @php($barColor = $pct >= 80 ? 'bg-success' : ($pct >= 50 ? 'bg-warn' : 'bg-danger'))
                        <div class="flex flex-wrap items-center gap-3.5 border-b border-line px-5 py-3.5 last:border-b-0" style="--sc: {{ $quiz->chapter->subject->rgb }}">
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-[12px] bg-subject-wash text-lg"><x-subject-emoji :subject="$quiz->chapter->subject" class="text-base" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[14.5px] font-extrabold text-ink">{{ $quiz->title }}</p>
                                <p class="text-[12px] text-ink-2">{{ $quiz->chapter->subject->displayName() }} · Bab {{ $quiz->chapter->number }} · {{ $attempt->completed_at?->translatedFormat('d M') }}</p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <span class="text-[15px] font-extrabold {{ $scoreColor }}">{{ $pct }}%</span>
                                <span class="h-1.5 w-[110px] overflow-hidden rounded-full bg-surface-2">
                                    <span class="block h-full rounded-full {{ $barColor }}" style="width: {{ $pct }}%"></span>
                                </span>
                            </div>
                            <a href="{{ route('kuiz.intro', $quiz) }}" class="btn-secondary btn-sm shrink-0">{{ __('Semak') }}</a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Recommended (not yet attempted) --}}
        @if ($recommended->isNotEmpty())
            <section class="mt-8">
                <h2 class="mb-3 text-[17px] font-extrabold text-ink">{{ __('Kuiz Dicadangkan') }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recommended as $quiz)
                        <div class="flex flex-col gap-3 rounded-panel border border-line bg-surface p-5 shadow-card transition duration-200 ease-smooth hover:-translate-y-0.5 hover:shadow-lift"
                             style="--sc: {{ $quiz->chapter->subject->rgb }}">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full bg-subject-wash px-2.5 py-1 text-[11.5px] font-extrabold text-subject-ink"><x-subject-emoji :subject="$quiz->chapter->subject" class="text-sm" /> {{ $quiz->chapter->subject->displayName() }}</span>
                                <span class="text-[12px] font-bold text-ink-2">Bab {{ $quiz->chapter->number }}</span>
                            </div>
                            <p class="text-[15.5px] font-extrabold text-ink">{{ $quiz->title }}</p>
                            <div class="mt-auto flex items-center gap-3">
                                <span class="text-[12.5px] font-bold text-ink-2">
                                    @if ($quiz->isInteractive())
                                        {{ __(':count soalan', ['count' => $quiz->questions_count]) }}@if ($quiz->duration_minutes) · {{ $quiz->duration_minutes }} {{ __('minit') }}@endif
                                    @else
                                        {{ __('Kuiz Bercetak') }}
                                    @endif
                                </span>
                                <a href="{{ route('kuiz.intro', $quiz) }}" class="btn-primary btn-sm ml-auto shrink-0">{{ $quiz->isFile() ? __('Lihat') : __('Mula Kuiz') }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @endif
</x-student-layout>
