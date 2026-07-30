@php
    $editing = $quiz->exists;
    $type = old('type', $quiz->type);
@endphp

<x-cikgu-layout :title="$editing ? __('Sunting Kuiz') : __('Kuiz Baru')"
    :heading="$editing ? __('Sunting Kuiz') : __('Kuiz Baru')"
    :sub="__('Kuiz interaktif yang menanda sendiri, dan kuiz bercetak')">

    <form method="POST"
          action="{{ $editing ? route('cikgu.kuiz.update', $quiz) : route('cikgu.kuiz.store') }}"
          enctype="multipart/form-data" class="tp-formwrap"
          x-data="quizForm({{ Js::from([
              'type' => $type,
              'translate' => [
                  'enabled' => $translatorEnabled,
                  'url' => route('cikgu.kuiz.terjemah-meta'),
                  'failed' => __('Terjemahan gagal. Sila cuba lagi.'),
                  'needTitle' => __('Isi tajuk dahulu.'),
                  'en2ms' => __('English → Bahasa Melayu'),
                  'ms2en' => __('Bahasa Melayu → English'),
                  'hint' => __('Terjemah tajuk & penerangan. Semak sebelum simpan.'),
              ],
              'titleT' => old('title_translated', $quiz->title_translated),
              'descriptionT' => old('description_translated', $quiz->description_translated),
              'sourceLocale' => old('source_locale', $quiz->source_locale),
          ]) }})"
        @csrf
        @if ($editing) @method('PUT') @endif
        <input type="hidden" name="type" :value="type">

        <a href="{{ route('cikgu.kuiz.index') }}" class="tp-back">← {{ __('Kuiz Saya') }}</a>

        @if ($editing && ($hasAttempts ?? false))
            <div style="display:flex;gap:10px;background:#FEF0CE;border:1px solid rgba(138,106,18,.25);border-radius:14px;padding:14px 18px;font-size:13.5px;color:#8A6A12">
                <span>⚠️</span>
                <div>{{ __('Kuiz ini sudah ada percubaan murid. Menukar soalan akan menggantikan semua soalan lama, dan semakan jawapan percubaan lama tidak lagi dapat dipaparkan. Mata dan ranking yang sudah diperoleh murid kekal tidak berubah.') }}</div>
            </div>
        @endif

        {{-- Quiz type --}}
        <div class="tp-panelform">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Jenis kuiz') }}</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <button type="button" @click="type = 'interactive'" class="tp-typeopt" :class="{ 'is-on': type === 'interactive' }" :aria-pressed="type === 'interactive'">
                    <span class="tp-typeopt-head">
                        <span style="font-size:16px;flex-shrink:0">📝</span>
                        <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink)">{{ __('Kuiz Interaktif') }}</span>
                    </span>
                    <span style="font-size:12.5px;color:var(--tp-muted-2);line-height:1.45">{{ __('Ditanda secara automatik. Memberi mata ranking.') }}</span>
                </button>
                <button type="button" @click="type = 'file'" class="tp-typeopt" :class="{ 'is-on': type === 'file' }" :aria-pressed="type === 'file'">
                    <span class="tp-typeopt-head">
                        <span style="font-size:16px;flex-shrink:0">📄</span>
                        <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink)">{{ __('Kuiz Bercetak') }}</span>
                    </span>
                    <span style="font-size:12.5px;color:var(--tp-muted-2);line-height:1.45">{{ __('Fail untuk dimuat turun. Tiada mata.') }}</span>
                </button>
            </div>
            @error('type') <span class="tp-error">{{ $message }}</span> @enderror
        </div>

        {{-- Location --}}
        <div class="tp-panelform">
            <div style="display:flex;flex-direction:column;gap:3px">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Lokasi kuiz') }}</h2>
                <span style="font-size:13px;color:var(--tp-muted)">{{ __('Kuiz ini akan dipaparkan pada halaman Bab tersebut.') }}</span>
            </div>
            <x-chapter-picker :subjects="$subjects" :grades="$grades" :chapter="$chapter" />
        </div>

        {{-- Details --}}
        <div class="tp-panelform">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Butiran kuiz') }}</h2>
            {{-- A single shared title for an interactive quiz (and when editing any quiz). Printed
                 quizzes created in a batch are titled per file in the drop zone below instead. --}}
            <div class="tp-field" x-show="type === 'interactive' || {{ $editing ? 'true' : 'false' }}" @unless ($editing) x-cloak @endunless>
                <label for="title" class="tp-label">{{ __('Tajuk') }}</label>
                <input id="title" name="title" type="text" value="{{ old('title', $quiz->title) }}" class="tp-input" @error('title') aria-invalid="true" @enderror>
                @error('title') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
            <div class="tp-field">
                <label for="description" class="tp-label">{{ __('Penerangan (pilihan)') }}</label>
                <textarea id="description" name="description" rows="3" class="tp-textarea">{{ old('description', $quiz->description) }}</textarea>
                @error('description') <span class="tp-error">{{ $message }}</span> @enderror
            </div>

            @if ($translatorEnabled)
                {{-- Auto-translate the title + description for the language toggle. Editable so a
                     wrong machine translation is corrected before students see it; runs on save too,
                     so it is optional. Follows the title field's visibility (title-per-file quizzes
                     translate their titles in the drop zone instead). --}}
                <div x-show="type === 'interactive' || {{ $editing ? 'true' : 'false' }}" @unless ($editing) x-cloak @endunless
                     style="border-top:1px solid var(--tp-line);padding-top:14px;display:flex;flex-direction:column;gap:12px">
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                        <div style="flex:1;min-width:180px;display:flex;flex-direction:column;gap:2px">
                            <span class="tp-g" style="font-size:14px;font-weight:800;color:var(--tp-ink)">{{ __('Terjemahan automatik') }}</span>
                            <span style="font-size:12.5px;color:var(--tp-muted)"
                                  x-text="sourceLocale ? (sourceLocale === 'en' ? translate.en2ms : translate.ms2en) : translate.hint"></span>
                        </div>
                        <span style="font-size:12.5px;font-weight:700;color:#C24936" x-show="translateError" x-cloak x-text="translateError"></span>
                        <button type="button" class="tp-btn-outline tp-btn-sm" @click="translateMeta()" :disabled="translating">
                            <span x-show="! translating">✦ {{ __('Terjemah automatik') }}</span>
                            <span x-show="translating" x-cloak>{{ __('Menterjemah...') }}</span>
                        </button>
                    </div>

                    <input type="hidden" name="source_locale" :value="sourceLocale || ''">

                    <div class="tp-field">
                        <label for="title_translated" class="tp-label">{{ __('Tajuk (terjemahan)') }}</label>
                        <input id="title_translated" name="title_translated" type="text" x-model="titleT" class="tp-input" placeholder="{{ __('Terjemahan tajuk') }}">
                    </div>
                    <div class="tp-field">
                        <label for="description_translated" class="tp-label">{{ __('Penerangan (terjemahan)') }}</label>
                        <textarea id="description_translated" name="description_translated" rows="3" x-model="descriptionT" class="tp-textarea" placeholder="{{ __('Terjemahan penerangan') }}"></textarea>
                    </div>
                </div>
            @endif

            {{-- Interactive only --}}
            <div x-show="type === 'interactive'" x-cloak class="tp-field">
                <label for="duration_minutes" class="tp-label">{{ __('Had masa dalam minit (pilihan)') }}</label>
                <input id="duration_minutes" name="duration_minutes" type="number" min="1" max="180"
                       value="{{ old('duration_minutes', $quiz->duration_minutes) }}" class="tp-input" aria-describedby="duration-help" @error('duration_minutes') aria-invalid="true" @enderror>
                <p id="duration-help" class="tp-hint">{{ __('Biarkan kosong untuk kuiz tanpa had masa. Jika ditetapkan, jawapan murid dihantar secara automatik apabila masa tamat.') }}</p>
                @error('duration_minutes') <span class="tp-error">{{ $message }}</span> @enderror
            </div>

            {{-- File only --}}
            @if ($editing)
                {{-- Editing replaces the one file this quiz points at, so it stays single. --}}
                <div x-show="type === 'file'" x-cloak class="tp-field">
                    <label for="file" class="tp-label">{{ __('Fail kuiz') }}</label>
                    <x-file-input id="file" name="file" accept=".pdf,.doc,.docx" aria-describedby="quiz-file-help" @error('file') aria-invalid="true" @enderror />
                    <p id="quiz-file-help" class="tp-hint">
                        {{ __('PDF, DOC atau DOCX. Had saiz :size MB.', ['size' => config('lms.quiz_file_max_mb')]) }}
                        @if ($quiz->file_path) {{ __('Biarkan kosong untuk mengekalkan fail sedia ada (:name).', ['name' => $quiz->original_name]) }} @endif
                    </p>
                    @error('file') <span class="tp-error">{{ $message }}</span> @enderror
                </div>
            @else
                {{-- Creating takes many files at once, each becoming its own printed quiz, titled
                     per row — the same drop zone the material and video uploads use. --}}
                <div x-show="type === 'file'" x-cloak>
                    <x-file-dropzone
                        name="files[]"
                        title-name="titles[]"
                        :extensions="config('lms.quiz_file_mimes')"
                        :accept="collect(config('lms.quiz_file_mimes'))->map(fn ($e) => '.'.$e)->implode(',')"
                        :max-mb="config('lms.quiz_file_max_mb')"
                        :max-files="\App\Http\Requests\QuizRequest::MAX_FILES"
                        :hint="__('PDF, DOC atau DOCX. Had saiz :size MB setiap fail.', ['size' => config('lms.quiz_file_max_mb')])"
                        :title-label="__('Tajuk kuiz (untuk pelajar)')" />

                    @error('files') <span class="tp-error">{{ $message }}</span> @enderror
                    @foreach ($errors->get('files.*') as $messages)
                        <span class="tp-error">{{ $messages[0] }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Scramble answer options (interactive quizzes only). --}}
        <label for="shuffle_options" class="tp-checkrow" x-show="type === 'interactive'" x-cloak>
            <input id="shuffle_options" name="shuffle_options" type="checkbox" value="1" @checked(old('shuffle_options', $quiz->shuffle_options ?? false)) style="width:20px;height:20px;margin-top:2px;accent-color:#17907B">
            <span style="display:flex;flex-direction:column;gap:2px">
                <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ __('Acak susunan jawapan') }}</span>
                <span style="font-size:12.5px;color:var(--tp-muted)">{{ __('Setiap murid melihat pilihan jawapan dalam susunan rawak.') }}</span>
            </span>
        </label>

        {{-- Publish --}}
        <label for="is_published" class="tp-checkrow">
            <input id="is_published" name="is_published" type="checkbox" value="1" @checked(old('is_published', $quiz->is_published ?? true)) style="width:20px;height:20px;margin-top:2px;accent-color:#17907B">
            <span style="display:flex;flex-direction:column;gap:2px">
                <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ __('Terbitkan kepada murid') }}</span>
                <span style="font-size:12.5px;color:var(--tp-muted)">{{ __('Nyahtanda untuk simpan sebagai draf.') }}</span>
            </span>
        </label>

        <div style="display:flex;gap:12px;flex-wrap:wrap">
            <button type="submit" class="tp-btn" style="min-height:48px">
                <span x-show="type === 'interactive' && ! {{ $editing ? 'true' : 'false' }}" x-cloak>{{ __('Seterusnya: Tambah Soalan') }}</span>
                <span x-show="type === 'file' || {{ $editing ? 'true' : 'false' }}" @unless ($editing) x-cloak @endunless>{{ $editing ? __('Simpan Perubahan') : __('Simpan Kuiz') }}</span>
            </button>

            @if ($editing && $quiz->isInteractive())
                <a href="{{ route('cikgu.kuiz.soalan', $quiz) }}" class="tp-btn-outline" style="min-height:48px">📝 {{ __('Sunting Soalan') }}</a>
            @endif

            <a href="{{ route('cikgu.kuiz.index') }}" class="tp-btn-outline" style="min-height:48px">{{ __('Batal') }}</a>
        </div>
    </form>

    @push('scripts')
        <script>
            function quizForm({ type, translate, titleT, descriptionT, sourceLocale }) {
                return {
                    type,
                    translate,
                    titleT: titleT ?? '',
                    descriptionT: descriptionT ?? '',
                    sourceLocale: sourceLocale ?? '',
                    translating: false,
                    translateError: '',
                    csrf() { return document.querySelector('input[name="_token"]')?.value ?? ''; },
                    async translateMeta() {
                        if (! this.translate.enabled || this.translating) return;
                        const title = document.getElementById('title')?.value?.trim() ?? '';
                        const description = document.getElementById('description')?.value?.trim() ?? '';
                        if (! title) { this.translateError = this.translate.needTitle; return; }
                        this.translateError = '';
                        this.translating = true;
                        try {
                            const res = await fetch(this.translate.url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                                body: JSON.stringify({ title, description }),
                            });
                            if (! res.ok) {
                                const err = await res.json().catch(() => ({}));
                                throw new Error(err.message || this.translate.failed);
                            }
                            const data = await res.json();
                            this.titleT = data.title ?? '';
                            this.descriptionT = data.description ?? '';
                            this.sourceLocale = data.source_locale ?? '';
                        } catch (e) {
                            this.translateError = e.message || this.translate.failed;
                        } finally {
                            this.translating = false;
                        }
                    },
                };
            }
        </script>
    @endpush
</x-cikgu-layout>
