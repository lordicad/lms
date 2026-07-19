<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @class(['theme-dark' => ($theme ?? 'light') === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('WeLearn. Belajar di mana-mana, bila-bila masa.') }}</title>
    <meta name="description"
          content="Platform pembelajaran untuk sekolah rendah. Murid menonton video kelas, mencuba kuiz, dan naik ranking. Guru memuat naik rakaman kelas, bahan bantu mengajar dan kuiz.">

    {{--
        The landing page is deliberately self-contained: its own fonts and a scoped stylesheet, no
        app.css/app.js. That keeps the WeLearn marketing look entirely isolated from the signed-in
        app (student/teacher/admin) — nothing here can shift a colour anywhere else.
    --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; }

        :root {
            --bg: #FDFDFB; --surface: #FFFFFF;
            --ink: #24312A; --muted: #5C685C; --faint: #879182;
            --brand: #3E6B45; --brand-hover: #4A7D52; --brand-ink: #56793F; --brand-soft: #E7EEDA;
            --line: rgba(36,49,42,.08); --line-strong: rgba(36,49,42,.14);
            --dark-green: #24402C; --accent: #A9C97E;
            --shadow-sm: 0 4px 16px rgba(36,49,42,.05);
            --shadow-lg: 0 24px 60px rgba(36,49,42,.14);
        }

        html.theme-dark {
            --bg: #0C1410; --surface: #15221A;
            --ink: #ECF2F4; --muted: #B8C8BC; --faint: #8FA093;
            --brand: #9DC284; --brand-hover: #A9C97E; --brand-ink: #9DC284; --brand-soft: rgba(157,194,132,.14);
            --line: rgba(255,255,255,.09); --line-strong: rgba(255,255,255,.14);
            --dark-green: #101B14;
            --shadow-sm: 0 4px 16px rgba(0,0,0,.35);
            --shadow-lg: 0 24px 60px rgba(0,0,0,.5);
        }

        html { scroll-behavior: smooth; }
        body {
            margin: 0; overflow-x: hidden;
            background: var(--bg); color: var(--ink);
            font-family: 'Nunito', sans-serif; font-size: 16px; line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, .font-display { font-family: 'Geist', sans-serif; }
        a { color: var(--brand); text-decoration: none; }
        img, svg { display: block; }
        @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; scroll-behavior: auto; } }

        .wl-wrap { max-width: 1080px; margin: 0 auto; padding-left: 24px; padding-right: 24px; }
        .wl-skip { position: absolute; left: -9999px; top: 0; background: var(--brand); color: #fff; padding: 10px 16px; border-radius: 10px; z-index: 100; font-weight: 700; }
        .wl-skip:focus { left: 16px; top: 12px; }

        /* Logo */
        .wl-logo { display: inline-flex; align-items: center; gap: 10px; flex-shrink: 0; }
        .wl-logo-icon { width: 42px; height: 42px; flex-shrink: 0; display: block; }
        .wl-logo-text { font-family: 'Geist', sans-serif; font-weight: 800; font-size: 23px; letter-spacing: -.01em; line-height: 1; }
        .wl-logo-text .we { color: var(--brand); }
        .wl-logo-text .learn { color: var(--ink); }

        /* Header */
        header.wl-header { position: sticky; top: 0; z-index: 40; background: color-mix(in srgb, var(--surface) 95%, transparent); backdrop-filter: blur(8px); border-bottom: 1px solid var(--line); }
        .wl-nav { display: flex; align-items: center; gap: 10px; padding-top: 10px; padding-bottom: 10px; flex-wrap: nowrap; min-width: 0; }
        .wl-nav-links { display: flex; gap: 2px; margin-left: auto; font-family: 'Geist', sans-serif; font-size: 14px; font-weight: 600; white-space: nowrap; }
        .wl-nav-links a { padding: 10px; border-radius: 10px; color: var(--muted); transition: background .15s, color .15s; }
        .wl-nav-links a:hover { background: var(--brand-soft); color: var(--brand-ink); }
        .wl-actions { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }

        /* Language pill (server-rendered, still a real toggle) */
        .wl-langpill { display: flex; background: var(--brand-soft); border-radius: 999px; padding: 3px; font-family: 'Geist', sans-serif; font-size: 13px; font-weight: 700; }
        .wl-langpill a { min-width: 44px; min-height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; padding: 6px 14px; color: var(--brand-ink); transition: background .15s, color .15s; }
        .wl-langpill a[aria-current="true"] { background: var(--brand); color: #fff; }

        .wl-iconbtn { width: 40px; height: 40px; flex-shrink: 0; border-radius: 12px; border: 1px solid var(--line-strong); background: var(--surface); color: var(--muted); cursor: pointer; display: grid; place-items: center; transition: background .15s; }
        .wl-iconbtn:hover { background: var(--brand-soft); color: var(--brand-ink); }
        .wl-iconbtn svg { width: 20px; height: 20px; }

        .wl-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 44px; padding: 0 16px; border-radius: 12px; font-family: 'Geist', sans-serif; font-weight: 700; font-size: 14px; white-space: nowrap; transition: background .15s, transform .15s, color .15s; }
        .wl-btn-outline { border: 1.5px solid var(--brand); color: var(--brand); }
        .wl-btn-outline:hover { background: var(--brand-soft); color: var(--brand-ink); }
        .wl-btn-solid { background: var(--brand); color: #fff; box-shadow: 0 2px 8px rgba(30,77,43,.25); }
        .wl-btn-solid:hover { background: var(--brand-hover); color: #fff; }
        .wl-btn-lg { min-height: 52px; padding: 0 28px; border-radius: 14px; font-size: 17px; }
        .wl-btn-solid.wl-btn-lg { box-shadow: 0 6px 18px rgba(30,77,43,.3); }
        .wl-btn-solid.wl-btn-lg:hover { transform: translateY(-2px); }
        .wl-btn-outline.wl-btn-lg:hover { transform: none; }

        /* Hero */
        .wl-hero { background: linear-gradient(180deg, var(--surface) 0%, var(--bg) 100%); }
        .wl-hero-grid { display: grid; grid-template-columns: 1.05fr .95fr; gap: 56px; align-items: center; padding: 72px 0 64px; }
        .wl-eyebrow { display: inline-flex; align-self: flex-start; align-items: center; gap: 8px; background: var(--brand-soft); color: var(--brand-ink); border-radius: 999px; padding: 8px 16px; font-family: 'Geist', sans-serif; font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .wl-eyebrow .dot { width: 8px; height: 8px; border-radius: 50%; background: #6D9C55; }
        .wl-h1 { margin: 0; font-size: 56px; line-height: 1.08; font-weight: 800; letter-spacing: -.02em; color: var(--ink); }
        .wl-h1 .accent { color: var(--brand-ink); }
        .wl-lead { margin: 0; font-size: 19px; line-height: 1.6; color: var(--muted); max-width: 520px; }
        .wl-note { margin: 4px 0 0; font-size: 14px; color: var(--faint); }

        .wl-card { background: var(--surface); border: 1px solid var(--line); border-radius: 24px; box-shadow: var(--shadow-lg); padding: 24px; }
        .wl-card-label { font-family: 'Geist', sans-serif; font-size: 13px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: var(--faint); }
        .wl-chips { display: flex; flex-wrap: wrap; gap: 9px; }
        .wl-chip { display: inline-flex; align-items: center; gap: 7px; border-radius: 10px; padding: 7px 13px; font-weight: 700; font-size: 13.5px; }

        /* Sections */
        .wl-section { padding: 72px 0; }
        .wl-center { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
        .wl-kicker { font-family: 'Geist', sans-serif; font-size: 16px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; color: var(--brand-ink); }
        .wl-h2 { margin: 0; font-size: 36px; font-weight: 800; letter-spacing: -.01em; color: var(--ink); }

        .wl-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
        .wl-step { background: var(--surface); border-radius: 20px; border: 1px solid var(--line); box-shadow: var(--shadow-sm); padding: 28px; display: flex; flex-direction: column; gap: 14px; transition: transform .15s, box-shadow .15s; }
        .wl-step:hover { transform: translateY(-4px); box-shadow: 0 12px 28px rgba(36,49,42,.1); }
        .wl-step-icon { width: 52px; height: 52px; border-radius: 16px; display: grid; place-items: center; }
        .wl-step-icon svg { width: 24px; height: 24px; }
        .wl-step-n { font-family: 'Geist', sans-serif; font-weight: 800; font-size: 13px; color: var(--brand-ink); }
        .wl-h3 { margin: 0; font-size: 20px; font-weight: 700; color: var(--ink); }
        .wl-p { margin: 0; font-size: 15.5px; line-height: 1.6; color: var(--muted); }

        /* Content totals */
        .wl-total { border-radius: 18px; border: 1px solid var(--line); padding: 26px; display: flex; flex-direction: column; gap: 8px; min-height: 130px; transition: transform .15s; }
        .wl-total:hover { transform: translateY(-3px); }
        .wl-total .em { font-size: 28px; }
        .wl-total .num { font-family: 'Geist', sans-serif; font-weight: 900; font-size: 34px; color: var(--ink); }
        .wl-total .name { font-family: 'Geist', sans-serif; font-weight: 800; font-size: 16px; color: var(--ink); }
        .wl-total .sub { font-size: 13px; color: var(--faint); }
        .wl-total.t-vid { background: #E3EAF3; }
        .wl-total.t-mat { background: #E8EFDE; }
        .wl-total.t-quiz { background: #F1EBDD; }
        /* Dark mode: the light tint would wash out the (now light) text — darken the card like the design does. */
        html.theme-dark .wl-total.t-vid, html.theme-dark .wl-total.t-mat, html.theme-dark .wl-total.t-quiz { background: #1C2A21; }

        /* Teachers band */
        .wl-band { background: color-mix(in srgb, var(--surface) 60%, var(--bg)); }
        html.theme-dark .wl-band { background: var(--dark-green); }
        .wl-band-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: center; padding: 64px 0; }
        .wl-ticks { margin: 6px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 12px; font-size: 15.5px; color: var(--muted); }
        .wl-ticks li { display: flex; gap: 12px; align-items: flex-start; }
        .wl-ticks .tick { color: var(--brand-ink); font-weight: 800; }

        /* Bakat scorecard (marketing illustration) */
        .wl-score { background: var(--dark-green); border: 1px solid rgba(255,255,255,.08); border-radius: 22px; padding: 28px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 20px 50px rgba(36,64,44,.3); }
        .wl-score-top { display: flex; align-items: baseline; justify-content: space-between; }
        .wl-score-top .lbl { font-family: 'Geist', sans-serif; font-weight: 800; font-size: 15px; color: #fff; }
        .wl-score-top .val { font-family: 'Geist', sans-serif; font-weight: 900; font-size: 44px; color: var(--accent); }
        .wl-score-top .val small { font-size: 18px; color: #7E9878; font-weight: 800; }
        .wl-bar-row { display: flex; flex-direction: column; gap: 6px; }
        .wl-bar-head { display: flex; justify-content: space-between; font-size: 13px; }
        .wl-bar-head .k { font-weight: 700; color: #DCE8D2; }
        .wl-bar-head .v { color: var(--accent); font-family: 'Geist', sans-serif; font-weight: 800; }
        .wl-bar { height: 8px; border-radius: 999px; background: rgba(255,255,255,.14); overflow: hidden; }
        .wl-bar span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #8CB56E, #A9C97E); }
        .wl-score-foot { margin: 0; font-size: 12px; color: #8FA98A; line-height: 1.5; }

        /* Final CTA */
        .wl-cta { background: var(--dark-green); border-radius: 28px; padding: 64px 48px; display: flex; flex-direction: column; align-items: center; gap: 18px; text-align: center; box-shadow: 0 20px 50px rgba(36,64,44,.3); }
        .wl-cta h2 { margin: 0; font-size: 40px; font-weight: 800; color: #fff; letter-spacing: -.01em; }
        .wl-cta p { margin: 0; font-size: 17px; color: #D9E5CE; max-width: 520px; line-height: 1.6; }
        .wl-cta .wl-btn-solid { background: #fff; color: var(--brand); box-shadow: none; }
        .wl-cta .wl-btn-solid:hover { background: #fff; transform: translateY(-2px); }
        .wl-cta .wl-btn-outline { border-color: rgba(255,255,255,.5); color: #fff; }
        .wl-cta .wl-btn-outline:hover { background: rgba(255,255,255,.12); color: #fff; }

        footer.wl-footer { border-top: 1px solid var(--line); padding: 28px 0; background: var(--surface); }
        .wl-footer-row { display: flex; align-items: center; gap: 20px; }
        .wl-footer-row .copy { font-size: 13.5px; color: var(--faint); }

        .wl-flex { display: flex; align-items: center; }
        .wl-gap-14 { gap: 14px; }
        .wl-col { display: flex; flex-direction: column; }
        .wl-gap-22 { gap: 22px; }
        .wl-mt-40 { margin-top: 40px; }
        .wl-mt-28 { margin-top: 28px; }
        .wl-baseline { display: flex; align-items: baseline; gap: 14px; }

        @media (max-width: 1020px) {
            .wl-nav-links { display: none; }
            .wl-logo-text { font-size: 20px; }
            .wl-hero-grid { grid-template-columns: 1fr; gap: 36px; padding: 56px 0 48px; }
            .wl-band-grid { grid-template-columns: 1fr; gap: 36px; }
            .wl-h1 { font-size: 44px; }
            .wl-h2 { font-size: 30px; }
        }
        @media (max-width: 760px) {
            .wl-nav { flex-wrap: wrap; row-gap: 8px; }
            .wl-grid-3 { grid-template-columns: 1fr; }
            .wl-cta { padding: 48px 24px; }
            .wl-cta h2 { font-size: 30px; }
            .wl-h1 { font-size: 36px; }
        }
    </style>
</head>

<body>
    <a href="#kandungan" class="wl-skip">{{ __('Terus ke kandungan') }}</a>

    @php
        // The real WeLearn tree-and-book mark, embedded as a data URI so it needs no separate asset
        // in the split-deployment docroot — it travels inside the page. Defined once; header and
        // footer both reference it.
        $wlLogo = 'data:image/png;base64,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';
    @endphp

    <!-- HEADER -->
    <header class="wl-header">
        <div class="wl-wrap wl-nav">
            <a href="{{ url('/') }}" class="wl-logo" aria-label="WeLearn">
                <img src="{{ $wlLogo }}" class="wl-logo-icon" alt="" width="42" height="42">
                <span class="wl-logo-text"><span class="we">We</span><span class="learn">Learn</span></span>
            </a>

            <nav class="wl-nav-links" aria-label="{{ __('Navigasi utama') }}">
                <a href="#ciri">{{ __('Cara Ia Berfungsi') }}</a>
                <a href="#subjek">{{ __('Kandungan') }}</a>
                <a href="#cikgu">{{ __('Untuk Cikgu') }}</a>
            </nav>

            <div class="wl-actions">
                {{-- Real BM/EN toggle: two links to the locale route, styled as the design's pill. --}}
                @php($lang = app()->getLocale())
                <nav class="wl-langpill" aria-label="{{ __('Tukar bahasa') }}">
                    <a href="{{ route('locale.switch', 'ms') }}" @if ($lang === 'ms') aria-current="true" @endif>BM<span class="wl-skip">Bahasa Melayu</span></a>
                    <a href="{{ route('locale.switch', 'en') }}" @if ($lang === 'en') aria-current="true" @endif>EN<span class="wl-skip">English</span></a>
                </nav>

                {{-- Real light/dark toggle: link to the theme route; icon shows the mode you switch TO. --}}
                @php($isDark = ($theme ?? 'light') === 'dark')
                <a href="{{ route('theme.switch', $isDark ? 'light' : 'dark') }}" class="wl-iconbtn"
                   role="button" aria-pressed="{{ $isDark ? 'true' : 'false' }}"
                   aria-label="{{ $isDark ? __('Mod Terang') : __('Mod Gelap') }}">
                    @if ($isDark)
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
                    @endif
                </a>

                <a href="{{ route('login') }}" class="wl-btn wl-btn-outline">{{ __('Log Masuk') }}</a>
                <a href="{{ route('register') }}" class="wl-btn wl-btn-solid">{{ __('Daftar') }}</a>
            </div>
        </div>
    </header>

    <main id="kandungan">
        <!-- HERO -->
        <section class="wl-hero">
            <div class="wl-wrap wl-hero-grid">
                <div class="wl-col" style="gap:22px">
                    <span class="wl-eyebrow"><span class="dot"></span>{{ __('Belajar · Membesar · Berjaya') }}</span>

                    <h1 class="wl-h1">{{ __('Belajar di mana-mana,') }}<br>{{ __('bila-bila masa.') }}</h1>

                    <p class="wl-lead">{{ __('Video pelajaran, bahan dan kuiz daripada cikgu anda — semuanya tersusun mengikut Subjek dan Tahun, sedia ditonton seperti perkhidmatan penstriman kegemaran anda.') }}</p>

                    <div class="wl-flex wl-gap-14" style="flex-wrap:wrap">
                        <a href="{{ route('register') }}" class="wl-btn wl-btn-solid wl-btn-lg">{{ __('Daftar Sekarang') }}</a>
                        <a href="#ciri" class="wl-btn wl-btn-outline wl-btn-lg">{{ __('Lihat Cara Ia Berfungsi') }}</a>
                    </div>

                    <p class="wl-note">{{ __('Murid mendaftar sendiri · Cikgu memerlukan kod sekolah') }}</p>
                </div>

                {{-- Real core subjects from the database, styled as the design's chip grid. --}}
                <div class="wl-card wl-col wl-gap-22" style="align-self:center;max-width:440px;justify-self:center">
                    <span class="wl-card-label">{{ __('Mata Pelajaran Teras') }}</span>

                    <div class="wl-chips">
                        @foreach ($terasSubjects as $subject)
                            <span class="wl-chip" style="background: rgb({{ $subject->rgb }} / .13); color: rgb({{ $subject->rgb }});">
                                <span aria-hidden="true">{{ $subject->icon }}</span>{{ $subject->displayName() }}
                            </span>
                        @endforeach
                    </div>

                    <div style="border-top:1px solid var(--line); padding-top:14px; font-size:13px; color:var(--muted); font-weight:600;">
                        {{ __('dan :count subjek lagi merentas 5 kategori Kurikulum Persekolahan 2027.', ['count' => $moreSubjectCount]) }}
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section id="ciri" class="wl-section">
            <div class="wl-wrap wl-col" style="gap:40px">
                <div class="wl-center">
                    <span class="wl-kicker">{{ __('Cara Ia Berfungsi') }}</span>
                    <h2 class="wl-h2">{{ __('Tiga langkah mudah') }}</h2>
                </div>

                <div class="wl-grid-3">
                    <div class="wl-step">
                        <div class="wl-step-icon" style="background:#E8EFDE; color:#3E6B45;">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"/></svg>
                        </div>
                        <div class="wl-flex" style="gap:10px">
                            <span class="wl-step-n">01</span>
                            <h3 class="wl-h3">{{ __('Tonton') }}</h3>
                        </div>
                        <p class="wl-p">{{ __('Pilih subjek anda dan tonton video pelajaran daripada cikgu — sambung dari tempat anda berhenti.') }}</p>
                    </div>

                    <div class="wl-step">
                        <div class="wl-step-icon" style="background:#F1EBDD; color:#9A6B2F;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </div>
                        <div class="wl-flex" style="gap:10px">
                            <span class="wl-step-n">02</span>
                            <h3 class="wl-h3">{{ __('Cuba Kuiz') }}</h3>
                        </div>
                        <p class="wl-p">{{ __('Jawab kuiz semak-sendiri selepas setiap pelajaran dan lihat keputusan serta-merta.') }}</p>
                    </div>

                    <div class="wl-step">
                        <div class="wl-step-icon" style="background:#E3EAF3; color:#3B5BA5;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6m12 5h1.5a2.5 2.5 0 0 0 0-5H18M6 4h12v5a6 6 0 0 1-12 0V4Z"/><path d="M9 19h6M8 22h8M12 15v4"/></svg>
                        </div>
                        <div class="wl-flex" style="gap:10px">
                            <span class="wl-step-n">03</span>
                            <h3 class="wl-h3">{{ __('Naik Ranking') }}</h3>
                        </div>
                        <p class="wl-p">{{ __('Kumpul mata dari setiap kuiz dan naik Papan Ranking bersama rakan Tahun anda.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CONTENT TOTALS -->
        <section id="subjek" class="wl-section" style="padding-top:0">
            <div class="wl-wrap wl-col wl-mt-28" style="gap:28px">
                <div class="wl-baseline" style="flex-wrap:wrap">
                    <h2 class="wl-h2" style="font-size:28px">{{ __('Kandungan Pembelajaran') }}</h2>
                    <span style="font-size:15px; color:var(--faint);">{{ __('Tersusun mengikut Subjek → Tahun → Bab') }}</span>
                </div>

                <div class="wl-grid-3">
                    <div class="wl-total t-vid">
                        <span class="em">🎬</span>
                        <span class="num">{{ number_format($lessonCount) }}</span>
                        <span class="name">{{ __('Video Pengajaran') }}</span>
                        <span class="sub">{{ __('Merentas semua subjek dan tahun') }}</span>
                    </div>
                    <div class="wl-total t-mat">
                        <span class="em">📚</span>
                        <span class="num">{{ number_format($materialCount) }}</span>
                        <span class="name">{{ __('Bahan Pembelajaran') }}</span>
                        <span class="sub">{{ __('PDF, slaid dan lembaran kerja') }}</span>
                    </div>
                    <div class="wl-total t-quiz">
                        <span class="em">✍️</span>
                        <span class="num">{{ number_format($quizCount) }}</span>
                        <span class="name">{{ __('Kuiz') }}</span>
                        <span class="sub">{{ __('Kuiz interaktif semak-sendiri') }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOR TEACHERS -->
        <section id="cikgu" class="wl-band">
            <div class="wl-wrap wl-band-grid">
                <div class="wl-col" style="gap:18px">
                    <span class="wl-kicker">{{ __('Untuk Cikgu') }}</span>
                    <h2 class="wl-h2" style="font-size:38px">{{ __('Studio kandungan anda — kemas, jelas, profesional.') }}</h2>
                    <p class="wl-p" style="font-size:17px; max-width:480px;">{{ __('Susun Bab, muat naik video (dari peranti atau YouTube), lampirkan bahan, dan bina kuiz semak-sendiri. Skor Bakat yang telus menunjukkan impak pengajaran anda.') }}</p>

                    <ul class="wl-ticks">
                        <li><span class="tick">✓</span><span>{{ __('Muat naik dari peranti atau pautkan saluran YouTube anda sendiri') }}</span></li>
                        <li><span class="tick">✓</span><span>{{ __('Lampirkan bahan — PDF, DOCX, PPTX dan lembaran kerja') }}</span></li>
                        <li><span class="tick">✓</span><span>{{ __('Bina kuiz interaktif yang menyemak sendiri') }}</span></li>
                        <li><span class="tick">✓</span><span>{{ __('Statistik per-pelajaran dan Skor Bakat yang telus') }}</span></li>
                    </ul>
                </div>

                {{-- Illustration of the real Bakat scorecard teachers get. Static on the marketing page. --}}
                <div class="wl-score">
                    <div class="wl-score-top">
                        <span class="lbl">{{ __('Skor Bakat') }}</span>
                        <span class="val">86<small>/100</small></span>
                    </div>
                    <div class="wl-col" style="gap:14px">
                        @foreach ([[__('Penglibatan'), '88'], [__('Kualiti'), '82'], [__('Hasil Pembelajaran'), '90'], [__('Keluasan'), '84']] as [$label, $val])
                            <div class="wl-bar-row">
                                <div class="wl-bar-head"><span class="k">{{ $label }}</span><span class="v">{{ $val }}</span></div>
                                <div class="wl-bar"><span style="width: {{ $val }}%;"></span></div>
                            </div>
                        @endforeach
                    </div>
                    <p class="wl-score-foot">{{ __('Skor telus berdasarkan 4 sub-skor yang boleh dilihat. Bukan kotak hitam.') }}</p>
                </div>
            </div>
        </section>

        <!-- FINAL CTA -->
        <section class="wl-section" style="padding-bottom:80px">
            <div class="wl-wrap">
                <div class="wl-cta">
                    <h2>{{ __('Sedia untuk mula belajar?') }}</h2>
                    <p>{{ __('Murid mendaftar sendiri. Cikgu, dapatkan kod sekolah anda dan mula berkongsi hari ini.') }}</p>
                    <div class="wl-flex wl-gap-14" style="margin-top:8px">
                        <a href="{{ route('register') }}" class="wl-btn wl-btn-solid wl-btn-lg">{{ __('Daftar Sekarang') }}</a>
                        <a href="{{ route('login') }}" class="wl-btn wl-btn-outline wl-btn-lg">{{ __('Log Masuk') }}</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- FOOTER -->
    <footer class="wl-footer">
        <div class="wl-wrap wl-footer-row">
            <a href="{{ url('/') }}" class="wl-logo" aria-label="WeLearn">
                <img src="{{ $wlLogo }}" class="wl-logo-icon" alt="" width="42" height="42">
                <span class="wl-logo-text"><span class="we">We</span><span class="learn">Learn</span></span>
            </a>
            <span class="copy">{{ __('© 2026 WeLearn — Sistem Pengurusan Pembelajaran. Belajar · Membesar · Berjaya.') }}</span>
        </div>
    </footer>
</body>
</html>
