@props([
    'subjects',
    'grades',
    'chapter' => null,     // pre-selected chapter, when editing
    'name' => 'chapter_id',
])

{{--
    Subject -> Tahun -> Bab, three dependent dropdowns.

    Subject and Tahun filter each other using the Kurikulum 2027 availability map embedded below
    (@js, no extra endpoint): picking Sains leaves only Tahun 5–6 selectable, and vice versa. The
    Bab list is then fetched from /api/bab for the chosen pair, which also 422s an unoffered combo,
    so a teacher can never file content into a Bab that is not part of the curriculum. On edit the
    current chapter is pre-selected — even if its pair has since left the syllabus, so the teacher
    can see what to move.
--}}

@php
    $availability = \App\Models\Subject::availabilityMap();

    $subjectData = $subjects->map(fn ($subject) => [
        'id' => $subject->id,
        'name' => $subject->displayName(),
        'icon' => $subject->icon,
        'levels' => array_values($availability[$subject->id] ?? []),
    ])->values();

    $gradeData = $grades->map(fn ($grade) => [
        'id' => $grade->id,
        'level' => $grade->level,
        'name' => $grade->name,
    ])->values();
@endphp

<div x-data="chapterPicker({
        subject: {{ old('subject_id', $chapter?->subject_id) ?: 'null' }},
        grade: {{ old('grade_id', $chapter?->grade_id) ?: 'null' }},
        chapter: {{ old($name, $chapter?->id) ?: 'null' }},
        endpoint: '{{ route('api.bab') }}',
        subjects: @js($subjectData),
        grades: @js($gradeData),
        labels: { loading: @js(__('Memuatkan bab...')), placeholder: @js(__('Pilih bab')) },
     })"
     x-init="init()"
     style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px">

    {{-- Order: Tahun -> Subjek -> Bab (brief §1). The Alpine dependency logic is symmetric, so
         Tahun leads visually while Subject and Tahun still filter each other. --}}
    <div class="tp-field">
        <label for="grade_id" class="tp-label">{{ __('Tahun') }}</label>

        <select id="grade_id" name="grade_id" class="tp-select" x-model.number="grade" @change="onGradeChange()" required>
            <option value="">{{ __('Pilih tahun') }}</option>
            <template x-for="option in availableGrades" :key="option.id">
                <option :value="option.id" x-text="option.name"></option>
            </template>
        </select>
    </div>

    <div class="tp-field">
        <label for="subject_id" class="tp-label">{{ __('Subjek') }}</label>

        <select id="subject_id" name="subject_id" class="tp-select" x-model.number="subject" @change="onSubjectChange()" required>
            <option value="">{{ __('Pilih subjek') }}</option>
            <template x-for="option in availableSubjects" :key="option.id">
                <option :value="option.id" x-text="`${option.icon} ${option.name}`"></option>
            </template>
        </select>
    </div>

    <div class="tp-field">
        <label for="{{ $name }}" class="tp-label">{{ __('Bab') }}</label>

        <select id="{{ $name }}" name="{{ $name }}" class="tp-select" x-model.number="chapter"
                :disabled="loading || ! subject || ! grade" required
                @error($name) aria-invalid="true" @enderror>
            <option value="" x-text="loading ? labels.loading : labels.placeholder">{{ __('Pilih bab') }}</option>

            <template x-for="option in chapters" :key="option.id">
                <option :value="option.id" x-text="option.label"></option>
            </template>
        </select>

        <p class="tp-hint" x-show="! subject || ! grade" x-cloak>{{ __('Pilih subjek dan tahun dahulu.') }}</p>

        <p class="tp-hint" x-show="subject && grade && ! loading && chapters.length === 0" x-cloak>
            {{ __('Tiada bab untuk kombinasi ini. Tambah bab di halaman Bab dahulu.') }}
        </p>

        @error($name)
            <p style="font-size:13px;font-weight:700;color:#C24936">{{ $message }}</p>
        @enderror
    </div>
</div>

@once
    @push('scripts')
        <script>
            function chapterPicker({ subject, grade, chapter, endpoint, subjects, grades, labels }) {
                return {
                    // Start empty so the full <option> lists render first; the saved values are
                    // applied in init() once those options exist (see below).
                    subject: null,
                    grade: null,
                    chapter: null,
                    saved: { subject, grade, chapter },
                    endpoint,
                    subjects,
                    grades,
                    labels,
                    chapters: [],
                    loading: false,

                    init() {
                        // Let sibling fields (e.g. the material "attach to a video" list) react to the
                        // chosen Bab. Harmless where nothing listens (video/quiz forms).
                        this.$watch('chapter', (value) => this.$dispatch('chapter-changed', { chapter: value }));

                        // Apply the saved Subject/Tahun/Bab after Alpine has rendered the x-for
                        // <option>s. A native <select> silently drops a value whose <option> does
                        // not exist yet, which is why editing a video showed empty pickers.
                        this.$nextTick(() => {
                            this.subject = this.saved.subject;
                            this.grade = this.saved.grade;
                            this.chapter = this.saved.chapter;

                            if (this.subject && this.grade) this.reload(true);
                        });
                    },

                    subjectLevels(id) {
                        return this.subjects.find(item => item.id === id)?.levels ?? [];
                    },

                    gradeLevel(id) {
                        return this.grades.find(item => item.id === id)?.level ?? null;
                    },

                    /* Tahun offered for the chosen Subject; the current pick stays visible on edit. */
                    get availableGrades() {
                        if (! this.subject) return this.grades;

                        const levels = this.subjectLevels(this.subject);

                        return this.grades.filter(g => levels.includes(g.level) || g.id === this.grade);
                    },

                    /* Subjects offered in the chosen Tahun; the current pick stays visible on edit. */
                    get availableSubjects() {
                        if (! this.grade) return this.subjects;

                        const level = this.gradeLevel(this.grade);

                        return this.subjects.filter(s => s.levels.includes(level) || s.id === this.subject);
                    },

                    onSubjectChange() {
                        // Drop a Tahun the new Subject does not offer, then reload the Bab list.
                        if (this.grade && ! this.subjectLevels(this.subject).includes(this.gradeLevel(this.grade))) {
                            this.grade = null;
                        }

                        this.reload();
                    },

                    onGradeChange() {
                        if (this.subject && ! this.subjects.find(s => s.id === this.subject)?.levels.includes(this.gradeLevel(this.grade))) {
                            this.subject = null;
                        }

                        this.reload();
                    },

                    reload(keepSelection = false) {
                        const desired = keepSelection ? this.chapter : null;

                        this.chapter = null;
                        this.chapters = [];

                        if (! this.subject || ! this.grade) return;

                        this.loading = true;

                        const url = `${this.endpoint}?subject=${this.subject}&grade=${this.grade}`;

                        fetch(url, { headers: { 'Accept': 'application/json' } })
                            .then(response => response.ok ? response.json() : [])
                            .then(data => {
                                this.chapters = data;

                                // Re-apply the selection only once the <option> elements exist,
                                // otherwise the select has nothing to bind the value to yet.
                                this.$nextTick(() => {
                                    this.chapter = data.some(item => item.id === desired) ? desired : null;
                                });
                            })
                            .catch(() => { this.chapters = []; })
                            .finally(() => { this.loading = false; });
                    },
                };
            }
        </script>
    @endpush
@endonce
