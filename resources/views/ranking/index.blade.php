<x-student-layout :title="__('Ranking')">
    @php($me = auth()->user())

    <div class="mx-auto max-w-3xl">
        <header>
            <h1 class="text-3xl font-extrabold text-ink">{{ __('Ranking') }}</h1>

            <p class="mt-2 text-ink-2">
                {{ __('Kedudukan murid :grade, berdasarkan mata daripada percubaan pertama setiap kuiz.', ['grade' => $grade?->name ?? __('tahun anda')]) }}
            </p>
        </header>

        {{-- Subject filter. A grouped <select>, not tabs: 27 subjects across 5 categories. --}}
        <form method="GET" action="{{ route('ranking.index') }}" class="mt-6 max-w-sm">
            <label for="subjek" class="label mb-1">{{ __('Tapis mengikut subjek') }}</label>

            <select id="subjek" name="subjek" class="input min-h-[44px] py-2" onchange="this.form.submit()">
                <option value="">{{ __('Keseluruhan') }}</option>

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

            <noscript>
                <button type="submit" class="btn-secondary btn-sm mt-2">{{ __('Tapis') }}</button>
            </noscript>
        </form>

        <section class="mt-6">
            <h2 class="sr-only">{{ __('Kedudukan 10 teratas') }}</h2>

            @if ($top->isEmpty())
                <x-empty icon="trophy" :title="__('Belum ada ranking')"
                         :text="__('Belum ada murid yang menyelesaikan kuiz :subject dalam :grade. Jadilah yang pertama!', ['subject' => $subject ? $subject->name : '', 'grade' => $grade?->name ?? __('tahun anda')])">
                    <a href="{{ route('belajar.index') }}" class="btn-primary">{{ __('Cari Kuiz') }}</a>
                </x-empty>
            @else
                <ol class="space-y-2">
                    @foreach ($top as $row)
                        @php($isMe = $row->student->id === $me->id)

                        <li class="card flex items-center gap-4 p-4 {{ $isMe ? 'ring-2 ring-brand' : '' }}">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-lg font-bold tabular-nums
                                @if ($row->rank === 1) bg-warn/15 text-warn ring-1 ring-warn/30
                                @elseif ($row->rank === 2) bg-ink-2/15 text-ink ring-1 ring-ink-2/25
                                @elseif ($row->rank === 3) bg-danger/10 text-danger ring-1 ring-danger/25
                                @else bg-surface-2 text-ink-2 @endif">
                                {{ $row->rank }}
                                <span class="sr-only">{{ __('Kedudukan :rank', ['rank' => $row->rank]) }}</span>
                            </span>

                            <x-avatar :user="$row->student" size="md" />

                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-extrabold text-ink">
                                    {{ $row->student->name }}
                                    @if ($isMe)
                                        <span class="chip ml-1 bg-brand-soft text-brand">{{ __('Anda') }}</span>
                                    @endif
                                </span>

                                <span class="block text-sm text-ink-2">
                                    {{ __('Ketepatan :accuracy%. :quizzes kuiz', ['accuracy' => $row->accuracy, 'quizzes' => $row->quizzes]) }}
                                </span>
                            </span>

                            <span class="shrink-0 text-right">
                                <span class="block text-2xl font-extrabold text-ink">{{ $row->points }}</span>
                                <span class="block text-xs font-bold text-ink-2">{{ __('mata') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>

                {{-- The student's own row, pinned, when they sit outside the top 10. --}}
                @if ($showMyRow && $myRow)
                    <p class="mt-6 text-center text-sm font-bold text-ink-2">{{ __('Kedudukan anda') }}</p>

                    <div class="card mt-2 flex items-center gap-4 p-4 ring-2 ring-brand">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-soft text-lg font-extrabold text-brand">
                            {{ $myRow->rank }}
                        </span>

                        <x-avatar :user="$me" size="md" />

                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-extrabold text-ink">
                                {{ $me->name }}
                                <span class="chip ml-1 bg-brand-soft text-brand">{{ __('Anda') }}</span>
                            </span>

                            <span class="block text-sm text-ink-2">
                                {{ __('Ketepatan :accuracy%. :quizzes kuiz', ['accuracy' => $myRow->accuracy, 'quizzes' => $myRow->quizzes]) }}
                            </span>
                        </span>

                        <span class="shrink-0 text-right">
                            <span class="block text-2xl font-extrabold text-ink">{{ $myRow->points }}</span>
                            <span class="block text-xs font-bold text-ink-2">{{ __('mata') }}</span>
                        </span>
                    </div>
                @elseif (! $myRow)
                    <div class="card mt-6 p-5 text-center">
                        <p class="font-bold text-ink">{{ __('Anda belum ada mata :subject.', ['subject' => $subject ? 'untuk '.$subject->name : '']) }}</p>
                        <p class="mt-1 text-ink-2">{{ __('Selesaikan satu kuiz untuk masuk ke dalam ranking.') }}</p>

                        <a href="{{ route('belajar.index') }}" class="btn-primary mt-4">{{ __('Cari Kuiz') }}</a>
                    </div>
                @endif
            @endif
        </section>

        <p class="mt-6 text-center text-sm text-ink-2">
            {{ __('Hanya percubaan pertama setiap kuiz dikira. Latihan semula tidak menambah mata.') }}
        </p>
    </div>
</x-student-layout>
