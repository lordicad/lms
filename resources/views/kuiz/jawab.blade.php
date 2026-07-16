<x-student-layout :title="$quiz->title">
    {{--
        One question per screen with a progress bar. Chosen over a single long form because a
        9 year old on a phone loses their place in a 20-question scroll.

        Every question stays in the DOM inside one <form>, so nothing is lost when stepping
        back and forth, and a submit posts the whole set at once. Alpine only shows and hides.
        Grading happens entirely on the server: no answer key is ever sent to the browser.
    --}}
    <div class="mx-auto max-w-2xl" style="--sc: {{ $subject->rgb }}"
         x-data="quizRunner({
             total: {{ $questions->count() }},
             secondsLeft: {{ $secondsLeft === null ? 'null' : $secondsLeft }},
             labels: { answered: @js(__('dijawab')) },
         })"
         x-init="start()">

        <header class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-extrabold text-ink">{{ $quiz->title }}</h1>

                @if ($secondsLeft !== null)
                    <span class="chip text-base"
                          :class="secondsLeft <= 60 ? 'bg-danger-soft text-danger' : 'bg-warn-soft text-warn'"
                          role="timer" aria-live="off">
                        <x-icon name="clock" class="h-5 w-5" />
                        <span x-text="clock()">{{ gmdate('i:s', $secondsLeft) }}</span>
                    </span>
                @endif
            </div>

            <div class="mt-4">
                <div class="flex items-center justify-between text-sm font-bold text-ink-2">
                    <span>{{ __('Soalan') }} <span x-text="current + 1">1</span> {{ __('daripada') }} {{ $questions->count() }}</span>
                    <span x-text="answeredCount() + ' ' + labels.answered">0 {{ __('dijawab') }}</span>
                </div>

                <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-subject/15"
                     role="progressbar" aria-label="{{ __('Kemajuan kuiz') }}"
                     :aria-valuenow="current + 1" aria-valuemin="1" aria-valuemax="{{ $questions->count() }}">
                    <div class="h-full rounded-full bg-subject-ink transition-[width] duration-200"
                         :style="`width: ${((current + 1) / total) * 100}%`"></div>
                </div>
            </div>
        </header>

        <noscript>
            <div class="alert-warn mb-6">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    {{ __('JavaScript perlu dihidupkan untuk menjawab kuiz ini. Sila hidupkan JavaScript dalam pelayar anda, kemudian muat semula halaman ini.') }}
                </div>
            </div>
        </noscript>

        <form method="POST" action="{{ route('kuiz.hantar', $attempt) }}" x-ref="form"
              @submit="submitting = true">
            @csrf

            @foreach ($questions as $index => $question)
                {{-- The first question renders without x-cloak so it paints immediately;
                     the rest stay hidden until Alpine takes over. --}}
                <section x-show="current === {{ $index }}" @if ($index > 0) x-cloak @endif
                         class="card card-pad"
                         aria-labelledby="soalan-{{ $question->id }}">

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="chip bg-subject-wash text-subject-ink">{{ $question->points }} {{ __('mata') }}</span>

                        <span class="chip bg-surface-2 text-ink-2">
                            {{ $question->isMultiple() ? __('Pilih semua yang betul') : __('Pilih satu jawapan') }}
                        </span>
                    </div>

                    <h2 id="soalan-{{ $question->id }}" class="mt-4 text-xl font-extrabold leading-snug text-ink">
                        {{ $question->question_text }}
                    </h2>

                    <fieldset class="mt-5 space-y-3">
                        <legend class="sr-only">{{ __('Pilihan jawapan untuk soalan :n', ['n' => $index + 1]) }}</legend>

                        @foreach ($question->options as $option)
                            @php
                                $inputId = "s{$question->id}-o{$option->id}";
                                $inputName = $question->isMultiple()
                                    ? "answers[{$question->id}][]"
                                    : "answers[{$question->id}][]";
                            @endphp

                            <label for="{{ $inputId }}"
                                   class="flex cursor-pointer items-center gap-4 rounded-card border-2 border-line p-4
                                          transition-colors hover:border-subject has-[:checked]:border-subject
                                          has-[:checked]:bg-subject/8">

                                <input id="{{ $inputId }}"
                                       name="{{ $inputName }}"
                                       type="{{ $question->isMultiple() ? 'checkbox' : 'radio' }}"
                                       value="{{ $option->id }}"
                                       @change="touch({{ $index }})"
                                       class="h-6 w-6 shrink-0 border-line text-subject-ink focus:ring-subject
                                              {{ $question->isMultiple() ? 'rounded' : 'rounded-full' }}">

                                <span class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-2 text-sm font-extrabold text-ink-2">
                                        {{ $option->letter() }}
                                    </span>

                                    <span class="font-semibold text-ink">{{ $option->option_text }}</span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                </section>
            @endforeach

            {{-- Step controls --}}
            <nav class="mt-6 flex items-center justify-between gap-3" aria-label="{{ __('Navigasi soalan') }}">
                <button type="button" class="btn-secondary" x-show="current > 0" x-cloak @click="previous()">
                    <x-icon name="arrow-left" class="h-5 w-5" />
                    {{ __('Sebelum') }}
                </button>

                <span x-show="current === 0" aria-hidden="true"></span>

                <button type="button" class="btn-primary" x-show="current < total - 1" @click="next()">
                    {{ __('Seterusnya') }}
                    <x-icon name="arrow-right" class="h-5 w-5" />
                </button>

                <button type="submit" class="btn-primary" x-show="current === total - 1"
                        x-cloak :disabled="submitting">
                    <span x-show="! submitting">{{ __('Hantar Jawapan') }}</span>
                    <span x-show="submitting" x-cloak>{{ __('Menghantar...') }}</span>
                </button>
            </nav>

            {{-- Question map, so a student can jump back to one they skipped. --}}
            <div class="mt-8">
                <p class="mb-2 text-sm font-bold text-ink-2">{{ __('Lompat ke soalan') }}</p>

                <ul class="flex flex-wrap gap-2">
                    @foreach ($questions as $index => $question)
                        <li>
                            <button type="button" @click="go({{ $index }})"
                                    class="flex h-11 w-11 items-center justify-center rounded-control border-2 text-sm font-extrabold transition-colors"
                                    :class="current === {{ $index }}
                                        ? 'border-subject bg-subject-ink text-white'
                                        : (answered[{{ $index }}]
                                            ? 'border-success bg-success-soft text-success'
                                            : 'border-line bg-surface text-ink-2 hover:border-subject')"
                                    :aria-current="current === {{ $index }} ? 'true' : 'false'">
                                {{ $index + 1 }}
                                <span class="sr-only">
                                    {{ __('Soalan') }} {{ $index + 1 }}<span x-show="answered[{{ $index }}]">{{ __(', sudah dijawab') }}</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            function quizRunner({ total, secondsLeft, labels }) {
                return {
                    total,
                    current: 0,
                    secondsLeft,
                    labels,
                    submitting: false,
                    answered: Array(total).fill(false),
                    timer: null,

                    start() {
                        // Restores the "answered" ticks when the browser refills a reloaded form.
                        this.syncAnswered();

                        if (this.secondsLeft === null) return;

                        this.timer = setInterval(() => {
                            this.secondsLeft -= 1;

                            if (this.secondsLeft <= 0) {
                                clearInterval(this.timer);
                                this.autoSubmit();
                            }
                        }, 1000);
                    },

                    /* Time is up: hand in whatever has been answered so far. */
                    autoSubmit() {
                        if (this.submitting) return;

                        this.submitting = true;
                        this.$refs.form.submit();
                    },

                    clock() {
                        const left = Math.max(0, this.secondsLeft ?? 0);
                        const minutes = String(Math.floor(left / 60)).padStart(2, '0');
                        const seconds = String(left % 60).padStart(2, '0');

                        return `${minutes}:${seconds}`;
                    },

                    syncAnswered() {
                        this.$refs.form.querySelectorAll('section').forEach((section, index) => {
                            this.answered[index] = section.querySelectorAll('input:checked').length > 0;
                        });
                    },

                    touch(index) {
                        this.$nextTick(() => this.syncAnswered());
                    },

                    answeredCount() {
                        return this.answered.filter(Boolean).length;
                    },

                    go(index) {
                        this.current = Math.min(Math.max(index, 0), this.total - 1);
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    },

                    next() {
                        this.go(this.current + 1);
                    },

                    previous() {
                        this.go(this.current - 1);
                    },
                };
            }
        </script>
    @endpush
</x-student-layout>
