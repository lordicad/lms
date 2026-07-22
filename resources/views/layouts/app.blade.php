<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @class(['theme-dark' => ($theme ?? 'light') === 'dark'])>
<head>
    <meta charset="utf-8">
    {{-- Tab icon. One 196px PNG serves the browser tab and the phone home screen alike. --}}
    <link rel="icon" type="image/png" href="{{ asset('images/welearn.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/welearn.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' | '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Alias the WeLearn student (--wl-*) tokens to the design-system tokens, so pages shared
         between the student shell and this shell (subjek, bab, tonton, profil) theme here too. --}}
    <style>
        :root {
            --wl-page: rgb(var(--c-bg)); --wl-surface: rgb(var(--c-surface)); --wl-surface-2: rgb(var(--c-surface-2));
            --wl-input: rgb(var(--c-surface-2)); --wl-chip: rgb(var(--c-surface-2));
            --wl-ink: rgb(var(--c-ink)); --wl-body: rgb(var(--c-ink)); --wl-muted: rgb(var(--c-ink-2)); --wl-muted-2: rgb(var(--c-ink-2));
            --wl-line: var(--border-subtle); --wl-line-2: var(--border-subtle); --wl-line-3: var(--border-strong);
        }
    </style>
</head>

<body class="min-h-screen bg-bg font-sans {{ auth()->user()?->isStudent() ? 'type-student' : '' }}">
    <a href="#kandungan" class="skip-link">{{ __('Terus ke kandungan') }}</a>

    @php($user = auth()->user())

    <header class="sticky top-0 z-30 border-b border-line bg-surface">
        <nav class="mx-auto flex h-[72px] max-w-7xl items-center gap-4 px-4 sm:px-6" aria-label="{{ __('Navigasi utama') }}">
            <a href="{{ $user->homeRoute() }}" class="flex shrink-0 items-center gap-2">
                <span class="rounded-control bg-brand px-2 py-1 text-sm font-extrabold text-on-brand">LMS</span>
                <span class="text-lg font-extrabold text-ink">MOE</span>
            </a>

            {{-- Desktop nav stays on one line: the label set is short on purpose. --}}
            <div class="relative ml-2 hidden items-center gap-1 lg:flex"
                 x-data="navPill"
                 @mouseover="follow($event)" @focusin="follow($event)" @mouseleave="settle()">
                {{-- Shared sliding highlight: glides between tabs and rests on the active one. --}}
                <span aria-hidden="true" x-cloak x-show="show"
                      :style="pillStyle()"
                      class="pointer-events-none absolute rounded-control bg-brand-soft transition-all duration-300 ease-out"></span>

                @if ($user->isTeacher())
                    <x-nav-link :href="route('cikgu.dashboard')" :active="request()->routeIs('cikgu.dashboard')" pill>{{ __('Papan Pemuka') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.video.index')" :active="request()->routeIs('cikgu.video.*')" pill>{{ __('Video') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.bahan.index')" :active="request()->routeIs('cikgu.bahan.*')" pill>{{ __('Bahan') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.kuiz.index')" :active="request()->routeIs('cikgu.kuiz.*')" pill>{{ __('Kuiz') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.bab.index')" :active="request()->routeIs('cikgu.bab.*')" pill>{{ __('Bab') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.ranking')" :active="request()->routeIs('cikgu.ranking')" pill>{{ __('Ranking') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.bakat')" :active="request()->routeIs('cikgu.bakat')" pill>{{ __('Bakat') }}</x-nav-link>
                @elseif ($user->isAdmin())
                    {{-- The dropdown draws its own highlight on its button: the sliding pill only
                         tracks <a> tabs, and a menu that opens on hover needs a steady background. --}}
                    <x-nav-dropdown :label="__('Kandungan')" :active="request()->routeIs('admin.kandungan.*')">
                        <x-dropdown-link :href="route('admin.kandungan.video')">{{ __('Video') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.kandungan.bahan')">{{ __('Bahan') }}</x-dropdown-link>
                        <x-dropdown-link :href="route('admin.kandungan.kuiz')">{{ __('Kuiz') }}</x-dropdown-link>
                    </x-nav-dropdown>

                    <x-nav-link :href="route('admin.bakat')" :active="request()->routeIs('admin.bakat*')" pill>{{ __('Skor Bakat') }}</x-nav-link>
                    <x-nav-link :href="route('admin.murid')" :active="request()->routeIs('admin.murid')" pill>{{ __('Murid') }}</x-nav-link>
                @else
                    <x-nav-link :href="route('belajar.index')" :active="request()->routeIs('belajar.*')" pill>{{ __('Belajar') }}</x-nav-link>
                    <x-nav-link :href="route('ranking.index')" :active="request()->routeIs('ranking.index')" pill>{{ __('Ranking') }}</x-nav-link>
                @endif
            </div>

            <div class="ml-auto flex items-center gap-2">
                <x-lang-toggle class="hidden sm:inline-flex" />
                <x-theme-toggle class="hidden sm:inline-flex" />

                @if ($user->isTeacher())
                    <a href="{{ route('cikgu.video.create') }}" class="btn-primary btn-sm hidden sm:inline-flex">
                        <x-icon name="plus" class="h-4 w-4" />
                        {{ __('Video Baharu') }}
                    </a>
                @endif

                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button type="button"
                                class="flex items-center gap-2 rounded-control p-1 pr-2 transition-colors hover:bg-surface-2">
                            <x-avatar :user="$user" size="sm" />
                            <span class="hidden max-w-[10rem] truncate text-sm font-bold text-ink sm:block">{{ $user->name }}</span>
                            <x-icon name="chevron-down" class="h-4 w-4 text-ink-2" />
                            <span class="sr-only">{{ __('Buka menu akaun') }}</span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="border-b border-line px-4 py-3">
                            <p class="truncate font-bold text-ink">{{ $user->name }}</p>
                            <p class="truncate text-sm text-ink-2">
                                @if ($user->isAdmin()) {{ __('Admin MOE') }}
                                @elseif ($user->isTeacher()) {{ __('Guru') }}
                                @else {{ $user->grade?->name ?? __('Murid') }}
                                @endif
                            </p>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profil Saya') }}</x-dropdown-link>

                        <x-logout-confirm class="block w-full px-4 py-2.5 text-left text-sm font-semibold text-ink transition-colors hover:bg-surface-2">
                            {{ __('Log Keluar') }}
                        </x-logout-confirm>
                    </x-slot>
                </x-dropdown>

                <button type="button" class="btn-ghost btn-sm lg:hidden" x-data
                        @click="$dispatch('toggle-mobile-nav')"
                        aria-controls="menu-mudah-alih">
                    <x-icon name="menu" class="h-5 w-5" />
                    <span class="sr-only">{{ __('Menu') }}</span>
                </button>
            </div>
        </nav>

        <div id="menu-mudah-alih" x-data="{ open: false }" @toggle-mobile-nav.window="open = !open"
             x-show="open" x-cloak x-transition.opacity class="border-t border-line lg:hidden">
            <div class="mx-auto max-w-7xl space-y-1 px-4 py-3">
                @if ($user->isTeacher())
                    <x-nav-link :href="route('cikgu.dashboard')" :active="request()->routeIs('cikgu.dashboard')" block>{{ __('Papan Pemuka') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.video.index')" :active="request()->routeIs('cikgu.video.*')" block>{{ __('Video') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.bahan.index')" :active="request()->routeIs('cikgu.bahan.*')" block>{{ __('Bahan') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.kuiz.index')" :active="request()->routeIs('cikgu.kuiz.*')" block>{{ __('Kuiz') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.bab.index')" :active="request()->routeIs('cikgu.bab.*')" block>{{ __('Bab') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.ranking')" :active="request()->routeIs('cikgu.ranking')" block>{{ __('Ranking') }}</x-nav-link>
                    <x-nav-link :href="route('cikgu.bakat')" :active="request()->routeIs('cikgu.bakat')" block>{{ __('Bakat') }}</x-nav-link>
                @elseif ($user->isAdmin())
                    {{-- Flattened on mobile: a hover menu has no meaning on touch. --}}
                    <x-nav-link :href="route('admin.kandungan.video')" :active="request()->routeIs('admin.kandungan.video')" block>{{ __('Kandungan') }}: {{ __('Video') }}</x-nav-link>
                    <x-nav-link :href="route('admin.kandungan.bahan')" :active="request()->routeIs('admin.kandungan.bahan')" block>{{ __('Kandungan') }}: {{ __('Bahan') }}</x-nav-link>
                    <x-nav-link :href="route('admin.kandungan.kuiz')" :active="request()->routeIs('admin.kandungan.kuiz')" block>{{ __('Kandungan') }}: {{ __('Kuiz') }}</x-nav-link>
                    <x-nav-link :href="route('admin.bakat')" :active="request()->routeIs('admin.bakat*')" block>{{ __('Skor Bakat') }}</x-nav-link>
                    <x-nav-link :href="route('admin.murid')" :active="request()->routeIs('admin.murid')" block>{{ __('Murid') }}</x-nav-link>
                @else
                    <x-nav-link :href="route('belajar.index')" :active="request()->routeIs('belajar.*')" block>{{ __('Belajar') }}</x-nav-link>
                    <x-nav-link :href="route('ranking.index')" :active="request()->routeIs('ranking.index')" block>{{ __('Ranking') }}</x-nav-link>
                @endif

                <div class="flex items-stretch gap-2 border-t border-line pt-3">
                    <x-lang-toggle block class="flex-1" />
                    <x-theme-toggle />
                </div>
            </div>
        </div>
    </header>

    <main id="kandungan" class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <x-flash />

        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-7xl px-4 pb-10 pt-4 text-sm text-ink-2 sm:px-6">
        <p>{{ config('app.name') }}. {{ __('Platform pembelajaran untuk sekolah rendah.') }}</p>
    </footer>

    {{-- Page-specific behaviour (quiz runner, quiz builder). Runs before Alpine starts. --}}
    @stack('scripts')
</body>
</html>
