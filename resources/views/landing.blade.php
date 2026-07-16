<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" @class(['theme-dark' => ($theme ?? 'light') === 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('LMS MOE. Belajar di mana-mana, bila-bila masa.') }}</title>
    <meta name="description"
          content="Platform pembelajaran untuk sekolah rendah. Murid menonton video kelas, mencuba kuiz, dan naik ranking. Guru memuat naik rakaman kelas, bahan bantu mengajar dan kuiz.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-bg font-sans">
    <a href="#kandungan" class="skip-link">{{ __('Terus ke kandungan') }}</a>

    <header class="border-b border-line bg-surface">
        <nav class="mx-auto flex h-[72px] max-w-6xl items-center justify-between px-4 sm:px-6" aria-label="{{ __('Navigasi utama') }}">
            <span class="flex items-center gap-2">
                <span class="rounded-control bg-brand px-2 py-1 text-sm font-extrabold text-on-brand">LMS</span>
                <span class="text-lg font-extrabold text-ink">MOE</span>
            </span>

            <div class="flex items-center gap-2">
                <x-lang-toggle class="mr-1 hidden sm:inline-flex" />
                <x-theme-toggle class="mr-1 hidden sm:inline-flex" />
                <a href="{{ route('login') }}" class="btn-ghost btn-sm">{{ __('Log Masuk') }}</a>
                <a href="{{ route('register') }}" class="btn-primary btn-sm">{{ __('Daftar') }}</a>
            </div>
        </nav>
    </header>

    <main id="kandungan">
        {{-- Hero. Split layout, not centred: headline left, the real subject cards right. --}}
        <section class="mx-auto grid max-w-6xl items-center gap-10 px-4 pb-16 pt-12 sm:px-6 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:pt-24">
            <div>
                <h1 class="text-4xl font-extrabold leading-[1.1] text-ink md:text-5xl lg:text-6xl">
                    {{ __('Belajar di mana-mana,') }}<br>{{ __('bila-bila masa.') }}
                </h1>

                <p class="mt-5 max-w-prose text-lg text-ink-2">
                    {{ __('Tonton semula rakaman kelas cikgu, muat turun bahan, dan cuba kuiz. Semuanya disusun ikut subjek, tahun dan bab.') }}
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-primary">{{ __('Daftar Sekarang') }}</a>
                    <a href="{{ route('login') }}" class="btn-secondary">{{ __('Log Masuk') }}</a>
                </div>
            </div>

            {{-- Not a mock screenshot: the real core subjects, straight from the database. --}}
            <div class="card card-pad">
                <p class="text-xs font-extrabold uppercase tracking-widest text-ink-2">{{ __('Mata Pelajaran Teras') }}</p>

                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($terasSubjects as $subject)
                        <li class="chip bg-subject-wash text-subject-ink" style="--sc: {{ $subject->rgb }}">
                            <span aria-hidden="true">{{ $subject->icon }}</span>
                            {{ $subject->displayName() }}
                        </li>
                    @endforeach
                </ul>

                <p class="mt-5 border-t border-line pt-4 text-sm text-ink-2">
                    {{ __('dan :count subjek lagi merentas 5 kategori Kurikulum Persekolahan 2027.', ['count' => $moreSubjectCount]) }}
                </p>
            </div>
        </section>

        {{-- How it works. Three steps, told as verbs, no "Langkah 1 / 2 / 3" labels. --}}
        <section class="border-y border-line bg-surface py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <h2 class="text-2xl font-extrabold text-ink md:text-3xl">{{ __('Tiga langkah mudah') }}</h2>

                <ol class="mt-8 grid gap-6 md:grid-cols-3">
                    <li class="flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-control bg-brand-soft text-brand">
                            <x-icon name="play" class="h-6 w-6" />
                        </span>
                        <div>
                            <h3 class="text-lg font-extrabold text-ink">{{ __('Tonton') }}</h3>
                            <p class="mt-1 text-ink-2">{{ __('Video kelas cikgu, terus di dalam platform ini.') }}</p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-control bg-brand-soft text-brand">
                            <x-icon name="quiz" class="h-6 w-6" />
                        </span>
                        <div>
                            <h3 class="text-lg font-extrabold text-ink">{{ __('Cuba Kuiz') }}</h3>
                            <p class="mt-1 text-ink-2">{{ __('Jawab soalan dan dapat markah serta-merta.') }}</p>
                        </div>
                    </li>

                    <li class="flex gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-control bg-brand-soft text-brand">
                            <x-icon name="trophy" class="h-6 w-6" />
                        </span>
                        <div>
                            <h3 class="text-lg font-extrabold text-ink">{{ __('Naik Ranking') }}</h3>
                            <p class="mt-1 text-ink-2">{{ __('Kumpul mata dan lihat kedudukan anda dalam Tahun anda.') }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        {{-- For teachers. Full-width band, a different layout family from the sections above. --}}
        <section class="py-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="card card-pad grid gap-8 lg:grid-cols-2 lg:items-center lg:p-10">
                    <div>
                        <h2 class="text-2xl font-extrabold text-ink md:text-3xl">{{ __('Untuk cikgu') }}</h2>

                        <p class="mt-4 max-w-prose text-ink-2">
                            {{ __('Muat naik rakaman kelas terus dari peranti anda, atau tampal pautan YouTube daripada akaun anda sendiri. Murid menontonnya tanpa meninggalkan platform.') }}
                        </p>

                        <ul class="mt-6 space-y-3">
                            <li class="flex items-start gap-3">
                                <x-icon name="check" class="mt-1 h-5 w-5 shrink-0 text-success" />
                                <span class="text-ink">{{ __('Susun kandungan ikut Subjek, Tahun dan Bab.') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <x-icon name="check" class="mt-1 h-5 w-5 shrink-0 text-success" />
                                <span class="text-ink">{{ __('Lampirkan bahan bantu mengajar: slaid, PDF, lembaran kerja.') }}</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <x-icon name="check" class="mt-1 h-5 w-5 shrink-0 text-success" />
                                <span class="text-ink">{{ __('Bina kuiz interaktif yang menyemak jawapan sendiri.') }}</span>
                            </li>
                        </ul>
                    </div>

                    <dl class="grid grid-cols-2 gap-4">
                        <div class="rounded-card bg-surface-2 p-5">
                            <dt class="text-sm font-bold text-ink-2">{{ __('Video pelajaran') }}</dt>
                            <dd class="mt-1 text-3xl font-extrabold text-ink">{{ $lessonCount }}</dd>
                        </div>
                        <div class="rounded-card bg-surface-2 p-5">
                            <dt class="text-sm font-bold text-ink-2">{{ __('Kuiz tersedia') }}</dt>
                            <dd class="mt-1 text-3xl font-extrabold text-ink">{{ $quizCount }}</dd>
                        </div>
                        <div class="col-span-2 rounded-card bg-brand-soft p-5">
                            <dt class="text-sm font-bold text-brand">{{ __('Tahun 1 hingga Tahun 6') }}</dt>
                            <dd class="mt-1 text-ink">
                                {{ __('Setiap subjek disusun ikut bab supaya murid tahu di mana mereka berada.') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section class="border-t border-line bg-surface py-16">
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
                <h2 class="text-2xl font-extrabold text-ink md:text-3xl">{{ __('Sedia untuk bermula?') }}</h2>
                <p class="mt-3 text-ink-2">{{ __('Murid mendaftar sendiri. Cikgu perlukan kod pendaftaran daripada sekolah.') }}</p>

                <div class="mt-7 flex flex-wrap justify-center gap-3">
                    <a href="{{ route('register') }}" class="btn-primary">{{ __('Daftar Sekarang') }}</a>
                    <a href="{{ route('login') }}" class="btn-secondary">{{ __('Log Masuk') }}</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-line py-8">
        <div class="mx-auto max-w-6xl px-4 text-sm text-ink-2 sm:px-6">
            <p>{{ __('LMS MOE. Platform pembelajaran untuk sekolah rendah.') }}</p>
        </div>
    </footer>
</body>
</html>
