{{-- The mandatory expectation-setting disclaimer. Shown on every talent surface, teacher + admin. --}}

<p {{ $attributes->merge(['class' => 'flex items-start gap-2 rounded-card border border-line bg-surface-2 p-4 text-sm text-ink-2']) }}>
    <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
    <span>{{ __('Skor ini mencerminkan penglibatan dalam platform (tontonan, kegemaran dan hasil kuiz murid) untuk kandungan guru sendiri sahaja. Ia satu petunjuk untuk semakan lanjut oleh MOE — bukan penilaian muktamad kualiti pengajaran, dan tidak menggunakan kiraan tontonan awam YouTube.') }}</span>
</p>
