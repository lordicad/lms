@props(['lesson', 'progress' => null])

{{--
    One player for both sources. It counts a view on first play (unchanged rule), resumes from
    saved progress, and pings /kemajuan every ~10s while playing, on pause, on end, and on the way
    out via sendBeacon. Upload uses HTML5 <video>; YouTube uses the IFrame Player API on the
    youtube-nocookie host so the student never leaves the platform.
--}}

@php
    $resumeAt = ($progress && ! $progress->completed && $progress->position_seconds >= 5)
        ? $progress->position_seconds
        : 0;

    $config = [
        'source' => $lesson->source,
        'markUrl' => route('video.tonton', $lesson),
        'progressUrl' => route('kemajuan.simpan', $lesson),
        'resumeAt' => $resumeAt,
        'alreadyWatched' => $lesson->watchedBy(auth()->user()),
        'frameId' => 'player-yt-'.$lesson->id,
        // Only students have progress to save; a teacher previewing just plays + counts a view.
        'track' => (bool) auth()->user()?->isStudent(),
        'labels' => ['resume' => __('Sambung dari :time')],
    ];
@endphp

<div x-data="player(@js($config))" x-init="init()">
    {{-- Resume banner. --}}
    <div x-show="showResume" x-cloak x-transition
         class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-control border border-line bg-surface-2 px-4 py-2.5">
        <span class="flex items-center gap-2 text-sm font-bold text-ink">
            <x-icon name="clock" class="h-4 w-4 text-brand" />
            <span x-text="resumeText"></span>
        </span>

        <button type="button" @click="restart()" class="text-sm font-bold text-brand hover:underline">
            {{ __('Mula semula') }}
        </button>
    </div>

    <div class="overflow-hidden rounded-card border border-line bg-black">
        @if ($lesson->isYoutube())
            <div class="aspect-video">
                <iframe id="{{ $config['frameId'] }}" x-ref="ytframe" class="h-full w-full"
                        src="{{ $lesson->embedUrl() }}&enablejsapi=1&origin={{ urlencode(config('app.url')) }}"
                        title="{{ $lesson->title }}" frameborder="0"
                        allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        @else
            <video x-ref="video" class="aspect-video w-full" controls preload="metadata"
                   @if ($lesson->thumbnailUrl()) poster="{{ $lesson->thumbnailUrl() }}" @endif>
                <source src="{{ $lesson->videoUrl() }}"
                        type="video/{{ str_ends_with($lesson->video_path ?? '', '.webm') ? 'webm' : 'mp4' }}">
                {{ __('Pelayar anda tidak menyokong video HTML5.') }}
                <a href="{{ $lesson->videoUrl() }}" class="underline">{{ __('Muat turun video') }}</a>.
            </video>
        @endif
    </div>
</div>

