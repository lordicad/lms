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
              'maxVideos' => \App\Http\Requests\LessonRequest::MAX_VIDEOS,
              'editing' => $editing,
              'hasVideo' => (bool) $lesson->video_path,
              'fallbackUrl' => route('cikgu.video.index'),
              'labels' => [
                  'tooBig' => __('Saiz video :size MB melebihi had :max MB. Sila muat naik ke YouTube (Unlisted) dan tampal pautannya.'),
                  'serverTooBig' => __('Fail terlalu besar untuk server. Sila guna pautan YouTube.'),
                  'uploadFailed' => __('Muat naik gagal (ralat :status). Sila cuba lagi.'),
                  'networkFailed' => __('Muat naik gagal. Sila semak sambungan internet anda dan cuba lagi.'),
                  'thumbReady' => __('✓ Gambar kecil diambil daripada video anda.'),
                  'thumbFailed' => __('Gambar kecil tidak dapat diambil daripada video ini. Anda boleh muat naik gambar sendiri.'),
                  'notVideo' => __('":name" bukan fail video. Guna MP4 atau WEBM.'),
                  'tooManyFiles' => __('Had :max video sekali muat naik.'),
                  'videoCount' => __(':count video dipilih'),
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

        {{-- Details. Uploading a batch names each video in its own row, so this panel steps aside:
             one title and one description cannot describe five different recordings. --}}
        <div class="tp-panelform" @if (! $editing) x-show="! batch" x-cloak @endif>
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Butiran video') }}</h2>
            <div class="tp-field">
                <label for="title" class="tp-label">{{ __('Tajuk') }}</label>
                <input id="title" name="title" type="text" value="{{ old('title', $lesson->title) }}" class="tp-input" @error('title') aria-invalid="true" @enderror>
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
                <label class="tp-label">{{ $editing ? __('Fail video') : __('Fail video') }}</label>

                @if ($editing)
                    {{-- Editing replaces the one file this video points at, so it stays single. --}}
                    <div class="tp-dropzone" :class="dragging && 'is-dragging'"
                         role="button" tabindex="0" aria-controls="video"
                         @click="$refs.video.click()"
                         @keydown.enter.prevent="$refs.video.click()"
                         @keydown.space.prevent="$refs.video.click()"
                         @dragover.prevent="dragging = true"
                         @dragenter.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="dragging = false; replaceVideo($event.dataTransfer?.files?.[0])">
                        <x-icon name="upload" class="h-7 w-7" style="color:var(--tp-teal)" />
                        <template x-if="! videoName">
                            <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ __('Seret & lepaskan fail di sini') }}</span>
                        </template>
                        <template x-if="videoName">
                            <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink);word-break:break-all" x-text="videoName"></span>
                        </template>
                        <span class="tp-hint" x-text="videoName ? videoSize : @js(__('atau'))"></span>
                        <span class="tp-btn-outline" style="min-height:38px;padding:0 16px;font-size:13px;pointer-events:none">{{ __('Tambah Fail') }}</span>
                    </div>

                    <input id="video" name="video" type="file" accept=".mp4,.webm,video/mp4,video/webm"
                           x-ref="video" @change="onVideoChosen($event)" class="sr-only" aria-describedby="video-help" @error('video') aria-invalid="true" @enderror>
                    <p id="video-help" class="tp-hint">
                        {{ __('Format MP4 atau WEBM. Had saiz :max MB.', ['max' => config('lms.video_max_mb')]) }}
                        @if ($lesson->video_path) {{ __('Biarkan kosong untuk mengekalkan video sedia ada.') }} @endif
                    </p>
                @else
                    {{-- Creating takes any number of recordings at once. Each becomes its own
                         video, titled by its row. --}}
                    <div class="tp-dropzone" :class="dragging && 'is-dragging'"
                         role="button" tabindex="0"
                         @click="$refs.picker.click()"
                         @keydown.enter.prevent="$refs.picker.click()"
                         @keydown.space.prevent="$refs.picker.click()"
                         @dragover.prevent="dragging = true"
                         @dragenter.prevent="dragging = true"
                         @dragleave.prevent="dragging = false"
                         @drop.prevent="dragging = false; take($event.dataTransfer?.files)">
                        <x-icon name="upload" class="h-7 w-7" style="color:var(--tp-teal)" />
                        <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ __('Seret & lepaskan fail di sini') }}</span>
                        <span class="tp-hint">{{ __('atau') }}</span>
                        <span class="tp-btn-outline" style="min-height:38px;padding:0 16px;font-size:13px;pointer-events:none">{{ __('Tambah Fail') }}</span>
                    </div>

                    <input type="file" multiple x-ref="picker" accept=".mp4,.webm,video/mp4,video/webm"
                           @change="take($event.target.files); $event.target.value = ''" class="sr-only">
                    <input type="file" name="videos[]" multiple x-ref="videos" class="sr-only" tabindex="-1" aria-hidden="true">

                    <p id="video-help" class="tp-hint">
                        {{ __('Format MP4 atau WEBM. Had saiz :max MB setiap fail.', ['max' => config('lms.video_max_mb')]) }}
                        {{ __('Setiap fail menjadi satu video berasingan.') }}
                    </p>

                    <div x-show="videos.length" x-cloak style="border:1px solid var(--tp-line-2);border-radius:14px;overflow:hidden;margin-top:4px">
                        <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--tp-line)">
                            <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink)"
                                  x-text="labels.videoCount.replace(':count', videos.length)"></span>
                            <span class="tp-hint" style="margin-left:auto">{{ __('Maks. saiz setiap fail: :max MB', ['max' => config('lms.video_max_mb')]) }}</span>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 88px minmax(180px,1.2fr) 44px;gap:12px;padding:9px 16px;border-bottom:1px solid var(--tp-line)">
                            <span class="tp-g tp-hint" style="font-weight:800">{{ __('Fail') }}</span>
                            <span class="tp-g tp-hint" style="font-weight:800">{{ __('Saiz') }}</span>
                            <span class="tp-g tp-hint" style="font-weight:800">{{ __('Tajuk video') }}</span>
                            <span></span>
                        </div>

                        <template x-for="(row, index) in videos" :key="row.key">
                            <div style="display:grid;grid-template-columns:1fr 88px minmax(180px,1.2fr) 44px;gap:12px;align-items:center;padding:12px 16px;border-bottom:1px solid var(--tp-line)">
                                <span style="display:flex;align-items:center;gap:10px;min-width:0">
                                    <span style="width:34px;height:34px;flex-shrink:0;border-radius:9px;background:#E4EEF9;color:#2E6CA8;display:grid;place-items:center">
                                        <x-icon name="video" class="h-4 w-4" />
                                    </span>
                                    <span style="min-width:0;display:flex;flex-direction:column">
                                        <span class="tp-g" style="font-weight:800;font-size:13.5px;color:var(--tp-ink);word-break:break-all" x-text="row.name"></span>
                                        <span class="tp-hint" x-text="row.thumbReady ? @js(__('Gambar kecil sedia')) : row.ext"></span>
                                    </span>
                                </span>
                                <span class="tp-hint" x-text="row.size"></span>
                                <span style="display:flex;flex-direction:column;gap:3px;min-width:0">
                                    {{-- Paired to the file by position: video_titles[i] names videos[i]. --}}
                                    <input type="text" name="video_titles[]" maxlength="255"
                                           class="tp-input" style="min-height:38px;font-size:13.5px"
                                           :placeholder="row.name"
                                           aria-label="{{ __('Tajuk video') }}"
                                           x-model="row.title">
                                </span>
                                <button type="button" class="tp-icon-action tp-icon-danger" @click="removeVideo(index)" title="{{ __('Buang') }}">
                                    <x-icon name="trash" class="h-4 w-4" />
                                    <span class="sr-only">{{ __('Buang video') }}</span>
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- One hidden input per captured frame, keyed by the row it belongs to, so a
                         capture that fails leaves a gap instead of shifting the rest. --}}
                    <div x-ref="thumbs" class="sr-only" aria-hidden="true"></div>

                    @foreach ($errors->get('videos.*') as $messages)
                        <span class="tp-error">{{ $messages[0] }}</span>
                    @endforeach
                    @error('videos') <span class="tp-error">{{ $message }}</span> @enderror
                @endif

                <p x-show="sizeError" x-cloak class="tp-error" x-text="sizeError"></p>
                @error('video') <span class="tp-error">{{ $message }}</span> @enderror
                <div style="display:flex;gap:10px;background:#FEF0CE;border:1px solid rgba(138,106,18,.25);border-radius:12px;padding:12px 14px;font-size:13px;color:#8A6A12;margin-top:6px">
                    <span>ℹ️</span>
                    <div>{{ __('Untuk rakaman kelas penuh (video panjang atau besar), kami syorkan muat naik ke YouTube (Unlisted) dan tampal pautan di sini. Muat naik terus sesuai untuk klip pendek sahaja.') }}</div>
                </div>
            </div>

            {{-- Thumbnail. A batch captures one frame per video automatically, so a single
                 picker here would only be ambiguous. --}}
            <div class="tp-field" style="border-top:1px solid var(--tp-line);padding-top:16px"
                 @if (! $editing) x-show="! batch" x-cloak @endif>
                <label for="thumbnail" class="tp-label">{{ __('Gambar kecil (pilihan)') }}</label>
                <input id="thumbnail" name="thumbnail" type="file" accept="image/*" class="tp-file"
                       x-ref="thumbnail" @change="onThumbnailPicked()" aria-describedby="thumbnail-help">
                <p id="thumbnail-help" class="tp-hint">{{ __('Dibuat secara automatik daripada video anda. Muat naik gambar sendiri untuk menggantikannya.') }}</p>
                {{-- Live status for the frame we capture from the chosen video file. --}}
                <p x-show="thumbBusy" x-cloak class="tp-hint" aria-live="polite">⏳ {{ __('Sedang mengambil gambar daripada video…') }}</p>
                <p x-show="thumbNote" x-cloak class="tp-hint" style="color:#0F7A68;font-weight:700" aria-live="polite" x-text="thumbNote"></p>
                <p x-show="thumbError" x-cloak class="tp-hint" style="color:#8A6A12" aria-live="polite" x-text="thumbError"></p>
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
                {{-- Object syntax, not a string: Alpine applies a string :style with setAttribute,
                     which would replace the whole style attribute and leave the fill with no height
                     and no colour — a bar that tracks progress perfectly and is invisible doing it. --}}
                <div style="height:100%;border-radius:999px;background:#17907B;transition:width .15s" :style="{ width: progress + '%' }"></div>
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
            function videoForm({ source, maxMb, maxVideos, editing, hasVideo, fallbackUrl, labels }) {
                return {
                    source, maxMb, maxVideos, editing, hasVideo, fallbackUrl, labels,
                    uploading: false, progress: 0, sizeError: '', failed: '',
                    // autoThumb marks the thumbnail input as holding a frame we captured, so a
                    // teacher's own picture is never overwritten but ours can be replaced.
                    autoThumb: false, thumbBusy: false, thumbNote: '', thumbError: '',
                    dragging: false, videoName: '', videoSize: '',
                    // One row per chosen recording: { key, file, name, ext, size, title,
                    // thumbFile, thumbReady }. Row order is submission order, so videos[i] is
                    // named by video_titles[i] on the server.
                    videos: [], nextKey: 0,

                    /** Creating on the upload tab: one lesson per file, each titled by its row. */
                    get batch() {
                        return ! this.editing && this.source === 'upload';
                    },

                    take(fileList) {
                        if (! fileList || typeof DataTransfer === 'undefined') return;

                        this.sizeError = '';

                        for (const file of Array.from(fileList)) {
                            if (this.videos.length >= this.maxVideos) {
                                this.sizeError = this.labels.tooManyFiles.replace(':max', this.maxVideos);
                                break;
                            }
                            if (! this.isVideo(file)) {
                                this.sizeError = this.labels.notVideo.replace(':name', file.name);
                                continue;
                            }

                            const megabytes = file.size / (1024 * 1024);
                            if (megabytes > this.maxMb) {
                                this.sizeError = this.labels.tooBig
                                    .replace(':size', megabytes.toFixed(1))
                                    .replace(':max', this.maxMb);
                                continue;
                            }

                            const row = {
                                key: this.nextKey++,
                                file,
                                name: file.name,
                                ext: this.extensionOf(file.name).toUpperCase(),
                                size: this.megabytes(file.size),
                                title: '',
                                thumbFile: null,
                                thumbReady: false,
                            };

                            this.videos.push(row);
                            // Capture runs per file and takes a moment each; the row is already
                            // on screen, so the teacher is not kept waiting for it.
                            this.captureFor(row);
                        }

                        this.syncVideos();
                    },

                    removeVideo(index) {
                        this.videos.splice(index, 1);
                        this.sizeError = '';
                        this.syncVideos();
                    },

                    isVideo(file) {
                        return /^video\//.test(file.type) || /\.(mp4|webm)$/i.test(file.name);
                    },

                    extensionOf(name) {
                        const dot = name.lastIndexOf('.');
                        return dot < 0 ? '' : name.slice(dot + 1).toLowerCase();
                    },

                    megabytes(bytes) {
                        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
                    },

                    /** Rebuild the hidden inputs so they match the list on screen, in order. */
                    syncVideos() {
                        const transfer = new DataTransfer();
                        this.videos.forEach((row) => transfer.items.add(row.file));
                        this.$refs.videos.files = transfer.files;
                        this.syncThumbs();
                    },

                    /**
                     * One input per captured frame, named for the row it belongs to.
                     *
                     * Keying by index rather than pushing into a single list matters: a video the
                     * browser cannot decode simply leaves its key absent, instead of shifting
                     * every later thumbnail onto the wrong video.
                     */
                    syncThumbs() {
                        const holder = this.$refs.thumbs;
                        if (! holder) return;

                        holder.innerHTML = '';

                        this.videos.forEach((row, index) => {
                            if (! row.thumbFile) return;

                            const input = document.createElement('input');
                            input.type = 'file';
                            input.name = `thumbnails[${index}]`;

                            const transfer = new DataTransfer();
                            transfer.items.add(row.thumbFile);
                            input.files = transfer.files;

                            holder.appendChild(input);
                        });
                    },

                    async captureFor(row) {
                        row.thumbFile = await this.frameFrom(row.file);
                        row.thumbReady = row.thumbFile !== null;
                        this.syncThumbs();
                    },

                    /** The single-file path, used when editing one video. */
                    replaceVideo(file) {
                        if (! file || typeof DataTransfer === 'undefined') return;
                        if (! this.isVideo(file)) {
                            this.sizeError = this.labels.notVideo.replace(':name', file.name);
                            return;
                        }

                        const transfer = new DataTransfer();
                        transfer.items.add(file);
                        this.$refs.video.files = transfer.files;
                        // Assigning .files does not fire change, so the size check and the
                        // capture would silently never run.
                        this.$refs.video.dispatchEvent(new Event('change', { bubbles: true }));
                    },

                    onVideoChosen(event) {
                        this.checkSize(event);

                        // checkSize clears the input when the file is too large, so read it back
                        // rather than trusting what was picked.
                        const file = event.target.files?.[0] ?? null;
                        this.videoName = file ? file.name : '';
                        this.videoSize = file ? this.megabytes(file.size) : '';
                    },

                    checkSize(event) {
                        this.sizeError = '';
                        const file = event.target.files[0];
                        if (! file) return;
                        const megabytes = file.size / (1024 * 1024);
                        if (megabytes > this.maxMb) {
                            this.sizeError = this.labels.tooBig.replace(':size', megabytes.toFixed(1)).replace(':max', this.maxMb);
                            event.target.value = '';
                            return;
                        }
                        this.captureThumbnail(file);
                    },

                    /**
                     * Grab a still from the chosen video and hand it to the thumbnail input.
                     *
                     * Done in the browser because the server has no ffmpeg — and it never needs
                     * one: the file is already here, and the captured frame rides along in the
                     * same multipart form as an ordinary image upload.
                     */
                    async captureThumbnail(file) {
                        const input = this.$refs.thumbnail;
                        const chosenByTeacher = input && input.files.length && ! this.autoThumb;
                        if (! input || chosenByTeacher) return;

                        this.thumbBusy = true; this.thumbNote = ''; this.thumbError = '';

                        const frame = await this.frameFrom(file);

                        if (frame) {
                            const transfer = new DataTransfer();
                            transfer.items.add(frame);
                            input.files = transfer.files;
                            this.autoThumb = true;
                            this.thumbNote = this.labels.thumbReady;
                        } else {
                            this.thumbError = this.labels.thumbFailed;
                        }

                        this.thumbBusy = false;
                    },

                    /**
                     * Grab a still from a video file and return it as an image, or null.
                     *
                     * Done in the browser because the server has no ffmpeg — and never needs one:
                     * the file is already here, and the frame rides along with the upload. A codec
                     * the browser cannot decode, or a seek that never lands, returns null and the
                     * video falls back to the subject artwork.
                     */
                    async frameFrom(file) {
                        if (typeof DataTransfer === 'undefined' || ! HTMLCanvasElement.prototype.toBlob) return null;

                        const url = URL.createObjectURL(file);
                        const video = document.createElement('video');
                        video.preload = 'auto'; video.muted = true; video.playsInline = true; video.src = url;

                        try {
                            await this.videoEvent(video, 'loadedmetadata');
                            // A little way in, never frame zero: openings are often black or a title card.
                            const duration = isFinite(video.duration) ? video.duration : 0;
                            video.currentTime = Math.min(Math.max(duration * 0.1, 1), Math.max(duration - 0.1, 0));
                            await this.videoEvent(video, 'seeked');

                            const scale = Math.min(1, 1280 / (video.videoWidth || 1280));
                            const canvas = document.createElement('canvas');
                            canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
                            canvas.height = Math.max(1, Math.round(video.videoHeight * scale));
                            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.82));
                            if (! blob) return null;

                            return new File([blob], 'auto-thumbnail.jpg', { type: 'image/jpeg' });
                        } catch (error) {
                            return null;
                        } finally {
                            URL.revokeObjectURL(url);
                        }
                    },

                    /** Resolve on the given media event, rejecting on error or if it never arrives. */
                    videoEvent(video, name) {
                        return new Promise((resolve, reject) => {
                            const timer = setTimeout(() => reject(new Error('timed out')), 15000);
                            const done = (fn) => (value) => { clearTimeout(timer); fn(value); };
                            video.addEventListener(name, done(resolve), { once: true });
                            video.addEventListener('error', done(reject), { once: true });
                        });
                    },

                    /** The teacher picked their own image, so stop treating it as ours. */
                    onThumbnailPicked() {
                        this.autoThumb = false; this.thumbNote = ''; this.thumbError = '';
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
