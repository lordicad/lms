<x-student-layout :title="__('Keputusan')">
    @php($pct = $attempt->percentage())
    @php($good = $pct >= 80)
    @php($mid = $pct >= 50)
    @php($name = \Illuminate\Support\Str::before(auth()->user()->name, ' '))

    <div style="display:flex;flex-direction:column;gap:24px;max-width:760px;margin:0 auto;width:100%">
        {{-- Score card --}}
        <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:22px;padding:32px;display:flex;flex-direction:column;align-items:center;gap:14px;text-align:center;box-shadow:0 8px 24px rgba(46,44,80,.06)">
            <span style="font-size:40px">{{ $good ? '🎉' : ($mid ? '💪' : '📚') }}</span>
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:26px;font-weight:800;letter-spacing:-.01em;color:#28293F">{{ $good ? __('Syabas, :name!', ['name' => $name]) : __('Kerja yang baik!') }}</h2>
            <span style="font-size:14.5px;color:#8B8AA3">{{ $good ? __('Keputusan cemerlang. Teruskan usaha!') : ($mid ? __('Usaha yang baik. Cuba tingkatkan lagi!') : __('Jangan putus asa — tonton semula video dan cuba lagi.')) }}</span>
            <div style="display:flex;align-items:baseline;gap:4px;margin-top:6px">
                <span style="font-family:'Geist',sans-serif;font-size:48px;font-weight:800;color:#28293F">{{ $attempt->score }}</span>
                <span style="font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:#8B8AA3">/{{ $attempt->max_score }}</span>
            </div>
            <div style="width:70%;height:9px;border-radius:999px;background:#DCEAF8;overflow:hidden">
                <div style="height:100%;border-radius:999px;background:#17907B;width:{{ $pct }}%"></div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;width:100%;margin-top:10px">
                <div style="background:#F6F3EC;border-radius:14px;padding:14px 18px;display:flex;flex-direction:column;gap:3px;text-align:left">
                    <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">{{ __('Betul') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:#28293F">{{ $attempt->correct_count }}/{{ $attempt->question_count }}</span>
                </div>
                <div style="background:#F6F3EC;border-radius:14px;padding:14px 18px;display:flex;flex-direction:column;gap:3px;text-align:left">
                    <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">{{ __('Ketepatan') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:#28293F">{{ $pct }}%</span>
                </div>
                <div style="background:#F6F3EC;border-radius:14px;padding:14px 18px;display:flex;flex-direction:column;gap:3px;text-align:left">
                    <span style="font-size:12.5px;font-weight:700;color:#8B8AA3">{{ __('Masa') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:#28293F">{{ $attempt->humanDuration() }}</span>
                </div>
            </div>
            <div style="width:100%;background:#DCF2EE;border:1px solid rgba(23,144,123,.25);border-radius:14px;padding:14px 18px;display:flex;align-items:center;gap:10px;margin-top:4px">
                <span style="color:#0F7A68;font-size:15px">✓</span>
                <span style="font-family:'Geist',sans-serif;font-size:13.5px;font-weight:700;color:#0F7A68;text-align:left">{{ $attempt->counts_for_ranking ? __('Ini percubaan pertama anda, jadi :score mata dikira untuk ranking.', ['score' => $attempt->score]) : __('Ini latihan semula. Markah ini tidak menjejaskan ranking anda.') }}</span>
            </div>
            <div style="display:flex;gap:12px;margin-top:8px;flex-wrap:wrap;justify-content:center">
                <a href="{{ route('kuiz.intro', $quiz) }}" class="wl-btn-secondary" style="min-height:48px;display:inline-flex;align-items:center;border-radius:13px;border:1.5px solid rgba(46,44,80,.12);background:#fff;color:#28293F;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px;text-decoration:none">{{ __('Cuba Lagi (Latihan)') }}</a>
                <a href="{{ route('ranking.index') }}" class="wl-btn-primary" style="min-height:48px;display:inline-flex;align-items:center;border-radius:13px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 22px;text-decoration:none">🏆&nbsp; {{ __('Lihat Ranking') }}</a>
            </div>
        </div>

        {{-- Answer review --}}
        <div style="display:flex;flex-direction:column;gap:14px">
            <h3 style="margin:0;font-family:'Geist',sans-serif;font-size:18px;font-weight:800;color:#28293F">{{ __('Semakan Jawapan') }}</h3>
            @foreach ($questions as $index => $question)
                @php($answer = $answersByQuestion[$question->id] ?? null)
                @php($ok = $answer?->is_correct)
                <div style="background:#fff;border:1px solid rgba(46,44,80,.08);border-radius:20px;padding:24px;display:flex;flex-direction:column;gap:14px;box-shadow:0 4px 16px rgba(46,44,80,.04)">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
                        <span style="font-family:'Geist',sans-serif;font-size:13px;font-weight:800;color:#8B8AA3">{{ __('Soalan') }} {{ $index + 1 }}</span>
                        @if ($ok)
                            <span style="border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;background:#DCF2EE;color:#0F7A68">✓ {{ __('Betul.') }} {{ $answer->points_awarded }} {{ __('mata') }}</span>
                        @else
                            <span style="border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;background:#FDE7E0;color:#C24936">✗ {{ __('Salah. 0 mata') }}</span>
                        @endif
                    </div>
                    <h4 style="margin:0;font-family:'Geist',sans-serif;font-size:17px;font-weight:800;line-height:1.4;color:#28293F">{{ $question->question_text }}</h4>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        @foreach ($question->options as $option)
                            @php($sel = $answer?->selected($option->id) ?? false)
                            @php($isC = $option->is_correct)
                            @php($border = 'rgba(46,44,80,.1)')
                            @php($bg = '#fff')
                            @php($tagTxt = '')
                            @php($tagStyle = '')
                            @php($letterStyle = 'background:#F1F0E8;color:#6C6F87')
                            @if ($sel && $isC)
                                @php($border = '#17907B') @php($bg = '#DCF2EE') @php($tagTxt = __('Jawapan anda')) @php($tagStyle = 'background:#17907B;color:#fff') @php($letterStyle = 'background:#17907B;color:#fff')
                            @elseif ($sel && ! $isC)
                                @php($border = '#EB5E5A') @php($bg = '#FDE7E0') @php($tagTxt = __('Jawapan anda')) @php($tagStyle = 'background:#EB5E5A;color:#fff') @php($letterStyle = 'background:#EB5E5A;color:#fff')
                            @elseif ($isC)
                                @php($border = '#17907B') @php($tagTxt = __('Jawapan betul')) @php($tagStyle = 'background:#DCF2EE;color:#0F7A68') @php($letterStyle = 'background:#17907B;color:#fff')
                            @endif
                            <div style="display:flex;align-items:center;gap:14px;border-radius:14px;padding:14px 18px;border:1.5px solid {{ $border }};background:{{ $bg }}">
                                <span style="width:30px;height:30px;border-radius:50%;flex-shrink:0;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:13px;{{ $letterStyle }}">{{ $option->letter() }}</span>
                                <span style="font-family:'Geist',sans-serif;font-weight:700;font-size:14.5px;color:#28293F;flex:1">{{ $option->option_text }}</span>
                                @if ($tagTxt)
                                    <span style="border-radius:999px;padding:4px 12px;font-family:'Geist',sans-serif;font-size:11.5px;font-weight:800;{{ $tagStyle }}">{{ $tagTxt }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <a href="{{ route('bab.show', $quiz->chapter) }}" class="wl-btn-secondary" style="align-self:center;min-height:48px;display:inline-flex;align-items:center;border-radius:13px;border:1.5px solid rgba(46,44,80,.12);background:#fff;color:#28293F;font-family:'Geist',sans-serif;font-weight:800;font-size:14.5px;padding:0 24px;text-decoration:none">← {{ __('Kembali ke Bab') }}</a>
    </div>
</x-student-layout>
