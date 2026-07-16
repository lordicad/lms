@php($editing = $material->exists)

<x-app-layout :title="$editing ? __('Sunting Bahan') : __('Bahan Baharu')">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('cikgu.bahan.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Bahan Bantu Mengajar') }}
        </a>

        <h1 class="mt-4 text-3xl font-extrabold text-ink">
            {{ $editing ? __('Sunting Bahan') : __('Bahan Baharu') }}
        </h1>

        <form method="POST"
              action="{{ $editing ? route('cikgu.bahan.update', $material) : route('cikgu.bahan.store') }}"
              enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <section class="card card-pad">
                <h2 class="text-xl font-extrabold text-ink">{{ __('Lokasi bahan') }}</h2>
                <p class="help mb-4">{{ __('Bahan akan dipaparkan di halaman Bab ini.') }}</p>

                <x-chapter-picker :subjects="$subjects" :grades="$grades" :chapter="$chapter" />

                @if ($lessons->isNotEmpty() || $lesson)
                    <div class="mt-5 border-t border-line pt-5">
                        <label for="lesson_id" class="label">{{ __('Lampirkan pada video (pilihan)') }}</label>

                        <select id="lesson_id" name="lesson_id" class="input">
                            <option value="">{{ __('Tiada. Papar pada halaman Bab sahaja.') }}</option>

                            @foreach ($lessons as $option)
                                <option value="{{ $option->id }}"
                                    @selected(old('lesson_id', $material->lesson_id) == $option->id)>
                                    {{ $option->title }}
                                </option>
                            @endforeach
                        </select>

                        <p class="help">
                            {{ __('Bahan yang dilampirkan akan muncul di bawah pemain video, dalam bahagian "Bahan sokongan".') }}
                        </p>

                        @error('lesson_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </section>

            <section class="card card-pad space-y-5">
                <h2 class="text-xl font-extrabold text-ink">{{ __('Fail') }}</h2>

                <div>
                    <label for="title" class="label">{{ __('Tajuk') }}</label>

                    <input id="title" name="title" type="text" value="{{ old('title', $material->title) }}"
                           required class="input" @error('title') aria-invalid="true" @enderror>

                    @error('title')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="file" class="label">{{ __('Fail bahan') }}</label>

                    <input id="file" name="file" type="file"
                           accept=".pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                           class="input py-2.5 file:mr-3 file:rounded-control file:border-0 file:bg-brand-soft
                                  file:px-3 file:py-1.5 file:font-bold file:text-brand"
                           aria-describedby="file-help" @error('file') aria-invalid="true" @enderror>

                    <p id="file-help" class="help">
                        {{ __('PDF, PowerPoint, Word, Excel atau imej.') }} {{ __('Had saiz :max MB.', ['max' => config('lms.material_max_mb')]) }}
                        @if ($editing)
                            {{ __('Biarkan kosong untuk mengekalkan fail sedia ada.') }}
                        @endif
                    </p>

                    @error('file')
                        <p class="field-error">{{ $message }}</p>
                    @enderror

                    @if ($editing)
                        <p class="mt-3 flex items-center gap-2 rounded-card bg-surface-2 p-3 text-sm text-ink-2">
                            <span class="text-xl" aria-hidden="true">{{ $material->icon() }}</span>
                            {{ __('Fail semasa:') }} {{ $material->original_name }} ({{ $material->humanSize() }})
                        </p>
                    @endif
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">
                    {{ $editing ? __('Simpan Perubahan') : __('Muat Naik Bahan') }}
                </button>

                <a href="{{ route('cikgu.bahan.index') }}" class="btn-secondary">{{ __('Batal') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
