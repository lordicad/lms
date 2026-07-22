<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @class(['theme-dark' => ($theme ?? 'light') === 'dark'])>
<head>
    <meta charset="utf-8">
    {{-- Tab icon. One 196px PNG serves the browser tab and the phone home screen alike. --}}
    <link rel="icon" type="image/png" href="{{ asset('images/welearn1.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/welearn1.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ isset($title) ? $title.' | '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-bg font-sans">
    <a href="#kandungan" class="skip-link">{{ __('Terus ke kandungan') }}</a>

    <header class="border-b border-line bg-surface">
        <div class="mx-auto flex h-[72px] max-w-5xl items-center justify-between px-4 sm:px-6">
            <a href="{{ route('landing') }}" class="flex items-center gap-2">
                <span class="rounded-control bg-brand px-2 py-1 text-sm font-extrabold text-on-brand">LMS</span>
                <span class="text-lg font-extrabold text-ink">MOE</span>
            </a>

            <div class="flex items-center gap-3">
                <x-lang-toggle />
                <x-theme-toggle />

                <a href="{{ request()->routeIs('login') ? route('register') : route('login') }}"
                   class="hidden text-sm font-bold text-brand hover:underline sm:inline">
                    {{ request()->routeIs('login') ? __('Belum ada akaun? Daftar') : __('Sudah ada akaun? Log Masuk') }}
                </a>
            </div>
        </div>
    </header>

    <main id="kandungan" class="mx-auto flex max-w-lg flex-col px-4 py-10 sm:px-6">
        {{ $slot }}
    </main>
</body>
</html>
