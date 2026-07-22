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
                <select id="lesson_id" name="lesson_id" class="tp-select" x-model.number="selected" :disabled="loading">
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
            <div class="tp-field">
                <label for="title" class="tp-label">{{ __('Tajuk') }}</label>
                <input id="title" name="title" type="text" value="{{ old('title', $material->title) }}" required class="tp-input" @error('title') aria-invalid="true" @enderror>
                @error('title') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
            <div class="tp-field">
                <label for="file" class="tp-label">{{ __('Fail bahan') }}</label>
                <x-file-dropzone name="file" accept=".pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                                 aria-describedby="file-help" :aria-invalid="$errors->has('file') ? 'true' : null">
                    @if ($editing)
                        <x-slot:current>
                            <span style="font-size:18px">{{ $material->icon() }}</span>
                            {{ __('Fail semasa:') }} {{ $material->original_name }} ({{ $material->humanSize() }})
                        </x-slot:current>
                    @endif
                </x-file-dropzone>
                <p id="file-help" class="tp-hint">
                    {{ __('PDF, PowerPoint, Word, Excel atau imej.') }} {{ __('Had saiz :max MB.', ['max' => config('lms.material_max_mb')]) }}
                    @if ($editing) {{ __('Biarkan kosong untuk mengekalkan fail sedia ada.') }} @endif
                </p>
                @error('file') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
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
