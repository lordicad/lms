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

    {{--
        Preview is a modal rather than a trip to the watch page: an admin is auditing a list, not
        studying, so they should stay on it. Deliberately not <x-player>, which counts views and
        saves watch progress — this is a look, not a lesson.

        The player markup lives in an <template x-if>, so closing destroys the element and the
        video (or the YouTube iframe) stops. Merely hiding it would keep the audio playing.
    --}}
    <section class="mt-6"
             x-data="{
                 lesson: null,
                 open(data) { this.lesson = data; document.body.classList.add('overflow-hidden'); },
                 close() { this.lesson = null; document.body.classList.remove('overflow-hidden'); },
             }"
             @keydown.escape.window="close()">
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
                                    <button type="button" class="btn-ghost btn-sm"
                                            @click="open(@js([
                                                'title' => $lesson->title,
                                                'teacher' => $lesson->teacher?->name,
                                                'kind' => $lesson->isYoutube() ? 'youtube' : 'upload',
                                                'src' => $lesson->isYoutube() ? $lesson->embedUrl() : $lesson->videoUrl(),
                                                'poster' => $lesson->thumbnailUrl(),
                                            ]))">
                                        <x-icon name="eye" class="h-4 w-4" />
                                        {{ __('Lihat') }}
                                        <span class="sr-only">{{ $lesson->title }}</span>
                                    </button>
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

        {{--
            The whole dialog is an x-if rather than an x-show: closing must *remove* the player,
            not just hide it, or a hidden YouTube iframe keeps playing audio over the page.
            x-if also means there is no stale overlay left able to swallow clicks.
        --}}
        <template x-if="lesson">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 role="dialog" aria-modal="true" :aria-label="lesson.title">
                <div class="absolute inset-0 bg-black/70" @click="close()" aria-hidden="true"></div>

                <div class="relative w-full max-w-3xl overflow-hidden rounded-card border border-line bg-surface shadow-hero">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="truncate font-extrabold text-ink" x-text="lesson.title"></h2>
                            <p class="truncate text-xs text-ink-2" x-text="lesson.teacher"></p>
                        </div>

                        <button type="button" class="btn-ghost btn-sm shrink-0" @click="close()" x-init="$el.focus()">
                            <x-icon name="x" class="h-4 w-4" />
                            <span class="sr-only">{{ __('Tutup') }}</span>
                        </button>
                    </div>

                    <div class="bg-black">
                        <template x-if="lesson.kind === 'youtube'">
                            <div class="aspect-video">
                                <iframe class="h-full w-full" :src="lesson.src" :title="lesson.title"
                                        frameborder="0" referrerpolicy="strict-origin-when-cross-origin"
                                        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                            </div>
                        </template>

                        <template x-if="lesson.kind === 'upload'">
                            <video class="aspect-video w-full" controls preload="metadata"
                                   :src="lesson.src" :poster="lesson.poster"></video>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </section>
</x-app-layout>
