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

        @include('admin.kandungan._filters', ['subjects' => $subjects, 'grades' => $grades, 'action' => route('admin.kandungan.bahan')])

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
                                                'teacher' => $material->teacher?->name,
                                                'kind' => $material->previewKind(),
                                                'src' => $material->fileUrl(),
                                                'name' => $material->original_name,
                                                'type' => strtoupper($material->extension()),
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

        {{-- Preview modal. PDFs/images render in place; Office files offer the download rather than a fake render. --}}
        <template x-if="item">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 role="dialog" aria-modal="true" :aria-label="item.title">
                <div class="absolute inset-0 bg-black/70" @click="close()" aria-hidden="true"></div>

                <div class="relative flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-card border border-line bg-surface shadow-hero">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="truncate font-extrabold text-ink" x-text="item.title"></h2>
                            <p class="truncate text-xs text-ink-2">
                                <span x-text="item.teacher"></span> &middot; <span x-text="item.name"></span>
                            </p>
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            <a :href="item.downloadUrl" class="btn-secondary btn-sm">
                                <x-icon name="download" class="h-4 w-4" />
                                {{ __('Muat Turun') }}
                            </a>

                            <button type="button" class="btn-ghost btn-sm" @click="close()" x-init="$el.focus()">
                                <x-icon name="x" class="h-4 w-4" />
                                <span class="sr-only">{{ __('Tutup') }}</span>
                            </button>
                        </div>
                    </div>

                    <template x-if="item.kind === 'pdf'">
                        <iframe class="h-[70vh] w-full bg-black" :src="item.src" :title="item.title"></iframe>
                    </template>

                    <template x-if="item.kind === 'image'">
                        <div class="flex max-h-[70vh] justify-center overflow-auto bg-surface-2 p-4">
                            <img :src="item.src" :alt="item.title" class="max-w-full object-contain">
                        </div>
                    </template>

                    <template x-if="item.kind === 'none'">
                        <div class="px-6 py-12 text-center">
                            <p class="font-bold text-ink">
                                <span x-text="item.type"></span>
                                {{ __('tidak boleh dipaparkan dalam pelayar.') }}
                            </p>
                            <p class="mx-auto mt-1 max-w-prose text-sm text-ink-2">
                                {{ __('Sila muat turun fail untuk membukanya.') }}
                            </p>

                            <a :href="item.downloadUrl" class="btn-primary btn-sm mt-4">
                                <x-icon name="download" class="h-4 w-4" />
                                {{ __('Muat Turun') }}
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
