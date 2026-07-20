<x-student-layout :title="__('Utama')">
    @if (! $grade)
        <div style="background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:22px;padding:56px;display:flex;flex-direction:column;align-items:center;gap:10px;text-align:center;max-width:520px;margin:0 auto">
            <span style="font-size:32px">📚</span>
            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--wl-ink)">{{ __('Tahun anda belum ditetapkan') }}</h3>
            <p style="margin:0;font-size:14.5px;color:var(--wl-muted);max-width:360px">{{ __('Sila kemas kini profil anda dan pilih Tahun supaya kami boleh tunjukkan kandungan yang betul.') }}</p>
            <a href="{{ route('profile.edit') }}" class="wl-btn-primary" style="margin-top:6px;min-height:46px;display:inline-flex;align-items:center;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px;text-decoration:none">{{ __('Kemas Kini Profil') }}</a>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:28px">

            {{-- ── TRENDING / RESUME HERO ── --}}
            @if ($hero)
                @php($hs = $hero->chapter->subject)
                @php($heroFav = $hero->isFavouritedBy($user))
                <div style="display:grid;grid-template-columns:minmax(0,1fr);gap:20px;align-items:stretch">
                    <div style="border-radius:22px;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) minmax(160px,42%);background:#E3F0FA;box-shadow:0 10px 30px rgba(66,118,174,.18)">
                        <div style="padding:50px;display:flex;flex-direction:column;justify-content:center;gap:14px;min-width:0">
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                                <span style="background:var(--wl-surface);color:#2E6CA8;border-radius:999px;padding:5px 13px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800"><x-subject-emoji :subject="$hs" class="text-sm" /> {{ $hs->displayName() }}</span>
                                <span style="background:var(--wl-surface);color:#4A5A6B;border-radius:999px;padding:5px 13px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">Bab {{ $hero->chapter->number }}</span>
                                @unless ($heroResuming)
                                    <span style="background:#17907B;color:#fff;border-radius:999px;padding:5px 13px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800;letter-spacing:.08em">TRENDING</span>
                                @endunless
                            </div>
                            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:26px;font-weight:800;color:#1A2433;letter-spacing:-.01em;text-wrap:balance">{{ $hero->title }}</h2>
                            <div style="display:flex;gap:10px;flex-wrap:wrap">
                                <a href="{{ route('video.show', $hero) }}" class="wl-btn-primary" style="min-height:46px;display:inline-flex;align-items:center;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px;text-decoration:none">▶&nbsp; {{ $heroResuming ? __('Sambung Menonton') : __('Tonton') }}</a>
                                {{-- AJAX favourite toggle (endpoint returns JSON; no navigation). --}}
                                <button type="button" x-data="{
                                            fav: {{ $heroFav ? 'true' : 'false' }},
                                            busy: false,
                                            toggle() {
                                                if (this.busy) return;
                                                const was = this.fav;
                                                this.fav = ! was;
                                                this.busy = true;
                                                const token = document.querySelector('meta[name=csrf-token]')?.content;
                                                fetch(was ? '{{ route('kegemaran.padam', $hero) }}' : '{{ route('kegemaran.simpan', $hero) }}', {
                                                    method: was ? 'DELETE' : 'POST',
                                                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                                                }).then(r => { if (! r.ok) throw new Error('failed'); })
                                                  .catch(() => { this.fav = was; })
                                                  .finally(() => { this.busy = false; });
                                            }
                                        }"
                                        @click="toggle()"
                                        :aria-pressed="fav ? 'true' : 'false'"
                                        class="wl-btn-secondary"
                                        style="min-height:46px;cursor:pointer;border-radius:12px;border:1.5px solid var(--wl-line-3);background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:700;font-size:14.5px;padding:0 18px;display:inline-flex;align-items:center;gap:6px">
                                    <span x-text="fav ? '♥' : '♡'" :style="fav ? 'color:#EB5E5A' : ''"></span>
                                    <span>{{ __('Favourite') }}</span>
                                </button>
                            </div>
                        </div>
                        <a href="{{ route('video.show', $hero) }}" style="background:linear-gradient(135deg,#C4DCF2,#A5C9EA);display:grid;place-items:center;position:relative;min-height:200px;text-decoration:none">
                            @if ($hero->thumbnailUrl())
                                <img src="{{ $hero->thumbnailUrl() }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">
                            @endif
                            <span style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.92);display:grid;place-items:center;color:#4276AE;font-size:17px;z-index:1">▶</span>
                            @if ($hero->durationLabel())
                                <span style="position:absolute;right:12px;bottom:10px;background:rgba(66,118,174,.85);color:#fff;font-size:11px;font-weight:700;border-radius:999px;padding:3px 9px">{{ $hero->durationLabel() }}</span>
                            @endif
                        </a>
                    </div>
                </div>
            @endif

            {{-- ── CONTINUE WATCHING (horizontal rail — swipe / arrow-scroll) ── --}}
            @if ($continue->isNotEmpty())
                <x-rail :title="__('Sambung menonton')" :seeAll="route('sambung.index')">
                    @foreach ($continue as $lesson)
                        <div style="flex:0 0 280px;max-width:85%">
                            <x-vid-card :lesson="$lesson" :thumbHeight="110" showMeta showProgress />
                        </div>
                    @endforeach
                </x-rail>
            @endif

            {{-- ── PALING POPULAR (falls back to newest) ── --}}
            @if ($trending->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div style="display:flex;justify-content:space-between;align-items:baseline">
                        <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:21px;font-weight:800;color:var(--wl-ink)">{{ $trendingFallback ? __('Baru ditambah') : __('Paling popular') }}</h2>
                        <a href="{{ route('subjek.index') }}" style="font-size:13.5px;font-weight:700">{{ __('Lihat semua') }}</a>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
                        @foreach ($trending->take(4) as $lesson)
                            <x-vid-card :lesson="$lesson" :thumbHeight="104" :showViews="! $trendingFallback" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── BARU DITAMBAH (skipped when trending already fell back) ── --}}
            @if ($newest->isNotEmpty() && ! $trendingFallback)
                <div style="display:flex;flex-direction:column;gap:16px">
                    <div style="display:flex;justify-content:space-between;align-items:baseline">
                        <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:21px;font-weight:800;color:var(--wl-ink)">{{ __('Baru ditambah') }}</h2>
                        <a href="{{ route('subjek.index') }}" style="font-size:13.5px;font-weight:700">{{ __('Lihat semua') }}</a>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
                        @foreach ($newest->take(4) as $lesson)
                            <x-vid-card :lesson="$lesson" :thumbHeight="104" />
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── ANDA MUNGKIN SUKA ── --}}
            @if ($suggested->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:16px">
                    <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:21px;font-weight:800;color:var(--wl-ink)">{{ __('Anda mungkin suka') }}</h2>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px">
                        @foreach ($suggested->take(3) as $lesson)
                            <x-vid-card :lesson="$lesson" :thumbHeight="116" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @if (! $hero && $continue->isEmpty() && $trending->isEmpty() && $newest->isEmpty() && $suggested->isEmpty())
            <div style="background:var(--wl-surface);border:1px dashed var(--wl-line-3);border-radius:18px;padding:44px;text-align:center;color:var(--wl-muted);font-weight:600">
                {{ __('Belum ada video untuk :grade. Sila semak semula kemudian.', ['grade' => $grade->name]) }}
            </div>
        @endif
    @endif
</x-student-layout>
