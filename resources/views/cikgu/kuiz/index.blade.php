<x-app-layout :title="__('Kuiz Saya')">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Kuiz Saya') }}</h1>
            <p class="mt-1 text-ink-2">{{ __('Kuiz interaktif yang disemak automatik, dan kuiz bercetak.') }}</p>
        </div>

        <a href="{{ route('cikgu.kuiz.mod') }}" class="btn-primary">
            <x-icon name="plus" class="h-5 w-5" />
            {{ __('Kuiz Baharu') }}
        </a>
    </header>

    <div class="mt-6">
        <x-cikgu-filters :subjects="$subjects" :grades="$grades" :action="route('cikgu.kuiz.index')" />
    </div>

    <section class="mt-6">
        <h2 class="sr-only">{{ __('Senarai kuiz') }}</h2>

        @if ($quizzes->isEmpty())
            <x-empty emoji="📝" :title="__('Belum ada kuiz')"
                     :text="__('Bina kuiz interaktif yang menyemak jawapan sendiri, atau muat naik kuiz bercetak.')">
                <a href="{{ route('cikgu.kuiz.mod') }}" class="btn-primary">{{ __('Cipta Kuiz Pertama') }}</a>
            </x-empty>
        @else
            <ul class="space-y-3">
                @foreach ($quizzes as $quiz)
                    <li class="card p-5" style="--sc: {{ $quiz->chapter->subject->rgb }}">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-extrabold text-ink">{{ $quiz->title }}</h3>

                                    @if ($quiz->isFile())
                                        <span class="chip bg-surface-2 text-ink-2">{{ __('Kuiz Bercetak') }}</span>
                                    @else
                                        <span class="chip bg-brand-soft text-brand">{{ __('Interaktif') }}</span>
                                    @endif

                                    @if (! $quiz->is_published)
                                        <span class="chip bg-warn-soft text-warn">{{ __('Draf') }}</span>
                                    @endif
                                </div>

                                <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-2">
                                    <span class="chip bg-subject-wash text-subject-ink">{{ $quiz->chapter->subject->name }}</span>
                                    <span>{{ $quiz->chapter->grade->name }}</span>
                                    <span>Bab {{ $quiz->chapter->number }}</span>

                                    @unless ($quiz->chapter->is_active)
                                        <span class="chip bg-warn-soft text-warn">{{ __('Bab tidak lagi dalam kurikulum — sila pindahkan') }}</span>
                                    @endunless

                                    @if ($quiz->isInteractive())
                                        <span>{{ __(':count soalan', ['count' => $quiz->questions_count]) }}</span>
                                        <span>{{ __(':count percubaan', ['count' => $quiz->completed_attempts_count]) }}</span>

                                        @if ($quiz->duration_minutes)
                                            <span class="flex items-center gap-1">
                                                <x-icon name="clock" class="h-4 w-4" />
                                                {{ __(':count min', ['count' => $quiz->duration_minutes]) }}
                                            </span>
                                        @endif
                                    @endif
                                </p>

                                @if ($quiz->isInteractive() && $quiz->questions_count === 0)
                                    <p class="mt-3 flex items-center gap-2 text-sm font-bold text-warn">
                                        <x-icon name="alert" class="h-4 w-4" />
                                        {{ __('Kuiz ini belum ada soalan, jadi murid belum boleh mencubanya.') }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                @if ($quiz->isInteractive())
                                    <a href="{{ route('cikgu.kuiz.soalan', $quiz) }}" class="btn-primary btn-sm">
                                        <x-icon name="quiz" class="h-4 w-4" />
                                        {{ __('Soalan') }}
                                    </a>

                                    <a href="{{ route('cikgu.kuiz.statistik', $quiz) }}" class="btn-secondary btn-sm">
                                        <x-icon name="chart" class="h-4 w-4" />
                                        {{ __('Statistik') }}
                                    </a>
                                @else
                                    <a href="{{ route('muat-turun.kuiz', $quiz) }}" class="btn-secondary btn-sm">
                                        <x-icon name="download" class="h-4 w-4" />
                                        {{ __('Fail') }}
                                    </a>
                                @endif

                                <a href="{{ route('cikgu.kuiz.edit', $quiz) }}" class="btn-ghost btn-sm">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                    <span class="sr-only">{{ __('Sunting :title', ['title' => $quiz->title]) }}</span>
                                </a>

                                <form method="POST" action="{{ route('cikgu.kuiz.destroy', $quiz) }}"
                                      onsubmit='return confirm(@js(__("Padam kuiz \":title\"? Semua soalan dan percubaan murid akan dipadam sekali. Tindakan ini tidak boleh dibatalkan.", ["title" => $quiz->title])))'>
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn-ghost btn-sm text-danger hover:bg-danger-soft">
                                        <x-icon name="trash" class="h-4 w-4" />
                                        <span class="sr-only">{{ __('Padam :title', ['title' => $quiz->title]) }}</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                {{ $quizzes->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
