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

        <x-year-subject-filter :action="route('admin.kandungan.video')" :grades="$grades" :subjects="$subjects" :filter="$filter" />

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
                            {{-- Everything but the title is centred; titles keep a common left edge to scan down. --}}
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tajuk Video') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Subjek') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Tahun') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Tontonan') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Tarikh Siar') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Kegemaran') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:center">{{ __('Tindakan') }}</span>
                        </div>
                        @foreach ($lessons as $lesson)
                            <div class="tp-tr" style="display:grid;{{ $cols }};padding:12px 20px;border-bottom:1px solid var(--tp-line)">
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</span>
                                    <span style="font-size:11.5px;color:var(--tp-muted)">{{ $lesson->teacher?->name }}</span>
                                </div>
                                <span style="font-size:13px;font-weight:700;color:#4276AE;text-align:center">{{ $lesson->chapter->subject->displayName() }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ $lesson->chapter->grade->name }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ number_format($lesson->views_count) }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ $lesson->created_at->translatedFormat('j M Y') }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2);text-align:center">{{ number_format($lesson->favourites_count) }}</span>
                                {{-- justify-self, not text-align: a button sizes to its label rather than the column. --}}
                                <button type="button" class="tp-linkbtn" style="justify-self:center"
                                        @click="open(@js([
                                            'title' => $lesson->title,
                                            'kindLabel' => $lesson->isYoutube() ? 'YouTube' : __('Video'),
                                            'subtitle' => collect([$lesson->teacher?->name, $lesson->chapter->subject->displayName(), $lesson->chapter->grade->name])->filter()->implode(' · '),
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

        {{-- Preview modal (WeLearn Admin design): gradient header + black video body.
             A look, not a lesson (no view counted); x-if so closing destroys the player + its audio. --}}
        <template x-if="lesson">
            <x-content-preview obj="lesson" :pill="'🎬 '.__('Video')">
                <div style="overflow-y:auto;background:#000;height:min(72vh,620px)">
                    <template x-if="lesson.kind === 'youtube'">
                        <iframe style="width:100%;height:100%;border:0;display:block" :src="lesson.src" :title="lesson.title"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    </template>

                    <template x-if="lesson.kind === 'upload'">
                        <video style="width:100%;height:100%;object-fit:contain;background:#000;display:block" controls preload="metadata"
                               :src="lesson.src" :poster="lesson.poster"></video>
                    </template>
                </div>
            </x-content-preview>
        </template>
    </div>
</x-admin-layout>
