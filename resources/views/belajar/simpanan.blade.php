<x-student-layout :title="__('Simpanan Offline')">
    <div x-data="offlineDownloads()">
        <header class="mb-6">
            <div style="display:flex;align-items:flex-start;gap:16px">
                <span class="hi-tile" style="width:48px;height:48px;border-radius:14px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="download" style="width:24px;height:24px" /></span>
                <div style="display:flex;flex-direction:column;gap:3px;min-width:0">
                    <h1 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ __('Simpanan Offline') }}</h1>
                    <p style="margin:0;max-width:62ch;font-size:14px;font-weight:600;color:var(--wl-muted);line-height:1.5">{{ __('Muat turun video yang dimuat naik dan bahan sokongan untuk ditonton atau dibaca tanpa internet. Video YouTube hanya boleh ditonton dalam talian.') }}</p>
                </div>
            </div>
        </header>

        {{-- Dependent Year -> Subject filter (brief §3.1). Defaults to the student's active Tahun. --}}
        <div class="mb-6">
            <x-year-subject-filter variant="student" :all-years="false" with-chapter
                :action="route('simpanan.index')" :grades="$grades" :subjects="$subjects" :filter="$filter" />
        </div>

        {{-- Uploaded videos are downloadable; YouTube ones are honestly marked online-only. --}}
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-extrabold text-ink">{{ __('Video Pelajaran') }}</h2>

            @if ($lessons->isEmpty())
                <p class="rounded-card border border-dashed border-line p-4 text-sm text-ink-2">
                    {{ __('Tiada video untuk Tahun ini lagi.') }}
                </p>
            @else
                @php($vpal = [
                    ['accent' => '#17907B', 'tint' => '#DCF2EE', 'grad' => 'linear-gradient(135deg,#E6F4EE,#CFEADD)'],
                    ['accent' => '#3E86C9', 'tint' => '#E4EEF9', 'grad' => 'linear-gradient(135deg,#E9F1FB,#D3E4F6)'],
                    ['accent' => '#C8901C', 'tint' => '#FBEFCF', 'grad' => 'linear-gradient(135deg,#FDF4DC,#FBE7B8)'],
                    ['accent' => '#7C5CBF', 'tint' => '#E9E4F9', 'grad' => 'linear-gradient(135deg,#EEE8FB,#DDD1F3)'],
                    ['accent' => '#17907B', 'tint' => '#DCF2EE', 'grad' => 'linear-gradient(135deg,#E6F4EE,#CFEADD)'],
                    ['accent' => '#D6357F', 'tint' => '#FBE0EC', 'grad' => 'linear-gradient(135deg,#FCE7F1,#F8D2E3)'],
                ])
                <ul style="display:flex;flex-direction:column;gap:12px">
                    @foreach ($lessons as $lesson)
                        @php($p = $vpal[$loop->index % 6])
                        <li style="position:relative;overflow:hidden;display:flex;align-items:center;gap:14px;background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:12px 16px;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                            {{-- Faint leaf flourish on the right. --}}
                            <svg aria-hidden="true" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);opacity:.2;pointer-events:none;z-index:0" width="92" height="74" viewBox="0 0 92 74" fill="{{ $p['accent'] }}">
                                <path d="M8 68 C 26 54 46 42 84 12" fill="none" stroke="{{ $p['accent'] }}" stroke-width="1.5"/>
                                <ellipse cx="0" cy="0" rx="10" ry="4.5" transform="translate(26 54) rotate(-32)"/>
                                <ellipse cx="0" cy="0" rx="10" ry="4.5" transform="translate(40 47) rotate(28)"/>
                                <ellipse cx="0" cy="0" rx="9" ry="4" transform="translate(52 37) rotate(-32)"/>
                                <ellipse cx="0" cy="0" rx="9" ry="4" transform="translate(64 30) rotate(28)"/>
                                <ellipse cx="0" cy="0" rx="8" ry="3.6" transform="translate(76 19) rotate(-30)"/>
                            </svg>

                            {{-- Thumbnail on a soft gradient (video image, or the subject icon). --}}
                            <span style="position:relative;z-index:1;flex-shrink:0;width:104px;height:66px;border-radius:13px;overflow:hidden;background:{{ $p['grad'] }};display:grid;place-items:center">
                                @if ($lesson->thumbnailUrl())
                                    <img src="{{ $lesson->thumbnailUrl() }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
                                @else
                                    <span style="color:{{ $p['accent'] }}"><x-subject-icon :subject="$lesson->chapter->subject" :size="30" /></span>
                                @endif
                            </span>

                            <span style="position:relative;z-index:1;min-width:0;flex:1;display:flex;flex-direction:column;gap:3px">
                                <a href="{{ route('video.show', $lesson) }}" style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink);text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $lesson->title }}</a>
                                <span style="display:inline-flex;align-items:center;gap:9px;flex-wrap:wrap">
                                    <span style="background:{{ $p['tint'] }};color:color-mix(in oklab, {{ $p['accent'] }} 80%, #000);border-radius:999px;padding:3px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">{{ $lesson->chapter->subject->displayName() }}</span>
                                    <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">· Bab {{ $lesson->chapter->number }}</span>
                                </span>
                                @if ($lesson->teacher)
                                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:var(--wl-muted)"><x-icon name="user" style="width:14px;height:14px" />{{ __('Guru: :name', ['name' => $lesson->teacher->name]) }}</span>
                                @endif
                            </span>

                            @if ($lesson->isUpload())
                                <a href="{{ route('muat-turun.video', $lesson) }}" @click="remember(@js($lesson->title))"
                                   style="position:relative;z-index:1;flex-shrink:0;display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 20px;border-radius:12px;background:var(--wl-surface);border:1.5px solid color-mix(in oklab, {{ $p['accent'] }} 32%, #fff);color:{{ $p['accent'] }};font-family:'Geist',sans-serif;font-weight:800;font-size:14px;text-decoration:none">
                                    <x-icon name="download" style="width:17px;height:17px" />{{ __('Muat Turun') }}
                                </a>
                            @else
                                <span style="position:relative;z-index:1;flex-shrink:0;display:flex;flex-direction:column;align-items:flex-end;gap:5px">
                                    <span style="display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 18px;border-radius:12px;background:{{ $p['tint'] }};color:{{ $p['accent'] }};font-family:'Geist',sans-serif;font-weight:800;font-size:14px"><x-icon name="play" style="width:16px;height:16px" />{{ __('Dalam talian sahaja') }}</span>
                                    <span style="font-size:12px;color:var(--wl-muted);text-align:right">{{ __('Video ini hanya boleh ditonton dalam talian.') }}</span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Supporting materials: genuinely useful offline even for YouTube lessons. --}}
        <section class="mb-10">
            <h2 class="mb-4 text-lg font-extrabold text-ink">{{ __('Bahan Sokongan') }}</h2>

            @if ($materialsByChapter->isEmpty())
                <p class="rounded-card border border-dashed border-line p-4 text-sm text-ink-2">
                    {{ __('Tiada bahan sokongan untuk Tahun ini lagi.') }}
                </p>
            @else
                <ul style="display:flex;flex-direction:column;gap:12px">
                    @foreach ($materialsByChapter as $materials)
                        @foreach ($materials as $material)
                            @php($chapter = $material->chapter)
                            @php($ac = $chapter->subject->color ?: '#17907B')
                            @php($tint = "color-mix(in oklab, {$ac} 14%, #fff)")
                            @php($tintInk = "color-mix(in oklab, {$ac} 80%, #000)")
                            @php($grad = "linear-gradient(135deg, color-mix(in oklab, {$ac} 10%, #fff), color-mix(in oklab, {$ac} 22%, #fff))")
                            <li style="position:relative;overflow:hidden;display:flex;align-items:center;gap:16px;background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:12px 16px;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                                {{-- Faint leaf flourish. --}}
                                <svg aria-hidden="true" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);opacity:.18;pointer-events:none;z-index:0" width="92" height="74" viewBox="0 0 92 74" fill="{{ $ac }}">
                                    <path d="M8 68 C 26 54 46 42 84 12" fill="none" stroke="{{ $ac }}" stroke-width="1.5"/>
                                    <ellipse cx="0" cy="0" rx="10" ry="4.5" transform="translate(26 54) rotate(-32)"/>
                                    <ellipse cx="0" cy="0" rx="10" ry="4.5" transform="translate(40 47) rotate(28)"/>
                                    <ellipse cx="0" cy="0" rx="9" ry="4" transform="translate(52 37) rotate(-32)"/>
                                    <ellipse cx="0" cy="0" rx="9" ry="4" transform="translate(64 30) rotate(28)"/>
                                    <ellipse cx="0" cy="0" rx="8" ry="3.6" transform="translate(76 19) rotate(-30)"/>
                                </svg>

                                {{-- File-type icon on a soft gradient. --}}
                                <span style="position:relative;z-index:1;flex-shrink:0;width:96px;height:66px;border-radius:13px;background:{{ $grad }};display:grid;place-items:center;color:{{ $ac }}"><x-icon :name="$material->iconName()" style="width:30px;height:30px" /></span>

                                <span style="position:relative;z-index:1;min-width:0;flex:1;display:flex;flex-direction:column;gap:5px">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15.5px;color:var(--wl-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $material->title }}</span>
                                    <span style="display:inline-flex;align-items:center;gap:9px;flex-wrap:wrap">
                                        <span style="background:{{ $tint }};color:{{ $tintInk }};border-radius:999px;padding:3px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">{{ $chapter->subject->displayName() }}</span>
                                        <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">· Bab {{ $chapter->number }}: {{ $chapter->title }}</span>
                                    </span>
                                    <span style="display:inline-flex;align-items:center;gap:10px;font-size:12.5px;color:var(--wl-muted)">
                                        <span style="display:inline-flex;align-items:center;gap:5px"><x-icon name="file" style="width:14px;height:14px" />{{ $material->humanSize() }}</span>
                                        @if ($material->teacher)
                                            <span style="width:4px;height:4px;border-radius:50%;background:var(--wl-line-2)"></span>
                                            <span style="display:inline-flex;align-items:center;gap:5px"><x-icon name="user" style="width:14px;height:14px" />{{ __('Guru: :name', ['name' => $material->teacher->name]) }}</span>
                                        @endif
                                    </span>
                                </span>

                                <a href="{{ route('muat-turun.bahan', $material) }}" @click="remember(@js($material->title))"
                                   style="position:relative;z-index:1;flex-shrink:0;display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 20px;border-radius:12px;background:var(--wl-surface);border:1.5px solid color-mix(in oklab, {{ $ac }} 32%, #fff);color:{{ $ac }};font-family:'Geist',sans-serif;font-weight:800;font-size:14px;text-decoration:none">
                                    <x-icon name="download" style="width:17px;height:17px" />{{ __('Muat Turun') }}
                                </a>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            @endif
        </section>

    </div>

    @push('scripts')
        <script>
            function offlineDownloads() {
                return {
                    downloads: JSON.parse(localStorage.getItem('my-downloads') || '[]'),

                    remember(title) {
                        const entry = { title, at: new Date().toLocaleDateString() };
                        this.downloads = [entry, ...this.downloads.filter(d => d.title !== title)].slice(0, 50);
                        localStorage.setItem('my-downloads', JSON.stringify(this.downloads));
                    },

                    clearAll() {
                        this.downloads = [];
                        localStorage.removeItem('my-downloads');
                    },
                };
            }
        </script>
    @endpush
</x-student-layout>
