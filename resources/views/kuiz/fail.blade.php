<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="$quiz->title">
    <div class="mx-auto max-w-2xl" style="--sc: {{ $subject->rgb }}">
        <a href="{{ route('bab.show', $chapter) }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Bab {{ $chapter->number }}: {{ $chapter->title }}
        </a>

        <div class="card card-pad mt-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="chip bg-subject-wash text-subject-ink"><x-subject-icon :subject="$subject" class="h-4 w-4" /> {{ $subject->name }}</span>
                <span class="chip bg-surface-2 text-ink-2">{{ __('Kuiz Bercetak') }}</span>
            </div>

            <h1 class="mt-3 text-3xl font-extrabold text-ink">{{ $quiz->title }}</h1>

            @if ($quiz->description)
                <p class="mt-3 max-w-prose text-ink-2">{{ $quiz->description }}</p>
            @endif

            {{-- File facts: format, page count (when the PDF exposes it) and when it was created. --}}
            @php($pages = $quiz->pageCount())
            <div class="mt-5 flex flex-wrap items-center gap-x-8 gap-y-4 border-t border-line pt-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-surface-2 text-ink"><x-icon name="file" class="h-5 w-5" /></span>
                    <span class="flex flex-col leading-tight">
                        <span class="text-xs font-bold text-ink-2">{{ __('Format') }}</span>
                        <span class="text-sm font-extrabold text-ink">{{ $quiz->extension() }}</span>
                    </span>
                </div>

                @if ($pages)
                    <span class="hidden h-8 w-px bg-line sm:block"></span>
                    <div class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-surface-2 text-ink"><x-icon name="printer" class="h-5 w-5" /></span>
                        <span class="flex flex-col leading-tight">
                            <span class="text-xs font-bold text-ink-2">{{ __('Halaman') }}</span>
                            <span class="text-sm font-extrabold text-ink">{{ __(':count halaman', ['count' => $pages]) }}</span>
                        </span>
                    </div>
                @endif

                <span class="hidden h-8 w-px bg-line sm:block"></span>
                <div class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-surface-2 text-ink"><x-icon name="clock" class="h-5 w-5" /></span>
                    <span class="flex flex-col leading-tight">
                        <span class="text-xs font-bold text-ink-2">{{ __('Dicipta') }}</span>
                        <span class="text-sm font-extrabold text-ink">{{ $quiz->created_at->translatedFormat('j M Y') }}</span>
                    </span>
                </div>
            </div>

            <p class="mt-6 rounded-card border border-line bg-surface-2 p-4 text-ink-2">
                {{ __('Kuiz ini disediakan sebagai fail untuk dicetak atau dijawab di atas kertas.') }}
                {{ __('Ia tidak disemak secara automatik dan tidak memberi mata ranking.') }}
            </p>

            @if ($quiz->file_path)
                <a href="{{ route('muat-turun.kuiz', $quiz) }}" class="btn-primary mt-6 w-full">
                    <x-icon name="download" class="h-5 w-5" />
                    {{ __('Muat Turun Kuiz') }}
                </a>
            @else
                <x-alert type="warn" class="mt-6">{{ __('Fail kuiz tidak dijumpai. Sila hubungi cikgu anda.') }}</x-alert>
            @endif
        </div>
    </div>
</x-dynamic-component>
