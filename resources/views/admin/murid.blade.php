<x-app-layout :title="__('Murid')">
    <header>
        <h1 class="text-3xl font-extrabold text-ink">{{ __('Murid') }}</h1>
        <p class="mt-1 max-w-prose text-ink-2">
            {{ __('Gambaran keseluruhan murid dan aktiviti mereka di platform.') }}
        </p>
    </header>

    {{--
        Counts describe the school, not the table: they stay put when the filter below changes.
    --}}
    <section class="mt-8">
        <h2 class="sr-only">{{ __('Ringkasan murid') }}</h2>

        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
            <div class="card p-5">
                <dt class="flex items-center gap-2 text-sm font-bold text-ink-2">
                    <x-icon name="users" class="h-5 w-5" />
                    {{ __('Jumlah') }}
                </dt>
                <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">{{ number_format($totalStudents) }}</dd>
            </div>

            @foreach ($grades as $grade)
                <div class="card p-5">
                    <dt class="text-sm font-bold text-ink-2">{{ $grade->name }}</dt>
                    <dd class="mt-2 text-3xl font-extrabold tabular-nums text-ink">
                        {{ number_format($countsByGrade[$grade->level] ?? 0) }}
                    </dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{--
        ======================================================================
        Senarai murid
        ======================================================================
    --}}
    <section class="mt-10">
        <h2 class="text-xl font-extrabold text-ink">{{ __('Senarai Murid') }}</h2>

        <form method="GET" action="{{ route('admin.murid') }}" class="mt-4 flex flex-wrap items-end gap-3">
            {{-- Keep every podium's subject choice while the roster filter changes. --}}
            @foreach ($podiums as $podium)
                @if ($podium->subjectSlug)
                    <input type="hidden" name="subjek_{{ $podium->grade->level }}" value="{{ $podium->subjectSlug }}">
                @endif
            @endforeach

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

            @if ($gradeLevel)
                <a href="{{ route('admin.murid', request()->except('tahun')) }}" class="btn-ghost btn-sm">{{ __('Kosongkan') }}</a>
            @endif
        </form>

        <div class="mt-4">
            @if ($students->isEmpty())
                <x-empty icon="users" :title="__('Tiada murid untuk dipaparkan')"
                         :text="__('Tiada murid yang sepadan dengan tapisan ini.')" />
            @else
                <div class="card overflow-x-auto p-2">
                    <table class="w-full min-w-[60rem] text-sm">
                        <thead>
                            <tr class="border-b border-line text-left text-ink-2">
                                <th class="px-3 py-2 font-semibold">{{ __('Nama Murid') }}</th>
                                <th class="px-3 py-2 font-semibold">{{ __('Tahun') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Video Ditonton') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Bahan Dimuat Turun') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Percubaan Kuiz') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Lulus') }}</th>
                                <th class="px-3 py-2 text-right font-semibold">{{ __('Tidak lulus') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students as $student)
                                <tr class="border-b border-line/60 last:border-0 hover:bg-surface-2/60">
                                    <td class="px-3 py-2">
                                        <span class="block font-bold text-ink">{{ $student->name }}</span>
                                        <span class="block text-xs text-ink-2">{{ $student->username }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-ink-2">{{ $student->grade?->name ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($student->videos_viewed) }}</td>

                                    {{-- Downloads are counted per file, never per student, so there is no
                                         honest number to put here. An em dash beats inventing one. --}}
                                    <td class="px-3 py-2 text-right text-ink-2">—</td>

                                    <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format($student->attempts_count) }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        @if ($student->attempts_count > 0)
                                            <span class="font-bold text-success">{{ number_format($student->pass_count) }}</span>
                                        @else
                                            <span class="text-ink-2">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right tabular-nums">
                                        @if ($student->attempts_count > 0)
                                            <span class="font-bold text-danger">{{ number_format($student->attempts_count - $student->pass_count) }}</span>
                                        @else
                                            <span class="text-ink-2">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs text-ink-2">
                    {{ __('Bahan dimuat turun tidak direkodkan per murid — hanya jumlah muat turun bagi setiap fail. Lulus bermaksud :percent% atau lebih.', ['percent' => \App\Models\QuizAttempt::PASS_AT]) }}
                </p>

                <div class="mt-4">{{ $students->links() }}</div>
            @endif
        </div>
    </section>

    {{--
        ======================================================================
        Murid Teratas — a podium per Tahun
        ======================================================================
    --}}
    <section class="mt-12">
        <h2 class="text-xl font-extrabold text-ink">{{ __('Murid Teratas') }}</h2>
        <p class="mt-1 max-w-prose text-sm text-ink-2">
            {{ __('Tiga murid paling aktif bagi setiap Tahun. Keaktifan = video ditonton + percubaan kuiz + kegemaran. Ia mengukur penglibatan, bukan markah — jadi ia berbeza daripada papan Ranking yang dilihat murid.') }}
        </p>

        <div class="mt-6 space-y-8">
            @foreach ($podiums as $podium)
                <div class="card p-5">
                    {{-- Year label, with that year's own subject filter beside it. --}}
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="flex items-center gap-2">
                            <span class="chip bg-brand-soft text-brand">{{ $podium->grade->name }}</span>
                        </h3>

                        <form method="GET" action="{{ route('admin.murid') }}" class="flex items-end gap-2">
                            {{-- Preserve the roster filter and every other year's subject. --}}
                            @if ($gradeLevel) <input type="hidden" name="tahun" value="{{ $gradeLevel }}"> @endif
                            @foreach ($podiums as $other)
                                @if ($other->grade->level !== $podium->grade->level && $other->subjectSlug)
                                    <input type="hidden" name="subjek_{{ $other->grade->level }}" value="{{ $other->subjectSlug }}">
                                @endif
                            @endforeach

                            <label for="subjek_{{ $podium->grade->level }}" class="sr-only">{{ __('Subjek') }}</label>
                            <select id="subjek_{{ $podium->grade->level }}" name="subjek_{{ $podium->grade->level }}"
                                    class="input min-h-[40px] py-1.5 text-sm" onchange="this.form.submit()">
                                <option value="">{{ __('Semua subjek') }}</option>
                                @foreach ($subjects->where('id', '!=', null) as $subject)
                                    <option value="{{ $subject->slug }}" @selected($podium->subjectSlug === $subject->slug)>{{ $subject->displayName() }}</option>
                                @endforeach
                            </select>

                            <noscript><button type="submit" class="btn-secondary btn-sm">{{ __('Tapis') }}</button></noscript>
                        </form>
                    </div>

                    @if ($podium->students->isEmpty())
                        <p class="mt-6 text-center text-sm text-ink-2">{{ __('Belum ada aktiviti murid untuk Tahun ini.') }}</p>
                    @else
                        {{-- Podium order: 2nd, 1st, 3rd — the winner stands in the middle on the tallest
                             block. Ordered by rank in the DOM for screen readers, then re-ordered
                             visually, so the markup still reads 1-2-3. --}}
                        @php
                            $blocks = [
                                ['rank' => 2, 'height' => 'h-14', 'order' => 'order-1', 'tone' => 'bg-surface-3'],
                                ['rank' => 1, 'height' => 'h-24', 'order' => 'order-2', 'tone' => 'bg-brand'],
                                ['rank' => 3, 'height' => 'h-10', 'order' => 'order-3', 'tone' => 'bg-surface-3'],
                            ];
                        @endphp

                        <ol class="mt-6 flex items-end justify-center gap-3 sm:gap-6">
                            @foreach ($blocks as $block)
                                @php($student = $podium->students[$block['rank'] - 1] ?? null)

                                @if ($student)
                                    <li class="{{ $block['order'] }} flex w-28 flex-col items-center sm:w-40">
                                        <x-avatar :user="$student" size="sm" />

                                        <p class="mt-2 w-full truncate text-center text-sm font-bold text-ink">{{ $student->name }}</p>
                                        <p class="text-xs text-ink-2">{{ $student->effort }} {{ __('mata keaktifan') }}</p>

                                        <div class="{{ $block['height'] }} {{ $block['tone'] }} mt-2 flex w-full items-center justify-center rounded-t-card">
                                            <span @class([
                                                'text-2xl font-extrabold tabular-nums',
                                                'text-on-brand' => $block['rank'] === 1,
                                                'text-ink-2' => $block['rank'] !== 1,
                                            ])>{{ $block['rank'] }}</span>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ol>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
