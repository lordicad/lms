@php
    $user = auth()->user();
    $grades = \App\Models\Grade::orderBy('level')->get();
    $activeGrade = \App\Support\ActiveGrade::for($user);
    $current = app()->getLocale();
    $isDark = ($theme ?? 'light') === 'dark';

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
        ['route' => 'simpanan.index',  'active' => request()->routeIs('simpanan.index'),                                         'icon' => 'offline', 'label' => __('Offline')],
        ['route' => 'ranking.index',   'active' => request()->routeIs('ranking.index'),                                          'icon' => 'trophy',  'label' => __('Ranking')],
        ['route' => 'kuiz-saya.index', 'active' => request()->routeIs('kuiz-saya.index', 'kuiz.intro', 'kuiz.jawab', 'keputusan.show'), 'icon' => 'quiz', 'label' => __('Kuiz')],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ $current }}" @class(['theme-dark' => $isDark])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
        }
        html.theme-dark .wl {
            --wl-page:#0E1116; --wl-surface:#171E27; --wl-surface-2:#1E2731; --wl-input:#1E2731; --wl-chip:#232D38;
            --wl-ink:#EDF2F8; --wl-body:#C9D2DC; --wl-muted:#8A94A3; --wl-muted-2:#A6AFBC;
            --wl-line:rgba(255,255,255,.09); --wl-line-2:rgba(255,255,255,.12); --wl-line-3:rgba(255,255,255,.16);
        }

        /* ── WeLearn prototype styles, ported verbatim ── */
        /* The page wallpaper. `fixed` keeps it sized to the viewport and still while the page
           scrolls: the artwork is portrait, so letting it stretch to the document height would
           smear it on long pages. --wl-page stays underneath as the fallback colour. */
        body {
            margin: 0;
            background: var(--wl-page) url('{{ asset('images/gambar4.jpg') }}') center center / cover no-repeat fixed;
            font-family: 'Nunito', sans-serif;
            color: var(--wl-body);
        }
        /* Night mode keeps its dark ramp — a pale wallpaper behind it would undo the point of it
           and leave the light text on the cards fighting the background. */
        html.theme-dark body { background: var(--wl-page); }
        .wl a { color: #17907B; text-decoration: none; }
        .wl a:hover { color: #2BB39B; }
        .wl input:focus, .wl select:focus { outline: none; border-color: #17907B !important; box-shadow: 0 0 0 3px rgba(43,179,155,.25); }
        @media (prefers-reduced-motion: reduce) { .wl * { animation: none !important; transition: none !important; } }

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
        .wl-back { transition: color .15s; }
        .wl-back:hover { color: #17907B !important; }
        .wl-acct-row { transition: background .15s; }
        .wl-acct-row:hover { background: #FAF8F3 !important; }

        @media (max-width: 720px) {
            .wl-shell { grid-template-columns: 76px 1fr !important; }
            .wl-main { padding: 20px 16px 40px !important; }
        }
    </style>
</head>

<body class="wl">
<div class="wl-shell" style="min-height:100vh;display:grid;grid-template-columns:96px 1fr">
    {{-- ── SIDEBAR ── --}}
    <aside style="background:var(--wl-surface);border-right:1px solid var(--wl-line);display:flex;flex-direction:column;align-items:center;padding:12px 8px;gap:4px;position:sticky;top:0;height:100vh;box-sizing:border-box">
        <a href="{{ route('belajar.index') }}" title="WeLearn" style="width:46px;height:46px;flex-shrink:0;border-radius:14px;overflow:hidden;display:block;background:var(--wl-surface)">
            <img src="{{ asset('images/welearn1.png') }}" alt="WeLearn" style="width:46px;height:46px;object-fit:contain;display:block">
        </a>
        <div style="height:6px"></div>

        @foreach ($nav as $n)
            <a href="{{ route($n['route']) }}" title="{{ $n['label'] }}" @if ($n['active']) aria-current="page" @endif
               style="width:70px;min-height:52px;flex-shrink:0;text-decoration:none;border-radius:16px;display:flex;flex-direction:column;gap:3px;align-items:center;justify-content:center;padding:6px 4px;{{ $n['active'] ? 'background:#DCF2EE;color:#0F7A68' : 'background:transparent;color:var(--wl-muted)' }}">
                <span style="display:block;width:22px;height:22px;margin:0 auto">{!! $icons[$n['icon']] !!}</span>
                <span style="font-family:'Geist',sans-serif;font-size:11.5px;font-weight:700">{{ $n['label'] }}</span>
            </a>
        @endforeach

        <div style="margin-top:auto"></div>

        <a href="{{ route('profile.edit') }}" title="{{ __('Profil') }}" class="wl-profile"
           style="width:44px;height:44px;flex-shrink:0;border-radius:50%;background:#17907B;color:#fff;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;text-decoration:none">{{ $user->initials() }}</a>
    </aside>

    {{-- ── MAIN ── --}}
    <main class="wl-main" style="min-width:0;padding:28px 36px 48px;display:flex;flex-direction:column;gap:28px;max-width:1180px;box-sizing:border-box;width:100%;margin:0 auto">
        {{-- HEADER --}}
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
            <form method="GET" action="{{ route('cari.index') }}" role="search"
                  style="display:flex;align-items:center;gap:10px;background:var(--wl-surface);border:1px solid var(--wl-line-2);border-radius:999px;padding:0 18px;min-height:48px;flex:0 1 380px;min-width:220px;margin-right:auto">
                <span style="color:var(--wl-muted);font-size:15px">🔍</span>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari video...') }}" aria-label="{{ __('Cari video') }}"
                       style="border:none;background:transparent;font-family:'Nunito',sans-serif;font-size:14.5px;color:var(--wl-body);width:100%;min-height:44px">
            </form>

            {{-- Tahun switcher — kept for real revision use, styled to match the header pills. --}}
            <select onchange="if (this.value) window.location.href = '{{ url('tahun') }}/' + this.value"
                    style="min-height:48px;border:1px solid var(--wl-line-2);border-radius:999px;padding:0 38px 0 16px;-webkit-appearance:none;-moz-appearance:none;appearance:none;background:var(--wl-surface) url(&quot;data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='24'%20height='24'%20viewBox='0%200%2024%2024'%20fill='none'%20stroke='%2328293F'%20stroke-width='2.5'%20stroke-linecap='round'%20stroke-linejoin='round'%3E%3Cpath%20d='M6%209l6%206%206-6'/%3E%3C/svg%3E&quot;) no-repeat right 14px center;background-size:12px;font-family:'Geist',sans-serif;font-weight:700;font-size:12.5px;color:var(--wl-ink);cursor:pointer">
                @foreach ($grades as $g)
                    <option value="{{ $g->level }}" @selected($activeGrade?->level === $g->level)>{{ $g->name }}</option>
                @endforeach
            </select>

            <div style="display:flex;background:var(--wl-chip);border-radius:999px;padding:3px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:700">
                @foreach (['ms' => 'BM', 'en' => 'EN'] as $code => $lbl)
                    <a href="{{ route('locale.switch', $code) }}" @if ($current === $code) aria-current="true" @endif
                       style="min-width:40px;min-height:34px;border-radius:999px;padding:5px 12px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;text-decoration:none;display:flex;align-items:center;justify-content:center;{{ $current === $code ? 'background:#17907B;color:#fff' : 'background:transparent;color:var(--wl-muted-2)' }}">{{ $lbl }}</a>
                @endforeach
            </div>

            <a href="{{ route('theme.switch', $isDark ? 'light' : 'dark') }}" title="{{ __('Mod Malam') }}" class="wl-icbtn"
               style="width:48px;height:48px;border-radius:14px;border:1px solid var(--wl-line-2);background:var(--wl-surface);display:grid;place-items:center;color:#4A5A52;text-decoration:none">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
            </a>

            <button title="{{ __('Notifikasi') }}" class="wl-icbtn"
                    style="width:48px;height:48px;border-radius:50%;border:1px solid var(--wl-line-2);background:var(--wl-surface);cursor:pointer;font-size:17px">🔔</button>
        </div>

        <x-flash />

        {{ $slot }}
    </main>
</div>

@stack('scripts')
</body>
</html>
