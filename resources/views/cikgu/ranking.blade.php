<x-app-layout :title="__('Ranking Murid')">
    <div class="mx-auto max-w-5xl">
        <header>
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Ranking Murid') }}</h1>

            <p class="mt-2 max-w-prose text-ink-2">
                {{ __('Kedudukan penuh semua murid. Mata dikira daripada percubaan pertama setiap kuiz sahaja, jadi latihan semula tidak menaikkan kedudukan.') }}
            </p>
        </header>

        <form method="GET" action="{{ route('cikgu.ranking') }}" class="mt-6 flex flex-wrap items-end gap-3">
            <div>
                <label for="tahun" class="label mb-1">{{ __('Tahun') }}</label>

                <select id="tahun" name="tahun" class="input min-h-[44px] py-2" onchange="this.form.submit()">
                    <option value="">{{ __('Semua tahun') }}</option>
                    @foreach ($grades as $option)
                        <option value="{{ $option->level }}" @selected($grade?->id === $option->id)>
                            {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="subjek" class="label mb-1">{{ __('Subjek') }}</label>

                <select id="subjek" name="subjek" class="input min-h-[44px] py-2" onchange="this.form.submit()">
                    <option value="">{{ __('Semua subjek') }}</option>
                    @foreach ($subjects->groupBy('category') as $category => $group)
                        <optgroup label="{{ \App\Models\Subject::categoryLabel($category) }}">
                            @foreach ($group as $option)
                                <option value="{{ $option->slug }}" @selected($subject?->id === $option->id)>
                                    {{ $option->displayName() }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="kuiz" class="label mb-1">{{ __('Kuiz') }}</label>

                <select id="kuiz" name="kuiz" class="input min-h-[44px] py-2" onchange="this.form.submit()">
                    <option value="">{{ __('Semua kuiz') }}</option>
                    @foreach ($quizzes as $option)
                        <option value="{{ $option->id }}" @selected($quiz?->id === $option->id)>
                            {{ $option->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <noscript>
                <button type="submit" class="btn-secondary btn-sm">{{ __('Tapis') }}</button>
            </noscript>

            @if (request()->hasAny(['tahun', 'subjek', 'kuiz']))
                <a href="{{ route('cikgu.ranking') }}" class="btn-ghost btn-sm">{{ __('Kosongkan') }}</a>
            @endif
        </form>

        <section class="mt-6">
            <h2 class="sr-only">{{ __('Jadual ranking') }}</h2>

            @if ($rows->isEmpty())
                <x-empty emoji="🏁" :title="__('Belum ada data ranking')"
                         :text="__('Ranking akan muncul setelah murid menyelesaikan kuiz interaktif yang diterbitkan.')" />
            @else
                <div class="card overflow-x-auto">
                    <table class="w-full text-left">
                        <caption class="sr-only">
                            {{ __('Ranking murid') }}
                            {{ $grade ? __('bagi').' '.$grade->name : __('bagi semua tahun') }}
                            {{ $subject ? ', '.__('subjek').' '.$subject->name : '' }}
                            {{ $quiz ? ', '.__('kuiz').' '.$quiz->title : '' }}
                        </caption>

                        <thead class="border-b border-line">
                            <tr>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">#</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Murid') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Tahun') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Mata') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Betul') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Ketepatan') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Kuiz') }}</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-line">
                            @foreach ($rows as $row)
                                <tr>
                                    <td class="p-4">
                                        <span class="font-extrabold text-ink">
                                            @if ($row->rank === 1)
                                                <span aria-hidden="true">🥇</span>
                                            @elseif ($row->rank === 2)
                                                <span aria-hidden="true">🥈</span>
                                            @elseif ($row->rank === 3)
                                                <span aria-hidden="true">🥉</span>
                                            @else
                                                {{ $row->rank }}
                                            @endif

                                            <span class="sr-only">{{ __('Kedudukan :rank', ['rank' => $row->rank]) }}</span>
                                        </span>
                                    </td>

                                    <td class="p-4">
                                        <span class="flex items-center gap-3">
                                            <x-avatar :user="$row->student" size="sm" />
                                            <span class="font-bold text-ink">{{ $row->student->name }}</span>
                                        </span>
                                    </td>

                                    <td class="p-4 text-ink-2">{{ $row->student->grade?->name ?? '-' }}</td>

                                    <td class="p-4 text-lg font-extrabold text-ink">{{ $row->points }}</td>

                                    <td class="p-4 text-ink-2">{{ $row->correct }}/{{ $row->questions }}</td>

                                    <td class="p-4">
                                        <span class="chip
                                            @if ($row->accuracy >= 80) bg-success-soft text-success
                                            @elseif ($row->accuracy >= 50) bg-warn-soft text-warn
                                            @else bg-danger-soft text-danger @endif">
                                            {{ $row->accuracy }}%
                                        </span>
                                    </td>

                                    <td class="p-4 text-ink-2">{{ $row->quizzes }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-4 text-sm text-ink-2">
                    {{ __(':count murid dalam senarai ini.', ['count' => $rows->count()]) }}
                </p>
            @endif
        </section>
    </div>
</x-app-layout>
