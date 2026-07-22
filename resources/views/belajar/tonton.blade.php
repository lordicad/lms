@php($me = auth()->user())

<x-dynamic-component :component="$me->isTeacher() ? 'app-layout' : 'student-layout'" :title="$lesson->title">
    @php($col = $subject->color ?: '#17907B')
    @php($tagBg = "color-mix(in oklab, {$col} 15%, #fff)")
    @php($tagColor = "color-mix(in oklab, {$col} 82%, #000)")
    @php($done = count($watchedIds))
    @php($total = $playlist->count())
    @php($pct = $total ? (int) round($done / $total * 100) : 0)

    <div style="display:flex;flex-direction:column;gap:18px">
        <a href="{{ route('bab.show', $chapter) }}" class="wl-back"
           style="align-self:flex-start;display:flex;align-items:center;gap:8px;font-family:'Geist',sans-serif;font-size:14px;font-weight:800;color:var(--wl-muted-2);text-decoration:none;padding:6px 0"><x-icon name="arrow-left" class="h-4 w-4" /> Bab {{ $chapter->number }}: {{ $chapter->title }}</a>

        <div class="wl-watch-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:24px;align-items:start">
            {{-- ══ LEFT: player, title, actions, tabs ══ --}}
            <div style="display:flex;flex-direction:column;gap:16px;min-width:0">
                <div style="border-radius:20px;overflow:hidden;box-shadow:0 10px 30px var(--wl-line-2)">
                    <x-player :lesson="$lesson" :progress="$progress" />
                </div>

                <div style="display:flex;align-items:flex-start;gap:16px;flex-wrap:wrap">
                    <div style="display:flex;flex-direction:column;gap:10px;min-width:0;flex:1">
                        <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:24px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ $lesson->title }}</h2>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span style="background:{{ $tagBg }};color:{{ $tagColor }};border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800"><x-subject-emoji :subject="$subject" class="text-sm" /> {{ $subject->displayName() }}</span>
                            <span style="font-size:13px;font-weight:700;color:var(--wl-muted)">{{ $grade->name }}</span>
                            <span style="font-size:13px;font-weight:700;color:var(--wl-muted)">Bab {{ $chapter->number }}: {{ $chapter->title }}</span>
                            @if ($lesson->durationLabel())
                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:13px;font-weight:700;color:var(--wl-muted)"><x-icon name="clock" class="h-4 w-4" /> {{ $lesson->durationLabel() }}</span>
                            @endif
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:13px;font-weight:700;color:var(--wl-muted)"><x-icon name="eye" class="h-4 w-4" /> {{ $lesson->views_count }} {{ __('tontonan') }}</span>
                            @if ($me->isStudent() && in_array($lesson->id, $watchedIds))
                                <span style="display:inline-flex;align-items:center;gap:4px;background:#DCF2EE;color:#0F7A68;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800"><x-icon name="check" class="h-4 w-4" /> {{ __('Ditonton') }}</span>
                            @endif
                        </div>
                    </div>
                    @if ($me->isStudent())
                        {{-- AJAX favourite toggle: adds/removes without navigating (the endpoint
                             returns JSON), and updates the heart + label live. --}}
                        <button type="button" x-data="{
                                    fav: {{ $favourited ? 'true' : 'false' }},
                                    busy: false,
                                    toggle() {
                                        if (this.busy) return;
                                        const was = this.fav;
                                        this.fav = ! was;
                                        this.busy = true;
                                        const token = document.querySelector('meta[name=csrf-token]')?.content;
                                        fetch(was ? '{{ route('kegemaran.padam', $lesson) }}' : '{{ route('kegemaran.simpan', $lesson) }}', {
                                            method: was ? 'DELETE' : 'POST',
                                            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                                        }).then(r => { if (! r.ok) throw new Error('failed'); })
                                          .catch(() => { this.fav = was; })
                                          .finally(() => { this.busy = false; });
                                    }
                                }"
                                @click="toggle()"
                                :aria-pressed="fav ? 'true' : 'false'"
                                style="flex-shrink:0;min-height:46px;cursor:pointer;border-radius:12px;border:1.5px solid var(--wl-line-2);background:var(--wl-surface);font-family:'Geist',sans-serif;font-weight:800;font-size:14px;padding:0 18px;display:flex;align-items:center;gap:8px;color:var(--wl-ink)">
                            <span x-text="fav ? '♥' : '♡'" :style="fav ? 'color:#EB5E5A;font-size:16px' : 'color:var(--wl-muted-2);font-size:16px'"></span>
                            <span>{{ __('Gemari') }}</span>
                        </button>
                    @endif
                </div>

                {{-- Action row: previous / download / share / next, exactly the real capabilities. --}}
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    @if ($previous)
                        <a href="{{ route('video.show', $previous) }}" class="wl-btn-secondary"
                           style="display:inline-flex;align-items:center;gap:8px;min-height:44px;border:1.5px solid var(--wl-line-2);border-radius:12px;background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 16px;text-decoration:none">
                            <x-icon name="arrow-left" class="h-4 w-4" /> {{ __('Video Sebelum') }}
                        </a>
                    @endif

                    <div style="margin-left:auto;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                        @if ($me->isStudent() && $lesson->isUpload())
                            <a href="{{ route('muat-turun.video', $lesson) }}" class="wl-btn-secondary"
                               style="display:inline-flex;align-items:center;gap:8px;min-height:44px;border:1.5px solid var(--wl-line-2);border-radius:12px;background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 16px;text-decoration:none">
                                <x-icon name="download" class="h-4 w-4" /> {{ __('Muat Turun') }}
                            </a>
                        @endif

                        {{-- Copy-link share. navigator.share where the device offers a real share
                             sheet; the clipboard otherwise, with the label as the confirmation. --}}
                        <button type="button" class="wl-btn-secondary"
                                x-data="{ copied: false,
                                          share() {
                                              const url = window.location.href;
                                              if (navigator.share) { navigator.share({ title: @js($lesson->title), url }).catch(() => {}); return; }
                                              navigator.clipboard?.writeText(url).then(() => {
                                                  this.copied = true;
                                                  setTimeout(() => this.copied = false, 2000);
                                              });
                                          } }"
                                @click="share()"
                                style="display:inline-flex;align-items:center;gap:8px;min-height:44px;cursor:pointer;border:1.5px solid var(--wl-line-2);border-radius:12px;background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 16px">
                            <x-icon name="share" class="h-4 w-4" />
                            <span x-text="copied ? @js(__('Pautan disalin')) : @js(__('Kongsi'))">{{ __('Kongsi') }}</span>
                        </button>

                        @if ($next)
                            <a href="{{ route('video.show', $next) }}" class="wl-btn-primary"
                               style="display:inline-flex;align-items:center;gap:8px;min-height:44px;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;text-decoration:none">
                                {{ __('Video Seterusnya') }} <x-icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Tabs: the video's own story on the first, its Bahan on the second. --}}
                <div x-data="{ tab: 'overview' }" style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;box-shadow:0 3px 12px rgba(46,44,80,.04);overflow:hidden">
                    <div role="tablist" aria-label="{{ __('Maklumat video') }}" style="display:flex;gap:4px;border-bottom:1px solid var(--wl-line);padding:0 14px">
                        <button type="button" role="tab" id="wtab-overview" :aria-selected="tab === 'overview'" aria-controls="wpanel-overview"
                                @click="tab = 'overview'"
                                style="border:none;background:transparent;cursor:pointer;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:14px 12px 12px"
                                :style="tab === 'overview' ? 'color:#17907B;box-shadow:inset 0 -2.5px 0 #17907B' : 'color:var(--wl-muted)'">
                            {{ __('Ringkasan') }}
                        </button>
                        <button type="button" role="tab" id="wtab-bahan" :aria-selected="tab === 'bahan'" aria-controls="wpanel-bahan"
                                @click="tab = 'bahan'"
                                style="border:none;background:transparent;cursor:pointer;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:14px 12px 12px"
                                :style="tab === 'bahan' ? 'color:#17907B;box-shadow:inset 0 -2.5px 0 #17907B' : 'color:var(--wl-muted)'">
                            {{ __('Bahan') }} ({{ $materials->count() }})
                        </button>
                    </div>

                    <div id="wpanel-overview" role="tabpanel" aria-labelledby="wtab-overview" x-show="tab === 'overview'" style="padding:18px 20px">
                        @if ($lesson->description)
                            <p style="margin:0;font-size:14px;line-height:1.6;color:#4A4B63;white-space:pre-line">{{ $lesson->description }}</p>
                        @else
                            <p style="margin:0;font-size:13.5px;color:var(--wl-muted)">{{ __('Tiada penerangan untuk video ini.') }}</p>
                        @endif
                        <p style="margin:14px 0 0;font-size:12.5px;font-weight:700;color:var(--wl-muted)">{{ __('Oleh') }} {{ $lesson->teacher->name }}</p>
                    </div>

                    <div id="wpanel-bahan" role="tabpanel" aria-labelledby="wtab-bahan" x-show="tab === 'bahan'" x-cloak style="padding:18px 20px;display:flex;flex-direction:column;gap:10px">
                        @forelse ($materials as $material)
                            <div style="display:flex;align-items:center;gap:12px;border:1px solid var(--wl-line);border-radius:12px;padding:12px 14px">
                                <span style="width:36px;height:36px;border-radius:10px;background:#FDE7E0;color:#C24936;display:grid;place-items:center;flex-shrink:0"><x-icon name="file" class="h-4 w-4" /></span>
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--wl-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->title }}</span>
                                    <span style="font-size:12px;color:var(--wl-muted)">{{ strtoupper($material->extension()) }} · {{ $material->humanSize() }}</span>
                                </div>
                                <a href="{{ route('muat-turun.bahan', $material) }}" title="{{ __('Muat turun') }}" style="width:36px;height:36px;border-radius:10px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;text-decoration:none;flex-shrink:0"><x-icon name="download" class="h-4 w-4" /></a>
                            </div>
                        @empty
                            <p style="margin:0;font-size:13.5px;color:var(--wl-muted)">{{ __('Tiada bahan sokongan untuk video ini.') }}</p>
                        @endforelse
                    </div>
                </div>

                {{-- Chapter progress + keep-going rail (students only — a teacher preview has no progress). --}}
                @if ($me->isStudent() && $total > 0)
                    <div class="wl-watch-foot" style="display:grid;grid-template-columns:260px minmax(0,1fr);gap:16px;align-items:stretch">
                        <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 3px 12px rgba(46,44,80,.04)">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--wl-ink)">{{ __('Kemajuan anda dalam Bab :number', ['number' => $chapter->number]) }}</span>
                            <div style="display:flex;align-items:center;gap:14px">
                                <div role="img" aria-label="{{ $pct }}%" style="width:72px;height:72px;border-radius:50%;background:conic-gradient(#17907B {{ $pct * 3.6 }}deg, var(--wl-chip) 0);display:grid;place-items:center;flex-shrink:0">
                                    <span style="width:52px;height:52px;border-radius:50%;background:var(--wl-surface);display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;color:var(--wl-ink)">{{ $pct }}%</span>
                                </div>
                                <span style="font-size:13px;font-weight:700;color:var(--wl-muted-2)">{{ __(':done daripada :total video ditonton', ['done' => $done, 'total' => $total]) }}</span>
                            </div>
                        </div>

                        @php($rest = $playlist->where('id', '!=', $lesson->id)->take(6))
                        @if ($rest->isNotEmpty())
                            <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 3px 12px rgba(46,44,80,.04);min-width:0">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--wl-ink)">{{ __('Teruskan belajar') }}</span>
                                <div style="display:flex;gap:12px;overflow-x:auto;padding-bottom:4px">
                                    @foreach ($rest as $item)
                                        <a href="{{ route('video.show', $item) }}" class="wl-lift" style="flex:0 0 150px;text-decoration:none;display:flex;flex-direction:column;gap:6px">
                                            <span style="position:relative;display:block;width:150px;height:88px;border-radius:12px;overflow:hidden;background:{{ $tagBg }}">
                                                @if ($item->thumbnailUrl())
                                                    <img src="{{ $item->thumbnailUrl() }}" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover;display:block">
                                                @else
                                                    <span style="position:absolute;inset:0;display:grid;place-items:center;color:{{ $tagColor }}"><x-icon name="play" class="h-6 w-6" /></span>
                                                @endif
                                                @if ($item->durationLabel())
                                                    <span style="position:absolute;right:6px;bottom:6px;background:rgba(0,0,0,.72);color:#fff;border-radius:6px;padding:2px 6px;font-family:'Geist',sans-serif;font-size:10.5px;font-weight:700">{{ $item->durationLabel() }}</span>
                                                @endif
                                            </span>
                                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:12.5px;color:var(--wl-ink);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $item->title }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- ══ RIGHT: chapter playlist, materials, quiz ══ --}}
            <div style="display:flex;flex-direction:column;gap:20px">
                {{-- Playlist --}}
                <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;box-shadow:0 3px 12px rgba(46,44,80,.04);overflow:hidden">
                    <div style="padding:16px 18px 12px;display:flex;flex-direction:column;gap:10px">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">Bab {{ $chapter->number }}: {{ $chapter->title }}</span>
                            @if ($me->isStudent())
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:12.5px;color:var(--wl-muted)">{{ $pct }}%</span>
                            @endif
                        </div>
                        @if ($me->isStudent())
                            <div style="height:7px;border-radius:999px;background:var(--wl-chip);overflow:hidden" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                <div style="height:100%;width:{{ $pct }}%;border-radius:999px;background:#17907B"></div>
                            </div>
                        @endif
                    </div>

                    <div style="display:flex;flex-direction:column">
                        @foreach ($playlist as $index => $item)
                            @php($isCurrent = $item->id === $lesson->id)
                            @php($isWatched = in_array($item->id, $watchedIds))
                            <a href="{{ route('video.show', $item) }}" @if ($isCurrent) aria-current="page" @endif
                               style="display:flex;align-items:center;gap:12px;padding:11px 18px;text-decoration:none;{{ $isCurrent ? 'background:#EFEBFB' : '' }}">
                                <span style="width:26px;height:26px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;{{ $isWatched ? 'background:#17907B;color:#fff' : ($isCurrent ? 'background:#7C5CD6;color:#fff' : 'border:1.5px solid var(--wl-line-3);color:var(--wl-muted);font-family:Geist,sans-serif;font-size:11.5px;font-weight:800') }}">
                                    @if ($isWatched)
                                        <x-icon name="check" class="h-4 w-4" />
                                    @elseif ($isCurrent)
                                        <x-icon name="play" class="h-3 w-3" />
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </span>
                                <span style="flex:1;min-width:0;font-family:'Geist',sans-serif;font-weight:{{ $isCurrent ? '800' : '700' }};font-size:13px;color:{{ $isCurrent ? '#5A3EC8' : 'var(--wl-ink)' }};overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $item->title }}</span>
                                @if ($item->durationLabel())
                                    <span style="flex-shrink:0;font-family:'Geist',sans-serif;font-size:12px;font-weight:700;color:var(--wl-muted)">{{ $item->durationLabel() }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>

                    <div style="padding:12px 18px 16px">
                        <a href="{{ route('bab.show', $chapter) }}" class="wl-btn-secondary"
                           style="display:flex;align-items:center;justify-content:center;min-height:42px;border:1.5px solid var(--wl-line-2);border-radius:12px;background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:800;font-size:13px;text-decoration:none">{{ __('Lihat bab penuh') }}</a>
                    </div>
                </div>

                {{-- Supporting materials --}}
                <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:16px 18px;display:flex;flex-direction:column;gap:10px;box-shadow:0 3px 12px rgba(46,44,80,.04)">
                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ __('Bahan sokongan') }}</span>
                    @forelse ($materials as $material)
                        <div class="wl-row-lift" style="display:flex;align-items:center;gap:12px;border:1px solid var(--wl-line);border-radius:12px;padding:10px 12px">
                            <span style="width:34px;height:34px;border-radius:10px;background:#FDE7E0;color:#C24936;display:grid;place-items:center;flex-shrink:0"><x-icon name="file" class="h-4 w-4" /></span>
                            <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13px;color:var(--wl-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->title }}</span>
                                <span style="font-size:11.5px;color:var(--wl-muted)">{{ strtoupper($material->extension()) }} · {{ $material->humanSize() }}</span>
                            </div>
                            <a href="{{ route('muat-turun.bahan', $material) }}" title="{{ __('Muat turun') }}" style="width:34px;height:34px;border-radius:10px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;text-decoration:none;flex-shrink:0"><x-icon name="download" class="h-4 w-4" /></a>
                        </div>
                    @empty
                        <p style="margin:0;font-size:13px;color:var(--wl-muted)">{{ __('Tiada bahan sokongan untuk video ini.') }}</p>
                    @endforelse
                </div>

                {{-- Chapter quizzes --}}
                @if ($quizzes->isNotEmpty())
                    <div style="display:flex;flex-direction:column;gap:12px">
                        @foreach ($quizzes as $quiz)
                            <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:16px 18px;display:flex;flex-direction:column;gap:6px;box-shadow:0 3px 12px rgba(46,44,80,.04)">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--wl-ink)">{{ $quiz->title }}</span>
                                <span style="font-size:12.5px;color:var(--wl-muted)">
                                    @if ($quiz->isInteractive())
                                        {{ __(':count soalan', ['count' => $quiz->questions_count]) }}@if ($quiz->duration_minutes) · ±{{ $quiz->duration_minutes }} {{ __('minit') }}@endif
                                    @else
                                        {{ __('Kuiz bercetak') }}
                                    @endif
                                </span>
                                <a href="{{ route('kuiz.intro', $quiz) }}"
                                   style="display:flex;align-items:center;justify-content:center;gap:8px;margin-top:8px;min-height:44px;border-radius:12px;background:#7C5CD6;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;text-decoration:none">
                                    <x-icon name="quiz" class="h-4 w-4" /> {{ $quiz->isFile() ? __('Lihat Kuiz') : __('Mula Kuiz') }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <style>
            /* The playlist column stacks under the player on narrow screens. */
            @media (max-width: 960px) {
                .wl-watch-grid { grid-template-columns: minmax(0,1fr) !important; }
                .wl-watch-foot { grid-template-columns: minmax(0,1fr) !important; }
            }
        </style>
    @endpush
</x-dynamic-component>
