<x-cikgu-layout
    :title="__('Bahan Bantu Mengajar')"
    :heading="__('Bahan Bantu Mengajar')"
    :sub="__('Slaid, PDF dan lembaran kerja yang menyokong video anda')">

    {{-- Total materials uploaded by this teacher (all-time, not the filtered count). --}}
    <div class="tp-stat" style="max-width:340px;margin-bottom:18px">
        <div style="display:flex;align-items:center;gap:10px">
            <span class="tp-stat-ico" style="background:#DCF2EE"><x-icon name="file" class="h-[18px] w-[18px]" style="color:#0F7A68" /></span>
            <span class="tp-stat-label">{{ __('Bahan Saya') }}</span>
        </div>
        <span class="tp-stat-value">{{ number_format($totalMaterials) }}</span>
        <span style="font-size:12.5px;font-weight:700;color:var(--tp-muted)">{{ __('Bahan pengajaran') }}</span>
    </div>

    <x-year-subject-filter :subjects="$subjects" :grades="$grades" :filter="$filter" with-chapter :action="route('cikgu.bahan.index')">
        <a href="{{ route('cikgu.bahan.create') }}" class="tp-btn" style="margin-left:auto">
            <x-icon name="plus" class="h-4 w-4" />
            {{ __('Bahan Baru') }}
        </a>
    </x-year-subject-filter>

    {{-- Clicking a material's name opens a preview here, the same shell the admin content list uses.
         PDFs and images render in place; anything the browser can't show offers a download. --}}
    <div x-data="{
             item: null,
             open(data) { this.item = data; document.body.classList.add('overflow-hidden'); },
             close() { this.item = null; document.body.classList.remove('overflow-hidden'); },
         }"
         @keydown.escape.window="close()">

    @if ($materials->isEmpty())
        <div class="tp-empty">
            <x-icon name="file" class="h-8 w-8" style="color:var(--tp-muted)" />
            <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada bahan') }}</h3>
            <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Muat naik slaid, PDF atau lembaran kerja untuk menyokong pembelajaran murid.') }}</p>
            <a href="{{ route('cikgu.bahan.create') }}" class="tp-btn" style="margin-top:6px">{{ __('Muat Naik Bahan') }}</a>
        </div>
    @else
        <div class="tp-list">
            @foreach ($materials as $material)
                @php($subject = $material->chapter->subject)
                @php($preview = [
                    'title' => $material->title,
                    'subtitle' => collect([$subject->name, $material->chapter->grade->name, __('Bab :n', ['n' => $material->chapter->number])])->implode(' · '),
                    'kind' => $material->previewKind(),
                    'src' => $material->fileUrl(),
                    'name' => $material->original_name,
                    'type' => strtoupper($material->extension()),
                    'size' => $material->humanSize(),
                    'downloadUrl' => route('muat-turun.bahan', $material),
                ])
                <div class="tp-listcard">
                    <button type="button" @click="open(@js($preview))" title="{{ __('Lihat bahan') }}"
                            style="width:46px;height:46px;border-radius:11px;background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }});display:grid;place-items:center;flex-shrink:0;border:none;cursor:pointer"><x-icon :name="$material->iconName()" class="h-5 w-5" /></button>

                    <div style="display:flex;flex-direction:column;gap:6px;min-width:0;flex:1">
                        <button type="button" @click="open(@js($preview))" class="tp-g" style="text-align:left;background:none;border:none;padding:0;cursor:pointer;font-family:inherit;font-weight:800;font-size:15.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $material->title }}</button>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="tp-tag" style="background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }})">{{ $subject->name }}</span>
                            <span class="tp-meta">{{ $material->chapter->grade->name }}</span>
                            <span class="tp-meta">Bab {{ $material->chapter->number }}</span>
                            @if ($material->lesson)
                                <span class="tp-meta" style="display:inline-flex;align-items:center;gap:4px;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><x-icon name="video" class="h-[13px] w-[13px]" style="flex-shrink:0" />{{ $material->lesson->title }}</span>
                            @endif
                            <span class="tp-tag-neutral">{{ strtoupper($material->extension()) }}</span>
                            <span class="tp-meta">{{ $material->humanSize() }}</span>
                            <span class="tp-meta" style="display:inline-flex;align-items:center;gap:4px"><x-icon name="download" class="h-[13px] w-[13px]" />{{ $material->download_count }}</span>
                        </div>
                    </div>

                    <a href="{{ route('muat-turun.bahan', $material) }}" class="tp-icon-action" style="flex-shrink:0" title="{{ __('Muat turun') }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span class="sr-only">{{ __('Muat turun :title', ['title' => $material->title]) }}</span>
                    </a>

                    <a href="{{ route('cikgu.bahan.edit', $material) }}" class="tp-btn-ghost" style="flex-shrink:0;display:inline-flex;align-items:center;gap:6px">
                        <x-icon name="pencil" class="h-4 w-4" />{{ __('Sunting') }}
                    </a>

                    <form method="POST" action="{{ route('cikgu.bahan.destroy', $material) }}" style="flex-shrink:0"
                          onsubmit="return confirm(@js(__("Padam bahan \":title\"? Fail juga akan dipadam.", ["title" => $material->title])))">
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

        {{-- Preview modal: PDFs and images render in place, other files show a document card with a
             download. Shared admin shell so both surfaces stay in step. --}}
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
