@php($editing = $lesson->exists)

<x-app-layout :title="$editing ? __('Sunting Video') : __('Video Baharu')">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('cikgu.video.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Video Saya') }}
        </a>

        <h1 class="mt-4 text-3xl font-extrabold text-ink">
            {{ $editing ? __('Sunting Video') : __('Video Baharu') }}
        </h1>

        {{--
            The form posts through XHR when a file is attached, purely so the teacher sees a real
            progress bar on a 100 MB upload instead of a frozen tab. Without a file it submits
            normally. Either way the same controller and the same validation run.
        --}}
        <form method="POST"
              action="{{ $editing ? route('cikgu.video.update', $lesson) : route('cikgu.video.store') }}"
              enctype="multipart/form-data"
              class="mt-6 space-y-6"
              x-data="videoForm({{ Js::from([
                  'source' => old('source', $lesson->source ?? 'youtube'),
                  'maxMb' => config('lms.video_max_mb'),
                  'hasVideo' => (bool) $lesson->video_path,
                  'fallbackUrl' => route('cikgu.video.index'),
                  'labels' => [
                      'tooBig' => __('Saiz video :size MB melebihi had :max MB. Sila muat naik ke YouTube (Unlisted) dan tampal pautannya.'),
                      'serverTooBig' => __('Fail terlalu besar untuk server. Sila guna pautan YouTube.'),
                      'uploadFailed' => __('Muat naik gagal (ralat :status). Sila cuba lagi.'),
                      'networkFailed' => __('Muat naik gagal. Sila semak sambungan internet anda dan cuba lagi.'),
                  ],
              ]) }})"
              @submit.prevent="submit($event)">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <section class="card card-pad">
                <h2 class="text-xl font-extrabold text-ink">{{ __('Lokasi video') }}</h2>
                <p class="help mb-4">{{ __('Setiap video mesti berada di dalam satu Bab.') }}</p>

                <x-chapter-picker :subjects="$subjects" :grades="$grades" :chapter="$chapter" />
            </section>

            <section class="card card-pad space-y-5">
                <h2 class="text-xl font-extrabold text-ink">{{ __('Maklumat video') }}</h2>

                <div>
                    <label for="title" class="label">{{ __('Tajuk') }}</label>

                    <input id="title" name="title" type="text" value="{{ old('title', $lesson->title) }}"
                           required class="input" @error('title') aria-invalid="true" @enderror>

                    @error('title')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="description" class="label">{{ __('Penerangan (pilihan)') }}</label>

                    <textarea id="description" name="description" rows="4" class="input py-3"
                              >{{ old('description', $lesson->description) }}</textarea>

                    @error('description')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="card card-pad">
                <h2 class="text-xl font-extrabold text-ink">{{ __('Sumber video') }}</h2>

                {{-- Two tabs: upload a file, or paste a YouTube link. --}}
                <div class="mt-4 flex gap-2" role="tablist" aria-label="{{ __('Sumber video') }}">
                    <button type="button" role="tab" id="tab-youtube"
                            :aria-selected="source === 'youtube'" aria-controls="panel-youtube"
                            @click="source = 'youtube'"
                            class="btn flex-1"
                            :class="source === 'youtube'
                                ? 'bg-brand text-on-brand'
                                : 'border-2 border-line bg-surface text-ink hover:border-brand'">
                        <x-icon name="youtube" class="h-5 w-5" />
                        {{ __('Pautan YouTube') }}
                    </button>

                    <button type="button" role="tab" id="tab-upload"
                            :aria-selected="source === 'upload'" aria-controls="panel-upload"
                            @click="source = 'upload'"
                            class="btn flex-1"
                            :class="source === 'upload'
                                ? 'bg-brand text-on-brand'
                                : 'border-2 border-line bg-surface text-ink hover:border-brand'">
                        <x-icon name="upload" class="h-5 w-5" />
                        {{ __('Muat Naik Video') }}
                    </button>
                </div>

                <input type="hidden" name="source" :value="source">

                {{-- YouTube --}}
                <div id="panel-youtube" role="tabpanel" aria-labelledby="tab-youtube"
                     x-show="source === 'youtube'" x-cloak class="mt-5">
                    <label for="youtube_url" class="label">{{ __('Pautan YouTube') }}</label>

                    <input id="youtube_url" name="youtube_url" type="url"
                           value="{{ old('youtube_url', $lesson->youtube_id ? 'https://www.youtube.com/watch?v='.$lesson->youtube_id : '') }}"
                           placeholder="https://www.youtube.com/watch?v=..."
                           class="input" aria-describedby="youtube-help"
                           @error('youtube_url') aria-invalid="true" @enderror>

                    <p id="youtube-help" class="help">
                        {{ __('Boleh guna pautan biasa, youtu.be, /embed/, /shorts/ atau /live/. Video akan dimainkan terus di dalam platform ini.') }}
                    </p>

                    @error('youtube_url')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Upload --}}
                <div id="panel-upload" role="tabpanel" aria-labelledby="tab-upload"
                     x-show="source === 'upload'" x-cloak class="mt-5">
                    <label for="video" class="label">{{ __('Fail video') }}</label>

                    <input id="video" name="video" type="file" accept=".mp4,.webm,video/mp4,video/webm"
                           x-ref="video" @change="checkSize($event)"
                           class="input py-2.5 file:mr-3 file:rounded-control file:border-0 file:bg-brand-soft
                                  file:px-3 file:py-1.5 file:font-bold file:text-brand"
                           aria-describedby="video-help" @error('video') aria-invalid="true" @enderror>

                    <p id="video-help" class="help">
                        {{ __('Format MP4 atau WEBM. Had saiz :max MB.', ['max' => config('lms.video_max_mb')]) }}
                        @if ($editing && $lesson->video_path)
                            {{ __('Biarkan kosong untuk mengekalkan video sedia ada.') }}
                        @endif
                    </p>

                    <p x-show="sizeError" x-cloak class="field-error" x-text="sizeError"></p>

                    @error('video')
                        <p class="field-error">{{ $message }}</p>
                    @enderror

                    {{-- The reason the YouTube tab is the default. --}}
                    <div class="alert-warn mt-4">
                        <x-icon name="info" class="mt-0.5 h-5 w-5 shrink-0" />
                        <div>
                            {{ __('Untuk rakaman kelas penuh (video panjang atau besar), kami syorkan muat naik ke YouTube (Unlisted) dan tampal pautan di sini. Muat naik terus sesuai untuk klip pendek sahaja.') }}
                        </div>
                    </div>
                </div>

                {{-- Thumbnail --}}
                <div class="mt-6 border-t border-line pt-5">
                    <label for="thumbnail" class="label">{{ __('Gambar kecil (pilihan)') }}</label>

                    <input id="thumbnail" name="thumbnail" type="file" accept="image/*"
                           class="input py-2.5 file:mr-3 file:rounded-control file:border-0 file:bg-surface-2
                                  file:px-3 file:py-1.5 file:font-bold file:text-ink-2"
                           aria-describedby="thumbnail-help">

                    <p id="thumbnail-help" class="help">
                        {{ __('Untuk video YouTube, gambar kecil diambil automatik jika anda tidak memuat naik.') }}
                    </p>

                    @error('thumbnail')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </section>

            <section class="card card-pad">
                <label for="is_published" class="flex items-start gap-3">
                    <input id="is_published" name="is_published" type="checkbox" value="1"
                           @checked(old('is_published', $lesson->is_published ?? true))
                           class="mt-0.5 h-5 w-5 rounded border-line text-brand focus:ring-brand">

                    <span>
                        <span class="block font-bold text-ink">{{ __('Terbitkan kepada murid') }}</span>
                        <span class="block text-sm text-ink-2">
                            {{ __('Buang tanda untuk menyimpan sebagai draf. Murid tidak akan nampak draf.') }}
                        </span>
                    </span>
                </label>
            </section>

            {{-- Upload progress --}}
            <div x-show="uploading" x-cloak class="card card-pad">
                <div class="flex items-center justify-between text-sm font-bold text-ink">
                    <span>{{ __('Memuat naik video...') }}</span>
                    <span x-text="progress + '%'">0%</span>
                </div>

                <div class="mt-2 h-3 w-full overflow-hidden rounded-full bg-surface-2"
                     role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-brand transition-[width] duration-150"
                         :style="`width: ${progress}%`"></div>
                </div>

                <p class="help">{{ __('Jangan tutup halaman ini sehingga selesai.') }}</p>
            </div>

            <div x-show="failed" x-cloak class="alert-danger">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 shrink-0" />
                <div x-text="failed"></div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="btn-primary" :disabled="uploading">
                    <span x-show="! uploading">{{ $editing ? __('Simpan Perubahan') : __('Simpan Video') }}</span>
                    <span x-show="uploading" x-cloak>{{ __('Menyimpan...') }}</span>
                </button>

                <a href="{{ route('cikgu.video.index') }}" class="btn-secondary">{{ __('Batal') }}</a>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            function videoForm({ source, maxMb, hasVideo, fallbackUrl, labels }) {
                return {
                    source,
                    maxMb,
                    hasVideo,
                    fallbackUrl,
                    labels,
                    uploading: false,
                    progress: 0,
                    sizeError: '',
                    failed: '',

                    /* Catch an oversized file before spending minutes uploading it. */
                    checkSize(event) {
                        this.sizeError = '';

                        const file = event.target.files[0];
                        if (! file) return;

                        const megabytes = file.size / (1024 * 1024);

                        if (megabytes > this.maxMb) {
                            this.sizeError = this.labels.tooBig
                                .replace(':size', megabytes.toFixed(1))
                                .replace(':max', this.maxMb);
                            event.target.value = '';
                        }
                    },

                    submit(event) {
                        const form = event.target;

                        if (this.sizeError) return;

                        const file = this.$refs.video?.files?.[0];

                        // No file to stream: let the browser post it the ordinary way.
                        if (this.source !== 'upload' || ! file) {
                            form.submit();
                            return;
                        }

                        this.uploading = true;
                        this.progress = 0;
                        this.failed = '';

                        const request = new XMLHttpRequest();
                        request.open('POST', form.action);
                        request.setRequestHeader('Accept', 'text/html');

                        request.upload.addEventListener('progress', (progressEvent) => {
                            if (! progressEvent.lengthComputable) return;

                            this.progress = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                        });

                        request.addEventListener('load', () => {
                            /*
                             * Laravel answers with a redirect either way: to the list on success, or
                             * back to this form with the errors flashed to the session when validation
                             * fails. XHR follows the redirect itself, so we simply go where it landed.
                             */
                            if (request.status >= 200 && request.status < 400) {
                                window.location.href = request.responseURL || this.fallbackUrl;
                                return;
                            }

                            this.uploading = false;

                            this.failed = request.status === 413
                                ? this.labels.serverTooBig
                                : this.labels.uploadFailed.replace(':status', request.status);
                        });

                        request.addEventListener('error', () => {
                            this.uploading = false;
                            this.failed = this.labels.networkFailed;
                        });

                        request.send(new FormData(form));
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
