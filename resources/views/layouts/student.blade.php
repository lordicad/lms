@php
    $user = auth()->user();
    $grades = \App\Models\Grade::orderBy('level')->get();
    $activeGrade = \App\Support\ActiveGrade::for($user);

    // Short labels so they fit the 96px icon rail; full labels stay as the accessible name / tooltip.
    $nav = [
        ['route' => 'belajar.index',   'active' => request()->routeIs('belajar.index'),                                'icon' => 'home',   'label' => __('Utama'),            'short' => __('Utama')],
        ['route' => 'subjek.index',    'active' => request()->routeIs('subjek.index', 'belajar.subjek', 'bab.show'),   'icon' => 'grid',   'label' => __('Subjek'),           'short' => __('Subjek')],
        ['route' => 'kegemaran.index', 'active' => request()->routeIs('kegemaran.index'),                              'icon' => 'heart',  'label' => __('Kegemaran'),        'short' => __('Kegemaran')],
        ['route' => 'sambung.index',   'active' => request()->routeIs('sambung.index'),                                'icon' => 'play',   'label' => __('Sambung Menonton'), 'short' => __('Sambung')],
        ['route' => 'simpanan.index',  'active' => request()->routeIs('simpanan.index'),                               'icon' => 'device', 'label' => __('Simpanan Offline'), 'short' => __('Offline')],
        ['route' => 'ranking.index',   'active' => request()->routeIs('ranking.index'),                                'icon' => 'trophy', 'label' => __('Papan Ranking'),    'short' => __('Ranking')],
        ['route' => 'kuiz-saya.index', 'active' => request()->routeIs('kuiz-saya.index'),                              'icon' => 'quiz',   'label' => __('Kuiz'),             'short' => __('Kuiz')],
    ];

    // Bottom tab bar shows the five most-used; the rest live in the "Lagi" sheet.
    $tabRoutes = ['belajar.index', 'subjek.index', 'kegemaran.index', 'kuiz-saya.index', 'ranking.index'];
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @class(['theme-dark' => ($theme ?? 'light') === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title.' | '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-bg type-student" x-data="studentShell()">
    <a href="#kandungan" class="skip-link">{{ __('Terus ke kandungan') }}</a>

    {{-- ===================== DESKTOP ICON SIDEBAR (96px) ===================== --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden w-24 flex-col items-center gap-1 border-r border-line bg-surface px-2 py-3 lg:flex"
           aria-label="{{ __('Navigasi murid') }}">
        <a href="{{ route('belajar.index') }}" title="{{ config('app.name') }}"
           class="grid h-[46px] w-[46px] shrink-0 place-items-center rounded-[14px] bg-brand text-[15px] font-extrabold tracking-tight text-on-brand">
            WL
        </a>

        <div class="h-1.5"></div>

        <nav class="flex w-full flex-col items-center gap-1" aria-label="{{ __('Menu utama') }}">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                   @if ($item['active']) aria-current="page" @endif
                   @class([
                       'flex w-[74px] flex-col items-center justify-center gap-1 rounded-[16px] px-1 py-2 text-center transition-colors duration-150 ease-smooth',
                       'bg-brand-soft text-brand' => $item['active'],
                       'text-ink-2 hover:bg-surface-2 hover:text-ink' => ! $item['active'],
                   ])>
                    <x-icon :name="$item['icon']" class="h-[22px] w-[22px]" />
                    <span class="text-[11.5px] font-bold leading-tight">{{ $item['short'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="mt-auto"></div>

        <a href="{{ route('profile.edit') }}" title="{{ __('Profil Saya') }}"
           class="grid h-11 w-11 shrink-0 place-items-center rounded-full transition-transform duration-150 ease-smooth hover:scale-105">
            <x-avatar :user="$user" size="md" />
        </a>

        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" title="{{ __('Log Keluar') }}" aria-label="{{ __('Log Keluar') }}"
                    class="grid h-11 w-11 place-items-center rounded-[14px] text-danger transition-colors duration-150 ease-smooth hover:bg-danger-soft">
                <x-icon name="logout" class="h-5 w-5" />
            </button>
        </form>
    </aside>

    {{-- ===================== MAIN COLUMN ===================== --}}
    <div class="flex min-h-screen flex-col lg:pl-24">
        <header class="sticky top-0 z-20 border-b border-line bg-bg/85 backdrop-blur">
            <div class="mx-auto flex w-full max-w-content flex-wrap items-center gap-2.5 px-4 py-3 sm:gap-3 sm:px-6 lg:px-8">
                <a href="{{ route('belajar.index') }}" class="flex shrink-0 items-center gap-2 lg:hidden">
                    <span class="grid h-9 w-9 place-items-center rounded-[12px] bg-brand text-sm font-extrabold text-on-brand">WL</span>
                </a>

                {{-- Search: a rounded pill, matching the WeLearn header --}}
                <form method="GET" action="{{ route('cari.index') }}"
                      class="relative order-3 w-full min-w-0 flex-[1_1_100%] sm:order-none sm:flex-[0_1_380px] sm:mr-auto" role="search">
                    <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-2" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari video...') }}"
                           class="min-h-[48px] w-full rounded-full border border-line bg-surface pl-11 pr-4 text-[14.5px] text-ink placeholder:text-ink-2 focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/25"
                           aria-label="{{ __('Cari video') }}">
                </form>

                {{-- Tahun switcher: real app need (revision across years), kept compact --}}
                <label class="hidden shrink-0 sm:block">
                    <span class="sr-only">{{ __('Tukar Tahun') }}</span>
                    <select class="min-h-[48px] rounded-full border border-line bg-surface px-4 text-[13.5px] font-bold text-ink focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/25"
                            onchange="if (this.value) window.location.href = '{{ url('tahun') }}/' + this.value">
                        @foreach ($grades as $g)
                            <option value="{{ $g->level }}" @selected($activeGrade?->level === $g->level)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </label>

                <x-lang-toggle class="shrink-0 rounded-full" />
                <x-theme-toggle class="!rounded-full" />

                {{-- Notifications: honest — a real popover that says there is nothing new --}}
                <div class="relative shrink-0" @keydown.escape.window="notifOpen = false">
                    <button type="button" @click="notifOpen = ! notifOpen"
                            class="grid h-12 w-12 place-items-center rounded-full border border-line bg-surface text-ink-2 transition-colors duration-150 ease-smooth hover:bg-surface-2 hover:text-ink"
                            :aria-expanded="notifOpen" aria-label="{{ __('Notifikasi') }}" title="{{ __('Notifikasi') }}">
                        <x-icon name="bell" class="h-5 w-5" />
                    </button>
                    <div x-show="notifOpen" x-cloak @click.outside="notifOpen = false"
                         x-transition.opacity.duration.150ms
                         class="absolute right-0 top-14 z-30 w-64 rounded-panel border border-line bg-surface p-5 text-center shadow-lift"
                         role="dialog" aria-label="{{ __('Notifikasi') }}">
                        <p class="text-3xl">🔔</p>
                        <p class="mt-2 text-sm font-bold text-ink">{{ __('Tiada notifikasi baharu') }}</p>
                        <p class="mt-1 text-[13px] text-ink-2">{{ __('Kami akan beritahu anda apabila ada sesuatu yang baharu.') }}</p>
                    </div>
                </div>

                <button type="button" class="shrink-0 lg:hidden" @click="moreOpen = true" aria-label="{{ __('Menu & akaun') }}">
                    <x-avatar :user="$user" size="sm" />
                </button>
            </div>
        </header>

        <main id="kandungan" class="mx-auto w-full max-w-content flex-1 px-4 py-7 pb-28 sm:px-6 lg:px-8 lg:pb-12">
            <x-flash />

            {{ $slot }}
        </main>
    </div>

    {{-- ===================== MOBILE BOTTOM TAB BAR ===================== --}}
    <nav class="fixed inset-x-0 bottom-0 z-30 flex border-t border-line bg-surface lg:hidden" aria-label="{{ __('Navigasi murid') }}">
        @foreach ($nav as $item)
            @if (in_array($item['route'], $tabRoutes, true))
                <a href="{{ route($item['route']) }}" @class(['tab-link', 'tab-link-active' => $item['active']])
                   @if ($item['active']) aria-current="page" @endif>
                    <x-icon :name="$item['icon']" class="h-6 w-6" />
                    <span>{{ $item['short'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    {{-- ===================== "LAGI" / ACCOUNT SHEET (mobile) ===================== --}}
    <div x-show="moreOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" @keydown.escape.window="moreOpen = false">
        <div class="absolute inset-0 bg-ink/40" @click="moreOpen = false" x-transition.opacity></div>

        <div class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-panel border-t border-line bg-surface p-4 pb-[max(1rem,env(safe-area-inset-bottom))]"
             x-transition:enter="transition duration-200" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             role="dialog" aria-modal="true" aria-label="{{ __('Menu & akaun') }}">
            <div class="mx-auto mb-4 h-1.5 w-10 rounded-full bg-line"></div>

            <div class="mb-4 flex items-center gap-3">
                <x-avatar :user="$user" size="md" />
                <div class="min-w-0">
                    <p class="truncate font-bold text-ink">{{ $user->name }}</p>
                    <p class="truncate text-sm text-ink-2">{{ $user->grade?->name ?? __('Murid') }}</p>
                </div>
            </div>

            <nav class="space-y-1" aria-label="{{ __('Menu lain') }}">
                @foreach ($nav as $item)
                    @unless (in_array($item['route'], $tabRoutes, true))
                        <a href="{{ route($item['route']) }}" @class(['side-link', 'side-link-active' => $item['active']])>
                            <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                            {{ $item['label'] }}
                        </a>
                    @endunless
                @endforeach
            </nav>

            <div class="mt-4 space-y-3 border-t border-line pt-4">
                <div>
                    <label for="sheet-tahun" class="label">{{ __('Tahun') }}</label>
                    <select id="sheet-tahun" class="input min-h-[44px] py-2"
                            onchange="if (this.value) window.location.href = '{{ url('tahun') }}/' + this.value">
                        @foreach ($grades as $g)
                            <option value="{{ $g->level }}" @selected($activeGrade?->level === $g->level)>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-stretch gap-2">
                    <x-lang-toggle block class="flex-1" />
                    <x-theme-toggle />
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('profile.edit') }}" class="btn-secondary flex-1">{{ __('Profil Saya') }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="btn-ghost w-full">{{ __('Log Keluar') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')

    <script>
        function studentShell() {
            return {
                moreOpen: false,
                notifOpen: false,
            };
        }
    </script>
</body>
</html>