@once
    @push('scripts')
        <script>
            function player(config) {
                return {
                    ...config,
                    duration: 0,
                    position: 0,
                    counted: config.alreadyWatched,
                    showResume: false,
                    resumeText: '',
                    yt: null,
                    saveTimer: null,
                    pollTimer: null,
                    resumed: false,

                    init() {
                        if (this.source === 'youtube') this.initYoutube();
                        else this.initUpload();

                        // Save on the way out — sendBeacon survives the page unloading.
                        document.addEventListener('visibilitychange', () => { if (document.hidden) this.save(true); });
                        window.addEventListener('pagehide', () => this.save(true));
                        window.addEventListener('beforeunload', () => this.save(true));
                    },

                    token() {
                        return document.querySelector('meta[name=csrf-token]')?.content;
                    },

                    formatTime(seconds) {
                        seconds = Math.max(0, Math.floor(seconds));
                        const m = Math.floor(seconds / 60);
                        const s = seconds % 60;
                        return m + ':' + String(s).padStart(2, '0');
                    },

                    gotDuration(value) {
                        if (value && ! this.duration) this.duration = Math.floor(value);
                    },

                    markViewed() {
                        if (this.counted) return;
                        this.counted = true;

                        fetch(this.markUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': this.token(), 'Accept': 'application/json' },
                        }).catch(() => {});
                    },

                    maybeResume(seekFn) {
                        if (this.resumeAt > 0 && ! this.resumed) {
                            this.resumed = true;
                            seekFn(this.resumeAt);
                            this.resumeText = this.labels.resume.replace(':time', this.formatTime(this.resumeAt));
                            this.showResume = true;
                            setTimeout(() => { this.showResume = false; }, 8000);
                        }
                    },

                    restart() {
                        this.resumeAt = 0;
                        this.showResume = false;

                        if (this.source === 'youtube' && this.yt) this.yt.seekTo(0, true);
                        else if (this.$refs.video) this.$refs.video.currentTime = 0;
                    },

                    save(useBeacon = false) {
                        if (! this.track) return;
                        if (this.position <= 0 && ! this.duration) return;

                        const body = new FormData();
                        body.append('position_seconds', Math.max(0, Math.floor(this.position)));
                        if (this.duration) body.append('duration_seconds', Math.floor(this.duration));
                        body.append('_token', this.token());

                        if (useBeacon && navigator.sendBeacon) {
                            navigator.sendBeacon(this.progressUrl, body);
                        } else {
                            fetch(this.progressUrl, { method: 'POST', body, headers: { 'Accept': 'application/json' } }).catch(() => {});
                        }
                    },

                    startTicking() {
                        if (! this.track) return;
                        if (! this.saveTimer) this.saveTimer = setInterval(() => this.save(false), 10000);
                    },

                    stopTicking() {
                        clearInterval(this.saveTimer);
                        this.saveTimer = null;
                    },

                    // ----- HTML5 upload -----
                    initUpload() {
                        const v = this.$refs.video;

                        v.addEventListener('loadedmetadata', () => {
                            this.gotDuration(v.duration);
                            this.maybeResume((t) => { v.currentTime = t; });
                        });
                        v.addEventListener('play', () => { this.markViewed(); this.startTicking(); });
                        v.addEventListener('timeupdate', () => { this.position = v.currentTime; });
                        v.addEventListener('pause', () => { this.position = v.currentTime; this.stopTicking(); this.save(false); });
                        v.addEventListener('ended', () => { this.position = this.duration || v.duration; this.stopTicking(); this.save(false); });
                    },

                    // ----- YouTube IFrame API -----
                    initYoutube() {
                        const build = () => {
                            if (! window.YT || ! window.YT.Player) return;

                            this.yt = new window.YT.Player(this.frameId, {
                                events: {
                                    onReady: () => {
                                        this.gotDuration(this.yt.getDuration());
                                        this.maybeResume((t) => this.yt.seekTo(t, true));
                                    },
                                    onStateChange: (event) => {
                                        const state = event.data;

                                        if (state === window.YT.PlayerState.PLAYING) {
                                            this.markViewed();
                                            this.gotDuration(this.yt.getDuration());
                                            this.startTicking();
                                            this.startPolling();
                                        } else if (state === window.YT.PlayerState.PAUSED) {
                                            this.stopTicking();
                                            this.save(false);
                                        } else if (state === window.YT.PlayerState.ENDED) {
                                            this.position = this.duration;
                                            this.stopTicking();
                                            this.stopPolling();
                                            this.save(false);
                                        }
                                    },
                                },
                            });
                        };

                        if (window.YT && window.YT.Player) {
                            build();
                            return;
                        }

                        const previous = window.onYouTubeIframeAPIReady;
                        window.onYouTubeIframeAPIReady = () => {
                            if (typeof previous === 'function') previous();
                            build();
                        };

                        if (! document.getElementById('yt-api')) {
                            const script = document.createElement('script');
                            script.id = 'yt-api';
                            script.src = 'https://www.youtube.com/iframe_api';
                            document.head.appendChild(script);
                        }
                    },

                    startPolling() {
                        if (this.pollTimer) return;
                        this.pollTimer = setInterval(() => {
                            if (this.yt && this.yt.getCurrentTime) this.position = this.yt.getCurrentTime();
                        }, 1000);
                    },

                    stopPolling() {
                        clearInterval(this.pollTimer);
                        this.pollTimer = null;
                    },
                };
            }
        </script>
    @endpush
@endonce
