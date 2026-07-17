<x-app-layout :title="__('Skor Bakat Guru')">
    <header>
        <h1 class="text-3xl font-extrabold text-ink">{{ __('Skor Bakat Guru') }}</h1>
        <p class="mt-1 max-w-prose text-ink-2">
            {{ __('Gambaran keseluruhan guru, penyumbang teratas, dan kandungan paling berkesan.') }}
        </p>
    </header>

    {{--
        ======================================================================
        Guru
        ======================================================================
    --}}
    <section class="mt-8">
        <h2 class="text-xl font-extrabold text-ink">{{ __('Guru') }}</h2>

        <dl class="mt-4 grid gap-4 sm:grid-cols-3">
            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="users" class="h-5 w-5" />
                    {{ __('Jumlah guru') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($totalTeachers) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="check-circle" class="h-5 w-5 text-success" />
                    {{ __('Aktif') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($activeCount) }}</dd>
            </div>

            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="x-circle" class="h-5 w-5 text-ink-2" />
                    {{ __('Tidak aktif') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($inactiveCount) }}</dd>
            </div>
        </dl>

        {{-- Carries the contributor filter through, so filtering here does not reset that one. --}}
        <form method="GET" action="{{ route('admin.bakat') }}" class="mt-6 flex flex-wrap items-end gap-3">
            @if ($contribSubject) <input type="hidden" name="p_subjek" value="{{ $contribSubject }}"> @endif
            @if ($contribGrade) <input type="hidden" name="p_tahun" value="{{ $contribGrade }}"> @endif

            <div>
                <label for="subjek" class="label mb-1">{{ __('Subjek') }}</label>
                <select id="subjek" name="subjek" class="input min-h-[44px] py-2" onchange="this.form.submit()">
                    <option value="">{{ __('Semua subjek') }}</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->slug }}" @selected($subjectSlug === $subject->slug)>{{ $subject->displayName() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="tahun" class="label mb-1">{{ __('Tahun') }}</label>
                <select id="tahun" name="tahun" class="input min-h-[44px] py-2" onchange="this.form.submit()">
                    <option value="">{{ __('Semua tahun') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->level }}" @selected($gradeLevel === $grade->level)>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>

            <noscript><button type="submit" class="btn-secondary btn-sm">{{ __('Tapis') }}</button></noscript>

            @if ($subjectSlug || $gradeLevel)
                <a href="{{ route('admin.bakat', array_filter(['p_subjek' => $contribSubject, 'p_tahun' => $contribGrade])) }}"
                   class="btn-ghost btn-sm">{{ __('Kosongkan') }}</a>
            @endif
        </form>

        <div class="mt-4">
            @if ($teachers->isEmpty())
                <x-empty icon="users" :title="__('Tiada guru untuk dipaparkan')"
                         :text="__('Tiada guru yang sepadan dengan tapisan ini.')" />
            @else
                <div class="card overflow-x-auto p-2">
                    <table class="w-full min-w-[60rem] text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-ink-2">
                                <th class="px-3 py-2 font-semibold">{{ __('Nama Guru') }}</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Subjek') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Video') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Bahan') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Kuiz') }}</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Status') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Tindakan') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($teachers as $teacher)
                                @php($taught = $subjectsByTeacher[$teacher->id] ?? collect())
                                <tr class="border-b border-line/60 last:border-0 hover:bg-surface-2/60">
                                    <td class="px-3 py-2">
                                        <a href="{{ route('admin.bakat.show', $teacher) }}" class="font-bold text-ink hover:text-brand">{{ $teacher->name }}</a>
                                        @if ($teacher->email)
                                            <span class="block text-xs text-ink-2">{{ $teacher->email }}</span>
                                        @endif
                                    </td>

                                    {{-- A teacher has no subject of their own; this is where they have posted. --}}
                                    <td class="px-3 py-2 text-ink-2">
                                        @if ($taught->isEmpty())
                                            <span class="text-ink-2">—</span>
                                        @else
                                            {{ $subjects->whereIn('id', $taught)->take(2)->map->displayName()->join(', ') }}@if ($taught->count() > 2) <span class="text-xs">+{{ $taught->count() - 2 }}</span>@endif
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($teacher->video_count) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($teacher->material_count) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($teacher->quiz_count) }}</td>

                                    <td class="px-3 py-2">
                                        @if ($teacher->isActive())
                                            <span class="chip bg-success-soft text-success">{{ __('Aktif') }}</span>
                                        @else
                                            <span class="chip bg-surface-2 text-ink-2">{{ __('Tidak aktif') }}</span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-2 text-right">
                                        <form method="POST" action="{{ route('admin.guru.status', $teacher) }}" class="inline">
                                            @csrf
                                            <button type="submit" @class(['btn-sm', 'btn-ghost' => $teacher->isActive(), 'btn-secondary' => ! $teacher->isActive()])>
                                                <x-icon :name="$teacher->isActive() ? 'eye-off' : 'check'" class="h-4 w-4" />
                                                {{ $teacher->isActive() ? __('Nyahaktif') : __('Aktifkan') }}
                                                <span class="sr-only">{{ $teacher->name }}</span>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs text-ink-2">
                    {{ __('Menyahaktifkan menghalang guru daripada log masuk sahaja. Video, bahan dan kuiz mereka kekal diterbitkan untuk murid.') }}
                </p>

                <div class="mt-4">{{ $teachers->links() }}</div>
            @endif
        </div>
    </section>

    {{--
        ======================================================================
        Penyumbang teratas
        ======================================================================
    --}}
    <section class="mt-12">
        <h2 class="text-xl font-extrabold text-ink">{{ __('Penyumbang Teratas') }}</h2>
        <p class="mt-1 text-sm text-ink-2">{{ __('Sepuluh guru dengan kandungan diterbitkan paling banyak.') }}</p>

        {{-- Carries the teacher filter through, so filtering here does not reset that one. --}}
        <form method="GET" action="{{ route('admin.bakat') }}" class="mt-4 flex flex-wrap items-end gap-3">
            @if ($subjectSlug) <input type="hidden" name="subjek" value="{{ $subjectSlug }}"> @endif
            @if ($gradeLevel) <input type="hidden" name="tahun" value="{{ $gradeLevel }}"> @endif

            <div>
                <label for="p_subjek" class="label mb-1">{{ __('Subjek') }}</label>
                <select id="p_subjek" name="p_subjek" class="input min-h-[44px] py-2" onchange="this.form.submit()">
                    <option value="">{{ __('Semua subjek') }}</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->slug }}" @selected($contribSubject === $subject->slug)>{{ $subject->displayName() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="p_tahun" class="label mb-1">{{ __('Tahun') }}</label>
                <select id="p_tahun" name="p_tahun" class="input min-h-[44px] py-2" onchange="this.form.submit()">
                    <option value="">{{ __('Semua tahun') }}</option>
                    @foreach ($grades as $grade)
                        <option value="{{ $grade->level }}" @selected($contribGrade === $grade->level)>{{ $grade->name }}</option>
                    @endforeach
                </select>
            </div>

            <noscript><button type="submit" class="btn-secondary btn-sm">{{ __('Tapis') }}</button></noscript>

            @if ($contribSubject || $contribGrade)
                <a href="{{ route('admin.bakat', array_filter(['subjek' => $subjectSlug, 'tahun' => $gradeLevel])) }}"
                   class="btn-ghost btn-sm">{{ __('Kosongkan') }}</a>
            @endif
        </form>

        <div class="mt-4">
            @if ($contributors->isEmpty())
                <x-empty icon="trophy" :title="__('Tiada penyumbang untuk dipaparkan')"
                         :text="__('Tiada guru dengan kandungan diterbitkan yang sepadan dengan tapisan ini.')" />
            @else
                <div class="card overflow-x-auto p-2">
                    <table class="w-full min-w-[52rem] text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-ink-2">
                                <th class="px-3 py-2 font-semibold">#</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Nama Guru') }}</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Subjek') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Kandungan Diterbitkan') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Tontonan Murid') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Jumlah Kegemaran') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($contributors as $index => $teacher)
                                @php($taught = $subjectsByTeacher[$teacher->id] ?? collect())
                                <tr class="border-b border-line/60 last:border-0 hover:bg-surface-2/60">
                                    <td class="px-3 py-2 tabular-nums text-ink-2">{{ $index + 1 }}</td>
                                    <td class="px-3 py-2">
                                        <a href="{{ route('admin.bakat.show', $teacher) }}" class="font-bold text-ink hover:text-brand">{{ $teacher->name }}</a>
                                    </td>
                                    <td class="px-3 py-2 text-ink-2">
                                        @if ($taught->isEmpty())
                                            <span>—</span>
                                        @else
                                            {{ $subjects->whereIn('id', $taught)->take(2)->map->displayName()->join(', ') }}@if ($taught->count() > 2) <span class="text-xs">+{{ $taught->count() - 2 }}</span>@endif
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right font-bold tabular-nums text-ink">{{ number_format($teacher->published_content) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format((int) $teacher->views_sum) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format((int) $teacher->favourites_sum) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs text-ink-2">
                    {{ __('Kandungan diterbitkan = video + bahan + kuiz yang diterbitkan.') }}
                </p>
            @endif
        </div>
    </section>

    {{--
        ======================================================================
        Kandungan terbaik
        ======================================================================
    --}}
    <section class="mt-12">
        <h2 class="text-xl font-extrabold text-ink">{{ __('Kandungan Terbaik') }}</h2>
        <p class="mt-1 text-sm text-ink-2">{{ __('Merentas seluruh platform, tanpa tapisan.') }}</p>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            {{-- Most viewed video --}}
            <div class="card p-5">
                <p class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="video" class="h-5 w-5" />
                    {{ __('Video Paling Ditonton') }}
                </p>

                @if ($topVideo)
                    <p class="mt-3 font-extrabold text-ink">{{ $topVideo->title }}</p>
                    <p class="text-xs text-ink-2">{{ $topVideo->chapter->subject->displayName() }} &middot; {{ $topVideo->teacher?->name }}</p>

                    <dl class="mt-4 flex gap-6">
                        <div>
                            <dt class="text-xs font-bold text-ink-2">{{ __('Tontonan') }}</dt>
                            <dd class="text-2xl font-extrabold tabular-nums text-ink">{{ number_format($topVideo->views_count) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-ink-2">{{ __('Kegemaran') }}</dt>
                            <dd class="text-2xl font-extrabold tabular-nums text-ink">{{ number_format($topVideo->favourites_count) }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-3 text-sm text-ink-2">{{ __('Belum ada video.') }}</p>
                @endif
            </div>

            {{-- Most downloaded material --}}
            <div class="card p-5">
                <p class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="file" class="h-5 w-5" />
                    {{ __('Bahan Paling Dimuat Turun') }}
                </p>

                @if ($topMaterial)
                    <p class="mt-3 font-extrabold text-ink">{{ $topMaterial->title }}</p>
                    <p class="text-xs text-ink-2">{{ $topMaterial->chapter->subject->displayName() }} &middot; {{ $topMaterial->teacher?->name }}</p>

                    <dl class="mt-4">
                        <dt class="text-xs font-bold text-ink-2">{{ __('Muat Turun') }}</dt>
                        <dd class="text-2xl font-extrabold tabular-nums text-ink">{{ number_format($topMaterial->download_count) }}</dd>
                    </dl>
                @else
                    <p class="mt-3 text-sm text-ink-2">{{ __('Belum ada bahan.') }}</p>
                @endif
            </div>

            {{-- Most attempted quiz --}}
            <div class="card p-5">
                <p class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="quiz" class="h-5 w-5" />
                    {{ __('Kuiz Paling Dicuba') }}
                </p>

                @if ($topQuiz)
                    <p class="mt-3 font-extrabold text-ink">{{ $topQuiz->title }}</p>
                    <p class="text-xs text-ink-2">{{ $topQuiz->chapter->subject->displayName() }} &middot; {{ $topQuiz->teacher?->name }}</p>

                    <dl class="mt-4 flex gap-6">
                        <div>
                            <dt class="text-xs font-bold text-ink-2">{{ __('Percubaan') }}</dt>
                            <dd class="text-2xl font-extrabold tabular-nums text-ink">{{ number_format($topQuiz->attempt_total) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-ink-2">{{ __('Lulus') }}</dt>
                            <dd class="text-2xl font-extrabold tabular-nums text-success">{{ number_format($topQuiz->pass_total) }}</dd>
                        </div>
                    </dl>
                @else
                    <p class="mt-3 text-sm text-ink-2">{{ __('Belum ada percubaan kuiz.') }}</p>
                @endif
            </div>
        </div>
    </section>
</x-app-layout>
