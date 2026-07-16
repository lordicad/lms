<x-app-layout :title="__('Pengurusan Bab')">
    @php
        $slugLevels = $subjects->mapWithKeys(fn ($option) => [
            $option->slug => array_values($availability[$option->id] ?? []),
        ]);
    @endphp

    <div class="mx-auto max-w-4xl">
        <header>
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Pengurusan Bab') }}</h1>

            <p class="mt-2 max-w-prose text-ink-2">
                {{ __('Bab dikongsi oleh semua guru. Namakan semula bab supaya sepadan dengan sukatan KSSR sekolah anda, atau tambah bab baharu. Bab yang mengandungi kandungan tidak boleh dipadam.') }}
            </p>
        </header>

        {{-- Pick a Subject and Tahun. Choosing a Subject narrows the Tahun to the ones it is
             offered in under Kurikulum 2027; an unavailable Tahun is disabled. --}}
        <form method="GET" action="{{ route('cikgu.bab.index') }}" class="mt-6 flex flex-wrap items-end gap-3"
              x-ref="form"
              x-data="babFilter({
                  subject: @js($subject?->slug),
                  grade: {{ $grade?->level ?? 'null' }},
                  availability: @js($slugLevels),
              })">
            <div>
                <label for="subjek" class="label mb-1">{{ __('Subjek') }}</label>

                <select id="subjek" name="subjek" class="input min-h-[44px] py-2"
                        x-model="subject" @change="onSubjectChange()">
                    @foreach ($subjects as $option)
                        <option value="{{ $option->slug }}" @selected($subject?->id === $option->id)>
                            {{ $option->icon }} {{ $option->displayName() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="tahun" class="label mb-1">{{ __('Tahun') }}</label>

                <select id="tahun" name="tahun" class="input min-h-[44px] py-2"
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
                <button type="submit" class="btn-secondary btn-sm">{{ __('Papar') }}</button>
            </noscript>
        </form>

        @if ($subject && $grade)
            <section class="mt-8" style="--sc: {{ $subject->rgb }}">
                <h2 class="mb-4 text-xl font-extrabold text-ink">
                    {{ $subject->name }}. {{ $grade->name }}
                </h2>

                @unless ($isOffered)
                    <div class="alert-warn mb-4">
                        <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0" />
                        <div>
                            {{ __(':subject tidak ditawarkan untuk :grade dalam Kurikulum 2027. Anda tidak boleh menambah bab baharu di sini. Bab lama yang masih mengandungi kandungan ditandakan tidak aktif — sila pindahkan kandungannya ke Tahun yang betul.', ['subject' => $subject->name, 'grade' => $grade->name]) }}
                        </div>
                    </div>
                @endunless

                @if ($chapters->isEmpty())
                    @if ($isOffered)
                        <x-empty emoji="📚" :title="__('Belum ada bab')"
                                 :text="__('Tambah bab pertama untuk :subject :grade menggunakan borang di bawah.', ['subject' => $subject->name, 'grade' => $grade->name])" />
                    @endif
                @else
                    <ul class="space-y-2">
                        @foreach ($chapters as $chapter)
                            @php($used = $chapter->lessons_count + $chapter->materials_count + $chapter->quizzes_count)

                            <li class="card flex flex-wrap items-center gap-4 p-4 {{ $chapter->is_active ? '' : 'opacity-70' }}">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-control bg-subject-wash font-extrabold text-subject-ink">
                                    {{ $chapter->number }}
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2 font-extrabold text-ink">
                                        {{ $chapter->title }}

                                        @unless ($chapter->is_active)
                                            <span class="chip bg-warn-soft text-warn">{{ __('Tidak aktif') }}</span>
                                        @endunless
                                    </span>

                                    <span class="mt-0.5 flex flex-wrap gap-x-3 text-sm text-ink-2">
                                        <span>🎬 {{ $chapter->lessons_count }}</span>
                                        <span>📄 {{ $chapter->materials_count }}</span>
                                        <span>📝 {{ $chapter->quizzes_count }}</span>
                                    </span>
                                </span>

                                <span class="flex shrink-0 items-center gap-2">
                                    <a href="{{ route('cikgu.bab.edit', $chapter) }}" class="btn-secondary btn-sm">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                        {{ __('Namakan Semula') }}
                                    </a>

                                    @if ($used === 0)
                                        <form method="POST" action="{{ route('cikgu.bab.destroy', $chapter) }}"
                                              onsubmit='return confirm(@js(__("Padam Bab :number: :title?", ["number" => $chapter->number, "title" => $chapter->title])))'>
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn-ghost btn-sm text-danger hover:bg-danger-soft">
                                                <x-icon name="trash" class="h-4 w-4" />
                                                <span class="sr-only">{{ __('Padam') }} Bab {{ $chapter->number }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="chip bg-surface-2 text-ink-2" title="{{ __('Bab ini mengandungi kandungan') }}">
                                            {{ __(':count kandungan', ['count' => $used]) }}
                                        </span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                {{-- Add a Bab, only where the pair is offered. The number is the next in sequence. --}}
                @if ($isOffered)
                    <div class="card card-pad mt-6">
                        <h3 class="text-lg font-extrabold text-ink">{{ __('Tambah') }} Bab {{ $nextNumber }}</h3>

                        <form method="POST" action="{{ route('cikgu.bab.store') }}" class="mt-4 space-y-4">
                            @csrf

                            <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                            <input type="hidden" name="grade_id" value="{{ $grade->id }}">

                            <div>
                                <label for="new-title" class="label">{{ __('Tajuk bab') }}</label>

                                <input id="new-title" name="title" type="text" value="{{ old('title') }}"
                                       required class="input" placeholder="{{ __('Contoh: Nombor Bulat Hingga 1000') }}"
                                       @error('title') aria-invalid="true" @enderror>

                                @error('title')
                                    <p class="field-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="new-description" class="label">{{ __('Penerangan (pilihan)') }}</label>

                                <textarea id="new-description" name="description" rows="2"
                                          class="input py-3">{{ old('description') }}</textarea>
                            </div>

                            <button type="submit" class="btn-primary">
                                <x-icon name="plus" class="h-5 w-5" />
                                {{ __('Tambah Bab') }}
                            </button>
                        </form>
                    </div>
                @endif
            </section>
        @endif
    </div>

    @push('scripts')
        <script>
            function babFilter({ subject, grade, availability }) {
                return {
                    subject,
                    grade,
                    availability,

                    levelAvailable(level) {
                        return (this.availability[this.subject] ?? []).includes(level);
                    },

                    onSubjectChange() {
                        const levels = this.availability[this.subject] ?? [];

                        // Jump to the first Tahun this subject is offered in, if the current one is not.
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
</x-app-layout>
