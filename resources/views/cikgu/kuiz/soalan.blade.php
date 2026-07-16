<x-app-layout :title="__('Soalan:').' '.$quiz->title">
    <div class="mx-auto max-w-3xl" style="--sc: {{ $chapter->subject->rgb }}">
        <a href="{{ route('cikgu.kuiz.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Kuiz Saya') }}
        </a>

        <header class="mt-4">
            <span class="chip bg-subject-wash text-subject-ink">
                {{ $chapter->subject->icon }} {{ $chapter->subject->name }}. {{ $chapter->grade->name }}. Bab {{ $chapter->number }}
            </span>

            <h1 class="mt-2 text-3xl font-extrabold text-ink">{{ $quiz->title }}</h1>
            <p class="mt-1 text-ink-2">{{ __('Bina soalan aneka pilihan. Semua soalan disimpan sekaligus.') }}</p>
        </header>

        @if ($hasAttempts)
            <x-alert type="warn" class="mt-4">
                {{ __('Kuiz ini sudah ada percubaan murid. Menyimpan soalan baharu akan menggantikan semua soalan lama, dan semakan jawapan percubaan lama tidak lagi dapat dipaparkan. Mata dan ranking yang sudah diperoleh murid kekal tidak berubah.') }}
            </x-alert>
        @endif

        @error('questions')
            <x-alert type="danger" class="mt-4">{{ $message }}</x-alert>
        @enderror

        {{--
            The builder lives entirely in Alpine and posts one payload. Server-side validation
            re-checks every rule (radio has exactly one correct answer, checkbox at least one),
            because none of this markup is trustworthy on its own.
        --}}
        <form method="POST" action="{{ route('cikgu.kuiz.soalan.simpan', $quiz) }}" class="mt-6"
              x-data="quizBuilder({{ Js::from([
                  'questions' => old('questions', $questions),
                  'defaults' => config('lms.quiz'),
                  'labels' => [
                      'optionAria' => __('Teks pilihan :letter'),
                      'radioError' => __('Soalan radio mesti ada tepat satu jawapan betul.'),
                      'checkboxError' => __('Soalan checkbox mesti ada sekurang-kurangnya satu jawapan betul.'),
                  ],
              ]) }})"
              @submit="submitting = true">
            @csrf
            @method('PUT')

            <template x-for="(question, qIndex) in questions" :key="question.uid">
                <section class="card card-pad mb-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="font-extrabold text-ink">
                            {{ __('Soalan') }} <span x-text="qIndex + 1"></span>
                        </h2>

                        <div class="flex items-center gap-1">
                            <button type="button" class="btn-ghost btn-sm" @click="moveUp(qIndex)"
                                    :disabled="qIndex === 0">
                                <x-icon name="arrow-left" class="h-4 w-4 rotate-90" />
                                <span class="sr-only">{{ __('Alih ke atas') }}</span>
                            </button>

                            <button type="button" class="btn-ghost btn-sm" @click="moveDown(qIndex)"
                                    :disabled="qIndex === questions.length - 1">
                                <x-icon name="arrow-right" class="h-4 w-4 rotate-90" />
                                <span class="sr-only">{{ __('Alih ke bawah') }}</span>
                            </button>

                            <button type="button" class="btn-ghost btn-sm text-danger hover:bg-danger-soft"
                                    @click="removeQuestion(qIndex)" :disabled="questions.length === 1">
                                <x-icon name="trash" class="h-4 w-4" />
                                <span class="sr-only">{{ __('Padam soalan') }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="label" :for="`q-${question.uid}-text`">{{ __('Teks soalan') }}</label>

                        <textarea :id="`q-${question.uid}-text`"
                                  :name="`questions[${qIndex}][question_text]`"
                                  x-model="question.question_text"
                                  rows="2" required class="input py-3"
                                  placeholder="{{ __('Contoh: Organ manakah yang mengepam darah?') }}"></textarea>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" :for="`q-${question.uid}-type`">{{ __('Jenis jawapan') }}</label>

                            <select :id="`q-${question.uid}-type`"
                                    :name="`questions[${qIndex}][question_type]`"
                                    x-model="question.question_type"
                                    @change="onTypeChange(question)"
                                    class="input">
                                <option value="single">{{ __('Radio (satu jawapan)') }}</option>
                                <option value="multiple">{{ __('Checkbox (banyak jawapan)') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="label" :for="`q-${question.uid}-points`">{{ __('Mata') }}</label>

                            <input :id="`q-${question.uid}-points`"
                                   :name="`questions[${qIndex}][points]`"
                                   x-model.number="question.points"
                                   type="number" min="1" max="100" required class="input">
                        </div>
                    </div>

                    <fieldset class="mt-5">
                        <legend class="label">
                            {{ __('Pilihan jawapan.') }}
                            <span x-show="question.question_type === 'single'">{{ __('Tanda SATU jawapan betul.') }}</span>
                            <span x-show="question.question_type === 'multiple'" x-cloak>
                                {{ __('Tanda semua jawapan betul.') }}
                            </span>
                        </legend>

                        <div class="space-y-2">
                            <template x-for="(option, oIndex) in question.options" :key="option.uid">
                                <div class="flex items-center gap-3 rounded-card border-2 p-3 transition-colors"
                                     :class="option.is_correct ? 'border-success bg-success-soft' : 'border-line'">

                                    {{-- Correct-answer marker. Radio questions allow only one. --}}
                                    <label class="flex shrink-0 cursor-pointer items-center gap-2">
                                        <input :type="question.question_type === 'single' ? 'radio' : 'checkbox'"
                                               :name="`correct-${question.uid}`"
                                               :checked="option.is_correct"
                                               @change="markCorrect(question, oIndex, $event.target.checked)"
                                               class="h-6 w-6 border-line text-success focus:ring-success"
                                               :class="question.question_type === 'single' ? 'rounded-full' : 'rounded'">

                                        <span class="sr-only">{{ __('Tandakan pilihan ini sebagai jawapan betul') }}</span>
                                    </label>

                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-surface-2 text-sm font-extrabold text-ink-2"
                                          x-text="String.fromCharCode(65 + oIndex)"></span>

                                    <input type="text"
                                           :name="`questions[${qIndex}][options][${oIndex}][option_text]`"
                                           x-model="option.option_text"
                                           required
                                           class="input min-h-[44px] flex-1"
                                           :aria-label="labels.optionAria.replace(':letter', String.fromCharCode(65 + oIndex))"
                                           placeholder="{{ __('Teks jawapan') }}">

                                    {{-- The correct flag travels as a real form field, not just a UI state. --}}
                                    <input type="hidden"
                                           :name="`questions[${qIndex}][options][${oIndex}][is_correct]`"
                                           :value="option.is_correct ? 1 : 0">

                                    <button type="button" class="btn-ghost btn-sm shrink-0 text-danger hover:bg-danger-soft"
                                            @click="removeOption(question, oIndex)"
                                            :disabled="question.options.length <= defaults.min_options">
                                        <x-icon name="x" class="h-4 w-4" />
                                        <span class="sr-only">{{ __('Buang pilihan') }}</span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <button type="button" class="btn-ghost btn-sm mt-3"
                                @click="addOption(question)"
                                :disabled="question.options.length >= defaults.max_options">
                            <x-icon name="plus" class="h-4 w-4" />
                            {{ __('Tambah pilihan') }}
                        </button>

                        <p class="field-error" x-show="! isQuestionValid(question)" x-cloak
                           x-text="questionError(question)"></p>
                    </fieldset>
                </section>
            </template>

            <button type="button" class="btn-secondary w-full" @click="addQuestion()">
                <x-icon name="plus" class="h-5 w-5" />
                {{ __('Tambah Soalan') }}
            </button>

            <div class="sticky bottom-4 mt-6">
                <div class="card flex flex-wrap items-center justify-between gap-3 p-4">
                    <p class="text-sm font-bold text-ink-2">
                        <span x-text="questions.length"></span> {{ __('soalan.') }}
                        <span x-text="totalPoints()"></span> {{ __('mata keseluruhan.') }}
                    </p>

                    <div class="flex gap-2">
                        <a href="{{ route('cikgu.kuiz.index') }}" class="btn-secondary btn-sm">{{ __('Batal') }}</a>

                        <button type="submit" class="btn-primary btn-sm" :disabled="! isValid() || submitting">
                            <span x-show="! submitting">{{ __('Simpan Soalan') }}</span>
                            <span x-show="submitting" x-cloak>{{ __('Menyimpan...') }}</span>
                        </button>
                    </div>
                </div>

                <p class="mt-2 text-center text-sm text-danger" x-show="! isValid()" x-cloak>
                    {{ __('Semak semula soalan anda. Setiap soalan radio perlu tepat satu jawapan betul, dan setiap soalan checkbox perlu sekurang-kurangnya satu.') }}
                </p>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            function quizBuilder({ questions, defaults, labels }) {
                let counter = 0;
                const uid = () => `u${++counter}`;

                const blankOption = (text = '', correct = false) => ({
                    uid: uid(),
                    option_text: text,
                    is_correct: correct,
                });

                const blankQuestion = () => ({
                    uid: uid(),
                    question_text: '',
                    question_type: 'single',
                    points: defaults.default_points,
                    options: Array.from({ length: defaults.default_options }, () => blankOption()),
                });

                // Rehydrate whatever the server sent: saved questions, or old() input after a failed save.
                const hydrate = (raw) => (raw ?? []).map((question) => ({
                    uid: uid(),
                    question_text: question.question_text ?? '',
                    question_type: question.question_type ?? 'single',
                    points: Number(question.points ?? defaults.default_points),
                    options: (question.options ?? []).map((option) => blankOption(
                        option.option_text ?? '',
                        option.is_correct === true || option.is_correct === 1 || option.is_correct === '1',
                    )),
                }));

                return {
                    defaults,
                    labels,
                    submitting: false,
                    questions: questions.length ? hydrate(questions) : [blankQuestion()],

                    addQuestion() {
                        this.questions.push(blankQuestion());

                        this.$nextTick(() => {
                            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
                        });
                    },

                    removeQuestion(index) {
                        if (this.questions.length === 1) return;

                        this.questions.splice(index, 1);
                    },

                    moveUp(index) {
                        if (index === 0) return;

                        [this.questions[index - 1], this.questions[index]] =
                            [this.questions[index], this.questions[index - 1]];
                    },

                    moveDown(index) {
                        if (index === this.questions.length - 1) return;

                        [this.questions[index + 1], this.questions[index]] =
                            [this.questions[index], this.questions[index + 1]];
                    },

                    addOption(question) {
                        if (question.options.length >= this.defaults.max_options) return;

                        question.options.push(blankOption());
                    },

                    removeOption(question, index) {
                        if (question.options.length <= this.defaults.min_options) return;

                        question.options.splice(index, 1);
                    },

                    /* Radio: marking one answer clears the others. Checkbox: they stack. */
                    markCorrect(question, index, checked) {
                        if (question.question_type === 'single') {
                            question.options.forEach((option, i) => {
                                option.is_correct = i === index;
                            });

                            return;
                        }

                        question.options[index].is_correct = checked;
                    },

                    /* Switching to radio keeps only the first correct answer. */
                    onTypeChange(question) {
                        if (question.question_type !== 'single') return;

                        let seen = false;

                        question.options.forEach((option) => {
                            if (option.is_correct && ! seen) {
                                seen = true;
                                return;
                            }

                            option.is_correct = false;
                        });
                    },

                    correctCount(question) {
                        return question.options.filter((option) => option.is_correct).length;
                    },

                    isQuestionValid(question) {
                        const correct = this.correctCount(question);

                        return question.question_type === 'single' ? correct === 1 : correct >= 1;
                    },

                    questionError(question) {
                        return question.question_type === 'single'
                            ? 'Soalan radio mesti ada tepat satu jawapan betul.'
                            : 'Soalan checkbox mesti ada sekurang-kurangnya satu jawapan betul.';
                    },

                    isValid() {
                        return this.questions.length > 0
                            && this.questions.every((question) => this.isQuestionValid(question));
                    },

                    totalPoints() {
                        return this.questions.reduce((sum, question) => sum + (Number(question.points) || 0), 0);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
