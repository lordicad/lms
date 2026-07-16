<x-student-layout :title="__('Keputusan:').' '.$quiz->title">
    <div class="mx-auto max-w-2xl" style="--sc: {{ $subject->rgb }}">

        {{-- Score card. Celebrates at 80% and above, encourages otherwise. Never scolds. --}}
        <section class="card card-pad text-center">
            @if ($attempt->isCelebration())
                <p class="text-6xl" aria-hidden="true">🎉</p>
                <h1 class="mt-3 text-3xl font-extrabold text-ink">{{ __('Syabas, :name!', ['name' => Str::before(auth()->user()->name, ' ')]) }}</h1>
                <p class="mt-2 text-ink-2">{{ __('Keputusan yang sangat baik. Teruskan usaha anda.') }}</p>
            @else
                <p class="text-6xl" aria-hidden="true">💪</p>
                <h1 class="mt-3 text-3xl font-extrabold text-ink">{{ __('Kerja yang baik!') }}</h1>
                <p class="mt-2 text-ink-2">{{ __('Semak jawapan di bawah, kemudian cuba lagi sebagai latihan.') }}</p>
            @endif

            <p class="mt-6 text-5xl font-extrabold text-ink">
                {{ $attempt->score }}<span class="text-2xl text-ink-2">/{{ $attempt->max_score }}</span>
            </p>

            <div class="mx-auto mt-4 h-3 max-w-sm overflow-hidden rounded-full bg-subject/15">
                <div class="h-full rounded-full bg-subject-ink" style="width: {{ $attempt->percentage() }}%"></div>
            </div>

            <dl class="mt-6 grid grid-cols-3 gap-3 text-left">
                <div class="rounded-card bg-surface-2 p-4">
                    <dt class="text-sm font-bold text-ink-2">{{ __('Betul') }}</dt>
                    <dd class="text-2xl font-extrabold text-ink">
                        {{ $attempt->correct_count }}/{{ $attempt->question_count }}
                    </dd>
                </div>

                <div class="rounded-card bg-surface-2 p-4">
                    <dt class="text-sm font-bold text-ink-2">{{ __('Ketepatan') }}</dt>
                    <dd class="text-2xl font-extrabold text-ink">{{ $attempt->percentage() }}%</dd>
                </div>

                <div class="rounded-card bg-surface-2 p-4">
                    <dt class="text-sm font-bold text-ink-2">{{ __('Masa') }}</dt>
                    <dd class="text-lg font-extrabold text-ink">{{ $attempt->humanDuration() }}</dd>
                </div>
            </dl>

            @if ($attempt->counts_for_ranking)
                <x-alert type="success" class="mt-6 text-left">
                    {{ __('Ini percubaan pertama anda, jadi') }} {{ $attempt->score }} {{ __('mata dikira untuk ranking.') }}
                </x-alert>
            @else
                <x-alert type="warn" class="mt-6 text-left">
                    {{ __('Ini latihan semula. Markah ini tidak menjejaskan ranking anda.') }}
                </x-alert>
            @endif

            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('kuiz.intro', $quiz) }}" class="btn-secondary">{{ __('Cuba Lagi (Latihan)') }}</a>
                <a href="{{ route('ranking.index') }}" class="btn-primary">
                    <x-icon name="trophy" class="h-5 w-5" />
                    {{ __('Lihat Ranking') }}
                </a>
            </div>
        </section>

        {{-- Per-question review --}}
        <section class="mt-8">
            <h2 class="mb-4 text-xl font-extrabold text-ink">{{ __('Semakan jawapan') }}</h2>

            <ul class="space-y-4">
                @foreach ($questions as $index => $question)
                    {{-- Block form only in this file. Blade's raw-block regex reads an inline
                         @php(...) as the opening of a @php ... @endphp block and swallows
                         everything up to the next @endphp, so the two forms must not be mixed. --}}
                    @php
                        $answer = $answersByQuestion[$question->id] ?? null;
                    @endphp

                    <li class="card card-pad">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-bold text-ink-2">{{ __('Soalan') }} {{ $index + 1 }}</span>

                            @if ($answer?->is_correct)
                                <span class="chip bg-success-soft text-success">
                                    <x-icon name="check" class="h-4 w-4" />
                                    {{ __('Betul.') }} {{ $answer->points_awarded }} {{ __('mata') }}
                                </span>
                            @else
                                <span class="chip bg-danger-soft text-danger">
                                    <x-icon name="x" class="h-4 w-4" />
                                    {{ __('Salah.') }} 0 {{ __('mata') }}
                                </span>
                            @endif
                        </div>

                        <h3 class="mt-2 text-lg font-extrabold leading-snug text-ink">{{ $question->question_text }}</h3>

                        <ul class="mt-4 space-y-2">
                            @foreach ($question->options as $option)
                                @php
                                    $picked = $answer?->selected($option->id) ?? false;
                                    $correct = $option->is_correct;
                                @endphp

                                <li class="flex items-center gap-3 rounded-card border-2 p-3
                                    @if ($correct) border-success bg-success-soft
                                    @elseif ($picked) border-danger bg-danger-soft
                                    @else border-line @endif">

                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-extrabold
                                        @if ($correct) bg-success text-white
                                        @elseif ($picked) bg-danger text-white
                                        @else bg-surface-2 text-ink-2 @endif">
                                        {{ $option->letter() }}
                                    </span>

                                    <span class="flex-1 font-semibold
                                        @if ($correct) text-success @elseif ($picked) text-danger @else text-ink @endif">
                                        {{ $option->option_text }}
                                    </span>

                                    @if ($picked)
                                        <span class="chip shrink-0 {{ $correct ? 'bg-success text-white' : 'bg-danger text-white' }}">
                                            {{ __('Jawapan anda') }}
                                        </span>
                                    @endif

                                    @if ($correct && ! $picked)
                                        <span class="chip shrink-0 bg-success text-white">{{ __('Jawapan betul') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        @if (! $answer || $answer->selected_option_ids === [])
                            <p class="mt-3 text-sm font-semibold text-ink-2">{{ __('Anda tidak menjawab soalan ini.') }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="mt-8 text-center">
            <a href="{{ route('bab.show', $quiz->chapter) }}" class="btn-secondary">
                <x-icon name="arrow-left" class="h-5 w-5" />
                {{ __('Kembali ke Bab') }}
            </a>
        </div>
    </div>
</x-student-layout>
