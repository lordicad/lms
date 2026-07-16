@php
    $editing = $quiz->exists;
    $type = old('type', $quiz->type);
@endphp

<x-app-layout :title="$editing ? __('Sunting Kuiz') : __('Kuiz Baharu')">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('cikgu.kuiz.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Kuiz Saya') }}
        </a>

        <h1 class="mt-4 text-3xl font-extrabold text-ink">
            {{ $editing ? __('Sunting Kuiz') : __('Kuiz Baharu') }}
        </h1>

        @if ($editing && ($hasAttempts ?? false))
            {{-- Honest about the consequence: points already earned stay, the old per-question
                 review does not survive a rebuild of the question set. --}}
            <x-alert type="warn" class="mt-4">
                {{ __('Kuiz ini sudah ada percubaan murid. Menukar soalan akan menggantikan semua soalan lama, dan semakan jawapan percubaan lama tidak lagi dapat dipaparkan. Mata dan ranking yang sudah diperoleh murid kekal tidak berubah.') }}
            </x-alert>
        @endif

        <form method="POST"
              action="{{ $editing ? route('cikgu.kuiz.update', $quiz) : route('cikgu.kuiz.store') }}"
              enctype="multipart/form-data" class="mt-6 space-y-6"
              x-data="{ type: '{{ $type }}' }">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <input type="hidden" name="type" :value="type">

            <section class="card card-pad">
                <h2 class="text-xl font-extrabold text-ink">{{ __('Jenis kuiz') }}</h2>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <button type="button" @click="type = 'interactive'"
                            class="flex items-start gap-3 rounded-card border-2 p-4 text-left transition-colors"
                            :class="type === 'interactive' ? 'border-brand bg-brand-soft' : 'border-line hover:border-brand'"
                            :aria-pressed="type === 'interactive'">
                        <x-icon name="quiz" class="mt-0.5 h-5 w-5 shrink-0 text-brand" />

                        <span>
                            <span class="block font-bold text-ink">{{ __('Kuiz Interaktif') }}</span>
                            <span class="block text-sm text-ink-2">{{ __('Disemak automatik. Memberi mata ranking.') }}</span>
                        </span>
                    </button>

                    <button type="button" @click="type = 'file'"
                            class="flex items-start gap-3 rounded-card border-2 p-4 text-left transition-colors"
                            :class="type === 'file' ? 'border-brand bg-brand-soft' : 'border-line hover:border-brand'"
                            :aria-pressed="type === 'file'">
                        <x-icon name="file" class="mt-0.5 h-5 w-5 shrink-0 text-ink-2" />

                        <span>
                            <span class="block font-bold text-ink">{{ __('Kuiz Bercetak') }}</span>
                            <span class="block text-sm text-ink-2">{{ __('Fail untuk dimuat turun. Tiada mata.') }}</span>
                        </span>
                    </button>
                </div>

                @error('type')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </section>

            <section class="card card-pad">
                <h2 class="text-xl font-extrabold text-ink">{{ __('Lokasi kuiz') }}</h2>
                <p class="help mb-4">{{ __('Kuiz akan dipaparkan di halaman Bab ini.') }}</p>

                <x-chapter-picker :subjects="$subjects" :grades="$grades" :chapter="$chapter" />
            </section>

            <section class="card card-pad space-y-5">
                <h2 class="text-xl font-extrabold text-ink">{{ __('Maklumat kuiz') }}</h2>

                <div>
                    <label for="title" class="label">{{ __('Tajuk') }}</label>

                    <input id="title" name="title" type="text" value="{{ old('title', $quiz->title) }}"
                           required class="input" @error('title') aria-invalid="true" @enderror>

                    @error('title')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="label">{{ __('Penerangan (pilihan)') }}</label>

                    <textarea id="description" name="description" rows="3"
                              class="input py-3">{{ old('description', $quiz->description) }}</textarea>

                    @error('description')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Interactive only --}}
                <div x-show="type === 'interactive'" x-cloak>
                    <label for="duration_minutes" class="label">{{ __('Had masa dalam minit (pilihan)') }}</label>

                    <input id="duration_minutes" name="duration_minutes" type="number" min="1" max="180"
                           value="{{ old('duration_minutes', $quiz->duration_minutes) }}"
                           class="input" aria-describedby="duration-help"
                           @error('duration_minutes') aria-invalid="true" @enderror>

                    <p id="duration-help" class="help">
                        {{ __('Biarkan kosong untuk kuiz tanpa had masa. Jika diisi, jawapan murid akan dihantar automatik apabila masa tamat.') }}
                    </p>

                    @error('duration_minutes')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File only --}}
                <div x-show="type === 'file'" x-cloak>
                    <label for="file" class="label">{{ __('Fail kuiz') }}</label>

                    <input id="file" name="file" type="file" accept=".pdf,.doc,.docx"
                           class="input py-2.5 file:mr-3 file:rounded-control file:border-0 file:bg-brand-soft
                                  file:px-3 file:py-1.5 file:font-bold file:text-brand"
                           aria-describedby="quiz-file-help" @error('file') aria-invalid="true" @enderror>

                    <p id="quiz-file-help" class="help">
                        {{ __('PDF, DOC atau DOCX. Had saiz :size MB.', ['size' => config('lms.quiz_file_max_mb')]) }}
                        @if ($editing && $quiz->file_path)
                            {{ __('Biarkan kosong untuk mengekalkan fail sedia ada (:name).', ['name' => $quiz->original_name]) }}
                        @endif
                    </p>

                    @error('file')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="card card-pad">
                <label for="is_published" class="flex items-start gap-3">
                    <input id="is_published" name="is_published" type="checkbox" value="1"
                           @checked(old('is_published', $quiz->is_published ?? true))
                           class="mt-0.5 h-5 w-5 rounded border-line text-brand focus:ring-brand">

                    <span>
                        <span class="block font-bold text-ink">{{ __('Terbitkan kepada murid') }}</span>
                        <span class="block text-sm text-ink-2">{{ __('Buang tanda untuk menyimpan sebagai draf.') }}</span>
                    </span>
                </label>
            </section>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary">
                    <span x-show="type === 'interactive' && ! {{ $editing ? 'true' : 'false' }}" x-cloak>
                        {{ __('Seterusnya: Tambah Soalan') }}
                    </span>
                    <span x-show="type === 'file' || {{ $editing ? 'true' : 'false' }}"
                          @unless ($editing) x-cloak @endunless>
                        {{ $editing ? __('Simpan Perubahan') : __('Simpan Kuiz') }}
                    </span>
                </button>

                @if ($editing && $quiz->isInteractive())
                    <a href="{{ route('cikgu.kuiz.soalan', $quiz) }}" class="btn-secondary">
                        <x-icon name="quiz" class="h-5 w-5" />
                        {{ __('Sunting Soalan') }}
                    </a>
                @endif

                <a href="{{ route('cikgu.kuiz.index') }}" class="btn-secondary">{{ __('Batal') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
