<x-cikgu-layout
    :title="__('Bahan Bantu Mengajar')"
    :heading="__('Bahan Bantu Mengajar')"
    :sub="__('Slaid, PDF dan lembaran kerja yang menyokong video anda')">

    <x-cikgu-filters :subjects="$subjects" :grades="$grades" :action="route('cikgu.bahan.index')">
        <a href="{{ route('cikgu.bahan.create') }}" class="tp-btn" style="margin-left:auto">
            <x-icon name="plus" class="h-4 w-4" />
            {{ __('Bahan Baru') }}
        </a>
    </x-cikgu-filters>

    @if ($materials->isEmpty())
        <div class="tp-empty">
            <span style="font-size:30px">📄</span>
            <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada bahan') }}</h3>
            <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Muat naik slaid, PDF atau lembaran kerja untuk menyokong pembelajaran murid.') }}</p>
            <a href="{{ route('cikgu.bahan.create') }}" class="tp-btn" style="margin-top:6px">{{ __('Muat Naik Bahan') }}</a>
        </div>
    @else
        <div class="tp-list">
            @foreach ($materials as $material)
                @php($subject = $material->chapter->subject)
                <div class="tp-listcard">
                    <span style="width:46px;height:46px;border-radius:11px;background:rgb({{ $subject->rgb }} / .14);display:grid;place-items:center;font-size:18px;flex-shrink:0">{{ $material->icon() }}</span>

                    <div style="display:flex;flex-direction:column;gap:6px;min-width:0;flex:1">
                        <span class="tp-g" style="font-weight:800;font-size:15.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $material->title }}</span>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="tp-tag" style="background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }})">{{ $subject->name }}</span>
                            <span class="tp-meta">{{ $material->chapter->grade->name }}</span>
                            <span class="tp-meta">Bab {{ $material->chapter->number }}</span>
                            @if ($material->lesson)
                                <span class="tp-meta" style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">🎬 {{ $material->lesson->title }}</span>
                            @endif
                            <span class="tp-tag-neutral">{{ strtoupper($material->extension()) }}</span>
                            <span class="tp-meta">{{ $material->humanSize() }}</span>
                            <span class="tp-meta">⬇ {{ $material->download_count }}</span>
                        </div>
                    </div>

                    <a href="{{ route('muat-turun.bahan', $material) }}" class="tp-icon-action" style="flex-shrink:0" title="{{ __('Muat turun') }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span class="sr-only">{{ __('Muat turun :title', ['title' => $material->title]) }}</span>
                    </a>

                    <a href="{{ route('cikgu.bahan.edit', $material) }}" class="tp-btn-ghost" style="flex-shrink:0">
                        ✏️ {{ __('Sunting') }}
                    </a>

                    <form method="POST" action="{{ route('cikgu.bahan.destroy', $material) }}" style="flex-shrink:0"
                          onsubmit='return confirm(@js(__("Padam bahan \":title\"? Fail juga akan dipadam.", ["title" => $material->title])))'>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-icon-action tp-icon-danger" title="{{ __('Padam') }}">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            <span class="sr-only">{{ __('Padam :title', ['title' => $material->title]) }}</span>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div>{{ $materials->links() }}</div>
    @endif
</x-cikgu-layout>
