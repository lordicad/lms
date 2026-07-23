<x-cikgu-layout
    :title="__('Kuiz Saya')"
    :heading="__('Kuiz Saya')"
    :sub="__('Kuiz interaktif yang menanda sendiri, dan kuiz bercetak')">

    {{-- Total quizzes created by this teacher (all-time, not the filtered count). --}}
    <div class="tp-stat" style="max-width:340px;margin-bottom:18px">
        <div style="display:flex;align-items:center;gap:10px">
            <span class="tp-stat-ico" style="background:#FEF0CE"><x-icon name="quiz" class="h-5 w-5" style="color:#8A6A12" /></span>
            <span class="tp-stat-label">{{ __('Kuiz Saya') }}</span>
        </div>
        <span class="tp-stat-value">{{ number_format($totalQuizzes) }}</span>
        <span style="font-size:12.5px;font-weight:700;color:var(--tp-muted)">{{ __('Fail & interaktif') }}</span>
    </div>

    <x-year-subject-filter :subjects="$subjects" :grades="$grades" :filter="$filter" with-chapter :action="route('cikgu.kuiz.index')">
        <a href="{{ route('cikgu.kuiz.mod') }}" class="tp-btn" style="margin-left:auto">
            <x-icon name="plus" class="h-4 w-4" />
            {{ __('Kuiz Baru') }}
        </a>
    </x-year-subject-filter>

    <div x-data="{ quiz: null, open(data) { this.quiz = data }, close() { this.quiz = null } }">

    @if ($quizzes->isEmpty())
        <div class="tp-empty">
            <span style="font-size:30px">📝</span>
            <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada kuiz') }}</h3>
            <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:420px">{{ __('Bina kuiz interaktif yang menyemak jawapan sendiri, atau muat naik kuiz bercetak.') }}</p>
            <a href="{{ route('cikgu.kuiz.mod') }}" class="tp-btn" style="margin-top:6px">{{ __('Cipta Kuiz Pertama') }}</a>
        </div>
    @else
        <div class="tp-list">
            @foreach ($quizzes as $quiz)
                @php($subject = $quiz->chapter->subject)
                <div class="tp-listcard" style="padding:18px 20px">
                    <div style="display:flex;flex-direction:column;gap:8px;min-width:0;flex:1">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span class="tp-g" style="font-weight:800;font-size:16px;color:var(--tp-ink)">{{ $quiz->title }}</span>
                            @if ($quiz->isInteractive())
                                <span class="tp-tag" style="background:#DCF2EE;color:#0F7A68">{{ __('Interaktif') }}</span>
                            @else
                                <span class="tp-tag" style="background:#E4EEF9;color:#2E6CA8">{{ __('Bercetak') }}</span>
                            @endif
                            @unless ($quiz->is_published)
                                <span class="tp-tag" style="background:#FEF0CE;color:#8A6A12">{{ __('Draf') }}</span>
                            @endunless
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="tp-tag" style="background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }})">{{ $subject->name }}</span>
                            <span class="tp-meta">{{ $quiz->chapter->grade->name }}</span>
                            <span class="tp-meta">Bab {{ $quiz->chapter->number }}</span>
                            @if ($quiz->isInteractive())
                                <span class="tp-meta">{{ __(':count soalan', ['count' => $quiz->questions_count]) }}</span>
                                <span class="tp-meta">{{ __(':count percubaan', ['count' => $quiz->completed_attempts_count]) }}</span>
                                @if ($quiz->duration_minutes)
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:4px"><x-icon name="clock" class="h-4 w-4" />{{ __(':count min', ['count' => $quiz->duration_minutes]) }}</span>
                                @endif
                            @endif
                        </div>
                        @if ($quiz->isInteractive() && $quiz->questions_count === 0)
                            <span style="font-size:13px;font-weight:700;color:#C24936">{{ __('Kuiz ini belum ada soalan, jadi murid belum boleh mencubanya.') }}</span>
                        @endif
                    </div>

                    @if ($quiz->isInteractive())
                        <button type="button" class="tp-btn tp-btn-sm" style="flex-shrink:0" @click="open(@js([
                            'title' => $quiz->title,
                            'subtitle' => collect([$quiz->chapter->subject->displayName(), $quiz->chapter->grade->name, __(':count soalan', ['count' => $quiz->questions_count])])->filter()->implode(' · '),
                            'questions' => $quiz->questions->map(fn ($question) => [
                                'text' => $question->question_text,
                                'points' => $question->points,
                                'options' => $question->options->map(fn ($option) => [
                                    'letter' => $option->letter(),
                                    'text' => $option->option_text,
                                    'correct' => (bool) $option->is_correct,
                                ])->all(),
                            ])->all(),
                        ]))"><x-icon name="eye" class="h-4 w-4" />{{ __('Lihat Soalan') }}</button>
                        <a href="{{ route('cikgu.kuiz.statistik', $quiz) }}" class="tp-btn-ghost" style="flex-shrink:0"><x-icon name="chart" class="h-4 w-4" />{{ __('Statistik') }}</a>
                    @else
                        <a href="{{ route('muat-turun.kuiz', $quiz) }}" class="tp-btn-ghost" style="flex-shrink:0">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            {{ __('Fail') }}
                        </a>
                    @endif

                    <a href="{{ route('cikgu.kuiz.edit', $quiz) }}" class="tp-btn-ghost" style="flex-shrink:0;display:inline-flex;align-items:center;gap:6px">
                        <x-icon name="pencil" class="h-4 w-4" />{{ __('Sunting') }}
                    </a>

                    <form method="POST" action="{{ route('cikgu.kuiz.destroy', $quiz) }}" style="flex-shrink:0"
                          onsubmit="return confirm(@js(__("Padam kuiz \":title\"? Semua soalan dan percubaan murid akan dipadam sekali. Tindakan ini tidak boleh dibatalkan.", ["title" => $quiz->title])))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="tp-icon-action tp-icon-danger" title="{{ __('Padam') }}">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            <span class="sr-only">{{ __('Padam :title', ['title' => $quiz->title]) }}</span>
                        </button>
                    </form>
                </div>
            @endforeach
        </div>

        <div>{{ $quizzes->links() }}</div>
    @endif

        {{-- Read-only question preview (WeLearn Admin design: gradient header + green body) --}}
        <template x-if="quiz">
            <x-content-preview obj="quiz" :pill="'📝 '.__('Kuiz')">
                <div style="overflow-y:auto;background:linear-gradient(180deg,#E9F7F2,#FAF9F5)">
                    <template x-if="! quiz.questions.length">
                        <p style="text-align:center;color:#6C6F87;padding:44px 0;font-weight:700">{{ __('Kuiz ini belum ada soalan.') }}</p>
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
                                        <template x-for="(option, oIndex) in question.options" :key="oIndex">
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
</x-cikgu-layout>
