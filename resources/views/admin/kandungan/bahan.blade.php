<x-app-layout :title="__('Kandungan Bahan')">
    <header>
        <h1 class="text-3xl font-extrabold text-ink">{{ __('Kandungan Bahan') }}</h1>
        <p class="mt-1 max-w-prose text-ink-2">
            {{ __('Semua bahan bantu mengajar yang dimuat naik oleh guru, merentas setiap subjek dan Tahun.') }}
        </p>
    </header>

    <section class="mt-8">
        <h2 class="sr-only">{{ __('Ringkasan bahan') }}</h2>

        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="file" class="h-5 w-5" />
                    {{ __('Jumlah bahan') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($totalCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="file-pdf" class="h-5 w-5" />
                    {{ __('PDF') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($pdfCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="file-text" class="h-5 w-5" />
                    {{ __('DOCX') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($docxCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="presentation" class="h-5 w-5" />
                    {{ __('PPTX') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($pptxCount) }}</dd>
            </div>
        </dl>
    </section>

    {{-- Same Subjek/Tahun filter as the video list; each side works alone or together. --}}
    <div class="mt-8">
        <x-cikgu-filters :subjects="$subjects" :grades="$grades" :action="route('admin.kandungan.bahan')" />
    </div>

    {{--
        Preview keeps the admin on the list, the same way the video dialog does. PDFs and images
        show in place; Word/PowerPoint/Excel cannot be rendered by a browser, so rather than fake
        it (or hand the file to a third-party viewer) the dialog says so and offers the download.

        x-if, not x-show: closing must remove the embedded file, not merely hide it.
    --}}
    <section class="mt-6"
             x-data="{
                 item: null,
                 open(data) { this.item = data; document.body.classList.add('overflow-hidden'); },
                 close() { this.item = null; document.body.classList.remove('overflow-hidden'); },
             }"
             @keydown.escape.window="close()">
        @if ($materials->isEmpty())
            <x-empty icon="file" :title="__('Tiada bahan untuk dipaparkan')"
                     :text="__('Tiada bahan yang sepadan dengan tapisan ini.')" />
        @else
            <div class="card overflow-x-auto p-2">
                <table class="w-full min-w-[64rem] text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-ink-2">
                            <th class="px-3 py-2 font-semibold">{{ __('Tajuk Bahan') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Subjek') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Tahun') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Muat Turun') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Jenis') }}</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Tarikh Dimuat Naik') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Tindakan') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($materials as $material)
                            <tr class="border-b border-line/60 last:border-0 hover:bg-surface-2/60">
                                <td class="px-3 py-2">
                                    <span class="block font-bold text-ink">{{ $material->title }}</span>
                                    <span class="block text-xs text-ink-2">{{ $material->teacher?->name }}</span>
                                </td>
                                <td class="px-3 py-2 text-ink-2">{{ $material->chapter->subject->displayName() }}</td>
                                <td class="px-3 py-2 text-ink-2">{{ $material->chapter->grade->name }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($material->download_count) }}</td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex items-center gap-1.5 text-ink-2">
                                        <x-icon :name="$material->iconName()" class="h-4 w-4" />
                                        <span class="uppercase">{{ $material->extension() }}</span>
                                        <span class="text-xs">{{ $material->humanSize() }}</span>
                                    </span>
                                </td>
                                <td class="px-3 py-2 tabular-nums text-ink-2">{{ $material->created_at->translatedFormat('j M Y') }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" class="btn-ghost btn-sm"
                                                @click="open(@js([
                                                    'title' => $material->title,
                                                    'teacher' => $material->teacher?->name,
                                                    'kind' => $material->previewKind(),
                                                    'src' => $material->fileUrl(),
                                                    'name' => $material->original_name,
                                                    'type' => strtoupper($material->extension()),
                                                    'downloadUrl' => route('muat-turun.bahan', $material),
                                                ]))">
                                            <x-icon name="eye" class="h-4 w-4" />
                                            {{ __('Lihat') }}
                                            <span class="sr-only">{{ $material->title }}</span>
                                        </button>

                                        <a href="{{ route('muat-turun.bahan', $material) }}" class="btn-ghost btn-sm">
                                            <x-icon name="download" class="h-4 w-4" />
                                            {{ __('Muat Turun') }}
                                            <span class="sr-only">{{ $material->title }}</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $materials->links() }}
            </div>
        @endif

        <template x-if="item">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 role="dialog" aria-modal="true" :aria-label="item.title">
                <div class="absolute inset-0 bg-black/70" @click="close()" aria-hidden="true"></div>

                <div class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-card border border-line bg-surface shadow-hero">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="truncate font-extrabold text-ink" x-text="item.title"></h2>
                            <p class="truncate text-xs text-ink-2">
                                <span x-text="item.teacher"></span> &middot; <span x-text="item.name"></span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <a :href="item.downloadUrl" class="btn-secondary btn-sm">
                                <x-icon name="download" class="h-4 w-4" />
                                {{ __('Muat Turun') }}
                            </a>

                            <button type="button" class="btn-ghost btn-sm" @click="close()" x-init="$el.focus()">
                                <x-icon name="x" class="h-4 w-4" />
                                <span class="sr-only">{{ __('Tutup') }}</span>
                            </button>
                        </div>
                    </div>

                    <template x-if="item.kind === 'pdf'">
                        <iframe class="h-[70vh] w-full bg-black" :src="item.src" :title="item.title"></iframe>
                    </template>

                    <template x-if="item.kind === 'image'">
                        <div class="flex max-h-[70vh] justify-center overflow-auto bg-surface-2 p-4">
                            <img :src="item.src" :alt="item.title" class="max-w-full object-contain">
                        </div>
                    </template>

                    <template x-if="item.kind === 'none'">
                        <div class="px-6 py-12 text-center">
                            <p class="font-bold text-ink">
                                <span x-text="item.type"></span>
                                {{ __('tidak boleh dipaparkan dalam pelayar.') }}
                            </p>
                            <p class="mx-auto mt-1 max-w-prose text-sm text-ink-2">
                                {{ __('Sila muat turun fail untuk membukanya.') }}
                            </p>

                            <a :href="item.downloadUrl" class="btn-primary btn-sm mt-4">
                                <x-icon name="download" class="h-4 w-4" />
                                {{ __('Muat Turun') }}
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </section>
</x-app-layout>
