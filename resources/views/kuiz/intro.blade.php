<x-dynamic-component :component="auth()->user()->isTeacher() ? 'app-layout' : 'student-layout'" :title="$quiz->title">
    @php($col = $subject->color ?: '#17907B')
    @php($tagBg = "color-mix(in oklab, {$col} 15%, #fff)")
    @php($tagColor = "color-mix(in oklab, {$col} 82%, #000)")

    <div style="display:flex;flex-direction:column;gap:16px;max-width:760px;margin:0 auto;width:100%">
        <a href="{{ route('bab.show', $chapter) }}" class="wl-back">← {{ __('Kembali') }}</a>

        @if ($isPreview)
            <div style="background:#FEF0CE;border-radius:14px;padding:14px 18px;font-weight:700;font-size:14px;color:#8A6A12">{{ __('Anda melihat kuiz ini sebagai cikgu. Guru tidak boleh mencuba kuiz, hanya menyemak.') }}</div>
        @endif

        <div style="background:var(--wl-surface);border:1px solid var(--wl-line);border-radius:22px;padding:28px;display:flex;flex-direction:column;gap:20px;box-shadow:0 8px 24px var(--wl-line)">
            <span style="align-self:flex-start;background:{{ $tagBg }};color:{{ $tagColor }};border-radius:999px;padding:5px 14px;font-family:'Geist',sans-serif;font-size:12.5px;font-weight:800"><x-subject-emoji :subject="$subject" class="text-sm" /> {{ $subject->name }}</span>
            <h2 style="margin:0;font-family:'Geist',sans-serif;font-size:26px;font-weight:800;letter-spacing:-.01em;color:var(--wl-ink)">{{ $quiz->title }}</h2>

            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px">
                <div style="background:#F6F3EC;border-radius:14px;padding:14px 18px;display:flex;flex-direction:column;gap:3px">
                    <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">{{ __('Soalan') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:var(--wl-ink)">{{ $questionCount }}</span>
                </div>
                <div style="background:#F6F3EC;border-radius:14px;padding:14px 18px;display:flex;flex-direction:column;gap:3px">
                    <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">{{ __('Markah penuh') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:var(--wl-ink)">{{ $maxScore }}</span>
                </div>
                <div style="background:#F6F3EC;border-radius:14px;padding:14px 18px;display:flex;flex-direction:column;gap:3px">
                    <span style="font-size:12.5px;font-weight:700;color:var(--wl-muted)">{{ __('Masa') }}</span>
                    <span style="font-family:'Geist',sans-serif;font-size:20px;font-weight:800;color:var(--wl-ink)">{{ $quiz->duration_minutes ? $quiz->duration_minutes.' minit' : __('Bebas') }}</span>
                </div>
            </div>

            <div style="background:#F6F3EC;border:1px solid var(--wl-line);border-radius:16px;padding:20px 22px;display:flex;flex-direction:column;gap:12px">
                <span style="font-family:'Geist',sans-serif;font-size:14.5px;font-weight:800;color:var(--wl-ink)">ℹ️ {{ __('Peraturan kuiz') }}</span>
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="color:#17907B;font-size:13px;flex-shrink:0">✓</span>
                    <span style="font-size:13.5px;color:#4A4B63;line-height:1.5">{{ __('Soalan pilihan: pilih') }} <b>{{ __('satu') }}</b> {{ __('jawapan sahaja.') }}</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="color:#17907B;font-size:13px;flex-shrink:0">✓</span>
                    <span style="font-size:13.5px;color:#4A4B63;line-height:1.5">{{ __('Soalan kotak semak: pilih') }} <b>{{ __('semua') }}</b> {{ __('jawapan yang betul. Semua mesti betul untuk mendapat markah.') }}</span>
                </div>
                <div style="display:flex;gap:10px;align-items:flex-start">
                    <span style="color:#17907B;font-size:13px;flex-shrink:0">🏆</span>
                    <span style="font-size:13.5px;color:#4A4B63;line-height:1.5"><b>{{ __('Hanya percubaan pertama') }}</b> {{ __('dikira untuk ranking. Percubaan seterusnya adalah latihan sahaja dan tidak menjejaskan mata anda.') }}</span>
                </div>
                @if ($quiz->duration_minutes)
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <span style="color:#E3A31C;font-size:13px;flex-shrink:0">⏰</span>
                        <span style="font-size:13.5px;color:#4A4B63;line-height:1.5">{{ __('Anda ada :minutes minit. Jawapan dihantar secara automatik apabila masa tamat.', ['minutes' => $quiz->duration_minutes]) }}</span>
                    </div>
                @endif
            </div>

            @if ($rankedAttempt)
                <div style="background:#DCF2EE;border:1px solid rgba(23,144,123,.25);border-radius:14px;padding:14px 18px;font-family:'Geist',sans-serif;font-size:13.5px;font-weight:700;color:#0F7A68">✓ {{ __('Percubaan pertama anda: :score/:max mata. Percubaan baharu adalah latihan.', ['score' => $rankedAttempt->score, 'max' => $rankedAttempt->max_score]) }}</div>
            @endif

            @unless ($isPreview)
                <form method="POST" action="{{ route('kuiz.mula', $quiz) }}">
                    @csrf
                    <button type="submit" class="wl-btn-primary" style="width:100%;min-height:54px;border:none;cursor:pointer;border-radius:14px;background:#17907B;color:#fff;font-family:'Geist',sans-serif;font-weight:800;font-size:16px">{{ $rankedAttempt ? __('Cuba Lagi (Latihan)') : __('Mula Kuiz') }}</button>
                </form>
            @endunless
        </div>
    </div>
</x-dynamic-component>
