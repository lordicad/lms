<x-app-layout :title="__('Kandungan Video')">
    <header>
        <h1 class="text-3xl font-extrabold text-ink">{{ __('Kandungan Video') }}</h1>
        <p class="mt-1 max-w-prose text-ink-2">
            {{ __('Semua video yang dimuat naik oleh guru, merentas setiap subjek dan Tahun.') }}
        </p>
    </header>

    <section class="mt-8">
        <h2 class="sr-only">{{ __('Ringkasan video') }}</h2>

        <dl class="grid gap-4 sm:grid-cols-3">
            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="video" class="h-5 w-5" />
                    {{ __('Jumlah video') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($totalCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="youtube" class="h-5 w-5" />
                    {{ __('Video YouTube') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($youtubeCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="upload" class="h-5 w-5" />
                    {{ __('Video muat naik') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($uploadCount) }}</dd>
            </div>
        </dl>
    </section>

    {{-- Same Subjek/Tahun filter the teacher lists use; each side works alone or together. --}}
    <div class="mt-8">
        <x-cikgu-filters :subjects="$subjects" :grades="$grades" :action="route('admin.kandungan.video')" />
    </div>

    <section class="mt-6">
        @if ($lessons->isEmpty())
            <x-empty icon="video" :title="__('Tiada video untuk dipaparkan')"
                     :text="__('Tiada video yang sepadan dengan tapisan ini.')" />
        @else
            <div class="card overflow-x-auto p-2">
                <table class="w-full min-w-[60rem] text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-ink-2">
                            <th class="px-3 py-2 font-semibold">{{ __('Tajuk Video') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Subjek') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Tahun') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Tontonan') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Tarikh Dimuat Naik') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Kegemaran') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Tindakan') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lessons as $lesson)
                            <tr class="border-b border-line/60 last:border-0 hover:bg-surface-2/60">
                                <td class="px-3 py-2">
                                    <span class="block font-bold text-ink">{{ $lesson->title }}</span>
                                    <span class="block text-xs text-ink-2">{{ $lesson->teacher?->name }}</span>
                                </td>
                                <td class="px-3 py-2 text-ink-2">{{ $lesson->chapter->subject->displayName() }}</td>
                                <td class="px-3 py-2 text-ink-2">{{ $lesson->chapter->grade->name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($lesson->views_count) }}</td>
                                <td class="px-3 py-2 tabular-nums text-ink-2">{{ $lesson->created_at->translatedFormat('j M Y') }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($lesson->favourites_count) }}</td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('video.show', $lesson) }}" class="btn-ghost btn-sm">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        {{ __('Lihat') }}
                                        <span class="sr-only">{{ $lesson->title }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $lessons->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
