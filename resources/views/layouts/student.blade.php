@php
    $user = auth()->user();
    $grades = \App\Models\Grade::orderBy('level')->get();
    $activeGrade = \App\Support\ActiveGrade::for($user);
    // The Tahun the current page is actually showing: a ?tahun= in the URL (as the Subject page
    // honours) wins, else the session's active Tahun. Keeps the switcher in sync with the page.
    $browseLevel = request()->integer('tahun') ?: $activeGrade?->level;
    $current = app()->getLocale();
    $isDark = ($theme ?? 'light') === 'dark';

    // New-content notifications for this student's school + Tahun. The feed is shared, so the unread
    // count is everything newer than the student's last-read marker; each recent row gets a
    // transient read_at so the bell can highlight what is new.
    $notifReadAt = $user->content_notifications_read_at;
    $notifQuery = \App\Models\ContentNotification::scopeFor($user->school_id, $user->grade_id);
    $unreadNotifications = $notifReadAt
        ? (clone $notifQuery)->where('created_at', '>', $notifReadAt)->count()
        : (clone $notifQuery)->count();
    $recentNotifications = (clone $notifQuery)->latest()->limit(8)->get()->each(function ($n) use ($notifReadAt) {
        $n->read_at = ($notifReadAt && $n->created_at->lte($notifReadAt)) ? $n->created_at : null;
    });
    $notifMeta = [
        \App\Models\ContentNotification::TYPE_VIDEO    => ['icon' => 'video', 'tint' => '#E4EEF9', 'fg' => '#2E6CA8', 'text' => __(':actor memuat naik video ":title"')],
        \App\Models\ContentNotification::TYPE_MATERIAL => ['icon' => 'file',  'tint' => '#DCF2EE', 'fg' => '#0F7A68', 'text' => __(':actor memuat naik bahan ":title"')],
        \App\Models\ContentNotification::TYPE_QUIZ     => ['icon' => 'quiz',  'tint' => '#FEF0CE', 'fg' => '#8A6A12', 'text' => __(':actor menambah kuiz ":title"')],
    ];

    // Exact sidebar icons ported verbatim from the WeLearn prototype.
    $icons = [
        'home' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>',
        'save' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>',
        'offline' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 16.5v.75A2.75 2.75 0 0 0 5.75 20h12.5A2.75 2.75 0 0 0 21 17.25v-.75M12 3v12m0 0 4.5-4.5M12 15l-4.5-4.5"/></svg>',
        'trophy' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8m-4-4v4m-6-17h12v5a6 6 0 0 1-12 0V4Z"/><path d="M6 6H4a2 2 0 0 0 0 4h2M18 6h2a2 2 0 0 1 0 4h-2"/></svg>',
        'quiz' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6M6.75 3h10.5A1.75 1.75 0 0 1 19 4.75v16.5L12 18l-7 3.25V4.75A1.75 1.75 0 0 1 6.75 3Z"/><path d="M9 7.5h6"/></svg>',
    ];

    $nav = [
        ['route' => 'belajar.index',   'active' => request()->routeIs('belajar.index'),                                          'icon' => 'home',    'label' => __('Utama')],
        ['route' => 'subjek.index',    'active' => request()->routeIs('subjek.index', 'belajar.subjek', 'bab.show'),             'icon' => 'book',    'label' => __('Subjek')],
        ['route' => 'kegemaran.index', 'active' => request()->routeIs('kegemaran.index'),                                        'icon' => 'save',    'label' => __('Kegemaran')],
        ['route' => 'simpanan.index',  'active' => request()->routeIs('simpanan.index'),                                         'icon' => 'offline', 'label' => __('Simpanan Offline')],
        ['route' => 'ranking.index',   'active' => request()->routeIs('ranking.index'),                                          'icon' => 'trophy',  'label' => app()->getLocale() === 'en' ? 'Leaderboard' : 'Kedudukan'],
        ['route' => 'kuiz-saya.index', 'active' => request()->routeIs('kuiz-saya.index', 'kuiz.intro', 'kuiz.jawab', 'keputusan.show'), 'icon' => 'quiz', 'label' => __('Kuiz')],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ $current }}" @class(['theme-dark' => $isDark])>
