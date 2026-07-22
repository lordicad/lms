@php($me = auth()->user())

<x-dynamic-component :component="$me->isTeacher() ? 'app-layout' : 'student-layout'" :title="$lesson->title">
    @php($col = $subject->color ?: '#17907B')
    @php($tagBg = "color-mix(in oklab, {$col} 15%, #fff)")
    @php($tagColor = "color-mix(in oklab, {$col} 82%, #000)")

    <div style="display:flex;flex-direction:column;gap:18px">
        <a href="{{ route('bab.show', $chapter) }}" class="wl-back"
           style="align-self:flex-start;display:flex;align-items:center;gap:8px;font-family:'Geist',sans-serif;font-size:14px;font-weight:800;color:var(--wl-muted-2);text-decoration:none;padding:6px 0">← Bab {{ $chapter->number }}: {{ $chapter->title }}</a>

        <div style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:24px;align-items:start">
            {{-- LEFT: player + title/meta --}}
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
                            <span style="font-size:13px;font-weight:700;color:var(--wl-muted)">{{ $lesson->teacher->name }}</span>
                            <span style="font-size:13px;font-weight:700;color:var(--wl-muted)">👁 {{ $lesson->views_count }} {{ __('tontonan') }}</span>
                            @if ($me->isStudent() && $lesson->watchedBy($me))
                                <span style="background:#DCF2EE;color:#0F7A68;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">✓ {{ __('Ditonton') }}</span>
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

                @if ($lesson->description)
                    <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:18px 20px;box-shadow:0 3px 12px rgba(46,44,80,.04)">
                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:var(--wl-ink)">{{ __('Tentang video ini') }}</span>
                        <p style="margin:8px 0 0;font-size:14px;line-height:1.6;color:#4A4B63;white-space:pre-line">{{ $lesson->description }}</p>
                    </div>
                @endif
            </div>

            {{-- RIGHT: materials + quizzes --}}
            <div style="display:flex;flex-direction:column;gap:20px">
                <div style="display:flex;flex-direction:column;gap:10px">
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:16px;font-weight:800;color:var(--wl-ink)">{{ __('Bahan sokongan') }}</h3>
                    @if ($materials->isEmpty())
                        <p style="margin:0;background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:16px;padding:16px;font-size:13px;color:var(--wl-muted)">{{ __('Tiada bahan sokongan untuk video ini.') }}</p>
                    @else
                        @foreach ($materials as $material)
                            <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:0 3px 12px rgba(46,44,80,.04)">
                                <span style="width:38px;height:38px;border-radius:10px;background:#FDE7E0;display:grid;place-items:center;font-size:15px;flex-shrink:0">📄</span>
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0;flex:1">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--wl-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->title }}</span>
                                    <span style="font-size:12px;color:var(--wl-muted)">{{ $material->humanSize() }}</span>
                                </div>
                                <a href="{{ route('muat-turun.bahan', $material) }}" title="{{ __('Muat turun') }}" style="width:38px;height:38px;border-radius:10px;background:#DCF2EE;color:#0F7A68;font-size:15px;display:grid;place-items:center;text-decoration:none;flex-shrink:0">⬇</a>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div style="display:flex;flex-direction:column;gap:10px">
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:16px;font-weight:800;color:var(--wl-ink)">{{ __('Kuiz dalam bab ini') }}</h3>
                    @if ($quizzes->isEmpty())
                        <p style="margin:0;background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:16px;padding:16px;font-size:13px;color:var(--wl-muted)">{{ __('Tiada kuiz untuk bab ini lagi.') }}</p>
                    @else
                        @foreach ($quizzes as $quiz)
                            <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:16px;padding:16px 18px;display:flex;flex-direction:column;gap:6px;box-shadow:0 3px 12px rgba(46,44,80,.04)">
                                <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;color:var(--wl-ink)">{{ $quiz->title }}</span>
                                <span style="font-size:12.5px;color:var(--wl-muted)">{{ $quiz->isFile() ? __('Kuiz bercetak') : __('Kuiz interaktif') }}</span>
                                <a href="{{ route('kuiz.intro', $quiz) }}" class="wl-btn-primary" style="align-self:flex-start;margin-top:6px;min-height:42px;display:inline-flex;align-items:center;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;text-decoration:none">{{ $quiz->isFile() ? __('Lihat') : __('Cuba Kuiz') }}</a>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
