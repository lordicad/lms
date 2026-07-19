<x-student-layout :title="__('Belajar')">
    @if (! $grade)
        <div class="mx-auto max-w-lg">
            <x-empty icon="book" :title="__('Tahun anda belum ditetapkan')"
                     :text="__('Sila kemas kini profil anda dan pilih Tahun supaya kami boleh tunjukkan kandungan yang betul.')">
                <a href="{{ route('profile.edit') }}" class="btn-primary">{{ __('Kemas Kini Profil') }}</a>
            </x-empty>
        </div>
    @else
        <div class="space-y-8">
            {{--
                Trending / resume hero. The cover bleeds in from the right and feathers into a
                subject-wash panel — no text sits on the photo. A play affordance + duration chip
                sit on the cover; the actions live on the panel.
            --}}
            @if ($hero)
                @php($hs = $hero->chapter->subject)
                <section class="relative" style="--sc: {{ $hs->rgb }}">
                    <div class="grid overflow-hidden rounded-hero bg-surface shadow-hero md:min-h-[320px] md:grid-cols-[minmax(0,1fr)_42%]">
                        {{-- LEFT: content on the subject-wash panel --}}
                        <div class="order-2 flex flex-col justify-center gap-3.5 bg-subject-wash p-7 sm:p-12 md:order-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="chip border border-line bg-surface text-subject-ink"><x-subject-icon :subject="$hs" class="h-4 w-4" /> {{ $hs->displayName() }}</span>
                                <span class="chip border border-line bg-surface text-ink-2">Bab {{ $hero->chapter->number }}</span>
                                @unless ($heroResuming)
                                    <span class="chip bg-brand micro text-on-brand">{{ __('Trending') }}</span>
                                @endunless
                            </div>

                            <h2 class="line-clamp-2 text-[26px] font-extrabold leading-[1.1] tracking-[-0.01em] text-ink sm:text-[30px]">{{ $hero->title }}</h2>

                            @if ($hero->description)
                                <p class="line-clamp-2 max-w-[46ch] text-[15px] text-ink-2 font-reading">{{ $hero->description }}</p>
                            @endif

                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <a href="{{ route('video.show', $hero) }}" class="btn-primary">
                                    <x-icon name="play" class="h-5 w-5" />
                                    {{ $heroResuming ? __('Sambung Menonton') : __('Tonton') }}
                                </a>

                                <x-favourite-button :lesson="$hero" :favourited="$hero->isFavouritedBy($user)" labelled />
                            </div>
                        </div>

                        {{-- RIGHT: the cover, bleeding to the card edges --}}
                        <a href="{{ route('video.show', $hero) }}"
                           class="group relative order-1 block aspect-video md:order-2 md:aspect-auto"
                           aria-label="{{ $heroResuming ? __('Sambung Menonton') : __('Tonton') }}: {{ $hero->title }}">
                            @if ($hero->thumbnailUrl())
                                <img src="{{ $hero->thumbnailUrl() }}" alt="" class="hero-feather absolute inset-0 h-full w-full object-cover">
                            @else
                                <div class="absolute inset-0"
                                     style="background-image: linear-gradient(120deg, color-mix(in oklab, rgb(var(--sc)) 42%, rgb(var(--c-surface))), color-mix(in oklab, rgb(var(--sc)) 14%, rgb(var(--c-surface))));"></div>
                            @endif

                            <span class="glass-pill-light absolute left-1/2 top-1/2 grid h-14 w-14 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full text-subject-ink transition-transform duration-150 ease-smooth group-hover:scale-105">
                                <x-icon name="play" class="h-6 w-6" />
                            </span>

                            @if ($hero->durationLabel())
                                <span class="glass-pill absolute bottom-3 right-3 rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums text-white">{{ $hero->durationLabel() }}</span>
                            @endif
                        </a>
                    </div>
                </section>
            @endif

            {{-- Sambung Menonton — hidden entirely when empty. --}}
            @if ($continue->isNotEmpty())
                <x-home-section :title="__('Sambung Menonton')" :seeAll="route('sambung.index')">
                    @foreach ($continue->take(4) as $lesson)
                        <x-lesson-card :lesson="$lesson" grid />
                    @endforeach
                </x-home-section>
            @endif

            {{-- Paling Popular (falls back to newest, relabelled). --}}
            @if ($trending->isNotEmpty())
                <x-home-section :title="$trendingFallback ? __('Baru Ditambah') : __('Paling Popular')">
                    @foreach ($trending->take(4) as $lesson)
                        <x-lesson-card :lesson="$lesson" grid />
                    @endforeach
                </x-home-section>
            @endif

            {{-- Kegemaran Saya --}}
            @if ($favourites->isNotEmpty())
                <x-home-section :title="__('Kegemaran Saya')" :seeAll="route('kegemaran.index')">
                    @foreach ($favourites->take(4) as $lesson)
                        <x-lesson-card :lesson="$lesson" grid />
                    @endforeach
                </x-home-section>
            @endif

            {{-- Baru Ditambah — skipped when Trending already fell back to newest. --}}
            @if ($newest->isNotEmpty() && ! $trendingFallback)
                <x-home-section :title="__('Baru Ditambah')">
                    @foreach ($newest->take(4) as $lesson)
                        <x-lesson-card :lesson="$lesson" grid />
                    @endforeach
                </x-home-section>
            @endif

            {{-- Mungkin Anda Suka --}}
            @if ($suggested->isNotEmpty())
                <x-home-section :title="__('Mungkin Anda Suka')" :cols="3">
                    @foreach ($suggested->take(3) as $lesson)
                        <x-lesson-card :lesson="$lesson" grid />
                    @endforeach
                </x-home-section>
            @endif
        </div>

        @if (! $hero && $continue->isEmpty() && $trending->isEmpty() && $favourites->isEmpty() && $newest->isEmpty() && $suggested->isEmpty())
            <x-empty icon="inbox" :title="__('Belum ada video')"
                     :text="__('Belum ada video untuk :grade. Sila semak semula kemudian.', ['grade' => $grade->name])" />
        @endif
    @endif
</x-student-layout>
