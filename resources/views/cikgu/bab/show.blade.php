<x-cikgu-layout
    :title="__('Bab :number: :title', ['number' => $chapter->number, 'title' => $chapter->title])"
    :heading="$chapter->title"
    :sub="__('Kandungan anda dalam bab ini')">

    <div style="display:flex;flex-direction:column;gap:22px"
         x-data="{
             lesson: null,
             item: null,
             open(data) { this.lesson = data; document.body.classList.add('overflow-hidden'); },
             openMaterial(data) { this.item = data; document.body.classList.add('overflow-hidden'); },
             close() { this.lesson = null; this.item = null; document.body.classList.remove('overflow-hidden'); },
         }"
         @keydown.escape.window="close()">
        <a href="{{ route('cikgu.bab.index', ['subjek' => $subject->slug, 'tahun' => $grade->level]) }}" class="tp-back">← {{ __('Semua Bab') }}</a>

        <span style="align-self:flex-start;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;display:inline-flex;align-items:center;gap:6px"><x-icon :name="$subject->iconName()" class="h-[15px] w-[15px]" />{{ $subject->name }} – {{ $grade->displayName() }} – {{ __('Bab :number', ['number' => $chapter->number]) }}</span>

        @if ($chapter->description)
            <p style="margin:0;font-size:15px;color:var(--tp-muted-2);max-width:640px">{{ $chapter->description }}</p>
        @endif

        {{-- Videos --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="display:flex;align-items:center;gap:8px;font-size:17px;font-weight:800;color:var(--tp-ink)"><x-icon name="video" style="width:19px;height:19px;color:#0F7A68" />{{ __('Video') }} <span style="color:var(--tp-muted)">({{ $lessons->count() }})</span></h2>

            @if ($lessons->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum memuat naik video dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.video.create') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Video Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($lessons as $lesson)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="video" style="width:20px;height:20px" /></span>
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $lesson->title }}</span>
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                                    @if ($lesson->is_published)
                                        <span class="tp-tag" style="background:#DCF2EE;color:#0F7A68">{{ __('Diterbitkan') }}</span>
                                    @else
                                        <span class="tp-tag-neutral">{{ __('Draf') }}</span>
                                    @endif
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon :name="$lesson->isYoutube() ? 'youtube' : 'upload'" class="h-4 w-4" style="color:#0F7A68" />{{ $lesson->isYoutube() ? 'YouTube' : __('Muat naik') }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="calendar" class="h-4 w-4" style="color:#0F7A68" />{{ $lesson->created_at->format('d/m/Y') }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="eye" class="h-4 w-4" style="color:#0F7A68" />{{ $lesson->views_count }}</span>
                                </div>
                            </div>
                            <button type="button" class="tp-btn-outline" style="flex-shrink:0"
                                    @click="open(@js([
                                        'title' => $lesson->title,
                                        'subtitle' => collect([$subject->name, $grade->displayName(), __('Bab :number', ['number' => $chapter->number])])->filter()->implode(' · '),
                                        'kind' => $lesson->isYoutube() ? 'youtube' : 'upload',
                                        'src' => $lesson->isYoutube() ? $lesson->embedUrl() : $lesson->videoUrl(),
                                        'poster' => $lesson->thumbnailUrl(),
                                    ]))"><x-icon name="eye" class="h-4 w-4" />{{ __('Lihat') }}</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Materials --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="display:flex;align-items:center;gap:8px;font-size:17px;font-weight:800;color:var(--tp-ink)"><x-icon name="file" style="width:19px;height:19px;color:#0F7A68" />{{ __('Bahan') }} <span style="color:var(--tp-muted)">({{ $materials->count() }})</span></h2>

            @if ($materials->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum memuat naik bahan dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.bahan.create') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Bahan Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($materials as $material)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#FBE4ED;color:#B84A75;display:grid;place-items:center;flex-shrink:0"><x-icon :name="$material->iconName()" class="h-5 w-5" /></span>
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->title }}</span>
                                {{-- File facts: type, size, upload date, and download count — in the
                                     soft rose of the material theme. --}}
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:14px">
                                    <span style="background:#FBE4ED;color:#B84A75;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ strtoupper($material->extension()) }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="file" class="h-4 w-4" style="color:#B84A75" />{{ $material->humanSize() }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="calendar" class="h-4 w-4" style="color:#B84A75" />{{ $material->created_at->format('d/m/Y') }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="download" class="h-4 w-4" style="color:#B84A75" />{{ $material->download_count }}</span>
                                </div>
                            </div>
                            <button type="button" class="tp-btn-outline" style="flex-shrink:0"
                                    @click="openMaterial(@js([
                                        'title' => $material->title,
                                        'subtitle' => collect([$subject->name, $grade->displayName(), __('Bab :number', ['number' => $chapter->number])])->filter()->implode(' · '),
                                        'kind' => $material->previewKind(),
                                        'src' => $material->fileUrl(),
                                        'name' => $material->original_name,
                                        'type' => strtoupper($material->extension()),
                                        'size' => $material->humanSize(),
                                        'downloadUrl' => route('muat-turun.bahan', $material),
                                    ]))"><x-icon name="eye" class="h-4 w-4" />{{ __('Lihat') }}</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Quizzes --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="display:flex;align-items:center;gap:8px;font-size:17px;font-weight:800;color:var(--tp-ink)"><x-icon name="quiz" style="width:19px;height:19px;color:#0F7A68" />{{ __('Kuiz') }} <span style="color:var(--tp-muted)">({{ $quizzes->count() }})</span></h2>

            @if ($quizzes->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum mencipta kuiz dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.kuiz.index') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Kuiz Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($quizzes as $quiz)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#FEF0CE;color:#8A6A12;display:grid;place-items:center;flex-shrink:0"><x-icon name="quiz" style="width:20px;height:20px" /></span>
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $quiz->title }}</span>
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                                    <span class="tp-tag-neutral">{{ $quiz->isInteractive() ? __('Interaktif') : __('Fail') }}</span>
                                    @if ($quiz->is_published)
                                        <span class="tp-tag" style="background:#DCF2EE;color:#0F7A68">{{ __('Diterbitkan') }}</span>
                                    @else
                                        <span class="tp-tag-neutral">{{ __('Draf') }}</span>
                                    @endif
                                    @if ($quiz->isInteractive())
                                        <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="help-circle" class="h-4 w-4" style="color:#8A6A12" />{{ __(':count soalan', ['count' => $quiz->questions_count]) }}</span>
                                    @endif
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="calendar" class="h-4 w-4" style="color:#8A6A12" />{{ $quiz->created_at->format('d/m/Y') }}</span>
                                    @if ($quiz->isInteractive())
                                        <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="users" class="h-4 w-4" style="color:#8A6A12" />{{ __(':count percubaan', ['count' => $quiz->completed_attempts_count]) }}</span>
                                    @endif
                                </div>
                            </div>
                            @if ($quiz->isInteractive())
                                <a href="{{ route('cikgu.kuiz.statistik', $quiz) }}" class="tp-btn-ghost" style="flex-shrink:0">📊 {{ __('Statistik') }}</a>
                            @else
                                <a href="{{ $quiz->fileUrl() }}" target="_blank" rel="noopener" class="tp-btn-outline" style="flex-shrink:0"><x-icon name="eye" class="h-4 w-4" />{{ __('Lihat') }}</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Video preview modal — a look, not a lesson (no view counted); x-if so closing destroys
             the player and its audio. Same shell the admin content preview uses. --}}
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

        {{-- Material preview modal — PDFs and images render in place, other files show a document
             card with a download. Same shell the admin/teacher content lists use. --}}
        <template x-if="item">
            <x-content-preview obj="item" :pill="__('Bahan')">
                <template x-if="item.kind === 'pdf'">
                    <iframe style="width:100%;height:min(72vh,620px);border:0;display:block;background:#000" :src="item.src" :title="item.title"></iframe>
                </template>

                <template x-if="item.kind === 'image'">
                    <div style="overflow:auto;height:min(72vh,620px);display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#EDF3FA,#F6F5F0);padding:20px">
                        <img :src="item.src" :alt="item.title" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:8px;box-shadow:0 8px 28px rgba(46,44,80,.14)">
                    </div>
                </template>

                <template x-if="item.kind === 'none'">
                    <div style="overflow-y:auto;padding:28px;background:linear-gradient(180deg,#EDF3FA,#F6F5F0);display:flex;flex-direction:column;align-items:center;gap:18px">
                        <div style="width:min(440px,100%);aspect-ratio:1/1.28;background:#fff;border:1px solid rgba(46,44,80,.1);border-radius:10px;box-shadow:0 8px 28px rgba(46,44,80,.14);padding:32px 28px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;position:relative">
                            <span style="position:absolute;top:18px;right:18px;background:#3E86C9;color:#fff;border-radius:8px;padding:5px 11px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800" x-text="item.type"></span>
                            <x-icon name="file" class="h-12 w-12" style="color:#6C6F87" />
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:#28293F;text-align:center;word-break:break-word" x-text="item.name"></span>
                            <span style="font-size:12.5px;color:#6C6F87;font-weight:700"><span x-text="item.type"></span> · <span x-text="item.size"></span></span>
                            <a :href="item.downloadUrl" style="margin-top:6px;display:inline-flex;align-items:center;gap:8px;min-height:42px;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;text-decoration:none"><x-icon name="download" class="h-4 w-4" />{{ __('Muat Turun') }}</a>
                        </div>
                        <span style="font-size:12.5px;color:#6C6F87;font-weight:700">{{ __('Fail ini tidak boleh dipaparkan dalam pelayar. Muat turun untuk membukanya.') }}</span>
                    </div>
                </template>
            </x-content-preview>
        </template>
    </div>
</x-cikgu-layout>
