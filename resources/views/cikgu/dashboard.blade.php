<x-app-layout :title="__('Papan Pemuka')">
    @php($teacher = auth()->user())

    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Selamat datang, :name.', ['name' => Str::before($teacher->name, ' ')]) }}</h1>
            <p class="mt-1 text-ink-2">{{ __('Ringkasan kandungan dan aktiviti murid anda.') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('cikgu.video.create') }}" class="btn-primary btn-sm">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Video') }}
            </a>

            <a href="{{ route('cikgu.bahan.create') }}" class="btn-secondary btn-sm">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Bahan') }}
            </a>

            <a href="{{ route('cikgu.kuiz.mod') }}" class="btn-secondary btn-sm">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Kuiz') }}
            </a>
        </div>
    </header>

    <section class="mt-8">
        <h2 class="sr-only">{{ __('Statistik kandungan anda') }}</h2>

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('cikgu.video.index') }}" class="card p-5 transition-shadow hover:shadow-lift">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="video" class="h-5 w-5" />
                    {{ __('Video saya') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold text-ink">{{ $lessonCount }}</dd>
                <dd class="mt-1 text-sm text-ink-2">{{ __(':count tontonan keseluruhan', ['count' => $viewCount]) }}</dd>
            </a>

            <a href="{{ route('cikgu.bahan.index') }}" class="card p-5 transition-shadow hover:shadow-lift">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="file" class="h-5 w-5" />
                    {{ __('Bahan saya') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold text-ink">{{ $materialCount }}</dd>
                <dd class="mt-1 text-sm text-ink-2">{{ __('Bahan bantu mengajar') }}</dd>
            </a>

            <a href="{{ route('cikgu.kuiz.index') }}" class="card p-5 transition-shadow hover:shadow-lift">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="quiz" class="h-5 w-5" />
                    {{ __('Kuiz saya') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold text-ink">{{ $quizCount }}</dd>
                <dd class="mt-1 text-sm text-ink-2">{{ __('Fail dan interaktif') }}</dd>
            </a>

            <a href="{{ route('cikgu.ranking') }}" class="card p-5 transition-shadow hover:shadow-lift">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="users" class="h-5 w-5" />
                    {{ __('Percubaan kuiz') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold text-ink">{{ $attemptCount }}</dd>
                <dd class="mt-1 text-sm text-ink-2">{{ __('Oleh murid, pada kuiz anda') }}</dd>
            </a>
        </dl>
    </section>

    <section class="mt-10">
        <div class="mb-4 flex items-center justify-between gap-4">
            <h2 class="text-xl font-extrabold text-ink">{{ __('Percubaan terkini') }}</h2>

            <a href="{{ route('cikgu.ranking') }}" class="text-sm font-bold text-brand hover:underline">
                {{ __('Lihat ranking penuh') }}
            </a>
        </div>

        @if ($latestAttempts->isEmpty())
            <x-empty emoji="📝" :title="__('Belum ada murid mencuba kuiz anda')"
                     :text="__('Setelah anda menerbitkan kuiz interaktif, percubaan murid akan dipaparkan di sini.')">
                <a href="{{ route('cikgu.kuiz.mod') }}" class="btn-primary">{{ __('Cipta Kuiz') }}</a>
            </x-empty>
        @else
            <ul class="space-y-2">
                @foreach ($latestAttempts as $attempt)
                    <li class="card flex flex-wrap items-center gap-4 p-4"
                        style="--sc: {{ $attempt->quiz->chapter->subject->rgb }}">
                        <x-avatar :user="$attempt->student" size="sm" />

                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-bold text-ink">{{ $attempt->student->name }}</span>
                            <span class="block truncate text-sm text-ink-2">
                                {{ $attempt->quiz->title }}. {{ $attempt->student->grade?->name }}
                            </span>
                        </span>

                        <span class="chip bg-subject-wash text-subject-ink">
                            {{ $attempt->score }}/{{ $attempt->max_score }}
                        </span>

                        <span class="w-24 shrink-0 text-right text-sm text-ink-2">
                            {{ $attempt->completed_at->diffForHumans(short: true) }}
                        </span>

                        <a href="{{ route('cikgu.kuiz.statistik', $attempt->quiz) }}"
                           class="btn-ghost btn-sm shrink-0">
                            {{ __('Statistik') }}
                            <span class="sr-only">{{ __('untuk :title', ['title' => $attempt->quiz->title]) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-layout>
