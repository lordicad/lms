<x-cikgu-layout
    :title="__('Kuiz Saya')"
    :heading="__('Kuiz Saya')"
    :sub="__('Kuiz interaktif yang menanda sendiri, dan kuiz bercetak')">

    <x-cikgu-filters :subjects="$subjects" :grades="$grades" :action="route('cikgu.kuiz.index')">
        <a href="{{ route('cikgu.kuiz.mod') }}" class="tp-btn" style="margin-left:auto">
            <x-icon name="plus" class="h-4 w-4" />
            {{ __('Kuiz Baru') }}
        </a>
    </x-cikgu-filters>

    <div x-data="{ quiz: null, open(data) { this.quiz = data }, close() { this.quiz = null } }">

    @if ($quizzes->isEmpty())
        <div class="tp-empty">
            <span style="font-size:30px">📝</span>
            <h3 class="tp-g" style="font-size:19px;font-weight:800;color:#28293F">{{ __('Belum ada kuiz') }}</h3>
            <p style="margin:0;font-size:14.5px;color:#8B8AA3;max-width:420px">{{ __('Bina kuiz interaktif yang menyemak jawapan sendiri, atau muat naik kuiz bercetak.') }}</p>
            <a href="{{ route('cikgu.kuiz.mod') }}" class="tp-btn" style="margin-top:6px">{{ __('Cipta Kuiz Pertama') }}</a>
        </div>
    @else
        <div class="tp-list">
            @foreach ($quizzes as $quiz)
                @php($subject = $quiz->chapter->subject)
                <div class="tp-listcard" style="padding:18px 20px">
                    <div style="display:flex;flex-direction:column;gap:8px;min-width:0;flex:1">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span class="tp-g" style="font-weight:800;font-size:16px;color:#28293F">{{ $quiz->title }}</span>
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
                                    <span class="tp-meta">🕐 {{ __(':count min', ['count' => $quiz->duration_minutes]) }}</span>
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
                            'questions' => $quiz->questions->map(fn ($question) => [
                                'text' => $question->question_text,
                                'points' => $question->points,
                                'options' => $question->options->map(fn ($option) => [
                                    'letter' => $option->letter(),
                                    'text' => $option->option_text,
                                    'correct' => (bool) $option->is_correct,
                                ])->all(),
                            ])->all(),
                        ]))">👁 {{ __('Lihat Soalan') }}</button>
                        <a href="{{ route('cikgu.kuiz.statistik', $quiz) }}" class="tp-btn-ghost" style="flex-shrink:0">📊 {{ __('Statistik') }}</a>
                    @else
                        <a href="{{ route('muat-turun.kuiz', $quiz) }}" class="tp-btn-ghost" style="flex-shrink:0">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                            {{ __('Fail') }}
                        </a>
                    @endif

                    <a href="{{ route('cikgu.kuiz.edit', $quiz) }}" class="tp-icon-action" style="flex-shrink:0" title="{{ __('Sunting') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                        <span class="sr-only">{{ __('Sunting :title', ['title' => $quiz->title]) }}</span>
                    </a>

                    <form method="POST" action="{{ route('cikgu.kuiz.destroy', $quiz) }}" style="flex-shrink:0"
                          onsubmit='return confirm(@js(__("Padam kuiz \":title\"? Semua soalan dan percubaan murid akan dipadam sekali. Tindakan ini tidak boleh dibatalkan.", ["title" => $quiz->title])))'>
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

        {{-- Read-only question preview (teacher view; mirrors the admin quiz preview) --}}
        <template x-if="quiz">
            <div style="position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;padding:16px" role="dialog" aria-modal="true" :aria-label="quiz.title">
                <div @click="close()" aria-hidden="true" style="position:absolute;inset:0;background:rgba(20,18,40,.6)"></div>

                <div style="position:relative;display:flex;flex-direction:column;max-height:90vh;width:100%;max-width:640px;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 24px 70px rgba(46,44,80,.4)">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:16px 20px;border-bottom:1px solid rgba(46,44,80,.08)">
                        <div style="min-width:0">
                            <h2 class="tp-g" style="margin:0;font-weight:800;font-size:17px;color:#28293F;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="quiz.title"></h2>
                            <p style="margin:2px 0 0;font-size:12.5px;color:#8B8AA3"><span x-text="quiz.questions.length"></span> {{ __('soalan') }}</p>
                        </div>
                        <button type="button" @click="close()" x-init="$el.focus()" title="{{ __('Tutup') }}"
                                style="flex-shrink:0;width:34px;height:34px;border:none;border-radius:9px;background:#F1F0E8;color:#6C6F87;cursor:pointer;font-size:15px">✕</button>
                    </div>

                    <div style="overflow-y:auto;padding:16px 20px">
                        <template x-if="! quiz.questions.length">
                            <p style="text-align:center;color:#8B8AA3;padding:32px 0;font-weight:700">{{ __('Kuiz ini belum ada soalan.') }}</p>
                        </template>

                        <template x-if="quiz.questions.length">
                            <ol style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:14px">
                                <template x-for="(question, index) in quiz.questions" :key="index">
                                    <li style="border:1.5px solid rgba(46,44,80,.1);border-radius:13px;padding:14px 16px">
                                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
                                            <p class="tp-g" style="margin:0;font-weight:800;font-size:14.5px;color:#28293F"><span x-text="(index + 1) + '.'"></span> <span x-text="question.text"></span></p>
                                            <span style="flex-shrink:0;font-size:12px;font-weight:800;color:#8B8AA3" x-text="question.points + ' {{ __('mata') }}'"></span>
                                        </div>
                                        <div style="display:flex;flex-direction:column;gap:6px;margin-top:10px">
                                            <template x-for="(option, oIndex) in question.options" :key="oIndex">
                                                <div class="tp-optview" :class="{ 'is-correct': option.correct }">
                                                    <span class="tp-optview-badge" x-text="option.letter"></span>
                                                    <span style="flex:1;min-width:0;font-size:13.5px;color:#28293F" x-text="option.text"></span>
                                                    <span x-show="option.correct" style="flex-shrink:0;font-size:12px;font-weight:800;color:#0F7A68">✓ {{ __('Betul') }}</span>
                                                </div>
                                            </template>
                                        </div>
                                    </li>
                                </template>
                            </ol>
                        </template>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-cikgu-layout>
