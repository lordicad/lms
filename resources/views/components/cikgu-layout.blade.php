@props([
    'title' => null,
    'heading' => null,
    'headingIcon' => null,
    'sub' => null,
])

@php
    $user = auth()->user();
    $current = app()->getLocale();
    $heading ??= $title;
    $unreadNotifications = $user->teacherNotifications()->whereNull('read_at')->count();
    $recentNotifications = $user->teacherNotifications()->latest()->limit(8)->get();
    $notifMeta = [
        \App\Models\TeacherNotification::TYPE_QUIZ => ['icon' => 'quiz', 'tint' => '#FEF0CE', 'fg' => '#8A6A12', 'text' => __(':actor menjawab kuiz ":title"')],
        \App\Models\TeacherNotification::TYPE_FAVOURITE => ['icon' => 'heart', 'tint' => '#FBE4ED', 'fg' => '#B84A75', 'text' => __(':actor menggemari video ":title"')],
        \App\Models\TeacherNotification::TYPE_DOWNLOAD => ['icon' => 'download', 'tint' => '#E4EEF9', 'fg' => '#2E6CA8', 'text' => __(':actor memuat turun bahan ":title"')],
    ];

    // Sidebar nav - mirrors the WeLearn Cikgu design (icon + label, active pill).
    $nav = [
        ['label' => __('Utama'),   'icon' => 'home',     'route' => 'cikgu.dashboard', 'active' => request()->routeIs('cikgu.dashboard')],
        ['label' => __('Video'),   'icon' => 'video',    'route' => 'cikgu.video.index', 'active' => request()->routeIs('cikgu.video.*')],
        ['label' => __('Bahan'),   'icon' => 'file',     'route' => 'cikgu.bahan.index', 'active' => request()->routeIs('cikgu.bahan.*')],
        ['label' => __('Kuiz'),    'icon' => 'quiz',     'route' => 'cikgu.kuiz.index', 'active' => request()->routeIs('cikgu.kuiz.*')],
        ['label' => __('Bab'),     'icon' => 'book',     'route' => 'cikgu.bab.index', 'active' => request()->routeIs('cikgu.bab.*')],
        ['label' => app()->getLocale() === 'en' ? 'Leaderboard' : 'Kedudukan', 'icon' => 'trophy',   'route' => 'cikgu.ranking', 'active' => request()->routeIs('cikgu.ranking')],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @class(['theme-dark' => ($theme ?? 'light') === 'dark'])>
<head>
    <meta charset="utf-8">
    {{-- Tab icon. One 196px PNG serves the browser tab and the phone home screen alike. --}}
    <link rel="icon" type="image/png" href="{{ asset('images/welearn.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/welearn.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ ($theme ?? 'light') === 'dark' ? '#12181f' : '#ffffff' }}">
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
            --tp-page:#F7F6F2; --tp-surface:#fff; --tp-surface-2:#FAF9F5; --tp-hover:#F1F0E8; --tp-chip:#EFEDE6; --tp-icon:#4A5A52;
            /* Subject pill tone (shared with the student shell): pale + dark text in light mode. */
            --pill-bw:15%; --pill-bb:#fff; --pill-fw:82%; --pill-fb:#000;
            font-family:'Nunito',sans-serif; color:var(--tp-body);
        }
        /* Night mode: same token NAMES, dark values - so every .tp element and every page
           colour that was converted to a var(--tp-*) recolours for free. */
        html.theme-dark .tp {
            --tp-teal:#17907B; --tp-teal-hover:#2BB39B;
            --tp-ink:#EDF2F8; --tp-body:#C9D2DC;
            --tp-muted:#8A94A3; --tp-muted-2:#A6AFBC;
            --tp-line:rgba(255,255,255,.09); --tp-line-2:rgba(255,255,255,.14); --tp-line-3:rgba(255,255,255,.11);
            --tp-shadow:0 1px 2px rgba(0,0,0,.4), 0 8px 24px -8px rgba(0,0,0,.55);
            --tp-shadow-lift:0 2px 6px rgba(0,0,0,.45), 0 18px 44px -14px rgba(0,0,0,.6);
            --tp-input:#1E2731; --tp-active-bg:#123029; --tp-active-fg:#5EEAD4;
            --tp-page:#0E1116; --tp-surface:#1E2732; --tp-surface-2:#26313E; --tp-hover:#232D38; --tp-chip:#232D38; --tp-icon:#AEB6C2;
            --pill-bw:22%; --pill-bb:#10161C; --pill-fw:82%; --pill-fb:#fff;
        }
        .tp *,.tp *::before,.tp *::after { box-sizing:border-box; }
        .tp a { text-decoration:none; }
        /* Only plain (class-less) content links get the teal colour. Links styled as buttons,
           the avatar, nav items, etc. carry their own class colour and must not be overridden
           (a teal button link would otherwise get teal text on a teal fill - invisible). */
        .tp a:not([class]) { color:var(--tp-teal); }
        .tp a:not([class]):hover { color:var(--tp-teal-hover); }
        .tp h1,.tp h2,.tp h3 { margin:0; }
        .tp input:focus,.tp select:focus,.tp textarea:focus {
            outline:none; border-color:var(--tp-teal) !important;
            box-shadow:0 0 0 3px rgba(43,179,155,.2);
        }
        .tp button { font-family:inherit; }
        .tp-g { font-family:'Geist',sans-serif; }
        /* Heading-icon tile: the light teal reads too bright at night, so darken it (dark mode only). */
        html.theme-dark .hi-tile { background:rgba(45,212,191,.15) !important; color:#5EEAD4 !important; }

        /* Page wallpaper: a fixed, cover-sized artwork that stays put while the page scrolls, with
           --tp-page underneath as the fallback. The sidebar and content cards keep their own solid
           backgrounds, so the image shows in the space around them. Dark mode drops the photo - a
           light image behind pale text would fight it. Set here rather than inline on <body> so the
           dark override can win. */
        body.tp { background: var(--tp-page) url('{{ asset('images/gambarbg.png') }}') center center / cover no-repeat fixed; }
        html.theme-dark body.tp { background: var(--tp-page) url('{{ asset('images/DMgambarbg.png') }}?v=3') center center / cover no-repeat fixed; }

        /* Shell */
        .tp-shell { min-height:100vh; display:grid; grid-template-columns:236px 1fr; }
        .tp-side {
            background:var(--tp-surface); border-right:1px solid var(--tp-line);
            display:flex; flex-direction:column; padding:20px 14px; gap:4px;
            /* Fixed rail so it can't scroll with the page. The grid still reserves the 236px
               track; .tp-main is pinned to column 2 since a fixed item leaves the grid flow.
               overflow-y:auto lets a tall nav scroll inside the rail. */
            position:fixed; top:0; left:0; width:236px; height:100vh; overflow-y:auto;
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
            width:42px; height:42px; border-radius:50%; background:var(--tp-teal); color:#fff;
            display:grid; place-items:center; font-family:'Geist',sans-serif; font-weight:800;
            font-size:15px; flex-shrink:0; cursor:pointer; border:none; overflow:hidden;
        }
        .tp-ava img { width:100%; height:100%; object-fit:cover; }
        .tp-userbar-name { font-family:'Geist',sans-serif; font-weight:800; font-size:13.5px; color:var(--tp-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .tp-userbar-sub  { font-size:11.5px; font-weight:700; color:var(--tp-muted); }
        .tp-logout {
            width:36px; height:36px; border-radius:10px; display:grid; place-items:center;
            color:#C24936; flex-shrink:0; border:none; background:transparent; cursor:pointer;
        }
        .tp-logout:hover { background:#FDE7E0; }

        /* Main + header */
        .tp-main { grid-column:2; padding:28px 40px 48px; display:flex; flex-direction:column; gap:24px; min-width:0; max-width:clamp(1240px, 78vw, 1440px); width:100%; margin:0 auto; }
        .tp-head { display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .tp-heading { display:flex; align-items:center; gap:14px; flex:1; min-width:200px; }
        .tp-topbar { display:flex; align-items:center; gap:14px; }
        .tp-h1  { font-family:'Geist',sans-serif; font-size:24px; font-weight:800; letter-spacing:-.01em; color:var(--tp-ink); }
        .tp-hsub{ font-size:14px; color:var(--tp-muted); }
        .tp-langbar { display:flex; align-items:center; background:var(--tp-chip); border:1px solid var(--tp-line-3); border-radius:999px; padding:4px; }
        .tp-pill {
            min-height:38px; display:inline-flex; align-items:center; border:none; cursor:pointer;
            border-radius:999px; padding:0 16px; font-family:'Geist',sans-serif; font-weight:800;
            font-size:13.5px; transition:all .15s; background:transparent; color:var(--tp-muted-2);
        }
        .tp-pill.is-on { background:var(--tp-teal); color:#fff; }
        .tp-iconbtn {
            width:46px; height:46px; border-radius:50%; border:1px solid var(--tp-line-3);
            background:var(--tp-surface); cursor:pointer; display:grid; place-items:center; color:var(--tp-icon); position:relative;
        }
        .tp-iconbtn:hover { background:var(--tp-chip); }
        .tp-iconbtn svg { width:19px; height:19px; }
        [x-cloak] { display:none !important; }

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
        .tp-icon-action { width:42px; height:42px; border-radius:11px; border:none; cursor:pointer; background:transparent; color:var(--tp-muted-2); display:grid; place-items:center; flex-shrink:0; }
        .tp-icon-action:hover { background:var(--tp-chip); }
        .tp-icon-danger { color:#C24936; }
        .tp-icon-danger:hover { background:#FDE7E0; }
        .tp-icon-action:disabled { opacity:.4; cursor:not-allowed; }
        .tp-icon-action:disabled:hover { background:transparent; }

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
            background:var(--tp-surface); font-family:'Geist',sans-serif; font-weight:800; font-size:14px; color:var(--tp-ink); cursor:pointer;
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
        .tp-stat { background:var(--tp-surface); border:1px solid var(--tp-line); border-radius:16px; padding:20px 22px; display:flex; flex-direction:column; gap:6px; box-shadow:var(--tp-shadow); }
        .tp-stat-ico { width:40px; height:40px; border-radius:12px; display:grid; place-items:center; font-size:17px; }
        .tp-stat-label { font-size:13.5px; font-weight:700; color:var(--tp-muted); }
        .tp-stat-value { font-family:'Geist',sans-serif; font-size:28px; font-weight:800; color:var(--tp-ink); }

        .tp-listcard { background:var(--tp-surface); border:1px solid var(--tp-line); border-radius:16px; padding:16px 20px; display:flex; align-items:center; gap:18px; box-shadow:var(--tp-shadow); }
        .tp-listcard:hover { box-shadow:var(--tp-shadow-lift); }
        .tp-list { display:flex; flex-direction:column; gap:12px; }
        .tp-toolbar { display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap; }
        .tp-thumb { border-radius:10px; overflow:hidden; display:grid; place-items:center; color:rgba(66,118,174,.8); flex-shrink:0; }
        .tp-empty { background:var(--tp-surface); border:1px dashed rgba(46,44,80,.2); border-radius:20px; padding:56px 24px; display:flex; flex-direction:column; align-items:center; gap:10px; text-align:center; }
        .tp-panelform { background:var(--tp-surface); border:1px solid var(--tp-line); border-radius:18px; padding:24px; display:flex; flex-direction:column; gap:16px; box-shadow:var(--tp-shadow); }
        /* An outlined button rather than plain text, matching the back links on the admin side -
           going back looks and behaves the same wherever you are, and it reads as something to
           press instead of drifting into the heading above it. */
        .tp-back {
            align-self:flex-start; display:inline-flex; align-items:center; gap:8px;
            min-height:40px; cursor:pointer; border-radius:11px; border:1.5px solid var(--tp-teal);
            background:var(--tp-surface); color:var(--tp-teal);
            font-family:'Geist',sans-serif; font-weight:800; font-size:13px; padding:0 14px;
        }
        .tp-back:hover { background:var(--tp-active-bg); color:var(--tp-teal); }
        .tp-check { width:24px; height:24px; border-radius:7px; flex-shrink:0; display:grid; place-items:center; font-size:14px; margin-top:2px; background:var(--tp-teal); color:#fff; border:2px solid var(--tp-teal); }
        .tp-check-off { width:24px; height:24px; border-radius:7px; flex-shrink:0; display:grid; place-items:center; margin-top:2px; background:var(--tp-surface); border:2px solid rgba(46,44,80,.25); }
        .tp-toggle { min-height:48px; cursor:pointer; border-radius:12px; font-family:'Geist',sans-serif; font-weight:800; font-size:14px; display:inline-flex; align-items:center; justify-content:center; gap:8px; flex:1; transition:all .15s; border:1.5px solid var(--tp-line-2); background:var(--tp-surface); color:#28293F; }
        .tp-toggle.is-on { border:none; background:var(--tp-teal); color:#fff; }
        .tp-dropzone { border:2px dashed rgba(46,44,80,.18); border-radius:14px; padding:36px; display:flex; flex-direction:column; align-items:center; gap:8px; text-align:center; background:var(--tp-surface-2); cursor:pointer; transition:border-color .15s, background .15s; }
        .tp-dropzone:hover { border-color:var(--tp-teal); }
        /* Without this, crossing onto a child fires dragleave and the highlight flickers. */
        .tp-dropzone > * { pointer-events:none; }
        /* While a file is held over it, so it is obvious the drop will land. */
        .tp-dropzone.is-dragging { border-color:var(--tp-teal); background:var(--tp-active-bg); }
        .tp-dropzone:focus-visible { outline:none; border-color:var(--tp-teal); box-shadow:0 0 0 3px rgba(43,179,155,.2); }
        .tp-checkrow { background:var(--tp-surface); border:1px solid var(--tp-line); border-radius:18px; padding:20px 24px; display:flex; align-items:flex-start; gap:14px; box-shadow:var(--tp-shadow); cursor:pointer; }
        .tp-typecard { background:var(--tp-surface); border:1px solid var(--tp-line); border-radius:18px; padding:26px; display:flex; flex-direction:column; gap:14px; box-shadow:var(--tp-shadow); cursor:pointer; text-decoration:none; }
        .tp-typecard:hover { box-shadow:var(--tp-shadow-lift); transform:translateY(-2px); }
        .tp-typeopt { border:1.5px solid var(--tp-line-2); background:var(--tp-surface); border-radius:14px; padding:16px 18px; display:flex; flex-direction:column; gap:6px; align-items:flex-start; text-align:left; cursor:pointer; text-decoration:none; }
        .tp-typeopt-head { display:flex; align-items:center; gap:10px; }
        /* Quiz builder answer-option row. Kept as a class (not inline) because an Alpine
           :style string would replace the whole style attribute and drop display:flex. */
        .tp-optrow { border:1.5px solid rgba(46,44,80,.1); border-radius:13px; padding:10px 14px; display:flex; align-items:center; gap:12px; background:var(--tp-surface); transition:all .12s; }
        .tp-optrow.is-correct { border-color:#17907B; background:#E6F5F1; }
        /* Read-only answer option in the "Lihat Soalan" preview modal. */
        .tp-optview { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:10px; border:1px solid rgba(46,44,80,.08); background:var(--tp-input); }
        .tp-optview.is-correct { border-color:#17907B; background:#E6F5F1; }
        .tp-optview-badge { width:24px; height:24px; flex-shrink:0; border-radius:50%; display:grid; place-items:center; font-family:'Geist',sans-serif; font-weight:800; font-size:11.5px; background:#EDECE4; color:#8B8AA3; }
        .tp-optview.is-correct .tp-optview-badge { background:#17907B; color:#fff; }
        .tp-typeopt:hover { border-color:var(--tp-teal); }
        .tp-typeopt.is-on { border-color:var(--tp-teal); background:var(--tp-active-bg); }
        .tp-formwrap { display:flex; flex-direction:column; gap:20px; max-width:860px; }
        .tp-error { font-size:13px; font-weight:700; color:#C24936; }
        .tp-row { display:flex; align-items:center; gap:16px; padding:15px 22px; border-bottom:1px solid rgba(46,44,80,.05); }
        .tp-row:hover { background:var(--tp-surface-2); }
        .tp-row:last-child { border-bottom:none; }

        /* Brand + close button at the top of the drawer; the buttons only show on mobile. */
        .tp-side-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
        .tp-burger { display:none; align-items:center; justify-content:center; width:40px; height:40px; border-radius:11px; border:1px solid var(--tp-line-2); background:var(--tp-surface); color:var(--tp-ink); cursor:pointer; flex-shrink:0; }
        .tp-burger:hover { background:var(--tp-hover); }
        /* Header hamburger that opens the drawer (mobile only) + the drawer backdrop - matches the student portal. */
        .tp-burger-open { display:none; align-items:center; justify-content:center; width:48px; height:48px; flex-shrink:0; border-radius:50%; border:1px solid var(--tp-line-2); background:var(--tp-surface); color:var(--tp-icon); cursor:pointer; }
        .tp-burger-open:hover { background:var(--tp-hover); }
        .tp-backdrop { display:none; position:fixed; inset:0; background:rgba(20,24,20,.45); z-index:55; }

        @media (max-width:900px) {
            .tp-shell { grid-template-columns:1fr; }
            /* Sidebar slides in from the left as a drawer (exactly like the student portal). */
            .tp-side { width:284px !important; top:0 !important; bottom:0 !important; height:auto !important; z-index:60; transform:translateX(-100%); transition:transform .25s ease; box-shadow:0 0 44px rgba(20,24,20,.28); }
            .tp-side.is-open { transform:translateX(0); }
            .tp-backdrop { display:block; }
            .tp-burger { display:flex; }                                  /* close (X) inside the drawer */
            .tp-burger-open { display:flex; width:38px; height:38px; margin-right:auto; }  /* hamburger in the header */
            .tp-main { grid-column:auto; padding:20px; }
            .tp-stats { grid-template-columns:1fr; }
            /* Controls become the full-width top header row; the heading drops below it. */
            .tp-head { gap:14px; }
            .tp-topbar { order:-1; flex:1 1 100%; gap:8px; }
            /* Heading sized + spaced to match the student "Selamat datang" home heading. */
            .tp-heading { flex:1 1 100%; min-width:0; margin-top:8px; }
            .tp-heading .hi-tile { width:40px !important; height:40px !important; border-radius:12px !important; }
            .tp-heading .hi-tile img { width:24px !important; height:24px !important; }
            .tp-heading .hi-tile svg { width:22px !important; height:22px !important; }
            .tp-heading .hi-tile > span { font-size:22px !important; }
            .tp-heading .tp-h1 { font-size:21px !important; }
            .tp-heading .tp-hsub { font-size:13px !important; }
            .tp-langbar { padding:2px; }
            .tp-pill { min-height:30px; padding:4px 10px; font-size:11px; }
            .tp-iconbtn { width:38px !important; height:38px !important; }
            .tp-iconbtn svg { width:17px !important; height:17px !important; }
            /* Year / Subject / Chapter filter: keep the dropdowns on one line, shrunk. */
            /* Row 1 = the three filters (a real basis so they fill the row); row 2 = Clear (left) +
               Video Baru button (right), sharing one line below the filters. */
            .ysf-form-cikgu { flex-wrap:wrap !important; column-gap:8px !important; row-gap:12px !important; align-items:flex-end; margin-bottom:-10px; }
            .ysf-form-cikgu .tp-field { flex:1 1 30% !important; min-width:0 !important; gap:4px !important; }
            .ysf-form-cikgu .tp-label { font-size:11px !important; }
            .ysf-form-cikgu .ss-wrap { display:block !important; width:100% !important; min-width:0 !important; }
            .ysf-form-cikgu .ss-trigger { min-height:38px !important; font-size:12px !important; padding:0 30px 0 12px !important; }
            .ysf-form-cikgu .ss-chevron { width:14px !important; height:14px !important; }
            .ysf-form-cikgu select.tp-filter-select { min-width:0 !important; width:100% !important; min-height:38px !important; font-size:12px !important; }
            .ysf-form-cikgu > .ysf-reset { flex:0 0 auto !important; margin-right:auto !important; margin-left:0 !important; min-height:38px !important; font-size:12.5px !important; }
            .ysf-form-cikgu > .tp-newbtn-wrap { flex:0 0 auto !important; display:flex; justify-content:flex-end; margin-left:auto !important; }
            .ysf-form-cikgu > .tp-newbtn-wrap .tp-btn { min-height:38px !important; padding:0 16px !important; font-size:12.5px !important; }
            .ysf-form-cikgu > .tp-newbtn-wrap .tp-btn svg { width:14px !important; height:14px !important; }
            /* List cards (video etc.): wrap so the action buttons drop below instead of overlapping. */
            .tp-listcard { flex-wrap:wrap; row-gap:12px; padding:14px 16px; }
            .tp-listcard:has(.tp-listactions) > button { width:104px !important; height:66px !important; }
            .tp-listactions { flex:1 1 100% !important; gap:10px; }
            .tp-listactions .tp-btn-ghost { min-height:34px !important; padding:0 12px !important; font-size:12px !important; }
            .tp-listactions .tp-btn-ghost svg { width:14px !important; height:14px !important; }
            .tp-listactions .tp-icon-action { width:34px !important; height:34px !important; }
            .tp-listactions .tp-icon-action svg { width:16px !important; height:16px !important; }
        }
        @media (prefers-reduced-motion:reduce){ .tp * { transition:none !important; } }
    </style>
</head>

<body class="tp" style="margin:0;">
<div class="tp-shell" x-data="{ navOpen: false }">
    {{-- Backdrop behind the mobile drawer (tap to close). Only ever visible on mobile. --}}
    <div class="tp-backdrop" x-show="navOpen" x-transition.opacity x-cloak @click="navOpen = false"></div>

    {{-- SIDEBAR --}}
    <aside class="tp-side" :class="{ 'is-open': navOpen }">
        <div class="tp-side-top">
            <a href="{{ $user->homeRoute() }}" class="tp-brand" title="WeLearn">
                <img src="{{ asset('images/welearn1.png') }}" alt="WeLearn">
                <span style="display:flex;flex-direction:column">
                    <span class="tp-brand-name">WeLearn</span>
                    <span class="tp-brand-sub">{{ __('Portal Cikgu') }}</span>
                </span>
            </a>
            <button type="button" class="tp-burger" @click="navOpen = false" aria-label="{{ __('Tutup menu') }}">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
            </button>
        </div>

        @foreach ($nav as $item)
            <a href="{{ route($item['route']) }}" @class(['tp-nav', 'is-active' => $item['active']])>
                <x-icon :name="$item['icon']" />
                {{ $item['label'] }}
            </a>
        @endforeach

        <div style="margin-top:auto"></div>

        <div class="tp-userbar">
            <a href="{{ route('profile.edit') }}" class="tp-ava" title="{{ __('Profil') }}">@if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="">@else{{ $user->initials() }}@endif</a>
            <a href="{{ route('profile.edit') }}" style="display:flex;flex-direction:column;min-width:0;flex:1">
                <span class="tp-userbar-name">{{ __('Cikgu :name', ['name' => $user->username]) }}</span>
                <span class="tp-userbar-sub">{{ __('Guru') }}</span>
            </a>
            <x-logout-confirm id="tp-logout-form">
                <button type="button" class="tp-logout" title="{{ __('Log Keluar') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </x-logout-confirm>
        </div>
    </aside>

    {{-- MAIN --}}
    <main class="tp-main">
        <div class="tp-head">
            {{-- Heading (icon + title). On mobile it drops below the controls row. --}}
            <div class="tp-heading">
                @if ($headingIcon)
                    {{-- Accepts an image filename (from public/images), an icon name (line icon), or an emoji. --}}
                    <span class="hi-tile" style="width:48px;height:48px;border-radius:14px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0;align-self:flex-start" aria-hidden="true">@if (\Illuminate\Support\Str::endsWith($headingIcon, ['.png', '.jpg', '.jpeg', '.svg', '.webp']))<img src="{{ asset('images/'.$headingIcon) }}" alt="" style="width:30px;height:30px;object-fit:contain" />@elseif (preg_match('/^[a-z0-9-]+$/', $headingIcon))<x-icon :name="$headingIcon" style="width:24px;height:24px" />@else<span style="font-size:26px;line-height:1">{{ $headingIcon }}</span>@endif</span>
                @endif
                <div style="display:flex;flex-direction:column;gap:2px;flex:1;min-width:0">
                    <h1 class="tp-h1">{{ $heading }}</h1>
                    @if ($sub)
                        <span class="tp-hsub">{{ $sub }}</span>
                    @endif
                </div>
            </div>

            {{-- Controls: hamburger (mobile) + language pill + night mode + notifications. On mobile
                 this is the full-width top header row (matches the student portal). --}}
            <div class="tp-topbar">
                <button type="button" class="tp-burger-open" @click="navOpen = true" aria-label="{{ __('Menu') }}">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>

                <div class="tp-langbar">
                    <a href="{{ route('locale.switch', 'ms') }}" @class(['tp-pill', 'is-on' => $current === 'ms'])>BM</a>
                    <a href="{{ route('locale.switch', 'en') }}" @class(['tp-pill', 'is-on' => $current === 'en'])>EN</a>
                </div>

                @php($isDark = ($theme ?? 'light') === 'dark')
                <a href="{{ route('theme.switch', $isDark ? 'light' : 'dark') }}" class="tp-iconbtn" title="{{ $isDark ? __('Mod Terang') : __('Mod Malam') }}">
                    <x-icon :name="$isDark ? 'sun' : 'moon'" class="h-[19px] w-[19px]" />
                </a>

                <x-notif-bell :notifications="$recentNotifications"
                              :unread="$unreadNotifications"
                              :meta="$notifMeta"
                              :all-url="route('cikgu.notifikasi')"
                              :mark-read-url="route('cikgu.notifikasi.baca')" />
            </div>
        </div>

        <x-flash />

        {{ $slot }}
    </main>
</div>

@stack('scripts')
</body>
</html>
