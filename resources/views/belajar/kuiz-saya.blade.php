<x-student-layout :title="__('Kuiz')">
    <header class="mb-6">
        <h1 class="text-2xl font-extrabold text-ink">{{ __('Kuiz Saya') }}</h1>
        <p class="mt-1 text-ink-2">
            {{ $grade ? __('Semua kuiz untuk :grade — yang sudah dicuba dan yang belum.', ['grade' => $grade->name]) : __('Tahun anda belum ditetapkan.') }}
        </p>
    </header>

    @if ($quizzes->isEmpty())
        <x-empty icon="quiz" :title="__('Belum ada kuiz')"
                 :text="__('Belum ada kuiz untuk Tahun anda. Sila semak semula kemudian.')" />
    @else
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($quizzes as $quiz)
                @php($attempt = $rankedAttempts[$quiz->id] ?? null)

                <li>
                    <a href="{{ route('kuiz.intro', $quiz) }}"
                       class="card card-pad flex h-full flex-col gap-3 transition-shadow hover:shadow-lift"
                       style="--sc: {{ $quiz->chapter->subject->rgb }}">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip bg-subject-wash text-subject-ink">
                                <x-subject-icon :subject="$quiz->chapter->subject" class="h-4 w-4" /> {{ $quiz->chapter->subject->displayName() }}
                            </span>

                            @if ($quiz->isFile())
                                <span class="chip bg-surface-2 text-ink-2">{{ __('Kuiz Bercetak') }}</span>
                            @endif
                        </div>

                        <h3 class="font-extrabold text-ink">{{ $quiz->title }}</h3>

                        <p class="flex flex-wrap items-center gap-x-3 text-sm text-ink-2">
                            <span>Bab {{ $quiz->chapter->number }}</span>
                            @if ($quiz->isInteractive())
                                <span>{{ __(':count soalan', ['count' => $quiz->questions_count]) }}</span>
                            @endif
                        </p>

                        <div class="mt-auto">
                            @if ($attempt)
                                <span class="chip bg-success-soft text-success">
                                    <x-icon name="check" class="h-4 w-4" />
                                    {{ __('Skor: :score/:max', ['score' => $attempt->score, 'max' => $attempt->max_score]) }}
                                </span>
                            @else
                                <span class="chip bg-brand-soft text-brand">{{ __('Belum dicuba') }}</span>
                            @endif
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</x-student-layout>
