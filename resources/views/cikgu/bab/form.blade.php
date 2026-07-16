<x-app-layout :title="__('Namakan Semula Bab')">
    <div class="mx-auto max-w-2xl" style="--sc: {{ $chapter->subject->rgb }}">
        <a href="{{ route('cikgu.bab.index', ['subjek' => $chapter->subject->slug, 'tahun' => $chapter->grade->level]) }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Pengurusan Bab') }}
        </a>

        <header class="mt-4">
            <span class="chip bg-subject-wash text-subject-ink">
                {{ $chapter->subject->icon }} {{ $chapter->subject->name }}. {{ $chapter->grade->name }}
            </span>

            <h1 class="mt-2 text-3xl font-extrabold text-ink">Bab {{ $chapter->number }}</h1>
        </header>

        <form method="POST" action="{{ route('cikgu.bab.update', $chapter) }}" class="card card-pad mt-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="label">{{ __('Tajuk bab') }}</label>

                <input id="title" name="title" type="text" value="{{ old('title', $chapter->title) }}"
                       required autofocus class="input" @error('title') aria-invalid="true" @enderror>

                @error('title')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="label">{{ __('Penerangan (pilihan)') }}</label>

                <textarea id="description" name="description" rows="3"
                          class="input py-3">{{ old('description', $chapter->description) }}</textarea>

                @error('description')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">{{ __('Simpan') }}</button>

                <a href="{{ route('cikgu.bab.index', ['subjek' => $chapter->subject->slug, 'tahun' => $chapter->grade->level]) }}"
                   class="btn-secondary">{{ __('Batal') }}</a>
            </div>
        </form>

        <p class="help mt-4">
            {{ __('Nombor bab tidak boleh ditukar. Ia mengikut susunan bab dalam Subjek dan Tahun ini.') }}
        </p>
    </div>
</x-app-layout>
