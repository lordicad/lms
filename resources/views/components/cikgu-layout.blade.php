@props([
    'title' => null,
    'heading' => null,
    'sub' => null,
])

@php
    $user = auth()->user();
    $current = app()->getLocale();
    $heading ??= $title;

    // Sidebar nav — mirrors the WeLearn Cikgu design (icon + label, active pill).
    $nav = [
        ['label' => __('Utama'),   'icon' => 'home',     'route' => 'cikgu.dashboard', 'active' => request()->routeIs('cikgu.dashboard')],
        ['label' => __('Video'),   'icon' => 'video',    'route' => 'cikgu.video.index', 'active' => request()->routeIs('cikgu.video.*')],
        ['label' => __('Bahan'),   'icon' => 'file',     'route' => 'cikgu.bahan.index', 'active' => request()->routeIs('cikgu.bahan.*')],
        ['label' => __('Kuiz'),    'icon' => 'quiz',     'route' => 'cikgu.kuiz.index', 'active' => request()->routeIs('cikgu.kuiz.*')],
        ['label' => __('Bab'),     'icon' => 'book',     'route' => 'cikgu.bab.index', 'active' => request()->routeIs('cikgu.bab.*')],
        ['label' => __('Ranking'), 'icon' => 'trophy',   'route' => 'cikgu.ranking', 'active' => request()->routeIs('cikgu.ranking')],
        ['label' => __('Bakat'),   'icon' => 'star',     'route' => 'cikgu.bakat', 'active' => request()->routeIs('cikgu.bakat')],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' | '.config('app.name') : config('app.name') }}</title>

    {{-- Pulls in the self-hosted Geist + Nunito fonts and Alpine. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Scoped WeLearn Cikgu design system: exact palette, sizing, radii from the mockup. --}}
    <style>
        .tp {
            --tp-teal:#17907B; --tp-teal-hover:#2BB39B;
            --tp-ink:#28293F; --tp-body:#2D2F44;
            --tp-muted:#8B8AA3; --tp-muted-2:#6C6F87;
            --tp-line:rgba(46,44,80,.08); --tp-line-2:rgba(46,44,80,.12); --tp-line-3:rgba(46,44,80,.1);
            --tp-shadow:0 2px 10px rgba(46,44,80,.04);
            --tp-shadow-lift:0 6px 18px rgba(46,44,80,.08);
            --tp-input:#F6F5F0; --tp-active-bg:#E6F5F1; --tp-active-fg:#0F7A68;
            font-family:'Nunito',sans-serif; color:var(--tp-body);
        }
        .tp *,.tp *::before,.tp *::after { box-sizing:border-box; }
        .tp a { text-decoration:none; }
        /* Only plain (class-less) content links get the teal colour. Links styled as buttons,
           the avatar, nav items, etc. carry their own class colour and must not be overridden
           (a teal button link would otherwise get teal text on a teal fill — invisible). */
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
            background:#fff; border-right:1px solid var(--tp-line);
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
        .tp-nav:hover { background:#F1F0E8; color:var(--tp-ink); }
        .tp-nav.is-active { background:var(--tp-active-bg); color:var(--tp-active-fg); }
        .tp-nav.is-active:hover { background:var(--tp-active-bg); }
        .tp-nav svg { width:21px; height:21px; flex-shrink:0; }

        .tp-userbar {
            display:flex; align-items:center; gap:10px; padding:10px 8px;
            border-top:1px solid var(--tp-line);
        }
        .tp-ava {
            width:42px; height:42px; border-radius:50%; background:var(--tp-teal); color:#fff;
            display:grid; place-items:center; font-family:'Geist',sans-serif; font-weight:800;
            font-size:15px; flex-shrink:0; cursor:pointer; border:none;
        }
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
        .tp-langbar { display:flex; align-items:center; background:#EFEDE6; border-radius:999px; padding:4px; }
        .tp-pill {
            min-height:38px; display:inline-flex; align-items:center; border:none; cursor:pointer;
            border-radius:999px; padding:0 16px; font-family:'Geist',sans-serif; font-weight:800;
            font-size:13.5px; transition:all .15s; background:transparent; color:var(--tp-muted-2);
        }
        .tp-pill.is-on { background:var(--tp-teal); color:#fff; }
        .tp-iconbtn {
            width:46px; height:46px; border-radius:12px; border:1px solid var(--tp-line-3);
            background:#fff; cursor:pointer; display:grid; place-items:center; color:#4A5A52; position:relative;
        }
        .tp-iconbtn:hover { background:#EFEDE6; }
        .tp-iconbtn svg { width:19px; height:19px; }
        .tp-dot { position:absolute; top:9px; right:10px; width:8px; height:8px; border-radius:50%; background:#EB5E5A; border:2px solid #fff; }

        /* Cards */
        .tp-card { background:#fff; border:1px solid var(--tp-line); border-radius:18px; box-shadow:var(--tp-shadow); }
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
            background:#fff; color:var(--tp-teal); font-family:'Geist',sans-serif; font-weight:800;
            font-size:14px; padding:0 18px; display:inline-flex; align-items:center; justify-content:center; gap:8px;
        }
        .tp-btn-outline:hover { background:var(--tp-active-bg); color:var(--tp-teal); }
        .tp-btn-ghost {
            min-height:42px; cursor:pointer; border-radius:11px; border:1.5px solid var(--tp-line-2);
            background:#fff; color:var(--tp-ink); font-family:'Geist',sans-serif; font-weight:800;
            font-size:13px; padding:0 16px; display:inline-flex; align-items:center; gap:7px;
        }
        .tp-btn-ghost:hover { background:#F4F8FC; }
        .tp-icon-action { width:42px; height:42px; border-radius:11px; border:none; cursor:pointer; background:transparent; color:var(--tp-muted-2); display:grid; place-items:center; flex-shrink:0; }
        .tp-icon-action:hover { background:#EFEDE6; }
        .tp-icon-danger { color:#C24936; }
        .tp-icon-danger:hover { background:#FDE7E0; }

        /* Forms */
        .tp-field { display:flex; flex-direction:column; gap:6px; }
        .tp-label { font-family:'Geist',sans-serif; font-size:12.5px; font-weight:800; color:var(--tp-muted-2); }
        .tp-input, .tp-select, .tp-textarea {
            min-height:46px; border:1.5px solid var(--tp-line-2); border-radius:12px; padding:0 14px;
            background:var(--tp-input); font-family:'Nunito',sans-serif; font-size:14.5px; color:var(--tp-ink);
            width:100%;
        }
        .tp-select { font-family:'Geist',sans-serif; font-weight:700; cursor:pointer; }
        .tp-textarea { padding:12px 14px; resize:vertical; min-height:0; }
        .tp-input::placeholder,.tp-textarea::placeholder { color:var(--tp-muted); }
        .tp-hint { font-size:12.5px; color:var(--tp-muted); }
        .tp-file { min-height:46px; border:1.5px solid var(--tp-line-2); border-radius:12px; padding:10px 14px; background:var(--tp-input); font-family:'Nunito',sans-serif; font-size:13.5px; color:var(--tp-ink); width:100%; }
        .tp-file::file-selector-button { min-height:38px; border:none; cursor:pointer; border-radius:10px; background:var(--tp-teal); color:#fff; font-family:'Geist',sans-serif; font-weight:800; font-size:13px; padding:0 16px; margin-right:14px; transition:background .15s; }
        .tp-file::file-selector-button:hover { background:var(--tp-teal-hover); }
        .tp-filter-select {
            min-height:46px; border:1.5px solid var(--tp-line-2); border-radius:12px; padding:0 14px;
            background:#fff; font-family:'Geist',sans-serif; font-weight:800; font-size:14px; color:var(--tp-ink); cursor:pointer;
        }
        /* Custom down-chevron on every select (the forms reset strips the native arrow). */
        .tp-select, .tp-filter-select {
            appearance:none; -webkit-appearance:none; -moz-appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236C6F87' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 14px center; background-size:16px;
            padding-right:40px;
        }

        /* Tags / badges / chips */
        .tp-tag { border-radius:999px; padding:4px 12px; font-family:'Geist',sans-serif; font-size:11.5px; font-weight:800; }
        .tp-tag-neutral { border:1px solid var(--tp-line-2); color:var(--tp-muted-2); border-radius:999px; padding:3px 11px; font-family:'Geist',sans-serif; font-size:11.5px; font-weight:800; }
        .tp-badge { flex-shrink:0; border-radius:999px; padding:6px 14px; font-family:'Geist',sans-serif; font-size:12px; font-weight:800; }
        .tp-badge-ok { background:#DCF2EE; color:#0F7A68; }
        .tp-badge-draft { background:#FEF0CE; color:#8A6A12; }
        .tp-meta { font-size:12.5px; font-weight:700; color:var(--tp-muted); }

        /* Stat cards */
        .tp-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
        .tp-stat { background:#fff; border:1px solid var(--tp-line); border-radius:16px; padding:20px 22px; display:flex; flex-direction:column; gap:6px; box-shadow:var(--tp-shadow); }
        .tp-stat-ico { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; font-size:17px; }
        .tp-stat-label { font-size:13.5px; font-weight:700; color:var(--tp-muted); }
        .tp-stat-value { font-family:'Geist',sans-serif; font-size:28px; font-weight:800; color:var(--tp-ink); }

        .tp-listcard { background:#fff; border:1px solid var(--tp-line); border-radius:16px; padding:16px 20px; display:flex; align-items:center; gap:18px; box-shadow:var(--tp-shadow); }
        .tp-listcard:hover { box-shadow:var(--tp-shadow-lift); }
        .tp-list { display:flex; flex-direction:column; gap:12px; }
        .tp-toolbar { display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap; }
        .tp-thumb { border-radius:10px; overflow:hidden; display:grid; place-items:center; color:rgba(66,118,174,.8); flex-shrink:0; }
        .tp-empty { background:#fff; border:1px dashed rgba(46,44,80,.2); border-radius:20px; padding:56px 24px; display:flex; flex-direction:column; align-items:center; gap:10px; text-align:center; }
        .tp-panelform { background:#fff; border:1px solid var(--tp-line); border-radius:18px; padding:24px; display:flex; flex-direction:column; gap:16px; box-shadow:var(--tp-shadow); }
        .tp-back { align-self:flex-start; display:flex; align-items:center; gap:8px; border:none; background:transparent; cursor:pointer; font-family:'Geist',sans-serif; font-size:14px; font-weight:800; color:#6C6F87; padding:2px 0; }
        .tp-back:hover { color:var(--tp-teal); }
        .tp-check { width:24px; height:24px; border-radius:7px; flex-shrink:0; display:grid; place-items:center; font-size:14px; margin-top:2px; background:var(--tp-teal); color:#fff; border:2px solid var(--tp-teal); }
        .tp-check-off { width:24px; height:24px; border-radius:7px; flex-shrink:0; display:grid; place-items:center; margin-top:2px; background:#fff; border:2px solid rgba(46,44,80,.25); }
        .tp-toggle { min-height:48px; cursor:pointer; border-radius:12px; font-family:'Geist',sans-serif; font-weight:800; font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:8px; flex:1; transition:all .15s; border:1.5px solid var(--tp-line-2); background:#fff; color:#28293F; }
        .tp-toggle.is-on { border:none; background:var(--tp-teal); color:#fff; }
        .tp-dropzone { border:2px dashed rgba(46,44,80,.18); border-radius:14px; padding:36px; display:flex; flex-direction:column; align-items:center; gap:8px; text-align:center; background:#FAF9F5; }
        .tp-checkrow { background:#fff; border:1px solid var(--tp-line); border-radius:18px; padding:20px 24px; display:flex; align-items:flex-start; gap:14px; box-shadow:var(--tp-shadow); cursor:pointer; }
        .tp-typecard { background:#fff; border:1px solid var(--tp-line); border-radius:18px; padding:26px; display:flex; flex-direction:column; gap:14px; box-shadow:var(--tp-shadow); cursor:pointer; text-decoration:none; }
        .tp-typecard:hover { box-shadow:var(--tp-shadow-lift); transform:translateY(-2px); }
        .tp-typeopt { border:1.5px solid var(--tp-line-2); background:#fff; border-radius:14px; padding:16px 18px; display:flex; gap:12px; align-items:center; cursor:pointer; text-decoration:none; }
        .tp-typeopt:hover { border-color:var(--tp-teal); }
        .tp-typeopt.is-on { border-color:var(--tp-teal); background:var(--tp-active-bg); }
        .tp-formwrap { display:flex; flex-direction:column; gap:20px; max-width:860px; }
        .tp-error { font-size:13px; font-weight:700; color:#C24936; }
        .tp-row { display:flex; align-items:center; gap:16px; padding:15px 22px; border-bottom:1px solid rgba(46,44,80,.05); }
        .tp-row:hover { background:#FAF9F5; }
        .tp-row:last-child { border-bottom:none; }

        @media (max-width:900px) {
            .tp-shell { grid-template-columns:1fr; }
            .tp-side { position:static; height:auto; flex-direction:row; flex-wrap:wrap; }
            .tp-main { padding:20px; }
            .tp-stats { grid-template-columns:1fr; }
        }
        @media (prefers-reduced-motion:reduce){ .tp * { transition:none !important; } }
    </style>
</head>

<body class="tp" style="margin:0; background:#F7F6F2;">
<div class="tp-shell">
    {{-- SIDEBAR --}}
    <aside class="tp-side">
        <a href="{{ $user->homeRoute() }}" class="tp-brand" title="WeLearn">
            <img src="{{ asset('images/welearn1.png') }}" alt="WeLearn">
            <span style="display:flex;flex-direction:column">
                <span class="tp-brand-name">WeLearn</span>
                <span class="tp-brand-sub">{{ __('Portal Cikgu') }}</span>
            </span>
        </a>

        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}" @class(['tp-nav', 'is-active' => $item['active']])>
                <x-icon :name="$item['icon']" />
                {{ $item['label'] }}
            </a>
        @endforeach

        <div style="margin-top:auto"></div>

        <div class="tp-userbar">
            <a href="{{ route('profile.edit') }}" class="tp-ava" title="{{ __('Profil') }}">{{ $user->initials() }}</a>
            <a href="{{ route('profile.edit') }}" style="display:flex;flex-direction:column;min-width:0;flex:1">
                <span class="tp-userbar-name">{{ $user->name }}</span>
                <span class="tp-userbar-sub">{{ __('Guru') }}</span>
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
