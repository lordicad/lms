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
              'materialMaxMb' => config('lms.material_max_mb'),
              'editing' => $editing,
              'hasVideo' => (bool) $lesson->video_path,
              'fallbackUrl' => route('cikgu.video.index'),
              'materialUrl' => route('cikgu.bahan.store'),
              'labels' => [
                  'tooBig' => __('Saiz video :size MB melebihi had :max MB. Sila muat naik ke YouTube (Unlisted) dan tampal pautannya.'),
                  'attachTooBig' => __('Saiz fail :size MB melebihi had :max MB untuk lampiran.'),
                  'serverTooBig' => __('Fail terlalu besar untuk server. Sila guna pautan YouTube.'),
                  'uploadFailed' => __('Muat naik gagal (ralat :status). Sila cuba lagi.'),
                  'networkFailed' => __('Muat naik gagal. Sila semak sambungan internet anda dan cuba lagi.'),
                  'thumbReady' => __('✓ Gambar kecil diambil daripada video anda.'),
                  'thumbFailed' => __('Gambar kecil tidak dapat diambil daripada video ini. Anda boleh muat naik gambar sendiri.'),
                  'notSupported' => __('Fail ":name" tidak disokong. Video (MP4/WEBM) atau lampiran (PDF, PowerPoint, Word, Excel, imej) sahaja.'),
                  'needChapter' => __('Sila pilih Subjek, Tahun dan Bab dahulu.'),
                  'needFile' => __('Sila pilih sekurang-kurangnya satu fail.'),
                  'video' => __('Video'),
                  'attachment' => __('Lampiran'),
                  'waiting' => __('Menunggu...'),
                  'done' => __('Selesai'),
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
                <input id="title" name="title" type="text" value="{{ old('title', $lesson->title) }}"
                       :required="titleRequired" class="tp-input" @error('title') aria-invalid="true" @enderror>
                {{-- With several files, each item is named after its fail name; a typed title only applies to a single video. --}}
                <p class="tp-hint" x-show="source === 'upload' && ! editing && videoCount() > 1" x-cloak>{{ __('Lebih daripada satu video dipilih: setiap satu akan dinamakan mengikut nama failnya.') }}</p>
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
                    <x-icon name="youtube" class="h-4 w-4" /> {{ __('Pautan YouTube') }}
                </button>
                <button type="button" role="tab" id="tab-upload" :aria-selected="source === 'upload'" aria-controls="panel-upload"
                        @click="source = 'upload'" class="tp-toggle" :class="{ 'is-on': source === 'upload' }">
                    <x-icon name="upload" class="h-4 w-4" /> {{ __('Muat Naik Fail') }}
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
                <label for="video" class="tp-label">{{ $editing ? __('Fail video') : __('Fail video dan lampiran') }}</label>

                {{-- Drop target that also opens the picker, so files can be dragged in or chosen.
                     The real input stays in the page (screen-reader reachable); on create it feeds
                     the queue below, and the queue is what actually gets uploaded. --}}
                <div class="tp-dropzone" :class="dragging && 'is-dragging'"
                     role="button" tabindex="0" aria-controls="video"
                     @click="$refs.video.click()"
                     @keydown.enter.prevent="$refs.video.click()"
                     @keydown.space.prevent="$refs.video.click()"
                     @dragover.prevent="dragging = true"
                     @dragenter.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="onDrop($event)">
                    <span style="color:var(--tp-teal)" aria-hidden="true"><x-icon name="upload" class="h-8 w-8" /></span>

                    <template x-if="! files.length">
                        <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)">{{ $editing ? __('Seret fail video ke sini') : __('Seret fail ke sini') }}</span>
                    </template>
                    <template x-if="files.length">
                        <span class="tp-g" style="font-weight:800;font-size:14.5px;color:var(--tp-ink)" x-text="summary()"></span>
                    </template>

                    <span class="tp-hint">{{ __('atau') }}</span>

                    <span class="tp-btn-outline" style="min-height:38px;padding:0 16px;font-size:13px;pointer-events:none">
                        <span x-text="files.length ? @js(__('Tambah Fail')) : @js(__('Pilih Fail'))"></span>
                    </span>
                </div>

                <input id="video" type="file"
                       accept="{{ $editing ? '.mp4,.webm,video/mp4,video/webm' : '.mp4,.webm,video/mp4,video/webm,.pdf,.ppt,.pptx,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg' }}"
                       @unless($editing) multiple @endunless
                       x-ref="video" @change="onPicked($event)" class="sr-only" aria-describedby="video-help">
                <p id="video-help" class="tp-hint">
                    @if ($editing)
                        {{ __('Format MP4 atau WEBM. Had saiz :max MB.', ['max' => config('lms.video_max_mb')]) }}
                        @if ($lesson->video_path) {{ __('Biarkan kosong untuk mengekalkan video sedia ada.') }} @endif
                    @else
                        {{ __('Video (MP4/WEBM, had :max MB) dan lampiran (PDF, PowerPoint, Word, Excel, imej — had :attach MB) diasingkan secara automatik: video disimpan sebagai Video, fail lain sebagai Bahan dalam Bab yang sama.', ['max' => config('lms.video_max_mb'), 'attach' => config('lms.material_max_mb')]) }}
                    @endif
                </p>
                <p x-show="sizeError" x-cloak class="tp-error" x-text="sizeError"></p>
                @error('video') <span class="tp-error">{{ $message }}</span> @enderror

                {{-- The queue: each picked file, its detected kind, and a remove control. --}}
                <div x-show="files.length" x-cloak style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
                    <template x-for="(item, index) in files" :key="item.key">
                        <div style="display:flex;align-items:center;gap:12px;border:1px solid var(--tp-line);border-radius:12px;padding:10px 14px;background:var(--tp-surface)">
                            <span aria-hidden="true" style="flex-shrink:0;display:grid;place-items:center;width:34px;height:34px;border-radius:10px"
                                  :style="{ background: item.kind === 'video' ? '#DCF2EE' : '#FBE4ED', color: item.kind === 'video' ? '#0F7A68' : '#B84A75' }">
                                <template x-if="item.kind === 'video'"><x-icon name="video" class="h-4 w-4" /></template>
                                <template x-if="item.kind !== 'video'"><x-icon name="file" class="h-4 w-4" /></template>
                            </span>
                            <div style="display:flex;flex-direction:column;gap:2px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:13.5px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap" x-text="item.name"></span>
                                <span class="tp-hint" style="margin:0">
                                    <span x-text="item.kind === 'video' ? labels.video : labels.attachment"></span> · <span x-text="item.sizeLabel"></span>
                                </span>
                            </div>
                            <button type="button" @click="remove(index)" class="tp-icon-action tp-icon-danger" :title="@js(__('Buang'))"><x-icon name="x" class="h-4 w-4" /><span class="sr-only">{{ __('Buang fail') }}</span></button>
                        </div>
                    </template>
                </div>

                <div style="display:flex;gap:10px;background:#FEF0CE;border:1px solid rgba(138,106,18,.25);border-radius:12px;padding:12px 14px;font-size:13px;color:#8A6A12;margin-top:6px">
                    <span style="flex-shrink:0" aria-hidden="true"><x-icon name="alert" class="h-4 w-4" /></span>
                    <div>{{ __('Untuk rakaman kelas penuh (video panjang atau besar), kami syorkan muat naik ke YouTube (Unlisted) dan tampal pautan di sini. Muat naik terus sesuai untuk klip pendek sahaja.') }}</div>
                </div>
            </div>

            {{-- Thumbnail: applies to the single-video case. With several videos, a frame is
                 captured from each automatically at upload time. --}}
            <div class="tp-field" style="border-top:1px solid var(--tp-line);padding-top:16px" x-show="editing || videoCount() <= 1">
                <label for="thumbnail" class="tp-label">{{ __('Gambar kecil (pilihan)') }}</label>
                <input id="thumbnail" name="thumbnail" type="file" accept="image/*" class="tp-file"
                       x-ref="thumbnail" @change="onThumbnailPicked()" aria-describedby="thumbnail-help">
                <p id="thumbnail-help" class="tp-hint">{{ __('Dibuat secara automatik daripada video anda. Muat naik gambar sendiri untuk menggantikannya.') }}</p>
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

        <div x-show="failed" x-cloak style="display:flex;gap:10px;background:#FDE7E0;border:1px solid rgba(194,73,54,.25);border-radius:14px;padding:14px 18px;font-size:13.5px;color:#C24936">
            <span>⚠️</span>
            <div x-text="failed"></div>
        </div>

        <div style="display:flex;gap:12px">
            <button type="submit" class="tp-btn" style="min-height:48px" :disabled="uploading">
                <span x-show="! uploading">{{ $editing ? __('Simpan Perubahan') : __('Simpan') }}</span>
                <span x-show="uploading" x-cloak>{{ __('Menyimpan...') }}</span>
            </button>
            <a href="{{ route('cikgu.video.index') }}" class="tp-btn-outline" style="min-height:48px">{{ __('Batal') }}</a>
        </div>

        {{-- Upload progress dialog. x-if, not x-show: an x-show overlay has reproducibly failed
             to re-hide in this codebase (see HANDOVER), and x-if genuinely removes it. --}}
        <template x-if="uploading || dialogDone">
            <div style="position:fixed;inset:0;z-index:90;display:grid;place-items:center;padding:20px">
                <div style="position:absolute;inset:0;background:rgba(46,44,80,.45)"></div>
                <div role="dialog" aria-modal="true" aria-labelledby="upload-dialog-title"
                     style="position:relative;background:var(--tp-surface);border-radius:20px;max-width:460px;width:100%;padding:26px;display:flex;flex-direction:column;gap:14px;box-shadow:0 24px 60px rgba(46,44,80,.3)">
                    <h2 id="upload-dialog-title" class="tp-g" style="margin:0;font-size:17px;font-weight:800;color:var(--tp-ink)"
                        x-text="uploading ? @js(__('Memuat naik...')) : @js(__('Muat naik selesai'))"></h2>

                    <div style="display:flex;flex-direction:column;gap:10px;max-height:320px;overflow-y:auto">
                        <template x-for="item in queue" :key="item.key">
                            <div style="display:flex;flex-direction:column;gap:6px">
                                <div style="display:flex;align-items:center;gap:10px;font-size:13px">
                                    <span aria-hidden="true" style="flex-shrink:0;display:grid;place-items:center;color:var(--tp-muted)">
                                        <template x-if="item.kind === 'video'"><x-icon name="video" class="h-4 w-4" /></template>
                                        <template x-if="item.kind !== 'video'"><x-icon name="file" class="h-4 w-4" /></template>
                                    </span>
                                    <span class="tp-g" style="font-weight:800;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1" x-text="item.name"></span>
                                    <span style="flex-shrink:0;display:inline-flex;align-items:center;gap:4px;font-weight:700"
                                          :style="{ color: item.status === 'failed' ? '#C24936' : item.status === 'done' ? '#0F7A68' : 'var(--tp-muted)' }">
                                        <template x-if="item.status === 'done'"><x-icon name="check" class="h-4 w-4" /></template>
                                        <template x-if="item.status === 'failed'"><x-icon name="x" class="h-4 w-4" /></template>
                                        <span x-text="item.status === 'done' ? labels.done : item.status === 'failed' ? '' : item.status === 'uploading' ? item.progress + '%' : labels.waiting"></span>
                                    </span>
                                </div>
                                <div style="height:8px;width:100%;overflow:hidden;border-radius:999px;background:var(--tp-line)" role="progressbar" :aria-valuenow="item.progress" aria-valuemin="0" aria-valuemax="100">
                                    <div style="height:100%;border-radius:999px;transition:width .15s"
                                         :style="{ width: item.progress + '%', background: item.status === 'failed' ? '#C24936' : '#17907B' }"></div>
                                </div>
                                <p x-show="item.error" x-cloak class="tp-error" style="margin:0" x-text="item.error"></p>
                            </div>
                        </template>
                    </div>

                    <p class="tp-hint" style="margin:0" x-show="uploading">{{ __('Jangan tutup halaman ini sehingga selesai.') }}</p>

                    <div x-show="dialogDone" x-cloak style="display:flex;gap:10px">
                        <button type="button" class="tp-btn" style="min-height:44px" @click="finish()"
                                x-text="failedCount() ? @js(__('Tutup')) : @js(__('Teruskan'))"></button>
                    </div>
                </div>
            </div>
        </template>
    </form>

    @push('scripts')
        <script>
            function videoForm({ source, maxMb, materialMaxMb, editing, hasVideo, fallbackUrl, materialUrl, labels }) {
                return {
                    source, maxMb, materialMaxMb, editing, hasVideo, fallbackUrl, materialUrl, labels,
                    uploading: false, dialogDone: false, sizeError: '', failed: '',
                    // autoThumb marks the thumbnail input as holding a frame we captured, so a
                    // teacher's own picture is never overwritten but ours can be replaced.
                    autoThumb: false, thumbBusy: false, thumbNote: '', thumbError: '',
                    dragging: false,
                    files: [],   // picked files: {key, file, kind, name, sizeLabel, status, progress, error}
                    queue: [],   // the slice of files shown in the progress dialog for this run

                    get titleRequired() {
                        if (this.source === 'youtube' || this.editing) return true;
                        return false; // filenames name each item when several are picked
                    },

                    videoCount() { return this.files.filter((f) => f.kind === 'video').length; },
                    failedCount() { return this.queue.filter((f) => f.status === 'failed').length; },

                    summary() {
                        const videos = this.videoCount();
                        const attachments = this.files.length - videos;
                        const parts = [];
                        if (videos) parts.push(videos + ' ' + this.labels.video.toLowerCase());
                        if (attachments) parts.push(attachments + ' ' + this.labels.attachment.toLowerCase());
                        return parts.join(', ');
                    },

                    classify(file) {
                        if (/^video\//.test(file.type) || /\.(mp4|webm)$/i.test(file.name)) return 'video';
                        if (/\.(pdf|pptx?|docx?|xlsx?|png|jpe?g)$/i.test(file.name)) return 'attachment';
                        return null;
                    },

                    add(list) {
                        this.sizeError = '';
                        for (const file of list) {
                            const kind = this.classify(file);
                            if (! kind) {
                                this.sizeError = this.labels.notSupported.replace(':name', file.name);
                                continue;
                            }
                            const megabytes = file.size / (1024 * 1024);
                            const limit = kind === 'video' ? this.maxMb : this.materialMaxMb;
                            if (megabytes > limit) {
                                this.sizeError = (kind === 'video' ? this.labels.tooBig : this.labels.attachTooBig)
                                    .replace(':size', megabytes.toFixed(1)).replace(':max', limit);
                                continue;
                            }
                            this.files.push({
                                key: file.name + ':' + file.size + ':' + this.files.length,
                                file, kind,
                                name: file.name,
                                sizeLabel: megabytes.toFixed(1) + ' MB',
                                status: 'pending', progress: 0, error: '',
                            });
                        }

                        // Single video picked: capture its thumbnail now so the teacher sees the note.
                        if (this.videoCount() === 1 && ! this.editing) {
                            this.captureThumbnail(this.files.find((f) => f.kind === 'video').file);
                        }
                    },

                    onDrop(event) {
                        this.dragging = false;
                        const dropped = Array.from(event.dataTransfer?.files ?? []);
                        if (! dropped.length) return;

                        if (this.editing) {
                            // Edit replaces the one video, exactly as before.
                            const file = dropped[0];
                            if (this.classify(file) !== 'video') { this.sizeError = this.labels.notSupported.replace(':name', file.name); return; }
                            if (typeof DataTransfer === 'undefined') return;
                            const transfer = new DataTransfer();
                            transfer.items.add(file);
                            this.$refs.video.files = transfer.files;
                            this.$refs.video.dispatchEvent(new Event('change', { bubbles: true }));
                            return;
                        }

                        this.add(dropped);
                    },

                    onPicked(event) {
                        const picked = Array.from(event.target.files ?? []);
                        if (! picked.length) return;

                        if (this.editing) {
                            this.files = [];
                            this.sizeError = '';
                            const file = picked[0];
                            const megabytes = file.size / (1024 * 1024);
                            if (megabytes > this.maxMb) {
                                this.sizeError = this.labels.tooBig.replace(':size', megabytes.toFixed(1)).replace(':max', this.maxMb);
                                event.target.value = '';
                                return;
                            }
                            this.files.push({ key: file.name, file, kind: 'video', name: file.name, sizeLabel: megabytes.toFixed(1) + ' MB', status: 'pending', progress: 0, error: '' });
                            this.captureThumbnail(file);
                            return;
                        }

                        this.add(picked);
                        // The input is only a picker on create; the queue holds the files. Clearing it
                        // lets the same file be picked again after a remove.
                        event.target.value = '';
                    },

                    remove(index) {
                        this.files.splice(index, 1);
                        if (this.editing) this.$refs.video.value = '';
                    },

                    /**
                     * Grab a still from the chosen video and hand it to the thumbnail input.
                     * Done in the browser because the server has no ffmpeg.
                     */
                    async captureThumbnail(file) {
                        const input = this.$refs.thumbnail;
                        const chosenByTeacher = input && input.files.length && ! this.autoThumb;
                        if (! input || chosenByTeacher) return;
                        if (typeof DataTransfer === 'undefined' || ! HTMLCanvasElement.prototype.toBlob) return;

                        this.thumbBusy = true; this.thumbNote = ''; this.thumbError = '';
                        try {
                            const blob = await this.captureFrame(file);
                            const transfer = new DataTransfer();
                            transfer.items.add(new File([blob], 'auto-thumbnail.jpg', { type: 'image/jpeg' }));
                            input.files = transfer.files;
                            this.autoThumb = true;
                            this.thumbNote = this.labels.thumbReady;
                        } catch (error) {
                            this.thumbError = this.labels.thumbFailed;
                        } finally {
                            this.thumbBusy = false;
                        }
                    },

                    /** Decode a frame a little way in (openings are often black or a title card). */
                    async captureFrame(file) {
                        const url = URL.createObjectURL(file);
                        const video = document.createElement('video');
                        video.preload = 'auto'; video.muted = true; video.playsInline = true; video.src = url;

                        try {
                            await this.videoEvent(video, 'loadedmetadata');
                            const duration = isFinite(video.duration) ? video.duration : 0;
                            video.currentTime = Math.min(Math.max(duration * 0.1, 1), Math.max(duration - 0.1, 0));
                            await this.videoEvent(video, 'seeked');

                            const scale = Math.min(1, 1280 / (video.videoWidth || 1280));
                            const canvas = document.createElement('canvas');
                            canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
                            canvas.height = Math.max(1, Math.round(video.videoHeight * scale));
                            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.82));
                            if (! blob) throw new Error('frame could not be encoded');
                            return blob;
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

                    /** Strip the extension so "bab-3-pecahan.mp4" titles as "bab-3-pecahan". */
                    titleFrom(name) {
                        return name.replace(/\.[^.]+$/, '').slice(0, 255) || name;
                    },

                    submit(event) {
                        const form = event.target;
                        this.failed = '';

                        // YouTube keeps the plain form submit: no file, nothing to show progress for.
                        if (this.source !== 'upload') { form.submit(); return; }

                        if (this.editing) { this.submitEdit(form); return; }

                        if (! form.querySelector('[name=chapter_id]')?.value) { this.failed = this.labels.needChapter; return; }
                        if (! this.files.length) { this.failed = this.labels.needFile; return; }

                        this.uploadQueue(form);
                    },

                    /** Edit mode: the original single-request flow, with the same progress dialog. */
                    submitEdit(form) {
                        const file = this.files[0]?.file;
                        if (! file) { form.submit(); return; }

                        // Hand the picked file back to the real input so FormData(form) carries it.
                        if (typeof DataTransfer !== 'undefined') {
                            const transfer = new DataTransfer();
                            transfer.items.add(file);
                            this.$refs.video.files = transfer.files;
                            this.$refs.video.name = 'video';
                        }

                        this.queue = this.files;
                        this.uploading = true;
                        const item = this.queue[0];
                        item.status = 'uploading';

                        this.send(form.action, this.editFormData(form, file), item)
                            .then((request) => { window.location.href = request.responseURL || this.fallbackUrl; })
                            .catch(() => { this.uploading = false; this.dialogDone = false; this.failed = item.error; });
                    },

                    editFormData(form, file) {
                        const data = new FormData(form);
                        data.set('video', file);
                        return data;
                    },

                    /** Create mode: one request per file, videos to Lesson, attachments to Bahan. */
                    async uploadQueue(form) {
                        this.uploading = true;
                        this.dialogDone = false;
                        this.queue = this.files;

                        const token = form.querySelector('[name=_token]').value;
                        const chapter = form.querySelector('[name=chapter_id]').value;
                        const published = form.querySelector('#is_published').checked;
                        const typedTitle = form.querySelector('#title').value.trim();
                        const description = form.querySelector('#description').value;
                        const singleVideo = this.videoCount() === 1;

                        for (const item of this.queue) {
                            if (item.status === 'done') continue; // a retry after partial failure
                            item.status = 'uploading'; item.progress = 0; item.error = '';

                            const data = new FormData();
                            data.set('_token', token);
                            data.set('chapter_id', chapter);

                            if (item.kind === 'video') {
                                data.set('source', 'upload');
                                data.set('video', item.file);
                                data.set('title', (singleVideo && typedTitle) ? typedTitle : this.titleFrom(item.name));
                                if (singleVideo && description) data.set('description', description);
                                if (published) data.set('is_published', '1');

                                // Thumbnail: the input's (teacher's own or captured) for the single
                                // video; a fresh capture per file when several are queued.
                                const picked = this.$refs.thumbnail?.files?.[0];
                                if (singleVideo && picked) {
                                    data.set('thumbnail', picked);
                                } else {
                                    try { data.set('thumbnail', new File([await this.captureFrame(item.file)], 'auto-thumbnail.jpg', { type: 'image/jpeg' })); }
                                    catch (error) { /* no frame — subject artwork covers it */ }
                                }
                            } else {
                                data.set('title', this.titleFrom(item.name));
                                data.set('file', item.file);
                            }

                            const url = item.kind === 'video' ? form.action : this.materialUrl;
                            try { await this.send(url, data, item); }
                            catch (error) { /* recorded on the item; the loop moves on */ }
                        }

                        this.uploading = false;
                        this.dialogDone = true;

                        // Everything landed: straight through, no extra click.
                        if (! this.failedCount()) this.finish();
                    },

                    /** One XHR with per-item progress, resolving only on a 2xx/3xx. */
                    send(url, data, item) {
                        return new Promise((resolve, reject) => {
                            const request = new XMLHttpRequest();
                            request.open('POST', url);
                            request.setRequestHeader('Accept', 'text/html');
                            request.upload.addEventListener('progress', (progressEvent) => {
                                if (! progressEvent.lengthComputable) return;
                                item.progress = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                            });
                            request.addEventListener('load', () => {
                                if (request.status >= 200 && request.status < 400) {
                                    item.status = 'done'; item.progress = 100;
                                    resolve(request); return;
                                }
                                item.status = 'failed';
                                item.error = request.status === 413 ? this.labels.serverTooBig : this.labels.uploadFailed.replace(':status', request.status);
                                reject(new Error(item.error));
                            });
                            request.addEventListener('error', () => {
                                item.status = 'failed';
                                item.error = this.labels.networkFailed;
                                reject(new Error(item.error));
                            });
                            request.send(data);
                        });
                    },

                    finish() {
                        if (! this.failedCount()) { window.location.href = this.fallbackUrl; return; }
                        // Leave only the failures in the picker so a resubmit retries just those.
                        this.files = this.files.filter((f) => f.status !== 'done');
                        this.dialogDone = false;
                    },
                };
            }
        </script>
    @endpush
</x-cikgu-layout>
