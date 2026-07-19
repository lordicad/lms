<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="$subject->name.' '.$grade->name">
    <div style="--sc: {{ $subject->rgb }}">
        <a href="{{ route('belajar.index', ['tahun' => $grade->level]) }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Semua subjek') }}
        </a>

        <header class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="flex h-16 w-16 items-center justify-center rounded-card bg-subject-wash"
                      aria-hidden="true"><x-subject-emoji :subject="$subject" class="text-3xl" /></span>

                <div>
                    <h1 class="text-3xl font-extrabold text-ink">{{ $subject->name }}</h1>
                    <p class="text-ink-2">{{ $grade->name }}</p>
                </div>
            </div>

            <form method="GET" class="flex items-end gap-2"
                  action="{{ route('belajar.subjek', ['subject' => $subject->slug, 'grade' => $grade->level]) }}"
                  x-data
                  @change="window.location = '{{ url('/belajar/'.$subject->slug) }}/' + $event.target.value">
                <div>
                    <label for="tahun" class="label mb-1">{{ __('Tukar Tahun') }}</label>

                    <select id="tahun" class="input min-h-[44px] py-2">
                        @foreach ($grades as $option)
                            <option value="{{ $option->level }}" @selected($option->level === $grade->level)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </header>

        <section class="mt-8">
            <h2 class="sr-only">{{ __('Senarai bab') }}</h2>

            @if ($chapters->isEmpty())
                <x-empty icon="book" :title="__('Belum ada bab untuk subjek ini')"
                         :text="__('Cikgu belum menyediakan bab untuk :subject :grade.', ['subject' => $subject->name, 'grade' => $grade->name])" />
            @else
                <ul class="space-y-3">
                    @foreach ($chapters as $chapter)
                        @php
                            $watched = (int) ($watchedByChapter[$chapter->id] ?? 0);
                            $total = $chapter->lessons_count;
                            $percent = $total > 0 ? (int) round($watched / $total * 100) : 0;
                        @endphp

                        <li>
                            <a href="{{ route('bab.show', $chapter) }}"
                               class="card flex flex-wrap items-center gap-4 p-5 transition-shadow hover:shadow-lift">
                                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-control bg-subject-wash font-extrabold text-subject-ink">
                                    {{ $chapter->number }}
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block font-extrabold text-ink">{{ $chapter->title }}</span>

                                    <span class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-sm text-ink-2">
                                        <span class="inline-flex items-center gap-1.5"><x-icon name="video" class="h-4 w-4" /> {{ $chapter->lessons_count }} {{ __('video') }}</span>
                                        <span class="inline-flex items-center gap-1.5"><x-icon name="file" class="h-4 w-4" /> {{ $chapter->materials_count }} {{ __('bahan') }}</span>
                                        <span class="inline-flex items-center gap-1.5"><x-icon name="quiz" class="h-4 w-4" /> {{ $chapter->quizzes_count }} {{ __('kuiz') }}</span>
                                    </span>
                                </span>

                                @if (auth()->user()->isStudent() && $total > 0)
                                    <span class="w-full sm:w-44">
                                        <span class="flex items-center justify-between text-sm font-bold text-ink-2">
                                            <span>{{ __('Ditonton') }}</span>
                                            <span>{{ $watched }}/{{ $total }}</span>
                                        </span>

                                        <span class="mt-1.5 block h-2 w-full overflow-hidden rounded-full bg-surface-2"
                                              role="img"
                                              aria-label="{{ __(':watched daripada :total video telah ditonton', ['watched' => $watched, 'total' => $total]) }}">
                                            <span class="block h-full rounded-full bg-brand transition-[width] duration-300"
                                                  style="width: {{ $percent }}%"></span>
                                        </span>
                                    </span>
                                @endif

                                <x-icon name="chevron-right" class="h-5 w-5 shrink-0 text-ink-2" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-dynamic-component>
