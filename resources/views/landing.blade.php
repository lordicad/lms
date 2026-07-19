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
        .wl-logo-badge { width: 40px; height: 40px; border-radius: 12px; background: var(--brand); display: grid; place-items: center; flex-shrink: 0; box-shadow: 0 2px 8px rgba(30,77,43,.25); }
        .wl-logo-badge svg { width: 20px; height: 20px; }
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
        // One place defines the WeLearn mark; header and footer reuse it. Swap in a real logo image
        // here (an <img src=...>) and it changes in both spots.
        $wlPlay = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z" fill="#fff"/></svg>';
    @endphp

    <!-- HEADER -->
    <header class="wl-header">
        <div class="wl-wrap wl-nav">
            <a href="{{ url('/') }}" class="wl-logo" aria-label="WeLearn">
                <span class="wl-logo-badge">{!! $wlPlay !!}</span>
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
                <span class="wl-logo-badge">{!! $wlPlay !!}</span>
                <span class="wl-logo-text"><span class="we">We</span><span class="learn">Learn</span></span>
            </a>
            <span class="copy">{{ __('© 2026 WeLearn — Sistem Pengurusan Pembelajaran. Belajar · Membesar · Berjaya.') }}</span>
        </div>
    </footer>
</body>
</html>
