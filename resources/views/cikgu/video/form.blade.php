@php($editing = $lesson->exists)

<x-cikgu-layout :title="$editing ? __('Sunting Video') : __('Video Baru')"
    :heading="$editing ? __('Sunting Video') : __('Video Baru')"
    :sub="__('Rakaman kelas yang anda muat naik atau pautkan dari YouTube')">

    <form method="POST"
          action="{{ $editing ? route('cikgu.video.update', $lesson) : route('cikgu.video.store') }}"
          enctype="multipart/form-data"
          class="tp-formwrap"
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
        @if ($editing) @method('PUT') @endif

        <a href="{{ route('cikgu.video.index') }}" class="tp-back">← {{ __('Video') }}</a>

        {{-- Location --}}
        <div class="tp-panelform">
            <div style="display:flex;flex-direction:column;gap:3px">
                <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Lokasi video') }}</h2>
                <span style="font-size:13px;color:var(--tp-muted)">{{ __('Setiap video mesti dimasukkan dalam satu Bab.') }}</span>
            </div>
            <x-chapter-picker :subjects="$subjects" :grades="$grades" :chapter="$chapter" />
        </div>

        {{-- Details --}}
        <div class="tp-panelform">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Butiran video') }}</h2>
            <div class="tp-field">
                <label for="title" class="tp-label">{{ __('Tajuk') }}</label>
                <input id="title" name="title" type="text" value="{{ old('title', $lesson->title) }}" required class="tp-input" @error('title') aria-invalid="true" @enderror>
                @error('title') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
            <div class="tp-field">
                <label for="description" class="tp-label">{{ __('Penerangan (pilihan)') }}</label>
                <textarea id="description" name="description" rows="4" class="tp-textarea">{{ old('description', $lesson->description) }}</textarea>
                @error('description') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Source --}}
        <div class="tp-panelform">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Sumber video') }}</h2>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" role="tablist" aria-label="{{ __('Sumber video') }}">
                <button type="button" role="tab" id="tab-youtube" :aria-selected="source === 'youtube'" aria-controls="panel-youtube"
                        @click="source = 'youtube'" class="tp-toggle" :class="{ 'is-on': source === 'youtube' }">
                    ▶ {{ __('Pautan YouTube') }}
                </button>
                <button type="button" role="tab" id="tab-upload" :aria-selected="source === 'upload'" aria-controls="panel-upload"
                        @click="source = 'upload'" class="tp-toggle" :class="{ 'is-on': source === 'upload' }">
                    ⬆ {{ __('Muat Naik Video') }}
                </button>
            </div>

            <input type="hidden" name="source" :value="source">

            {{-- YouTube --}}
            <div id="panel-youtube" role="tabpanel" aria-labelledby="tab-youtube" x-show="source === 'youtube'" x-cloak class="tp-field">
                <label for="youtube_url" class="tp-label">{{ __('Pautan YouTube') }}</label>
                <input id="youtube_url" name="youtube_url" type="url"
                       value="{{ old('youtube_url', $lesson->youtube_id ? 'https://www.youtube.com/watch?v='.$lesson->youtube_id : '') }}"
                       placeholder="https://www.youtube.com/watch?v=..." class="tp-input" aria-describedby="youtube-help"
                       @error('youtube_url') aria-invalid="true" @enderror>
                <p id="youtube-help" class="tp-hint">{{ __('Anda boleh guna pautan biasa, youtu.be, /embed/, /shorts/ atau /live/. Video dimainkan terus dalam platform ini.') }}</p>
                @error('youtube_url') <span class="tp-error">{{ $message }}</span> @enderror
            </div>

            {{-- Upload --}}
            <div id="panel-upload" role="tabpanel" aria-labelledby="tab-upload" x-show="source === 'upload'" x-cloak class="tp-field">
                <label for="video" class="tp-label">{{ __('Fail video') }}</label>
                <input id="video" name="video" type="file" accept=".mp4,.webm,video/mp4,video/webm"
                       x-ref="video" @change="checkSize($event)" class="tp-file" aria-describedby="video-help" @error('video') aria-invalid="true" @enderror>
                <p id="video-help" class="tp-hint">
                    {{ __('Format MP4 atau WEBM. Had saiz :max MB.', ['max' => config('lms.video_max_mb')]) }}
                    @if ($editing && $lesson->video_path) {{ __('Biarkan kosong untuk mengekalkan video sedia ada.') }} @endif
                </p>
                <p x-show="sizeError" x-cloak class="tp-error" x-text="sizeError"></p>
                @error('video') <span class="tp-error">{{ $message }}</span> @enderror
                <div style="display:flex;gap:10px;background:#FEF0CE;border:1px solid rgba(138,106,18,.25);border-radius:12px;padding:12px 14px;font-size:13px;color:#8A6A12;margin-top:6px">
                    <span>ℹ️</span>
                    <div>{{ __('Untuk rakaman kelas penuh (video panjang atau besar), kami syorkan muat naik ke YouTube (Unlisted) dan tampal pautan di sini. Muat naik terus sesuai untuk klip pendek sahaja.') }}</div>
                </div>
            </div>

            {{-- Thumbnail --}}
            <div class="tp-field" style="border-top:1px solid var(--tp-line);padding-top:16px">
                <label for="thumbnail" class="tp-label">{{ __('Gambar kecil (pilihan)') }}</label>
                <input id="thumbnail" name="thumbnail" type="file" accept="image/*" class="tp-file" aria-describedby="thumbnail-help">
                <p id="thumbnail-help" class="tp-hint">{{ __('Untuk video YouTube, gambar kecil diambil secara automatik jika anda tidak memuat naik.') }}</p>
                @error('thumbnail') <span class="tp-error">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Publish --}}
        <label for="is_published" class="tp-checkrow">
            <input id="is_published" name="is_published" type="checkbox" value="1" @checked(old('is_published', $lesson->is_published ?? true)) style="width:20px;height:20px;margin-top:2px;accent-color:#17907B">
            <span style="display:flex;flex-direction:column;gap:2px">
                <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ __('Terbitkan kepada murid') }}</span>
                <span style="font-size:12.5px;color:var(--tp-muted)">{{ __('Nyahtanda untuk simpan sebagai draf. Murid tidak dapat melihat draf.') }}</span>
            </span>
        </label>

        {{-- Upload progress --}}
        <div x-show="uploading" x-cloak class="tp-panelform">
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:13.5px;font-weight:700;color:var(--tp-ink)">
                <span>{{ __('Memuat naik video...') }}</span>
                <span x-text="progress + '%'">0%</span>
            </div>
            <div style="height:12px;width:100%;overflow:hidden;border-radius:999px;background:var(--tp-line)" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                <div style="height:100%;border-radius:999px;background:#17907B;transition:width .15s" :style="`width: ${progress}%`"></div>
            </div>
            <p class="tp-hint">{{ __('Jangan tutup halaman ini sehingga selesai.') }}</p>
        </div>

        <div x-show="failed" x-cloak style="display:flex;gap:10px;background:#FDE7E0;border:1px solid rgba(194,73,54,.25);border-radius:14px;padding:14px 18px;font-size:13.5px;color:#C24936">
            <span>⚠️</span>
            <div x-text="failed"></div>
        </div>

        <div style="display:flex;gap:12px">
            <button type="submit" class="tp-btn" style="min-height:48px" :disabled="uploading">
                <span x-show="! uploading">{{ $editing ? __('Simpan Perubahan') : __('Simpan Video') }}</span>
                <span x-show="uploading" x-cloak>{{ __('Menyimpan...') }}</span>
            </button>
            <a href="{{ route('cikgu.video.index') }}" class="tp-btn-outline" style="min-height:48px">{{ __('Batal') }}</a>
        </div>
    </form>

    @push('scripts')
        <script>
            function videoForm({ source, maxMb, hasVideo, fallbackUrl, labels }) {
                return {
                    source, maxMb, hasVideo, fallbackUrl, labels,
                    uploading: false, progress: 0, sizeError: '', failed: '',
                    checkSize(event) {
                        this.sizeError = '';
                        const file = event.target.files[0];
                        if (! file) return;
                        const megabytes = file.size / (1024 * 1024);
                        if (megabytes > this.maxMb) {
                            this.sizeError = this.labels.tooBig.replace(':size', megabytes.toFixed(1)).replace(':max', this.maxMb);
                            event.target.value = '';
                        }
                    },
                    submit(event) {
                        const form = event.target;
                        if (this.sizeError) return;
                        const file = this.$refs.video?.files?.[0];
                        if (this.source !== 'upload' || ! file) { form.submit(); return; }
                        this.uploading = true; this.progress = 0; this.failed = '';
                        const request = new XMLHttpRequest();
                        request.open('POST', form.action);
                        request.setRequestHeader('Accept', 'text/html');
                        request.upload.addEventListener('progress', (progressEvent) => {
                            if (! progressEvent.lengthComputable) return;
                            this.progress = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                        });
                        request.addEventListener('load', () => {
                            if (request.status >= 200 && request.status < 400) {
                                window.location.href = request.responseURL || this.fallbackUrl; return;
                            }
                            this.uploading = false;
                            this.failed = request.status === 413 ? this.labels.serverTooBig : this.labels.uploadFailed.replace(':status', request.status);
                        });
                        request.addEventListener('error', () => { this.uploading = false; this.failed = this.labels.networkFailed; });
                        request.send(new FormData(form));
                    },
                };
            }
        </script>
    @endpush
</x-cikgu-layout>
