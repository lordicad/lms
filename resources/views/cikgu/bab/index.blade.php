<x-cikgu-layout
    :title="__('Bab')"
    :heading="__('Bab')"
    :sub="__('Bab dikongsi oleh semua guru mengikut sukatan Kurikulum 2027. Pilih Tahun dan Subjek, kemudian buka sesebuah Bab untuk melihat kandungan yang anda muat naik.')">

    @php
        $slugLevels = $subjects->mapWithKeys(fn ($option) => [
            $option->slug => array_values($availability[$option->id] ?? []),
        ]);
    @endphp

    <div style="display:flex;flex-direction:column;gap:18px;max-width:860px">
        {{-- Pick a Tahun then a Subject (order per brief §1). --}}
        <form method="GET" action="{{ route('cikgu.bab.index') }}"
              class="tp-toolbar"
              x-ref="form"
              x-data="babFilter({
                  subject: @js($subject?->slug),
                  grade: {{ $grade?->level ?? 'null' }},
                  availability: @js($slugLevels),
              })">
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

            <noscript>
                <button type="submit" class="tp-btn-ghost">{{ __('Papar') }}</button>
            </noscript>
        </form>

        @if ($subject && $grade)
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ $subject->name }}. {{ $grade->name }}</h2>

            @if ($chapters->isEmpty())
                <div class="tp-empty">
                    <span style="font-size:30px">📚</span>
                    <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada bab') }}</h3>
                    <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:420px">{{ __('Tiada bab untuk :subject :grade lagi.', ['subject' => $subject->name, 'grade' => $grade->name]) }}</p>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($chapters as $chapter)
                        <a href="{{ route('cikgu.bab.show', $chapter) }}" class="tp-listcard" style="text-decoration:none;{{ $chapter->is_active ? '' : 'opacity:.7' }}">
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

                            <span class="tp-btn-ghost" style="flex-shrink:0" aria-hidden="true">👁 {{ __('Lihat') }}</span>
                            <span class="sr-only">{{ __('Lihat Bab :number: :title', ['number' => $chapter->number, 'title' => $chapter->title]) }}</span>
                        </a>
                    @endforeach
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
