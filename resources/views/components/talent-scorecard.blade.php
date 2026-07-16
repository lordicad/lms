@props(['result', 'disclaimer' => true])

{{-- Headline "Skor Bakat" + the four transparent sub-score bars. Reused on the teacher's own
     page and the admin drill-down, so both see exactly the same numbers. --}}

@php
    $r = $result;
    $bars = [
        ['key' => 'engagement', 'label' => __('Penglibatan'), 'hint' => __('Tontonan + kegemaran murid (unik)'), 'value' => number_format((int) round($r->raw['engagement']))],
        ['key' => 'quality', 'label' => __('Kualiti'), 'hint' => __('Kadar kegemaran per penonton'), 'value' => round($r->raw['quality'] * 100, 1).'%'],
        ['key' => 'outcome', 'label' => __('Hasil Pembelajaran'), 'hint' => __('Beza markah kuiz penonton vs purata bab'), 'value' => $r->raw['outcome'] === null ? '—' : (($r->raw['outcome'] > 0 ? '+' : '').$r->raw['outcome'])],
        ['key' => 'breadth', 'label' => __('Keluasan'), 'hint' => __('Bilangan bab disumbang'), 'value' => (int) $r->raw['breadth']],
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="micro text-ink-2">{{ __('Skor Bakat') }}</p>

            @if ($r->sufficient && $r->headline !== null)
                <p class="text-5xl font-extrabold tabular-nums text-ink">{{ $r->headline }}<span class="text-2xl font-bold text-ink-2">/100</span></p>
            @else
                <p class="mt-1 text-2xl font-extrabold text-ink-2">{{ __('Data belum mencukupi') }}</p>
            @endif
        </div>

        <p class="text-sm text-ink-2">
            {{ __(':n murid terlibat', ['n' => $r->engaged_students]) }}
            @unless ($r->sufficient)
                · <span class="text-warn">{{ __('perlu sekurang-kurangnya :min untuk skor', ['min' => config('talent.min_engaged_students')]) }}</span>
            @endunless
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($bars as $bar)
            <div class="rounded-card border border-line p-4">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-bold text-ink">{{ $bar['label'] }}</span>
                    <span class="text-sm font-extrabold tabular-nums text-ink">{{ $bar['value'] }}</span>
                </div>

                <div class="mt-2 h-2 overflow-hidden rounded-full bg-surface-2" role="presentation">
                    <div class="h-full rounded-full bg-brand" style="width: {{ round(($r->norm[$bar['key']] ?? 0) * 100) }}%"></div>
                </div>

                <p class="mt-1.5 text-xs text-ink-2">{{ $bar['hint'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($disclaimer)
        <x-talent-disclaimer />
    @endif
</div>
