<x-cikgu-layout
    :title="__('Video')"
    :heading="__('Video')"
    :sub="__('Rakaman kelas yang anda muat naik atau pautkan dari YouTube')">

    {{-- Total videos uploaded by this teacher (all-time, not the filtered count). --}}
    <div class="tp-stat" style="max-width:340px;margin-bottom:18px">
        <div style="display:flex;align-items:center;gap:10px">
            <span class="tp-stat-ico" style="background:#E4EEF9"><x-icon name="video" class="h-5 w-5" style="color:#2E6CA8" /></span>
            <span class="tp-stat-label">{{ __('Video Saya') }}</span>
        </div>
        <span class="tp-stat-value">{{ number_format($totalVideos) }}</span>
        <span style="font-size:12.5px;font-weight:700;color:var(--tp-muted)">{{ __('Jumlah tontonan: :count', ['count' => number_format($viewCount)]) }}</span>
    </div>

    <x-year-subject-filter :subjects="$subjects" :grades="$grades" :filter="$filter" with-chapter :action="route('cikgu.video.index')">
        <a href="{{ route('cikgu.video.create') }}" class="tp-btn" style="margin-left:auto">
            <x-icon name="plus" class="h-4 w-4" />
            {{ __('Video Baru') }}
        </a>
    </x-year-subject-filter>

    {{-- Clicking a video opens a preview here instead of navigating to the watch page — the same
         look the admin content page uses. A look, not a lesson: no view is counted. --}}
    <div x-data="{
             lesson: null,
             open(data) { this.lesson = data; document.body.classList.add('overflow-hidden'); },
             close() { this.lesson = null; document.body.classList.remove('overflow-hidden'); },
         }"
         @keydown.escape.window="close()">

    @if ($lessons->isEmpty())
        <div class="tp-empty">
            <span style="font-size:30px">🎬</span>
            <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada video') }}</h3>
            <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Muat naik rakaman kelas anda, atau tampal pautan YouTube dari akaun anda sendiri.') }}</p>
            <a href="{{ route('cikgu.video.create') }}" class="tp-btn" style="margin-top:6px">{{ __('Tambah Video Pertama') }}</a>
        </div>
    @else
        <div class="tp-list">
            @foreach ($lessons as $lesson)
                @php($subject = $lesson->chapter->subject)
                @php($preview = [
                    'title' => $lesson->title,
                    'subtitle' => collect([$subject->name, $lesson->chapter->grade->name, __('Bab :n', ['n' => $lesson->chapter->number])])->implode(' · '),
                    'kind' => $lesson->isYoutube() ? 'youtube' : 'upload',
                    'src' => $lesson->isYoutube() ? $lesson->embedUrl() : $lesson->videoUrl(),
                    'poster' => $lesson->thumbnailUrl(),
                ])
                <div class="tp-listcard">
                    {{-- Thumbnail: click to preview. A play disc sits over the frame and, once a
                         duration has been captured, a badge in the corner shows it. --}}
                    <button type="button" @click="open(@js($preview))" title="{{ __('Lihat video') }}"
                            style="position:relative;width:128px;height:80px;border-radius:12px;overflow:hidden;background:rgb({{ $subject->rgb }} / .14);border:none;padding:0;cursor:pointer;flex-shrink:0">
                        @if ($lesson->thumbnailUrl())
                            <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                        @endif
                        <span style="position:absolute;inset:0;display:grid;place-items:center">
                            <span style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.92);display:grid;place-items:center;box-shadow:0 2px 8px rgba(0,0,0,.28)">
                                <x-icon name="play" class="h-4 w-4" style="color:#1F2937;margin-left:2px" />
                            </span>
                        </span>
                        @if ($lesson->durationLabel())
                            <span style="position:absolute;right:6px;bottom:6px;background:rgba(0,0,0,.8);color:#fff;font-family:'Geist',sans-serif;font-weight:700;font-size:11px;padding:2px 6px;border-radius:6px">{{ $lesson->durationLabel() }}</span>
                        @endif
                    </button>

                    <div style="display:flex;flex-direction:column;gap:8px;min-width:0;flex:1">
                        <button type="button" @click="open(@js($preview))" class="tp-g" style="text-align:left;background:none;border:none;padding:0;cursor:pointer;font-family:inherit;font-weight:800;font-size:16px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</button>

                        {{-- Subject on its own line: the coloured chip is what the eye picks out
                             when scanning the list. --}}
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="tp-tag" style="background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }})">{{ $subject->name }}</span>
                        </div>

                        {{-- Detail row, each item led by an icon. --}}
                        <div style="display:flex;align-items:center;gap:18px;flex-wrap:wrap">
                            <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="calendar" class="h-4 w-4" style="color:var(--tp-muted-2)" />{{ $lesson->chapter->grade->name }} · Bab {{ $lesson->chapter->number }}</span>
                            <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="{{ $lesson->isYoutube() ? 'youtube' : 'upload' }}" class="h-4 w-4" style="color:var(--tp-muted-2)" />{{ $lesson->isYoutube() ? 'YouTube' : __('Muat naik') }}</span>
                            <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="eye" class="h-4 w-4" style="color:var(--tp-muted-2)" />{{ $lesson->views_count }} {{ __('tontonan') }}</span>
                            @unless ($lesson->chapter->is_active)
                                <span class="tp-tag" style="background:#FEF0CE;color:#8A6A12">{{ __('Bab tidak lagi dalam kurikulum') }}</span>
                            @endunless
                        </div>
                    </div>

                    {{-- Publish toggle: green with a check when live, amber when a draft. Still a
                         button — clicking it flips the state. --}}
                    <form method="POST" action="{{ route('cikgu.video.terbit', $lesson) }}" style="flex-shrink:0">
                        @csrf
                        <button type="submit" class="tp-badge {{ $lesson->is_published ? 'tp-badge-ok' : 'tp-badge-draft' }}" style="border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px">
                            @if ($lesson->is_published)
                                <x-icon name="check-circle" class="h-4 w-4" />
                            @endif
                            {{ $lesson->is_published ? __('Diterbitkan') : __('Draf') }}
                        </button>
                    </form>

                    <a href="{{ route('cikgu.video.edit', $lesson) }}" class="tp-btn-ghost" style="flex-shrink:0;display:inline-flex;align-items:center;gap:6px">
                        <x-icon name="pencil" class="h-4 w-4" />{{ __('Sunting') }}
                    </a>

                    <form method="POST" action="{{ route('cikgu.video.destroy', $lesson) }}" style="flex-shrink:0"
                          onsubmit="return confirm(@js(__("Padam video \":title\"? Fail video juga akan dipadam. Tindakan ini tidak boleh dibatalkan.", ["title" => $lesson->title])))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-icon-action tp-icon-danger" title="{{ __('Padam') }}" style="border:1.5px solid var(--tp-line-2)">
                            <x-icon name="trash" class="h-[18px] w-[18px]" />
                            <span class="sr-only">{{ __('Padam :title', ['title' => $lesson->title]) }}</span>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div>{{ $lessons->links() }}</div>
    @endif

        {{-- Preview modal: gradient header + black video body, the shared admin shell. x-if so
             closing destroys the player and stops its audio. --}}
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
</x-cikgu-layout>
