@php
    $user = auth()->user();
    $grades = \App\Models\Grade::orderBy('level')->get();
    $activeGrade = \App\Support\ActiveGrade::for($user);

    $nav = [
        ['route' => 'belajar.index',   'active' => request()->routeIs('belajar.index'),                                'icon' => 'home',   'label' => __('Utama')],
        ['route' => 'subjek.index',    'active' => request()->routeIs('subjek.index', 'belajar.subjek', 'bab.show'),   'icon' => 'grid',   'label' => __('Subjek')],
        ['route' => 'kegemaran.index', 'active' => request()->routeIs('kegemaran.index'),                              'icon' => 'heart',  'label' => __('Kegemaran')],
        ['route' => 'sambung.index',   'active' => request()->routeIs('sambung.index'),                                'icon' => 'play',   'label' => __('Sambung Menonton')],
        ['route' => 'simpanan.index',  'active' => request()->routeIs('simpanan.index'),                               'icon' => 'device', 'label' => __('Simpanan Offline')],
        ['route' => 'ranking.index',   'active' => request()->routeIs('ranking.index'),                                'icon' => 'trophy', 'label' => __('Papan Ranking')],
        ['route' => 'kuiz-saya.index', 'active' => request()->routeIs('kuiz-saya.index'),                              'icon' => 'quiz',   'label' => __('Kuiz')],
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

    {{-- ===================== DESKTOP SIDEBAR ===================== --}}
    <aside class="fixed inset-y-0 left-0 z-30 hidden flex-col border-r border-line bg-surface transition-[width] duration-200 lg:flex"
           :class="collapsed ? 'w-[76px]' : 'w-64'" aria-label="{{ __('Navigasi murid') }}">
        <div class="flex h-[68px] items-center gap-2 border-b border-line px-4">
            <a href="{{ route('belajar.index') }}" class="flex items-center gap-2">
                <span class="rounded-control bg-brand px-2 py-1 text-sm font-extrabold text-on-brand">LMS</span>
                <span class="text-lg font-extrabold text-ink" x-show="! collapsed" x-cloak>MOE</span>
            </a>

            <button type="button" class="btn-ghost btn-sm ml-auto" @click="toggleCollapse()" x-show="! collapsed" x-cloak
                    aria-label="{{ __('Kecilkan bar sisi') }}">
                <x-icon name="chevron-left" class="h-5 w-5" />
            </button>
        </div>

        <button type="button" class="mx-3 mt-3 inline-flex min-h-[44px] items-center justify-center rounded-control text-ink-2 hover:bg-surface-2 hover:text-ink"
                @click="toggleCollapse()" x-show="collapsed" x-cloak aria-label="{{ __('Besarkan bar sisi') }}">
            <x-icon name="menu" class="h-5 w-5" />
        </button>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3">
            @foreach ($nav as $item)
                <a href="{{ route($item['route']) }}" @class(['side-link', 'side-link-active' => $item['active']])
                   :class="collapsed ? 'justify-center' : ''" @if ($item['active']) aria-current="page" @endif
                   :title="collapsed ? @js($item['label']) : null">
                    <x-icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                    <span x-show="! collapsed" x-cloak>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Bottom: Tahun switcher, language, account. --}}
        <div class="space-y-2 border-t border-line p-3" x-show="! collapsed" x-cloak>
            <label for="side-tahun" class="sr-only">{{ __('Tukar Tahun') }}</label>
            <select id="side-tahun" class="input min-h-[44px] py-2 text-sm"
                    onchange="if (this.value) window.location.href = '{{ url('tahun') }}/' + this.value">
                @foreach ($grades as $g)
                    <option value="{{ $g->level }}" @selected($activeGrade?->level === $g->level)>{{ $g->name }}</option>
                @endforeach
            </select>

            <div class="flex items-stretch gap-2">
                <x-lang-toggle block class="flex-1" />
                <x-theme-toggle />
            </div>

            <div class="flex items-center gap-2 rounded-control p-2">
                <a href="{{ route('profile.edit') }}" class="flex min-w-0 flex-1 items-center gap-2 hover:opacity-80">
                    <x-avatar :user="$user" size="sm" />
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-bold text-ink">{{ $user->name }}</span>
                        <span class="block truncate text-xs text-ink-2">{{ $user->grade?->name ?? __('Murid') }}</span>
                    </span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-ghost btn-sm" aria-label="{{ __('Log Keluar') }}">
                        <x-icon name="logout" class="h-5 w-5" />
                    </button>
                </form>
            </div>
        </div>

        <div class="flex justify-center border-t border-line p-3" x-show="collapsed" x-cloak>
            <a href="{{ route('profile.edit') }}" aria-label="{{ __('Profil Saya') }}"><x-avatar :user="$user" size="sm" /></a>
        </div>
    </aside>

    {{-- ===================== MAIN COLUMN ===================== --}}
    <div class="flex min-h-screen flex-col transition-[padding] duration-200" :class="collapsed ? 'lg:pl-[76px]' : 'lg:pl-64'">
        <header class="sticky top-0 z-20 border-b border-line bg-surface">
            <div class="flex h-[64px] items-center gap-3 px-4 sm:px-6">
                <a href="{{ route('belajar.index') }}" class="flex shrink-0 items-center gap-2 lg:hidden">
                    <span class="rounded-control bg-brand px-2 py-1 text-sm font-extrabold text-on-brand">LMS</span>
                    <span class="text-lg font-extrabold text-ink">MOE</span>
                </a>

                <form method="GET" action="{{ route('cari.index') }}" class="relative w-full max-w-xl" role="search">
                    <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-2" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari video...') }}"
                           class="input min-h-[44px] pl-11" aria-label="{{ __('Cari video') }}">
                </form>

                <button type="button" class="ml-auto shrink-0 lg:hidden" @click="moreOpen = true"
                        aria-label="{{ __('Menu & akaun') }}">
                    <x-avatar :user="$user" size="sm" />
                </button>
            </div>
        </header>

        <main id="kandungan" class="mx-auto w-full max-w-content flex-1 px-4 py-6 pb-28 sm:px-6 lg:px-8 lg:pb-12">
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
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    {{-- ===================== "LAGI" / ACCOUNT SHEET (mobile) ===================== --}}
    <div x-show="moreOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" @keydown.escape.window="moreOpen = false">
        <div class="absolute inset-0 bg-ink/40" @click="moreOpen = false" x-transition.opacity></div>

        <div class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-card border-t border-line bg-surface p-4 pb-[max(1rem,env(safe-area-inset-bottom))]"
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
                collapsed: JSON.parse(localStorage.getItem('sidebar-collapsed') || 'false'),
                moreOpen: false,

                init() {
                    this.$watch('collapsed', value => localStorage.setItem('sidebar-collapsed', JSON.stringify(value)));
                },

                toggleCollapse() {
                    this.collapsed = ! this.collapsed;
                },
            };
        }
    </script>
</body>
</html>
