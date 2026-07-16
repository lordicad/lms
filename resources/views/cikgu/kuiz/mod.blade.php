<x-app-layout :title="__('Kuiz Baharu')">
    <div class="mx-auto max-w-3xl">
        <a href="{{ route('cikgu.kuiz.index') }}"
           class="inline-flex items-center gap-2 text-sm font-bold text-ink-2 hover:text-ink">
            <x-icon name="arrow-left" class="h-4 w-4" />
            {{ __('Kuiz Saya') }}
        </a>

        <h1 class="mt-4 text-3xl font-extrabold text-ink">{{ __('Kuiz Baharu') }}</h1>
        <p class="mt-2 text-ink-2">{{ __('Pilih jenis kuiz yang anda mahu cipta.') }}</p>

        <div class="mt-8 grid gap-4 md:grid-cols-2">
            <a href="{{ route('cikgu.kuiz.create', ['jenis' => 'interactive']) }}"
               class="card card-pad flex flex-col gap-3 transition-shadow hover:shadow-lift">
                <span class="flex h-14 w-14 items-center justify-center rounded-control bg-brand-soft text-brand">
                    <x-icon name="quiz" class="h-7 w-7" />
                </span>

                <span class="text-xl font-extrabold text-ink">{{ __('Bina Kuiz Interaktif') }}</span>

                <span class="text-ink-2">
                    {{ __('Bina soalan aneka pilihan terus di dalam sistem. Murid menjawab dalam talian dan mendapat markah serta-merta. Kuiz jenis ini memberi mata untuk ranking.') }}
                </span>

                <span class="mt-auto pt-3 font-bold text-brand">{{ __('Pilih ini') }}</span>
            </a>

            <a href="{{ route('cikgu.kuiz.create', ['jenis' => 'file']) }}"
               class="card card-pad flex flex-col gap-3 transition-shadow hover:shadow-lift">
                <span class="flex h-14 w-14 items-center justify-center rounded-control bg-surface-2 text-ink-2">
                    <x-icon name="file" class="h-7 w-7" />
                </span>

                <span class="text-xl font-extrabold text-ink">{{ __('Muat Naik Fail Kuiz') }}</span>

                <span class="text-ink-2">
                    {{ __('Muat naik kuiz sedia ada dalam bentuk PDF atau Word untuk dicetak. Murid hanya boleh melihat dan memuat turunnya. Tiada semakan automatik dan tiada mata ranking.') }}
                </span>

                <span class="mt-auto pt-3 font-bold text-brand">{{ __('Pilih ini') }}</span>
            </a>
        </div>
    </div>
</x-app-layout>
