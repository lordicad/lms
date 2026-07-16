<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="'Bab '.$chapter->number.': '.$chapter->title">
    <div style="--sc: {{ $subject->rgb }}">
        <a href="{{ route('belajar.subjek', ['subject' => $subject->slug, 'grade' => $grade->level]) }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ $subject->name }} {{ $grade->name }}
        </a>

        <header class="mt-4 rounded-card border border-line bg-subject/8 p-6">
            <p class="flex items-center gap-1.5 font-semibold text-subject-ink"><x-subject-icon :subject="$subject" class="h-5 w-5" /> {{ $subject->name }}. {{ $grade->name }}</p>
            <h1 class="mt-1 text-3xl font-extrabold text-ink">Bab {{ $chapter->number }}: {{ $chapter->title }}</h1>

            @if ($chapter->description)
                <p class="mt-3 max-w-prose text-ink-2">{{ $chapter->description }}</p>
            @endif
        </header>

        {{-- Videos --}}
        <section class="mt-8">
            <h2 class="mb-4 text-xl font-extrabold text-ink">{{ __('Video pelajaran') }}</h2>

            @if ($lessons->isEmpty())
                <x-empty icon="inbox" :title="__('Belum ada video untuk bab ini')"
                         :text="__('Cikgu belum memuat naik video untuk bab ini. Sila cuba lagi nanti.')" />
            @else
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($lessons as $lesson)
                        <li>
                            <a href="{{ route('video.show', $lesson) }}"
                               class="card group flex h-full flex-col overflow-hidden transition-shadow hover:shadow-lift">
                                <span class="relative block aspect-video overflow-hidden bg-surface-2">
                                    @if ($lesson->thumbnailUrl())
                                        <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy"
                                             class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-ink-2"
                                              aria-hidden="true"><x-subject-icon :subject="$subject" class="h-10 w-10" /></span>
                                    @endif

                                    <span class="absolute inset-0 flex items-center justify-center bg-ink/0 transition-colors group-hover:bg-ink/25">
                                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-surface/90 text-ink opacity-0 transition-opacity group-hover:opacity-100">
                                            <x-icon name="play" class="h-6 w-6" />
                                        </span>
                                    </span>

                                    @if ($watchedIds->contains($lesson->id))
                                        <span class="chip absolute left-2 top-2 bg-success text-white">
                                            <x-icon name="check" class="h-4 w-4" />
                                            {{ __('Dah tonton') }}
                                        </span>
                                    @endif
                                </span>

                                <span class="flex flex-1 flex-col gap-1 p-4">
                                    <span class="font-extrabold leading-snug text-ink">{{ $lesson->title }}</span>
                                    <span class="text-sm text-ink-2">{{ $lesson->teacher->name }}</span>

                                    <span class="mt-auto flex items-center gap-1.5 pt-2 text-sm text-ink-2">
                                        <x-icon name="eye" class="h-4 w-4" /> {{ $lesson->views_count }} {{ __('tontonan') }}
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Materials --}}
        <section class="mt-10">
            <h2 class="mb-4 text-xl font-extrabold text-ink">{{ __('Bahan sokongan') }}</h2>

            @if ($materials->isEmpty())
                <x-empty icon="file-text" :title="__('Belum ada bahan untuk bab ini')"
                         :text="__('Cikgu belum memuat naik slaid, PDF atau lembaran kerja untuk bab ini.')" />
            @else
                <ul class="grid gap-3 md:grid-cols-2">
                    @foreach ($materials as $material)
                        <li class="card flex items-center gap-4 p-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-control bg-surface-2 text-ink-2" aria-hidden="true"><x-icon :name="$material->iconName()" class="h-6 w-6" /></span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-extrabold text-ink">{{ $material->title }}</span>
                                <span class="block truncate text-sm text-ink-2">
                                    {{ $material->original_name }}. {{ $material->humanSize() }}
                                </span>
                            </span>

                            <a href="{{ route('muat-turun.bahan', $material) }}" class="btn-secondary btn-sm shrink-0">
                                <x-icon name="download" class="h-4 w-4" />
                                {{ __('Muat Turun') }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Quizzes --}}
        <section class="mt-10">
            <h2 class="mb-4 text-xl font-extrabold text-ink">{{ __('Kuiz') }}</h2>

            @if ($quizzes->isEmpty())
                <x-empty icon="quiz" :title="__('Belum ada kuiz untuk bab ini')"
                         :text="__('Tonton video dahulu. Kuiz akan muncul di sini apabila cikgu menyediakannya.')" />
            @else
                <ul class="grid gap-3 md:grid-cols-2">
                    @foreach ($quizzes as $quiz)
                        <li class="card flex flex-wrap items-center gap-4 p-5">
                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="font-extrabold text-ink">{{ $quiz->title }}</span>

                                    @if ($quiz->isFile())
                                        <span class="chip bg-surface-2 text-ink-2">{{ __('Kuiz Bercetak') }}</span>
                                    @elseif ($quiz->duration_minutes)
                                        <span class="chip bg-warn-soft text-warn">
                                            <x-icon name="clock" class="h-4 w-4" />
                                            {{ $quiz->duration_minutes }} {{ __('minit') }}
                                        </span>
                                    @endif
                                </span>

                                @if ($quiz->my_attempts_count > 0)
                                    <span class="mt-1 block text-sm text-ink-2">
                                        {{ __('Anda sudah mencuba :count kali.', ['count' => $quiz->my_attempts_count]) }}
                                    </span>
                                @endif
                            </span>

                            <a href="{{ route('kuiz.intro', $quiz) }}" class="btn-primary btn-sm shrink-0">
                                {{ $quiz->isFile() ? __('Lihat Kuiz') : __('Cuba Kuiz') }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-dynamic-component>
