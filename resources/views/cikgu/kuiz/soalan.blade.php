<x-cikgu-layout :title="__('Soalan:').' '.$quiz->title"
    :heading="__('Tambah Soalan')"
    :sub="__('Bina soalan aneka pilihan. Semua soalan disimpan serentak.')">

    <div class="tp-formwrap">
        @unless ($hasAttempts)
            <div style="background:#DCF2EE;border:1px solid rgba(23,144,123,.3);border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:10px">
                <span style="color:#0F7A68;font-size:15px">✓</span>
                <span class="tp-g" style="font-size:13.5px;font-weight:700;color:#0F7A68">{{ __('Kuiz sedia dibina. Sekarang tambah soalan.') }}</span>
            </div>
        @endunless

        <a href="{{ route('cikgu.kuiz.index') }}" class="tp-back">← {{ __('Kuiz Saya') }}</a>

        <div style="display:flex;flex-direction:column;gap:6px">
            <span style="align-self:flex-start;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800">{{ $chapter->subject->icon }} {{ $chapter->subject->name }}. {{ $chapter->grade->name }}. Bab {{ $chapter->number }}</span>
            <h2 class="tp-g" style="font-size:24px;font-weight:800;letter-spacing:-.01em;color:#28293F">{{ $quiz->title }}</h2>
        </div>

        @if ($hasAttempts)
            <div style="display:flex;gap:10px;background:#FEF0CE;border:1px solid rgba(138,106,18,.25);border-radius:14px;padding:14px 18px;font-size:13.5px;color:#8A6A12">
                <span>⚠️</span>
                <div>{{ __('Kuiz ini sudah ada percubaan murid. Menyimpan soalan baharu akan menggantikan semua soalan lama, dan semakan jawapan percubaan lama tidak lagi dapat dipaparkan. Mata dan ranking yang sudah diperoleh murid kekal tidak berubah.') }}</div>
            </div>
        @endif

        @error('questions')
            <div style="display:flex;gap:10px;background:#FDE7E0;border:1px solid rgba(194,73,54,.25);border-radius:14px;padding:14px 18px;font-size:13.5px;color:#C24936">
                <span>⚠️</span><div>{{ $message }}</div>
            </div>
        @enderror

        <form method="POST" action="{{ route('cikgu.kuiz.soalan.simpan', $quiz) }}"
              style="display:flex;flex-direction:column;gap:18px"
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
                <div class="tp-panelform" style="padding:24px">
                    <div style="display:flex;align-items:center;gap:8px">
                        <h3 class="tp-g" style="font-size:16px;font-weight:800;color:#28293F;flex:1">{{ __('Soalan') }} <span x-text="qIndex + 1"></span></h3>
                        <button type="button" class="tp-icon-action" style="width:36px;height:36px" @click="moveUp(qIndex)" :disabled="qIndex === 0" title="{{ __('Naik') }}">↑</button>
                        <button type="button" class="tp-icon-action" style="width:36px;height:36px" @click="moveDown(qIndex)" :disabled="qIndex === questions.length - 1" title="{{ __('Turun') }}">↓</button>
                        <button type="button" class="tp-icon-action tp-icon-danger" style="width:36px;height:36px" @click="removeQuestion(qIndex)" :disabled="questions.length === 1" title="{{ __('Padam') }}">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                        </button>
                    </div>

                    <div class="tp-field">
                        <label class="tp-label" :for="`q-${question.uid}-text`">{{ __('Teks soalan') }}</label>
                        <textarea :id="`q-${question.uid}-text`" :name="`questions[${qIndex}][question_text]`" x-model="question.question_text"
                                  rows="2" required class="tp-textarea" placeholder="{{ __('Contoh: Organ manakah yang mengepam darah?') }}"></textarea>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                        <div class="tp-field">
                            <label class="tp-label" :for="`q-${question.uid}-type`">{{ __('Jenis jawapan') }}</label>
                            <select :id="`q-${question.uid}-type`" :name="`questions[${qIndex}][question_type]`" x-model="question.question_type" @change="onTypeChange(question)" class="tp-select">
                                <option value="single">{{ __('Radio (satu jawapan)') }}</option>
                                <option value="multiple">{{ __('Kotak semak (banyak jawapan)') }}</option>
                            </select>
                        </div>
                        <div class="tp-field">
                            <label class="tp-label" :for="`q-${question.uid}-points`">{{ __('Mata') }}</label>
                            <input :id="`q-${question.uid}-points`" :name="`questions[${qIndex}][points]`" x-model.number="question.points" type="number" min="1" max="100" required class="tp-input">
                        </div>
                    </div>

                    <fieldset style="border:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px">
                        <legend class="tp-label" style="padding:0">
                            {{ __('Pilihan jawapan.') }}
                            <span x-show="question.question_type === 'single'">{{ __('Tanda SATU jawapan betul.') }}</span>
                            <span x-show="question.question_type === 'multiple'" x-cloak>{{ __('Tanda SEMUA jawapan betul.') }}</span>
                        </legend>

                        <template x-for="(option, oIndex) in question.options" :key="option.uid">
                            <div class="tp-optrow" :class="{ 'is-correct': option.is_correct }">
                                <label style="display:flex;flex-shrink:0;cursor:pointer;align-items:center">
                                    <input :type="question.question_type === 'single' ? 'radio' : 'checkbox'"
                                           :name="`correct-${question.uid}`" :checked="option.is_correct"
                                           @change="markCorrect(question, oIndex, $event.target.checked)"
                                           style="width:22px;height:22px;accent-color:#17907B;cursor:pointer">
                                    <span class="sr-only">{{ __('Tandakan pilihan ini sebagai jawapan betul') }}</span>
                                </label>

                                <span style="width:30px;height:30px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;background:#F1F0E8;color:#6C6F87" x-text="String.fromCharCode(65 + oIndex)"></span>

                                <input type="text" :name="`questions[${qIndex}][options][${oIndex}][option_text]`" x-model="option.option_text" required
                                       style="flex:1;min-height:42px;border:1.5px solid rgba(46,44,80,.1);border-radius:10px;padding:0 12px;background:#F6F5F0;font-family:'Nunito',sans-serif;font-size:14px;color:#28293F;min-width:0"
                                       :aria-label="labels.optionAria.replace(':letter', String.fromCharCode(65 + oIndex))" placeholder="{{ __('Teks jawapan') }}">

                                <input type="hidden" :name="`questions[${qIndex}][options][${oIndex}][is_correct]`" :value="option.is_correct ? 1 : 0">

                                <button type="button" @click="removeOption(question, oIndex)" :disabled="question.options.length <= defaults.min_options"
                                        style="width:34px;height:34px;border-radius:9px;border:none;cursor:pointer;background:transparent;color:#C24936;font-size:15px;flex-shrink:0" title="{{ __('Buang') }}">×</button>
                            </div>
                        </template>

                        <button type="button" @click="addOption(question)" :disabled="question.options.length >= defaults.max_options"
                                class="tp-g" style="align-self:flex-start;min-height:40px;border:none;cursor:pointer;border-radius:10px;background:transparent;color:#17907B;font-weight:800;font-size:13.5px;padding:0 8px">+ {{ __('Tambah pilihan') }}</button>

                        <span style="font-size:13px;font-weight:700;color:#C24936" x-show="! isQuestionValid(question)" x-cloak x-text="questionError(question)"></span>
                    </fieldset>
                </div>
            </template>

            <button type="button" @click="addQuestion()"
                    class="tp-g" style="min-height:52px;cursor:pointer;border-radius:14px;border:1.5px dashed rgba(46,44,80,.2);background:#F1F0E8;color:#28293F;font-weight:800;font-size:14.5px">+ {{ __('Tambah Soalan') }}</button>

            <div style="position:sticky;bottom:16px">
                <div class="tp-card" style="border-radius:16px;padding:16px 20px;display:flex;align-items:center;gap:14px">
                    <span style="font-size:13.5px;font-weight:700;color:#6C6F87;flex:1"><span x-text="questions.length"></span> {{ __('soalan.') }} <span x-text="totalPoints()"></span> {{ __('mata keseluruhan.') }}</span>
                    <a href="{{ route('cikgu.kuiz.index') }}" class="tp-btn-ghost">{{ __('Batal') }}</a>
                    <button type="submit" class="tp-btn tp-btn-sm" :disabled="! isValid() || submitting">
                        <span x-show="! submitting">{{ __('Simpan Soalan') }}</span>
                        <span x-show="submitting" x-cloak>{{ __('Menyimpan...') }}</span>
                    </button>
                </div>
                <p style="margin:8px 0 0;text-align:center;font-size:13px;color:#C24936" x-show="! isValid()" x-cloak>{{ __('Semak semula soalan anda. Setiap soalan radio perlu tepat satu jawapan betul, dan setiap soalan checkbox perlu sekurang-kurangnya satu.') }}</p>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            function quizBuilder({ questions, defaults, labels }) {
                let counter = 0;
                const uid = () => `u${++counter}`;
                const blankOption = (text = '', correct = false) => ({ uid: uid(), option_text: text, is_correct: correct });
                const blankQuestion = () => ({
                    uid: uid(), question_text: '', question_type: 'single', points: defaults.default_points,
                    options: Array.from({ length: defaults.default_options }, () => blankOption()),
                });
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
                    defaults, labels, submitting: false,
                    questions: questions.length ? hydrate(questions) : [blankQuestion()],
                    addQuestion() {
                        this.questions.push(blankQuestion());
                        this.$nextTick(() => { window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' }); });
                    },
                    removeQuestion(index) { if (this.questions.length === 1) return; this.questions.splice(index, 1); },
                    moveUp(index) { if (index === 0) return; [this.questions[index - 1], this.questions[index]] = [this.questions[index], this.questions[index - 1]]; },
                    moveDown(index) { if (index === this.questions.length - 1) return; [this.questions[index + 1], this.questions[index]] = [this.questions[index], this.questions[index + 1]]; },
                    addOption(question) { if (question.options.length >= this.defaults.max_options) return; question.options.push(blankOption()); },
                    removeOption(question, index) { if (question.options.length <= this.defaults.min_options) return; question.options.splice(index, 1); },
                    markCorrect(question, index, checked) {
                        if (question.question_type === 'single') { question.options.forEach((option, i) => { option.is_correct = i === index; }); return; }
                        question.options[index].is_correct = checked;
                    },
                    onTypeChange(question) {
                        if (question.question_type !== 'single') return;
                        let seen = false;
                        question.options.forEach((option) => { if (option.is_correct && ! seen) { seen = true; return; } option.is_correct = false; });
                    },
                    correctCount(question) { return question.options.filter((option) => option.is_correct).length; },
                    isQuestionValid(question) { const correct = this.correctCount(question); return question.question_type === 'single' ? correct === 1 : correct >= 1; },
                    questionError(question) { return question.question_type === 'single' ? labels.radioError : labels.checkboxError; },
                    isValid() { return this.questions.length > 0 && this.questions.every((question) => this.isQuestionValid(question)); },
                    totalPoints() { return this.questions.reduce((sum, question) => sum + (Number(question.points) || 0), 0); },
                };
            }
        </script>
    @endpush
</x-cikgu-layout>
