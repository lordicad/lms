@php
    $cols = 'grid-template-columns:minmax(0,2fr) 1.3fr .8fr .8fr .6fr .6fr 1fr .7fr;gap:12px;align-items:center';
    $stats = [
        ['icon' => '📝', 'label' => __('Jumlah kuiz'),      'value' => $totalCount,   'labelColor' => '#8B8AA3'],
        ['icon' => '👥', 'label' => __('Jumlah percubaan'), 'value' => $attemptCount, 'labelColor' => '#8B8AA3'],
        ['icon' => '✅', 'label' => __('Lulus'),            'value' => $passCount,    'labelColor' => '#0F7A68'],
        ['icon' => '❌', 'label' => __('Gagal'),            'value' => $failCount,    'labelColor' => '#C24936'],
    ];
@endphp

<x-admin-layout :title="__('Kandungan Kuiz')"
                :heading="__('Kandungan Kuiz')"
                :sub="__('Setiap kuiz yang dibina oleh cikgu, merentas semua subjek dan Tahun')">

    <div style="display:flex;flex-direction:column;gap:18px"
         x-data="{
             quiz: null,
             open(data) { this.quiz = data; document.body.classList.add('overflow-hidden'); },
             close() { this.quiz = null; document.body.classList.remove('overflow-hidden'); },
         }"
         @keydown.escape.window="close()">

        @include('admin.kandungan._tabs', ['active' => 'kuiz'])

        {{-- Stats + disclaimer --}}
        <div style="display:flex;flex-direction:column;gap:8px">
            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px">
                @foreach ($stats as $s)
                    <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:8px;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                        <span style="font-size:13.5px;font-weight:700;color:{{ $s['labelColor'] }}">{{ $s['icon'] }} {{ $s['label'] }}</span>
                        <span style="font-family:'Geist',sans-serif;font-size:28px;font-weight:800;color:var(--tp-ink)">{{ number_format($s['value']) }}</span>
                    </div>
                @endforeach
            </div>
            <span style="font-size:12.5px;color:var(--tp-muted)">{{ __('Lulus bermaksud percubaan mendapat :percent% atau lebih. Setiap percubaan yang selesai dikira, termasuk ulangan.', ['percent' => \App\Models\QuizAttempt::PASS_AT]) }}</span>
        </div>

        @include('admin.kandungan._filters', ['subjects' => $subjects, 'grades' => $grades, 'action' => route('admin.kandungan.kuiz')])

        @if ($quizzes->isEmpty())
            <div class="tp-empty">
                <span style="font-size:30px">📝</span>
                <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Tiada kuiz untuk dipaparkan') }}</h3>
                <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:380px">{{ __('Tiada kuiz yang sepadan dengan tapisan ini.') }}</p>
            </div>
        @else
            <div style="background:var(--tp-surface);border:1px solid var(--tp-line);border-radius:18px;overflow:hidden;box-shadow:0 2px 10px rgba(46,44,80,.04)">
                <div style="overflow-x:auto">
                    <div style="min-width:900px">
                        <div style="display:grid;{{ $cols }};padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tajuk Kuiz') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Subjek') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tahun') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Percubaan') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Lulus') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Gagal') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted)">{{ __('Tarikh Siar') }}</span>
                            <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;color:var(--tp-muted);text-align:right">{{ __('Tindakan') }}</span>
                        </div>
                        @foreach ($quizzes as $quiz)
                            @php($hasAttempts = $quiz->attempts_count > 0)
                            <div class="tp-tr" style="display:grid;{{ $cols }};padding:12px 20px;border-bottom:1px solid var(--tp-line)">
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                    <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $quiz->title }}@if ($quiz->isFile()) <span style="font-size:11px;font-weight:800;color:var(--tp-muted)">· {{ __('Fail') }}</span>@endif</span>
                                    <span style="font-size:11.5px;color:var(--tp-muted)">{{ $quiz->teacher?->name }}</span>
                                </div>
                                <span style="font-size:13px;font-weight:700;color:#4276AE">{{ $quiz->chapter->subject->displayName() }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ $quiz->chapter->grade->name }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ number_format($quiz->attempts_count) }}</span>
                                <span style="font-size:13px;font-weight:800;color:{{ $hasAttempts ? '#0F7A68' : '#8B8AA3' }}">{{ $hasAttempts ? number_format($quiz->pass_count) : '—' }}</span>
                                <span style="font-size:13px;font-weight:800;color:{{ $hasAttempts ? '#C24936' : '#8B8AA3' }}">{{ $hasAttempts ? number_format($quiz->attempts_count - $quiz->pass_count) : '—' }}</span>
                                <span style="font-size:13px;font-weight:700;color:var(--tp-muted-2)">{{ $quiz->created_at->translatedFormat('j M Y') }}</span>
                                <button type="button" class="tp-linkbtn" style="justify-self:end"
                                        @click="open(@js([
                                            'title' => $quiz->title,
                                            'teacher' => $quiz->teacher?->name,
                                            'type' => $quiz->type,
                                            'downloadUrl' => $quiz->isFile() ? route('muat-turun.kuiz', $quiz) : null,
                                            'questions' => $quiz->questions->map(fn ($question) => [
                                                'text' => $question->question_text,
                                                'multiple' => $question->isMultiple(),
                                                'points' => $question->points,
                                                'options' => $question->options->map(fn ($option) => [
                                                    'letter' => $option->letter(),
                                                    'text' => $option->option_text,
                                                    'correct' => (bool) $option->is_correct,
                                                ])->all(),
                                            ])->all(),
                                        ]))">
                                    👁 {{ __('Lihat') }}<span class="sr-only">{{ $quiz->title }}</span>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>{{ $quizzes->links() }}</div>
        @endif

        {{-- Preview modal. Interactive quizzes list their questions + correct answers; file quizzes offer the download. --}}
        <template x-if="quiz">
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 role="dialog" aria-modal="true" :aria-label="quiz.title">
                <div class="absolute inset-0 bg-black/70" @click="close()" aria-hidden="true"></div>

                <div class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-card border border-line bg-surface shadow-hero">
                    <div class="flex items-start justify-between gap-4 border-b border-line px-4 py-3">
                        <div class="min-w-0">
                            <h2 class="truncate font-extrabold text-ink" x-text="quiz.title"></h2>
                            <p class="truncate text-xs text-ink-2">
                                <span x-text="quiz.teacher"></span>
                                <span x-show="quiz.questions.length">
                                    &middot; <span x-text="quiz.questions.length"></span> {{ __('soalan') }}
                                </span>
                            </p>
                        </div>

                        <button type="button" class="btn-ghost btn-sm shrink-0" @click="close()" x-init="$el.focus()">
                            <x-icon name="x" class="h-4 w-4" />
                            <span class="sr-only">{{ __('Tutup') }}</span>
                        </button>
                    </div>

                    <div class="overflow-y-auto px-4 py-4">
                        <template x-if="quiz.type === 'file'">
                            <div class="px-6 py-12 text-center">
                                <p class="font-bold text-ink">{{ __('Kuiz ini ialah fail untuk dicetak.') }}</p>
                                <p class="mx-auto mt-1 max-w-prose text-sm text-ink-2">
                                    {{ __('Ia tiada soalan dalam sistem. Muat turun fail untuk melihatnya.') }}
                                </p>
                                <a :href="quiz.downloadUrl" class="btn-primary btn-sm mt-4">
                                    <x-icon name="download" class="h-4 w-4" />
                                    {{ __('Muat Turun') }}
                                </a>
                            </div>
                        </template>

                        <template x-if="quiz.type === 'interactive' && ! quiz.questions.length">
                            <div class="px-6 py-12 text-center">
                                <p class="font-bold text-ink">{{ __('Kuiz ini belum ada soalan.') }}</p>
                            </div>
                        </template>

                        <template x-if="quiz.questions.length">
                            <ol class="space-y-4">
                                <template x-for="(question, index) in quiz.questions" :key="index">
                                    <li class="rounded-control border border-line p-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <p class="font-bold text-ink">
                                                <span x-text="(index + 1) + '.'"></span>
                                                <span x-text="question.text"></span>
                                            </p>
                                            <span class="chip shrink-0 bg-surface-2 text-ink-2">
                                                <span x-text="question.points"></span> {{ __('mata') }}
                                            </span>
                                        </div>

                                        <p class="mt-1 text-xs text-ink-2"
                                           x-text="question.multiple ? '{{ __('Pilih semua yang betul') }}' : '{{ __('Pilih satu jawapan') }}'"></p>

                                        <ul class="mt-2 space-y-1">
                                            <template x-for="option in question.options" :key="option.letter">
                                                <li class="flex items-start gap-2 text-sm"
                                                    :class="option.correct ? 'font-bold text-success' : 'text-ink-2'">
                                                    <span class="w-5 shrink-0 tabular-nums" x-text="option.letter + '.'"></span>
                                                    <span x-text="option.text"></span>
                                                    <template x-if="option.correct">
                                                        <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
                                                    </template>
                                                </li>
                                            </template>
                                        </ul>
                                    </li>
                                </template>
                            </ol>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-admin-layout>
