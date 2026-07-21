<x-cikgu-layout
    :title="__('Bab :number: :title', ['number' => $chapter->number, 'title' => $chapter->title])"
    :heading="$chapter->title"
    :sub="__('Kandungan anda dalam bab ini')">

    <div style="display:flex;flex-direction:column;gap:22px;max-width:960px">
        <a href="{{ route('cikgu.bab.index', ['subjek' => $subject->slug, 'tahun' => $grade->level]) }}" class="tp-back">← {{ __('Semua Bab') }}</a>

        <span style="align-self:flex-start;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800">{{ $subject->icon }} {{ $subject->name }}. {{ $grade->name }}. {{ __('Bab :number', ['number' => $chapter->number]) }}</span>

        @if ($chapter->description)
            <p style="margin:0;font-size:15px;color:var(--tp-muted-2);max-width:640px">{{ $chapter->description }}</p>
        @endif

        {{-- Videos --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">🎬 {{ __('Video') }} <span style="color:var(--tp-muted)">({{ $lessons->count() }})</span></h2>

            @if ($lessons->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum memuat naik video dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.video.create') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Video Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($lessons as $lesson)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0">🎬</span>
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $lesson->title }}</span>
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                                    @if ($lesson->is_published)
                                        <span class="tp-tag" style="background:#DCF2EE;color:#0F7A68">{{ __('Diterbitkan') }}</span>
                                    @else
                                        <span class="tp-tag-neutral">{{ __('Draf') }}</span>
                                    @endif
                                    <span class="tp-meta">{{ $lesson->isYoutube() ? 'YouTube' : __('Muat naik') }}</span>
                                    <span class="tp-meta">👁 {{ $lesson->views_count }}</span>
                                    <span class="tp-meta">{{ $lesson->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <a href="{{ route('video.show', $lesson) }}" class="tp-btn-ghost" style="flex-shrink:0">👁 {{ __('Lihat') }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Materials --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">📄 {{ __('Bahan') }} <span style="color:var(--tp-muted)">({{ $materials->count() }})</span></h2>

            @if ($materials->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum memuat naik bahan dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.bahan.create') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Bahan Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($materials as $material)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#FBE4ED;color:#B84A75;display:grid;place-items:center;flex-shrink:0">{{ $material->icon() }}</span>
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->title }}</span>
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                                    <span class="tp-tag-neutral">{{ strtoupper($material->extension()) }}</span>
                                    <span class="tp-meta">{{ $material->humanSize() }}</span>
                                    <span class="tp-meta">⬇ {{ $material->download_count }}</span>
                                    <span class="tp-meta">{{ $material->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <a href="{{ $material->fileUrl() }}" target="_blank" rel="noopener" class="tp-btn-ghost" style="flex-shrink:0">👁 {{ __('Buka') }}</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Quizzes --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">📝 {{ __('Kuiz') }} <span style="color:var(--tp-muted)">({{ $quizzes->count() }})</span></h2>

            @if ($quizzes->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum mencipta kuiz dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.kuiz.index') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Kuiz Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($quizzes as $quiz)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#FEF0CE;color:#8A6A12;display:grid;place-items:center;flex-shrink:0">📝</span>
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
                                        <span class="tp-meta">{{ __(':count soalan', ['count' => $quiz->questions_count]) }}</span>
                                        <span class="tp-meta">{{ __(':count percubaan', ['count' => $quiz->completed_attempts_count]) }}</span>
                                    @endif
                                    <span class="tp-meta">{{ $quiz->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            @if ($quiz->isInteractive())
                                <a href="{{ route('cikgu.kuiz.statistik', $quiz) }}" class="tp-btn-ghost" style="flex-shrink:0">📊 {{ __('Statistik') }}</a>
                            @else
                                <a href="{{ $quiz->fileUrl() }}" target="_blank" rel="noopener" class="tp-btn-ghost" style="flex-shrink:0">👁 {{ __('Buka') }}</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-cikgu-layout>
