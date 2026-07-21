@props([
    'title' => null,
    'heading' => null,
    'sub' => null,
])

@php
    $user = auth()->user();
    $current = app()->getLocale();
    $heading ??= $title;

    // Sidebar nav — mirrors the WeLearn Admin design (icon + label, active pill). The SVGs are the
    // exact glyphs from the prototype (Feather set, 1.8 stroke), inlined so they match pixel-for-pixel.
    $icons = [
        'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'users' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'content' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="14" height="14" rx="2"/><polygon points="16 11 22 7 22 17 16 13"/></svg>',
        'teachers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'students' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10L12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
    ];

    $nav = [
        ['label' => __('Utama'),     'icon' => 'home',     'route' => 'admin.dashboard',      'active' => request()->routeIs('admin.dashboard')],
        ['label' => __('Pengguna'),  'icon' => 'users',    'route' => 'admin.pengguna',       'active' => request()->routeIs('admin.pengguna')],
        ['label' => __('Kandungan'), 'icon' => 'content',  'route' => 'admin.kandungan.video', 'active' => request()->routeIs('admin.kandungan.*')],
        ['label' => __('Cikgu'),     'icon' => 'teachers', 'route' => 'admin.bakat',          'active' => request()->routeIs('admin.bakat*', 'admin.guru.*')],
        ['label' => __('Murid'),     'icon' => 'students', 'route' => 'admin.murid',          'active' => request()->routeIs('admin.murid')],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @class(['theme-dark' => ($theme ?? 'light') === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' | '.config('app.name') : config('app.name') }}</title>

    {{-- Pulls in the self-hosted Geist + Nunito fonts and Alpine. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Scoped WeLearn Admin design system: exact palette, sizing, radii from the mockup.
         Shared vocabulary with the Cikgu portal (the `.tp-*` prefix) so the two surfaces are one family. --}}
    <style>
        .tp {
            --tp-teal:#17907B; --tp-teal-hover:#2BB39B;
            --tp-ink:#28293F; --tp-body:#2D2F44;
            --tp-muted:#8B8AA3; --tp-muted-2:#6C6F87;
            --tp-line:rgba(46,44,80,.08); --tp-line-2:rgba(46,44,80,.12); --tp-line-3:rgba(46,44,80,.1);
            --tp-shadow:0 2px 10px rgba(46,44,80,.04);
            --tp-shadow-lift:0 6px 18px rgba(46,44,80,.08);
            --tp-input:#F6F5F0; --tp-active-bg:#E6F5F1; --tp-active-fg:#0F7A68;
            --tp-page:#F7F6F2; --tp-surface:#fff; --tp-surface-2:#FAF9F5; --tp-hover:#F1F0E8; --tp-chip:#EFEDE6; --tp-icon:#4A5A52;
            font-family:'Nunito',sans-serif; color:var(--tp-body);
        }
        /* Night mode: same token NAMES, dark values — every .tp element and every page colour
           converted to a var(--tp-*) recolours for free. */
        html.theme-dark .tp {
            --tp-teal:#2DD4BF; --tp-teal-hover:#5EEAD4;
            --tp-ink:#EDF2F8; --tp-body:#C9D2DC;
            --tp-muted:#8A94A3; --tp-muted-2:#A6AFBC;
            --tp-line:rgba(255,255,255,.09); --tp-line-2:rgba(255,255,255,.14); --tp-line-3:rgba(255,255,255,.11);
            --tp-shadow:0 1px 2px rgba(0,0,0,.4), 0 8px 24px -8px rgba(0,0,0,.55);
            --tp-shadow-lift:0 2px 6px rgba(0,0,0,.45), 0 18px 44px -14px rgba(0,0,0,.6);
            --tp-input:#1E2731; --tp-active-bg:#123029; --tp-active-fg:#5EEAD4;
            --tp-page:#0E1116; --tp-surface:#171E27; --tp-surface-2:#1E2731; --tp-hover:#232D38; --tp-chip:#232D38; --tp-icon:#AEB6C2;
        }
        .tp *,.tp *::before,.tp *::after { box-sizing:border-box; }
        .tp a { text-decoration:none; }
        .tp a:not([class]) { color:var(--tp-teal); }
        .tp a:not([class]):hover { color:var(--tp-teal-hover); }
        .tp h1,.tp h2,.tp h3 { margin:0; }
        .tp input:focus,.tp select:focus,.tp textarea:focus {
            outline:none; border-color:var(--tp-teal) !important;
            box-shadow:0 0 0 3px rgba(43,179,155,.2);
        }
        .tp button { font-family:inherit; }
        .tp-g { font-family:'Geist',sans-serif; }

        /* Shell */
        .tp-shell { min-height:100vh; display:grid; grid-template-columns:236px 1fr; }
        .tp-side {
            background:var(--tp-surface); border-right:1px solid var(--tp-line);
            display:flex; flex-direction:column; padding:20px 14px; gap:4px;
            position:sticky; top:0; height:100vh;
        }
        .tp-brand { display:flex; align-items:center; gap:10px; padding:4px 8px 16px; }
        .tp-brand img { width:42px; height:42px; object-fit:contain; display:block; }
        .tp-brand-name { font-family:'Geist',sans-serif; font-weight:800; font-size:16px; color:var(--tp-ink); }
        .tp-brand-sub  { font-size:11.5px; font-weight:700; color:var(--tp-muted); }

        .tp-nav {
            display:flex; align-items:center; gap:12px; width:100%; min-height:48px;
            border:none; cursor:pointer; border-radius:12px; padding:0 14px;
            font-family:'Geist',sans-serif; font-weight:800; font-size:14.5px; text-align:left;
            background:transparent; color:var(--tp-muted-2); transition:all .15s;
        }
        .tp-nav:hover { background:var(--tp-hover); color:var(--tp-ink); }
        .tp-nav.is-active { background:var(--tp-active-bg); color:var(--tp-active-fg); }
        .tp-nav.is-active:hover { background:var(--tp-active-bg); }
        .tp-nav svg { width:21px; height:21px; flex-shrink:0; }

        .tp-userbar {
            display:flex; align-items:center; gap:10px; padding:10px 8px;
            border-top:1px solid var(--tp-line);
        }
        .tp-ava {
            width:42px; height:42px; border-radius:50%; background:#28293F; color:#fff;
            display:grid; place-items:center; font-family:'Geist',sans-serif; font-weight:800;
            font-size:15px; flex-shrink:0; cursor:pointer; border:none; transition:transform .15s;
        }
        .tp-ava:hover { transform:scale(1.06); }
        .tp-userbar-name { font-family:'Geist',sans-serif; font-weight:800; font-size:13.5px; color:var(--tp-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .tp-userbar-sub  { font-size:11.5px; font-weight:700; color:var(--tp-muted); }
        .tp-logout {
            width:36px; height:36px; border-radius:10px; display:grid; place-items:center;
            color:#C24936; flex-shrink:0; border:none; background:transparent; cursor:pointer;
        }
        .tp-logout:hover { background:#FDE7E0; }

        /* Main + header */
        .tp-main { padding:28px 40px 48px; display:flex; flex-direction:column; gap:24px; min-width:0; max-width:1240px; width:100%; margin:0 auto; }
        .tp-head { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .tp-h1  { font-family:'Geist',sans-serif; font-size:24px; font-weight:800; letter-spacing:-.01em; color:var(--tp-ink); }
        .tp-hsub{ font-size:14px; color:var(--tp-muted); }
        .tp-langbar { display:flex; align-items:center; background:var(--tp-chip); border-radius:999px; padding:4px; }
        .tp-pill {
            min-height:38px; display:inline-flex; align-items:center; border:none; cursor:pointer;
            border-radius:999px; padding:0 16px; font-family:'Geist',sans-serif; font-weight:800;
            font-size:13.5px; transition:all .15s; background:transparent; color:var(--tp-muted-2);
        }
        .tp-pill.is-on { background:var(--tp-teal); color:#fff; }
        .tp-iconbtn {
            width:46px; height:46px; border-radius:12px; border:1px solid var(--tp-line-3);
            background:var(--tp-surface); cursor:pointer; display:grid; place-items:center; color:var(--tp-icon); position:relative;
        }
        .tp-iconbtn:hover { background:var(--tp-chip); }
        .tp-iconbtn svg { width:19px; height:19px; }
        .tp-dot { position:absolute; top:9px; right:10px; width:8px; height:8px; border-radius:50%; background:#EB5E5A; border:2px solid #fff; }

        /* Cards */
        .tp-card { background:var(--tp-surface); border:1px solid var(--tp-line); border-radius:18px; box-shadow:var(--tp-shadow); }
        .tp-card-16 { border-radius:16px; }

        /* Buttons */
        .tp-btn {
            min-height:46px; border:none; cursor:pointer; border-radius:12px; background:var(--tp-teal);
            color:#fff; font-family:'Geist',sans-serif; font-weight:800; font-size:14px; padding:0 20px;
            display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:background .15s;
        }
        .tp-btn:hover { background:var(--tp-teal-hover); color:#fff; }
        .tp-btn:active { transform:scale(.98); }
        .tp-btn-sm { min-height:44px; border-radius:11px; font-size:13.5px; padding:0 18px; }
        .tp-btn-outline {
            min-height:46px; cursor:pointer; border-radius:12px; border:1.5px solid var(--tp-teal);
            background:var(--tp-surface); color:var(--tp-teal); font-family:'Geist',sans-serif; font-weight:800;
            font-size:14px; padding:0 18px; display:inline-flex; align-items:center; justify-content:center; gap:8px;
        }
        .tp-btn-outline:hover { background:var(--tp-active-bg); color:var(--tp-teal); }
        .tp-btn-ghost {
            min-height:42px; cursor:pointer; border-radius:11px; border:1.5px solid var(--tp-line-2);
            background:var(--tp-surface); color:var(--tp-ink); font-family:'Geist',sans-serif; font-weight:800;
            font-size:13px; padding:0 16px; display:inline-flex; align-items:center; gap:7px;
        }
        .tp-btn-ghost:hover { background:#F4F8FC; }
        .tp-linkbtn {
            min-height:38px; border:none; cursor:pointer; background:transparent; color:var(--tp-teal);
            font-family:'Geist',sans-serif; font-weight:800; font-size:13px; padding:0 8px;
            display:inline-flex; align-items:center; gap:5px;
        }
        .tp-linkbtn:hover { color:var(--tp-teal-hover); }
        .tp-linkbtn.is-muted { color:var(--tp-muted-2); }
        .tp-linkbtn.is-muted:hover { color:var(--tp-ink); }
        .tp-linkbtn.is-danger:hover { color:#C24936; }

        /* Forms */
        .tp-field { display:flex; flex-direction:column; gap:6px; }
        .tp-label { font-family:'Geist',sans-serif; font-size:12.5px; font-weight:800; color:var(--tp-muted-2); }
        .tp-input {
            min-height:46px; border:1.5px solid var(--tp-line-2); border-radius:12px; padding:0 14px;
            background:var(--tp-input); font-family:'Nunito',sans-serif; font-size:14.5px; color:var(--tp-ink);
            width:100%;
        }
        .tp-file { min-height:46px; border:1.5px solid var(--tp-line-2); border-radius:12px; padding:9px 14px; background:var(--tp-input); font-family:'Nunito',sans-serif; font-size:13.5px; color:var(--tp-ink); width:100%; box-sizing:border-box; }
        .tp-file::file-selector-button { min-height:36px; border:none; cursor:pointer; border-radius:9px; background:#17907B; color:#fff; font-family:'Geist',sans-serif; font-weight:800; font-size:12.5px; padding:0 16px; margin-right:14px; transition:background .15s; }
        .tp-file::file-selector-button:hover { background:#2BB39B; }
        .tp-filter-select {
            min-height:46px; border:1.5px solid var(--tp-line-2); border-radius:12px; padding:0 14px;
            background:var(--tp-surface); font-family:'Geist',sans-serif; font-weight:800; font-size:14px; color:var(--tp-ink); cursor:pointer;
        }
        .tp-filter-select {
            appearance:none; -webkit-appearance:none; -moz-appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236C6F87' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 14px center; background-size:16px;
            padding-right:40px;
        }
        .tp-hint { font-size:12.5px; color:var(--tp-muted); }

        /* Empty state + placeholder */
        .tp-empty { background:var(--tp-surface); border:1px dashed rgba(46,44,80,.2); border-radius:20px; padding:64px; display:flex; flex-direction:column; align-items:center; gap:10px; text-align:center; }

        /* Table rows */
        .tp-tr:hover { background:var(--tp-surface-2); }

        @media (max-width:900px) {
            .tp-shell { grid-template-columns:1fr; }
            .tp-side { position:static; height:auto; flex-direction:row; flex-wrap:wrap; }
            .tp-main { padding:20px; }
        }
        @media (prefers-reduced-motion:reduce){ .tp * { transition:none !important; } }
    </style>
</head>

<body class="tp" style="margin:0; background:var(--tp-page);">
<div class="tp-shell">
    {{-- SIDEBAR --}}
    <aside class="tp-side">
        <a href="{{ route('admin.dashboard') }}" class="tp-brand" title="WeLearn">
            <img src="{{ asset('images/welearn1.png') }}" alt="WeLearn">
            <span style="display:flex;flex-direction:column">
                <span class="tp-brand-name">WeLearn</span>
                <span class="tp-brand-sub">{{ __('Portal Admin') }}</span>
            </span>
        </a>

        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}" @class(['tp-nav', 'is-active' => $item['active']])>
                <span style="display:grid;place-items:center;width:21px;height:21px">{!! $icons[$item['icon']] !!}</span>
                {{ $item['label'] }}
            </a>
        @endforeach

        <div style="margin-top:auto"></div>

        <div class="tp-userbar">
            <a href="{{ route('admin.profil') }}" class="tp-ava" title="{{ __('Profil') }}">{{ $user->initials() }}</a>
            <a href="{{ route('admin.profil') }}" style="display:flex;flex-direction:column;min-width:0;flex:1">
                <span class="tp-userbar-name">{{ $user->name }}</span>
                <span class="tp-userbar-sub">{{ __('Admin MOE') }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="tp-logout" title="{{ __('Log Keluar') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="tp-main">
        <div class="tp-head">
            <div style="display:flex;flex-direction:column;gap:2px;flex:1;min-width:200px">
                <h1 class="tp-h1">{{ $heading }}</h1>
                @if ($sub)
                    <span class="tp-hsub">{{ $sub }}</span>
                @endif
            </div>

            <div class="tp-langbar">
                <a href="{{ route('locale.switch', 'ms') }}" @class(['tp-pill', 'is-on' => $current === 'ms'])>BM</a>
                <a href="{{ route('locale.switch', 'en') }}" @class(['tp-pill', 'is-on' => $current === 'en'])>EN</a>
            </div>

            @php($isDark = ($theme ?? 'light') === 'dark')
            <a href="{{ route('theme.switch', $isDark ? 'light' : 'dark') }}" class="tp-iconbtn" title="{{ $isDark ? __('Mod Terang') : __('Mod Malam') }}">
                <x-icon :name="$isDark ? 'sun' : 'moon'" class="h-[19px] w-[19px]" />
            </a>

            <button type="button" class="tp-iconbtn" title="{{ __('Notifikasi') }}">
                <x-icon name="bell" class="h-[19px] w-[19px]" />
                <span class="tp-dot"></span>
            </button>
        </div>

        <x-flash />

        {{ $slot }}
    </main>
</div>

@stack('scripts')
</body>
</html>
