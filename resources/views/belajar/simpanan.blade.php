<x-student-layout :title="__('Simpanan Offline')">
    <div x-data="offlineDownloads()">
        <header class="mb-6">
            <h1 class="text-2xl font-extrabold text-ink">{{ __('Simpanan Offline') }}</h1>
            <p class="mt-1 max-w-prose text-ink-2">
                {{ __('Muat turun video yang dimuat naik dan bahan sokongan untuk ditonton atau dibaca tanpa internet. Video YouTube hanya boleh ditonton dalam talian.') }}
            </p>
        </header>

        {{-- Dependent Year -> Subject filter (brief §3.1). Defaults to the student's active Tahun. --}}
        <div class="mb-6">
            <x-year-subject-filter variant="student" :all-years="false" with-chapter
                :action="route('simpanan.index')" :grades="$grades" :subjects="$subjects" :filter="$filter" />
        </div>

        {{-- Uploaded videos are downloadable; YouTube ones are honestly marked online-only. --}}
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-extrabold text-ink">{{ __('Video Pelajaran') }}</h2>

            @if ($lessons->isEmpty())
                <p class="rounded-card border border-dashed border-line p-4 text-sm text-ink-2">
                    {{ __('Tiada video untuk Tahun ini lagi.') }}
                </p>
            @else
                <ul class="space-y-3">
                    @foreach ($lessons as $lesson)
                        <li class="card flex flex-wrap items-center gap-4 p-4" style="--sc: {{ $lesson->chapter->subject->rgb }}">
                            {{-- Video thumbnail at the existing 44px size; falls back to the subject icon. --}}
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-control bg-subject-wash"
                                  aria-hidden="true">
                                @if ($lesson->thumbnailUrl())
                                    <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <x-subject-icon :subject="$lesson->chapter->subject" class="h-5 w-5" />
                                @endif
                            </span>

                            <span class="min-w-0 flex-1">
                                <a href="{{ route('video.show', $lesson) }}" class="block truncate font-extrabold text-ink hover:text-brand">{{ $lesson->title }}</a>
                                <span class="block text-sm text-ink-2">{{ $lesson->chapter->subject->displayName() }} · Bab {{ $lesson->chapter->number }}</span>
                                @if ($lesson->teacher)
                                    <span class="block text-xs text-ink-2">{{ __('Guru: :name', ['name' => $lesson->teacher->name]) }}</span>
                                @endif
                            </span>

                            @if ($lesson->isUpload())
                                <a href="{{ route('muat-turun.video', $lesson) }}"
                                   @click="remember(@js($lesson->title))" class="btn-secondary btn-sm shrink-0">
                                    <x-icon name="download" class="h-4 w-4" />
                                    {{ __('Muat Turun') }}
                                </a>
                            @else
                                <span class="shrink-0 text-right">
                                    <span class="btn-secondary btn-sm pointer-events-none opacity-50" aria-disabled="true">
                                        <x-icon name="youtube" class="h-4 w-4" />
                                        {{ __('Dalam talian sahaja') }}
                                    </span>
                                    <span class="mt-1 block text-xs text-ink-2">{{ __('Video ini hanya boleh ditonton dalam talian.') }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Supporting materials: genuinely useful offline even for YouTube lessons. --}}
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-extrabold text-ink">{{ __('Bahan Sokongan') }}</h2>

            @if ($materialsByChapter->isEmpty())
                <p class="rounded-card border border-dashed border-line p-4 text-sm text-ink-2">
                    {{ __('Tiada bahan sokongan untuk Tahun ini lagi.') }}
                </p>
            @else
                <div class="space-y-6">
                    @foreach ($materialsByChapter as $materials)
                        @php($chapter = $materials->first()->chapter)

                        <div class="card card-pad" style="--sc: {{ $chapter->subject->rgb }}">
                            <h3 class="mb-3 flex items-center gap-2 font-extrabold text-ink">
                                <span class="chip bg-subject-wash text-subject-ink"><x-subject-icon :subject="$chapter->subject" class="h-4 w-4" /> {{ $chapter->subject->displayName() }}</span>
                                Bab {{ $chapter->number }}: {{ $chapter->title }}
                            </h3>

                            <ul class="divide-y divide-line">
                                @foreach ($materials as $material)
                                    <li>
                                        <a href="{{ route('muat-turun.bahan', $material) }}"
                                           @click="remember(@js($material->title))"
                                           class="flex items-center gap-3 py-2.5 hover:text-brand">
                                            <span class="text-ink-2" aria-hidden="true"><x-icon :name="$material->iconName()" class="h-5 w-5" /></span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-sm font-bold text-ink">{{ $material->title }}</span>
                                                <span class="block text-xs text-ink-2">{{ $material->humanSize() }}@if ($material->teacher) · {{ __('Guru: :name', ['name' => $material->teacher->name]) }}@endif</span>
                                            </span>
                                            <x-icon name="download" class="h-5 w-5 shrink-0 text-brand" />
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- A local, informational record of what the student downloaded (not a real cache). --}}
        <section x-show="downloads.length" x-cloak>
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-extrabold text-ink">{{ __('Muat Turun Saya') }}</h2>
                <button type="button" @click="clearAll()" class="text-sm font-bold text-danger hover:underline">{{ __('Kosongkan') }}</button>
            </div>

            <p class="mb-3 text-sm text-ink-2">{{ __('Fail yang anda muat turun tersimpan dalam folder Muat Turun peranti anda.') }}</p>

            <ul class="card divide-y divide-line">
                <template x-for="item in downloads" :key="item.title + item.at">
                    <li class="flex items-center gap-3 p-3">
                        <x-icon name="download" class="h-5 w-5 shrink-0 text-ink-2" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-bold text-ink" x-text="item.title"></span>
                            <span class="block text-xs text-ink-2" x-text="item.at"></span>
                        </span>
                    </li>
                </template>
            </ul>
        </section>
    </div>

    @push('scripts')
        <script>
            function offlineDownloads() {
                return {
                    downloads: JSON.parse(localStorage.getItem('my-downloads') || '[]'),

                    remember(title) {
                        const entry = { title, at: new Date().toLocaleDateString() };
                        this.downloads = [entry, ...this.downloads.filter(d => d.title !== title)].slice(0, 50);
                        localStorage.setItem('my-downloads', JSON.stringify(this.downloads));
                    },

                    clearAll() {
                        this.downloads = [];
                        localStorage.removeItem('my-downloads');
                    },
                };
            }
        </script>
    @endpush
</x-student-layout>
