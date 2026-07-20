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

        <x-year-subject-filter :action="route('admin.kandungan.kuiz')" :grades="$grades" :subjects="$subjects" />

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
                                            'subtitle' => collect([$quiz->teacher?->name, $quiz->chapter->subject->displayName(), $quiz->chapter->grade->name])->filter()->implode(' · '),
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

        {{-- Preview modal (WeLearn Admin design): gradient header + green question body. --}}
        <template x-if="quiz">
            <x-content-preview obj="quiz" :pill="'📝 '.__('Kuiz')">
                <div style="overflow-y:auto;background:linear-gradient(180deg,#E9F7F2,#FAF9F5)">
                    <template x-if="quiz.type === 'file'">
                        <div style="padding:48px 28px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:6px">
                            <p style="margin:0;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:#28293F">{{ __('Kuiz ini ialah fail untuk dicetak.') }}</p>
                            <p style="margin:0;font-size:13.5px;color:#6C6F87;max-width:360px">{{ __('Ia tiada soalan dalam sistem. Muat turun fail untuk melihatnya.') }}</p>
                            <a :href="quiz.downloadUrl" style="margin-top:12px;display:inline-flex;align-items:center;gap:8px;min-height:44px;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;padding:0 20px;text-decoration:none">⬇ {{ __('Muat Turun') }}</a>
                        </div>
                    </template>

                    <template x-if="quiz.type === 'interactive' && ! quiz.questions.length">
                        <div style="padding:48px 28px;text-align:center">
                            <p style="margin:0;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:#28293F">{{ __('Kuiz ini belum ada soalan.') }}</p>
                        </div>
                    </template>

                    <template x-if="quiz.questions.length">
                        <div style="padding:24px;display:flex;flex-direction:column;gap:16px">
                            <div style="display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #B7E3D8;border-radius:12px;padding:10px 16px;align-self:flex-start">
                                <span style="width:16px;height:16px;border-radius:5px;background:#E9F7F2;border:1.5px solid #0F7A68;display:inline-block;flex-shrink:0"></span>
                                <span style="font-size:12.5px;font-weight:800;color:#0F7A68;font-family:'Geist',sans-serif">{{ __('Jawapan betul ditanda hijau') }}</span>
                            </div>

                            <template x-for="(question, index) in quiz.questions" :key="index">
                                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:14px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 2px 8px rgba(46,44,80,.04)">
                                    <div style="display:flex;gap:10px;align-items:flex-start">
                                        <span style="flex-shrink:0;width:26px;height:26px;border-radius:8px;background:#E6F5F1;color:#0F7A68;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:13px" x-text="index + 1"></span>
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:#28293F;line-height:1.4" x-text="question.text"></span>
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:8px;padding-left:36px">
                                        <template x-for="option in question.options" :key="option.letter">
                                            <div :style="(option.correct ? 'border:1px solid #17907B;background:#E6F5F1' : 'border:1px solid rgba(46,44,80,.08);background:#F6F5F0') + ';display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px'">
                                                <span :style="(option.correct ? 'background:#17907B;color:#fff' : 'background:#EDECE4;color:#8B8AA3') + ';width:24px;height:24px;flex-shrink:0;border-radius:50%;display:grid;place-items:center;font-family:\'Geist\',sans-serif;font-weight:800;font-size:11.5px'" x-text="option.letter"></span>
                                                <span style="font-size:13.5px;font-weight:700;color:#4A4B63" x-text="option.text"></span>
                                                <template x-if="option.correct">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#0F7A68" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:auto;flex-shrink:0"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </x-content-preview>
        </template>
    </div>
</x-admin-layout>
