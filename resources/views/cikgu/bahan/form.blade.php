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
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:#28293F">{{ __('Lokasi bahan') }}</h2>
                <span style="font-size:13px;color:#8B8AA3">{{ __('Bahan ini akan dipaparkan pada halaman Bab tersebut.') }}</span>
            </div>

            <x-chapter-picker :subjects="$subjects" :grades="$grades" :chapter="$chapter" />

            @if ($lessons->isNotEmpty() || $lesson)
                <div class="tp-field" style="border-top:1px solid var(--tp-line);padding-top:16px">
                    <label for="lesson_id" class="tp-label">{{ __('Lampirkan pada video (pilihan)') }}</label>
                    <select id="lesson_id" name="lesson_id" class="tp-select">
                        <option value="">{{ __('Tiada. Papar pada halaman Bab sahaja.') }}</option>
                        @foreach ($lessons as $option)
                            <option value="{{ $option->id }}" @selected(old('lesson_id', $material->lesson_id) == $option->id)>{{ $option->title }}</option>
                        @endforeach
                    </select>
                    <p class="tp-hint">{{ __('Bahan yang dilampirkan dipaparkan di bawah pemain video, dalam bahagian "Bahan sokongan".') }}</p>
                    @error('lesson_id') <span class="tp-error">{{ $message }}</span> @enderror
                </div>
            @endif
        </div>

        {{-- File --}}
        <div class="tp-panelform">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:#28293F">{{ __('Fail') }}</h2>
            <div class="tp-field">
                <label for="title" class="tp-label">{{ __('Tajuk') }}</label>
                <input id="title" name="title" type="text" value="{{ old('title', $material->title) }}" required class="tp-input" @error('title') aria-invalid="true" @enderror>
                @error('title') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
            <div class="tp-field">
                <label for="file" class="tp-label">{{ __('Fail bahan') }}</label>
                <input id="file" name="file" type="file" accept=".pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" class="tp-file" aria-describedby="file-help" @error('file') aria-invalid="true" @enderror>
                <p id="file-help" class="tp-hint">
                    {{ __('PDF, PowerPoint, Word, Excel atau imej.') }} {{ __('Had saiz :max MB.', ['max' => config('lms.material_max_mb')]) }}
                    @if ($editing) {{ __('Biarkan kosong untuk mengekalkan fail sedia ada.') }} @endif
                </p>
                @error('file') <span class="tp-error">{{ $message }}</span> @enderror
                @if ($editing)
                    <p style="display:flex;align-items:center;gap:8px;background:var(--tp-input);border-radius:12px;padding:12px 14px;font-size:13.5px;color:#6C6F87;margin:6px 0 0">
                        <span style="font-size:18px">{{ $material->icon() }}</span>
                        {{ __('Fail semasa:') }} {{ $material->original_name }} ({{ $material->humanSize() }})
                    </p>
                @endif
            </div>
        </div>

        <div style="display:flex;gap:12px">
            <button type="submit" class="tp-btn" style="min-height:48px">{{ $editing ? __('Simpan Perubahan') : __('Muat Naik Bahan') }}</button>
            <a href="{{ route('cikgu.bahan.index') }}" class="tp-btn-outline" style="min-height:48px">{{ __('Batal') }}</a>
        </div>
    </form>
</x-cikgu-layout>
