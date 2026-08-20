<x-cikgu-layout
    :title="__('Kuiz Saya')"
    :heading="__('Kuiz Saya')"
    heading-icon="quiz"
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
        <div class="tp-newbtn-wrap" style="margin-left:auto">
            <a href="{{ route('cikgu.kuiz.mod') }}" class="tp-btn">
                <x-icon name="plus" class="h-4 w-4" />
                {{ __('Kuiz Baru') }}
            </a>
        </div>
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
                    {{-- Subject icon square, colour-matched to the subject. --}}
                    <span style="width:60px;height:60px;border-radius:14px;background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }});display:grid;place-items:center;flex-shrink:0"><x-icon :name="$subject->iconName()" class="h-7 w-7" /></span>

                    <div style="display:flex;flex-direction:column;gap:8px;min-width:0;flex:1">
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                            <span class="tp-g" style="font-weight:800;font-size:17px;color:var(--tp-ink)">{{ $quiz->localizedTitle() }}</span>
                            @if ($quiz->isInteractive())
                                <span class="tp-tag" style="background:#DCF2EE;color:#0F7A68">{{ __('Interaktif') }}</span>
                            @else
                                <span class="tp-tag" style="background:#E4EEF9;color:#2E6CA8">{{ __('Bercetak') }}</span>
                            @endif
                            @unless ($quiz->is_published)
                                <span class="tp-tag" style="background:#FEF0CE;color:#8A6A12">{{ __('Draf') }}</span>
                            @endunless
                        </div>
                        {{-- Detail row, each item led by an icon. --}}
                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
                            <span class="tp-tag" style="background:rgb({{ $subject->rgb }} / .14);color:rgb({{ $subject->rgb }})">{{ $subject->name }}</span>
                            <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="graduation" class="h-4 w-4" style="color:#0F7A68" />{{ $quiz->chapter->grade->displayName() }}</span>
                            <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="book" class="h-4 w-4" style="color:#0F7A68" />{{ __('Bab :n', ['n' => $quiz->chapter->number]) }}</span>
                            @if ($quiz->isInteractive())
                                <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="help-circle" class="h-4 w-4" style="color:#0F7A68" />{{ __(':count soalan', ['count' => $quiz->questions_count]) }}</span>
                                <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="users" class="h-4 w-4" style="color:var(--tp-muted-2)" />{{ __(':count percubaan', ['count' => $quiz->completed_attempts_count]) }}</span>
                                @if ($quiz->duration_minutes)
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="clock" class="h-4 w-4" style="color:var(--tp-muted-2)" />{{ __(':count min', ['count' => $quiz->duration_minutes]) }}</span>
                                @endif
                            @endif
                        </div>
                        @if ($quiz->isInteractive() && $quiz->questions_count === 0)
                            <span style="font-size:13px;font-weight:700;color:#C24936">{{ __('Kuiz ini belum ada soalan, jadi murid belum boleh mencubanya.') }}</span>
                        @endif
                    </div>

                    {{-- Actions: on mobile they drop to their own row below the details. --}}
                    <div class="tp-listactions" style="display:flex;align-items:center;gap:14px;flex-shrink:0">
                    @if ($quiz->isInteractive())
                        <button type="button" class="tp-lihatbtn" style="flex-shrink:0;display:inline-flex;align-items:center;gap:7px;min-height:42px;border-radius:11px;border:1.5px solid #0F7A68;background:var(--tp-surface);color:#0F7A68;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;padding:0 16px;cursor:pointer" @click="open(@js([
                            'title' => $quiz->localizedTitle(),
                            'subtitle' => collect([$quiz->chapter->subject->displayName(), $quiz->chapter->grade->displayName(), __(':count soalan', ['count' => $quiz->questions_count])])->filter()->implode(' · '),
                            'questions' => $quiz->questions->map(fn ($question) => [
                                'text' => $question->localizedText(),
                                'points' => $question->points,
                                'options' => $question->options->map(fn ($option) => [
                                    'letter' => $option->letter(),
                                    'text' => $option->localizedText($question->source_locale),
                                    'correct' => (bool) $option->is_correct,
                                ])->all(),
                            ])->all(),
                        ]))">{{ __('Lihat Soalan') }}</button>
                        <a href="{{ route('cikgu.kuiz.statistik', $quiz) }}" class="tp-icon-action" style="flex-shrink:0;border:1.5px solid var(--tp-line-2)" title="{{ __('Statistik') }}">
                            <x-icon name="chart" class="h-[18px] w-[18px]" />
                            <span class="sr-only">{{ __('Statistik') }}</span>
                        </a>
                    @else
                        <a href="{{ route('muat-turun.kuiz', $quiz) }}" class="tp-btn-ghost" style="flex-shrink:0;display:inline-flex;align-items:center;gap:6px">
                            <x-icon name="download" class="h-4 w-4" />{{ __('Fail') }}
                        </a>
                    @endif

                    <a href="{{ route('cikgu.kuiz.edit', $quiz) }}" class="tp-icon-action" style="flex-shrink:0;border:1.5px solid var(--tp-line-2)" title="{{ __('Sunting') }}">
                        <x-icon name="pencil" class="h-[18px] w-[18px]" />
                        <span class="sr-only">{{ __('Sunting :title', ['title' => $quiz->title]) }}</span>
                    </a>

                    @php($delMsg = __('Padam kuiz ":title"? Semua soalan dan percubaan murid akan dipadam sekali. Tindakan ini tidak boleh dibatalkan.', ['title' => $quiz->title]))
                    <x-confirm-modal id="del-kuiz-{{ $quiz->id }}" :action="route('cikgu.kuiz.destroy', $quiz)"
                        :title="__('Padam kuiz?')" :message="$delMsg">
                        <button type="button" class="tp-icon-action tp-icon-danger" title="{{ __('Padam') }}" style="flex-shrink:0;background:#FDECEC;border:none;border-radius:14px">
                            <x-icon name="trash" class="h-[18px] w-[18px]" />
                            <span class="sr-only">{{ __('Padam :title', ['title' => $quiz->title]) }}</span>
                        </button>
                    </x-confirm-modal>
                    </div>
                </div>
            @endforeach
        </div>

        <div>{{ $quizzes->links('pagination.tp') }}</div>
    @endif

        {{-- Read-only question preview (WeLearn Admin design: gradient header + green body) --}}
        <template x-if="quiz">
            <x-content-preview obj="quiz">
                <div style="overflow-y:auto;background:linear-gradient(180deg,#E9F7F2,#FAF9F5)">
                    <template x-if="! quiz.questions.length">
                        <p style="text-align:center;color:#6C6F87;padding:44px 0;font-weight:700">{{ __('Kuiz ini belum ada soalan.') }}</p>
                    </template>

                    <template x-if="quiz.questions.length">
                        <div class="qb-preview" style="padding:24px;display:flex;flex-direction:column;gap:16px">
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
