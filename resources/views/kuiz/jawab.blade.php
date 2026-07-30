<x-student-layout :title="$quiz->title">
    <style>
        /* Answer options: a colour-tinted card each, turning solid when picked (colours set per
           option via --tint / --solid). Letter badge left, answer in the middle, tick on the right. */
        .qopt2 { position:relative; display:flex; align-items:center; gap:14px; border-radius:14px; padding:14px 18px; cursor:pointer; transition:transform .1s ease-out, box-shadow .12s; background:var(--tint); }
        .qopt2:hover { transform:translateY(-1px); }
        .qopt2 input { position:absolute; opacity:0; width:0; height:0; }
        .qopt2:has(input:checked) { background:var(--solid); box-shadow:0 10px 24px rgba(46,44,80,.18); }
        .q2letter { width:36px; height:36px; border-radius:10px; flex-shrink:0; display:grid; place-items:center; font-family:'Geist',sans-serif; font-weight:800; font-size:14px; background:#fff; color:var(--solid); box-shadow:0 1px 3px rgba(46,44,80,.12); }
        .qopt2:has(input:checked) .q2letter { background:rgba(255,255,255,.25); color:#fff; box-shadow:none; }
        .q2text { flex:1; min-width:0; font-family:'Geist',sans-serif; font-weight:800; font-size:15px; color:var(--wl-ink); }
        .qopt2:has(input:checked) .q2text { color:#fff; }
        .q2radio { width:26px; height:26px; flex-shrink:0; border-radius:50%; border:2px solid rgba(40,40,60,.18); background:#fff; display:grid; place-items:center; color:transparent; }
        .qopt2.check .q2radio { border-radius:8px; }
        .qopt2:has(input:checked) .q2radio { background:#fff; border-color:#fff; color:var(--solid); }
        /* The question card. Flex/gap live here (not inline) so Alpine's x-show does not drop the
           display back to block on reveal — which would collapse the gap and stack the rows. */
        .qcard { display:flex; flex-direction:column; gap:22px; }
        /* Footer question jumper */
        .qnum { width:44px; height:44px; border-radius:12px; cursor:pointer; font-family:'Geist',sans-serif; font-weight:800; font-size:15px; border:1.5px solid var(--wl-line-2); background:var(--wl-surface); color:#4A4B63; transition:all .12s; }
        .qnum.answered { border-color:#17907B; color:#0F7A68; }
        .qnum.active { border:none; background:#17907B; color:#fff; }
    </style>

    <div style="display:flex;flex-direction:column;gap:18px;max-width:900px;margin:16px auto 0;width:100%"
         x-data="quizRunner({ total: {{ $questions->count() }}, secondsLeft: {{ $secondsLeft === null ? 'null' : $secondsLeft }}, labels: { answered: @js(__('dijawab')) } })"
         x-init="start()">

        <noscript>
            <div style="background:#FEF0CE;border-radius:14px;padding:14px 18px;font-weight:700;font-size:14px;color:#8A6A12">{{ __('JavaScript perlu dihidupkan untuk menjawab kuiz ini.') }}</div>
        </noscript>

        <form method="POST" action="{{ route('kuiz.hantar', $attempt) }}" x-ref="form" @submit="submitting = true"
              style="display:flex;flex-direction:column;gap:18px">
            @csrf
            @foreach ($questions as $index => $question)
                <section x-show="current === {{ $index }}" @if ($index > 0) x-cloak @endif class="qcard"
                         style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;padding:26px;box-shadow:0 10px 30px var(--wl-line)">
                    {{-- Title + timer, at the top of the card. --}}
                    <div style="display:flex;align-items:center;gap:16px">
                        <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink);flex:1;min-width:0">{{ $quiz->title }}</h2>
                        @if ($secondsLeft !== null)
                            {{-- Object syntax so the binding does not wipe the static pill styles. --}}
                            <span :style="secondsLeft < 60 ? { background: '#FDE7E0', color: '#C24936' } : { background: '#FBEECB', color: '#8A6A12' }"
                                  style="display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:8px 16px;font-family:'Geist',sans-serif;font-weight:800;font-size:15px"><x-icon name="clock" style="width:17px;height:17px" /><span x-text="clock()">{{ gmdate('i:s', $secondsLeft) }}</span></span>
                        @else
                            {{-- No time limit: an infinity mark rather than a misleading 00:00. --}}
                            <span title="{{ __('Tiada had masa') }}" style="display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:9px 16px;font-family:'Geist',sans-serif;font-weight:800;background:#EDECF2;color:#6C6F87"><x-icon name="clock" style="width:18px;height:18px" /><x-icon name="infinity" style="width:22px;height:22px" /></span>
                        @endif
                    </div>

                    {{-- Position + points + answered count --}}
                    <div style="display:flex;align-items:center;gap:9px;flex-wrap:wrap">
                        <span style="background:#17907B;color:#fff;border-radius:999px;padding:6px 15px;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px">{{ $index + 1 }} / {{ $questions->count() }}</span>
                        <span style="background:#DCEBF8;color:#2E6CA8;border-radius:999px;padding:6px 13px;font-family:'Geist',sans-serif;font-weight:800;font-size:13.5px">{{ $question->points }} {{ __('mata') }}</span>
                        <span style="margin-left:auto;font-size:13px;font-weight:700;color:var(--wl-muted)" x-text="answeredCount() + ' ' + labels.answered">0 {{ __('dijawab') }}</span>
                    </div>

                    {{-- Segmented progress: one bar per question — solid where answered, brighter on the
                         current one, faint for the rest. --}}
                    <div style="display:flex;gap:8px">
                        @for ($i = 0; $i < $questions->count(); $i++)
                            <div style="flex:1;height:8px;border-radius:999px"
                                 :style="answered[{{ $i }}] ? { background: '#17907B' } : (current === {{ $i }} ? { background: '#2BB39B' } : { background: '#DCEAF8' })"></div>
                        @endfor
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px">
                        <span style="font-family:'Geist',sans-serif;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--wl-muted)">{{ $question->isMultiple() ? __('Pilih semua jawapan betul') : __('Pilih satu jawapan') }}</span>
                        <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:20px;font-weight:800;line-height:1.35;color:var(--wl-ink)">{{ $question->localizedText() }}</h3>
                    </div>

                    {{-- Answer options, one per row. --}}
                    <div style="display:flex;flex-direction:column;gap:12px">
                        @foreach ($question->options as $oIndex => $option)
                            @php($pal = [
                                ['tint' => '#FBF0D9', 'solid' => '#D99A28'],
                                ['tint' => '#DCEBF8', 'solid' => '#3E86C9'],
                                ['tint' => '#FCE1EA', 'solid' => '#E0568A'],
                                ['tint' => '#EDE7F9', 'solid' => '#7C5CBF'],
                            ][$oIndex % 4])
                            <label class="qopt2 {{ $question->isMultiple() ? 'check' : 'radio' }}" style="--tint:{{ $pal['tint'] }};--solid:{{ $pal['solid'] }}">
                                <input type="{{ $question->isMultiple() ? 'checkbox' : 'radio' }}" name="answers[{{ $question->id }}][]" value="{{ $option->id }}" @change="touch({{ $index }})">
                                <span class="q2letter">{{ chr(65 + $oIndex) }}</span>
                                <span class="q2text">{{ $option->localizedText($question->source_locale) }}</span>
                                <span class="q2radio"><x-icon name="check" style="width:15px;height:15px" /></span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach

            {{-- Footer, in its own card below the question: Sebelum · question jumper · Seterusnya/Hantar --}}
            <div style="display:flex;align-items:center;gap:12px;background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:20px;padding:16px 20px;box-shadow:0 8px 24px var(--wl-line)">
                <button type="button" @click="previous()" :style="{ visibility: current === 0 ? 'hidden' : 'visible' }"
                        style="min-height:48px;cursor:pointer;border-radius:14px;border:1.5px solid var(--wl-line-2);background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 20px">← {{ __('Sebelum') }}</button>

                <div style="flex:1;display:flex;justify-content:center;gap:8px;flex-wrap:wrap">
                    @foreach ($questions as $index => $question)
                        <button type="button" class="qnum" @click="go({{ $index }})"
                                :class="{ 'active': current === {{ $index }}, 'answered': current !== {{ $index }} && answered[{{ $index }}] }">{{ $index + 1 }}</button>
                    @endforeach
                </div>

                <button type="submit" x-show="current === total - 1" x-cloak :disabled="submitting"
                        style="min-height:48px;border:none;cursor:pointer;border-radius:14px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 24px">
                    <span x-show="! submitting">{{ __('Hantar Jawapan') }}</span><span x-show="submitting" x-cloak>{{ __('Menghantar...') }}</span>
                </button>
                <button type="button" x-show="current < total - 1" @click="next()"
                        style="min-height:48px;border:none;cursor:pointer;border-radius:14px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 24px">{{ __('Seterusnya') }} →</button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            function quizRunner({ total, secondsLeft, labels }) {
                return {
                    total, current: 0, secondsLeft, labels, submitting: false,
                    answered: Array(total).fill(false), timer: null,
                    start() {
                        this.syncAnswered();
                        if (this.secondsLeft === null) return;
                        this.timer = setInterval(() => {
                            this.secondsLeft -= 1;
                            if (this.secondsLeft <= 0) { clearInterval(this.timer); this.autoSubmit(); }
                        }, 1000);
                    },
                    autoSubmit() { if (this.submitting) return; this.submitting = true; this.$refs.form.submit(); },
                    clock() {
                        const left = Math.max(0, this.secondsLeft ?? 0);
                        return String(Math.floor(left / 60)).padStart(2, '0') + ':' + String(left % 60).padStart(2, '0');
                    },
                    syncAnswered() {
                        this.$refs.form.querySelectorAll('section').forEach((section, index) => {
                            this.answered[index] = section.querySelectorAll('input:checked').length > 0;
                        });
                    },
                    touch(index) { this.$nextTick(() => this.syncAnswered()); },
                    answeredCount() { return this.answered.filter(Boolean).length; },
                    go(index) { this.current = Math.min(Math.max(index, 0), this.total - 1); window.scrollTo({ top: 0, behavior: 'smooth' }); },
                    next() { this.go(this.current + 1); },
                    previous() { this.go(this.current - 1); },
                };
            }
        </script>
    @endpush
</x-student-layout>
