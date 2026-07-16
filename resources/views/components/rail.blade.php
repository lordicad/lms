@props(['title' => null, 'seeAll' => null, 'seeAllLabel' => null])

{{--
    Horizontal, scroll-snapping rail. No carousel library: native swipe, a focusable
    arrow-scrollable track, real focusable cards, and prev/next buttons. An edge fade mask hides
    the "cut off mid-card" look — and only fades the side that can actually still scroll.
--}}

<section x-data="rail()" {{ $attributes }}>
    @if ($title)
        <div class="mb-5 flex items-end justify-between gap-3">
            <h2 class="text-[22px] font-bold tracking-[-0.02em] text-ink">{{ $title }}</h2>

            <div class="flex items-center gap-3">
                @if ($seeAll)
                    <a href="{{ $seeAll }}" class="link-muted">
                        {{ $seeAllLabel ?? __('Lihat semua') }}
                        <x-icon name="chevron-right" class="h-4 w-4" />
                    </a>
                @endif

                <div class="hidden items-center gap-1.5 sm:flex">
                    <button type="button" class="rail-btn" @click="nudge(-1)" :disabled="atStart"
                            aria-label="{{ __('Skrol ke kiri') }}">
                        <x-icon name="chevron-left" class="h-5 w-5" />
                    </button>
                    <button type="button" class="rail-btn" @click="nudge(1)" :disabled="atEnd"
                            aria-label="{{ __('Skrol ke kanan') }}">
                        <x-icon name="chevron-right" class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    @endif

    <div x-ref="track" @scroll.debounce.100ms="update()" x-init="$nextTick(() => update())"
         :style="maskStyle"
         tabindex="0" role="group" aria-label="{{ $title ?? __('Senarai video') }}"
         class="rail-track flex gap-4 overflow-x-auto scroll-smooth pb-1">
        {{ $slot }}
    </div>
</section>

@once
    @push('scripts')
        <script>
            function rail() {
                return {
                    atStart: true,
                    atEnd: false,
                    maskStyle: '',

                    update() {
                        const el = this.$refs.track;
                        this.atStart = el.scrollLeft <= 2;
                        this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 2;

                        const left = this.atStart ? 'transparent 0, #000 0' : 'transparent 0, #000 24px';
                        const right = this.atEnd ? '#000 100%' : '#000 calc(100% - 48px), transparent 100%';
                        const mask = `linear-gradient(90deg, ${left}, ${right})`;
                        this.maskStyle = `-webkit-mask-image:${mask};mask-image:${mask}`;
                    },

                    nudge(direction) {
                        const el = this.$refs.track;
                        el.scrollBy({ left: direction * el.clientWidth * 0.85, behavior: 'smooth' });
                    },
                };
            }
        </script>
    @endpush
@endonce
