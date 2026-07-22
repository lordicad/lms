<x-student-layout :title="$quiz->title">
    <style>
        .qopt { position:relative; display:flex; align-items:center; gap:14px; border-radius:14px; padding:16px 18px; cursor:pointer; transition:all .12s; border:1.5px solid var(--wl-line-2); background:var(--wl-surface); }
        .qopt:has(input:checked) { border-color:#17907B; background:#DCF2EE; }
        .qopt input { position:absolute; opacity:0; width:0; height:0; }
        .qdot { width:22px; height:22px; flex-shrink:0; display:grid; place-items:center; color:#fff; font-size:12px; background:var(--wl-surface); border:2px solid var(--wl-line-3); }
        .qopt.radio .qdot { border-radius:50%; }
        .qopt.check .qdot { border-radius:6px; }
        .qopt:has(input:checked) .qdot { background:#17907B; border-color:#17907B; }
        .qopt:has(input:checked) .qdot::after { content:'✓'; }
        .qletter { width:30px; height:30px; border-radius:50%; flex-shrink:0; display:grid; place-items:center; font-family:'Geist',sans-serif; font-weight:800; font-size:13px; background:#F1F0E8; color:var(--wl-muted-2); }
        .qopt:has(input:checked) .qletter { background:#17907B; color:#fff; }
        .qjump { width:46px; height:46px; border-radius:12px; cursor:pointer; font-family:'Geist',sans-serif; font-weight:800; font-size:14px; transition:all .12s; border:1.5px solid var(--wl-line-2); background:var(--wl-surface); color:#4A4B63; }
        .qjump.answered { border:1.5px solid #17907B; background:#DCF2EE; color:#0F7A68; }
        .qjump.active { border:none; background:#17907B; color:#fff; }
    </style>

    <div style="display:flex;flex-direction:column;gap:18px;max-width:820px;margin:0 auto;width:100%"
         x-data="quizRunner({ total: {{ $questions->count() }}, secondsLeft: {{ $secondsLeft === null ? 'null' : $secondsLeft }}, labels: { answered: @js(__('dijawab')) } })"
         x-init="start()">

        <div style="display:flex;align-items:center;gap:16px">
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:22px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink);flex:1;min-width:0">{{ $quiz->title }}</h2>
            @if ($secondsLeft !== null)
                {{-- Object syntax, or the binding would replace the static style outright and the
                     badge would lose its pill shape and padding. --}}
                <span :style="secondsLeft < 60
                        ? { background: '#FDE7E0', color: '#C24936' }
                        : { background: '#FEF0CE', color: '#8A6A12' }"
                      style="display:flex;align-items:center;gap:6px;border-radius:999px;padding:8px 16px;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px">🕐 <span x-text="clock()">{{ gmdate('i:s', $secondsLeft) }}</span></span>
            @endif
        </div>

        <div style="display:flex;flex-direction:column;gap:8px">
            <div style="display:flex;align-items:center;justify-content:space-between">
                <span style="font-family:'Geist',sans-serif;font-size:13.5px;font-weight:800;color:#4A4B63">{{ __('Soalan') }} <span x-text="current + 1">1</span> {{ __('daripada') }} {{ $questions->count() }}</span>
                <span style="font-size:13px;font-weight:700;color:var(--wl-muted)" x-text="answeredCount() + ' ' + labels.answered">0 {{ __('dijawab') }}</span>
            </div>
            <div style="height:8px;border-radius:999px;background:#DCEAF8;overflow:hidden">
                <div style="height:100%;border-radius:999px;background:#17907B;transition:width .25s ease-out" :style="{ width: ((current + 1) / total) * 100 + '%' }"></div>
            </div>
        </div>

        <noscript>
            <div style="background:#FEF0CE;border-radius:14px;padding:14px 18px;font-weight:700;font-size:14px;color:#8A6A12">{{ __('JavaScript perlu dihidupkan untuk menjawab kuiz ini.') }}</div>
        </noscript>

        <form method="POST" action="{{ route('kuiz.hantar', $attempt) }}" x-ref="form" @submit="submitting = true">
            @csrf
            @foreach ($questions as $index => $question)
                <section x-show="current === {{ $index }}" @if ($index > 0) x-cloak @endif
                         style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;padding:26px;display:flex;flex-direction:column;gap:18px;box-shadow:0 8px 24px var(--wl-line)">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <span style="background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 13px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800">{{ $question->points }} {{ __('mata') }}</span>
                        <span style="background:#F1F0E8;color:var(--wl-muted-2);border-radius:999px;padding:5px 13px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800">{{ $question->isMultiple() ? __('Pilih semua jawapan betul') : __('Pilih satu jawapan') }}</span>
                    </div>
                    <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:20px;font-weight:800;line-height:1.4;color:var(--wl-ink)">{{ $question->question_text }}</h3>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        @foreach ($question->options as $option)
                            <label class="qopt {{ $question->isMultiple() ? 'check' : 'radio' }}">
                                <input type="{{ $question->isMultiple() ? 'checkbox' : 'radio' }}" name="answers[{{ $question->id }}][]" value="{{ $option->id }}" @change="touch({{ $index }})">
                                <span class="qdot"></span>
                                <span class="qletter">{{ $option->letter() }}</span>
                                <span style="font-family:'Geist',sans-serif;font-weight:700;font-size:15px;color:var(--wl-ink)">{{ $option->option_text }}</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endforeach

            <div style="display:flex;align-items:center;gap:10px">
                <button type="button" x-show="current > 0" x-cloak @click="previous()" class="wl-btn-secondary"
                        style="min-height:48px;cursor:pointer;border-radius:13px;border:1.5px solid var(--wl-line-2);background:var(--wl-surface);color:var(--wl-ink);font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 22px">← {{ __('Sebelum') }}</button>
                <button type="submit" x-show="current === total - 1" x-cloak :disabled="submitting"
                        style="margin-left:auto;min-height:48px;border:none;cursor:pointer;border-radius:13px;background:#EB5E5A;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 26px">
                    <span x-show="! submitting">{{ __('Hantar Jawapan') }}</span><span x-show="submitting" x-cloak>{{ __('Menghantar...') }}</span>
                </button>
                <button type="button" x-show="current < total - 1" @click="next()" class="wl-btn-primary"
                        style="margin-left:auto;min-height:48px;border:none;cursor:pointer;border-radius:13px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:15px;padding:0 26px">{{ __('Seterusnya') }} →</button>
            </div>

            <div style="display:flex;flex-direction:column;gap:10px">
                <span style="font-family:'Geist',sans-serif;font-size:13.5px;font-weight:800;color:#4A4B63">{{ __('Lompat ke soalan') }}</span>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @foreach ($questions as $index => $question)
                        <button type="button" class="qjump" @click="go({{ $index }})"
                                :class="{ 'active': current === {{ $index }}, 'answered': current !== {{ $index }} && answered[{{ $index }}] }">{{ $index + 1 }}</button>
                    @endforeach
                </div>
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
