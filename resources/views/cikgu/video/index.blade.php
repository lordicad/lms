<x-cikgu-layout
    :title="__('Video')"
    :heading="__('Video')"
    :sub="__('Rakaman kelas yang anda muat naik atau pautkan dari YouTube')">

    {{-- Total videos uploaded by this teacher (all-time, not the filtered count). --}}
    <div class="tp-stat" style="max-width:340px;margin-bottom:18px">
        <div style="display:flex;align-items:center;gap:10px">
            <span class="tp-stat-ico" style="background:#E4EEF9">🎬</span>
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
                <div class="tp-listcard">
                    <span class="tp-thumb" style="width:96px;height:60px;background:rgb({{ $subject->rgb }} / .14);font-size:14px">
                        @if ($lesson->thumbnailUrl())
                            <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                        @else
                            ▶
                        @endif
                    </span>

                    <div style="display:flex;flex-direction:column;gap:6px;min-width:0;flex:1">
                        <a href="{{ route('video.show', $lesson) }}" class="tp-g" style="font-weight:800;font-size:15.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</a>
                        {{-- Subject on its own line, with the rest of the detail beneath it: the
                             coloured chip is what the eye picks out when scanning the list. --}}
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="tp-tag" style="background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }})">{{ $subject->name }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="tp-meta">{{ $lesson->chapter->grade->name }}</span>
                            <span class="tp-meta">Bab {{ $lesson->chapter->number }}</span>
                            <span class="tp-tag-neutral">{{ $lesson->isYoutube() ? 'YouTube' : __('Muat naik') }}</span>
                            @unless ($lesson->chapter->is_active)
                                <span class="tp-tag" style="background:#FEF0CE;color:#8A6A12">{{ __('Bab tidak lagi dalam kurikulum') }}</span>
                            @endunless
                            <span class="tp-meta">👁 {{ $lesson->views_count }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('cikgu.video.terbit', $lesson) }}" style="flex-shrink:0">
                        @csrf
                        <button type="submit" class="tp-badge {{ $lesson->is_published ? 'tp-badge-ok' : 'tp-badge-draft' }}" style="border:none;cursor:pointer">
                            {{ $lesson->is_published ? __('Diterbitkan') : __('Draf') }}
                        </button>
                    </form>

                    <a href="{{ route('cikgu.video.edit', $lesson) }}" class="tp-btn-ghost" style="flex-shrink:0">
                        ✏️ {{ __('Sunting') }}
                    </a>

                    <form method="POST" action="{{ route('cikgu.video.destroy', $lesson) }}" style="flex-shrink:0"
                          onsubmit='return confirm(@js(__("Padam video \":title\"? Fail video juga akan dipadam. Tindakan ini tidak boleh dibatalkan.", ["title" => $lesson->title])))'>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-icon-action tp-icon-danger" title="{{ __('Padam') }}">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            <span class="sr-only">{{ __('Padam :title', ['title' => $lesson->title]) }}</span>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div>{{ $lessons->links() }}</div>
    @endif
</x-cikgu-layout>
