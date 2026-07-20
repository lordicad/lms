@php
    $cols = 'grid-template-columns:minmax(0,2.2fr) 1.4fr .8fr .6fr 1fr .9fr .7fr;gap:12px;align-items:center';
    $stats = [
        ['icon' => '🎥', 'label' => __('Jumlah video'),      'value' => $totalCount],
        ['icon' => '▶️', 'label' => __('Video YouTube'),     'value' => $youtubeCount],
        ['icon' => '⬆️', 'label' => __('Video dimuat naik'), 'value' => $uploadCount],
    ];
@endphp

<x-admin-layout :title="__('Kandungan Video')"
                :heading="__('Kandungan Video')"
                :sub="__('Setiap video yang dimuat naik oleh cikgu, merentas semua subjek dan Tahun')">

    <div style="display:flex;flex-direction:column;gap:18px"
         x-data="{
             lesson: null,
             open(data) { this.lesson = data; document.body.classList.add('overflow-hidden'); },
             close() { this.lesson = null; document.body.classList.remove('overflow-hidden'); },
         }"
         @keydown.escape.window="close()">

        @include('admin.kandungan._tabs', ['active' => 'video'])

        {{-- Stats --}}
        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
            @foreach ($stats as $s)
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="font-size:13.5px;font-weight:700;color:var(--tp-muted)">{{ $s['icon'] }} {{ $s['label'] }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:var(--tp-ink)">{{ number_format($s['value']) }}</span>
                </div>
            @endforeach
        </div>

        @include('admin.kandungan._filters', ['subjects' => $subjects, 'grades' => $grades, 'action' => route('admin.kandungan.video')])

        @if ($lessons->isEmpty())
            <div class="tp-empty">
                <span style="font-size:30px">🎬</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Tiada video untuk dipaparkan') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Tiada video yang sepadan dengan tapisan ini.') }}</p>
            </div>
        @else
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <div style="overflow-x:auto">
                    <div style="min-width:860px">
                        {{-- Header --}}
                        <div style="display:grid;{{ $cols }};padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tajuk Video') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Subjek') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tahun') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tontonan') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tarikh Siar') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Kegemaran') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:right">{{ __('Tindakan') }}</span>
                        </div>
                        @foreach ($lessons as $lesson)
                            <div class="tp-tr" style="display:grid;{{ $cols }};padding:12px 20px;border-bottom:1px solid var(--tp-line)">
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</span>
                                    <span style="font-size:11.5px;color:var(--tp-muted)">{{ $lesson->teacher?->name }}</span>
                                </div>
                                <span style="font-size:13px;font-weight:700;color:#4276AE">{{ $lesson->chapter->subject->displayName() }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ $lesson->chapter->grade->name }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ number_format($lesson->views_count) }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ $lesson->created_at->translatedFormat('j M Y') }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ number_format($lesson->favourites_count) }}</span>
                                <button type="button" class="tp-linkbtn" style="justify-self:end"
                                        @click="open(@js([
                                            'title' => $lesson->title,
                                            'teacher' => $lesson->teacher?->name,
                                            'kind' => $lesson->isYoutube() ? 'youtube' : 'upload',
                                            'src' => $lesson->isYoutube() ? $lesson->embedUrl() : $lesson->videoUrl(),
                                            'poster' => $lesson->thumbnailUrl(),
                                        ]))">
                                    👁 {{ __('Lihat') }}<span class="sr-only">{{ $lesson->title }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>{{ $lessons->links() }}</div>
        @endif

        {{-- Preview modal — a look, not a lesson: deliberately not the watch page (no view counted).
             x-if so closing destroys the player and its audio. --}}
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
    </div>
</x-admin-layout>
