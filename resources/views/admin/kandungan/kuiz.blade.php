<x-app-layout :title="__('Kandungan Kuiz')">
    <header>
        <h1 class="text-3xl font-extrabold text-ink">{{ __('Kandungan Kuiz') }}</h1>
        <p class="mt-1 max-w-prose text-ink-2">
            {{ __('Semua kuiz yang dibina oleh guru, merentas setiap subjek dan Tahun.') }}
        </p>
    </header>

    <section class="mt-8">
        <h2 class="sr-only">{{ __('Ringkasan kuiz') }}</h2>

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="quiz" class="h-5 w-5" />
                    {{ __('Jumlah kuiz') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($totalCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="users" class="h-5 w-5" />
                    {{ __('Jumlah percubaan') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($attemptCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="check-circle" class="h-5 w-5 text-success" />
                    {{ __('Lulus') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($passCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="x-circle" class="h-5 w-5 text-danger" />
                    {{ __('Tidak lulus') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($failCount) }}</dd>
            </div>
        </dl>

        {{-- The app never tells a child they failed, and the ministry has set no pass mark, so
             this threshold is a reporting choice and needs saying out loud on the page. --}}
        <p class="mt-3 text-xs text-ink-2">
            {{ __('Lulus bermaksud percubaan mencapai :percent% atau lebih. Semua percubaan yang selesai dikira, termasuk percubaan ulangan.', ['percent' => \App\Models\QuizAttempt::PASS_AT]) }}
        </p>
    </section>

    {{-- Same Subjek/Tahun filter as the video and bahan lists; each side works alone or together. --}}
    <div class="mt-8">
        <x-cikgu-filters :subjects="$subjects" :grades="$grades" :action="route('admin.kandungan.kuiz')" />
    </div>

    <section class="mt-6"
             x-data="{
                 quiz: null,
                 open(data) { this.quiz = data; document.body.classList.add('overflow-hidden'); },
                 close() { this.quiz = null; document.body.classList.remove('overflow-hidden'); },
             }"
             @keydown.escape.window="close()">
        @if ($quizzes->isEmpty())
            <x-empty icon="quiz" :title="__('Tiada kuiz untuk dipaparkan')"
                     :text="__('Tiada kuiz yang sepadan dengan tapisan ini.')" />
        @else
            <div class="card overflow-x-auto p-2">
                <table class="w-full min-w-[64rem] text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-ink-2">
                            <th class="px-3 py-2 font-semibold">{{ __('Tajuk Kuiz') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Subjek') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Tahun') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Percubaan') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Lulus') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Tidak lulus') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Tarikh Dimuat Naik') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Tindakan') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($quizzes as $quiz)
                            <tr class="border-b border-line/60 last:border-0 hover:bg-surface-2/60">
                                <td class="px-3 py-2">
                                    <span class="flex items-center gap-2">
                                        <span class="font-bold text-ink">{{ $quiz->title }}</span>
                                        {{-- A file quiz is a printable document; it has no questions to show. --}}
                                        @if ($quiz->isFile())
                                            <span class="chip bg-surface-2 text-ink-2">{{ __('Fail') }}</span>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-ink-2">{{ $quiz->teacher?->name }}</span>
                                </td>
                                <td class="px-3 py-2 text-ink-2">{{ $quiz->chapter->subject->displayName() }}</td>
                                <td class="px-3 py-2 text-ink-2">{{ $quiz->chapter->grade->name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($quiz->attempts_count) }}</td>

                                {{-- Fail is the remainder, not its own query: anything completed that is not
                                     a pass is a fail, so the two always add up to Percubaan. --}}
                                <td class="px-3 py-2 text-right tabular-nums">
                                    @if ($quiz->attempts_count > 0)
                                        <span class="font-bold text-success">{{ number_format($quiz->pass_count) }}</span>
                                    @else
                                        <span class="text-ink-2">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums">
                                    @if ($quiz->attempts_count > 0)
                                        <span class="font-bold text-danger">{{ number_format($quiz->attempts_count - $quiz->pass_count) }}</span>
                                    @else
                                        <span class="text-ink-2">—</span>
                                    @endif
                                </td>

                                <td class="px-3 py-2 tabular-nums text-ink-2">{{ $quiz->created_at->translatedFormat('j M Y') }}</td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" class="btn-ghost btn-sm"
                                            @click="open(@js([
                                                'title' => $quiz->title,
                                                'teacher' => $quiz->teacher?->name,
                                                'type' => $quiz->type,
                                                'downloadUrl' => $quiz->isFile() ? route('muat-turun.kuiz', $quiz) : null,
                                                'questions' => $quiz->questions->map(fn ($question) => [
                                                    'text' => $question->question_text,
                                                    'multiple' => $question->isMultiple(),
                                                    'points' => $question->points,
                                                    'options' => $question->options->map(fn ($option) => [
                                                        'letter' => $option->letter(),
                                                        'text' => $option->option_text,
                                                        'correct' => (bool) $option->is_correct,
                                                    ])->all(),
                                                ])->all(),
                                            ]))">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        {{ __('Lihat') }}
                                        <span class="sr-only">{{ $quiz->title }}</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $quizzes->links() }}
            </div>
        @endif

        {{-- x-if, not x-show: closing removes the questions rather than leaving them in the DOM. --}}
        <template x-if="quiz">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 role="dialog" aria-modal="true" :aria-label="quiz.title">
                <div class="absolute inset-0 bg-black/70" @click="close()" aria-hidden="true"></div>

                <div class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-card border border-line bg-surface shadow-hero">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="truncate font-extrabold text-ink" x-text="quiz.title"></h2>
                            <p class="truncate text-xs text-ink-2">
                                <span x-text="quiz.teacher"></span>
                                <span x-show="quiz.questions.length">
                                    &middot; <span x-text="quiz.questions.length"></span> {{ __('soalan') }}
                                </span>
                            </p>
                        </div>

                        <button type="button" class="btn-ghost btn-sm shrink-0" @click="close()" x-init="$el.focus()">
                            <x-icon name="x" class="h-4 w-4" />
                            <span class="sr-only">{{ __('Tutup') }}</span>
                        </button>
                    </div>

                    <div class="overflow-y-auto px-4 py-4">
                        {{-- File quiz: nothing to render, so offer the document instead of an empty box. --}}
                        <template x-if="quiz.type === 'file'">
                            <div class="px-6 py-12 text-center">
                                <p class="font-bold text-ink">{{ __('Kuiz ini ialah fail untuk dicetak.') }}</p>
                                <p class="mx-auto mt-1 max-w-prose text-sm text-ink-2">
                                    {{ __('Ia tiada soalan dalam sistem. Muat turun fail untuk melihatnya.') }}
                                </p>
                                <a :href="quiz.downloadUrl" class="btn-primary btn-sm mt-4">
                                    <x-icon name="download" class="h-4 w-4" />
                                    {{ __('Muat Turun') }}
                                </a>
                            </div>
                        </template>

                        {{-- Interactive quiz the teacher never finished. --}}
                        <template x-if="quiz.type === 'interactive' && ! quiz.questions.length">
                            <div class="px-6 py-12 text-center">
                                <p class="font-bold text-ink">{{ __('Kuiz ini belum ada soalan.') }}</p>
                            </div>
                        </template>

                        <template x-if="quiz.questions.length">
                            <ol class="space-y-4">
                                <template x-for="(question, index) in quiz.questions" :key="index">
                                    <li class="rounded-control border border-line p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="font-bold text-ink">
                                                <span x-text="(index + 1) + '.'"></span>
                                                <span x-text="question.text"></span>
                                            </p>
                                            <span class="chip shrink-0 bg-surface-2 text-ink-2">
                                                <span x-text="question.points"></span> {{ __('mata') }}
                                            </span>
                                        </div>

                                        <p class="mt-1 text-xs text-ink-2"
                                           x-text="question.multiple ? '{{ __('Pilih semua yang betul') }}' : '{{ __('Pilih satu jawapan') }}'"></p>

                                        <ul class="mt-2 space-y-1">
                                            <template x-for="option in question.options" :key="option.letter">
                                                <li class="flex items-start gap-2 text-sm"
                                                    :class="option.correct ? 'font-bold text-success' : 'text-ink-2'">
                                                    <span class="w-5 shrink-0 tabular-nums" x-text="option.letter + '.'"></span>
                                                    <span x-text="option.text"></span>
                                                    <template x-if="option.correct">
                                                        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                    </li>
                                </template>
                            </ol>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </section>
</x-app-layout>
