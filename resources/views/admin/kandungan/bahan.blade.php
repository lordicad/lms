@php
    $cols = 'grid-template-columns:minmax(0,2fr) 1.3fr .8fr .8fr 1.2fr 1fr 1.4fr;gap:12px;align-items:center';
    $stats = [
        ['icon' => '📁', 'label' => __('Jumlah bahan'), 'value' => $totalCount],
        ['icon' => '📕', 'label' => 'PDF',              'value' => $pdfCount],
        ['icon' => '📄', 'label' => 'DOCX',             'value' => $docxCount],
        ['icon' => '📊', 'label' => 'PPTX',             'value' => $pptxCount],
    ];
@endphp

<x-admin-layout :title="__('Kandungan Bahan')"
                :heading="__('Kandungan Bahan')"
                :sub="__('Setiap bahan pengajaran yang dimuat naik oleh cikgu, merentas semua subjek dan Tahun')">

    <div style="display:flex;flex-direction:column;gap:18px"
         x-data="{
             item: null,
             open(data) { this.item = data; document.body.classList.add('overflow-hidden'); },
             close() { this.item = null; document.body.classList.remove('overflow-hidden'); },
         }"
         @keydown.escape.window="close()">

        @include('admin.kandungan._tabs', ['active' => 'bahan'])

        {{-- Stats --}}
        <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
            @foreach ($stats as $s)
                <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                    <span style="font-size:13.5px;font-weight:700;color:var(--tp-muted)">{{ $s['icon'] }} {{ $s['label'] }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:var(--tp-ink)">{{ number_format($s['value']) }}</span>
                </div>
            @endforeach
        </div>

        <x-year-subject-filter :action="route('admin.kandungan.bahan')" :grades="$grades" :subjects="$subjects" :filter="$filter" />

        @if ($materials->isEmpty())
            <div class="tp-empty">
                <span style="font-size:30px">📁</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Tiada bahan untuk dipaparkan') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Tiada bahan yang sepadan dengan tapisan ini.') }}</p>
            </div>
        @else
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <div style="overflow-x:auto">
                    <div style="min-width:900px">
                        <div style="display:grid;{{ $cols }};padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tajuk Bahan') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Subjek') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tahun') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Muat Turun') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Jenis') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tarikh Siar') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:right">{{ __('Tindakan') }}</span>
                        </div>
                        @foreach ($materials as $material)
                            <div class="tp-tr" style="display:grid;{{ $cols }};padding:12px 20px;border-bottom:1px solid var(--tp-line)">
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $material->title }}</span>
                                    <span style="font-size:11.5px;color:var(--tp-muted)">{{ $material->teacher?->name }}</span>
                                </div>
                                <span style="font-size:13px;font-weight:700;color:#4276AE">{{ $material->chapter->subject->displayName() }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ $material->chapter->grade->name }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ number_format($material->download_count) }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">📄 {{ strtoupper($material->extension()) }} <span style="font-size:11.5px;color:var(--tp-muted)">{{ $material->humanSize() }}</span></span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ $material->created_at->translatedFormat('j M Y') }}</span>
                                <div style="display:flex;justify-content:flex-end;gap:4px">
                                    <button type="button" class="tp-linkbtn"
                                            @click="open(@js([
                                                'title' => $material->title,
                                                'subtitle' => collect([$material->teacher?->name, $material->chapter->subject->displayName(), $material->chapter->grade->name])->filter()->implode(' · '),
                                                'kind' => $material->previewKind(),
                                                'src' => $material->fileUrl(),
                                                'name' => $material->original_name,
                                                'type' => strtoupper($material->extension()),
                                                'size' => $material->humanSize(),
                                                'downloadUrl' => route('muat-turun.bahan', $material),
                                            ]))">
                                        👁 {{ __('Lihat') }}<span class="sr-only">{{ $material->title }}</span>
                                    </button>
                                    <a href="{{ route('muat-turun.bahan', $material) }}" class="tp-linkbtn is-muted">
                                        ⬇ {{ __('Muat Turun') }}<span class="sr-only">{{ $material->title }}</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>{{ $materials->links() }}</div>
        @endif

        {{-- Preview modal (WeLearn Admin design): gradient header + document body.
             PDFs/images render in place; other files show a document card with a download. --}}
        <template x-if="item">
            <x-content-preview obj="item" :pill="'📄 '.__('Bahan')">
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
                        {{-- Document card --}}
                        <div style="width:min(440px,100%);aspect-ratio:1/1.28;background:#fff;border:1px solid rgba(46,44,80,.1);border-radius:10px;box-shadow:0 8px 28px rgba(46,44,80,.14);padding:32px 28px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;position:relative">
                            <span style="position:absolute;top:18px;right:18px;background:#3E86C9;color:#fff;border-radius:8px;padding:5px 11px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800" x-text="item.type"></span>
                            <div style="font-size:52px">📄</div>
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:#28293F;text-align:center;word-break:break-word" x-text="item.name"></span>
                            <span style="font-size:12.5px;color:#6C6F87;font-weight:700"><span x-text="item.type"></span> · <span x-text="item.size"></span></span>
                            <a :href="item.downloadUrl" style="margin-top:6px;display:inline-flex;align-items:center;gap:8px;min-height:42px;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;text-decoration:none">⬇ {{ __('Muat Turun') }}</a>
                        </div>
                        <span style="font-size:12.5px;color:#6C6F87;font-weight:700">{{ __('Fail ini tidak boleh dipaparkan dalam pelayar. Muat turun untuk membukanya.') }}</span>
                    </div>
                </template>
            </x-content-preview>
        </template>
    </div>
</x-admin-layout>
