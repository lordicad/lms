<x-cikgu-layout :title="__('Soalan:').' '.$quiz->localizedTitle()"
    :heading="__('Tambah Soalan')"
    heading-icon="quiz"
    :sub="__('Bina soalan aneka pilihan. Semua soalan disimpan serentak.')">

    {{-- Back link keeps its original left position; the content block below is centred in the wide
         column. The "now add questions" message comes from the layout flash, no banner here. --}}
    <a href="{{ route('cikgu.kuiz.edit', $quiz) }}" class="tp-back">← {{ __('Kembali') }}</a>

    <div class="tp-formwrap" style="margin:0 auto;width:100%">
        <div style="display:flex;flex-direction:column;gap:6px">
            <span style="align-self:flex-start;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;display:inline-flex;align-items:center;gap:6px"><x-icon :name="$chapter->subject->iconName()" class="h-[15px] w-[15px]" />{{ $chapter->subject->name }}. {{ $chapter->grade->displayName() }}. {{ __('Bab :n', ['n' => $chapter->number]) }}</span>
            <h2 class="tp-g" style="font-size:24px;font-weight:800;letter-spacing:-.01em;color:var(--tp-ink)">{{ $quiz->title }}</h2>
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
                  'translate' => [
                      'enabled' => $translatorEnabled,
                      'url' => route('cikgu.kuiz.soalan.terjemah', $quiz),
                      'failed' => __('Terjemahan gagal. Sila cuba lagi.'),
                  ],
                  'labels' => [
                      'optionAria' => __('Teks pilihan :letter'),
                      'radioError' => __('Soalan radio mesti ada tepat satu jawapan betul.'),
                      'checkboxError' => __('Soalan checkbox mesti ada sekurang-kurangnya satu jawapan betul.'),
                  ],
              ]) }})"
              @submit="onSubmit($event)">
            @csrf
            @method('PUT')

            @if ($translatorEnabled)
                {{-- Auto-translate the whole quiz BM⇄EN in one click. Fills the editable
                     "Terjemahan" panel under each question so the teacher can review and correct
                     the machine translation before saving. Runs on save too, so it is optional. --}}
                <div class="tp-card" style="border-radius:16px;padding:16px 20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                    <div style="flex:1;min-width:200px;display:flex;flex-direction:column;gap:2px">
                        <span class="tp-g" style="font-size:14px;font-weight:800;color:var(--tp-ink)">{{ __('Terjemahan automatik') }}</span>
                        <span style="font-size:12.5px;color:var(--tp-muted)">{{ __('Terjemah soalan antara Bahasa Melayu dan English. Semak & betulkan sebelum simpan.') }}</span>
                    </div>
                    <span style="font-size:12.5px;font-weight:700;color:#C24936" x-show="translateError" x-cloak x-text="translateError"></span>
                    <button type="button" class="tp-btn tp-btn-sm" @click="translateAll()" :disabled="translating">
                        <span x-show="! translating">✦ {{ __('Terjemah automatik') }}</span>
                        <span x-show="translating" x-cloak>{{ __('Menterjemah...') }}</span>
                    </button>
                </div>
            @endif

            <template x-for="(question, qIndex) in questions" :key="question.uid">
                <div class="tp-panelform" style="padding:26px;gap:24px">
                    <div style="display:flex;align-items:center;gap:12px">
                        <span class="qb-badge" x-text="qIndex + 1"></span>
                        <h3 class="tp-g" style="font-size:16px;font-weight:800;color:var(--tp-ink);flex:1">{{ __('Soalan') }} <span x-text="qIndex + 1"></span></h3>
                        <button type="button" class="tp-icon-action" style="width:36px;height:36px;border:1.5px solid var(--tp-line-2)" @click="moveUp(qIndex)" :disabled="qIndex === 0" title="{{ __('Naik') }}"><x-icon name="arrow-up" class="h-4 w-4" /></button>
                        <button type="button" class="tp-icon-action" style="width:36px;height:36px;border:1.5px solid var(--tp-line-2)" @click="moveDown(qIndex)" :disabled="qIndex === questions.length - 1" title="{{ __('Turun') }}"><x-icon name="arrow-down" class="h-4 w-4" /></button>
                        <button type="button" class="tp-icon-action tp-icon-danger" style="width:36px;height:36px;border:1.5px solid var(--tp-line-2)" @click="removeQuestion(qIndex)" :disabled="questions.length === 1" title="{{ __('Padam') }}">
                            <x-icon name="trash" class="h-4 w-4" />
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
                            <select :id="`q-${question.uid}-type`" :name="`questions[${qIndex}][question_type]`" x-model="question.question_type" @change="onTypeChange(question)" class="tp-select js-styled-select">
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
                            <div class="qb-optrow" :class="{ 'is-correct': option.is_correct }">
                                <label style="display:flex;flex-shrink:0;cursor:pointer;align-items:center">
                                    <input :type="question.question_type === 'single' ? 'radio' : 'checkbox'"
                                           :name="`correct-${question.uid}`" :checked="option.is_correct"
                                           @change="markCorrect(question, oIndex, $event.target.checked)"
                                           style="width:22px;height:22px;accent-color:#17907B;cursor:pointer">
                                    <span class="sr-only">{{ __('Tandakan pilihan ini sebagai jawapan betul') }}</span>
                                </label>

                                <span class="qb-letter" x-text="String.fromCharCode(65 + oIndex)"></span>

                                <input type="text" :name="`questions[${qIndex}][options][${oIndex}][option_text]`" x-model="option.option_text" required
                                       class="qb-optinput"
                                       :aria-label="labels.optionAria.replace(':letter', String.fromCharCode(65 + oIndex))" placeholder="{{ __('Teks jawapan') }}">

                                <input type="hidden" :name="`questions[${qIndex}][options][${oIndex}][is_correct]`" :value="option.is_correct ? 1 : 0">

                                <button type="button" @click="removeOption(question, oIndex)" :disabled="question.options.length <= defaults.min_options"
                                        class="qb-del" title="{{ __('Buang') }}">×</button>
                            </div>
                        </template>

                        <button type="button" @click="addOption(question)" :disabled="question.options.length >= defaults.max_options"
                                class="tp-g" style="align-self:flex-start;display:inline-flex;align-items:center;gap:8px;min-height:40px;border:none;cursor:pointer;border-radius:10px;background:transparent;color:#17907B;font-weight:800;font-size:13.5px;padding:0 4px">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M12 3 21 12 12 21 3 12z"/><line x1="12" y1="9" x2="12" y2="15"/><line x1="9" y1="12" x2="15" y2="12"/></svg>
                            {{ __('Tambah pilihan') }}
                        </button>

                        <span style="font-size:13px;font-weight:700;color:#C24936" x-show="! isQuestionValid(question)" x-cloak x-text="questionError(question)"></span>
                    </fieldset>

                    {{-- Translation review: the auto-translated question + options, editable, so a
                         wrong machine translation is corrected before students see it. Submitted
                         with the question so the toggle can pick it later. --}}
                    <template x-if="translate.enabled">
                        <div style="border-top:1px solid var(--tp-line);padding-top:14px;display:flex;flex-direction:column;gap:10px">
                            <input type="hidden" :name="`questions[${qIndex}][source_locale]`" :value="question.source_locale || ''">

                            <button type="button" @click="question._showT = ! question._showT"
                                    class="tp-g" style="align-self:flex-start;display:inline-flex;align-items:center;gap:6px;border:none;background:transparent;cursor:pointer;color:#2E6CA8;font-weight:800;font-size:13px;padding:0">
                                <span x-text="question._showT ? '▾' : '▸'"></span>
                                {{ __('Terjemahan') }}
                                <span x-show="question.source_locale" x-cloak style="font-weight:700;color:var(--tp-muted)"
                                      x-text="question.source_locale === 'en' ? '· English → Bahasa Melayu' : '· Bahasa Melayu → English'"></span>
                            </button>

                            <div x-show="question._showT" x-cloak style="display:flex;flex-direction:column;gap:12px;background:#F4F7FB;border:1px solid var(--tp-line-2);border-radius:12px;padding:14px">
                                <span x-show="! question.source_locale" x-cloak style="font-size:12.5px;color:var(--tp-muted)">{{ __('Klik "Terjemah automatik" di atas untuk menjana terjemahan.') }}</span>

                                <div class="tp-field">
                                    <label class="tp-label" :for="`q-${question.uid}-ttext`">{{ __('Teks soalan (terjemahan)') }}</label>
                                    <textarea :id="`q-${question.uid}-ttext`" :name="`questions[${qIndex}][question_text_translated]`" x-model="question.question_text_translated"
                                              rows="2" class="tp-textarea" placeholder="{{ __('Terjemahan soalan') }}"></textarea>
                                </div>

                                <template x-for="(option, oIndex) in question.options" :key="`t-${option.uid}`">
                                    <div style="display:flex;align-items:center;gap:10px">
                                        <span style="width:26px;height:26px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:12px;background:#F1F0E8;color:var(--tp-muted-2)" x-text="String.fromCharCode(65 + oIndex)"></span>
                                        <input type="text" :name="`questions[${qIndex}][options][${oIndex}][option_text_translated]`" x-model="option.option_text_translated"
                                               style="flex:1;min-height:40px;border:1.5px solid var(--tp-line-3);border-radius:10px;padding:0 12px;background:var(--tp-input);font-family:'Nunito',sans-serif;font-size:14px;color:var(--tp-ink);min-width:0" placeholder="{{ __('Terjemahan jawapan') }}">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <button type="button" @click="addQuestion()"
                    class="tp-g" style="min-height:52px;cursor:pointer;border-radius:14px;border:1.5px dashed rgba(46,44,80,.2);background:#F1F0E8;color:var(--tp-ink);font-weight:800;font-size:14.5px">+ {{ __('Tambah Soalan') }}</button>

            <div>
                <div class="tp-card" style="border-radius:18px;padding:16px 22px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                    <span style="width:38px;height:38px;border-radius:11px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="quiz" class="h-[19px] w-[19px]" /></span>
                    <span style="font-size:13.5px;font-weight:700;color:var(--tp-muted-2);flex:1;min-width:140px"><span x-text="questions.length"></span> {{ __('soalan.') }} <span x-text="totalPoints()"></span> {{ __('mata keseluruhan.') }}</span>
                    {{-- Cancel posts to the discard endpoint (a separate form, so it is not nested in
                         the questions form): an empty quiz is thrown away, one with questions is kept. --}}
                    <button type="submit" form="kuiz-batal" class="tp-btn-ghost">{{ __('Batal') }}</button>
                    <button type="submit" class="tp-btn tp-btn-sm" :disabled="submitting" style="display:inline-flex;align-items:center;gap:8px">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span x-show="! submitting">{{ __('Simpan Soalan') }}</span>
                        <span x-show="submitting" x-cloak>{{ __('Menyimpan...') }}</span>
                    </button>
                </div>
                {{-- Shown once they try to save an incomplete quiz, rather than a permanently greyed
                     button that never says why. --}}
                <p style="margin:8px 0 0;text-align:center;font-size:13px;font-weight:700;color:#C24936" x-show="showError && ! isValid()" x-cloak>{{ __('Sila tambah sekurang-kurangnya satu soalan yang lengkap sebelum menyimpan. Setiap soalan radio perlu tepat satu jawapan betul, dan setiap soalan checkbox perlu sekurang-kurangnya satu.') }}</p>
            </div>
        </form>

        {{-- Kept outside the questions form (forms cannot nest); the Batal button targets it by id. --}}
        <form id="kuiz-batal" method="POST" action="{{ route('cikgu.kuiz.soalan.batal', $quiz) }}" class="sr-only">
            @csrf
            @method('DELETE')
        </form>
    </div>

    @once
        <style>
            .qb-badge {
                width:30px; height:30px; flex-shrink:0; border-radius:50%; background:#DCF2EE;
                color:#0F7A68; display:grid; place-items:center;
                font-family:'Geist',sans-serif; font-weight:800; font-size:14px;
            }
            .qb-optrow {
                display:flex; align-items:center; gap:12px; border:1.5px solid var(--tp-line-2);
                border-radius:14px; padding:10px 14px; background:var(--tp-surface);
                transition:border-color .15s, background .15s;
            }
            .qb-optrow.is-correct { border-color:#17907B; background:#F4FBF8; }
            .qb-letter {
                width:30px; height:30px; border-radius:50%; flex-shrink:0; display:grid; place-items:center;
                font-family:'Geist',sans-serif; font-weight:800; font-size:13px; background:#DCF2EE; color:#0F7A68;
            }
            .qb-optinput {
                flex:1; min-width:0; min-height:44px; border:1.5px solid var(--tp-line-3); border-radius:10px;
                padding:0 14px; background:var(--tp-input); font-family:'Nunito',sans-serif; font-size:14px; color:var(--tp-ink);
            }
            .qb-optinput:focus { outline:2px solid rgba(23,144,123,.28); outline-offset:1px; border-color:#17907B; }
            .qb-del {
                width:34px; height:34px; border-radius:9px; border:none; cursor:pointer; background:transparent;
                color:#C24936; font-size:18px; flex-shrink:0; transition:background .15s;
            }
            .qb-del:hover:not(:disabled) { background:#FDE7E0; }
            .qb-del:disabled { opacity:.35; cursor:default; }
        </style>
    @endonce

    @push('scripts')
        <script>
            function quizBuilder({ questions, defaults, translate, labels }) {
                let counter = 0;
                const uid = () => `u${++counter}`;
                const blankOption = (text = '', correct = false, translated = '') => ({ uid: uid(), option_text: text, option_text_translated: translated, is_correct: correct });
                const blankQuestion = () => ({
                    uid: uid(), question_text: '', question_type: 'single', points: defaults.default_points,
                    source_locale: '', question_text_translated: '', _showT: false,
                    options: Array.from({ length: defaults.default_options }, () => blankOption()),
                });
                const hydrate = (raw) => (raw ?? []).map((question) => ({
                    uid: uid(),
                    question_text: question.question_text ?? '',
                    source_locale: question.source_locale ?? '',
                    question_text_translated: question.question_text_translated ?? '',
                    _showT: false,
                    question_type: question.question_type ?? 'single',
                    points: Number(question.points ?? defaults.default_points),
                    options: (question.options ?? []).map((option) => blankOption(
                        option.option_text ?? '',
                        option.is_correct === true || option.is_correct === 1 || option.is_correct === '1',
                        option.option_text_translated ?? '',
                    )),
                }));
                return {
                    defaults, labels, translate, submitting: false, showError: false,
                    translating: false, translateError: '',
                    questions: questions.length ? hydrate(questions) : [blankQuestion()],
                    onSubmit(event) {
                        // Block the save and surface the reason instead of silently doing nothing.
                        if (! this.isValid()) {
                            event.preventDefault();
                            this.showError = true;
                            return;
                        }
                        this.submitting = true;
                    },
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
                    csrf() { return document.querySelector('input[name="_token"]')?.value ?? ''; },
                    async translateAll() {
                        if (! this.translate.enabled || this.translating) return;
                        this.translateError = '';
                        this.translating = true;
                        try {
                            const payload = {
                                questions: this.questions.map((q) => ({
                                    question_text: q.question_text,
                                    options: q.options.map((o) => ({ option_text: o.option_text })),
                                })),
                            };
                            const res = await fetch(this.translate.url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                                body: JSON.stringify(payload),
                            });
                            if (! res.ok) {
                                const err = await res.json().catch(() => ({}));
                                throw new Error(err.message || this.translate.failed);
                            }
                            const data = await res.json();
                            (data.items ?? []).forEach((item, i) => {
                                const q = this.questions[i];
                                if (! q) return;
                                q.source_locale = item.source_locale;
                                q.question_text_translated = item.question;
                                q.options.forEach((o, j) => { o.option_text_translated = (item.options ?? [])[j] ?? ''; });
                                q._showT = true;
                            });
                        } catch (e) {
                            this.translateError = e.message || this.translate.failed;
                        } finally {
                            this.translating = false;
                        }
                    },
                };
            }
        </script>
    @endpush
</x-cikgu-layout>
