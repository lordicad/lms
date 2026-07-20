<x-cikgu-layout :title="__('Kuiz Baru')"
    :heading="__('Kuiz Baru')"
    :sub="__('Pilih jenis kuiz yang anda mahu cipta')">

    <div class="tp-formwrap">
        <a href="{{ route('cikgu.kuiz.index') }}" class="tp-back">← {{ __('Kuiz Saya') }}</a>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:18px">
            <a href="{{ route('cikgu.kuiz.create', ['jenis' => 'interactive']) }}" class="tp-typecard">
                <span style="width:52px;height:52px;border-radius:14px;background:#DCF2EE;display:grid;place-items:center;color:#0F7A68">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1"></rect><line x1="9" y1="12" x2="15" y2="12"></line><line x1="9" y1="16" x2="13" y2="16"></line></svg>
                </span>
                <h2 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Bina Kuiz Interaktif') }}</h2>
                <p style="margin:0;font-size:14.5px;color:var(--tp-muted-2);line-height:1.55">{{ __('Bina soalan aneka pilihan terus dalam sistem. Murid menjawab dalam talian dan mendapat markah serta-merta. Kuiz jenis ini memberi mata ranking.') }}</p>
                <span class="tp-g" style="margin-top:auto;font-weight:800;font-size:14px;color:#17907B">{{ __('Pilih ini') }}</span>
            </a>

            <a href="{{ route('cikgu.kuiz.create', ['jenis' => 'file']) }}" class="tp-typecard">
                <span style="width:52px;height:52px;border-radius:14px;background:#EFEDE6;display:grid;place-items:center;color:#4A4B63">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="9" y1="13" x2="15" y2="13"></line><line x1="9" y1="17" x2="13" y2="17"></line></svg>
                </span>
                <h2 class="tp-g" style="font-size:19px;font-weight:800;color:var(--tp-ink)">{{ __('Muat Naik Fail Kuiz') }}</h2>
                <p style="margin:0;font-size:14.5px;color:var(--tp-muted-2);line-height:1.55">{{ __('Muat naik kuiz sedia ada sebagai fail PDF atau Word untuk dicetak. Murid hanya boleh melihat dan memuat turun. Tiada penandaan automatik dan tiada mata ranking.') }}</p>
                <span class="tp-g" style="margin-top:auto;font-weight:800;font-size:14px;color:#17907B">{{ __('Pilih ini') }}</span>
            </a>
        </div>
    </div>
</x-cikgu-layout>
