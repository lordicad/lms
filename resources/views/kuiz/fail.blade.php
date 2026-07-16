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

            <p class="mt-6 rounded-card border border-line bg-surface-2 p-4 text-ink-2">
                {{ __('Kuiz ini disediakan sebagai fail untuk dicetak atau dijawab di atas kertas.') }}
                {{ __('Ia tidak disemak secara automatik dan tidak memberi mata ranking.') }}
            </p>

            @if ($quiz->file_path)
                <a href="{{ route('muat-turun.kuiz', $quiz) }}" class="btn-primary mt-6 w-full">
                    <x-icon name="download" class="h-5 w-5" />
                    {{ __('Muat Turun Kuiz') }}
                </a>

                <p class="help text-center">{{ $quiz->original_name }}</p>
            @else
                <x-alert type="warn" class="mt-6">{{ __('Fail kuiz tidak dijumpai. Sila hubungi cikgu anda.') }}</x-alert>
            @endif
        </div>
    </div>
</x-dynamic-component>
