<x-cikgu-layout
    :title="__('Pengurusan Bab')"
    :heading="__('Bab')"
    :sub="__('Bab dikongsi oleh semua guru. Namakan semula bab mengikut sukatan KSSR sekolah anda, atau tambah bab baru. Bab yang mengandungi kandungan tidak boleh dipadam.')">

    @php
        $slugLevels = $subjects->mapWithKeys(fn ($option) => [
            $option->slug => array_values($availability[$option->id] ?? []),
        ]);
    @endphp

    <div style="display:flex;flex-direction:column;gap:18px;max-width:860px">
        {{-- Pick a Subject and Tahun. --}}
        <form method="GET" action="{{ route('cikgu.bab.index') }}"
              class="tp-toolbar"
              x-ref="form"
              x-data="babFilter({
                  subject: @js($subject?->slug),
                  grade: {{ $grade?->level ?? 'null' }},
                  availability: @js($slugLevels),
              })">
            <div class="tp-field">
                <label for="subjek" class="tp-label">{{ __('Subjek') }}</label>
                <select id="subjek" name="subjek" class="tp-filter-select" style="min-width:220px"
                        x-model="subject" @change="onSubjectChange()">
                    @foreach ($subjects as $option)
                        <option value="{{ $option->slug }}" @selected($subject?->id === $option->id)>
                            {{ $option->icon }} {{ $option->displayName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="tp-field">
                <label for="tahun" class="tp-label">{{ __('Tahun') }}</label>
                <select id="tahun" name="tahun" class="tp-filter-select" style="min-width:150px"
                        x-model.number="grade" @change="$refs.form.submit()">
                    @foreach ($grades as $option)
                        <option value="{{ $option->level }}" @selected($grade?->id === $option->id)
                                :disabled="! levelAvailable({{ $option->level }})">
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <noscript>
                <button type="submit" class="tp-btn-ghost">{{ __('Papar') }}</button>
            </noscript>
        </form>

        @if ($subject && $grade)
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ $subject->name }}. {{ $grade->name }}</h2>

            @unless ($isOffered)
                <div style="display:flex;gap:10px;background:#FEF0CE;border:1px solid rgba(138,106,18,.25);border-radius:14px;padding:14px 18px;font-size:13.5px;color:#8A6A12">
                    <span>ℹ️</span>
                    <div>{{ __(':subject tidak ditawarkan untuk :grade dalam Kurikulum 2027. Anda tidak boleh menambah bab baharu di sini. Bab lama yang masih mengandungi kandungan ditandakan tidak aktif — sila pindahkan kandungannya ke Tahun yang betul.', ['subject' => $subject->name, 'grade' => $grade->name]) }}</div>
                </div>
            @endunless

            @if ($chapters->isEmpty())
                @if ($isOffered)
                    <div class="tp-empty">
                        <span style="font-size:30px">📚</span>
                        <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada bab') }}</h3>
                        <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:420px">{{ __('Tambah bab pertama untuk :subject :grade menggunakan borang di bawah.', ['subject' => $subject->name, 'grade' => $grade->name]) }}</p>
                    </div>
                @endif
            @else
                <div class="tp-list">
                    @foreach ($chapters as $chapter)
                        @php($used = $chapter->lessons_count + $chapter->materials_count + $chapter->quizzes_count)
                        <div class="tp-listcard" style="{{ $chapter->is_active ? '' : 'opacity:.7' }}">
                            <span style="width:40px;height:40px;border-radius:12px;background:#E4EEF9;color:#2E6CA8;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;flex-shrink:0">{{ $chapter->number }}</span>

                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="display:flex;flex-wrap:wrap;align-items:center;gap:8px;font-weight:800;font-size:15px;color:var(--tp-ink)">
                                    {{ $chapter->title }}
                                    @unless ($chapter->is_active)
                                        <span class="tp-tag" style="background:#FEF0CE;color:#8A6A12">{{ __('Tidak aktif') }}</span>
                                    @endunless
                                </span>
                                <div style="display:flex;align-items:center;gap:12px">
                                    <span class="tp-meta">🎬 {{ $chapter->lessons_count }}</span>
                                    <span class="tp-meta">📄 {{ $chapter->materials_count }}</span>
                                    <span class="tp-meta">📝 {{ $chapter->quizzes_count }}</span>
                                </div>
                            </div>

                            <a href="{{ route('cikgu.bab.edit', $chapter) }}" class="tp-btn-ghost" style="flex-shrink:0">✏️ {{ __('Sunting') }}</a>

                            @if ($used === 0)
                                <form method="POST" action="{{ route('cikgu.bab.destroy', $chapter) }}" style="flex-shrink:0"
                                      onsubmit='return confirm(@js(__("Padam Bab :number: :title?", ["number" => $chapter->number, "title" => $chapter->title])))'>
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tp-icon-action tp-icon-danger" title="{{ __('Padam') }}">
                                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                        <span class="sr-only">{{ __('Padam') }} Bab {{ $chapter->number }}</span>
                                    </button>
                                </form>
                            @else
                                <span class="tp-tag-neutral" style="flex-shrink:0;padding:5px 13px;font-size:12px" title="{{ __('Bab ini mengandungi kandungan') }}">{{ __(':count kandungan', ['count' => $used]) }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Add a Bab, only where the pair is offered. --}}
            @if ($isOffered)
                <div class="tp-panelform">
                    <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Tambah') }} Bab {{ $nextNumber }}</h2>

                    <form method="POST" action="{{ route('cikgu.bab.store') }}" style="display:flex;flex-direction:column;gap:16px">
                        @csrf
                        <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                        <input type="hidden" name="grade_id" value="{{ $grade->id }}">

                        <div class="tp-field">
                            <label for="new-title" class="tp-label">{{ __('Tajuk bab') }}</label>
                            <input id="new-title" name="title" type="text" value="{{ old('title') }}" required
                                   class="tp-input" placeholder="{{ __('Contoh: Nombor Bulat Hingga 1000') }}"
                                   @error('title') aria-invalid="true" @enderror>
                            @error('title')
                                <span style="font-size:13px;font-weight:700;color:#C24936">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="tp-field">
                            <label for="new-description" class="tp-label">{{ __('Penerangan (pilihan)') }}</label>
                            <textarea id="new-description" name="description" rows="3" class="tp-textarea">{{ old('description') }}</textarea>
                        </div>

                        <button type="submit" class="tp-btn" style="align-self:flex-start">
                            <x-icon name="plus" class="h-4 w-4" />
                            {{ __('Tambah Bab') }}
                        </button>
                    </form>
                </div>
            @endif
        @endif
    </div>

    @push('scripts')
        <script>
            function babFilter({ subject, grade, availability }) {
                return {
                    subject, grade, availability,
                    levelAvailable(level) {
                        return (this.availability[this.subject] ?? []).includes(level);
                    },
                    onSubjectChange() {
                        const levels = this.availability[this.subject] ?? [];
                        if (! levels.includes(this.grade)) {
                            this.grade = levels[0] ?? null;
                            this.$nextTick(() => this.$refs.form.submit());
                        } else {
                            this.$refs.form.submit();
                        }
                    },
                };
            }
        </script>
    @endpush
</x-cikgu-layout>
