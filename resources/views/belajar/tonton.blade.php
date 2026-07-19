@php($me = auth()->user())

<x-dynamic-component :component="$me->isTeacher() ? 'app-layout' : 'student-layout'" :title="$lesson->title">
    <div style="--sc: {{ $subject->rgb }}" class="grid gap-8 lg:grid-cols-[1fr_20rem]">
        <div>
            <a href="{{ route('bab.show', $chapter) }}"
               class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
                <x-icon name="arrow-left" class="h-4 w-4" />
                Bab {{ $chapter->number }}: {{ $chapter->title }}
            </a>

            <div class="mt-4">
                <x-player :lesson="$lesson" :progress="$progress" />

                <div class="mt-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <h1 class="text-2xl font-extrabold text-ink md:text-3xl">{{ $lesson->title }}</h1>

                        @if ($me->isStudent())
                            <x-favourite-button :lesson="$lesson" :favourited="$favourited" labelled class="shrink-0" />
                        @endif
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-2">
                        <span class="chip bg-subject-wash text-subject-ink"><x-subject-emoji :subject="$subject" class="text-sm" /> {{ $subject->displayName() }}</span>
                        <span>{{ $grade->name }}</span>
                        <span>{{ $lesson->teacher->name }}</span>

                        @if ($lesson->durationLabel())
                            <span class="flex items-center gap-1.5">
                                <x-icon name="clock" class="h-4 w-4" />
                                {{ $lesson->durationLabel() }}
                            </span>
                        @endif

                        <span class="flex items-center gap-1.5">
                            <x-icon name="eye" class="h-4 w-4" />
                            {{ $lesson->views_count }} {{ __('tontonan') }}
                        </span>

                        @if ($lesson->watchedBy($me))
                            <span class="chip bg-success-soft text-success">
                                <x-icon name="check" class="h-4 w-4" />
                                {{ __('Dah tonton') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            @if ($lesson->description)
                <div class="card card-pad mt-6">
                    <h2 class="text-lg font-extrabold text-ink">{{ __('Tentang video ini') }}</h2>
                    <p class="mt-2 max-w-prose whitespace-pre-line text-ink-2">{{ $lesson->description }}</p>
                </div>
            @endif

            @if ($previous || $next)
                <nav class="mt-6 flex flex-wrap gap-3" aria-label="{{ __('Video lain dalam bab ini') }}">
                    @if ($previous)
                        <a href="{{ route('video.show', $previous) }}" class="btn-secondary flex-1 justify-start">
                            <x-icon name="arrow-left" class="h-5 w-5 shrink-0" />
                            <span class="min-w-0 text-left">
                                <span class="block text-xs font-bold text-ink-2">{{ __('Sebelum ini') }}</span>
                                <span class="block truncate">{{ $previous->title }}</span>
                            </span>
                        </a>
                    @endif

                    @if ($next)
                        <a href="{{ route('video.show', $next) }}" class="btn-secondary flex-1 justify-end">
                            <span class="min-w-0 text-right">
                                <span class="block text-xs font-bold text-ink-2">{{ __('Seterusnya') }}</span>
                                <span class="block truncate">{{ $next->title }}</span>
                            </span>
                            <x-icon name="arrow-right" class="h-5 w-5 shrink-0" />
                        </a>
                    @endif
                </nav>
            @endif
        </div>

        <aside class="space-y-6">
            <section>
                <h2 class="mb-3 text-lg font-extrabold text-ink">{{ __('Bahan sokongan') }}</h2>

                @if ($materials->isEmpty())
                    <p class="rounded-card border border-dashed border-line p-4 text-sm text-ink-2">
                        {{ __('Tiada bahan sokongan untuk video ini.') }}
                    </p>
                @else
                    <ul class="space-y-2">
                        @foreach ($materials as $material)
                            <li>
                                <a href="{{ route('muat-turun.bahan', $material) }}"
                                   class="card flex items-center gap-3 p-3 transition-shadow hover:shadow-lift">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-surface-2 text-ink-2" aria-hidden="true"><x-icon :name="$material->iconName()" class="h-5 w-5" /></span>

                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-extrabold text-ink">{{ $material->title }}</span>
                                        <span class="block text-xs text-ink-2">{{ $material->humanSize() }}</span>
                                    </span>

                                    <x-icon name="download" class="h-5 w-5 shrink-0 text-brand" />
                                    <span class="sr-only">{{ __('Muat turun :title', ['title' => $material->title]) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section>
                <h2 class="mb-3 text-lg font-extrabold text-ink">{{ __('Kuiz bab ini') }}</h2>

                @if ($quizzes->isEmpty())
                    <p class="rounded-card border border-dashed border-line p-4 text-sm text-ink-2">
                        {{ __('Tiada kuiz untuk bab ini lagi.') }}
                    </p>
                @else
                    <ul class="space-y-2">
                        @foreach ($quizzes as $quiz)
                            <li>
                                <a href="{{ route('kuiz.intro', $quiz) }}"
                                   class="card block p-4 transition-shadow hover:shadow-lift">
                                    <span class="block font-extrabold text-ink">{{ $quiz->title }}</span>

                                    <span class="mt-1 block text-sm text-ink-2">
                                        {{ $quiz->isFile() ? __('Kuiz bercetak') : __('Kuiz interaktif') }}
                                    </span>

                                    <span class="mt-2 block text-sm font-bold text-brand">
                                        {{ $quiz->isFile() ? __('Lihat') : __('Cuba Kuiz') }}
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </aside>
    </div>
</x-dynamic-component>
