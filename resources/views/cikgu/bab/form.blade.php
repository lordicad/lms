<x-cikgu-layout :title="__('Namakan Semula Bab')"
    :heading="'Bab '.$chapter->number"
    :sub="__('Namakan semula bab supaya sepadan dengan sukatan KSSR sekolah anda')">

    <div class="tp-formwrap" style="max-width:560px">
        <a href="{{ route('cikgu.bab.index', ['subjek' => $chapter->subject->slug, 'tahun' => $chapter->grade->level]) }}" class="tp-back">← {{ __('Pengurusan Bab') }}</a>

        <span style="align-self:flex-start;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800">{{ $chapter->subject->icon }} {{ $chapter->subject->name }}. {{ $chapter->grade->name }}</span>

        <form method="POST" action="{{ route('cikgu.bab.update', $chapter) }}" class="tp-panelform">
            @csrf
            @method('PUT')

            <div class="tp-field">
                <label for="title" class="tp-label">{{ __('Tajuk bab') }}</label>
                <input id="title" name="title" type="text" value="{{ old('title', $chapter->title) }}" required autofocus class="tp-input" @error('title') aria-invalid="true" @enderror>
                @error('title') <span class="tp-error">{{ $message }}</span> @enderror
            </div>

            <div class="tp-field">
                <label for="description" class="tp-label">{{ __('Penerangan (pilihan)') }}</label>
                <textarea id="description" name="description" rows="3" class="tp-textarea">{{ old('description', $chapter->description) }}</textarea>
                @error('description') <span class="tp-error">{{ $message }}</span> @enderror
            </div>

            <div style="display:flex;gap:10px">
                <button type="submit" class="tp-btn tp-btn-sm">{{ __('Simpan') }}</button>
                <a href="{{ route('cikgu.bab.index', ['subjek' => $chapter->subject->slug, 'tahun' => $chapter->grade->level]) }}" class="tp-btn-ghost">{{ __('Batal') }}</a>
            </div>
        </form>

        <span class="tp-hint">{{ __('Nombor bab tidak boleh ditukar. Ia mengikut susunan bab dalam Subjek dan Tahun ini.') }}</span>
    </div>
</x-cikgu-layout>
