@props([
    'active' => 'login',   // which tab is highlighted: 'login' | 'register'
    'title' => null,
    'tabs' => true,        // the Log Masuk / Daftar pair — off for pages reached while signed in
    'back' => true,        // the "back to home" link, likewise
])

{{--
    WeLearn split-screen auth shell (login / register).

    Deliberately self-contained — its own fonts and a scoped stylesheet, no app.css/app.js — so it
    matches the WeLearn marketing landing exactly and stays isolated from the signed-in app. Dark
    mode still works: the server emits `<html class="theme-dark">` (see guest layout / $theme) and
    the html.theme-dark overrides below flip the palette. The language and theme toggles are the
    real server-rendered switches (locale.switch / theme.switch routes), just restyled as pills.
--}}

@php($isDark = ($theme ?? 'light') === 'dark')
@php($current = app()->getLocale())
{{-- Inline the WeLearn logo as a data URI (same as the landing page): the auto-deploy serves
     static files only from public/build/, so a plain /images/... reference 404s. Reading the
     committed file and embedding it keeps the shell self-contained and docroot-independent. --}}
@php($wlLogo = is_file($p = public_path('images/welearn-banner.png')) ? 'data:image/png;base64,'.base64_encode(file_get_contents($p)) : asset('images/welearn-banner.png'))
<!DOCTYPE html>
<html lang="{{ $current }}" @class(['theme-dark' => $isDark])>
<head>
    <meta charset="utf-8">
    {{-- Tab icon. One 196px PNG serves the browser tab and the phone home screen alike. --}}
    <link rel="icon" type="image/png" href="{{ asset('images/welearn.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/welearn.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' | WeLearn' : 'WeLearn' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* The WeLearn Auth design was authored with the default content-box model (its
           stylesheet has no box-sizing reset). Matching it keeps the card 514px and the
           input boxes 440px wide, exactly as the mockup — a border-box reset here shrinks
           every box by its padding+border and makes the fields look narrower. */
        *, *::before, *::after { box-sizing: content-box; }

        :root {
            --bg: #FDFDFB; --surface: #FFFFFF;
            --ink: #24312A; --ink-2: #2A342C; --muted: #6B7568; --faint: #879182;
            --brand: #3E6B45; --brand-hover: #4A7D52; --brand-soft: #E7EEDA;
            --dark-green: #24402C; --dark-ink: #DCE8D2; --dark-muted: #B9CCAF; --dark-faint: #7E9878;
            --accent: #A9C97E;
            --line: rgba(36,49,42,.08); --line-strong: #D5D8CC;
            --tab-track: #F1F0E8; --pill-track: #ECEADF;
            --field-bg: #FDFDFB; --field-warn: #FFF9E6;
            --card-shadow: 0 16px 44px rgba(36,49,42,.08);
            --btn-shadow: 0 6px 18px rgba(62,107,69,.28);
            --info-bg: #E7EEDA; --info-ink: #3E6B45;
            --err-bg: #FBEAEA; --err-ink: #B4402F;
        }

        html.theme-dark {
            --bg: #0C1410; --surface: #15221A;
            --ink: #ECF2F4; --ink-2: #ECF2F4; --muted: #B8C8BC; --faint: #8FA093;
            --brand: #9DC284; --brand-hover: #A9C97E; --brand-soft: rgba(157,194,132,.14);
            --dark-green: #101B14; --dark-ink: #DCE8D2; --dark-muted: #A9C0AD; --dark-faint: #7E9878;
            --line: rgba(255,255,255,.09); --line-strong: rgba(255,255,255,.16);
            --tab-track: #0F1A13; --pill-track: #0F1A13;
            --field-bg: #101B14; --field-warn: #1C2417;
            --card-shadow: 0 16px 44px rgba(0,0,0,.45);
            --btn-shadow: 0 6px 18px rgba(0,0,0,.4);
            --info-bg: rgba(157,194,132,.14); --info-ink: #B8D39A;
            --err-bg: rgba(180,64,47,.16); --err-ink: #F0A79A;
        }

        body {
            margin: 0; background: var(--bg); color: var(--ink-2);
            font-family: 'Nunito', sans-serif; font-size: 16px; line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        a { color: var(--brand); text-decoration: none; }
        a:hover { color: var(--brand-hover); }
        img, svg { display: block; }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }

        .wla-shell { min-height: 100vh; display: grid; grid-template-columns: 1fr 1.1fr; }

        /* ── Brand panel ── */
        .wla-brand {
            /* The uploaded artwork fills the panel. A semi-transparent brand-green veil sits over
               it (first layer, so on top) so the light heading and copy stay readable and the tone
               stays on-brand; --dark-green shows underneath as the fallback if the image is slow. */
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--dark-green) 58%, transparent), color-mix(in srgb, var(--dark-green) 72%, transparent)),
                var(--dark-green) url('{{ asset('images/AuthPic.png') }}') center center / cover no-repeat;
            color: var(--dark-ink);
            display: flex; flex-direction: column; justify-content: space-between;
            padding: 48px 56px;
        }
        .wla-brand-logo {
            align-self: flex-start; background: #F4F6F2; border-radius: 14px;
            padding: 10px 18px; display: inline-flex;
        }
        .wla-brand-logo img { height: 48px; width: auto; }
        .wla-brand-copy { display: flex; flex-direction: column; gap: 18px; max-width: 420px; }
        .wla-brand h1 {
            margin: 0; font-family: 'Geist', sans-serif; font-size: 40px; line-height: 1.15;
            font-weight: 800; letter-spacing: -.01em; color: #fff; text-wrap: balance;
        }
        .wla-brand p { margin: 0; font-size: 16.5px; line-height: 1.65; color: var(--dark-muted); }
        .wla-brand-accent {
            display: flex; gap: 10px; align-items: center; font-family: 'Geist', sans-serif;
            font-weight: 700; font-size: 13px; letter-spacing: .1em; text-transform: uppercase;
            color: var(--accent);
        }
        .wla-brand-foot { font-size: 12.5px; color: var(--dark-faint); }

        /* ── Form panel ── */
        .wla-form {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 48px 32px; gap: 24px;
        }
        .wla-topbar { width: 100%; max-width: 440px; display: flex; justify-content: flex-end; align-items: center; gap: 10px; }
        .wla-pills { display: flex; background: var(--pill-track); border: 1px solid var(--line-strong); border-radius: 999px; padding: 3px; font-family: 'Geist', sans-serif; font-size: 13px; font-weight: 700; }
        .wla-pill {
            min-width: 30px; min-height: 22px; display: inline-flex; align-items: center; justify-content: center;
            border: none; cursor: pointer; border-radius: 999px; padding: 5px 10px;
            font-family: 'Geist', sans-serif; font-size: 13px; font-weight: 800;
            background: transparent; color: var(--muted); transition: background .15s, color .15s;
        }
        .wla-pill.is-active { background: var(--brand); color: #fff; }
        .wla-iconbtn {
            width: 44px; height: 44px; border-radius: 50%; border: 1px solid var(--line-strong);
            background: var(--surface); cursor: pointer; display: grid; place-items: center;
            color: var(--muted); transition: background .15s, transform .1s;
        }
        .wla-iconbtn:hover { background: var(--tab-track); }
        .wla-iconbtn:active { transform: scale(.95); }

        .wla-card {
            width: 100%; max-width: 440px; background: var(--surface);
            border: 1px solid var(--line); border-radius: 22px; box-shadow: var(--card-shadow);
            padding: 36px; display: flex; flex-direction: column; gap: 22px;
        }
        .wla-tabs {
            display: grid; grid-template-columns: 1fr 1fr; background: var(--tab-track);
            border-radius: 12px; padding: 4px; font-family: 'Geist', sans-serif; font-weight: 700; font-size: 14.5px;
        }
        .wla-tab {
            min-height: 44px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 9px; color: var(--muted); transition: background .15s, color .15s;
        }
        .wla-tab.is-active { background: var(--surface); color: var(--ink); box-shadow: 0 2px 6px rgba(36,49,42,.1); }

        .wla-stack { display: flex; flex-direction: column; gap: 18px; }
        .wla-head { display: flex; flex-direction: column; gap: 4px; }
        .wla-head h2 { margin: 0; font-family: 'Geist', sans-serif; font-size: 24px; font-weight: 800; color: var(--ink); }
        .wla-head p { margin: 0; font-size: 14.5px; color: var(--muted); }

        .wla-label { display: flex; flex-direction: column; gap: 6px; font-weight: 700; font-size: 14px; color: #4A5A4E; }
        html.theme-dark .wla-label { color: var(--muted); }
        .wla-label-row { display: flex; justify-content: space-between; align-items: center; }
        .wla-input, .wla-select {
            min-height: 48px; border: 1px solid var(--line-strong); border-radius: 12px;
            padding: 0 16px; font-family: 'Nunito', sans-serif; font-size: 15px; color: var(--ink-2);
            background: var(--field-bg);
        }
        .wla-select {
            appearance: none; -webkit-appearance: none; -moz-appearance: none; padding-right: 42px; cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236C6F87' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 16px center; background-size: 16px;
        }
        .wla-input:focus, .wla-select:focus { outline: none; border-color: var(--brand); box-shadow: 0 0 0 3px rgba(62,107,69,.15); }
        .wla-input[aria-invalid="true"], .wla-select[aria-invalid="true"] { border-color: var(--err-ink); }
        .wla-hint { font-weight: 600; font-size: 12.5px; color: var(--faint); }

        .wla-roles { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .wla-role {
            display: flex; flex-direction: column; align-items: center; gap: 6px; min-height: 76px;
            justify-content: center; cursor: pointer; border-radius: 14px;
            border: 1px solid var(--line-strong); background: var(--field-bg); color: var(--muted);
        }
        .wla-role .emoji { font-size: 22px; }
        .wla-role .name { font-family: 'Geist', sans-serif; font-weight: 800; font-size: 15px; }
        .wla-role.is-active { border: 2px solid var(--brand); background: var(--brand-soft); color: var(--ink); }

        .wla-btn {
            min-height: 52px; border: none; cursor: pointer; border-radius: 14px;
            background: var(--brand); color: #fff; font-family: 'Geist', sans-serif; font-weight: 800;
            font-size: 16px; box-shadow: var(--btn-shadow); transition: background .15s, transform .1s;
        }
        .wla-btn:hover { background: var(--brand-hover); }
        .wla-btn:active { transform: scale(.98); }

        .wla-alert { border-radius: 12px; padding: 12px 16px; font-weight: 700; font-size: 14px; }
        .wla-alert.info { background: var(--info-bg); color: var(--info-ink); }
        .wla-alert.err { background: var(--err-bg); color: var(--err-ink); }
        .wla-field-error { margin: 2px 0 0; font-weight: 700; font-size: 13px; color: var(--err-ink); }
        .wla-back { font-size: 14px; font-weight: 700; }

        [x-cloak] { display: none !important; }

        @media (max-width: 860px) {
            .wla-shell { grid-template-columns: 1fr; }
            .wla-brand { padding: 32px 28px; gap: 28px; }
            .wla-brand h1 { font-size: 30px; }
            .wla-form { padding: 32px 20px; }
        }
    </style>
</head>

<body>
<div class="wla-shell">
    {{-- Brand panel --}}
    <aside class="wla-brand">
        <a href="{{ url('/') }}" class="wla-brand-logo" title="{{ __('Kembali ke halaman utama') }}" aria-label="WeLearn">
            <img src="{{ $wlLogo }}" alt="WeLearn">
        </a>
        <div class="wla-brand-copy">
            <h1>{{ __('Belajar di mana-mana, bila-bila masa.') }}</h1>
            <p>{{ __('Video pelajaran, bahan dan kuiz — tersusun mengikut Subjek dan Tahun.') }}</p>
            <div class="wla-brand-accent">{{ __('Belajar · Membesar · Berjaya') }}</div>
        </div>
        <span class="wla-brand-foot">{{ __('© 2026 WeLearn — Weststar Engineering Learning Management System') }}</span>
    </aside>

    {{-- Form panel --}}
    <main class="wla-form">
        <div class="wla-topbar">
            {{-- Real server-rendered language switch, styled as pills --}}
            <nav class="wla-pills" aria-label="Tukar bahasa / Switch language">
                @foreach (['ms' => 'BM', 'en' => 'EN'] as $code => $lbl)
                    <a href="{{ route('locale.switch', $code) }}"
                       class="wla-pill {{ $current === $code ? 'is-active' : '' }}"
                       @if ($current === $code) aria-current="true" @endif>{{ $lbl }}</a>
                @endforeach
            </nav>
            {{-- Real server-rendered theme switch --}}
            <a href="{{ route('theme.switch', $isDark ? 'light' : 'dark') }}"
               class="wla-iconbtn" role="button"
               aria-pressed="{{ $isDark ? 'true' : 'false' }}"
               title="{{ $isDark ? __('Mod Terang') : __('Mod Gelap') }}"
               aria-label="{{ $isDark ? __('Mod Terang') : __('Mod Gelap') }}">
                @if ($isDark)
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
                @else
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                @endif
            </a>
        </div>

        <div class="wla-card">
            @if ($tabs)
                {{-- Tabs: real navigation between the two routes, so each keeps its own POST/validation --}}
                <div class="wla-tabs">
                    <a href="{{ route('login') }}" class="wla-tab {{ $active === 'login' ? 'is-active' : '' }}"
                       @if ($active === 'login') aria-current="page" @endif>{{ __('Log Masuk') }}</a>
                    <a href="{{ route('register') }}" class="wla-tab {{ $active === 'register' ? 'is-active' : '' }}"
                       @if ($active === 'register') aria-current="page" @endif>{{ __('Daftar') }}</a>
                </div>
            @endif

            {{ $slot }}
        </div>

        @if ($back)
            <a href="{{ url('/') }}" class="wla-back">← {{ __('Kembali ke halaman utama') }}</a>
        @endif
        {{ $footer ?? '' }}
    </main>
</div>
</body>
</html>
