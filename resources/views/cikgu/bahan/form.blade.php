@php($editing = $material->exists)

<x-cikgu-layout :title="$editing ? __('Sunting Bahan') : __('Bahan Baru')"
    :heading="$editing ? __('Sunting Bahan') : __('Bahan Baru')"
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
                <div x-data="{ del: false }" style="display:flex;flex-direction:column;gap:16px">
                    {{-- This material's own title. --}}
                    <div class="tp-field">
                        <label for="title" class="tp-label">{{ __('Tajuk') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $material->title) }}" class="tp-input" :disabled="del" @error('title') aria-invalid="true" @enderror>
                        @error('title') <span class="tp-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- Drag & drop more files into this chapter — each becomes a new material, so a
                         teacher can add a whole set of handouts from the edit page. --}}
                    <div class="tp-field">
                        <label class="tp-label">{{ __('Muat naik fail baharu (pilihan)') }}</label>
                        <x-file-dropzone
                            name="files[]"
                            title-name="titles[]"
                            :extensions="config('lms.material_mimes')"
                            :accept="collect(config('lms.material_mimes'))->map(fn ($e) => '.'.$e)->implode(',')"
                            :max-mb="config('lms.material_max_mb')"
                            :max-files="\App\Http\Requests\MaterialRequest::MAX_FILES"
                            :hint="__('Setiap fail menjadi bahan baharu dalam bab ini. Had saiz :max MB setiap fail.', ['max' => config('lms.material_max_mb')])"
                            :title-label="__('Tajuk (untuk pelajar)')" />
                        @error('files') <span class="tp-error">{{ $message }}</span> @enderror
                        @foreach ($errors->get('files.*') as $messages)<span class="tp-error">{{ $messages[0] }}</span>@endforeach
                    </div>

                    {{-- The current file for this material, shown below, with a delete option. --}}
                    <div class="tp-field">
                        <label class="tp-label">{{ __('Fail semasa') }}</label>

                        {{-- x-show lives on the wrapper so it never overrides the card's own
                             display:flex (which would drop the row into a stacked column). --}}
                        <div x-show="! del">
                            <div style="display:flex;align-items:center;gap:14px;background:var(--tp-surface);border:1px solid var(--tp-line-2);border-radius:16px;padding:14px 16px">
                                <span style="width:46px;height:46px;border-radius:13px;background:#FBE4ED;color:#B84A75;display:grid;place-items:center;flex-shrink:0">
                                    <x-icon :name="$material->iconName()" class="h-[22px] w-[22px]" />
                                </span>
                                <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:2px">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->original_name }}</span>
                                    <span style="font-size:12.5px;color:var(--tp-muted-2);font-weight:700">{{ strtoupper(pathinfo($material->original_name, PATHINFO_EXTENSION)) }} · {{ $material->humanSize() }}</span>
                                </div>
                                <button type="button" @click="del = true" title="{{ __('Padam bahan ini') }}"
                                        style="display:inline-flex;align-items:center;gap:7px;border:1.5px solid rgba(194,73,54,.28);background:#FDF1EE;cursor:pointer;color:#C24936;font-weight:800;font-size:13px;border-radius:11px;padding:9px 14px;flex-shrink:0">
                                    <x-icon name="trash" class="h-4 w-4" />
                                    {{ __('Padam') }}
                                </button>
                            </div>
                        </div>

                        <div x-show="del" x-cloak>
                            <div style="display:flex;align-items:center;gap:10px;background:#FDE7E0;border:1px solid rgba(194,73,54,.25);border-radius:12px;padding:12px 14px;font-size:13px;color:#C24936">
                                <span style="flex:1">{{ __('Bahan ini akan dipadam apabila anda simpan.') }}</span>
                                <button type="button" @click="del = false" style="border:none;background:transparent;cursor:pointer;color:#0F7A68;font-weight:800;font-size:13px;flex-shrink:0">{{ __('Kembalikan') }}</button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="delete_material" :value="del ? 1 : 0">
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
