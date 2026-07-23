<x-cikgu-layout :title="__('Statistik:').' '.$quiz->title"
    :heading="$quiz->title"
    :sub="__('Prestasi murid pada kuiz ini')">

    <div style="display:flex;flex-direction:column;gap:20px;max-width:1000px">
        <a href="{{ route('cikgu.kuiz.index') }}" class="tp-back">← {{ __('Kuiz Saya') }}</a>

        <span style="align-self:flex-start;background:#E4EEF9;color:#2E6CA8;border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800;display:inline-flex;align-items:center;gap:6px"><x-icon :name="$subject->iconName()" class="h-[15px] w-[15px]" />{{ $subject->name }}. {{ $chapter->grade->name }}. Bab {{ $chapter->number }}</span>

        {{-- Summary --}}
        <div class="tp-stats">
            <div class="tp-stat">
                <span class="tp-stat-label">{{ __('Percubaan selesai') }}</span>
                <span class="tp-stat-value">{{ $completedCount }}</span>
            </div>
            <div class="tp-stat">
                <span class="tp-stat-label">{{ __('Purata markah') }}</span>
                <span class="tp-stat-value">{{ $averageScore }}<span style="font-size:18px;color:var(--tp-muted)">/{{ $quiz->maxScore() }}</span></span>
            </div>
            <div class="tp-stat">
                <span class="tp-stat-label">{{ __('Purata ketepatan') }}</span>
                <span class="tp-stat-value">{{ $averagePercent }}%</span>
            </div>
        </div>

        {{-- Per-question correctness --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Kadar betul setiap soalan') }}</h2>

            @if ($completedCount === 0)
                <div class="tp-empty">
                    <span style="font-size:30px">📊</span>
                    <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada data') }}</h3>
                    <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:420px">{{ __('Statistik akan muncul setelah murid mula menjawab kuiz ini.') }}</p>
                </div>
            @else
                @foreach ($quiz->questions as $index => $question)
                    @php
                        $stat = $perQuestion[$question->id] ?? null;
                        $answered = (int) ($stat->answered ?? 0);
                        $correct = (int) ($stat->correct ?? 0);
                        $rate = $answered > 0 ? (int) round($correct / $answered * 100) : 0;
                        $barBg = $rate >= 70 ? '#DCF2EE' : ($rate >= 40 ? '#FEF0CE' : '#FDE7E0');
                        $barFg = $rate >= 70 ? '#0F7A68' : ($rate >= 40 ? '#8A6A12' : '#C24936');
                    @endphp
                    <div class="tp-panelform" style="padding:20px 22px;gap:12px">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
                            <div style="min-width:0;flex:1">
                                <p style="margin:0;font-size:13px;font-weight:700;color:var(--tp-muted)">{{ __('Soalan :number', ['number' => $index + 1]) }}</p>
                                <p class="tp-g" style="margin:4px 0 0;font-weight:800;font-size:15px;color:var(--tp-ink)">{{ $question->question_text }}</p>
                            </div>
                            <span style="flex-shrink:0;background:{{ $barBg }};color:{{ $barFg }};border-radius:999px;padding:5px 13px;font-family:'Geist',sans-serif;font-size:12px;font-weight:800">{{ __(':rate% betul', ['rate' => $rate]) }}</span>
                        </div>
                        <div style="height:10px;width:100%;overflow:hidden;border-radius:999px;background:var(--tp-line)">
                            <div style="height:100%;border-radius:999px;background:{{ $barFg }};width:{{ $rate }}%"></div>
                        </div>
                        <p style="margin:0;font-size:13.5px;color:var(--tp-muted-2)">
                            {{ __(':correct daripada :answered murid menjawab dengan betul.', ['correct' => $correct, 'answered' => $answered]) }}
                            @if ($rate < 40) <span style="font-weight:800;color:#C24936">{{ __('Mungkin perlu diterangkan semula.') }}</span> @endif
                        </p>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Attempts --}}
        <div style="display:flex;flex-direction:column;gap:12px">
            <h2 class="tp-g" style="font-size:17px;font-weight:800;color:var(--tp-ink)">{{ __('Percubaan murid') }}</h2>

            @if ($attempts->isEmpty())
                <div class="tp-empty">
                    <span style="font-size:30px">👋</span>
                    <h3 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Belum ada murid mencuba kuiz ini') }}</h3>
                    <p style="margin:0;font-size:14.5px;color:var(--tp-muted);max-width:420px">{{ __('Pastikan kuiz sudah diterbitkan dan mempunyai soalan.') }}</p>
                </div>
            @else
                <div class="tp-card" style="overflow:hidden">
                    <div style="overflow-x:auto">
                        <div style="min-width:800px">
                            @php($cols = '44px minmax(0,2fr) 1fr 1fr 1fr 1fr 1fr 1.4fr')
                            <div style="display:grid;grid-template-columns:{{ $cols }};gap:12px;padding:14px 20px;border-bottom:1px solid var(--tp-line)">
                                @foreach (['#', __('Murid'), __('Tahun'), __('Markah'), __('Betul'), __('Masa'), __('Jenis'), __('Tarikh')] as $h)
                                    <span class="tp-g" style="font-size:12px;font-weight:800;color:var(--tp-muted)">{{ $h }}</span>
                                @endforeach
                            </div>
                            @foreach ($attempts as $attempt)
                                <div class="tp-row" style="display:grid;grid-template-columns:{{ $cols }};gap:12px;padding:13px 20px">
                                    {{-- Continuous number across pages: page 1 is 1-10, page 2 is 11-20. --}}
                                    <span class="tp-meta" style="font-variant-numeric:tabular-nums">{{ $attempts->firstItem() + $loop->index }}</span>
                                    <div style="display:flex;align-items:center;gap:10px;min-width:0">
                                        <span style="width:34px;height:34px;border-radius:9px;background:#DCF2EE;color:#0F7A68;display:grid;place-items:center;font-family:'Geist',sans-serif;font-weight:800;font-size:11px;flex-shrink:0">{{ $attempt->student->initials() }}</span>
                                        <span class="tp-g" style="font-weight:800;font-size:14px;color:var(--tp-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $attempt->student->name }}</span>
                                    </div>
                                    <span class="tp-meta">{{ $attempt->student->grade?->name ?? '-' }}</span>
                                    <span><span class="tp-g" style="font-weight:800;color:var(--tp-ink)">{{ $attempt->score }}/{{ $attempt->max_score }}</span> <span class="tp-meta">{{ $attempt->percentage() }}%</span></span>
                                    <span class="tp-meta">{{ $attempt->correct_count }}/{{ $attempt->question_count }}</span>
                                    <span class="tp-meta">{{ $attempt->humanDuration() }}</span>
                                    <span>
                                        @if ($attempt->counts_for_ranking)
                                            <span class="tp-tag" style="background:#DCF2EE;color:#0F7A68">{{ __('Dikira') }}</span>
                                        @else
                                            <span class="tp-tag-neutral">{{ __('Latihan') }}</span>
                                        @endif
                                    </span>
                                    <span class="tp-meta">{{ $attempt->completed_at->format('d/m/Y, g:ia') }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($attempts->hasPages())
                    <div style="margin-top:4px">{{ $attempts->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-cikgu-layout>
