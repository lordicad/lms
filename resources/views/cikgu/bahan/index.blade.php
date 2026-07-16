<x-app-layout :title="__('Bahan Bantu Mengajar')">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Bahan Bantu Mengajar') }}</h1>
            <p class="mt-1 text-ink-2">{{ __('Slaid, PDF dan lembaran kerja yang menyokong video anda.') }}</p>
        </div>

        <a href="{{ route('cikgu.bahan.create') }}" class="btn-primary">
            <x-icon name="plus" class="h-5 w-5" />
            {{ __('Bahan Baharu') }}
        </a>
    </header>

    <div class="mt-6">
        <x-cikgu-filters :subjects="$subjects" :grades="$grades" :action="route('cikgu.bahan.index')" />
    </div>

    <section class="mt-6">
        <h2 class="sr-only">{{ __('Senarai bahan') }}</h2>

        @if ($materials->isEmpty())
            <x-empty emoji="📄" :title="__('Belum ada bahan')"
                     :text="__('Muat naik slaid, PDF atau lembaran kerja untuk menyokong pembelajaran murid.')">
                <a href="{{ route('cikgu.bahan.create') }}" class="btn-primary">{{ __('Muat Naik Bahan') }}</a>
            </x-empty>
        @else
            <ul class="space-y-3">
                @foreach ($materials as $material)
                    <li class="card flex flex-wrap items-center gap-4 p-4"
                        style="--sc: {{ $material->chapter->subject->rgb }}">

                        <span class="text-3xl" aria-hidden="true">{{ $material->icon() }}</span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-extrabold text-ink">{{ $material->title }}</span>

                            <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-2">
                                <span class="chip bg-subject-wash text-subject-ink">{{ $material->chapter->subject->name }}</span>
                                <span>{{ $material->chapter->grade->name }}</span>
                                <span>Bab {{ $material->chapter->number }}</span>

                                @unless ($material->chapter->is_active)
                                    <span class="chip bg-warn-soft text-warn">{{ __('Bab tidak lagi dalam kurikulum — sila pindahkan') }}</span>
                                @endunless

                                @if ($material->lesson)
                                    <span class="truncate">🎬 {{ $material->lesson->title }}</span>
                                @endif

                                <span>{{ $material->humanSize() }}</span>

                                <span class="flex items-center gap-1">
                                    <x-icon name="download" class="h-4 w-4" />
                                    {{ $material->download_count }}
                                </span>
                            </span>
                        </span>

                        <span class="flex shrink-0 items-center gap-2">
                            <a href="{{ route('muat-turun.bahan', $material) }}" class="btn-ghost btn-sm">
                                <x-icon name="download" class="h-4 w-4" />
                                <span class="sr-only">{{ __('Muat turun :title', ['title' => $material->title]) }}</span>
                            </a>

                            <a href="{{ route('cikgu.bahan.edit', $material) }}" class="btn-secondary btn-sm">
                                <x-icon name="pencil" class="h-4 w-4" />
                                {{ __('Sunting') }}
                            </a>

                            <form method="POST" action="{{ route('cikgu.bahan.destroy', $material) }}"
                                  onsubmit='return confirm(@js(__("Padam bahan \":title\"? Fail juga akan dipadam.", ["title" => $material->title])))'>
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn-ghost btn-sm text-danger hover:bg-danger-soft">
                                    <x-icon name="trash" class="h-4 w-4" />
                                    <span class="sr-only">{{ __('Padam :title', ['title' => $material->title]) }}</span>
                                </button>
                            </form>
                        </span>
                    </li>
                @endforeach
            </ul>

            <div class="mt-6">
                {{ $materials->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
