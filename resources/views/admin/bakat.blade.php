<x-app-layout :title="__('Skor Bakat Guru')">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Skor Bakat Guru') }}</h1>
            <p class="mt-1 max-w-prose text-ink-2">
                {{ __('Senarai untuk semakan MOE — guru berpotensi dikenal pasti daripada penglibatan murid dalam platform, dipasangkan dengan pertimbangan manusia.') }}
            </p>
        </div>

        <a href="{{ route('admin.bakat.export', request()->query()) }}" class="btn-secondary btn-sm shrink-0">
            <x-icon name="download" class="h-4 w-4" />
            {{ __('Eksport CSV') }}
        </a>
    </header>

    <x-talent-disclaimer class="mt-6" />

    {{-- Filters: subject, grade, and a shortlist toggle that surfaces quality + outcome. --}}
    <form method="GET" action="{{ route('admin.bakat') }}" class="mt-6 flex flex-wrap items-end gap-3">
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
                <option value="">{{ __('Semua Tahun') }}</option>
                @foreach ($grades as $grade)
                    <option value="{{ $grade->level }}" @selected($gradeLevel === $grade->level)>{{ $grade->name }}</option>
                @endforeach
            </select>
        </div>

        @if ($shortlist)
            <input type="hidden" name="shortlist" value="1">
        @endif

        <a href="{{ route('admin.bakat', array_merge(request()->except('shortlist'), $shortlist ? [] : ['shortlist' => 1])) }}"
           @class(['btn-sm', 'btn-primary' => $shortlist, 'btn-secondary' => ! $shortlist])>
            <x-icon name="star" class="h-4 w-4" />
            {{ $shortlist ? __('Papar semua') : __('Senarai pendek') }}
        </a>

        <noscript><button type="submit" class="btn-secondary btn-sm">{{ __('Tapis') }}</button></noscript>
    </form>

    <section class="mt-6">
        @if ($cohort->isEmpty())
            <x-empty icon="users" :title="__('Tiada guru untuk dipaparkan')"
                     :text="__('Tiada guru yang sepadan dengan tapisan ini.')" />
        @else
            <div class="card overflow-x-auto p-2">
                <table class="w-full min-w-[52rem] text-sm">
                    <thead>
                        <tr class="border-b border-line text-left text-ink-2">
                            <th class="px-3 py-2 font-semibold">#</th>
                            <th class="px-3 py-2 font-semibold">{{ __('Guru') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Skor') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Penglibatan') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Kualiti') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Hasil') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Keluasan') }}</th>
                            <th class="px-3 py-2 text-right font-semibold">{{ __('Murid') }}</th>
                            <th class="px-3 py-2 text-center font-semibold">{{ __('YouTube') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cohort as $index => $row)
                            <tr class="border-b border-line/60 last:border-0 hover:bg-surface-2/60">
                                <td class="px-3 py-2 tabular-nums text-ink-2">{{ $index + 1 }}</td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.bakat.show', $row->teacher) }}" class="font-bold text-ink hover:text-brand">{{ $row->teacher->name }}</a>
                                    @if ($row->teacher->email)
                                        <span class="block text-xs text-ink-2">{{ $row->teacher->email }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    @if ($row->sufficient && $row->headline !== null)
                                        <span class="text-lg font-extrabold tabular-nums text-ink">{{ $row->headline }}</span>
                                    @else
                                        <span class="text-xs font-semibold text-ink-2">{{ __('Data belum cukup') }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ number_format((int) round($row->raw['engagement'])) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ round($row->raw['quality'] * 100) }}%</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ $row->raw['outcome'] === null ? '—' : (($row->raw['outcome'] > 0 ? '+' : '').$row->raw['outcome']) }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ $row->raw['breadth'] }}</td>
                                <td class="px-3 py-2 text-right tabular-nums text-ink-2">{{ $row->engaged_students }}</td>
                                <td class="px-3 py-2 text-center">
                                    @if ($row->channels > 0)
                                        <span class="chip bg-brand-soft text-brand">{{ $row->channels }}</span>
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
                {{ __('Penglibatan = tontonan unik + kegemaran (berpemberat). Kualiti = kadar kegemaran per penonton. Hasil = beza markah kuiz penonton vs purata bab. Keluasan = bilangan bab.') }}
            </p>
        @endif
    </section>
</x-app-layout>
