<x-student-layout :title="__('Belajar')">
    @if (! $grade)
        <div class="mx-auto max-w-lg">
            <x-empty icon="book" :title="__('Tahun anda belum ditetapkan')"
                     :text="__('Sila kemas kini profil anda dan pilih Tahun supaya kami boleh tunjukkan kandungan yang betul.')">
                <a href="{{ route('profile.edit') }}" class="btn-primary">{{ __('Kemas Kini Profil') }}</a>
            </x-empty>
        </div>
    @else
        {{-- Greeting + standing. --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-ink">{{ __('Hai, :name!', ['name' => Str::before($user->name, ' ')]) }}</h1>
                <p class="text-ink-2 font-reading">{{ $grade->name }}</p>
            </div>

            <a href="{{ route('ranking.index') }}"
               class="flex items-center gap-4 rounded-card border border-line bg-surface px-4 py-2.5 transition duration-150 ease-smooth hover:border-line-strong">
                <span class="flex items-center gap-2 text-brand">
                    <x-icon name="trophy" class="h-5 w-5" />
                    <span class="text-xl font-bold tabular-nums">{{ $points }}</span>
                    <span class="micro text-ink-2">{{ __('mata') }}</span>
                </span>
                <span class="border-l border-line pl-4 text-sm font-semibold tabular-nums text-ink-2">
                    {{ $rank ? '#'.$rank : '—' }}
                </span>
            </a>
        </div>

        {{--
            1. Hero / Sorotan — editorial split. Text never sits on the photo (the light-mode
            contrast trap): content lives on a subject-wash panel, the cover bleeds in from the
            right and feathers into the panel (desktop) or sits on top at 16:9 (mobile).
        --}}
        @if ($hero)
            @php($hs = $hero->chapter->subject)
            <section class="relative" style="--sc: {{ $hs->rgb }}">
                {{-- The entire "cinematic" budget in light: one 4% accent glow behind the card. --}}
                <div class="pointer-events-none absolute -inset-x-6 -bottom-8 -top-8 -z-10 rounded-[44px] bg-brand/[0.04] blur-3xl"></div>

                <div class="grid overflow-hidden rounded-hero bg-surface shadow-hero md:min-h-[380px] md:grid-cols-[52%_48%]">
                    {{-- LEFT: content on the subject-wash panel (image sits on top on mobile). --}}
                    <div class="order-2 flex flex-col justify-center gap-3 bg-subject-wash p-6 sm:p-10 md:order-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="chip border border-line bg-surface text-subject-ink"><x-subject-icon :subject="$hs" class="h-4 w-4" /> {{ $hs->displayName() }}</span>
                            <span class="chip border border-line bg-surface text-ink-2">Bab {{ $hero->chapter->number }}</span>
                            @unless ($heroResuming)
                                <span class="chip bg-brand micro text-on-brand">{{ __('Trending') }}</span>
                            @endunless
                        </div>

                        <h2 class="line-clamp-2 text-[28px] font-bold leading-[1.08] text-ink sm:text-[32px]">{{ $hero->title }}</h2>

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

                    {{-- RIGHT: the cover, bleeding to the card edges. No text sits on it. --}}
                    <div class="relative order-1 aspect-video md:order-2 md:aspect-auto">
                        @if ($hero->thumbnailUrl())
                            <img src="{{ $hero->thumbnailUrl() }}" alt="" class="hero-feather absolute inset-0 h-full w-full object-cover">
                        @else
                            <div class="absolute inset-0"
                                 style="background-image: linear-gradient(120deg, color-mix(in oklab, rgb(var(--sc)) 42%, rgb(var(--c-surface))), color-mix(in oklab, rgb(var(--sc)) 14%, rgb(var(--c-surface))));"></div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <div class="mt-12 space-y-12">
            {{-- 2. Kategori (Subjek) --}}
            @if ($subjects->isNotEmpty())
                <x-rail :title="__('Terokai Subjek')" :seeAll="route('subjek.index')">
                    @foreach ($subjects as $subject)
                        <div class="w-[170px] shrink-0 sm:w-[190px]">
                            <x-subject-tile :subject="$subject" :grade="$grade" />
                        </div>
                    @endforeach
                </x-rail>
            @endif

            {{-- 3. Sambung Menonton — hidden entirely when empty. --}}
            @if ($continue->isNotEmpty())
                <x-rail :title="__('Sambung Menonton')" :seeAll="route('sambung.index')">
                    @foreach ($continue as $lesson)
                        <x-lesson-card :lesson="$lesson" />
                    @endforeach
                </x-rail>
            @endif

            {{-- 4. Trending / Paling Popular (falls back to newest, relabelled). --}}
            @if ($trending->isNotEmpty())
                <x-rail :title="$trendingFallback ? __('Baru Ditambah') : __('Paling Popular')">
                    @foreach ($trending as $lesson)
                        <x-lesson-card :lesson="$lesson" />
                    @endforeach
                </x-rail>
            @endif

            {{-- 5. Kegemaran Saya --}}
            @if ($favourites->isNotEmpty())
                <x-rail :title="__('Kegemaran Saya')" :seeAll="route('kegemaran.index')">
                    @foreach ($favourites as $lesson)
                        <x-lesson-card :lesson="$lesson" />
                    @endforeach
                </x-rail>
            @endif

            {{-- 6. Baru Ditambah — skipped when Trending already fell back to newest. --}}
            @if ($newest->isNotEmpty() && ! $trendingFallback)
                <x-rail :title="__('Baru Ditambah')">
                    @foreach ($newest as $lesson)
                        <x-lesson-card :lesson="$lesson" />
                    @endforeach
                </x-rail>
            @endif

            {{-- 7. Mungkin Anda Suka --}}
            @if ($suggested->isNotEmpty())
                <x-rail :title="__('Mungkin Anda Suka')">
                    @foreach ($suggested as $lesson)
                        <x-lesson-card :lesson="$lesson" />
                    @endforeach
                </x-rail>
            @endif
        </div>

        @if (! $hero && $subjects->isEmpty())
            <x-empty icon="inbox" :title="__('Belum ada video')"
                     :text="__('Belum ada video untuk :grade. Sila semak semula kemudian.', ['grade' => $grade->name])" />
        @endif
    @endif
</x-student-layout>
