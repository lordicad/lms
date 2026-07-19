<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="$quiz->title">
    <div class="mx-auto max-w-3xl" style="--sc: {{ $subject->rgb }}">
        <a href="{{ route('bab.show', $chapter) }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            Bab {{ $chapter->number }}: {{ $chapter->title }}
        </a>

        @if ($isPreview)
            <x-alert type="warn" class="mt-4">
                {{ __('Anda melihat kuiz ini sebagai cikgu. Guru tidak boleh mencuba kuiz, hanya menyemak.') }}
            </x-alert>
        @endif

        <div class="card card-pad mt-4">
            <span class="chip bg-subject-wash text-subject-ink"><x-subject-emoji :subject="$subject" class="text-sm" /> {{ $subject->name }}</span>

            <h1 class="mt-3 text-3xl font-extrabold text-ink">{{ $quiz->title }}</h1>

            @if ($quiz->description)
                <p class="mt-3 max-w-prose text-ink-2">{{ $quiz->description }}</p>
            @endif

            <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="rounded-card bg-surface-2 p-4">
                    <dt class="text-sm font-bold text-ink-2">{{ __('Soalan') }}</dt>
                    <dd class="text-2xl font-extrabold text-ink">{{ $questionCount }}</dd>
                </div>

                <div class="rounded-card bg-surface-2 p-4">
                    <dt class="text-sm font-bold text-ink-2">{{ __('Markah penuh') }}</dt>
                    <dd class="text-2xl font-extrabold text-ink">{{ $maxScore }}</dd>
                </div>

                <div class="rounded-card bg-surface-2 p-4">
                    <dt class="text-sm font-bold text-ink-2">{{ __('Masa') }}</dt>
                    <dd class="text-2xl font-extrabold text-ink">
                        {{ $quiz->duration_minutes ? $quiz->duration_minutes.' min' : __('Bebas') }}
                    </dd>
                </div>
            </dl>

            {{-- The ranking rule, said plainly. Kids need to know retries are safe. --}}
            <div class="mt-6 rounded-card border border-line bg-surface-2 p-5">
                <h2 class="flex items-center gap-2 font-extrabold text-ink">
                    <x-icon name="info" class="h-5 w-5 text-brand" />
                    {{ __('Peraturan kuiz') }}
                </h2>

                <ul class="mt-3 space-y-2 text-ink-2">
                    <li class="flex gap-2">
                        <x-icon name="check" class="mt-1 h-4 w-4 shrink-0 text-success" />
                        <span>{{ __('Soalan bulat (radio): pilih') }} <strong>{{ __('satu') }}</strong> {{ __('jawapan sahaja.') }}</span>
                    </li>
                    <li class="flex gap-2">
                        <x-icon name="check" class="mt-1 h-4 w-4 shrink-0 text-success" />
                        <span>{{ __('Soalan kotak (checkbox): pilih') }} <strong>{{ __('semua') }}</strong> {{ __('jawapan yang betul. Semua mesti betul untuk dapat markah.') }}</span>
                    </li>
                    <li class="flex gap-2">
                        <x-icon name="trophy" class="mt-1 h-4 w-4 shrink-0 text-brand" />
                        <span>
                            <strong>{{ __('Hanya percubaan pertama') }}</strong> {{ __('dikira untuk ranking.') }}
                            {{ __('Percubaan seterusnya adalah latihan sahaja dan tidak menjejaskan mata anda.') }}
                        </span>
                    </li>
                    @if ($quiz->duration_minutes)
                        <li class="flex gap-2">
                            <x-icon name="clock" class="mt-1 h-4 w-4 shrink-0 text-warn" />
                            <span>{{ __('Ada masa :minutes minit.', ['minutes' => $quiz->duration_minutes]) }} {{ __('Jawapan dihantar automatik apabila masa tamat.') }}</span>
                        </li>
                    @endif
                </ul>
            </div>

            @if (! $isPreview)
                @if ($rankedAttempt)
                    <x-alert type="success" class="mt-6">
                        {{ __('Percubaan pertama anda sudah direkodkan:') }} {{ $rankedAttempt->score }}/{{ $rankedAttempt->max_score }} {{ __('mata.') }}
                        {{ __('Percubaan baharu adalah latihan semula.') }}
                    </x-alert>
                @endif

                <form method="POST" action="{{ route('kuiz.mula', $quiz) }}" class="mt-6">
                    @csrf

                    <button type="submit" class="btn-primary w-full text-lg">
                        {{ $rankedAttempt ? __('Cuba Lagi (Latihan)') : __('Mula Kuiz') }}
                    </button>
                </form>
            @endif
        </div>

        {{-- Previous attempts --}}
        @if ($myAttempts->isNotEmpty())
            <section class="mt-8">
                <h2 class="mb-3 text-lg font-extrabold text-ink">{{ __('Percubaan anda') }}</h2>

                <ul class="space-y-2">
                    @foreach ($myAttempts as $attempt)
                        <li class="card flex flex-wrap items-center gap-4 p-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-control text-sm font-extrabold
                                         {{ $attempt->isCelebration() ? 'bg-success-soft text-success' : 'bg-surface-2 text-ink-2' }}">
                                {{ $attempt->percentage() }}%
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block font-bold text-ink">
                                    {{ $attempt->score }}/{{ $attempt->max_score }} {{ __('mata.') }}
                                    {{ $attempt->correct_count }}/{{ $attempt->question_count }} {{ __('betul') }}
                                </span>

                                <span class="block text-sm text-ink-2">
                                    {{ $attempt->completed_at->format('d/m/Y, g:ia') }}.
                                    {{ $attempt->counts_for_ranking ? __('Dikira untuk ranking') : __('Latihan semula') }}
                                </span>
                            </span>

                            <a href="{{ route('keputusan.show', $attempt) }}" class="btn-secondary btn-sm shrink-0">
                                {{ __('Lihat Semakan') }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-dynamic-component>
