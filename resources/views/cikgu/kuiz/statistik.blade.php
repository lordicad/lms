<x-app-layout :title="__('Statistik:').' '.$quiz->title">
    <div class="mx-auto max-w-4xl" style="--sc: {{ $subject->rgb }}">
        <a href="{{ route('cikgu.kuiz.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Kuiz Saya') }}
        </a>

        <header class="mt-4">
            <span class="chip bg-subject-wash text-subject-ink">
                {{ $subject->icon }} {{ $subject->name }}. {{ $chapter->grade->name }}. Bab {{ $chapter->number }}
            </span>

            <h1 class="mt-2 text-3xl font-extrabold text-ink">{{ $quiz->title }}</h1>
        </header>

        <dl class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="card p-5">
                <dt class="text-sm font-bold text-ink-2">{{ __('Percubaan selesai') }}</dt>
                <dd class="mt-1 text-3xl font-extrabold text-ink">{{ $completedCount }}</dd>
            </div>

            <div class="card p-5">
                <dt class="text-sm font-bold text-ink-2">{{ __('Purata markah') }}</dt>
                <dd class="mt-1 text-3xl font-extrabold text-ink">
                    {{ $averageScore }}<span class="text-lg text-ink-2">/{{ $quiz->maxScore() }}</span>
                </dd>
            </div>

            <div class="card p-5">
                <dt class="text-sm font-bold text-ink-2">{{ __('Purata ketepatan') }}</dt>
                <dd class="mt-1 text-3xl font-extrabold text-ink">{{ $averagePercent }}%</dd>
            </div>
        </dl>

        {{-- Which questions the class actually got wrong. --}}
        <section class="mt-10">
            <h2 class="mb-4 text-xl font-extrabold text-ink">{{ __('Kadar betul setiap soalan') }}</h2>

            @if ($completedCount === 0)
                <x-empty emoji="📊" :title="__('Belum ada data')"
                         :text="__('Statistik akan muncul setelah murid mula menjawab kuiz ini.')" />
            @else
                <ul class="space-y-3">
                    @foreach ($quiz->questions as $index => $question)
                        @php
                            $stat = $perQuestion[$question->id] ?? null;
                            $answered = (int) ($stat->answered ?? 0);
                            $correct = (int) ($stat->correct ?? 0);
                            $rate = $answered > 0 ? (int) round($correct / $answered * 100) : 0;
                        @endphp

                        <li class="card card-pad">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-bold text-ink-2">{{ __('Soalan :number', ['number' => $index + 1]) }}</p>
                                    <p class="mt-1 font-extrabold text-ink">{{ $question->question_text }}</p>
                                </div>

                                <span class="chip shrink-0
                                    @if ($rate >= 70) bg-success-soft text-success
                                    @elseif ($rate >= 40) bg-warn-soft text-warn
                                    @else bg-danger-soft text-danger @endif">
                                    {{ __(':rate% betul', ['rate' => $rate]) }}
                                </span>
                            </div>

                            <div class="mt-3 h-2.5 w-full overflow-hidden rounded-full bg-surface-2">
                                <div class="h-full rounded-full transition-[width] duration-300
                                    @if ($rate >= 70) bg-success
                                    @elseif ($rate >= 40) bg-warn
                                    @else bg-danger @endif"
                                     style="width: {{ $rate }}%"></div>
                            </div>

                            <p class="mt-2 text-sm text-ink-2">
                                {{ __(':correct daripada :answered murid menjawab dengan betul.', ['correct' => $correct, 'answered' => $answered]) }}
                                @if ($rate < 40)
                                    <span class="font-bold text-danger">{{ __('Mungkin perlu diterangkan semula.') }}</span>
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Attempt list --}}
        <section class="mt-10">
            <h2 class="mb-4 text-xl font-extrabold text-ink">{{ __('Percubaan murid') }}</h2>

            @if ($attempts->isEmpty())
                <x-empty emoji="👋" :title="__('Belum ada murid mencuba kuiz ini')"
                         :text="__('Pastikan kuiz sudah diterbitkan dan mempunyai soalan.')" />
            @else
                <div class="card overflow-x-auto">
                    <table class="w-full text-left">
                        <caption class="sr-only">{{ __('Senarai percubaan murid untuk kuiz :title', ['title' => $quiz->title]) }}</caption>

                        <thead class="border-b border-line">
                            <tr>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Murid') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Tahun') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Markah') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Betul') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Masa') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Jenis') }}</th>
                                <th scope="col" class="p-4 text-sm font-bold text-ink-2">{{ __('Tarikh') }}</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-line">
                            @foreach ($attempts as $attempt)
                                <tr>
                                    <td class="p-4">
                                        <span class="flex items-center gap-3">
                                            <x-avatar :user="$attempt->student" size="sm" />
                                            <span class="font-bold text-ink">{{ $attempt->student->name }}</span>
                                        </span>
                                    </td>

                                    <td class="p-4 text-ink-2">{{ $attempt->student->grade?->name ?? '-' }}</td>

                                    <td class="p-4">
                                        <span class="font-extrabold text-ink">
                                            {{ $attempt->score }}/{{ $attempt->max_score }}
                                        </span>
                                        <span class="block text-sm text-ink-2">{{ $attempt->percentage() }}%</span>
                                    </td>

                                    <td class="p-4 text-ink-2">
                                        {{ $attempt->correct_count }}/{{ $attempt->question_count }}
                                    </td>

                                    <td class="p-4 text-ink-2">{{ $attempt->humanDuration() }}</td>

                                    <td class="p-4">
                                        @if ($attempt->counts_for_ranking)
                                            <span class="chip bg-brand-soft text-brand">{{ __('Dikira') }}</span>
                                        @else
                                            <span class="chip bg-surface-2 text-ink-2">{{ __('Latihan') }}</span>
                                        @endif
                                    </td>

                                    <td class="p-4 text-sm text-ink-2">
                                        {{ $attempt->completed_at->format('d/m/Y, g:ia') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
