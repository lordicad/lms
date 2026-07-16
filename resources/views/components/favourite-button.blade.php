@props(['lesson', 'favourited' => false, 'labelled' => false, 'reveal' => false])

{{--
    Heart toggle. Optimistic + reverts on failure, aria-pressed. On a card it is a small white
    glass pill (28px) with a 44px invisible hit area, hidden until hover/focus but always shown
    when favourited or on touch. On the hero/watch page it is a labelled button.

    The pill sits on a photo, so it is white in both themes; its icon colours are therefore fixed
    (a dark slate idle, teal-700 when active) rather than theme tokens, which would invert to
    white on the white pill in dark mode. The labelled variant sits on a normal surface and uses
    theme tokens (ink idle, --c-brand when active).
--}}

@php
    $shape = $labelled
        ? 'min-h-[44px] gap-2 rounded-control border border-line-strong bg-surface-2 px-4 text-ink hover:bg-surface-3'
        : 'h-7 rounded-full glass-pill-light text-[#334155] hover:text-[#0f766e]';
    $activeFill = $labelled ? 'rgb(var(--c-brand))' : '#0f766e';
@endphp

<button type="button"
        x-data="favouriteButton({
            favourited: {{ $favourited ? 'true' : 'false' }},
            addUrl: '{{ route('kegemaran.simpan', $lesson) }}',
            removeUrl: '{{ route('kegemaran.padam', $lesson) }}',
        })"
        @click.prevent.stop="toggle()"
        :aria-pressed="favourited ? 'true' : 'false'"
        :aria-label="favourited ? @js(__('Buang dari kegemaran')) : @js(__('Simpan ke kegemaran'))"
        @if ($reveal) data-on="{{ $favourited ? 'true' : 'false' }}" :data-on="favourited ? 'true' : 'false'" @endif
        {{ $attributes->merge(['class' => ($reveal ? 'fav-reveal ' : '').'relative inline-flex items-center justify-center transition-colors duration-150 ease-smooth '.$shape]) }}>
    @unless ($labelled)
        {{-- Expand the tiny pill to a 44px hit target. --}}
        <span class="absolute -inset-2" aria-hidden="true"></span>
    @endunless

    <svg viewBox="0 0 24 24" class="relative h-4 w-4" :class="pulsing && 'heart-pop'"
         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
         :fill="favourited ? 'currentColor' : 'none'" :style="favourited ? 'color: {{ $activeFill }}' : ''" aria-hidden="true">
        <path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572" />
    </svg>

    @if ($labelled)
        <span class="text-[15px] font-semibold" x-text="favourited ? @js(__('Disimpan')) : @js(__('Simpan ke Kegemaran'))"></span>
    @endif
</button>

@once
    @push('scripts')
        <script>
            function favouriteButton({ favourited, addUrl, removeUrl }) {
                return {
                    favourited,
                    busy: false,
                    pulsing: false,

                    toggle() {
                        if (this.busy) return;

                        const wasFavourited = this.favourited;
                        this.favourited = ! wasFavourited;   // optimistic
                        this.busy = true;

                        if (this.favourited) {
                            this.pulsing = true;
                            setTimeout(() => { this.pulsing = false; }, 220);
                        }

                        const token = document.querySelector('meta[name=csrf-token]')?.content;

                        fetch(wasFavourited ? removeUrl : addUrl, {
                            method: wasFavourited ? 'DELETE' : 'POST',
                            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                        })
                            .then(response => { if (! response.ok) throw new Error('failed'); })
                            .catch(() => { this.favourited = wasFavourited; })
                            .finally(() => { this.busy = false; });
                    },
                };
            }
        </script>
    @endpush
@endonce
