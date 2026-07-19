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
          x-data="{ type: '{{ $type }}' }">
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
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:#28293F">{{ __('Jenis kuiz') }}</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <button type="button" @click="type = 'interactive'" class="tp-typeopt" :class="{ 'is-on': type === 'interactive' }" :aria-pressed="type === 'interactive'">
                    <span style="font-size:16px;flex-shrink:0">📝</span>
                    <span style="display:flex;flex-direction:column;gap:3px">
                        <span class="tp-g" style="font-weight:800;font-size:14px;color:#28293F">{{ __('Kuiz Interaktif') }}</span>
                        <span style="font-size:12.5px;color:#6C6F87;line-height:1.45">{{ __('Ditanda secara automatik. Memberi mata ranking.') }}</span>
                    </span>
                </button>
                <button type="button" @click="type = 'file'" class="tp-typeopt" :class="{ 'is-on': type === 'file' }" :aria-pressed="type === 'file'">
                    <span style="font-size:16px;flex-shrink:0">📄</span>
                    <span style="display:flex;flex-direction:column;gap:3px">
                        <span class="tp-g" style="font-weight:800;font-size:14px;color:#28293F">{{ __('Kuiz Bercetak') }}</span>
                        <span style="font-size:12.5px;color:#6C6F87;line-height:1.45">{{ __('Fail untuk dimuat turun. Tiada mata.') }}</span>
                    </span>
                </button>
            </div>
            @error('type') <span class="tp-error">{{ $message }}</span> @enderror
        </div>

        {{-- Location --}}
        <div class="tp-panelform">
            <div style="display:flex;flex-direction:column;gap:3px">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:#28293F">{{ __('Lokasi kuiz') }}</h2>
                <span style="font-size:13px;color:#8B8AA3">{{ __('Kuiz ini akan dipaparkan pada halaman Bab tersebut.') }}</span>
            </div>
            <x-chapter-picker :subjects="$subjects" :grades="$grades" :chapter="$chapter" />
        </div>

        {{-- Details --}}
        <div class="tp-panelform">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:#28293F">{{ __('Butiran kuiz') }}</h2>
            <div class="tp-field">
                <label for="title" class="tp-label">{{ __('Tajuk') }}</label>
                <input id="title" name="title" type="text" value="{{ old('title', $quiz->title) }}" required class="tp-input" @error('title') aria-invalid="true" @enderror>
                @error('title') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
            <div class="tp-field">
                <label for="description" class="tp-label">{{ __('Penerangan (pilihan)') }}</label>
                <textarea id="description" name="description" rows="3" class="tp-textarea">{{ old('description', $quiz->description) }}</textarea>
                @error('description') <span class="tp-error">{{ $message }}</span> @enderror
            </div>

            {{-- Interactive only --}}
            <div x-show="type === 'interactive'" x-cloak class="tp-field">
                <label for="duration_minutes" class="tp-label">{{ __('Had masa dalam minit (pilihan)') }}</label>
                <input id="duration_minutes" name="duration_minutes" type="number" min="1" max="180"
                       value="{{ old('duration_minutes', $quiz->duration_minutes) }}" class="tp-input" aria-describedby="duration-help" @error('duration_minutes') aria-invalid="true" @enderror>
                <p id="duration-help" class="tp-hint">{{ __('Biarkan kosong untuk kuiz tanpa had masa. Jika ditetapkan, jawapan murid dihantar secara automatik apabila masa tamat.') }}</p>
                @error('duration_minutes') <span class="tp-error">{{ $message }}</span> @enderror
            </div>

            {{-- File only --}}
            <div x-show="type === 'file'" x-cloak class="tp-field">
                <label for="file" class="tp-label">{{ __('Fail kuiz') }}</label>
                <input id="file" name="file" type="file" accept=".pdf,.doc,.docx" class="tp-file" aria-describedby="quiz-file-help" @error('file') aria-invalid="true" @enderror>
                <p id="quiz-file-help" class="tp-hint">
                    {{ __('PDF, DOC atau DOCX. Had saiz :size MB.', ['size' => config('lms.quiz_file_max_mb')]) }}
                    @if ($editing && $quiz->file_path) {{ __('Biarkan kosong untuk mengekalkan fail sedia ada (:name).', ['name' => $quiz->original_name]) }} @endif
                </p>
                @error('file') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Publish --}}
        <label for="is_published" class="tp-checkrow">
            <input id="is_published" name="is_published" type="checkbox" value="1" @checked(old('is_published', $quiz->is_published ?? true)) style="width:20px;height:20px;margin-top:2px;accent-color:#17907B">
            <span style="display:flex;flex-direction:column;gap:2px">
                <span class="tp-g" style="font-weight:800;font-size:14.5px;color:#28293F">{{ __('Terbitkan kepada murid') }}</span>
                <span style="font-size:12.5px;color:#8B8AA3">{{ __('Nyahtanda untuk simpan sebagai draf.') }}</span>
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
</x-cikgu-layout>
