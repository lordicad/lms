@php($editing = $material->exists)

<x-cikgu-layout :title="$editing ? __('Sunting Bahan') : __('Bahan Baru')"
    :heading="$editing ? __('Sunting Bahan') : __('Bahan Baru')"
    heading-icon="file"
    :sub="__('Slaid, PDF dan lembaran kerja yang menyokong video anda')">

    <form method="POST"
          action="{{ $editing ? route('cikgu.bahan.update', $material) : route('cikgu.bahan.store') }}"
          enctype="multipart/form-data" class="tp-formwrap">
        @csrf
        @if ($editing) @method('PUT') @endif

        <a href="{{ route('cikgu.bahan.index') }}" class="tp-back">← {{ __('Bahan Bantu Mengajar') }}</a>

        {{-- Location --}}
        <div class="tp-panelform">
            <div style="display:flex;flex-direction:column;gap:3px">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Lokasi bahan') }}</h2>
                <span style="font-size:13px;color:var(--tp-muted)">{{ __('Bahan ini akan dipaparkan pada halaman Bab tersebut.') }}</span>
            </div>

            <x-chapter-picker :subjects="$subjects" :grades="$grades" :chapter="$chapter" />

            {{-- Attach to a video (optional). The list loads the chosen Bab's own videos. --}}
            <div class="tp-field" style="border-top:1px solid var(--tp-line);padding-top:16px"
                 x-data="videoAttach({
                     selected: {{ old('lesson_id', $material->lesson_id) ?: 'null' }},
                     preset: @js($lessons->map(fn ($l) => ['id' => $l->id, 'title' => $l->title])->values()),
                     endpoint: '{{ route('api.bab.video') }}',
                     labels: { loading: @js(__('Memuatkan video...')), none: @js(__('Tiada. Papar pada halaman Bab sahaja.')) },
                 })"
                 @chapter-changed.window="onChapter($event.detail.chapter)">
                <label for="lesson_id" class="tp-label">{{ __('Lampirkan pada video (pilihan)') }}</label>
                <select id="lesson_id" name="lesson_id" class="tp-select js-styled-select" x-model.number="selected" :disabled="loading">
                    <option value="" x-text="loading ? labels.loading : labels.none"></option>
                    <template x-for="v in videos" :key="v.id">
                        <option :value="v.id" x-text="v.title"></option>
                    </template>
                </select>
                <p class="tp-hint" x-show="! loading && videos.length === 0" x-cloak>{{ __('Tiada video dalam bab ini lagi. Pilih bab yang mempunyai video, atau biarkan kosong.') }}</p>
                <p class="tp-hint">{{ __('Bahan yang dilampirkan dipaparkan di bawah pemain video, dalam bahagian "Bahan sokongan".') }}</p>
                @error('lesson_id') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- File --}}
        <div class="tp-panelform">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Fail') }}</h2>

            @if ($editing)
                <div style="display:flex;flex-direction:column;gap:16px">
                    {{-- This material's own title. --}}
                    <div class="tp-field">
                        <label for="title" class="tp-label">{{ __('Tajuk') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $material->title) }}" class="tp-input" @error('title') aria-invalid="true" @enderror>
                        @error('title') <span class="tp-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- The current file for this material. Read-only; the replace field below swaps it. --}}
                    <div class="tp-field">
                        <label class="tp-label">{{ __('Fail semasa') }}</label>
                        <div style="display:flex;align-items:center;gap:14px;background:var(--tp-surface);border:1px solid var(--tp-line-2);border-radius:16px;padding:14px 16px">
                            <span style="width:46px;height:46px;border-radius:13px;background:#FBE4ED;color:#B84A75;display:grid;place-items:center;flex-shrink:0">
                                <x-icon :name="$material->iconName()" class="h-[22px] w-[22px]" />
                            </span>
                            <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->original_name }}</span>
                                <span style="font-size:12.5px;color:var(--tp-muted-2);font-weight:700">{{ strtoupper(pathinfo($material->original_name, PATHINFO_EXTENSION)) }} · {{ $material->humanSize() }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Replace the file (optional). One file: it becomes this material's file, and the
                         old one is deleted on save. Leaving it empty keeps the current file. --}}
                    <div class="tp-field" x-data="{ picked: '' }">
                        <label for="file" class="tp-label">{{ __('Ganti fail (pilihan)') }}</label>
                        <label for="file"
                               @dragover.prevent="$el.dataset.drag = 1" @dragleave="$el.dataset.drag = 0"
                               @drop.prevent="$el.dataset.drag = 0; if ($event.dataTransfer.files.length) { $refs.file.files = $event.dataTransfer.files; picked = $event.dataTransfer.files[0].name }"
                               style="display:flex;align-items:center;gap:12px;cursor:pointer;border:1.5px dashed var(--tp-line-2);border-radius:16px;padding:16px;background:var(--tp-surface)">
                            <span style="width:42px;height:42px;border-radius:12px;background:color-mix(in oklab, var(--tp-teal) 14%, transparent);display:grid;place-items:center;flex-shrink:0">
                                <x-icon name="upload" class="h-5 w-5" style="color:var(--tp-teal)" />
                            </span>
                            <span style="flex:1;min-width:0;font-weight:800;font-size:13.5px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                                  x-text="picked || '{{ __('Seret & lepas fail di sini, atau klik untuk pilih') }}'"></span>
                        </label>
                        <input type="file" id="file" name="file" x-ref="file" class="sr-only"
                               accept="{{ collect(config('lms.material_mimes'))->map(fn ($e) => '.'.$e)->implode(',') }}"
                               @change="picked = $event.target.files[0]?.name || ''">
                        <p class="tp-hint">{{ __('Biarkan kosong untuk kekalkan fail semasa. Fail baharu akan menggantikan fail lama. Had saiz :max MB.', ['max' => config('lms.material_max_mb')]) }}</p>
                        @error('file') <span class="tp-error">{{ $message }}</span> @enderror
                    </div>
                </div>
            @else
                <x-file-dropzone
                    name="files[]"
                    title-name="titles[]"
                    :extensions="config('lms.material_mimes')"
                    :accept="collect(config('lms.material_mimes'))->map(fn ($e) => '.'.$e)->implode(',')"
                    :max-mb="config('lms.material_max_mb')"
                    :max-files="\App\Http\Requests\MaterialRequest::MAX_FILES"
                    :hint="__('PDF, PowerPoint, Word, Excel atau imej. Had saiz :max MB setiap fail.', ['max' => config('lms.material_max_mb')])"
                    :title-label="__('Tajuk (untuk pelajar)')" />

                @error('files') <span class="tp-error">{{ $message }}</span> @enderror
                @foreach ($errors->get('files.*') as $messages)
                    <span class="tp-error">{{ $messages[0] }}</span>
                @endforeach
            @endif
        </div>

        <div style="display:flex;gap:12px">
            <button type="submit" class="tp-btn" style="min-height:48px">{{ $editing ? __('Simpan Perubahan') : __('Muat Naik Bahan') }}</button>
            <a href="{{ route('cikgu.bahan.index') }}" class="tp-btn-outline" style="min-height:48px">{{ __('Batal') }}</a>
        </div>
    </form>

    @push('scripts')
        <script>
            function videoAttach({ selected, preset, endpoint, labels }) {
                return {
                    selected,
                    videos: preset ?? [],
                    endpoint,
                    labels,
                    loading: false,

                    onChapter(chapterId) {
                        if (! chapterId) { this.videos = []; return; }

                        const keep = this.selected;
                        this.loading = true;

                        fetch(`${this.endpoint}?chapter=${chapterId}`, { headers: { 'Accept': 'application/json' } })
                            .then((response) => response.ok ? response.json() : [])
                            .then((data) => {
                                this.videos = data;
                                // Keep the current pick only if it belongs to the new Bab.
                                this.$nextTick(() => {
                                    this.selected = data.some((v) => v.id === keep) ? keep : null;
                                });
                            })
                            .catch(() => { this.videos = []; })
                            .finally(() => { this.loading = false; });
                    },
                };
            }
        </script>
    @endpush
</x-cikgu-layout>
