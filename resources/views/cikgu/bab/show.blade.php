<x-cikgu-layout
    :title="__('Bab :number: :title', ['number' => $chapter->number, 'title' => $chapter->title])"
    :heading="$chapter->title"
    :sub="__('Kandungan anda dalam bab ini')">

    @php($isDark = ($theme ?? 'light') === 'dark')
    <div style="display:flex;flex-direction:column;gap:22px"
         x-data="{
             lesson: null,
             item: null,
             quiz: null,
             open(data) { this.lesson = data; document.body.classList.add('overflow-hidden'); },
             openMaterial(data) { this.item = data; document.body.classList.add('overflow-hidden'); },
             openQuiz(data) { this.quiz = data; document.body.classList.add('overflow-hidden'); },
             close() { this.lesson = null; this.item = null; this.quiz = null; document.body.classList.remove('overflow-hidden'); },
         }"
         @keydown.escape.window="close()">
        <a href="{{ route('cikgu.bab.index', ['subjek' => $subject->slug, 'tahun' => $grade->level]) }}" class="tp-back">← {{ __('Semua Bab') }}</a>

        <span style="align-self:flex-start;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;display:inline-flex;align-items:center;gap:6px"><x-icon :name="$subject->iconName()" class="h-[15px] w-[15px]" />{{ $subject->name }} – {{ $grade->displayName() }} – {{ __('Bab :number', ['number' => $chapter->number]) }}</span>

        @if ($chapter->description)
            <p style="margin:0;font-size:15px;color:var(--tp-muted-2);max-width:640px">{{ $chapter->description }}</p>
        @endif

        {{-- Videos --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="display:flex;align-items:center;gap:8px;font-size:17px;font-weight:800;color:var(--tp-ink)"><x-icon name="video" style="width:19px;height:19px;color:#0F7A68" />{{ __('Video') }} <span style="color:var(--tp-muted)">({{ $lessons->count() }})</span></h2>

            @if ($lessons->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum memuat naik video dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.video.create') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Video Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($lessons as $lesson)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;flex-shrink:0"><x-icon name="video" style="width:20px;height:20px" /></span>
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $lesson->title }}</span>
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                                    @if ($lesson->is_published)
                                        <span class="tp-tag" style="background:#DCF2EE;color:#0F7A68">{{ __('Diterbitkan') }}</span>
                                    @else
                                        <span class="tp-tag-neutral">{{ __('Draf') }}</span>
                                    @endif
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon :name="$lesson->isYoutube() ? 'youtube' : 'upload'" class="h-4 w-4" style="color:#0F7A68" />{{ $lesson->isYoutube() ? 'YouTube' : __('Muat naik') }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="calendar" class="h-4 w-4" style="color:#0F7A68" />{{ $lesson->created_at->format('d/m/Y') }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><img src="{{ asset('images/eye.png') }}" alt="" style="width:16px;height:16px;object-fit:contain">{{ $lesson->views_count }}</span>
                                </div>
                            </div>
                            <button type="button" class="tp-btn-outline" style="flex-shrink:0"
                                    @click="open(@js([
                                        'title' => $lesson->title,
                                        'subtitle' => collect([$subject->name, $grade->displayName(), __('Bab :number', ['number' => $chapter->number])])->filter()->implode(' · '),
                                        'kind' => $lesson->isYoutube() ? 'youtube' : 'upload',
                                        'src' => $lesson->isYoutube() ? $lesson->embedUrl() : $lesson->videoUrl(),
                                        'poster' => $lesson->thumbnailUrl(),
                                    ]))"><x-icon name="eye" class="h-4 w-4" />{{ __('Lihat') }}</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Materials --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="display:flex;align-items:center;gap:8px;font-size:17px;font-weight:800;color:var(--tp-ink)"><x-icon name="file" style="width:19px;height:19px;color:#0F7A68" />{{ __('Bahan') }} <span style="color:var(--tp-muted)">({{ $materials->count() }})</span></h2>

            @if ($materials->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum memuat naik bahan dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.bahan.create') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Bahan Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($materials as $material)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#FBE4ED;color:#B84A75;display:grid;place-items:center;flex-shrink:0"><x-icon :name="$material->iconName()" class="h-5 w-5" /></span>
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $material->title }}</span>
                                {{-- File facts: type, size, upload date, and download count - in the
                                     soft rose of the material theme. --}}
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:14px">
                                    <span style="background:#FBE4ED;color:#B84A75;border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800">{{ strtoupper($material->extension()) }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="file" class="h-4 w-4" style="color:#B84A75" />{{ $material->humanSize() }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="calendar" class="h-4 w-4" style="color:#B84A75" />{{ $material->created_at->format('d/m/Y') }}</span>
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="download" class="h-4 w-4" style="color:#B84A75" />{{ $material->download_count }}</span>
                                </div>
                            </div>
                            <button type="button" class="tp-btn-outline" style="flex-shrink:0"
                                    @click="openMaterial(@js([
                                        'title' => $material->title,
                                        'subtitle' => collect([$subject->name, $grade->displayName(), __('Bab :number', ['number' => $chapter->number])])->filter()->implode(' · '),
                                        'kind' => $material->previewKind(),
                                        'src' => $material->fileUrl(),
                                        'name' => $material->original_name,
                                        'type' => strtoupper($material->extension()),
                                        'size' => $material->humanSize(),
                                        'downloadUrl' => route('muat-turun.bahan', $material),
                                    ]))"><x-icon name="eye" class="h-4 w-4" />{{ __('Lihat') }}</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Quizzes --}}
        <section style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="display:flex;align-items:center;gap:8px;font-size:17px;font-weight:800;color:var(--tp-ink)"><x-icon name="quiz" style="width:19px;height:19px;color:#0F7A68" />{{ __('Kuiz') }} <span style="color:var(--tp-muted)">({{ $quizzes->count() }})</span></h2>

            @if ($quizzes->isEmpty())
                <div class="tp-empty" style="padding:26px">
                    <p style="margin:0;font-size:14px;color:var(--tp-muted)">{{ __('Anda belum mencipta kuiz dalam bab ini.') }}</p>
                    <a href="{{ route('cikgu.kuiz.index') }}" class="tp-btn-ghost" style="margin-top:8px">+ {{ __('Kuiz Baharu') }}</a>
                </div>
            @else
                <div class="tp-list">
                    @foreach ($quizzes as $quiz)
                        <div class="tp-listcard">
                            <span style="width:40px;height:40px;border-radius:12px;background:#FEF0CE;color:#8A6A12;display:grid;place-items:center;flex-shrink:0"><x-icon name="quiz" style="width:20px;height:20px" /></span>
                            <div style="display:flex;flex-direction:column;gap:4px;min-width:0;flex:1">
                                <span class="tp-g" style="font-weight:800;font-size:15px;color:var(--tp-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $quiz->title }}</span>
                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                                    <span class="tp-tag-neutral">{{ $quiz->isInteractive() ? __('Interaktif') : __('Fail') }}</span>
                                    @if ($quiz->is_published)
                                        <span class="tp-tag" style="background:#DCF2EE;color:#0F7A68">{{ __('Diterbitkan') }}</span>
                                    @else
                                        <span class="tp-tag-neutral">{{ __('Draf') }}</span>
                                    @endif
                                    @if ($quiz->isInteractive())
                                        <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="help-circle" class="h-4 w-4" style="color:#8A6A12" />{{ __(':count soalan', ['count' => $quiz->questions_count]) }}</span>
                                    @endif
                                    <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="calendar" class="h-4 w-4" style="color:#8A6A12" />{{ $quiz->created_at->format('d/m/Y') }}</span>
                                    @if ($quiz->isInteractive())
                                        <span class="tp-meta" style="display:inline-flex;align-items:center;gap:6px"><x-icon name="users" class="h-4 w-4" style="color:#8A6A12" />{{ __(':count percubaan', ['count' => $quiz->completed_attempts_count]) }}</span>
                                    @endif
                                </div>
                            </div>
                            <button type="button" class="tp-btn-outline" style="flex-shrink:0"
                                    @click="openQuiz(@js([
                                        'title' => $quiz->localizedTitle(),
                                        'subtitle' => collect([$subject->name, $grade->displayName(), __('Bab :number', ['number' => $chapter->number])])->filter()->implode(' · '),
                                        'type' => $quiz->type,
                                        'downloadUrl' => $quiz->isFile() ? route('muat-turun.kuiz', $quiz) : null,
                                        'questions' => $quiz->questions->map(fn ($question) => [
                                            'text' => $question->localizedText(),
                                            'multiple' => $question->isMultiple(),
                                            'points' => $question->points,
                                            'options' => $question->options->map(fn ($option) => [
                                                'letter' => $option->letter(),
                                                'text' => $option->localizedText($question->source_locale),
                                                'correct' => (bool) $option->is_correct,
                                            ])->all(),
                                        ])->all(),
                                    ]))"><x-icon name="eye" class="h-4 w-4" />{{ __('Lihat') }}</button>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Video preview modal - a look, not a lesson (no view counted); x-if so closing destroys
             the player and its audio. Same shell the admin content preview uses. --}}
        <template x-if="lesson">
            <x-content-preview obj="lesson">
                <div style="overflow-y:auto;background:#000;height:min(72vh,620px)">
                    <template x-if="lesson.kind === 'youtube'">
                        <iframe style="width:100%;height:100%;border:0;display:block" :src="lesson.src" :title="lesson.title"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen></iframe>
                    </template>
                    <template x-if="lesson.kind === 'upload'">
                        <video style="width:100%;height:100%;object-fit:contain;background:#000;display:block" controls preload="metadata"
                               :src="lesson.src" :poster="lesson.poster"></video>
                    </template>
                </div>
            </x-content-preview>
        </template>

        {{-- Material preview modal - PDFs and images render in place, other files show a document
             card with a download. Same shell the admin/teacher content lists use. --}}
        <template x-if="item">
            <x-content-preview obj="item">
                <template x-if="item.kind === 'pdf'">
                    <iframe style="width:100%;height:min(72vh,620px);border:0;display:block;background:#000" :src="item.src" :title="item.title"></iframe>
                </template>

                <template x-if="item.kind === 'image'">
                    <div style="overflow:auto;height:min(72vh,620px);display:flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#EDF3FA,#F6F5F0);padding:20px">
                        <img :src="item.src" :alt="item.title" style="max-width:100%;max-height:100%;object-fit:contain;border-radius:8px;box-shadow:0 8px 28px rgba(46,44,80,.14)">
                    </div>
                </template>

                <template x-if="item.kind === 'none'">
                    <div style="overflow-y:auto;padding:28px;background:linear-gradient(180deg,#EDF3FA,#F6F5F0);display:flex;flex-direction:column;align-items:center;gap:18px">
                        <div style="width:min(440px,100%);aspect-ratio:1/1.28;background:#fff;border:1px solid rgba(46,44,80,.1);border-radius:10px;box-shadow:0 8px 28px rgba(46,44,80,.14);padding:32px 28px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;position:relative">
                            <span style="position:absolute;top:18px;right:18px;background:#3E86C9;color:#fff;border-radius:8px;padding:5px 11px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800" x-text="item.type"></span>
                            <x-icon name="file" class="h-12 w-12" style="color:#6C6F87" />
                            <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:#28293F;text-align:center;word-break:break-word" x-text="item.name"></span>
                            <span style="font-size:12.5px;color:#6C6F87;font-weight:700"><span x-text="item.type"></span> · <span x-text="item.size"></span></span>
                            <a :href="item.downloadUrl" style="margin-top:6px;display:inline-flex;align-items:center;gap:8px;min-height:42px;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px;padding:0 18px;text-decoration:none"><x-icon name="download" class="h-4 w-4" />{{ __('Muat Turun') }}</a>
                        </div>
                        <span style="font-size:12.5px;color:#6C6F87;font-weight:700">{{ __('Fail ini tidak boleh dipaparkan dalam pelayar. Muat turun untuk membukanya.') }}</span>
                    </div>
                </template>
            </x-content-preview>
        </template>

        {{-- Quiz preview modal - questions with the correct answer marked, or a download card for a
             file quiz. Same body as the content-list preview. --}}
        <template x-if="quiz">
            <x-content-preview obj="quiz">
                <div style="overflow-y:auto;background:{{ $isDark ? 'linear-gradient(180deg,#141A20,#1B232C)' : 'linear-gradient(180deg,#E9F7F2,#FAF9F5)' }}">
                    <template x-if="quiz.type === 'file'">
                        <div style="padding:48px 28px;text-align:center;display:flex;flex-direction:column;align-items:center;gap:6px">
                            <p style="margin:0;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:{{ $isDark ? '#F3F5F8' : '#28293F' }}">{{ __('Kuiz ini ialah fail untuk dicetak.') }}</p>
                            <p style="margin:0;font-size:13.5px;color:{{ $isDark ? '#9AA3B2' : '#6C6F87' }};max-width:360px">{{ __('Ia tiada soalan dalam sistem. Muat turun fail untuk melihatnya.') }}</p>
                            <a :href="quiz.downloadUrl" style="margin-top:12px;display:inline-flex;align-items:center;gap:8px;min-height:44px;border-radius:12px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14px;padding:0 20px;text-decoration:none">⬇ {{ __('Muat Turun') }}</a>
                        </div>
                    </template>

                    <template x-if="quiz.type === 'interactive' && ! quiz.questions.length">
                        <div style="padding:48px 28px;text-align:center">
                            <p style="margin:0;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:{{ $isDark ? '#F3F5F8' : '#28293F' }}">{{ __('Kuiz ini belum ada soalan.') }}</p>
                        </div>
                    </template>

                    <template x-if="quiz.questions.length">
                        <div style="padding:24px;display:flex;flex-direction:column;gap:16px">
                            <div style="display:flex;align-items:center;gap:8px;background:{{ $isDark ? '#1E2732' : '#fff' }};border:1px solid {{ $isDark ? 'rgba(94,234,212,.35)' : '#B7E3D8' }};border-radius:12px;padding:10px 16px;align-self:flex-start">
                                <span style="width:16px;height:16px;border-radius:5px;background:{{ $isDark ? 'rgba(94,234,212,.15)' : '#E9F7F2' }};border:1.5px solid {{ $isDark ? '#5EEAD4' : '#0F7A68' }};display:inline-block;flex-shrink:0"></span>
                                <span style="font-size:12.5px;font-weight:800;color:{{ $isDark ? '#5EEAD4' : '#0F7A68' }};font-family:'Geist',sans-serif">{{ __('Jawapan betul ditanda hijau') }}</span>
                            </div>

                            <template x-for="(question, index) in quiz.questions" :key="index">
                                <div style="background:{{ $isDark ? '#1E2732' : '#fff' }};border:1px solid {{ $isDark ? 'rgba(255,255,255,.08)' : 'rgba(46,44,80,.08)' }};border-radius:14px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 2px 8px rgba(46,44,80,{{ $isDark ? '.25' : '.04' }})">
                                    <div style="display:flex;gap:10px;align-items:flex-start">
                                        <span style="flex-shrink:0;width:26px;height:26px;border-radius:8px;background:{{ $isDark ? 'rgba(23,144,123,.22)' : '#E6F5F1' }};color:{{ $isDark ? '#5EEAD4' : '#0F7A68' }};display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:13px" x-text="index + 1"></span>
                                        <span style="font-family:'Geist',sans-serif;font-weight:800;font-size:15px;color:{{ $isDark ? '#F3F5F8' : '#28293F' }};line-height:1.4" x-text="question.text"></span>
                                    </div>
                                    <div style="display:flex;flex-direction:column;gap:8px;padding-left:36px">
                                        <template x-for="option in question.options" :key="option.letter">
                                            <div :style="(option.correct ? 'border:1px solid {{ $isDark ? '#2BB39B' : '#17907B' }};background:{{ $isDark ? 'rgba(43,179,155,.18)' : '#E6F5F1' }}' : 'border:1px solid {{ $isDark ? 'rgba(255,255,255,.07)' : 'rgba(46,44,80,.08)' }};background:{{ $isDark ? '#26313E' : '#F6F5F0' }}') + ';display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px'">
                                                <span :style="(option.correct ? 'background:#17907B;color:#fff' : 'background:{{ $isDark ? '#2E3A47' : '#EDECE4' }};color:{{ $isDark ? '#8A93A0' : '#8B8AA3' }}') + ';width:24px;height:24px;flex-shrink:0;border-radius:50%;display:grid;place-items:center;font-family:\'Geist\',sans-serif;font-weight:800;font-size:11.5px'" x-text="option.letter"></span>
                                                <span style="font-size:13.5px;font-weight:700;color:{{ $isDark ? '#C6CDD6' : '#4A4B63' }}" x-text="option.text"></span>
                                                <template x-if="option.correct">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="{{ $isDark ? '#5EEAD4' : '#0F7A68' }}" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:auto;flex-shrink:0"><polyline points="20 6 9 17 4 12"></polyline></svg>
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