<head>
    <meta charset="utf-8">
    {{-- Tab icon. One 196px PNG serves the browser tab and the phone home screen alike. --}}
    <link rel="icon" type="image/png" href="{{ asset('images/welearn.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/welearn.png') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="{{ $isDark ? '#12181f' : '#ffffff' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' | WeLearn' : 'WeLearn' }}</title>

    {{-- app.css supplies the self-hosted Geist + Nunito @font-face; the prototype styles below win. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Student surface tokens (warm cream light + a night-mode ramp). Same NAMES are aliased
           in app-layout, so pages shared with the teacher shell theme there too. */
        .wl {
            --wl-page:#FAF5EE; --wl-surface:#fff; --wl-surface-2:#FBFAF6; --wl-input:#F6F5F0; --wl-chip:#ECEBF4;
            --wl-ink:#28293F; --wl-body:#2D2F44; --wl-muted:#8B8AA3; --wl-muted-2:#6C6F87;
            --wl-line:rgba(46,44,80,.08); --wl-line-2:rgba(46,44,80,.1); --wl-line-3:rgba(46,44,80,.15);
            --wl-hover:#F1F0E8; --wl-active-bg:#E6F5F1; --wl-active-fg:#0F7A68; --wl-teal:#17907B;
            /* Subject pill tone. Light = pale (mix into white) + dark text; a subject sets its own
               colour and mixes it with these bases. Dark mode flips to a dark tint + light text. */
            --pill-bw:15%; --pill-bb:#fff; --pill-fw:82%; --pill-fb:#000;
        }
        html.theme-dark .wl {
            --wl-page:#0E1116; --wl-surface:#1E2732; --wl-surface-2:#26313E; --wl-input:#1E2731; --wl-chip:#232D38;
            --wl-ink:#EDF2F8; --wl-body:#C9D2DC; --wl-muted:#8A94A3; --wl-muted-2:#A6AFBC;
            --wl-line:rgba(255,255,255,.09); --wl-line-2:rgba(255,255,255,.12); --wl-line-3:rgba(255,255,255,.16);
            --wl-hover:#232D38; --wl-active-bg:#123029; --wl-active-fg:#5EEAD4; --wl-teal:#17907B;
            --pill-bw:22%; --pill-bb:#10161C; --pill-fw:82%; --pill-fb:#fff;
        }

        /* ── WeLearn prototype styles, ported verbatim ── */
        /* The page wallpaper. `fixed` keeps it sized to the viewport and still while the page
           scrolls: the artwork is portrait, so letting it stretch to the document height would
           smear it on long pages. --wl-page stays underneath as the fallback colour. */
        body {
            margin: 0;
            background: var(--wl-page) url('{{ asset('images/gambarbg.png') }}') center center / cover no-repeat fixed;
            font-family: 'Nunito', sans-serif;
            color: var(--wl-body);
        }
        /* Night mode keeps its dark ramp - a pale wallpaper behind it would undo the point of it
           and leave the light text on the cards fighting the background. */
        html.theme-dark body { background: var(--wl-page) url('{{ asset('images/DMgambarbg.png') }}?v=3') center center / cover no-repeat fixed; }
        .wl a { text-decoration: none; }
        /* Only plain content links get teal. Links with a class - the sidebar nav, the back button -
           carry their own colour, so the nav can read grey like the teacher rail instead of being
           overridden to teal by a bare `.wl a` rule. Mirrors `.tp a:not([class])` on that side. */
        .wl a:not([class]) { color: #17907B; }
        .wl a:not([class]):hover { color: #2BB39B; }
        .wl input:focus, .wl select:focus { outline: none; border-color: #17907B !important; box-shadow: 0 0 0 3px rgba(43,179,155,.25); }
        @media (prefers-reduced-motion: reduce) { .wl * { animation: none !important; transition: none !important; } }
        /* Heading-icon tile (e.g. the wave / book / heart): darken the light teal at night and
           lift the glyph colour so it stays legible. */
        html.theme-dark .hi-tile { background:rgba(45,212,191,.15) !important; color:#5EEAD4 !important; }

        /* Favourite heart reveal (matches the prototype). */
        .fav-btn { opacity: 0; transition: opacity .15s ease-out, transform .15s ease-out; }
        .vid-card:hover .fav-btn, .fav-btn[data-fav="true"] { opacity: 1; }
        .fav-btn:hover { transform: scale(1.1); }

        /* Card + control hovers (prototype style-hover / style-active, as real CSS). */
        .vid-card { transition: transform .15s ease-out, box-shadow .15s ease-out; }
        .vid-card:hover { transform: translateY(-3px); box-shadow: 0 10px 24px rgba(46,44,80,.09) !important; }
        .wl-lift { transition: transform .15s ease-out, box-shadow .15s ease-out; }
        .wl-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 24px var(--wl-line-2) !important; }
        .wl-row-lift { transition: transform .15s ease-out, box-shadow .15s ease-out; }
        .wl-row-lift:hover { transform: translateY(-2px); box-shadow: 0 8px 20px var(--wl-line) !important; }
        .wl-btn-primary { transition: background .15s, transform .1s; }
        .wl-btn-primary:hover { background: #2BB39B !important; }
        .wl-btn-primary:active { transform: scale(.98); }
        .wl-btn-secondary { transition: background .15s, transform .1s; }
        .wl-btn-secondary:hover { background: #F4F8FC !important; }
        .wl-btn-secondary:active { transform: scale(.98); }
        .wl-icbtn { transition: background .15s; }
        .wl-icbtn:hover { background: #EFEDF9 !important; }
        .wl-profile { transition: transform .15s; }
        .wl-profile:hover { transform: scale(1.06); }
        .wl-logout:hover { background: #FDE7E0 !important; }
        /* Outlined teal button - matches the Cikgu/Admin .tp-back so "go back" looks the same
           app-wide and reads as something to press instead of drifting into the heading. */
        .wl-back {
            align-self:flex-start; display:inline-flex; align-items:center; gap:8px;
            min-height:36px; cursor:pointer; border-radius:10px; border:1.5px solid var(--wl-teal);
            background:var(--wl-surface); color:var(--wl-teal); text-decoration:none;
            font-family:'Geist',sans-serif; font-weight:700; font-size:12px; padding:0 12px;
            transition:background .15s, color .15s;
        }
        .wl-back:hover { background:var(--wl-active-bg); color:var(--wl-teal); }
        .wl-acct-row { transition: background .15s; }
        .wl-acct-row:hover { background: #FAF8F3 !important; }

        /* ── Sidebar - matched to the Cikgu/Admin wide labelled rail (236px, icon + label rows). ── */
        .wl-brand { display:flex; align-items:center; gap:10px; padding:4px 8px 16px; text-decoration:none; }
        .wl-brand img { width:42px; height:42px; object-fit:contain; display:block; }
        .wl-brand-name { font-family:'Geist',sans-serif; font-weight:800; font-size:16px; color:var(--wl-ink); }
        .wl-brand-sub  { font-size:11.5px; font-weight:700; color:var(--wl-muted); }

        .wl-nav {
            display:flex; align-items:center; gap:12px; width:100%; min-height:48px;
            border-radius:12px; padding:0 14px; text-decoration:none;
            font-family:'Geist',sans-serif; font-weight:800; font-size:14.5px;
            background:transparent; color:var(--wl-muted-2); transition:all .15s;
        }
        .wl-nav:hover { background:var(--wl-hover); color:var(--wl-ink); }
        .wl-nav.is-active { background:var(--wl-active-bg); color:var(--wl-active-fg); }
        .wl-nav.is-active:hover { background:var(--wl-active-bg); }
        .wl-nav svg { width:21px; height:21px; flex-shrink:0; }

        .wl-userbar { display:flex; align-items:center; gap:10px; padding:10px 8px; border-top:1px solid var(--wl-line); }
        .wl-ava {
            width:42px; height:42px; border-radius:50%; background:#17907B; color:#fff;
            display:grid; place-items:center; font-family:'Geist',sans-serif; font-weight:800;
            font-size:15px; flex-shrink:0; text-decoration:none; overflow:hidden;
        }
        .wl-ava img { width:100%; height:100%; object-fit:cover; }
        .wl-userbar-name { font-family:'Geist',sans-serif; font-weight:800; font-size:13.5px; color:var(--wl-ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .wl-userbar-sub  { font-size:11.5px; font-weight:700; color:var(--wl-muted); }
        .wl-logout { width:36px; height:36px; border-radius:10px; display:grid; place-items:center; color:#C24936; flex-shrink:0; border:none; background:transparent; cursor:pointer; }
        .wl-logout:hover { background:#FDE7E0; }

        /* Sidebar top row (brand + close button). The close (X) and the header hamburger only show
           on mobile, where the sidebar becomes a slide-in drawer over the content. */
        .wl-side-top { display:flex; align-items:center; justify-content:space-between; gap:8px; }
        .wl-burger { display:none; align-items:center; justify-content:center; width:40px; height:40px; border-radius:11px; border:1px solid var(--wl-line-2); background:var(--wl-surface); color:var(--wl-ink); cursor:pointer; flex-shrink:0; }
        .wl-burger:hover { background:var(--wl-hover); }
        /* Hamburger that opens the drawer; lives in the page header. */
        .wl-burger-open { display:none; align-items:center; justify-content:center; width:48px; height:48px; flex-shrink:0; border-radius:50%; border:1px solid var(--wl-line-2); background:var(--wl-surface); color:#4A5A52; cursor:pointer; }
        .wl-burger-open:hover { background:var(--wl-hover); }
        /* Dim backdrop behind the open drawer. */
        .wl-backdrop { position:fixed; inset:0; background:rgba(20,24,20,.45); z-index:55; }

        @media (max-width: 900px) {
            .wl-shell { grid-template-columns: 1fr !important; }
            /* Sidebar slides in from the left as a drawer over the content. Anchor it to BOTH the
               top and bottom of the viewport (not a vh/dvh height) so it always spans exactly the
               visible area on every device - 100vh is taller than the visible area on phones, which
               pushed the user bar off-screen and forced a scroll. */
            .wl-side { width:284px !important; top:0 !important; bottom:0 !important; height:auto !important; z-index:60; transform:translateX(-100%); transition:transform .25s ease; box-shadow:0 0 44px rgba(20,24,20,.28); }
            .wl-side.is-open { transform:translateX(0); }
            .wl-burger { display:flex; }        /* close (X) inside the drawer */
            .wl-burger-open { display:flex; }    /* hamburger in the header */
            .wl-main { grid-column:auto !important; padding: 20px 16px 40px !important; }

            /* Header: hamburger + Tahun + language + theme + bell on one row; the search box drops
               to its own row below. Everything shrinks so all five fit even on narrow phones. */
            /* The header carries an inline gap:14px, so this must be !important to win. */
            .wl-topbar { gap: 6px !important; }
            .wl-topbar > form[role="search"] { order: 5; flex: 1 1 100% !important; max-width: none !important; min-width: 100% !important; margin-top: 14px; min-height: 42px !important; padding: 0 14px !important; }
            .wl-topbar > form[role="search"] input { min-height: 38px !important; }
            /* Size the placeholder directly - the app.css anti-zoom rule pins the input itself to
               16px, and a placeholder otherwise inherits that. */
            .wl-topbar > form[role="search"] input::placeholder { font-size: 13px !important; opacity: 1; }
            .wl-topbar > form[role="search"] input::-webkit-input-placeholder { font-size: 13px !important; }
            /* Hamburger stays far left; the auto margin pushes the rest to the right edge. */
            .wl-burger-open { width: 38px; height: 38px; margin-right: auto; }
            .wl-topbar .wl-icbtn { width: 38px !important; height: 38px !important; }
            .wl-topbar .wl-icbtn svg { width: 17px !important; height: 17px !important; }
            .wl-topbar .wl-year-select { min-height: 34px !important; padding: 0 25px 0 10px !important; font-size: 11px !important; background-position: right 9px center !important; background-size: 11px !important; }
            /* When the select is enhanced into a styled trigger, shrink that too. */
            .wl-topbar .ss-wrap:has(> select.wl-year-select) .ss-trigger { min-height: 34px !important; padding: 0 11px !important; font-size: 11px !important; }
            .wl-topbar .wl-langsw { padding: 2px; }
            .wl-topbar .wl-langsw a { min-width: 28px !important; min-height: 30px !important; padding: 4px 8px !important; font-size: 11px !important; }

            /* Hero "Sambung Menonton" card: the two-column layout is too cramped on phones, so stack
               the thumbnail on top and the text below, with tighter padding and a smaller title. */
            .hero-card { grid-template-columns: 1fr !important; }
            .hero-card .hero-thumb { order: -1; min-height: 180px !important; }
            .hero-card > div { padding: 20px !important; }
            .hero-card .hero-title { font-size: 18px !important; }
            /* Pills (subject / Bab / Trending) a touch smaller. */
            .hero-card .hero-subpill, .hero-card .hero-babpill, .hero-card > div > div:first-child > span { font-size: 11px !important; padding: 4px 10px !important; }
            /* Hero buttons: side by side (no wrap) and smaller. */
            .hero-card .hero-cta { flex-wrap: nowrap !important; gap: 8px !important; }
            .hero-card .hero-cta > * { min-height: 40px !important; font-size: 12.5px !important; padding: 0 14px !important; }

            /* Section headings (Sambung menonton / Paling popular / etc.) a little smaller. */
            .wl-main h2 { font-size: 18px !important; }

            /* Page headings: smaller icon tile and text on phones (inline sizes, so !important). */
            .wl-main .hi-tile { width: 40px !important; height: 40px !important; border-radius: 12px !important; }
            .wl-main .hi-tile img { width: 24px !important; height: 24px !important; }
            .wl-main .hi-tile svg { width: 22px !important; height: 22px !important; }
            .wl-main h1 { font-size: 21px !important; }
            .wl-main h1 + span { font-size: 13px !important; }

            /* Subject category labels (Mata Pelajaran Teras / Wajib / Tambahan / Program ...). */
            .wl-catlabel { font-size: 11px !important; letter-spacing: .1em !important; }

            /* Subject cards (Mata Pelajaran Teras): smaller/less tall on phones. */
            .wl-subjgrid { gap: 12px !important; }
            .wl-subjgrid > a { padding: 14px !important; min-height: 104px !important; }
            .wl-subjgrid > a svg, .wl-subjgrid > a img { width: 22px !important; height: 22px !important; }
            .wl-subjgrid > a span { font-size: 13.5px !important; }
            .wl-subjgrid > a > div { margin-bottom: 4px !important; }

            /* Home video rows scroll sideways instead of cramming four per line. */
            .wl-cardrow { display:flex !important; flex-wrap:nowrap; overflow-x:auto; gap:16px; padding-bottom:6px; scroll-snap-type:x proximity; -webkit-overflow-scrolling:touch;
                          /* Hide the scrollbar and fade the trailing edge, like the continue rail. */
                          scrollbar-width:none; -ms-overflow-style:none;
                          -webkit-mask-image:linear-gradient(90deg,#000 calc(100% - 40px),transparent 100%);
                          mask-image:linear-gradient(90deg,#000 calc(100% - 40px),transparent 100%); }
            .wl-cardrow::-webkit-scrollbar { display:none; }
            /* Match the continue-watching rail cards: ~280px wide with a peek of the next. */
            .wl-cardrow > * { flex:0 0 280px !important; max-width:84%; scroll-snap-align:start; }
        }
    </style>
</head>

<body class="wl">
<div class="wl-shell" x-data="{ navOpen: false }" style="min-height:100vh;display:grid;grid-template-columns:236px 1fr">
    {{-- Backdrop behind the mobile drawer (tap to close). Hidden on desktop - navOpen only ever
         toggles from the mobile-only hamburger / close button. --}}
    <div class="wl-backdrop" x-show="navOpen" x-transition.opacity x-cloak @click="navOpen = false"></div>

    {{-- ── SIDEBAR (wide labelled rail; a slide-in drawer on mobile) ── --}}
    <aside class="wl-side" :class="{ 'is-open': navOpen }" style="background:var(--wl-surface);border-right:1px solid var(--wl-line);display:flex;flex-direction:column;padding:20px 14px;gap:4px;position:fixed;top:0;left:0;width:236px;height:100vh;overflow-y:auto;box-sizing:border-box">
        <div class="wl-side-top">
            <a href="{{ route('belajar.index') }}" class="wl-brand" title="WeLearn">
                <img src="{{ asset('images/welearn1.png') }}" alt="WeLearn">
                <span style="display:flex;flex-direction:column">
                    <span class="wl-brand-name">WeLearn</span>
                    <span class="wl-brand-sub">{{ __('Portal Murid') }}</span>
                </span>
            </a>
            <button type="button" class="wl-burger" @click="navOpen = false" aria-label="{{ __('Tutup menu') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
            </button>
        </div>

        @foreach ($nav as $n)
            <a href="{{ route($n['route']) }}" @class(['wl-nav', 'is-active' => $n['active']]) aria-current="{{ $n['active'] ? 'page' : 'false' }}">
                {!! $icons[$n['icon']] !!}
                {{ $n['label'] }}
            </a>
        @endforeach

        <div style="margin-top:auto"></div>

        <div class="wl-userbar">
            <a href="{{ route('profile.edit') }}" class="wl-ava" title="{{ __('Profil') }}">@if ($user->avatarUrl())<img src="{{ $user->avatarUrl() }}" alt="">@else{{ $user->initials() }}@endif</a>
            <a href="{{ route('profile.edit') }}" style="display:flex;flex-direction:column;min-width:0;flex:1;text-decoration:none">
                <span class="wl-userbar-name">{{ $user->username }}</span>
                <span class="wl-userbar-sub">{{ __('Murid') }}</span>
            </a>
            <x-logout-confirm id="wl-logout-form">
                <button type="button" class="wl-logout" title="{{ __('Log Keluar') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </x-logout-confirm>
        </div>
    </aside>

    {{-- ── MAIN ── --}}
    <main class="wl-main" style="min-width:0;grid-column:2;padding:28px clamp(14px, 3vw, 36px) 48px;display:flex;flex-direction:column;gap:28px;max-width:clamp(1180px, 78vw, 1440px);box-sizing:border-box;width:100%;margin:0 auto">
        {{-- HEADER --}}
        <div class="wl-topbar" style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
            {{-- Opens the drawer on mobile (hidden on desktop). --}}
            <button type="button" class="wl-burger-open" @click="navOpen = true" aria-label="{{ __('Menu') }}">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <form method="GET" action="{{ route('cari.index') }}" role="search"
                  style="display:flex;align-items:center;gap:10px;background:var(--wl-surface);border:1px solid var(--wl-line-2);border-radius:999px;padding:0 18px;min-height:48px;flex:0 1 380px;min-width:220px;margin-right:auto">
                <x-icon name="search" class="h-[18px] w-[18px]" style="color:var(--wl-muted);flex-shrink:0" />
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari video...') }}" aria-label="{{ __('Cari video') }}"
                       style="border:none;background:transparent;font-family:'Nunito',sans-serif;font-size:14.5px;color:var(--wl-body);width:100%;min-height:44px">
            </form>

            {{-- Tahun switcher - kept for real revision use, styled to match the header pills. --}}
            <select onchange="if (this.value) window.location.href = '{{ url('tahun') }}/' + this.value" class="js-styled-select wl-year-select"
                    style="min-height:48px;border:1px solid var(--wl-line-2);border-radius:999px;padding:0 38px 0 16px;-webkit-appearance:none;-moz-appearance:none;appearance:none;background:var(--wl-surface) url(&quot;data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='24'%20height='24'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%2328293F'%20stroke-width='2.5'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M6%209l6%206%206-6'/%3E%3C/svg%3E&quot;) no-repeat right 14px center;background-size:12px;font-family:'Geist',sans-serif;font-weight:700;font-size:12.5px;color:var(--wl-ink);cursor:pointer">
                @foreach ($grades as $g)
                    <option value="{{ $g->level }}" @selected($browseLevel === $g->level)>{{ $g->displayName() }}</option>
                @endforeach
            </select>

            <div class="wl-langsw" style="display:flex;background:var(--wl-chip);border:1px solid var(--wl-line-2);border-radius:999px;padding:3px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:700">
                @foreach (['ms' => 'BM', 'en' => 'EN'] as $code => $lbl)
                    <a href="{{ route('locale.switch', $code) }}" @if ($current === $code) aria-current="true" @endif
                       style="min-width:40px;min-height:34px;border-radius:999px;padding:5px 12px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;text-decoration:none;display:flex;align-items:center;justify-content:center;{{ $current === $code ? 'background:#17907B;color:#fff' : 'background:transparent;color:var(--wl-muted-2)' }}">{{ $lbl }}</a>
                @endforeach
            </div>

            <a href="{{ route('theme.switch', $isDark ? 'light' : 'dark') }}" title="{{ __('Mod Malam') }}" class="wl-icbtn"
               style="width:48px;height:48px;border-radius:50%;border:1px solid var(--wl-line-2);background:var(--wl-surface);display:grid;place-items:center;color:#4A5A52;text-decoration:none">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </a>

            <x-notif-bell :notifications="$recentNotifications" :unread="$unreadNotifications" :meta="$notifMeta"
                          :all-url="route('belajar.notifikasi')" :mark-read-url="route('belajar.notifikasi.baca')"
                          trigger-class="wl-icbtn"
                          trigger-style="width:48px;height:48px;border-radius:50%;border:1px solid var(--wl-line-2);background:var(--wl-surface);cursor:pointer;display:grid;place-items:center;color:#4A5A52" />
        </div>

        <x-flash />

        {{ $slot }}
    </main>
</div>

@stack('scripts')
</body>
</html>
